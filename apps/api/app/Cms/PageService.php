<?php

namespace App\Cms;

use App\Cms\Blocks\BlockRegistry;
use App\Models\Page;
use App\Models\PagePreviewToken;
use App\Models\PageRevision;
use App\Models\PageTranslation;
use App\Models\SeoMetadata;
use App\Models\Site;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PageService
{
    public function __construct(protected AuditLogger $audit) {}

    public function create(Site $site, User $user, array $payload): Page
    {
        $locale = app(SiteContext::class)->normalizeLocale($payload['locale'] ?? null);
        $title = trim((string) ($payload['title'] ?? ''));

        if ($title === '') {
            throw ValidationException::withMessages(['title' => 'A page title is required.']);
        }

        $slug = $this->slugify($payload['slug'] ?? $title);
        $this->assertSlugAvailable($site, $locale, $slug);

        $blocks = BlockRegistry::normalizeBlocks((array) ($payload['blocks'] ?? []), $site);
        $blocks = $this->sanitizeEmbeds($blocks);

        return DB::transaction(function () use ($site, $user, $payload, $locale, $title, $slug, $blocks) {
            $page = Page::create([
                'site_id' => $site->id,
                'parent_id' => $payload['parent_id'] ?? null,
                'template' => $payload['template'] ?? 'default',
                'status' => $payload['status'] ?? Page::STATUS_DRAFT,
                'author_id' => $user->id,
                'featured_media_id' => $payload['featured_media_id'] ?? null,
                'sort_order' => $payload['sort_order'] ?? 0,
                'is_home' => (bool) ($payload['is_home'] ?? false),
                'lock_version' => 1,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $page->translations()->create([
                'site_id' => $site->id,
                'locale' => $locale,
                'title' => $title,
                'slug' => $slug,
                'excerpt' => $payload['excerpt'] ?? null,
                'blocks_version' => BlockRegistry::SCHEMA_VERSION,
            ]);

            $this->replaceBlocks($page, $locale, $blocks);

            if (! empty($payload['seo'])) {
                $this->saveSeo($page, $locale, $payload['seo']);
            }

            $this->createRevision($page, $locale, $user, 'Initial draft');
            $this->audit->log('page.created', "Created page \"{$title}\"", [
                'subject_type' => 'page',
                'subject_id' => $page->id,
                'meta' => ['locale' => $locale, 'slug' => $slug],
            ]);

            return $page->fresh(['translations', 'blocks', 'seo']);
        });
    }

    public function update(Page $page, User $user, array $payload): Page
    {
        $locale = app(SiteContext::class)->normalizeLocale($payload['locale'] ?? null);

        if (isset($payload['lock_version']) && (int) $payload['lock_version'] !== (int) $page->lock_version) {
            throw ValidationException::withMessages([
                'lock_version' => 'This page was changed by someone else. Reload it before saving again.',
            ]);
        }

        $translation = $page->translations()->where('locale', $locale)->first();

        if (! $translation) {
            throw ValidationException::withMessages(['locale' => 'This locale has not been set up for the page yet.']);
        }

        if ($page->isPublished() && ! $user->hasPermission('pages.publish', $page->site_id)) {
            abort(403, 'This page is live. Only publishers may edit published content; unpublish it first or ask a publisher.');
        }

        $title = trim((string) ($payload['title'] ?? $translation->title));
        $slug = $this->slugify($payload['slug'] ?? $translation->slug);

        if ($title === '') {
            throw ValidationException::withMessages(['title' => 'A page title is required.']);
        }

        $this->assertSlugAvailable($page->site, $locale, $slug, $page->id);

        $blocks = array_key_exists('blocks', $payload)
            ? $this->sanitizeEmbeds(BlockRegistry::normalizeBlocks((array) $payload['blocks'], $page->site))
            : null;

        return DB::transaction(function () use ($page, $user, $payload, $locale, $title, $slug, $blocks) {
            $page->translations()->where('locale', $locale)->update([
                'title' => $title,
                'slug' => $slug,
                'excerpt' => $payload['excerpt'] ?? $page->translations()->where('locale', $locale)->value('excerpt'),
                'blocks_version' => BlockRegistry::SCHEMA_VERSION,
            ]);

            if (array_key_exists('parent_id', $payload)) {
                $page->parent_id = $payload['parent_id'];
            }

            if (array_key_exists('template', $payload)) {
                $page->template = $payload['template'];
            }

            if (array_key_exists('featured_media_id', $payload)) {
                $page->featured_media_id = $payload['featured_media_id'];
            }

            if ($blocks !== null) {
                $this->replaceBlocks($page, $locale, $blocks);
            }

            if (array_key_exists('seo', $payload)) {
                $this->saveSeo($page, $locale, (array) $payload['seo']);
            }

            $page->lock_version++;
            $page->updated_by = $user->id;
            $page->save();

            $this->createRevision($page, $locale, $user, $payload['revision_message'] ?? 'Content updated');
            $this->audit->log('page.updated', "Updated page \"{$title}\"", [
                'subject_type' => 'page',
                'subject_id' => $page->id,
                'meta' => ['locale' => $locale, 'slug' => $slug],
            ]);

            return $page->fresh(['translations', 'blocks', 'seo']);
        });
    }

    public function duplicate(Page $page, User $user): Page
    {
        return DB::transaction(function () use ($page, $user) {
            $copy = $page->replicate(['published_at', 'scheduled_at']);
            $copy->status = Page::STATUS_DRAFT;
            $copy->is_home = false;
            $copy->lock_version = 1;
            $copy->created_by = $user->id;
            $copy->updated_by = $user->id;
            $copy->save();

            foreach ($page->translations()->get() as $translation) {
                $copy->translations()->create([
                    'site_id' => $page->site_id,
                    'locale' => $translation->locale,
                    'title' => $translation->title.' (copy)',
                    'slug' => $this->uniqueCopySlug($page->site, $translation->locale, $translation->slug),
                    'excerpt' => $translation->excerpt,
                    'blocks_version' => $translation->blocks_version,
                ]);

                foreach ($page->blocks()->where('locale', $translation->locale)->get() as $block) {
                    $copy->blocks()->create([
                        'locale' => $translation->locale,
                        'block_uid' => (string) Str::uuid(),
                        'type' => $block->type,
                        'sort_order' => $block->sort_order,
                        'props' => $block->props,
                        'is_visible_desktop' => $block->is_visible_desktop,
                        'is_visible_tablet' => $block->is_visible_tablet,
                        'is_visible_mobile' => $block->is_visible_mobile,
                        'responsive' => $block->responsive,
                    ]);
                }

                $seo = $page->seo()->where('locale', $translation->locale)->first();

                if ($seo) {
                    $copy->seo()->create($seo->only([
                        'locale', 'description', 'focus_keyword', 'og_title', 'og_description',
                        'og_image_id', 'robots', 'schema_type', 'schema', 'breadcrumbs',
                    ]) + [
                        'site_id' => $page->site_id,
                        'title' => trim(($seo->title ?: $translation->title).' (copy)'),
                    ]);
                }
            }

            $this->audit->log('page.duplicated', "Duplicated page \"{$page->translations->first()?->title}\"", [
                'subject_type' => 'page',
                'subject_id' => $copy->id,
                'meta' => ['source_page_id' => $page->id],
            ]);

            return $copy->fresh(['translations', 'blocks', 'seo']);
        });
    }

    public function delete(Page $page, User $user): void
    {
        if ($page->is_home) {
            abort(422, 'The homepage cannot be deleted. Create another homepage first.');
        }

        DB::transaction(function () use ($page) {
            $page->delete();
            $this->audit->log('page.deleted', 'Deleted page', [
                'subject_type' => 'page',
                'subject_id' => $page->id,
                'meta' => ['title' => $page->translations->first()?->title],
            ]);
        });
    }

    public function saveBlocks(Page $page, string $locale, array $blocks, User $user): Page
    {
        $normalized = $this->sanitizeEmbeds(BlockRegistry::normalizeBlocks($blocks, $page->site));

        return DB::transaction(function () use ($page, $locale, $normalized, $user) {
            $this->replaceBlocks($page, $locale, $normalized);
            $page->lock_version++;
            $page->updated_by = $user->id;
            $page->save();

            $this->createRevision($page, $locale, $user, 'Layout updated');
            $this->audit->log('page.blocks_updated', 'Updated page layout', [
                'subject_type' => 'page',
                'subject_id' => $page->id,
                'meta' => ['locale' => $locale, 'blocks' => count($normalized)],
            ]);

            return $page->fresh(['translations', 'blocks', 'seo']);
        });
    }

    public function restoreRevision(Page $page, PageRevision $revision, User $user): Page
    {
        $snapshot = $revision->snapshot;

        return DB::transaction(function () use ($page, $revision, $snapshot, $user) {
            $page->translations()->updateOrCreate(
                ['locale' => $revision->locale],
                [
                    'site_id' => $page->site_id,
                    'title' => $snapshot['title'],
                    'slug' => $snapshot['slug'],
                    'excerpt' => $snapshot['excerpt'] ?? null,
                    'blocks_version' => BlockRegistry::SCHEMA_VERSION,
                ]
            );

            $this->replaceBlocks($page, $revision->locale, $snapshot['blocks'] ?? []);

            if (! empty($snapshot['seo'])) {
                $this->saveSeo($page, $revision->locale, $snapshot['seo']);
            }

            $page->lock_version++;
            $page->updated_by = $user->id;
            $page->save();

            $this->createRevision($page, $revision->locale, $user, 'Restored version '.$revision->version);
            $this->audit->log('page.revision_restored', 'Restored page version '.$revision->version, [
                'subject_type' => 'page',
                'subject_id' => $page->id,
                'meta' => ['locale' => $revision->locale, 'revision_id' => $revision->id],
            ]);

            return $page->fresh(['translations', 'blocks', 'seo']);
        });
    }

    public function previewToken(Page $page, string $locale, User $user): PagePreviewToken
    {
        $token = PagePreviewToken::create([
            'page_id' => $page->id,
            'site_id' => $page->site_id,
            'token' => Str::random(64),
            'locale' => $locale,
            'created_by' => $user->id,
            'expires_at' => now()->addHours(2),
        ]);

        $this->audit->log('page.preview_created', 'Generated a preview link', [
            'subject_type' => 'page',
            'subject_id' => $page->id,
            'meta' => ['locale' => $locale, 'expires_at' => $token->expires_at->toIso8601String()],
        ]);

        return $token;
    }

    public function createRevision(Page $page, string $locale, ?User $user, ?string $message = null): PageRevision
    {
        $translation = $page->translations()->where('locale', $locale)->first();
        $seo = $page->seo()->where('locale', $locale)->first();

        $snapshot = [
            'title' => $translation?->title,
            'slug' => $translation?->slug,
            'excerpt' => $translation?->excerpt,
            'blocks' => $page->blocks()->where('locale', $locale)->get()
                ->map(fn ($block) => [
                    'block_uid' => $block->block_uid,
                    'type' => $block->type,
                    'props' => $block->props,
                    'responsive' => [
                        'desktop' => $block->is_visible_desktop,
                        'tablet' => $block->is_visible_tablet,
                        'mobile' => $block->is_visible_mobile,
                        'overrides' => $block->responsive,
                    ],
                ])->values()->all(),
            'seo' => $seo?->only([
                'title', 'description', 'canonical', 'focus_keyword', 'og_title',
                'og_description', 'og_image_id', 'robots', 'schema_type', 'schema', 'breadcrumbs',
            ]),
            'status' => $page->status,
            'captured_at' => now()->toIso8601String(),
        ];

        $version = (int) $page->revisions()->where('locale', $locale)->max('version') + 1;

        return $page->revisions()->create([
            'locale' => $locale,
            'user_id' => $user?->id,
            'version' => $version,
            'snapshot' => $snapshot,
            'message' => $message,
            'created_at' => now(),
        ]);
    }

    public function saveSeo(Page $page, string $locale, array $seo): SeoMetadata
    {
        $canonical = trim((string) ($seo['canonical'] ?? ''));

        if ($canonical !== '' && ! BlockRegistry::isSafeUrl($canonical)) {
            throw ValidationException::withMessages(['seo.canonical' => 'The canonical URL must be internal or https.']);
        }

        return SeoMetadata::updateOrCreate(
            ['seoable_type' => 'page', 'seoable_id' => $page->id, 'locale' => $locale],
            [
                'site_id' => $page->site_id,
                'title' => $this->limit($seo['title'] ?? null, 255),
                'description' => $this->limit($seo['description'] ?? null, 500),
                'canonical' => $canonical ?: null,
                'focus_keyword' => $this->limit($seo['focus_keyword'] ?? null, 120),
                'og_title' => $this->limit($seo['og_title'] ?? null, 255),
                'og_description' => $this->limit($seo['og_description'] ?? null, 500),
                'og_image_id' => $seo['og_image_id'] ?? null,
                'robots' => $seo['robots'] ?? 'index,follow',
                'schema_type' => $seo['schema_type'] ?? null,
                'schema' => $seo['schema'] ?? null,
                'breadcrumbs' => $seo['breadcrumbs'] ?? null,
            ]
        );
    }

    public function slugify(string $value): string
    {
        $slug = trim($value);
        $slug = preg_replace('/[^\p{L}\p{N}\s\-_]+/u', '', $slug) ?? '';
        $slug = preg_replace('/[\s_]+/u', '-', $slug) ?? '';
        $slug = preg_replace('/-+/u', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        if ($slug === '') {
            $slug = 'page-'.Str::lower(Str::random(6));
        }

        return mb_strtolower($slug);
    }

    public function assertSlugAvailable(Site $site, string $locale, string $slug, ?int $exceptPageId = null): void
    {
        $query = PageTranslation::where('site_id', $site->id)
            ->where('locale', $locale)
            ->where('slug', $slug);

        if ($exceptPageId) {
            $query->where('page_id', '!=', $exceptPageId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'slug' => "The slug \"{$slug}\" is already used by another page in this locale.",
            ]);
        }
    }

    protected function uniqueCopySlug(Site $site, string $locale, string $base): string
    {
        $slug = $base.'-copy';
        $counter = 2;

        while (PageTranslation::where('site_id', $site->id)->where('locale', $locale)->where('slug', $slug)->exists()) {
            $slug = $base.'-copy-'.$counter++;
        }

        return $slug;
    }

    protected function replaceBlocks(Page $page, string $locale, array $blocks): void
    {
        $page->blocks()->where('locale', $locale)->delete();

        foreach (array_values($blocks) as $index => $block) {
            $page->blocks()->create([
                'locale' => $locale,
                'block_uid' => $block['block_uid'],
                'type' => $block['type'],
                'sort_order' => $index,
                'props' => $block['props'],
                'is_visible_desktop' => $block['is_visible_desktop'] ?? true,
                'is_visible_tablet' => $block['is_visible_tablet'] ?? true,
                'is_visible_mobile' => $block['is_visible_mobile'] ?? true,
                'responsive' => $block['responsive'] ?? null,
            ]);
        }
    }

    protected function sanitizeEmbeds(array $blocks): array
    {
        return array_map(function (array $block) {
            if ($block['type'] === 'html_embed') {
                $block['props']['html'] = BlockRegistry::sanitizeEmbed($block['props']['html'] ?? '');
            }

            return $block;
        }, $blocks);
    }

    protected function limit(?string $value, int $max): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return mb_substr(trim($value), 0, $max);
    }
}
