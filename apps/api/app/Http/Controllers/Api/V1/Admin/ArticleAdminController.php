<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Cms\SiteContext;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleTranslation;
use App\Models\ContentRevision;
use App\Models\SeoMetadata;
use App\Models\Tag;
use App\Support\AuditLogger;
use App\Support\RichTextSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ArticleAdminController extends Controller
{
    public function __construct(
        protected SiteContext $context,
        protected RichTextSanitizer $sanitizer,
        protected AuditLogger $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $locale = $this->context->normalizeLocale($request->query('locale'));
        $perPage = min(100, max(5, (int) $request->query('per_page', 20)));

        $query = Article::forSite($site)
            ->with(['translations', 'category', 'author', 'featuredMedia'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->query('category'), fn ($q, $category) => $q->whereHas('category', fn ($cat) => $cat->where('slug', $category)))
            ->when($request->query('q'), fn ($q, $search) => $q->whereHas('translations', fn ($translation) => $translation
                ->where(fn ($inner) => $inner->where('title', 'like', '%'.$search.'%')->orWhere('slug', 'like', '%'.$search.'%'))))
            ->orderByDesc('updated_at');

        $paginator = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'data' => collect($paginator->items())->map(function (Article $article) use ($site) {
                $translation = $article->translations->first();

                return [
                    'id' => $article->id,
                    'title' => $translation?->title,
                    'slug' => $translation?->slug,
                    'type' => $article->type,
                    'status' => $article->status,
                    'category' => $article->category?->labelFor($site->default_locale),
                    'author' => $article->author?->name,
                    'reading_time' => $article->reading_time,
                    'published_at' => $article->published_at?->toIso8601String(),
                    'updated_at' => $article->updated_at?->toIso8601String(),
                    'image' => $article->featuredMedia?->url,
                    'locales' => $article->translations->pluck('locale')->values()->all(),
                ];
            })->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
            ],
            'filters' => [
                'categories' => ArticleCategory::forSite($site)->orderBy('sort')->get()->map(fn ($category) => [
                    'slug' => $category->slug,
                    'name' => $category->labelFor($locale),
                ])->all(),
                'types' => Article::TYPES,
            ],
        ]);
    }

    public function show(Request $request, Article $article): JsonResponse
    {
        $this->ensureSite($article);
        abort_unless($request->user()->hasPermission('articles.view', $article->site_id), 403);
        $locale = $this->context->normalizeLocale($request->query('locale'));

        return response()->json(['data' => $this->payload($article->load(['translations', 'category', 'author', 'featuredMedia', 'tags', 'seo.ogImage', 'revisions.user']), $locale, $request->user())]);
    }

    public function store(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $data = $this->validated($request, true);
        $data['status'] = $data['status'] ?? 'draft';
        $locale = $this->context->normalizeLocale($data['locale']);

        abort_unless(in_array($locale, $site->enabled_locales, true), 422, 'That locale is not enabled.');
        $this->assertSlug($site->id, $locale, $this->slugify($data['slug'] ?? $data['title']));

        if ($data['status'] === 'published' && ! $request->user()->hasPermission('articles.publish', $site->id)) {
            abort(403, 'You are not allowed to publish articles.');
        }

        $article = DB::transaction(function () use ($site, $request, $data, $locale) {
            $article = Article::create([
                'site_id' => $site->id,
                'category_id' => $data['category_id'] ?? null,
                'type' => $data['type'],
                'status' => $data['status'],
                'author_id' => $request->user()->id,
                'featured_media_id' => $data['featured_media_id'] ?? null,
                'is_featured' => (bool) ($data['is_featured'] ?? false),
                'reading_time' => $this->readingTime($data['body'] ?? []),
                'published_at' => $data['status'] === 'published' ? now() : null,
                'updated_content_at' => now(),
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            $article->translations()->create([
                'site_id' => $site->id,
                'locale' => $locale,
                'title' => $data['title'],
                'slug' => $this->slugify($data['slug'] ?? $data['title']),
                'excerpt' => $data['excerpt'] ?? '',
                'body' => $data['body'] ?? ['type' => 'doc', 'content' => []],
                'toc' => $this->toc($data['body'] ?? []),
            ]);

            $this->syncTags($article, $site->id, $data['tags'] ?? []);
            $this->saveSeo($article, $locale, $data['seo'] ?? []);
            $this->revision($article, $locale, $request->user()->id, 'Initial draft');
            $this->audit->log('article.created', "Created article \"{$data['title']}\"", [
                'subject_type' => 'article', 'subject_id' => $article->id,
            ]);

            return $article;
        });

        return response()->json(['data' => $this->payload($article->fresh(['translations', 'category', 'author', 'tags', 'featuredMedia']), $locale, $request->user()), 'message' => 'Article created.'], 201);
    }

    public function update(Request $request, Article $article): JsonResponse
    {
        $this->ensureSite($article);
        $this->authorizeEdit($article, $request->user());
        $data = $this->validated($request, false);
        $locale = $this->context->normalizeLocale($data['locale'] ?? $request->query('locale'));

        if ($data['status'] === 'published' && ! $request->user()->hasPermission('articles.publish', $article->site_id)) {
            throw ValidationException::withMessages(['status' => 'You are not allowed to publish articles. Submit for review instead.']);
        }

        $translation = $article->translations()->where('locale', $locale)->first();

        if (! $translation) {
            throw ValidationException::withMessages(['locale' => 'Add this translation before editing it.']);
        }

        $slug = $this->slugify($data['slug'] ?? $translation->slug);
        $this->assertSlug($article->site_id, $locale, $slug, $article->id);

        DB::transaction(function () use ($article, $request, $data, $locale, $slug) {
            $article->update([
                'category_id' => $data['category_id'] ?? $article->category_id,
                'type' => $data['type'] ?? $article->type,
                'status' => $data['status'] ?? $article->status,
                'featured_media_id' => $data['featured_media_id'] ?? $article->featured_media_id,
                'is_featured' => $data['is_featured'] ?? $article->is_featured,
                'reading_time' => $this->readingTime($data['body'] ?? $translation->body),
                'published_at' => ($data['status'] ?? $article->status) === 'published'
                    ? ($article->published_at ?? now())
                    : $article->published_at,
                'updated_content_at' => now(),
                'updated_by' => $request->user()->id,
            ]);

            $article->translations()->where('locale', $locale)->update([
                'title' => $data['title'] ?? $translation->title,
                'slug' => $slug,
                'excerpt' => $data['excerpt'] ?? $translation->excerpt,
                'body' => $data['body'] ?? $translation->body,
                'toc' => $this->toc($data['body'] ?? $translation->body),
            ]);

            if (array_key_exists('tags', $data)) {
                $this->syncTags($article, $article->site_id, $data['tags']);
            }

            if (array_key_exists('seo', $data)) {
                $this->saveSeo($article, $locale, $data['seo']);
            }

            $this->revision($article, $locale, $request->user()->id, 'Content updated');
            $this->audit->log('article.updated', 'Updated article', [
                'subject_type' => 'article', 'subject_id' => $article->id,
            ]);
        });

        return response()->json(['data' => $this->payload($article->fresh(['translations', 'category', 'author', 'tags', 'featuredMedia', 'seo.ogImage']), $locale, $request->user()), 'message' => 'Article saved.']);
    }

    public function destroy(Request $request, Article $article): JsonResponse
    {
        $this->ensureSite($article);
        abort_unless($request->user()->hasPermission('articles.delete', $article->site_id), 403);
        $article->delete();
        $this->audit->log('article.deleted', 'Deleted article', ['subject_type' => 'article', 'subject_id' => $article->id]);

        return response()->json(['message' => 'Article deleted.']);
    }

    public function categories(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();

        return response()->json([
            'data' => ArticleCategory::forSite($site)->orderBy('sort')->get()->map(fn ($category) => [
                'id' => $category->id,
                'slug' => $category->slug,
                'name' => $category->name,
                'articles_count' => $category->articles()->count(),
            ])->all(),
        ]);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $data = $request->validate([
            'name' => ['required', 'array'],
            'name.th' => ['required', 'string', 'max:80'],
            'name.en' => ['required', 'string', 'max:80'],
            'slug' => ['nullable', 'string', 'max:80'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $category = ArticleCategory::create([
            'site_id' => $site->id,
            'slug' => $this->slugify($data['slug'] ?? $data['name']['en']),
            'name' => $data['name'],
            'sort' => $data['sort'] ?? 0,
        ]);

        return response()->json(['data' => $category], 201);
    }

    protected function validated(Request $request, bool $creating): array
    {
        return $request->validate([
            'locale' => [$creating ? 'required' : 'sometimes', 'in:th,en,zh-CN'],
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:1200'],
            'body' => ['nullable', 'array'],
            'type' => [$creating ? 'required' : 'sometimes', 'in:'.implode(',', Article::TYPES)],
            'status' => ['nullable', 'in:draft,published,archived'],
            'category_id' => ['nullable', 'integer'],
            'featured_media_id' => ['nullable', 'integer'],
            'is_featured' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:60'],
            'seo' => ['nullable', 'array'],
        ]);
    }

    protected function payload(Article $article, string $locale, $user): array
    {
        $translation = $article->translations->firstWhere('locale', $locale) ?? $article->translations->first();
        $seo = $article->seo->firstWhere('locale', $translation?->locale);

        return [
            'id' => $article->id,
            'locale' => $translation?->locale,
            'title' => $translation?->title,
            'slug' => $translation?->slug,
            'excerpt' => $translation?->excerpt,
            'body' => $translation?->body,
            'body_html' => $this->sanitizer->renderTiptapDocument($translation?->body),
            'toc' => $translation?->toc ?? [],
            'type' => $article->type,
            'status' => $article->status,
            'is_featured' => $article->is_featured,
            'reading_time' => $article->reading_time,
            'view_count' => $article->view_count,
            'published_at' => $article->published_at?->toIso8601String(),
            'updated_at' => $article->updated_at?->toIso8601String(),
            'author' => $article->author?->only(['id', 'name']),
            'category' => $article->category?->only(['id', 'slug']),
            'category_name' => $article->category?->labelFor($locale),
            'featured_media' => $article->featuredMedia?->toPublicArray($locale),
            'tags' => $article->tags->map(fn (Tag $tag) => ['id' => $tag->id, 'slug' => $tag->slug, 'name' => $tag->name[$locale] ?? $tag->name['en'] ?? $tag->slug])->values()->all(),
            'translations' => $article->translations->map(fn ($item) => ['locale' => $item->locale, 'title' => $item->title, 'slug' => $item->slug])->values()->all(),
            'seo' => $seo ? [
                'title' => $seo->title,
                'description' => $seo->description,
                'canonical' => $seo->canonical,
                'focus_keyword' => $seo->focus_keyword,
                'og_title' => $seo->og_title,
                'og_description' => $seo->og_description,
                'og_image' => $seo->ogImage?->toPublicArray($locale),
                'robots' => $seo->robots,
            ] : null,
            'revisions' => $article->revisions->take(10)->map(fn ($revision) => [
                'id' => $revision->id,
                'version' => $revision->version,
                'message' => $revision->message,
                'user' => $revision->user?->name,
                'created_at' => $revision->created_at?->toIso8601String(),
            ])->values()->all(),
            'capabilities' => [
                'edit' => $user->hasPermission('articles.edit_all', $article->site_id)
                    || ($user->hasPermission('articles.edit_own', $article->site_id) && $article->author_id === $user->id),
                'publish' => $user->hasPermission('articles.publish', $article->site_id),
                'delete' => $user->hasPermission('articles.delete', $article->site_id),
            ],
        ];
    }

    protected function toc(array $document): array
    {
        $toc = [];
        $content = $document['content'] ?? [];

        foreach ($content as $node) {
            if (($node['type'] ?? null) !== 'heading') {
                continue;
            }

            $level = (int) ($node['attrs']['level'] ?? 2);
            $text = $this->nodeText($node);

            if ($text !== '') {
                $toc[] = ['level' => $level, 'text' => $text, 'id' => Str::slug($text) ?: 'section-'.(count($toc) + 1)];
            }
        }

        return $toc;
    }

    protected function nodeText(array $node): string
    {
        $text = $node['text'] ?? '';
        foreach ($node['content'] ?? [] as $child) {
            $text .= ' '.$this->nodeText($child);
        }

        return trim($text);
    }

    protected function readingTime(?array $document): int
    {
        if (! $document) {
            return 1;
        }

        return $this->sanitizer->readingTime($this->sanitizer->renderTiptapDocument($document));
    }

    protected function syncTags(Article $article, int $siteId, array $tags): void
    {
        $ids = collect($tags)->filter()->map(function (string $tag) use ($siteId) {
            $slug = $this->slugify($tag);

            return Tag::firstOrCreate(
                ['site_id' => $siteId, 'slug' => $slug],
                ['name' => ['en' => $tag, 'th' => $tag, 'zh-CN' => $tag]]
            )->id;
        });

        $article->tags()->sync($ids);
    }

    protected function saveSeo(Article $article, string $locale, array $seo): void
    {
        SeoMetadata::updateOrCreate(
            ['seoable_type' => 'article', 'seoable_id' => $article->id, 'locale' => $locale],
            [
                'site_id' => $article->site_id,
                'title' => $seo['title'] ?? null,
                'description' => $seo['description'] ?? null,
                'canonical' => $seo['canonical'] ?? null,
                'focus_keyword' => $seo['focus_keyword'] ?? null,
                'og_title' => $seo['og_title'] ?? null,
                'og_description' => $seo['og_description'] ?? null,
                'og_image_id' => $seo['og_image_id'] ?? null,
                'robots' => $seo['robots'] ?? 'index,follow',
                'schema_type' => $seo['schema_type'] ?? 'Article',
            ]
        );
    }

    protected function revision(Article $article, string $locale, int $userId, string $message): void
    {
        $translation = $article->translations()->where('locale', $locale)->first();
        $version = (int) ContentRevision::where('revisionable_type', 'article')->where('revisionable_id', $article->id)->where('locale', $locale)->max('version') + 1;

        ContentRevision::create([
            'site_id' => $article->site_id,
            'revisionable_type' => 'article',
            'revisionable_id' => $article->id,
            'locale' => $locale,
            'user_id' => $userId,
            'version' => $version,
            'snapshot' => [
                'title' => $translation?->title,
                'slug' => $translation?->slug,
                'excerpt' => $translation?->excerpt,
                'body' => $translation?->body,
                'toc' => $translation?->toc,
            ],
            'message' => $message,
            'created_at' => now(),
        ]);
    }

    protected function assertSlug(int $siteId, string $locale, string $slug, ?int $exceptArticleId = null): void
    {
        $query = ArticleTranslation::where('site_id', $siteId)->where('locale', $locale)->where('slug', $slug);

        if ($exceptArticleId) {
            $query->where('article_id', '!=', $exceptArticleId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages(['slug' => 'That slug is already used by another article in this locale.']);
        }
    }

    protected function slugify(string $value): string
    {
        $slug = preg_replace('/[^\p{L}\p{N}\s\-_]+/u', '', trim($value)) ?? '';
        $slug = preg_replace('/[\s_]+/u', '-', $slug) ?? '';
        $slug = trim(preg_replace('/-+/u', '-', $slug) ?? '', '-');

        return mb_strtolower($slug !== '' ? $slug : 'article-'.Str::lower(Str::random(6)));
    }

    protected function ensureSite(Article $article): void
    {
        abort_unless($article->site_id === $this->context->id(), 404);
    }

    protected function authorizeEdit(Article $article, $user): void
    {
        abort_unless(
            $user->hasPermission('articles.edit_all', $article->site_id)
            || ($user->hasPermission('articles.edit_own', $article->site_id) && $article->author_id === $user->id),
            403
        );
    }
}
