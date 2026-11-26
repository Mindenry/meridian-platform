<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Cms\SiteContext;
use App\Http\Controllers\Controller;
use App\Models\CaseStudy;
use App\Models\CaseStudyTranslation;
use App\Models\ContentRevision;
use App\Models\SeoMetadata;
use App\Models\Solution;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CaseStudyAdminController extends Controller
{
    public function __construct(protected SiteContext $context, protected AuditLogger $audit) {}

    public function index(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $perPage = min(100, max(5, (int) $request->query('per_page', 20)));

        $paginator = CaseStudy::forSite($site)
            ->with(['translations', 'industry.translations', 'featuredMedia'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('q'), fn ($q, $search) => $q->whereHas('translations', fn ($t) => $t->where('title', 'like', '%'.$search.'%')))
            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'data' => collect($paginator->items())->map(fn (CaseStudy $study) => $this->summary($study, $site->default_locale))->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
            ],
        ]);
    }

    public function show(Request $request, CaseStudy $caseStudy): JsonResponse
    {
        $this->ensureSite($caseStudy);
        $locale = $this->context->normalizeLocale($request->query('locale'));

        return response()->json(['data' => $this->payload(
            $caseStudy->load(['translations', 'industry.translations', 'solutions.translations', 'featuredMedia', 'seo.ogImage']),
            $locale,
            $request->user()
        )]);
    }

    public function store(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $data = $this->validated($request, true);
        $locale = $this->context->normalizeLocale($data['locale']);
        $slug = $this->slugify($data['slug'] ?? $data['title']);
        $this->assertSlug($site->id, $locale, $slug);

        $study = DB::transaction(function () use ($site, $request, $data, $locale, $slug) {
            $study = CaseStudy::create([
                'site_id' => $site->id,
                'industry_id' => $data['industry_id'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'year' => $data['year'],
                'client_label' => $data['client_label'] ?? null,
                'featured_media_id' => $data['featured_media_id'] ?? null,
                'published_at' => ($data['status'] ?? 'draft') === 'published' ? now() : null,
                'is_featured' => (bool) ($data['is_featured'] ?? false),
                'sort' => $data['sort'] ?? 0,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            $study->translations()->create([
                'site_id' => $site->id,
                'locale' => $locale,
                'title' => $data['title'],
                'slug' => $slug,
                'summary' => $data['summary'],
                'challenge' => $data['challenge'],
                'strategy' => $data['strategy'],
                'implementation' => $data['implementation'],
                'results' => $data['results'] ?? [],
                'gallery' => $data['gallery'] ?? [],
                'illustrative_note' => $data['illustrative_note'] ?? 'Illustrative portfolio content. Outcomes are demonstration data, not client-verified results.',
            ]);

            if (! empty($data['solution_ids'])) {
                $study->solutions()->sync($data['solution_ids']);
            }

            $this->saveSeo($study, $locale, $data['seo'] ?? []);
            $this->revision($study, $locale, $request->user()->id, 'Initial draft');
            $this->audit->log('case_study.created', "Created case study \"{$data['title']}\"", ['subject_type' => 'case_study', 'subject_id' => $study->id]);

            return $study;
        });

        return response()->json(['data' => $this->payload($study->fresh(['translations', 'industry.translations', 'solutions.translations', 'featuredMedia']), $locale, $request->user()), 'message' => 'Case study created.'], 201);
    }

    public function update(Request $request, CaseStudy $caseStudy): JsonResponse
    {
        $this->ensureSite($caseStudy);
        $data = $this->validated($request, false);
        $locale = $this->context->normalizeLocale($data['locale'] ?? $request->query('locale'));
        $translation = $caseStudy->translations()->where('locale', $locale)->first();

        if (! $translation) {
            throw ValidationException::withMessages(['locale' => 'Add this translation before editing it.']);
        }

        $slug = $this->slugify($data['slug'] ?? $translation->slug);
        $this->assertSlug($caseStudy->site_id, $locale, $slug, $caseStudy->id);

        DB::transaction(function () use ($caseStudy, $request, $data, $locale, $slug, $translation) {
            $caseStudy->update([
                'industry_id' => $data['industry_id'] ?? $caseStudy->industry_id,
                'status' => $data['status'] ?? $caseStudy->status,
                'year' => $data['year'] ?? $caseStudy->year,
                'client_label' => $data['client_label'] ?? $caseStudy->client_label,
                'featured_media_id' => $data['featured_media_id'] ?? $caseStudy->featured_media_id,
                'is_featured' => $data['is_featured'] ?? $caseStudy->is_featured,
                'published_at' => ($data['status'] ?? $caseStudy->status) === 'published'
                    ? ($caseStudy->published_at ?? now())
                    : $caseStudy->published_at,
                'updated_by' => $request->user()->id,
            ]);

            $caseStudy->translations()->where('locale', $locale)->update([
                'title' => $data['title'] ?? $translation->title,
                'slug' => $slug,
                'summary' => $data['summary'] ?? $translation->summary,
                'challenge' => $data['challenge'] ?? $translation->challenge,
                'strategy' => $data['strategy'] ?? $translation->strategy,
                'implementation' => $data['implementation'] ?? $translation->implementation,
                'results' => $data['results'] ?? $translation->results,
                'gallery' => $data['gallery'] ?? $translation->gallery,
            ]);

            if (array_key_exists('solution_ids', $data)) {
                $caseStudy->solutions()->sync($data['solution_ids'] ?? []);
            }

            if (array_key_exists('seo', $data)) {
                $this->saveSeo($caseStudy, $locale, $data['seo']);
            }

            $this->revision($caseStudy, $locale, $request->user()->id, 'Content updated');
            $this->audit->log('case_study.updated', 'Updated case study', ['subject_type' => 'case_study', 'subject_id' => $caseStudy->id]);
        });

        return response()->json(['data' => $this->payload($caseStudy->fresh(['translations', 'industry.translations', 'solutions.translations', 'featuredMedia', 'seo.ogImage']), $locale, $request->user()), 'message' => 'Case study saved.']);
    }

    public function destroy(Request $request, CaseStudy $caseStudy): JsonResponse
    {
        $this->ensureSite($caseStudy);
        abort_unless($request->user()->hasPermission('case_studies.manage', $caseStudy->site_id), 403);
        $caseStudy->delete();

        return response()->json(['message' => 'Case study deleted.']);
    }

    protected function validated(Request $request, bool $creating): array
    {
        return $request->validate([
            'locale' => [$creating ? 'required' : 'sometimes', 'in:th,en,zh-CN'],
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'summary' => [$creating ? 'required' : 'sometimes', 'string', 'max:2000'],
            'challenge' => [$creating ? 'required' : 'sometimes', 'string', 'max:10000'],
            'strategy' => [$creating ? 'required' : 'sometimes', 'string', 'max:10000'],
            'implementation' => [$creating ? 'required' : 'sometimes', 'string', 'max:10000'],
            'results' => ['nullable', 'array', 'max:8'],
            'results.*.label' => ['required_with:results', 'string', 'max:120'],
            'results.*.value' => ['required_with:results', 'string', 'max:80'],
            'results.*.note' => ['nullable', 'string', 'max:120'],
            'gallery' => ['nullable', 'array', 'max:12'],
            'gallery.*.url' => ['required_with:gallery', 'string', 'max:1000'],
            'gallery.*.alt' => ['nullable', 'string', 'max:240'],
            'gallery.*.caption' => ['nullable', 'string', 'max:300'],
            'year' => [$creating ? 'required' : 'sometimes', 'integer', 'min:1990', 'max:2100'],
            'client_label' => ['nullable', 'string', 'max:160'],
            'industry_id' => ['nullable', 'integer'],
            'solution_ids' => ['nullable', 'array'],
            'solution_ids.*' => ['integer'],
            'featured_media_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:draft,published,archived'],
            'is_featured' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:999'],
            'illustrative_note' => ['nullable', 'string', 'max:500'],
            'seo' => ['nullable', 'array'],
        ]);
    }

    protected function payload(CaseStudy $study, string $locale, $user): array
    {
        $translation = $study->translations->firstWhere('locale', $locale) ?? $study->translations->first();
        $seo = $study->seo->firstWhere('locale', $translation?->locale);

        return [
            'id' => $study->id,
            'locale' => $translation?->locale,
            'title' => $translation?->title,
            'slug' => $translation?->slug,
            'summary' => $translation?->summary,
            'challenge' => $translation?->challenge,
            'strategy' => $translation?->strategy,
            'implementation' => $translation?->implementation,
            'results' => $translation?->results ?? [],
            'gallery' => $translation?->gallery ?? [],
            'illustrative_note' => $translation?->illustrative_note,
            'year' => $study->year,
            'client_label' => $study->client_label,
            'status' => $study->status,
            'is_featured' => $study->is_featured,
            'published_at' => $study->published_at?->toIso8601String(),
            'updated_at' => $study->updated_at?->toIso8601String(),
            'industry' => $study->industry ? ['id' => $study->industry->id, 'key' => $study->industry->key, 'title' => $study->industry->translations->first()?->title] : null,
            'solutions' => $study->solutions->map(fn (Solution $solution) => ['id' => $solution->id, 'key' => $solution->key, 'title' => $solution->translations->first()?->title])->values()->all(),
            'featured_media' => $study->featuredMedia?->toPublicArray($locale),
            'translations' => $study->translations->map(fn ($item) => ['locale' => $item->locale, 'title' => $item->title, 'slug' => $item->slug])->values()->all(),
            'seo' => $seo ? [
                'title' => $seo->title, 'description' => $seo->description, 'canonical' => $seo->canonical,
                'focus_keyword' => $seo->focus_keyword, 'og_title' => $seo->og_title, 'og_description' => $seo->og_description,
                'og_image' => $seo->ogImage?->toPublicArray($locale), 'robots' => $seo->robots,
            ] : null,
            'capabilities' => [
                'edit' => $user->hasPermission('case_studies.manage', $study->site_id),
                'publish' => $user->hasPermission('case_studies.publish', $study->site_id),
                'delete' => $user->hasPermission('case_studies.manage', $study->site_id),
            ],
        ];
    }

    protected function summary(CaseStudy $study, string $locale): array
    {
        $translation = $study->translations->firstWhere('locale', $locale) ?? $study->translations->first();

        return [
            'id' => $study->id,
            'title' => $translation?->title,
            'slug' => $translation?->slug,
            'status' => $study->status,
            'year' => $study->year,
            'industry' => $study->industry?->translations->first()?->title,
            'image' => $study->featuredMedia?->url,
            'featured' => $study->is_featured,
            'updated_at' => $study->updated_at?->toIso8601String(),
            'locales' => $study->translations->pluck('locale')->values()->all(),
        ];
    }

    protected function saveSeo(CaseStudy $study, string $locale, array $seo): void
    {
        SeoMetadata::updateOrCreate(
            ['seoable_type' => 'case_study', 'seoable_id' => $study->id, 'locale' => $locale],
            [
                'site_id' => $study->site_id,
                'title' => $seo['title'] ?? null,
                'description' => $seo['description'] ?? null,
                'canonical' => $seo['canonical'] ?? null,
                'focus_keyword' => $seo['focus_keyword'] ?? null,
                'og_title' => $seo['og_title'] ?? null,
                'og_description' => $seo['og_description'] ?? null,
                'og_image_id' => $seo['og_image_id'] ?? null,
                'robots' => $seo['robots'] ?? 'index,follow',
                'schema_type' => $seo['schema_type'] ?? 'CreativeWork',
            ]
        );
    }

    protected function revision(CaseStudy $study, string $locale, int $userId, string $message): void
    {
        $translation = $study->translations()->where('locale', $locale)->first();
        $version = (int) ContentRevision::where('revisionable_type', 'case_study')->where('revisionable_id', $study->id)->where('locale', $locale)->max('version') + 1;

        ContentRevision::create([
            'site_id' => $study->site_id,
            'revisionable_type' => 'case_study',
            'revisionable_id' => $study->id,
            'locale' => $locale,
            'user_id' => $userId,
            'version' => $version,
            'snapshot' => $translation?->only(['title', 'slug', 'summary', 'challenge', 'strategy', 'implementation', 'results', 'gallery']),
            'message' => $message,
            'created_at' => now(),
        ]);
    }

    protected function assertSlug(int $siteId, string $locale, string $slug, ?int $exceptId = null): void
    {
        $query = CaseStudyTranslation::where('site_id', $siteId)->where('locale', $locale)->where('slug', $slug);

        if ($exceptId) {
            $query->where('case_study_id', '!=', $exceptId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages(['slug' => 'That slug is already used by another case study.']);
        }
    }

    protected function slugify(string $value): string
    {
        $slug = preg_replace('/[^\p{L}\p{N}\s\-_]+/u', '', trim($value)) ?? '';
        $slug = trim(preg_replace('/-+/u', '-', preg_replace('/[\s_]+/u', '-', $slug) ?? '') ?? '', '-');

        return mb_strtolower($slug !== '' ? $slug : 'case-study-'.Str::lower(Str::random(6)));
    }

    protected function ensureSite(CaseStudy $study): void
    {
        abort_unless($study->site_id === $this->context->id(), 404);
    }
}
