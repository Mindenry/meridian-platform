<?php

namespace App\Cms;

use App\Models\ArticleTranslation;
use App\Models\CaseStudyTranslation;
use App\Models\IndustryTranslation;
use App\Models\JobPostingTranslation;
use App\Models\PageTranslation;
use App\Models\Site;
use App\Models\SolutionTranslation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SearchService
{
    public function __construct(protected SiteContext $context) {}

    public function search(Site $site, string $locale, string $query, int $limit = 20): array
    {
        $query = trim($query);

        if (mb_strlen($query) < 2) {
            return [
                'query' => $query,
                'groups' => [],
                'total' => 0,
                'engine' => 'database',
                'message' => 'Enter at least two characters to search.',
            ];
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $query).'%';
        $groups = [];

        if (config('meridian.search.driver') === 'meilisearch') {
            $remote = $this->searchMeilisearch($site, $locale, $query, $limit);

            if ($remote !== null && $remote['total'] > 0) {
                return $remote + ['searched_locale' => $locale];
            }
        }

        $pageMatches = PageTranslation::query()
            ->where('site_id', $site->id)
            ->where('locale', $locale)
            ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('excerpt', 'like', $like)->orWhere('slug', 'like', $like))
            ->with('page')
            ->limit($limit)
            ->get()
            ->filter(fn ($translation) => $translation->page?->isPublished())
            ->map(fn ($translation) => $this->result(
                'page',
                $translation->title,
                $translation->excerpt,
                $this->presenterPath($locale, $translation->slug),
                $query,
            ));

        if ($pageMatches->isNotEmpty()) {
            $groups[] = ['type' => 'page', 'label' => 'Pages', 'items' => $pageMatches->values()->all()];
        }

        $articleMatches = ArticleTranslation::query()
            ->where('site_id', $site->id)
            ->where('locale', $locale)
            ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('excerpt', 'like', $like))
            ->with(['article.featuredMedia', 'article.category'])
            ->limit($limit)
            ->get()
            ->filter(fn ($translation) => $translation->article?->status === 'published')
            ->map(fn ($translation) => $this->result(
                'article',
                $translation->title,
                $translation->excerpt,
                $this->presenterPath($locale, 'insights/'.$translation->slug),
                $query,
                ['date' => $translation->article?->published_at?->toDateString(), 'category' => $translation->article?->category?->labelFor($locale)],
            ));

        if ($articleMatches->isNotEmpty()) {
            $groups[] = ['type' => 'article', 'label' => 'Insights', 'items' => $articleMatches->values()->all()];
        }

        $caseMatches = CaseStudyTranslation::query()
            ->where('site_id', $site->id)
            ->where('locale', $locale)
            ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('summary', 'like', $like))
            ->with('caseStudy')
            ->limit($limit)
            ->get()
            ->filter(fn ($translation) => $translation->caseStudy?->status === 'published')
            ->map(fn ($translation) => $this->result(
                'case_study',
                $translation->title,
                $translation->summary,
                $this->presenterPath($locale, 'case-studies/'.$translation->slug),
                $query,
                ['year' => $translation->caseStudy?->year],
            ));

        if ($caseMatches->isNotEmpty()) {
            $groups[] = ['type' => 'case_study', 'label' => 'Case studies', 'items' => $caseMatches->values()->all()];
        }

        $solutionMatches = SolutionTranslation::query()
            ->where('site_id', $site->id)
            ->where('locale', $locale)
            ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('tagline', 'like', $like)->orWhere('overview', 'like', $like))
            ->with('solution')
            ->limit($limit)
            ->get()
            ->filter(fn ($translation) => $translation->solution?->status === 'published')
            ->map(fn ($translation) => $this->result(
                'solution',
                $translation->title,
                $translation->tagline,
                $this->presenterPath($locale, 'solutions/'.$translation->slug),
                $query,
            ));

        if ($solutionMatches->isNotEmpty()) {
            $groups[] = ['type' => 'solution', 'label' => 'Solutions', 'items' => $solutionMatches->values()->all()];
        }

        $industryMatches = IndustryTranslation::query()
            ->where('site_id', $site->id)
            ->where('locale', $locale)
            ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('tagline', 'like', $like))
            ->with('industry')
            ->limit($limit)
            ->get()
            ->filter(fn ($translation) => $translation->industry?->status === 'published')
            ->map(fn ($translation) => $this->result(
                'industry',
                $translation->title,
                $translation->tagline,
                $this->presenterPath($locale, 'industries/'.$translation->slug),
                $query,
            ));

        if ($industryMatches->isNotEmpty()) {
            $groups[] = ['type' => 'industry', 'label' => 'Industries', 'items' => $industryMatches->values()->all()];
        }

        $jobMatches = JobPostingTranslation::query()
            ->where('site_id', $site->id)
            ->where('locale', $locale)
            ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('summary', 'like', $like))
            ->with('jobPosting')
            ->limit($limit)
            ->get()
            ->filter(fn ($translation) => $translation->jobPosting?->status === 'open')
            ->map(fn ($translation) => $this->result(
                'job',
                $translation->title,
                $translation->summary,
                $this->presenterPath($locale, 'careers/'.$translation->slug),
                $query,
                ['department' => $translation->jobPosting?->department],
            ));

        if ($jobMatches->isNotEmpty()) {
            $groups[] = ['type' => 'job', 'label' => 'Careers', 'items' => $jobMatches->values()->all()];
        }

        $total = collect($groups)->sum(fn ($group) => count($group['items']));

        return [
            'query' => $query,
            'groups' => $groups,
            'total' => $total,
            'engine' => 'database',
            'searched_locale' => $locale,
        ];
    }

    /**
     * Query Meilisearch. Returns null when the service is unavailable so the
     * caller can fall back to the deterministic database search.
     */
    protected function searchMeilisearch(Site $site, string $locale, string $query, int $limit): ?array
    {
        try {
            $response = Http::baseUrl(rtrim((string) config('meridian.search.meilisearch_host'), '/'))
                ->withToken((string) config('meridian.search.meilisearch_key'))
                ->acceptJson()
                ->asJson()
                ->timeout(3)
                ->post('/indexes/'.SearchIndexer::INDEX.'/search', [
                    'q' => $query,
                    'limit' => max($limit * 6, 20),
                    'filter' => 'site_id = '.$site->id.' AND locale = "'.$locale.'"',
                ]);

            if (! $response->successful()) {
                return null;
            }
        } catch (\Throwable) {
            return null;
        }

        $labels = [
            'page' => 'Pages',
            'article' => 'Insights',
            'case_study' => 'Case studies',
            'solution' => 'Solutions',
            'industry' => 'Industries',
            'job_posting' => 'Careers',
        ];

        $groups = collect($response->json('hits', []))
            ->groupBy('type')
            ->map(function (Collection $hits, string $type) use ($query, $labels) {
                return [
                    'type' => $type === 'job_posting' ? 'job' : $type,
                    'label' => $labels[$type] ?? Str::headline($type),
                    'items' => $hits->take(20)->map(fn (array $hit) => $this->result(
                        $type === 'job_posting' ? 'job' : $type,
                        $hit['title'] ?? '',
                        $hit['excerpt'] ?? '',
                        $hit['url'] ?? '/',
                        $query,
                        $hit['meta'] ?? [],
                    ))->values()->all(),
                ];
            })
            ->values()
            ->all();

        return [
            'query' => $query,
            'groups' => $groups,
            'total' => collect($groups)->sum(fn ($group) => count($group['items'])),
            'engine' => 'meilisearch',
        ];
    }

    public function suggestions(Site $site, string $locale, string $query): array
    {
        $results = $this->search($site, $locale, $query, 3);

        return collect($results['groups'])->flatMap(fn ($group) => $group['items'])
            ->take(6)
            ->map(fn ($item) => ['title' => strip_tags($item['title']), 'url' => $item['url'], 'type' => $item['type']])
            ->values()
            ->all();
    }

    protected function result(string $type, string $title, ?string $excerpt, string $url, string $query, array $meta = []): array
    {
        $plainExcerpt = strip_tags((string) $excerpt);

        return [
            'type' => $type,
            'title' => $this->highlight($title, $query),
            'excerpt' => $this->highlight(Str::limit($plainExcerpt, 180), $query),
            'url' => $url,
            'meta' => $meta,
        ];
    }

    protected function highlight(string $text, string $query): string
    {
        $escaped = e($text);
        $escapedQuery = e($query);

        return preg_replace(
            '/('.preg_quote($escapedQuery, '/').')/iu',
            '<mark>$1</mark>',
            $escaped,
            3
        ) ?? $escaped;
    }

    protected function presenterPath(string $locale, string $path): string
    {
        $routeLocale = config('meridian.locales')[$locale]['route'] ?? strtolower($locale);

        return '/'.$routeLocale.'/'.ltrim($path, '/');
    }
}
