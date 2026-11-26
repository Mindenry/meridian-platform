<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Cms\Blocks\BlockRegistry;
use App\Cms\PageService;
use App\Cms\SiteContext;
use App\Cms\WorkflowService;
use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\PageRevision;
use App\Models\WorkflowInstance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PagesController extends Controller
{
    public function __construct(
        protected SiteContext $context,
        protected PageService $pages,
        protected WorkflowService $workflow,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $locale = $this->context->normalizeLocale($request->query('locale'));
        $perPage = min(100, max(5, (int) $request->query('per_page', 20)));

        $query = Page::forSite($site)
            ->with(['translations', 'author'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('q'), fn ($q, $search) => $q->whereHas('translations', fn ($translation) => $translation
                ->where(fn ($inner) => $inner->where('title', 'like', '%'.$search.'%')->orWhere('slug', 'like', '%'.$search.'%'))))
            ->when($request->query('locale'), fn ($q, $loc) => $q->whereHas('translations', fn ($translation) => $translation->where('locale', $loc)))
            ->orderByDesc('updated_at');

        $paginator = $query->paginate($perPage)->withQueryString();

        $rows = collect($paginator->items())->map(function (Page $page) use ($locale, $site) {
            $translation = $page->translations->firstWhere('locale', $locale)
                ?? $page->translations->firstWhere('locale', $site->default_locale)
                ?? $page->translations->first();

            return [
                'id' => $page->id,
                'title' => $translation?->title ?? 'Untitled page',
                'slug' => $translation?->slug,
                'status' => $page->status,
                'is_home' => $page->is_home,
                'author' => $page->author?->name,
                'updated_at' => $page->updated_at?->toIso8601String(),
                'published_at' => $page->published_at?->toIso8601String(),
                'scheduled_at' => $page->scheduled_at?->toIso8601String(),
                'lock_version' => $page->lock_version,
                'locales' => $page->translations->map(fn ($item) => [
                    'locale' => $item->locale,
                    'title' => $item->title,
                    'slug' => $item->slug,
                ])->values()->all(),
                'missing_locales' => array_values(array_diff($site->enabled_locales, $page->translations->pluck('locale')->all())),
            ];
        });

        $counts = Page::forSite($site)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'data' => $rows,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'counts' => $counts,
        ]);
    }

    public function show(Request $request, Page $page): JsonResponse
    {
        $this->ensureSite($page);
        abort_unless($request->user()->hasPermission('pages.view', $page->site_id), 403);

        $locale = $this->context->normalizeLocale($request->query('locale'));
        $page->load(['translations', 'author', 'featuredMedia', 'seo.ogImage', 'revisions.user', 'parent']);

        return response()->json(['data' => $this->pagePayload($page, $locale, $request->user())]);
    }

    public function store(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $data = $this->validated($request, true);
        $locale = $this->context->normalizeLocale($data['locale']);

        abort_unless(in_array($locale, $site->enabled_locales, true), 422, 'That locale is not enabled for this website.');

        if (! empty($data['parent_id'])) {
            abort_unless(Page::forSite($site)->whereKey($data['parent_id'])->exists(), 422, 'The parent page is invalid.');
        }

        if (! empty($data['featured_media_id'])) {
            abort_unless(MediaAsset::forSite($site)->whereKey($data['featured_media_id'])->exists(), 422, 'The featured image is invalid.');
        }

        if (! empty($data['is_home'])) {
            Page::forSite($site)->where('is_home', true)->update(['is_home' => false]);
        }

        $page = $this->pages->create($site, $request->user(), array_merge($data, ['locale' => $locale]));
        $page->load(['translations', 'author', 'featuredMedia', 'seo.ogImage', 'revisions.user']);

        return response()->json(['data' => $this->pagePayload($page, $locale, $request->user()), 'message' => 'Page created.'], 201);
    }

    public function update(Request $request, Page $page): JsonResponse
    {
        $this->ensureSite($page);
        $this->authorizeEdit($page, $request->user());
        $data = $this->validated($request, false);
        $locale = $this->context->normalizeLocale($data['locale'] ?? $request->query('locale'));

        if (isset($data['parent_id']) && $data['parent_id'] == $page->id) {
            throw ValidationException::withMessages(['parent_id' => 'A page cannot be its own parent.']);
        }

        if (isset($data['featured_media_id'])) {
            abort_unless(MediaAsset::forSite($page->site_id)->whereKey($data['featured_media_id'])->exists(), 422, 'The featured image is invalid.');
        }

        $page = $this->pages->update($page, $request->user(), array_merge($data, ['locale' => $locale]));
        $page->load(['translations', 'author', 'featuredMedia', 'seo.ogImage', 'revisions.user']);

        return response()->json(['data' => $this->pagePayload($page, $locale, $request->user()), 'message' => 'Page saved.']);
    }

    public function destroy(Request $request, Page $page): JsonResponse
    {
        $this->ensureSite($page);
        abort_unless($request->user()->hasPermission('pages.delete', $page->site_id), 403);
        $this->pages->delete($page, $request->user());

        return response()->json(['message' => 'Page deleted.']);
    }

    public function duplicate(Request $request, Page $page): JsonResponse
    {
        $this->ensureSite($page);
        $this->authorizeEdit($page, $request->user());
        $copy = $this->pages->duplicate($page, $request->user());

        return response()->json(['data' => ['id' => $copy->id], 'message' => 'Page duplicated as a draft.'], 201);
    }

    public function addTranslation(Request $request, Page $page): JsonResponse
    {
        $this->ensureSite($page);
        $this->authorizeEdit($page, $request->user());
        $site = $page->site;

        $data = $request->validate([
            'locale' => ['required', 'in:th,en,zh-CN'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
        ]);

        abort_unless(in_array($data['locale'], $site->enabled_locales, true), 422, 'That locale is not enabled.');
        abort_if($page->translations()->where('locale', $data['locale'])->exists(), 422, 'That translation already exists.');

        $slug = $this->pages->slugify($data['slug'] ?? $data['title']);
        $this->pages->assertSlugAvailable($site, $data['locale'], $slug, $page->id);

        $page->translations()->create([
            'site_id' => $site->id,
            'locale' => $data['locale'],
            'title' => $data['title'],
            'slug' => $slug,
            'excerpt' => null,
            'blocks_version' => BlockRegistry::SCHEMA_VERSION,
        ]);

        $this->pages->createRevision($page, $data['locale'], $request->user(), 'Translation added');

        return response()->json(['message' => 'Translation added.', 'data' => ['locale' => $data['locale'], 'slug' => $slug]], 201);
    }

    public function blocks(Request $request, Page $page): JsonResponse
    {
        $this->ensureSite($page);
        $this->authorizeEdit($page, $request->user());

        $data = $request->validate([
            'locale' => ['required', 'in:th,en,zh-CN'],
            'blocks' => ['required', 'array'],
            'lock_version' => ['nullable', 'integer'],
        ]);

        if (isset($data['lock_version']) && (int) $data['lock_version'] !== (int) $page->lock_version) {
            throw ValidationException::withMessages(['lock_version' => 'This page changed in another session. Reload before saving.']);
        }

        $page = $this->pages->saveBlocks($page, $data['locale'], $data['blocks'], $request->user());
        $page->load(['translations', 'author', 'featuredMedia', 'seo.ogImage', 'revisions.user']);

        return response()->json(['data' => $this->pagePayload($page, $data['locale'], $request->user()), 'message' => 'Layout saved.']);
    }

    public function seo(Request $request, Page $page): JsonResponse
    {
        $this->ensureSite($page);
        $this->authorizeEdit($page, $request->user());

        $data = $request->validate([
            'locale' => ['required', 'in:th,en,zh-CN'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'canonical' => ['nullable', 'string', 'max:1000'],
            'focus_keyword' => ['nullable', 'string', 'max:120'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:500'],
            'og_image_id' => ['nullable', 'integer'],
            'robots' => ['nullable', 'string', 'max:60'],
            'schema_type' => ['nullable', 'string', 'max:60'],
        ]);

        $seo = $this->pages->saveSeo($page, $data['locale'], $data);
        $page->lock_version++;
        $page->save();

        return response()->json(['data' => $this->seoPayload($seo), 'message' => 'SEO saved.']);
    }

    public function revisions(Request $request, Page $page): JsonResponse
    {
        $this->ensureSite($page);
        abort_unless($request->user()->hasPermission('pages.view', $page->site_id), 403);
        $locale = $this->context->normalizeLocale($request->query('locale'));

        $revisions = $page->revisions()
            ->where('locale', $locale)
            ->with('user')
            ->orderByDesc('version')
            ->limit(50)
            ->get()
            ->map(fn (PageRevision $revision) => [
                'id' => $revision->id,
                'version' => $revision->version,
                'message' => $revision->message,
                'user' => $revision->user?->name,
                'created_at' => $revision->created_at?->toIso8601String(),
                'snapshot' => $revision->snapshot,
            ]);

        return response()->json(['data' => $revisions]);
    }

    public function restoreRevision(Request $request, Page $page, PageRevision $revision): JsonResponse
    {
        $this->ensureSite($page);
        $this->authorizeEdit($page, $request->user());
        abort_unless($revision->page_id === $page->id, 404);

        $page = $this->pages->restoreRevision($page, $revision, $request->user());
        $page->load(['translations', 'author', 'featuredMedia', 'seo.ogImage', 'revisions.user']);

        return response()->json([
            'data' => $this->pagePayload($page, $revision->locale, $request->user()),
            'message' => 'Version restored.',
        ]);
    }

    public function previewToken(Request $request, Page $page): JsonResponse
    {
        $this->ensureSite($page);
        abort_unless($request->user()->hasPermission('pages.view', $page->site_id), 403);
        $locale = $this->context->normalizeLocale($request->query('locale'));
        $token = $this->pages->previewToken($page, $locale, $request->user());
        $route = config('meridian.locales')[$locale]['route'] ?? strtolower($locale);

        return response()->json([
            'data' => [
                'token' => $token->token,
                'expires_at' => $token->expires_at->toIso8601String(),
                'url' => rtrim((string) config('meridian.website_url'), '/').'/'.$route.'/preview/'.$token->token,
            ],
        ]);
    }

    public function submit(Request $request, Page $page): JsonResponse
    {
        $this->ensureSite($page);
        $this->authorizeEdit($page, $request->user());
        $page = $this->workflow->submit($page, $request->user(), $request->input('note'));

        return response()->json(['data' => $this->pagePayload($page->load('translations'), $page->translations->first()?->locale, $request->user()), 'message' => 'Submitted for review.']);
    }

    public function approve(Request $request, Page $page): JsonResponse
    {
        $this->ensureSite($page);
        abort_unless($request->user()->hasPermission('pages.approve', $page->site_id), 403);
        $page = $this->workflow->approve($page, $request->user(), $request->input('note'));

        return response()->json(['data' => $this->pagePayload($page->load('translations'), $page->translations->first()?->locale, $request->user()), 'message' => 'Page approved.']);
    }

    public function requestChanges(Request $request, Page $page): JsonResponse
    {
        $this->ensureSite($page);
        abort_unless($request->user()->hasPermission('pages.approve', $page->site_id), 403);
        $note = (string) $request->input('note', '');
        $page = $this->workflow->requestChanges($page, $request->user(), $note);

        return response()->json(['data' => $this->pagePayload($page->load('translations'), $page->translations->first()?->locale, $request->user()), 'message' => 'Changes requested.']);
    }

    public function publish(Request $request, Page $page): JsonResponse
    {
        $this->ensureSite($page);
        abort_unless($request->user()->hasPermission('pages.publish', $page->site_id), 403);
        $page = $this->workflow->publish($page, $request->user());

        return response()->json(['data' => $this->pagePayload($page->load('translations'), $page->translations->first()?->locale, $request->user()), 'message' => 'Page published.']);
    }

    public function schedule(Request $request, Page $page): JsonResponse
    {
        $this->ensureSite($page);
        abort_unless($request->user()->hasPermission('pages.publish', $page->site_id), 403);
        $data = $request->validate(['publish_at' => ['required', 'date', 'after:now']]);
        $page = $this->workflow->schedule($page, $request->user(), Carbon::parse($data['publish_at']));

        return response()->json(['data' => $this->pagePayload($page->load('translations'), $page->translations->first()?->locale, $request->user()), 'message' => 'Publication scheduled.']);
    }

    public function cancelSchedule(Request $request, Page $page): JsonResponse
    {
        $this->ensureSite($page);
        abort_unless($request->user()->hasPermission('pages.publish', $page->site_id), 403);
        $page = $this->workflow->cancelSchedule($page, $request->user());

        return response()->json(['data' => $this->pagePayload($page->load('translations'), $page->translations->first()?->locale, $request->user()), 'message' => 'Schedule cancelled.']);
    }

    public function unpublish(Request $request, Page $page): JsonResponse
    {
        $this->ensureSite($page);
        abort_unless($request->user()->hasPermission('pages.publish', $page->site_id), 403);
        $page = $this->workflow->unpublish($page, $request->user());

        return response()->json(['data' => $this->pagePayload($page->load('translations'), $page->translations->first()?->locale, $request->user()), 'message' => 'Page unpublished.']);
    }

    public function bulk(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'action' => ['required', 'in:publish,archive,delete'],
        ]);

        $results = [];

        foreach (Page::forSite($site)->whereIn('id', $data['ids'])->get() as $page) {
            try {
                if ($data['action'] === 'delete') {
                    abort_unless($request->user()->hasPermission('pages.delete', $site->id), 403);
                    $this->pages->delete($page, $request->user());
                } elseif ($data['action'] === 'publish') {
                    abort_unless($request->user()->hasPermission('pages.publish', $site->id), 403);
                    $this->workflow->publish($page, $request->user());
                } else {
                    $this->authorizeEdit($page, $request->user());
                    $page->update(['status' => Page::STATUS_ARCHIVED, 'updated_by' => $request->user()->id]);
                }

                $results[] = ['id' => $page->id, 'ok' => true];
            } catch (\Throwable $e) {
                $results[] = ['id' => $page->id, 'ok' => false, 'message' => $e->getMessage()];
            }
        }

        return response()->json(['data' => $results, 'message' => 'Bulk action processed.']);
    }

    public function blockTypes(): JsonResponse
    {
        return response()->json(['data' => BlockRegistry::publicDefinitions(), 'schema_version' => BlockRegistry::SCHEMA_VERSION]);
    }

    protected function validated(Request $request, bool $creating): array
    {
        return $request->validate([
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'locale' => [$creating ? 'required' : 'sometimes', 'in:th,en,zh-CN'],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'template' => ['nullable', 'string', 'max:60'],
            'blocks' => ['nullable', 'array'],
            'seo' => ['nullable', 'array'],
            'parent_id' => ['nullable', 'integer'],
            'is_home' => ['nullable', 'boolean'],
            'featured_media_id' => ['nullable', 'integer'],
            'lock_version' => ['nullable', 'integer'],
            'revision_message' => ['nullable', 'string', 'max:255'],
        ]);
    }

    protected function pagePayload(Page $page, string $locale, $user): array
    {
        $translation = $page->translations->firstWhere('locale', $locale)
            ?? $page->translations->firstWhere('locale', $page->site->default_locale)
            ?? $page->translations->first();

        $blocks = $page->blocks()->where('locale', $locale)->orderBy('sort_order')->get()
            ->map(fn ($block) => $block->toPublicArray())->all();

        $seo = $page->seo->firstWhere('locale', $locale) ?? $page->seo->firstWhere('locale', $page->site->default_locale);

        $instance = WorkflowInstance::where('workflowable_type', 'page')
            ->where('workflowable_id', $page->id)
            ->with(['approvals.user', 'definition'])
            ->latest()
            ->first();

        $canEdit = $user->hasPermission('pages.edit_all', $page->site_id)
            || ($user->hasPermission('pages.edit_own', $page->site_id) && $page->author_id === $user->id);

        if ($page->isPublished() && ! $user->hasPermission('pages.publish', $page->site_id)) {
            $canEdit = false;
        }

        return [
            'id' => $page->id,
            'title' => $translation?->title,
            'slug' => $translation?->slug,
            'excerpt' => $translation?->excerpt,
            'status' => $page->status,
            'template' => $page->template,
            'is_home' => $page->is_home,
            'parent_id' => $page->parent_id,
            'author' => $page->author?->only(['id', 'name', 'email']),
            'featured_media' => $page->featuredMedia?->toPublicArray($locale),
            'published_at' => $page->published_at?->toIso8601String(),
            'scheduled_at' => $page->scheduled_at?->toIso8601String(),
            'updated_at' => $page->updated_at?->toIso8601String(),
            'lock_version' => $page->lock_version,
            'locale' => $locale,
            'is_fallback' => $translation?->locale !== $locale,
            'translations' => $page->translations->map(fn ($item) => [
                'locale' => $item->locale,
                'title' => $item->title,
                'slug' => $item->slug,
                'excerpt' => $item->excerpt,
                'updated_at' => $item->updated_at?->toIso8601String(),
            ])->values()->all(),
            'missing_locales' => array_values(array_diff($page->site->enabled_locales, $page->translations->pluck('locale')->all())),
            'blocks' => $blocks,
            'seo' => $seo ? $this->seoPayload($seo) : null,
            'revisions' => $page->revisions->take(15)->map(fn ($revision) => [
                'id' => $revision->id,
                'version' => $revision->version,
                'message' => $revision->message,
                'locale' => $revision->locale,
                'user' => $revision->user?->name,
                'created_at' => $revision->created_at?->toIso8601String(),
            ])->values()->all(),
            'workflow' => $instance ? [
                'id' => $instance->id,
                'status' => $instance->status,
                'current_step' => $instance->current_step,
                'steps' => $instance->definition?->steps ?? [],
                'submitted_at' => $instance->submitted_at?->toIso8601String(),
                'approvals' => $instance->approvals->map(fn ($approval) => [
                    'step' => $approval->step,
                    'step_name' => $approval->step_name,
                    'decision' => $approval->decision,
                    'note' => $approval->note,
                    'user' => $approval->user?->name,
                    'decided_at' => $approval->decided_at?->toIso8601String(),
                ])->values()->all(),
            ] : null,
            'capabilities' => [
                'edit' => $canEdit,
                'delete' => $user->hasPermission('pages.delete', $page->site_id),
                'publish' => $user->hasPermission('pages.publish', $page->site_id),
                'approve' => $user->hasPermission('pages.approve', $page->site_id),
            ],
        ];
    }

    protected function seoPayload($seo): array
    {
        return [
            'title' => $seo->title,
            'description' => $seo->description,
            'canonical' => $seo->canonical,
            'focus_keyword' => $seo->focus_keyword,
            'og_title' => $seo->og_title,
            'og_description' => $seo->og_description,
            'og_image' => $seo->ogImage?->toPublicArray('en'),
            'robots' => $seo->robots,
            'schema_type' => $seo->schema_type,
        ];
    }

    protected function ensureSite(Page $page): void
    {
        abort_unless($page->site_id === $this->context->id(), 404);
    }

    protected function authorizeEdit(Page $page, $user): void
    {
        abort_unless(
            $user->hasPermission('pages.edit_all', $page->site_id)
            || ($user->hasPermission('pages.edit_own', $page->site_id) && $page->author_id === $user->id),
            403,
            'You are not allowed to edit this page.'
        );
    }
}
