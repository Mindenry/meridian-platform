<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Cms\SiteContext;
use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\JobPostingTranslation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class JobsAdminController extends Controller
{
    public function __construct(protected SiteContext $context) {}

    public function index(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $jobs = JobPosting::forSite($site)->with(['translations'])->withCount('applications')->orderBy('sort')->get();

        return response()->json(['data' => $jobs->map(fn (JobPosting $job) => [
            'id' => $job->id,
            'title' => $job->translations->first()?->title,
            'slug' => $job->translations->first()?->slug,
            'department' => $job->department,
            'location' => $job->location,
            'employment_type' => $job->employment_type,
            'status' => $job->status,
            'posted_at' => $job->posted_at?->toIso8601String(),
            'closes_at' => $job->closes_at?->toIso8601String(),
            'applications_count' => $job->applications_count,
            'locales' => $job->translations->pluck('locale')->values()->all(),
        ])->all()]);
    }

    public function show(Request $request, JobPosting $job): JsonResponse
    {
        abort_unless($job->site_id === $this->context->id(), 404);
        $locale = $this->context->normalizeLocale($request->query('locale'));
        $translation = $job->translations()->where('locale', $locale)->first() ?? $job->translations()->first();

        return response()->json(['data' => [
            'id' => $job->id,
            'locale' => $translation?->locale,
            'title' => $translation?->title,
            'slug' => $translation?->slug,
            'summary' => $translation?->summary,
            'description' => $translation?->description,
            'responsibilities' => $translation?->responsibilities ?? [],
            'requirements' => $translation?->requirements ?? [],
            'benefits' => $translation?->benefits ?? [],
            'department' => $job->department,
            'location' => $job->location,
            'employment_type' => $job->employment_type,
            'status' => $job->status,
            'posted_at' => $job->posted_at?->toIso8601String(),
            'closes_at' => $job->closes_at?->toIso8601String(),
            'translations' => $job->translations->map(fn ($t) => ['locale' => $t->locale, 'title' => $t->title, 'slug' => $t->slug])->values()->all(),
        ]]);
    }

    public function store(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $data = $this->validated($request, true);
        $locale = $this->context->normalizeLocale($data['locale']);
        $slug = $this->slug($data['slug'] ?? $data['title']);
        $this->assertSlug($site->id, $locale, $slug);

        $job = JobPosting::create([
            'site_id' => $site->id,
            'department' => $data['department'],
            'location' => $data['location'],
            'employment_type' => $data['employment_type'] ?? 'full_time',
            'status' => $data['status'] ?? 'open',
            'posted_at' => $data['posted_at'] ?? now(),
            'closes_at' => $data['closes_at'] ?? null,
            'sort' => $data['sort'] ?? 0,
        ]);

        $job->translations()->create([
            'site_id' => $site->id,
            'locale' => $locale,
            'title' => $data['title'],
            'slug' => $slug,
            'summary' => $data['summary'],
            'description' => $data['description'] ?? ['type' => 'doc', 'content' => []],
            'responsibilities' => $data['responsibilities'] ?? [],
            'requirements' => $data['requirements'] ?? [],
            'benefits' => $data['benefits'] ?? [],
        ]);

        return response()->json(['data' => ['id' => $job->id], 'message' => 'Job created.'], 201);
    }

    public function update(Request $request, JobPosting $job): JsonResponse
    {
        abort_unless($job->site_id === $this->context->id(), 404);
        abort_unless($request->user()->hasPermission('jobs.manage', $job->site_id), 403);
        $data = $this->validated($request, false);
        $locale = $this->context->normalizeLocale($data['locale'] ?? $request->query('locale'));
        $translation = $job->translations()->where('locale', $locale)->first();
        $slug = $this->slug($data['slug'] ?? $translation?->slug ?? $data['title']);

        $this->assertSlug($job->site_id, $locale, $slug, $job->id);

        $job->update([
            'department' => $data['department'] ?? $job->department,
            'location' => $data['location'] ?? $job->location,
            'employment_type' => $data['employment_type'] ?? $job->employment_type,
            'status' => $data['status'] ?? $job->status,
            'posted_at' => $data['posted_at'] ?? $job->posted_at,
            'closes_at' => $data['closes_at'] ?? $job->closes_at,
            'sort' => $data['sort'] ?? $job->sort,
        ]);

        $job->translations()->updateOrCreate(
            ['locale' => $locale],
            [
                'site_id' => $job->site_id,
                'title' => $data['title'] ?? $translation?->title,
                'slug' => $slug,
                'summary' => $data['summary'] ?? $translation?->summary,
                'description' => $data['description'] ?? $translation?->description,
                'responsibilities' => $data['responsibilities'] ?? $translation?->responsibilities,
                'requirements' => $data['requirements'] ?? $translation?->requirements,
                'benefits' => $data['benefits'] ?? $translation?->benefits,
            ]
        );

        return response()->json(['message' => 'Job saved.']);
    }

    public function destroy(Request $request, JobPosting $job): JsonResponse
    {
        abort_unless($job->site_id === $this->context->id(), 404);
        abort_unless($request->user()->hasPermission('jobs.manage', $job->site_id), 403);
        $job->delete();

        return response()->json(['message' => 'Job closed and archived.']);
    }

    public function applications(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $perPage = min(100, max(5, (int) $request->query('per_page', 20)));

        $paginator = JobApplication::forSite($site)
            ->with(['jobPosting.translations', 'resume'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('job_id'), fn ($q, $jobId) => $q->where('job_posting_id', $jobId))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'data' => collect($paginator->items())->map(fn (JobApplication $application) => [
                'id' => $application->id,
                'name' => $application->name,
                'email' => $application->email,
                'phone' => $application->phone,
                'role' => $application->jobPosting?->translations->first()?->title,
                'status' => $application->status,
                'resume_url' => $application->resume?->url,
                'created_at' => $application->created_at?->toIso8601String(),
            ])->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
            ],
        ]);
    }

    public function application(Request $request, JobApplication $application): JsonResponse
    {
        abort_unless($application->site_id === $this->context->id(), 404);
        $application->load(['jobPosting.translations', 'resume']);

        return response()->json(['data' => [
            'id' => $application->id,
            'name' => $application->name,
            'email' => $application->email,
            'phone' => $application->phone,
            'cover_letter' => $application->cover_letter,
            'portfolio_url' => $application->portfolio_url,
            'status' => $application->status,
            'role' => $application->jobPosting?->translations->first()?->title,
            'resume_url' => $application->resume?->url,
            'consent_at' => $application->consent_at?->toIso8601String(),
            'created_at' => $application->created_at?->toIso8601String(),
        ]]);
    }

    public function updateApplication(Request $request, JobApplication $application): JsonResponse
    {
        abort_unless($application->site_id === $this->context->id(), 404);
        abort_unless($request->user()->hasPermission('applications.manage', $application->site_id), 403);
        $data = $request->validate(['status' => ['required', 'in:new,screening,interview,offer,hired,rejected,withdrawn']]);
        $application->update($data);

        return response()->json(['message' => 'Application updated.']);
    }

    protected function validated(Request $request, bool $creating): array
    {
        return $request->validate([
            'locale' => [$creating ? 'required' : 'sometimes', 'in:th,en,zh-CN'],
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'summary' => [$creating ? 'required' : 'sometimes', 'string', 'max:1000'],
            'description' => ['nullable', 'array'],
            'responsibilities' => ['nullable', 'array'],
            'requirements' => ['nullable', 'array'],
            'benefits' => ['nullable', 'array'],
            'department' => [$creating ? 'required' : 'sometimes', 'string', 'max:80'],
            'location' => [$creating ? 'required' : 'sometimes', 'string', 'max:120'],
            'employment_type' => ['nullable', 'in:full_time,part_time,contract,internship'],
            'status' => ['nullable', 'in:open,closed,draft'],
            'posted_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);
    }

    protected function slug(string $value): string
    {
        $slug = preg_replace('/[^\p{L}\p{N}\s\-_]+/u', '', trim($value)) ?? '';
        $slug = trim(preg_replace('/-+/u', '-', preg_replace('/[\s_]+/u', '-', $slug) ?? '') ?? '', '-');

        return mb_strtolower($slug !== '' ? $slug : 'role-'.Str::lower(Str::random(6)));
    }

    protected function assertSlug(int $siteId, string $locale, string $slug, ?int $exceptId = null): void
    {
        $query = JobPostingTranslation::where('site_id', $siteId)->where('locale', $locale)->where('slug', $slug);
        if ($exceptId) {
            $query->where('job_posting_id', '!=', $exceptId);
        }
        if ($query->exists()) {
            throw ValidationException::withMessages(['slug' => 'That slug is already used by another role.']);
        }
    }
}
