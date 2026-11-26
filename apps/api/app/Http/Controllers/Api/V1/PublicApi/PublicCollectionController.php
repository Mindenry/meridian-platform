<?php

namespace App\Http\Controllers\Api\V1\PublicApi;

use App\Cms\BlockResolver;
use App\Cms\ContentPresenter;
use App\Cms\SiteContext;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\CaseStudy;
use App\Models\Document;
use App\Models\Industry;
use App\Models\JobPosting;
use App\Models\Solution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class PublicCollectionController extends Controller
{
    public function __construct(
        protected ContentPresenter $presenter,
        protected BlockResolver $blocks,
        protected SiteContext $context,
    ) {}

    public function solutions(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $locale = $this->context->locale();

        return response()->json([
            'data' => $this->blocks->solutions($site, $locale, 20),
        ]);
    }

    public function solution(Request $request, string $slug): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $locale = $this->context->locale();

        $solution = $this->firstTranslated(
            Solution::forSite($site)->where('status', 'published')
                ->with(['translations', 'featuredMedia', 'industries.translations', 'industries.featuredMedia', 'seo.ogImage']),
            $locale,
            $site->default_locale,
            $slug
        );

        abort_unless($solution, 404, 'Solution not found.');

        return response()->json(['data' => $this->presenter->solution($solution, $locale)]);
    }

    public function industries(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $locale = $this->context->locale();

        return response()->json([
            'data' => $this->blocks->industries($site, $locale, 20),
        ]);
    }

    public function industry(Request $request, string $slug): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $locale = $this->context->locale();

        $industry = $this->firstTranslated(
            Industry::forSite($site)->where('status', 'published')
                ->with(['translations', 'featuredMedia', 'solutions.translations', 'solutions.featuredMedia', 'seo.ogImage']),
            $locale,
            $site->default_locale,
            $slug
        );

        abort_unless($industry, 404, 'Industry not found.');

        return response()->json(['data' => $this->presenter->industry($industry, $locale)]);
    }

    public function caseStudies(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $locale = $this->context->locale();
        $perPage = min(24, max(1, (int) $request->query('per_page', 9)));

        $query = CaseStudy::forSite($site)->published()
            ->with(['translations', 'featuredMedia', 'industry.translations', 'solutions.translations'])
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at');

        if ($industry = $request->query('industry')) {
            $query->whereHas('industry', fn ($q) => $q->where('key', $industry));
        }

        if ($q = trim((string) $request->query('q'))) {
            $like = '%'.$q.'%';
            $query->whereHas('translations', fn ($translation) => $translation
                ->where('locale', $locale)
                ->where(fn ($inner) => $inner->where('title', 'like', $like)->orWhere('summary', 'like', $like)));
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'data' => collect($paginator->items())
                ->map(fn (CaseStudy $study) => $this->blocks->caseStudySummary($study, $locale))
                ->filter()->values()->all(),
            'meta' => $this->meta($paginator),
            'filters' => [
                'industries' => $this->blocks->industries($site, $locale, 10),
            ],
        ]);
    }

    public function caseStudy(Request $request, string $slug): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $locale = $this->context->locale();

        $study = $this->firstTranslated(
            CaseStudy::forSite($site)->published()
                ->with(['translations', 'featuredMedia', 'industry.translations', 'industry.featuredMedia', 'solutions.translations', 'solutions.featuredMedia', 'seo.ogImage']),
            $locale,
            $site->default_locale,
            $slug
        );

        abort_unless($study, 404, 'Case study not found.');

        return response()->json(['data' => $this->presenter->caseStudy($study, $locale)]);
    }

    public function articles(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $locale = $this->context->locale();
        $perPage = min(24, max(1, (int) $request->query('per_page', 9)));

        $query = Article::forSite($site)->published()
            ->with(['translations', 'featuredMedia', 'category', 'author'])
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at');

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($category = $request->query('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $category));
        }

        if ($q = trim((string) $request->query('q'))) {
            $like = '%'.$q.'%';
            $query->whereHas('translations', fn ($translation) => $translation
                ->where('locale', $locale)
                ->where(fn ($inner) => $inner->where('title', 'like', $like)->orWhere('excerpt', 'like', $like)));
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'data' => collect($paginator->items())
                ->map(fn (Article $article) => $this->blocks->articleSummary($article, $locale))
                ->filter()->values()->all(),
            'meta' => $this->meta($paginator),
            'filters' => [
                'categories' => ArticleCategory::forSite($site)->orderBy('sort')->get()->map(fn ($category) => [
                    'slug' => $category->slug,
                    'name' => $category->labelFor($locale),
                ])->all(),
                'types' => Article::TYPES,
            ],
        ]);
    }

    public function article(Request $request, string $slug): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $locale = $this->context->locale();

        $article = $this->firstTranslated(
            Article::forSite($site)->published()
                ->with(['translations', 'featuredMedia', 'category', 'author', 'tags', 'seo.ogImage']),
            $locale,
            $site->default_locale,
            $slug
        );

        abort_unless($article, 404, 'Article not found.');

        $article->increment('view_count');

        return response()->json(['data' => $this->presenter->article($article, $locale)]);
    }

    public function careers(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $locale = $this->context->locale();

        $jobs = JobPosting::forSite($site)->open()
            ->with('translations')
            ->orderBy('sort')
            ->get()
            ->map(fn (JobPosting $job) => $this->blocks->jobSummary($job, $locale))
            ->filter()->values()->all();

        return response()->json([
            'data' => $jobs,
            'filters' => [
                'departments' => collect($jobs)->pluck('department')->filter()->unique()->values()->all(),
            ],
        ]);
    }

    public function job(Request $request, string $slug): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $locale = $this->context->locale();

        $job = $this->firstTranslated(
            JobPosting::forSite($site)->open()->with(['translations']),
            $locale,
            $site->default_locale,
            $slug
        );

        abort_unless($job, 404, 'Job not found.');

        return response()->json(['data' => $this->presenter->job($job, $locale)]);
    }

    public function documents(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $locale = $this->context->locale();

        $query = Document::forSite($site)->with('media')->orderByDesc('year')->orderBy('sort');

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        return response()->json([
            'data' => $query->get()->map(fn (Document $document) => $this->blocks->documentSummary($document, $locale))->all(),
        ]);
    }

    public function team(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $locale = $this->context->locale();

        return response()->json([
            'data' => $this->blocks->team($site, $locale, ['leadership_only' => false, 'limit' => 30]),
        ]);
    }

    protected function meta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    protected function firstTranslated($query, string $locale, string $defaultLocale, string $slug)
    {
        $model = (clone $query)
            ->whereHas('translations', fn ($translation) => $translation->where('locale', $locale)->where('slug', $slug))
            ->first();

        if (! $model && $locale !== $defaultLocale) {
            $model = (clone $query)
                ->whereHas('translations', fn ($translation) => $translation->where('locale', $defaultLocale)->where('slug', $slug))
                ->first();
        }

        return $model;
    }
}
