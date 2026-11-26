<?php

namespace App\Cms;

use App\Models\Article;
use App\Models\CaseStudy;
use App\Models\Document;
use App\Models\FinancialHighlight;
use App\Models\Form;
use App\Models\Industry;
use App\Models\JobPosting;
use App\Models\Site;
use App\Models\Solution;
use App\Models\TeamMember;
use Illuminate\Support\Collection;

class BlockResolver
{
    public function __construct(protected SiteContext $context) {}

    public function resolve(array $blocks, Site $site, string $locale): array
    {
        return array_map(function (array $block) use ($site, $locale) {
            $resolved = match ($block['type']) {
                'services_grid' => ['items' => $this->solutions($site, $locale, (int) ($block['props']['limit'] ?? 6))],
                'industry_grid' => ['items' => $this->industries($site, $locale, (int) ($block['props']['limit'] ?? 5))],
                'project_grid' => $this->projects($site, $locale, $block['props']),
                'featured_project' => ['project' => $this->featuredProject($site, $locale, $block['props']['project_slug'] ?? '')],
                'latest_insights', 'article_list' => ['items' => $this->articles($site, $locale, $block['props'])],
                'team_grid' => ['items' => $this->team($site, $locale, $block['props'])],
                'job_list' => ['items' => $this->jobs($site, $locale, $block['props'])],
                'document_list' => ['items' => $this->documents($site, $locale, $block['props'])],
                'investor_highlights' => ['years' => $this->financialHighlights($site, $locale, (int) ($block['props']['years'] ?? 3))],
                'contact_form' => $this->contactForm($site, $locale, $block['props']['form_key'] ?? 'contact'),
                default => [],
            };

            if ($resolved !== []) {
                $block['resolved'] = $resolved;
            }

            return $block;
        }, $blocks);
    }

    public function solutions(Site $site, string $locale, int $limit = 6): array
    {
        return Solution::forSite($site)
            ->where('status', 'published')
            ->with(['translations', 'featuredMedia'])
            ->orderBy('sort')
            ->limit($limit)
            ->get()
            ->map(fn (Solution $solution) => $this->solutionSummary($solution, $locale))
            ->filter()
            ->values()
            ->all();
    }

    public function industries(Site $site, string $locale, int $limit = 5): array
    {
        return Industry::forSite($site)
            ->where('status', 'published')
            ->with(['translations', 'featuredMedia'])
            ->orderBy('sort')
            ->limit($limit)
            ->get()
            ->map(fn (Industry $industry) => $this->industrySummary($industry, $locale))
            ->filter()
            ->values()
            ->all();
    }

    public function projects(Site $site, string $locale, array $props = []): array
    {
        $query = CaseStudy::forSite($site)
            ->published()
            ->with(['translations', 'featuredMedia', 'industry.translations', 'solutions.translations'])
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at');

        if (! empty($props['industry'])) {
            $query->whereHas('industry', fn ($q) => $q->where('key', $props['industry']));
        }

        $items = $query->limit((int) ($props['limit'] ?? 6))->get()
            ->map(fn (CaseStudy $study) => $this->caseStudySummary($study, $locale))
            ->filter()
            ->values()
            ->all();

        return [
            'items' => $items,
            'industries' => $this->industries($site, $locale, 10),
        ];
    }

    public function featuredProject(Site $site, string $locale, string $slug): ?array
    {
        $study = CaseStudy::forSite($site)
            ->published()
            ->with(['translations', 'featuredMedia', 'industry.translations', 'solutions.translations'])
            ->whereHas('translations', fn ($q) => $q->where('slug', $slug))
            ->first();

        if (! $study) {
            $study = CaseStudy::forSite($site)->published()
                ->with(['translations', 'featuredMedia', 'industry.translations', 'solutions.translations'])
                ->orderByDesc('is_featured')
                ->first();
        }

        return $study ? $this->caseStudySummary($study, $locale) : null;
    }

    public function articles(Site $site, string $locale, array $props = []): array
    {
        $query = Article::forSite($site)
            ->published()
            ->with(['translations', 'featuredMedia', 'category', 'author'])
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at');

        if (! empty($props['category'])) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $props['category']));
        }

        if (! empty($props['type'])) {
            $query->where('type', $props['type']);
        }

        return $query->limit((int) ($props['limit'] ?? 3))->get()
            ->map(fn (Article $article) => $this->articleSummary($article, $locale))
            ->filter()
            ->values()
            ->all();
    }

    public function team(Site $site, string $locale, array $props = []): array
    {
        $query = TeamMember::forSite($site)->with(['photo']);

        if (! empty($props['leadership_only'])) {
            $query->where('is_leadership', true);
        }

        return $query->orderBy('sort')->limit((int) ($props['limit'] ?? 8))->get()
            ->map(fn (TeamMember $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'role' => $member->label('role', $locale),
                'department' => $member->department,
                'bio' => $member->label('bio', $locale),
                'initials' => $this->initials($member->name),
                'photo' => $member->photo?->toPublicArray($locale),
                'linkedin' => $member->social['linkedin'] ?? null,
            ])
            ->all();
    }

    public function jobs(Site $site, string $locale, array $props = []): array
    {
        $query = JobPosting::forSite($site)->open()->with(['translations']);

        if (! empty($props['department'])) {
            $query->where('department', $props['department']);
        }

        return $query->orderBy('sort')->limit((int) ($props['limit'] ?? 12))->get()
            ->map(fn (JobPosting $job) => $this->jobSummary($job, $locale))
            ->filter()
            ->values()
            ->all();
    }

    public function documents(Site $site, string $locale, array $props = []): array
    {
        $query = Document::forSite($site)->with('media');

        if (! empty($props['category'])) {
            $query->where('category', $props['category']);
        }

        return $query->orderByDesc('year')->orderBy('sort')->limit((int) ($props['limit'] ?? 12))->get()
            ->map(fn (Document $document) => $this->documentSummary($document, $locale))
            ->all();
    }

    public function financialHighlights(Site $site, string $locale, int $years = 3): array
    {
        return FinancialHighlight::forSite($site)
            ->orderByDesc('year')
            ->orderBy('sort')
            ->get()
            ->groupBy('year')
            ->take($years)
            ->map(fn (Collection $items, $year) => [
                'year' => (int) $year,
                'illustrative' => true,
                'items' => $items->map(fn (FinancialHighlight $highlight) => [
                    'label' => $highlight->label[$locale] ?? $highlight->label['en'] ?? '',
                    'value' => $highlight->value,
                    'unit' => $highlight->unit,
                    'note' => $highlight->note,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    public function contactForm(Site $site, string $locale, string $key): array
    {
        $form = Form::forSite($site)->where('key', $key)->where('is_active', true)->first();

        return ['form' => $form ? $this->formSchema($form, $locale) : null];
    }

    public function formSchema(Form $form, string $locale): array
    {
        $schema = $form->schema;
        $fields = collect($schema['fields'] ?? [])->map(function (array $field) use ($locale) {
            $field['label'] = $this->localize($field['label'] ?? [], $locale);
            $field['placeholder'] = $this->localize($field['placeholder'] ?? [], $locale);
            $field['options'] = collect($field['options'] ?? [])->map(fn (array $option) => [
                'value' => $option['value'],
                'label' => $this->localize($option['label'] ?? [], $locale),
            ])->all();

            return $field;
        })->all();

        return [
            'key' => $form->key,
            'name' => $form->name,
            'description' => $form->description,
            'fields' => $fields,
            'settings' => $form->settings,
        ];
    }

    public function solutionSummary(Solution $solution, string $locale): ?array
    {
        $translation = $this->translationFor($solution, $locale);

        if (! $translation) {
            return null;
        }

        return [
            'id' => $solution->id,
            'key' => $solution->key,
            'title' => $translation->title,
            'slug' => $translation->slug,
            'tagline' => $translation->tagline,
            'overview' => $translation->overview,
            'icon' => $solution->icon,
            'image' => $solution->featuredMedia?->toPublicArray($locale),
            'url' => $this->url($locale, 'solutions/'.$translation->slug),
        ];
    }

    public function industrySummary(Industry $industry, string $locale): ?array
    {
        $translation = $this->translationFor($industry, $locale);

        if (! $translation) {
            return null;
        }

        return [
            'id' => $industry->id,
            'key' => $industry->key,
            'title' => $translation->title,
            'slug' => $translation->slug,
            'tagline' => $translation->tagline,
            'icon' => $industry->icon,
            'image' => $industry->featuredMedia?->toPublicArray($locale),
            'url' => $this->url($locale, 'industries/'.$translation->slug),
        ];
    }

    public function caseStudySummary(CaseStudy $study, string $locale): ?array
    {
        $translation = $this->translationFor($study, $locale);

        if (! $translation) {
            return null;
        }

        return [
            'id' => $study->id,
            'title' => $translation->title,
            'slug' => $translation->slug,
            'summary' => $translation->summary,
            'year' => $study->year,
            'client_label' => $study->client_label,
            'industry' => $study->industry ? $this->industrySummary($study->industry, $locale) : null,
            'image' => $study->featuredMedia?->toPublicArray($locale),
            'results' => collect($translation->results ?? [])->take(2)->all(),
            'url' => $this->url($locale, 'case-studies/'.$translation->slug),
        ];
    }

    public function articleSummary(Article $article, string $locale): ?array
    {
        $translation = $this->translationFor($article, $locale);

        if (! $translation) {
            return null;
        }

        return [
            'id' => $article->id,
            'title' => $translation->title,
            'slug' => $translation->slug,
            'excerpt' => $translation->excerpt,
            'type' => $article->type,
            'reading_time' => $article->reading_time,
            'published_at' => $article->published_at?->toIso8601String(),
            'is_featured' => $article->is_featured,
            'category' => $article->category ? [
                'slug' => $article->category->slug,
                'name' => $article->category->labelFor($locale),
            ] : null,
            'author' => $article->author ? [
                'name' => $article->author->name,
                'job_title' => $article->author->job_title,
            ] : null,
            'image' => $article->featuredMedia?->toPublicArray($locale),
            'url' => $this->url($locale, 'insights/'.$translation->slug),
        ];
    }

    public function jobSummary(JobPosting $job, string $locale): ?array
    {
        $translation = $this->translationFor($job, $locale);

        if (! $translation) {
            return null;
        }

        return [
            'id' => $job->id,
            'title' => $translation->title,
            'slug' => $translation->slug,
            'summary' => $translation->summary,
            'department' => $job->department,
            'location' => $job->location,
            'employment_type' => $job->employment_type,
            'posted_at' => $job->posted_at?->toIso8601String(),
            'closes_at' => $job->closes_at?->toIso8601String(),
            'url' => $this->url($locale, 'careers/'.$translation->slug),
        ];
    }

    public function documentSummary(Document $document, string $locale): array
    {
        return [
            'id' => $document->id,
            'category' => $document->category,
            'year' => $document->year,
            'title' => $this->localize($document->title, $locale),
            'description' => $this->localize($document->description ?? [], $locale),
            'file_url' => $document->media?->url,
            'file_size' => $document->media?->size,
            'file_type' => $document->media?->extension,
            'is_illustrative' => $document->is_illustrative,
        ];
    }

    public function translationFor($model, string $locale)
    {
        $translations = $model->relationLoaded('translations')
            ? $model->translations
            : $model->translations()->get();

        return $translations->firstWhere('locale', $locale)
            ?? $translations->firstWhere('locale', config('meridian.fallback_locale'));
    }

    public function localize(mixed $values, string $locale): ?string
    {
        if (is_string($values)) {
            return $values;
        }

        if (! is_array($values)) {
            return null;
        }

        return $values[$locale] ?? $values[config('meridian.fallback_locale')] ?? null;
    }

    public function url(string $locale, string $path): string
    {
        $routeLocale = config('meridian.locales')[$locale]['route'] ?? strtolower($locale);

        return '/'.$routeLocale.'/'.ltrim($path, '/');
    }

    protected function initials(string $name): string
    {
        return collect(explode(' ', $name))->filter()->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }
}
