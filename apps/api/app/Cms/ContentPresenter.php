<?php

namespace App\Cms;

use App\Models\Article;
use App\Models\CaseStudy;
use App\Models\Industry;
use App\Models\JobPosting;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Setting;
use App\Models\Site;
use App\Models\Solution;
use App\Support\RichTextSanitizer;
use Illuminate\Support\Collection;

class ContentPresenter
{
    /** @var array<string, array<string, array{slug: string, locale: string}>> */
    protected array $pageAliasCache = [];

    public function __construct(
        protected BlockResolver $blocks,
        protected SiteContext $context,
        protected RichTextSanitizer $sanitizer,
    ) {}

    public function bootstrap(Site $site, string $locale): array
    {
        $locales = collect($site->enabled_locales)->map(function (string $code) use ($site) {
            $meta = config('meridian.locales')[$code] ?? [];

            return [
                'code' => $code,
                'route' => $meta['route'] ?? strtolower($code),
                'label' => $meta['label'] ?? strtoupper($code),
                'short' => $meta['short'] ?? strtoupper($code),
                'html' => $meta['html'] ?? $code,
                'is_default' => $code === $site->default_locale,
            ];
        })->values()->all();

        $settings = $this->publicSettings($site);

        return [
            'site' => [
                'name' => $site->name,
                'legal_name' => $site->legal_name,
                'slug' => $site->slug,
                'default_locale' => $site->default_locale,
                'brand' => $site->brand,
                'theme' => $site->theme,
                'demo_mode' => config('meridian.demo_mode'),
            ],
            'locales' => $locales,
            'current_locale' => $locale,
            'navigation' => $this->navigation($site, $locale),
            'settings' => $settings,
            'organization' => $this->organizationSchema($site, $settings),
        ];
    }

    public function publicSettings(Site $site): array
    {
        return Setting::forSite($site)
            ->where('is_public', true)
            ->get()
            ->mapWithKeys(fn (Setting $setting) => [$setting->group.'.'.$setting->key => $setting->value])
            ->all();
    }

    public function navigation(Site $site, string $locale): array
    {
        $keys = ['main', 'footer-company', 'footer-capabilities', 'footer-legal'];
        $menus = [];

        foreach ($keys as $key) {
            $menu = NavigationMenu::forSite($site)
                ->where('key', $key)
                ->where('locale', $locale)
                ->first();

            if (! $menu) {
                $menu = NavigationMenu::forSite($site)
                    ->where('key', $key)
                    ->where('locale', $site->default_locale)
                    ->first();
            }

            if (! $menu) {
                $menus[$key] = [];

                continue;
            }

            $items = NavigationItem::where('navigation_menu_id', $menu->id)
                ->whereNull('parent_id')
                ->where('is_visible', true)
                ->with(['children' => fn ($q) => $q->where('is_visible', true), 'children.children'])
                ->orderBy('sort')
                ->get();

            // The main menu mixes page links and collection links. Resolve the
            // page-backed ones by URL so the frontend gets a stable key even
            // when the item was authored with a localized slug.
            $menus[$key] = $this->mapNavItems($items, $site, $locale, $key === 'main');
        }

        return $menus;
    }

    protected function mapNavItems(Collection $items, Site $site, string $locale, bool $resolveUrlPages = false): array
    {
        return $items->map(function (NavigationItem $item) use ($site, $locale, $resolveUrlPages) {
            $page = $this->navPage($item, $site, $resolveUrlPages);

            return [
                'label' => $item->labelFor($locale, $site->default_locale),
                'url' => $this->navUrl($item, $site, $locale, $page),
                'key' => $this->navItemKey($page),
                'target' => $item->target,
                'children' => $this->mapNavItems($item->children, $site, $locale, $resolveUrlPages),
            ];
        })->values()->all();
    }

    protected function navPage(NavigationItem $item, Site $site, bool $resolveUrlPages): ?Page
    {
        if ($item->page_id) {
            return Page::forSite($site)->with('translations')->find($item->page_id);
        }

        if (! $resolveUrlPages) {
            return null;
        }

        $path = parse_url($item->url ?? '', PHP_URL_PATH) ?: '';
        $slug = rawurldecode(basename($path));

        if ($slug === '') {
            return null;
        }

        return Page::forSite($site)
            ->with('translations')
            ->whereHas('translations', fn ($query) => $query->where('slug', $slug))
            ->first();
    }

    /**
     * A locale-independent identifier for page-backed items, used by the
     * frontend to tell navigation items apart across translated slugs.
     */
    protected function navItemKey(?Page $page): ?string
    {
        if (! $page) {
            return null;
        }

        $fallback = config('meridian.fallback_locale');

        return $page->translations->firstWhere('locale', $fallback)?->slug
            ?? $page->translations->first()?->slug;
    }

    protected function navUrl(NavigationItem $item, Site $site, string $locale, ?Page $page = null): string
    {
        if ($item->page_id) {
            $page ??= Page::forSite($site)->with('translations')->find($item->page_id);
            $translation = $page?->translations->firstWhere('locale', $locale)
                ?? $page?->translations->firstWhere('locale', $site->default_locale);

            if ($translation) {
                return $this->localizedPath($locale, $translation->slug);
            }
        }

        return $this->localizeUrl($item->url ?? '/', $locale);
    }

    public function page(Page $page, string $locale, bool $preview = false): array
    {
        $site = $page->site;
        $translation = $page->translations()
            ->where('locale', $locale)
            ->first();

        $actualLocale = $translation?->locale;

        if (! $translation) {
            $translation = $page->translations()
                ->where('locale', config('meridian.fallback_locale'))
                ->first();
            $actualLocale = $translation?->locale;
        }

        if (! $translation) {
            $translation = $page->translations()
                ->where('locale', $site->default_locale)
                ->first();
            $actualLocale = $translation?->locale;
        }

        if (! $translation) {
            abort(404, 'This page has no available translation.');
        }

        $blocks = $page->blocks()
            ->where('locale', $actualLocale)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($block) => $block->toPublicArray())
            ->all();

        $blocks = $this->localizeBlockUrls($blocks, $actualLocale, $site);
        $blocks = $this->blocks->resolve($blocks, $site, $actualLocale);
        $seo = $this->seoPayload($page, $actualLocale, $translation, $blocks, $preview);
        $path = $page->is_home ? $this->localizedPath($locale, '') : $this->localizedPath($locale, $translation->slug);

        $breadcrumbs = $this->breadcrumbs($page, $locale);

        return [
            'type' => 'page',
            'id' => $page->id,
            'template' => $page->template,
            'status' => $page->status,
            'is_home' => $page->is_home,
            'locale' => $locale,
            'content_locale' => $actualLocale,
            'is_fallback' => $actualLocale !== $locale,
            'title' => $translation->title,
            'slug' => $translation->slug,
            'excerpt' => $translation->excerpt,
            'url' => $path,
            'published_at' => $page->published_at?->toIso8601String(),
            'updated_at' => $page->updated_at?->toIso8601String(),
            'featured_image' => $page->featuredMedia?->toPublicArray($locale),
            'blocks' => $blocks,
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
            'alternates' => $this->alternates($page, $locale),
            'preview' => $preview,
        ];
    }

    public function seoPayload($model, string $locale, $translation, array $blocks = [], bool $preview = false): array
    {
        $site = $model->site ?? $this->context->siteOrFail();
        $seo = method_exists($model, 'seo') ? $model->seo()->where('locale', $locale)->first() : null;
        $seoEn = $seo ?: (method_exists($model, 'seo') ? $model->seo()->where('locale', 'en')->first() : null);

        $heroImage = null;

        foreach ($blocks as $block) {
            if (! empty($block['props']['image']['url'])) {
                $heroImage = $block['props']['image'];
                break;
            }
        }

        $featured = $model->featuredMedia?->toPublicArray($locale);
        $defaultOg = data_get($site->seo_defaults, 'og_image_url');
        $ogImage = $seoEn?->ogImage?->url ?? $heroImage['url'] ?? $featured['url'] ?? $defaultOg;

        $title = $seo?->title ?: $translation->title.' — '.$site->name;
        $description = $seo?->description ?: ($translation->excerpt ?? data_get($site->seo_defaults, 'description'));
        $slug = $translation->slug;
        $path = $this->pathForModel($model, $translation->locale, $slug);
        $canonical = $seo?->canonical ?: rtrim((string) config('meridian.website_url'), '/').$path;

        $schema = array_values(array_filter([
            $seo?->schema,
            [
                '@context' => 'https://schema.org',
                '@type' => $seo?->schema_type ?: 'WebPage',
                'name' => $title,
                'description' => $description,
                'url' => $canonical,
                'inLanguage' => config('meridian.locales')[$locale]['html'] ?? $locale,
                'isPartOf' => [
                    '@type' => 'WebSite',
                    'name' => $site->name,
                    'url' => rtrim((string) config('meridian.website_url'), '/').$this->localizedPath($locale, ''),
                ],
            ],
        ]));

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $preview ? 'noindex,nofollow' : ($seo?->robots ?: 'index,follow'),
            'focus_keyword' => $seo?->focus_keyword,
            'og' => [
                'title' => $seo?->og_title ?: $title,
                'description' => $seo?->og_description ?: $description,
                'image' => $ogImage,
                'type' => $model instanceof Article ? 'article' : 'website',
            ],
            'schema' => $schema,
            'breadcrumbs' => $this->breadcrumbs($model, $locale),
        ];
    }

    public function article(Article $article, string $locale): array
    {
        $site = $article->site;
        $translation = $article->translations()->where('locale', $locale)->first()
            ?? $article->translations()->where('locale', $site->default_locale)->first();

        abort_unless($translation, 404);

        $body = $this->sanitizer->renderTiptapDocument($translation->body);
        $seo = $this->seoPayload($article, $locale, $translation);
        $canonical = $seo['canonical'];

        $seo['schema'][] = [
            '@context' => 'https://schema.org',
            '@type' => $article->type === 'news' ? 'NewsArticle' : 'Article',
            'headline' => $translation->title,
            'description' => $translation->excerpt,
            'datePublished' => $article->published_at?->toIso8601String(),
            'dateModified' => ($article->updated_content_at ?? $article->updated_at)?->toIso8601String(),
            'author' => $article->author ? [
                '@type' => 'Person',
                'name' => $article->author->name,
            ] : null,
            'publisher' => [
                '@type' => 'Organization',
                'name' => $site->name,
            ],
            'image' => $article->featuredMedia?->url,
            'mainEntityOfPage' => $canonical,
            'inLanguage' => config('meridian.locales')[$locale]['html'] ?? $locale,
        ];

        return [
            'type' => 'article',
            'id' => $article->id,
            'locale' => $locale,
            'content_locale' => $translation->locale,
            'is_fallback' => $translation->locale !== $locale,
            'title' => $translation->title,
            'slug' => $translation->slug,
            'excerpt' => $translation->excerpt,
            'body' => $body,
            'toc' => $translation->toc ?? [],
            'article_type' => $article->type,
            'reading_time' => $article->reading_time,
            'published_at' => $article->published_at?->toIso8601String(),
            'updated_at' => ($article->updated_content_at ?? $article->updated_at)?->toIso8601String(),
            'author' => $article->author ? [
                'name' => $article->author->name,
                'job_title' => $article->author->job_title,
                'initials' => $article->author->initials(),
            ] : null,
            'category' => $article->category ? [
                'slug' => $article->category->slug,
                'name' => $article->category->labelFor($locale),
            ] : null,
            'tags' => $article->tags->map(fn ($tag) => [
                'slug' => $tag->slug,
                'name' => $tag->name[$locale] ?? $tag->name['en'] ?? $tag->slug,
            ])->values()->all(),
            'image' => $article->featuredMedia?->toPublicArray($locale),
            'seo' => $seo,
            'alternates' => $this->alternates($article, $locale),
            'related' => $this->relatedArticles($article, $locale),
        ];
    }

    public function caseStudy(CaseStudy $study, string $locale): array
    {
        $site = $study->site;
        $translation = $study->translations()->where('locale', $locale)->first()
            ?? $study->translations()->where('locale', $site->default_locale)->first();

        abort_unless($translation, 404);

        $seo = $this->seoPayload($study, $locale, $translation);
        $seo['schema'][] = [
            '@context' => 'https://schema.org',
            '@type' => 'CreativeWork',
            'name' => $translation->title,
            'description' => $translation->summary,
            'image' => $study->featuredMedia?->url,
            'datePublished' => $study->published_at?->toIso8601String(),
            'creator' => ['@type' => 'Organization', 'name' => $site->name],
        ];

        return [
            'type' => 'case_study',
            'id' => $study->id,
            'locale' => $locale,
            'content_locale' => $translation->locale,
            'is_fallback' => $translation->locale !== $locale,
            'title' => $translation->title,
            'slug' => $translation->slug,
            'summary' => $translation->summary,
            'challenge' => $translation->challenge,
            'strategy' => $translation->strategy,
            'implementation' => $translation->implementation,
            'results' => $translation->results ?? [],
            'gallery' => collect($translation->gallery ?? [])->map(fn ($item) => [
                'url' => $item['url'] ?? null,
                'alt' => $item['alt'] ?? '',
                'caption' => $item['caption'] ?? null,
            ])->filter(fn ($item) => $item['url'])->values()->all(),
            'year' => $study->year,
            'client_label' => $study->client_label,
            'illustrative_note' => $translation->illustrative_note,
            'industry' => $study->industry ? $this->blocks->industrySummary($study->industry, $locale) : null,
            'solutions' => $study->solutions->map(fn (Solution $solution) => $this->blocks->solutionSummary($solution, $locale))->filter()->values()->all(),
            'image' => $study->featuredMedia?->toPublicArray($locale),
            'seo' => $seo,
            'alternates' => $this->alternates($study, $locale),
            'related' => $this->relatedCaseStudies($study, $locale),
        ];
    }

    public function solution(Solution $solution, string $locale): array
    {
        $site = $solution->site;
        $translation = $solution->translations()->where('locale', $locale)->first()
            ?? $solution->translations()->where('locale', $site->default_locale)->first();

        abort_unless($translation, 404);

        $seo = $this->seoPayload($solution, $locale, $translation);
        $relatedProjects = CaseStudy::forSite($site)->published()
            ->whereHas('solutions', fn ($q) => $q->where('solutions.id', $solution->id))
            ->with(['translations', 'featuredMedia', 'industry.translations'])
            ->limit(3)->get()
            ->map(fn (CaseStudy $study) => $this->blocks->caseStudySummary($study, $locale))
            ->filter()->values()->all();

        return [
            'type' => 'solution',
            'id' => $solution->id,
            'locale' => $locale,
            'content_locale' => $translation->locale,
            'is_fallback' => $translation->locale !== $locale,
            'key' => $solution->key,
            'title' => $translation->title,
            'slug' => $translation->slug,
            'tagline' => $translation->tagline,
            'overview' => $translation->overview,
            'problems' => $translation->problems ?? [],
            'approach' => $translation->approach ?? [],
            'capabilities' => $translation->capabilities ?? [],
            'benefits' => $translation->benefits ?? [],
            'icon' => $solution->icon,
            'image' => $solution->featuredMedia?->toPublicArray($locale),
            'industries' => $solution->industries->map(fn (Industry $industry) => $this->blocks->industrySummary($industry, $locale))->filter()->values()->all(),
            'related_projects' => $relatedProjects,
            'seo' => $seo,
            'breadcrumbs' => $this->breadcrumbs($solution, $locale),
            'alternates' => $this->alternates($solution, $locale),
        ];
    }

    public function industry(Industry $industry, string $locale): array
    {
        $site = $industry->site;
        $translation = $industry->translations()->where('locale', $locale)->first()
            ?? $industry->translations()->where('locale', $site->default_locale)->first();

        abort_unless($translation, 404);

        $seo = $this->seoPayload($industry, $locale, $translation);
        $projects = CaseStudy::forSite($site)->published()
            ->where('industry_id', $industry->id)
            ->with(['translations', 'featuredMedia', 'industry.translations'])
            ->limit(3)->get()
            ->map(fn (CaseStudy $study) => $this->blocks->caseStudySummary($study, $locale))
            ->filter()->values()->all();

        return [
            'type' => 'industry',
            'id' => $industry->id,
            'locale' => $locale,
            'content_locale' => $translation->locale,
            'is_fallback' => $translation->locale !== $locale,
            'key' => $industry->key,
            'title' => $translation->title,
            'slug' => $translation->slug,
            'tagline' => $translation->tagline,
            'overview' => $translation->overview,
            'challenges' => $translation->challenges ?? [],
            'capabilities' => $translation->capabilities ?? [],
            'stats' => $translation->stats ?? [],
            'icon' => $industry->icon,
            'image' => $industry->featuredMedia?->toPublicArray($locale),
            'solutions' => $industry->solutions->map(fn (Solution $solution) => $this->blocks->solutionSummary($solution, $locale))->filter()->values()->all(),
            'related_projects' => $projects,
            'seo' => $seo,
            'breadcrumbs' => $this->breadcrumbs($industry, $locale),
            'alternates' => $this->alternates($industry, $locale),
        ];
    }

    public function job(JobPosting $job, string $locale): array
    {
        $site = $job->site;
        $translation = $job->translations()->where('locale', $locale)->first()
            ?? $job->translations()->where('locale', $site->default_locale)->first();

        abort_unless($translation, 404);

        $seo = $this->seoPayload($job, $locale, $translation);
        $seo['schema'][] = [
            '@context' => 'https://schema.org',
            '@type' => 'JobPosting',
            'title' => $translation->title,
            'description' => $this->sanitizer->plainText($this->sanitizer->renderTiptapDocument($translation->description)),
            'datePosted' => $job->posted_at?->toIso8601String(),
            'validThrough' => $job->closes_at?->toIso8601String(),
            'employmentType' => strtoupper($job->employment_type),
            'hiringOrganization' => [
                '@type' => 'Organization',
                'name' => $site->name,
                'sameAs' => rtrim((string) config('meridian.website_url'), '/'),
            ],
            'jobLocation' => [
                '@type' => 'Place',
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => $job->location,
                    'addressCountry' => 'TH',
                ],
            ],
        ];

        return [
            'type' => 'job',
            'id' => $job->id,
            'locale' => $locale,
            'content_locale' => $translation->locale,
            'is_fallback' => $translation->locale !== $locale,
            'title' => $translation->title,
            'slug' => $translation->slug,
            'summary' => $translation->summary,
            'description' => $this->sanitizer->renderTiptapDocument($translation->description),
            'responsibilities' => $translation->responsibilities ?? [],
            'requirements' => $translation->requirements ?? [],
            'benefits' => $translation->benefits ?? [],
            'department' => $job->department,
            'location' => $job->location,
            'employment_type' => $job->employment_type,
            'posted_at' => $job->posted_at?->toIso8601String(),
            'closes_at' => $job->closes_at?->toIso8601String(),
            'seo' => $seo,
            'breadcrumbs' => $this->breadcrumbs($job, $locale),
            'alternates' => $this->alternates($job, $locale),
        ];
    }

    public function sitemap(Site $site, string $locale): array
    {
        $routes = [];
        $add = function (string $path, ?string $lastmod, string $priority = '0.7', string $changefreq = 'monthly') use (&$routes) {
            $routes[] = [
                'loc' => $path,
                'lastmod' => $lastmod,
                'changefreq' => $changefreq,
                'priority' => $priority,
            ];
        };

        $home = Page::forSite($site)->published()->where('is_home', true)->first();
        $add($this->localizedPath($locale, ''), $home?->updated_at?->toAtomString(), '1.0', 'weekly');

        $pages = Page::forSite($site)->published()
            ->where('is_home', false)
            ->with(['translations'])
            ->get();

        foreach ($pages as $page) {
            $translation = $page->translations->firstWhere('locale', $locale);

            if ($translation) {
                $add($this->localizedPath($locale, $translation->slug), $page->updated_at?->toAtomString(), '0.8', 'monthly');
            }
        }

        foreach (Solution::forSite($site)->where('status', 'published')->with('translations')->get() as $solution) {
            $translation = $solution->translations->firstWhere('locale', $locale);
            if ($translation) {
                $add($this->localizedPath($locale, 'solutions/'.$translation->slug), $solution->updated_at?->toAtomString(), '0.8');
            }
        }

        foreach (Industry::forSite($site)->where('status', 'published')->with('translations')->get() as $industry) {
            $translation = $industry->translations->firstWhere('locale', $locale);
            if ($translation) {
                $add($this->localizedPath($locale, 'industries/'.$translation->slug), $industry->updated_at?->toAtomString(), '0.7');
            }
        }

        foreach (CaseStudy::forSite($site)->published()->with('translations')->get() as $study) {
            $translation = $study->translations->firstWhere('locale', $locale);
            if ($translation) {
                $add($this->localizedPath($locale, 'case-studies/'.$translation->slug), $study->updated_at?->toAtomString(), '0.7');
            }
        }

        foreach (Article::forSite($site)->published()->with('translations')->get() as $article) {
            $translation = $article->translations->firstWhere('locale', $locale);
            if ($translation) {
                $add($this->localizedPath($locale, 'insights/'.$translation->slug), $article->updated_at?->toAtomString(), '0.6', 'weekly');
            }
        }

        foreach (JobPosting::forSite($site)->open()->with('translations')->get() as $job) {
            $translation = $job->translations->firstWhere('locale', $locale);
            if ($translation) {
                $add($this->localizedPath($locale, 'careers/'.$translation->slug), $job->updated_at?->toAtomString(), '0.6', 'weekly');
            }
        }

        return $routes;
    }

    public function alternates($model, string $locale): array
    {
        $translations = $model->translations()->get();
        $alternates = [];

        foreach ($translations as $translation) {
            $alternates[] = [
                'locale' => $translation->locale,
                'hreflang' => config('meridian.locales')[$translation->locale]['html'] ?? $translation->locale,
                'url' => rtrim((string) config('meridian.website_url'), '/').$this->pathForModel($model, $translation->locale, $translation->slug),
            ];
        }

        $default = collect($alternates)->firstWhere('locale', config('meridian.default_locale'));

        if ($default) {
            $alternates[] = ['locale' => 'x-default', 'hreflang' => 'x-default', 'url' => $default['url']];
        }

        return $alternates;
    }

    public function breadcrumbs($model, string $locale): array
    {
        $crumbs = [[
            'label' => $locale === 'th' ? 'หน้าหลัก' : ($locale === 'zh-CN' ? '首页' : 'Home'),
            'url' => $this->localizedPath($locale, ''),
        ]];

        if ($model instanceof Page) {
            $parent = $model->parent;

            while ($parent) {
                $translation = $parent->translations()->where('locale', $locale)->first();
                if ($translation) {
                    $crumbs[] = [
                        'label' => $translation->title,
                        'url' => $this->localizedPath($locale, $translation->slug),
                    ];
                }
                $parent = $parent->parent;
            }
        }

        $section = match (true) {
            $model instanceof Article => ['label' => 'Insights', 'path' => 'insights'],
            $model instanceof CaseStudy => ['label' => 'Case studies', 'path' => 'case-studies'],
            $model instanceof Solution => ['label' => 'Solutions', 'path' => 'solutions'],
            $model instanceof Industry => ['label' => 'Industries', 'path' => 'industries'],
            $model instanceof JobPosting => ['label' => 'Careers', 'path' => 'careers'],
            default => null,
        };

        if ($section && ! $model instanceof Page) {
            $crumbs[] = [
                'label' => $section['label'],
                'url' => $this->localizedPath($locale, $section['path']),
            ];
        }

        $title = $model instanceof Page
            ? $model->translations()->where('locale', $locale)->value('title')
            : ($model->translations()->where('locale', $locale)->first()?->title);

        if ($title) {
            $crumbs[] = ['label' => $title, 'url' => null];
        }

        return $crumbs;
    }

    public function organizationSchema(Site $site, array $settings): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $site->name,
            'legalName' => $site->legal_name,
            'url' => rtrim((string) config('meridian.website_url'), '/'),
            'description' => data_get($site->seo_defaults, 'description'),
            'address' => data_get($settings, 'contact.details.hq.address'),
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'sales',
                'email' => data_get($settings, 'contact.details.email', 'hello@meridian.example'),
                'availableLanguage' => ['th', 'en', 'zh-CN'],
            ],
            'sameAs' => data_get($settings, 'contact.details.social', []),
        ];
    }

    public function localizedPath(string $locale, string $path): string
    {
        $routeLocale = config('meridian.locales')[$locale]['route'] ?? strtolower($locale);

        return '/'.$routeLocale.($path !== '' ? '/'.ltrim($path, '/') : '');
    }

    public function localizeUrl(?string $url, string $locale): string
    {
        $url = $url ?: '/';

        if (preg_match('/^(https?:|mailto:|tel:|#|\/\/)/i', $url)) {
            return $url;
        }

        $parts = parse_url($url);
        $path = $parts['path'] ?? '/';

        $exemptPrefixes = ['/documents/', '/storage/', '/api/', '/preview/', '/_nuxt/'];
        foreach ($exemptPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $url;
            }
        }

        if (preg_match('/\.(pdf|jpe?g|png|webp|gif|svg|zip)$/i', $path)) {
            return $url;
        }

        $routeLocales = collect(array_keys(config('meridian.locales')))
            ->map(fn ($code) => config('meridian.locales')[$code]['route'] ?? strtolower($code))
            ->all();

        if (in_array(trim($path, '/'), $routeLocales, true)) {
            return $url;
        }

        if (preg_match('#^/('.implode('|', array_map(fn ($code) => preg_quote($code, '#'), $routeLocales)).')(/|$)#i', $path)) {
            return $url;
        }

        $localized = $this->localizedPath($locale, ltrim($path, '/'));

        return $localized
            .(isset($parts['query']) ? '?'.$parts['query'] : '')
            .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
    }

    /**
     * Internal URLs in block props are authored without a locale prefix. The
     * presenter applies the locale once, so every renderer and the preview see
     * the same canonical link.
     */
    protected function localizeBlockUrls(array $blocks, string $locale, Site $site): array
    {
        $walk = function (mixed $value, ?string $key = null) use (&$walk, $locale, $site): mixed {
            if (is_string($value)) {
                if (in_array($key, ['url', 'href', 'link_url', 'canonical'], true)) {
                    return $this->localizeUrl($this->resolveContentPath($value, $site, $locale), $locale);
                }

                if (in_array($key, ['body', 'content', 'html'], true) && str_contains($value, 'href=')) {
                    return preg_replace_callback(
                        '/href=(["\'])([^"\']+)\1/i',
                        fn (array $matches) => 'href='.$matches[1].$this->localizeUrl($this->resolveContentPath($matches[2], $site, $locale), $locale).$matches[1],
                        $value,
                    );
                }

                return $value;
            }

            if (is_array($value)) {
                foreach ($value as $childKey => $childValue) {
                    $value[$childKey] = $walk($childValue, is_string($childKey) ? $childKey : null);
                }
            }

            return $value;
        };

        return $walk($blocks);
    }

    /**
     * A block may reference a single page by any of its localized slugs, for
     * example /about. Resolve that alias to the slug for the current locale.
     */
    protected function resolveContentPath(string $url, Site $site, string $locale): string
    {
        $parts = parse_url($url);
        $path = $parts['path'] ?? '';

        if (! str_starts_with($path, '/')) {
            return $url;
        }

        $segments = array_values(array_filter(explode('/', trim($path, '/'))));

        if (count($segments) !== 1) {
            return $url;
        }

        $cacheKey = $site->id.':'.$locale;

        if (! isset($this->pageAliasCache[$cacheKey])) {
            $aliases = [];

            foreach (Page::forSite($site)->with('translations')->get() as $page) {
                $translation = $page->translations->firstWhere('locale', $locale)
                    ?? $page->translations->firstWhere('locale', config('meridian.fallback_locale'))
                    ?? $page->translations->firstWhere('locale', $site->default_locale);

                if (! $translation) {
                    continue;
                }

                foreach ($page->translations as $candidate) {
                    $aliases[$candidate->slug] = ['slug' => $translation->slug, 'locale' => $translation->locale];
                }
            }

            $this->pageAliasCache[$cacheKey] = $aliases;
        }

        $resolved = $this->pageAliasCache[$cacheKey][$segments[0]] ?? null;

        if (! $resolved) {
            return $url;
        }

        $suffix = (isset($parts['query']) ? '?'.$parts['query'] : '')
            .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');

        if ($resolved['locale'] !== $locale) {
            return $this->localizedPath($resolved['locale'], $resolved['slug']).$suffix;
        }

        if ($resolved['slug'] === $segments[0]) {
            return $url;
        }

        return '/'.$resolved['slug'].$suffix;
    }

    public function pathForModel($model, string $locale, string $slug): string
    {
        if ($model instanceof Page && $model->is_home) {
            return $this->localizedPath($locale, '');
        }

        return match (true) {
            $model instanceof Article => $this->localizedPath($locale, 'insights/'.$slug),
            $model instanceof CaseStudy => $this->localizedPath($locale, 'case-studies/'.$slug),
            $model instanceof Solution => $this->localizedPath($locale, 'solutions/'.$slug),
            $model instanceof Industry => $this->localizedPath($locale, 'industries/'.$slug),
            $model instanceof JobPosting => $this->localizedPath($locale, 'careers/'.$slug),
            default => $this->localizedPath($locale, $slug),
        };
    }

    protected function relatedArticles(Article $article, string $locale): array
    {
        return Article::forSite($article->site)
            ->published()
            ->where('id', '!=', $article->id)
            ->when($article->category_id, fn ($q) => $q->where('category_id', $article->category_id))
            ->with(['translations', 'featuredMedia', 'category', 'author'])
            ->limit(3)->get()
            ->map(fn (Article $related) => $this->blocks->articleSummary($related, $locale))
            ->filter()->values()->all();
    }

    protected function relatedCaseStudies(CaseStudy $study, string $locale): array
    {
        return CaseStudy::forSite($study->site)
            ->published()
            ->where('id', '!=', $study->id)
            ->when($study->industry_id, fn ($q) => $q->where('industry_id', $study->industry_id))
            ->with(['translations', 'featuredMedia', 'industry.translations'])
            ->limit(3)->get()
            ->map(fn (CaseStudy $related) => $this->blocks->caseStudySummary($related, $locale))
            ->filter()->values()->all();
    }
}
