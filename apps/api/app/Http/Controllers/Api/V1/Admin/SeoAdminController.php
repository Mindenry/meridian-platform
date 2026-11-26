<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Cms\ContentPresenter;
use App\Cms\SeoAuditService;
use App\Cms\SiteContext;
use App\Http\Controllers\Controller;
use App\Models\Redirect;
use App\Models\SeoAudit;
use App\Models\SeoIssue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SeoAdminController extends Controller
{
    public function __construct(
        protected SiteContext $context,
        protected SeoAuditService $audits,
        protected ContentPresenter $presenter,
    ) {}

    public function health(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $latest = SeoAudit::forSite($site)->latest('checked_at')->first();

        $counts = SeoIssue::forSite($site)
            ->where('status', 'open')
            ->select('severity', DB::raw('count(*) as total'))
            ->groupBy('severity')
            ->pluck('total', 'severity');

        return response()->json([
            'data' => [
                'status' => $latest ? 'audited' : 'not_run',
                'score' => $latest?->score,
                'checked_at' => $latest?->checked_at?->toIso8601String(),
                'summary' => $latest?->summary,
                'open' => [
                    'critical' => (int) ($counts['critical'] ?? 0),
                    'warning' => (int) ($counts['warning'] ?? 0),
                    'info' => (int) ($counts['info'] ?? 0),
                ],
                'method' => 'Deterministic checks. A score is an editorial health signal, never a guarantee of ranking.',
            ],
        ]);
    }

    public function audit(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        abort_unless($request->user()->hasPermission('seo.manage', $site->id), 403);
        $audit = $this->audits->run($site);

        return response()->json([
            'data' => [
                'id' => $audit->id,
                'score' => $audit->score,
                'summary' => $audit->summary,
                'checked_at' => $audit->checked_at?->toIso8601String(),
                'issues' => $audit->issues->count(),
            ],
            'message' => 'SEO audit completed.',
        ]);
    }

    public function issues(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $perPage = min(100, max(10, (int) $request->query('per_page', 25)));

        $query = SeoIssue::forSite($site)
            ->when($request->query('status', 'open'), fn ($q, $status) => $status !== 'all' ? $q->where('status', $status) : $q)
            ->when($request->query('severity'), fn ($q, $severity) => $q->where('severity', $severity))
            ->when($request->query('code'), fn ($q, $code) => $q->where('code', $code))
            ->orderByRaw("case severity when 'critical' then 1 when 'warning' then 2 else 3 end")
            ->latest('last_checked_at');

        $paginator = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'data' => collect($paginator->items())->map(fn (SeoIssue $issue) => [
                'id' => $issue->id,
                'code' => $issue->code,
                'severity' => $issue->severity,
                'url' => $issue->url,
                'title' => $issue->title,
                'explanation' => $issue->explanation,
                'recommendation' => $issue->recommendation,
                'status' => $issue->status,
                'locale' => $issue->locale,
                'last_checked_at' => $issue->last_checked_at?->toIso8601String(),
            ])->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
            ],
        ]);
    }

    public function updateIssue(Request $request, SeoIssue $issue): JsonResponse
    {
        abort_unless($issue->site_id === $this->context->id(), 404);
        abort_unless($request->user()->hasPermission('seo.manage', $issue->site_id), 403);
        $data = $request->validate(['status' => ['required', 'in:open,resolved,ignored']]);
        $issue->update([
            'status' => $data['status'],
            'resolved_at' => $data['status'] === 'open' ? null : now(),
        ]);

        return response()->json(['message' => 'Issue updated.']);
    }

    public function redirects(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();

        return response()->json(['data' => Redirect::forSite($site)->orderByDesc('created_at')->get()->map(fn (Redirect $redirect) => [
            'id' => $redirect->id,
            'from_path' => $redirect->from_path,
            'to_path' => $redirect->to_path,
            'status_code' => $redirect->status_code,
            'locale' => $redirect->locale,
            'is_active' => $redirect->is_active,
            'hits' => $redirect->hits,
            'created_at' => $redirect->created_at?->toIso8601String(),
        ])->all()]);
    }

    public function storeRedirect(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        abort_unless($request->user()->hasPermission('redirects.manage', $site->id), 403);
        $data = $request->validate([
            'from_path' => ['required', 'string', 'max:1000', 'starts_with:/'],
            'to_path' => ['required', 'string', 'max:1000'],
            'status_code' => ['nullable', 'in:301,302,307,308'],
            'locale' => ['nullable', 'in:th,en,zh-CN'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $redirect = Redirect::create([
            'site_id' => $site->id,
            'from_path' => $data['from_path'],
            'to_path' => $data['to_path'],
            'status_code' => $data['status_code'] ?? 301,
            'locale' => $data['locale'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json(['data' => ['id' => $redirect->id], 'message' => 'Redirect created.'], 201);
    }

    public function updateRedirect(Request $request, Redirect $redirect): JsonResponse
    {
        abort_unless($redirect->site_id === $this->context->id(), 404);
        abort_unless($request->user()->hasPermission('redirects.manage', $redirect->site_id), 403);
        $data = $request->validate([
            'from_path' => ['sometimes', 'string', 'max:1000', 'starts_with:/'],
            'to_path' => ['sometimes', 'string', 'max:1000'],
            'status_code' => ['nullable', 'in:301,302,307,308'],
            'locale' => ['nullable', 'in:th,en,zh-CN'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $redirect->update($data);

        return response()->json(['message' => 'Redirect updated.']);
    }

    public function destroyRedirect(Request $request, Redirect $redirect): JsonResponse
    {
        abort_unless($redirect->site_id === $this->context->id(), 404);
        abort_unless($request->user()->hasPermission('redirects.manage', $redirect->site_id), 403);
        $redirect->delete();

        return response()->json(['message' => 'Redirect deleted.']);
    }

    public function sitemap(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $locale = $this->context->locale();
        $base = rtrim((string) config('meridian.website_url'), '/');

        return response()->json(['data' => collect($this->presenter->sitemap($site, $locale))
            ->map(fn ($entry) => array_merge($entry, ['loc' => $base.$entry['loc']]))->all()]);
    }
}
