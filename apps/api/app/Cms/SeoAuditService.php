<?php

namespace App\Cms;

use App\Models\Article;
use App\Models\CaseStudy;
use App\Models\Industry;
use App\Models\JobPosting;
use App\Models\NavigationItem;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\SeoAudit;
use App\Models\SeoIssue;
use App\Models\Site;
use App\Models\Solution;
use App\Support\RichTextSanitizer;
use Illuminate\Support\Facades\DB;

class SeoAuditService
{
    public function __construct(
        protected ContentPresenter $presenter,
        protected RichTextSanitizer $sanitizer,
    ) {}

    public function run(Site $site): SeoAudit
    {
        $started = now();
        $locales = $site->activeLocales();
        $documents = $this->documents($site, $locales);
        $issues = [];

        foreach ($documents as $document) {
            $issues = array_merge($issues, $this->inspect($document, $documents, $site, $locales));
        }

        $issues = array_merge($issues, $this->duplicateIssues($documents));
        $issues = array_merge($issues, $this->internalLinkIssues($documents, $site));
        $issues = array_merge($issues, $this->redirectIssues($site, $locales));
        $issues = $this->dedupe($issues);

        $audit = DB::transaction(function () use ($site, $issues, $documents, $started) {
            $counts = collect($issues)->groupBy('severity')->map->count();
            $critical = (int) ($counts['critical'] ?? 0);
            $warning = (int) ($counts['warning'] ?? 0);
            $info = (int) ($counts['info'] ?? 0);
            $urlCount = max(1, count($documents));
            $deduction = (($critical * 8) + ($warning * 3) + ($info * 0.5)) / $urlCount * 10;
            $score = (int) max(0, min(100, round(100 - $deduction)));

            $audit = SeoAudit::create([
                'site_id' => $site->id,
                'score' => $score,
                'summary' => [
                    'documents' => count($documents),
                    'critical' => $critical,
                    'warning' => $warning,
                    'info' => $info,
                    'by_code' => collect($issues)->groupBy('code')->map->count()->all(),
                    'formula' => '100 − ((critical×8 + warning×3 + info×0.5) ÷ audited URLs × 10), clamped to 0–100.',
                ],
                'checked_at' => $started,
            ]);

            $seen = [];

            foreach ($issues as $issue) {
                $key = implode('|', [
                    $issue['seoable_type'] ?? 'none',
                    $issue['seoable_id'] ?? '0',
                    $issue['locale'],
                    $issue['code'],
                ]);

                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;

                SeoIssue::updateOrCreate(
                    [
                        'site_id' => $site->id,
                        'seoable_type' => $issue['seoable_type'] ?? null,
                        'seoable_id' => $issue['seoable_id'] ?? null,
                        'locale' => $issue['locale'],
                        'code' => $issue['code'],
                    ],
                    [
                        'seo_audit_id' => $audit->id,
                        'severity' => $issue['severity'],
                        'url' => $issue['url'] ?? null,
                        'title' => $issue['title'] ?? null,
                        'explanation' => $issue['explanation'],
                        'recommendation' => $issue['recommendation'],
                        'status' => 'open',
                        'last_checked_at' => $started,
                        'resolved_at' => null,
                    ]
                );
            }

            SeoIssue::where('site_id', $site->id)
                ->where('last_checked_at', '<', $started)
                ->where('status', 'open')
                ->update(['status' => 'resolved', 'resolved_at' => now()]);

            return $audit;
        });

        return $audit->load('issues');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function documents(Site $site, array $locales): array
    {
        $documents = [];
        $routeLocales = collect($locales)->mapWithKeys(fn ($locale) => [$locale => config('meridian.locales')[$locale]['route'] ?? strtolower($locale)]);

        $pages = Page::forSite($site)->published()
            ->with(['translations', 'blocks', 'seo.ogImage', 'featuredMedia'])
            ->get();

        foreach ($pages as $page) {
            foreach ($page->translations as $translation) {
                if (! in_array($translation->locale, $locales, true)) {
                    continue;
                }

                $blocks = $page->blocks->where('locale', $translation->locale)->values();
                $images = $this->imagesIn($blocks->map->props->all());
                $text = $blocks->map(fn ($block) => $this->flattenText($block->props))->implode(' ');
                $h1 = $blocks->filter(fn ($block) => in_array($block->type, ['hero', 'split_hero'], true)
                    && ! empty(data_get($block->props, 'headline')))->count();
                // The page template renders a visually hidden H1 when no hero exists.
                $h1 = max(1, $h1);

                $documents[] = [
                    'seoable_type' => 'page',
                    'seoable_id' => $page->id,
                    'model' => $page,
                    'locale' => $translation->locale,
                    'title' => $translation->title,
                    'description' => $translation->excerpt,
                    'url' => '/'.$routeLocales[$translation->locale].'/'.$translation->slug,
                    'seo' => $page->seo->firstWhere('locale', $translation->locale),
                    'featured_image' => $page->featuredMedia,
                    'images' => $images,
                    'h1_count' => $h1,
                    'word_count' => $this->wordCount($text),
                    'internal_links' => $this->linksIn($blocks->map->props->all()),
                    'is_home' => $page->is_home,
                    'translation_locales' => $page->translations->pluck('locale')->all(),
                ];
            }
        }

        foreach (Article::forSite($site)->published()->with(['translations', 'seo.ogImage', 'featuredMedia'])->get() as $article) {
            foreach ($article->translations as $translation) {
                if (! in_array($translation->locale, $locales, true)) {
                    continue;
                }

                $body = $this->sanitizer->renderTiptapDocument($translation->body);
                $documents[] = [
                    'seoable_type' => 'article',
                    'seoable_id' => $article->id,
                    'model' => $article,
                    'locale' => $translation->locale,
                    'title' => $translation->title,
                    'description' => $translation->excerpt,
                    'url' => '/'.$routeLocales[$translation->locale].'/insights/'.$translation->slug,
                    'seo' => $article->seo->firstWhere('locale', $translation->locale),
                    'featured_image' => $article->featuredMedia,
                    'images' => $article->featuredMedia ? [['url' => $article->featuredMedia->url, 'alt' => $article->featuredMedia->altFor($translation->locale)]] : [],
                    'h1_count' => 1,
                    'word_count' => $this->wordCount($this->sanitizer->plainText($body)),
                    'internal_links' => $this->linksIn($translation->body),
                    'is_home' => false,
                    'translation_locales' => $article->translations->pluck('locale')->all(),
                ];
            }
        }

        foreach (CaseStudy::forSite($site)->published()->with(['translations', 'seo.ogImage', 'featuredMedia'])->get() as $study) {
            foreach ($study->translations as $translation) {
                if (! in_array($translation->locale, $locales, true)) {
                    continue;
                }

                $text = implode(' ', [$translation->summary, $translation->challenge, $translation->strategy, $translation->implementation]);
                $documents[] = [
                    'seoable_type' => 'case_study',
                    'seoable_id' => $study->id,
                    'model' => $study,
                    'locale' => $translation->locale,
                    'title' => $translation->title,
                    'description' => $translation->summary,
                    'url' => '/'.$routeLocales[$translation->locale].'/case-studies/'.$translation->slug,
                    'seo' => $study->seo->firstWhere('locale', $translation->locale),
                    'featured_image' => $study->featuredMedia,
                    'images' => $study->featuredMedia ? [['url' => $study->featuredMedia->url, 'alt' => $study->featuredMedia->altFor($translation->locale)]] : [],
                    'h1_count' => 1,
                    'word_count' => $this->wordCount($text),
                    'internal_links' => [],
                    'is_home' => false,
                    'translation_locales' => $study->translations->pluck('locale')->all(),
                ];
            }
        }

        foreach (Solution::forSite($site)->where('status', 'published')->with(['translations', 'seo.ogImage', 'featuredMedia'])->get() as $solution) {
            foreach ($solution->translations as $translation) {
                if (! in_array($translation->locale, $locales, true)) {
                    continue;
                }

                $documents[] = [
                    'seoable_type' => 'solution',
                    'seoable_id' => $solution->id,
                    'model' => $solution,
                    'locale' => $translation->locale,
                    'title' => $translation->title,
                    'description' => $translation->tagline,
                    'url' => '/'.$routeLocales[$translation->locale].'/solutions/'.$translation->slug,
                    'seo' => $solution->seo->firstWhere('locale', $translation->locale),
                    'featured_image' => $solution->featuredMedia,
                    'images' => $solution->featuredMedia ? [['url' => $solution->featuredMedia->url, 'alt' => $solution->featuredMedia->altFor($translation->locale)]] : [],
                    'h1_count' => 1,
                    'word_count' => $this->wordCount($translation->overview.' '.$this->flattenText($translation->capabilities)),
                    'internal_links' => [],
                    'is_home' => false,
                    'translation_locales' => $solution->translations->pluck('locale')->all(),
                ];
            }
        }

        foreach (Industry::forSite($site)->where('status', 'published')->with(['translations', 'seo.ogImage', 'featuredMedia'])->get() as $industry) {
            foreach ($industry->translations as $translation) {
                if (! in_array($translation->locale, $locales, true)) {
                    continue;
                }

                $documents[] = [
                    'seoable_type' => 'industry',
                    'seoable_id' => $industry->id,
                    'model' => $industry,
                    'locale' => $translation->locale,
                    'title' => $translation->title,
                    'description' => $translation->tagline,
                    'url' => '/'.$routeLocales[$translation->locale].'/industries/'.$translation->slug,
                    'seo' => $industry->seo->firstWhere('locale', $translation->locale),
                    'featured_image' => $industry->featuredMedia,
                    'images' => $industry->featuredMedia ? [['url' => $industry->featuredMedia->url, 'alt' => $industry->featuredMedia->altFor($translation->locale)]] : [],
                    'h1_count' => 1,
                    'word_count' => $this->wordCount($translation->overview.' '.$this->flattenText($translation->challenges)),
                    'internal_links' => [],
                    'is_home' => false,
                    'translation_locales' => $industry->translations->pluck('locale')->all(),
                ];
            }
        }

        foreach (JobPosting::forSite($site)->open()->with(['translations'])->get() as $job) {
            foreach ($job->translations as $translation) {
                if (! in_array($translation->locale, $locales, true)) {
                    continue;
                }

                $documents[] = [
                    'seoable_type' => 'job_posting',
                    'seoable_id' => $job->id,
                    'model' => $job,
                    'locale' => $translation->locale,
                    'title' => $translation->title,
                    'description' => $translation->summary,
                    'url' => '/'.$routeLocales[$translation->locale].'/careers/'.$translation->slug,
                    'seo' => null,
                    'featured_image' => null,
                    'images' => [],
                    'h1_count' => 1,
                    'word_count' => $this->wordCount($translation->summary),
                    'internal_links' => [],
                    'is_home' => false,
                    'translation_locales' => $job->translations->pluck('locale')->all(),
                ];
            }
        }

        return $documents;
    }

    protected function inspect(array $document, array $documents, Site $site, array $locales): array
    {
        $issues = [];
        $seo = $document['seo'];
        $title = $seo?->title ?: $document['title'];
        $description = $seo?->description ?: $document['description'];
        $add = function (string $code, string $severity, string $explanation, string $recommendation, ?string $locale = null) use (&$issues, $document) {
            $issues[] = [
                'seoable_type' => $document['seoable_type'],
                'seoable_id' => $document['seoable_id'],
                'locale' => $locale ?? $document['locale'],
                'code' => $code,
                'severity' => $severity,
                'url' => $document['url'],
                'title' => $document['title'],
                'explanation' => $explanation,
                'recommendation' => $recommendation,
            ];
        };

        if (! $title) {
            $add('missing_title', 'critical', 'The page has no SEO title or editorial title.', 'Add a unique, descriptive title of roughly 50–60 characters.');
        } elseif (mb_strlen($title) > 65) {
            $add('title_too_long', 'warning', 'The title is '.mb_strlen($title).' characters and may be truncated in search results.', 'Shorten the title to about 60 characters without losing the subject.');
        } elseif (mb_strlen($title) < 20) {
            $add('title_too_short', 'info', 'The title is short and may lack context in search results.', 'Add the subject or audience to make the title more specific.');
        }

        if (! $description) {
            $add('missing_description', 'critical', 'No meta description or excerpt is available.', 'Write a specific 120–160 character description that states the page\'s value.');
        } elseif (mb_strlen($description) > 165) {
            $add('description_too_long', 'warning', 'The meta description is '.mb_strlen($description).' characters and may be truncated.', 'Tighten the description to roughly 155 characters.');
        }

        if ($description && mb_strlen($description) < 70) {
            $add('description_too_short', 'info', 'The meta description is short.', 'Expand it with the outcome or the audience it serves.');
        }

        if ((int) $document['h1_count'] === 0) {
            $add('missing_h1', 'critical', 'The rendered page has no H1 heading.', 'Add a hero or page header block with a headline, or use a template that renders the title as H1.');
        } elseif ((int) $document['h1_count'] > 1) {
            $add('multiple_h1', 'warning', 'The page renders more than one H1 heading.', 'Keep one H1 and demote other section headlines to H2.');
        }

        foreach ($document['images'] as $image) {
            if (empty(trim((string) ($image['alt'] ?? '')))) {
                $add('missing_image_alt', 'warning', 'An image has no alternative text.', 'Describe the image purpose in the alt field, or mark it decorative with an empty alt only when it adds no information.');
                break;
            }
        }

        if (! empty($seo?->canonical) && ! preg_match('/^https?:\/\//i', $seo->canonical)) {
            $add('invalid_canonical', 'warning', 'The canonical URL is not absolute.', 'Use a full https canonical URL, or leave it empty to use the generated canonical.');
        }

        if (! $seo?->ogImage?->url && ! $document['featured_image'] && empty($document['images']) && ! data_get($site->seo_defaults, 'og_image_url')) {
            $add('missing_og_image', 'warning', 'No social sharing image is available.', 'Choose an Open Graph image at least 1200×630 or attach a featured image.');
        }

        $minimumWords = match ($document['seoable_type']) {
            'article' => 300,
            'case_study' => 180,
            'solution', 'industry' => 140,
            default => 100,
        };

        if (! str_contains((string) $seo?->robots, 'noindex') && $document['word_count'] < $minimumWords) {
            $add('thin_content', 'warning', 'The page contains about '.$document['word_count'].' words.', "Add substantive, specific content; aim for at least {$minimumWords} words for this template.");
        }

        foreach ($locales as $locale) {
            if ($locale !== $document['locale'] && ! in_array($locale, $document['translation_locales'], true)) {
                $add('missing_translation', 'info', "No {$locale} translation exists for this content.", "Create a {$locale} translation, or keep the locale disabled for this site if it is not maintained.", $locale);
            }
        }

        if ($document['seoable_type'] === 'page' && ! $document['is_home']) {
            // Orphan detection happens once across all documents below; keep this branch
            // so the intent is explicit in the report.
        }

        return $issues;
    }

    protected function duplicateIssues(array $documents): array
    {
        $issues = [];
        $byLocale = collect($documents)->groupBy('locale');

        foreach ($byLocale as $locale => $docs) {
            foreach (['title' => 'duplicate_title', 'description' => 'duplicate_description'] as $field => $code) {
                $groups = $docs->filter(fn ($doc) => ! empty($doc[$field]))->groupBy(fn ($doc) => mb_strtolower(trim($doc[$field])));

                foreach ($groups as $value => $group) {
                    if ($group->count() < 2) {
                        continue;
                    }

                    foreach ($group as $doc) {
                        $issues[] = [
                            'seoable_type' => $doc['seoable_type'],
                            'seoable_id' => $doc['seoable_id'],
                            'locale' => $locale,
                            'code' => $code,
                            'severity' => 'warning',
                            'url' => $doc['url'],
                            'title' => $doc['title'],
                            'explanation' => ucfirst($field).' is shared by '.$group->count().' documents in this locale.',
                            'recommendation' => 'Give every page a unique '.$field.' that describes its specific subject.',
                        ];
                    }
                }
            }
        }

        return $issues;
    }

    protected function internalLinkIssues(array $documents, Site $site): array
    {
        $issues = [];
        $known = collect($documents)->pluck('url')->map(fn ($url) => rtrim($url, '/'))->all();
        $known = array_merge($known, [
            '/th', '/en', '/zh-cn',
            '/th/solutions', '/en/solutions', '/zh-cn/solutions',
            '/th/industries', '/en/industries', '/zh-cn/industries',
            '/th/case-studies', '/en/case-studies', '/zh-cn/case-studies',
            '/th/insights', '/en/insights', '/zh-cn/insights',
            '/th/careers', '/en/careers', '/zh-cn/careers',
            '/th/investor-relations', '/en/investor-relations', '/zh-cn/investor-relations',
            '/th/contact', '/en/contact', '/zh-cn/contact',
            '/th/search', '/en/search', '/zh-cn/search',
        ]);

        $linked = [];

        $aliases = [];
        foreach (Page::forSite($site)->with('translations')->get() as $page) {
            foreach ($site->activeLocales() as $code) {
                $translation = $page->translations->firstWhere('locale', $code)
                    ?? $page->translations->firstWhere('locale', config('meridian.fallback_locale'))
                    ?? $page->translations->firstWhere('locale', $site->default_locale);

                if (! $translation) {
                    continue;
                }

                foreach ($page->translations as $candidate) {
                    $aliases[$code][$candidate->slug] = ['slug' => $translation->slug, 'locale' => $translation->locale];
                }
            }
        }

        // Navigation is a real discovery path and must count as an internal link.
        foreach (NavigationItem::query()->whereHas('menu', fn ($query) => $query->where('site_id', $site->id))->pluck('url') as $navUrl) {
            $path = parse_url($navUrl, PHP_URL_PATH) ?: $navUrl;
            $linked['/'.ltrim(rtrim($path, '/'), '/')] = true;
        }

        foreach ($documents as $document) {
            foreach ($document['internal_links'] as $link) {
                $path = parse_url($link, PHP_URL_PATH) ?: $link;
                $path = $this->normalizeInternalPath('/'.ltrim(rtrim($path, '/'), '/'), $document['locale'], $aliases);
                $linked[$path] = true;

                if (preg_match('/\.(pdf|jpg|jpeg|png|webp)$/i', $path)) {
                    continue;
                }

                if (! in_array($path, $known, true) && ! in_array($path.'/', $known, true)) {
                    $issues[] = [
                        'seoable_type' => $document['seoable_type'],
                        'seoable_id' => $document['seoable_id'],
                        'locale' => $document['locale'],
                        'code' => 'broken_internal_link',
                        'severity' => 'warning',
                        'url' => $document['url'],
                        'title' => $document['title'],
                        'explanation' => "The internal link {$path} does not resolve to a published URL.",
                        'recommendation' => 'Update the link to a published page or add a redirect for the old path.',
                    ];
                }
            }
        }

        // Navigation links are not part of block props; pages reachable from the
        // footer or header are therefore treated as linked through the seeded nav
        // and the known section index list above.
        foreach ($documents as $document) {
            if ($document['seoable_type'] !== 'page' || $document['is_home']) {
                continue;
            }

            $path = rtrim($document['url'], '/');

            if (! isset($linked[$path])) {
                $issues[] = [
                    'seoable_type' => 'page',
                    'seoable_id' => $document['seoable_id'],
                    'locale' => $document['locale'],
                    'code' => 'orphan_page',
                    'severity' => 'warning',
                    'url' => $document['url'],
                    'title' => $document['title'],
                    'explanation' => 'No editorial block or navigation item links to this page.',
                    'recommendation' => 'Add an internal link from a relevant page or navigation menu so readers and crawlers can discover it.',
                ];
            }
        }

        return $issues;
    }

    /**
     * Block URLs are authored without a locale prefix. Normalise them the same
     * way the presenter does before checking whether the target is published.
     */
    protected function normalizeInternalPath(string $path, string $locale, array $aliases = []): string
    {
        if (preg_match('/\.(pdf|jpe?g|png|webp|gif|svg)$/i', $path)) {
            return $path;
        }

        $routeLocales = collect(array_keys(config('meridian.locales')))
            ->map(fn ($code) => config('meridian.locales')[$code]['route'] ?? strtolower($code));

        if ($routeLocales->contains(fn ($code) => $path === '/'.$code || str_starts_with($path, '/'.$code.'/'))) {
            return $path;
        }

        $slug = trim($path, '/');

        if (! str_contains($slug, '/') && isset($aliases[$locale][$slug])) {
            $target = $aliases[$locale][$slug];

            if ($target['locale'] !== $locale) {
                $targetRoute = config('meridian.locales')[$target['locale']]['route'] ?? strtolower($target['locale']);

                return '/'.$targetRoute.'/'.$target['slug'];
            }

            $slug = $target['slug'];
        }

        $route = config('meridian.locales')[$locale]['route'] ?? strtolower($locale);

        return '/'.$route.($slug !== '' ? '/'.$slug : '');
    }

    protected function redirectIssues(Site $site, array $locales): array
    {
        $issues = [];
        $redirects = Redirect::forSite($site)->where('is_active', true)->get();
        $from = $redirects->pluck('from_path')->all();

        foreach ($redirects as $redirect) {
            if ($redirect->from_path === $redirect->to_path) {
                $issues[] = $this->redirectIssue($redirect, 'redirect_loop', 'The redirect points to itself.', 'Point it to a different published URL or remove it.');

                continue;
            }

            if (in_array($redirect->to_path, $from, true)) {
                $issues[] = $this->redirectIssue($redirect, 'redirect_chain', 'The redirect target is itself redirected.', 'Point the redirect directly at the final destination.');
            }
        }

        return $issues;
    }

    protected function redirectIssue(Redirect $redirect, string $code, string $explanation, string $recommendation): array
    {
        return [
            'seoable_type' => 'redirect',
            'seoable_id' => $redirect->id,
            'locale' => $redirect->locale ?? 'th',
            'code' => $code,
            'severity' => 'warning',
            'url' => $redirect->from_path,
            'title' => 'Redirect '.$redirect->from_path,
            'explanation' => $explanation,
            'recommendation' => $recommendation,
        ];
    }

    protected function dedupe(array $issues): array
    {
        return collect($issues)->unique(fn ($issue) => implode('|', [
            $issue['seoable_type'] ?? 'none',
            $issue['seoable_id'] ?? '0',
            $issue['locale'],
            $issue['code'],
        ]))->values()->all();
    }

    protected function imagesIn(mixed $value): array
    {
        $images = [];

        if (is_array($value)) {
            if (isset($value['url']) && array_key_exists('alt', $value)) {
                $images[] = ['url' => $value['url'], 'alt' => $value['alt']];
            }

            foreach ($value as $item) {
                $images = array_merge($images, $this->imagesIn($item));
            }
        }

        return $images;
    }

    protected function linksIn(mixed $value): array
    {
        $links = [];

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                if (is_string($item) && in_array($key, ['url', 'href', 'link_url', 'canonical'], true)) {
                    if (preg_match('/^(\/|https?:\/\/)/i', $item)) {
                        $links[] = $item;
                    }
                } elseif (is_array($item)) {
                    $links = array_merge($links, $this->linksIn($item));
                }
            }
        } elseif (is_string($value)) {
            preg_match_all('/href=["\']([^"\']+)["\']/i', $value, $matches);
            foreach ($matches[1] ?? [] as $link) {
                if (preg_match('/^(\/|https?:\/\/)/i', $link)) {
                    $links[] = $link;
                }
            }
        }

        return array_values(array_unique($links));
    }

    protected function flattenText(mixed $value): string
    {
        if (is_string($value)) {
            return strip_tags($value);
        }

        if (is_array($value)) {
            return collect($value)->map(fn ($item) => $this->flattenText($item))->implode(' ');
        }

        return '';
    }

    protected function wordCount(string $text): int
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($text)) ?? '');

        if ($text === '') {
            return 0;
        }

        $words = preg_split('/\s+/u', $text) ?: [];
        $thaiChars = preg_match_all('/[\x{0E00}-\x{0E7F}]/u', $text);

        // Thai does not use spaces between words; approximate from characters.
        return max(count($words), (int) round(($thaiChars ?: 0) / 4));
    }
}
