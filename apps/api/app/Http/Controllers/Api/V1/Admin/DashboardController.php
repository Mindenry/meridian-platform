<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Cms\AnalyticsService;
use App\Cms\SiteContext;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\AuditLog;
use App\Models\CaseStudy;
use App\Models\FormSubmission;
use App\Models\Lead;
use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\ScheduledPublication;
use App\Models\SeoAudit;
use App\Models\SeoIssue;
use App\Models\WorkflowInstance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        protected SiteContext $context,
        protected AnalyticsService $analytics,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $from = $request->query('from') ? Carbon::parse($request->query('from'))->startOfDay() : now()->subDays(29)->startOfDay();
        $to = $request->query('to') ? Carbon::parse($request->query('to'))->endOfDay() : now()->endOfDay();

        $statusCounts = Page::forSite($site)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $analytics = $this->analytics->dashboard($site, $from, $to);
        $latestAudit = SeoAudit::forSite($site)->latest('checked_at')->first();

        $topPages = collect($analytics['top_pages'])->map(function (array $item) use ($site) {
            $path = parse_url($item['path'], PHP_URL_PATH) ?? $item['path'];
            $segments = array_values(array_filter(explode('/', $path)));
            $slug = end($segments) ?: '';
            $translation = PageTranslation::where('site_id', $site->id)->where('slug', $slug)->first();
            $sectionLabels = ['solutions' => 'Solutions index', 'industries' => 'Industries index', 'case-studies' => 'Case studies index', 'insights' => 'Insights index', 'careers' => 'Careers', 'contact' => 'Contact', 'about' => 'About', 'investor-relations' => 'Investor relations', 'search' => 'Search'];
            $item['title'] = count($segments) === 1
                ? 'Homepage ('.strtoupper($segments[0] ?? '').')'
                : ($translation?->title ?? $sectionLabels[$slug] ?? $slug ?: '/');
            $item['url'] = $item['path'];

            return $item;
        })->all();

        $storage = MediaAsset::forSite($site)->sum('size');
        $mediaCount = MediaAsset::forSite($site)->count();
        $openCritical = SeoIssue::forSite($site)->where('status', 'open')->where('severity', 'critical')->count();
        $openWarnings = SeoIssue::forSite($site)->where('status', 'open')->where('severity', 'warning')->count();

        $activities = AuditLog::where('site_id', $site->id)
            ->with('user')
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'user' => $log->user?->name ?? 'System',
                'created_at' => $log->created_at?->toIso8601String(),
                'subject_type' => $log->subject_type,
                'subject_id' => $log->subject_id,
            ]);

        $recentSubmissions = FormSubmission::forSite($site)
            ->with('form')
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (FormSubmission $submission) => [
                'id' => $submission->id,
                'form' => $submission->form?->name,
                'name' => $submission->data['name'] ?? '—',
                'email' => $submission->data['email'] ?? '—',
                'status' => $submission->status,
                'created_at' => $submission->created_at?->toIso8601String(),
                'is_demo' => $submission->is_demo,
            ]);

        return response()->json([
            'data' => [
                'range' => $analytics['range'],
                'content' => [
                    'published_pages' => (int) ($statusCounts[Page::STATUS_PUBLISHED] ?? 0),
                    'draft_pages' => (int) ($statusCounts[Page::STATUS_DRAFT] ?? 0),
                    'in_review_pages' => (int) ($statusCounts[Page::STATUS_IN_REVIEW] ?? 0),
                    'scheduled_pages' => (int) ($statusCounts[Page::STATUS_SCHEDULED] ?? 0),
                    'archived_pages' => (int) ($statusCounts[Page::STATUS_ARCHIVED] ?? 0),
                    'articles' => Article::forSite($site)->where('status', 'published')->count(),
                    'drafts' => Article::forSite($site)->where('status', 'draft')->count(),
                    'case_studies' => CaseStudy::forSite($site)->where('status', 'published')->count(),
                    'pending_approvals' => WorkflowInstance::forSite($site)->where('status', 'in_review')->count(),
                    'scheduled_publications' => ScheduledPublication::forSite($site)->where('status', 'pending')->count(),
                ],
                'leads' => [
                    'open' => Lead::forSite($site)->whereIn('status', ['new', 'contacted', 'qualified'])->count(),
                    'new_this_period' => Lead::forSite($site)->whereBetween('created_at', [$from, $to])->count(),
                    'demo' => Lead::forSite($site)->where('is_demo', true)->count(),
                ],
                'media' => [
                    'assets' => $mediaCount,
                    'storage_bytes' => (int) $storage,
                    'storage_human' => $this->humanBytes((int) $storage),
                ],
                'seo' => [
                    'score' => $latestAudit?->score,
                    'checked_at' => $latestAudit?->checked_at?->toIso8601String(),
                    'critical' => $openCritical,
                    'warnings' => $openWarnings,
                    'status' => $latestAudit ? ($openCritical > 0 ? 'attention' : 'healthy') : 'not_run',
                ],
                'analytics' => array_merge($analytics, ['top_pages' => $topPages]),
                'recent_activity' => $activities,
                'recent_submissions' => $recentSubmissions,
                'demo_mode' => (bool) config('meridian.demo_mode'),
            ],
        ]);
    }

    protected function humanBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1).' '.$units[$i];
    }
}
