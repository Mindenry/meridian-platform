<?php

namespace App\Cms;

use App\Models\AnalyticsEvent;
use App\Models\ArticleTranslation;
use App\Models\AuditLog;
use App\Models\FormSubmission;
use App\Models\Lead;
use App\Models\Site;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AnalyticsService
{
    public function record(Site $site, array $payload, ?string $ip, ?string $userAgent): AnalyticsEvent
    {
        $path = mb_substr((string) ($payload['path'] ?? '/'), 0, 1000);
        $referrer = isset($payload['referrer']) ? mb_substr((string) $payload['referrer'], 0, 1000) : null;
        $source = $this->sourceFrom($referrer, $payload['utm_source'] ?? null);
        $session = $payload['session_id'] ?? null;
        $sessionHash = $session ? hash('sha256', $site->id.'|'.$session) : hash('sha256', $site->id.'|'.$ip.'|'.($userAgent ?? '').'|'.now()->toDateString());

        $event = AnalyticsEvent::create([
            'site_id' => $site->id,
            'event' => in_array($payload['event'] ?? 'page_view', ['page_view', 'search', 'form_submit', 'cta_click', 'article_read'], true)
                ? $payload['event'] : 'page_view',
            'path' => $path,
            'locale' => $payload['locale'] ?? $site->default_locale,
            'session_hash' => $sessionHash,
            'referrer' => $referrer,
            'source' => $source,
            'device' => $this->deviceFrom($userAgent),
            'country' => $payload['country'] ?? null,
            'meta' => isset($payload['meta']) && is_array($payload['meta']) ? $payload['meta'] : null,
            'is_demo' => false,
            'occurred_at' => now(),
            'created_at' => now(),
        ]);

        return $event;
    }

    public function dashboard(Site $site, CarbonInterface $from, CarbonInterface $to): array
    {
        $from = Carbon::parse($from)->startOfDay();
        $to = Carbon::parse($to)->endOfDay();
        $days = max(1, (int) $from->diffInDays($to->copy()->startOfDay()) + 1);
        $previousFrom = $from->copy()->subDays($days);
        $previousTo = $from->copy()->subSecond();

        $events = AnalyticsEvent::forSite($site)
            ->whereBetween('occurred_at', [$from, $to])
            ->orderBy('occurred_at')
            ->limit(100000)
            ->get();

        $previous = AnalyticsEvent::forSite($site)
            ->whereBetween('occurred_at', [$previousFrom, $previousTo])
            ->get();

        $pageViews = $events->where('event', 'page_view');
        $prevPageViews = $previous->where('event', 'page_view');
        $submissions = FormSubmission::forSite($site)->whereBetween('created_at', [$from, $to])->get();
        $prevSubmissions = FormSubmission::forSite($site)->whereBetween('created_at', [$previousFrom, $previousTo])->count();
        $leads = Lead::forSite($site)->whereBetween('created_at', [$from, $to])->get();
        $prevLeads = Lead::forSite($site)->whereBetween('created_at', [$previousFrom, $previousTo])->count();

        $series = [];
        $cursor = $from->copy();

        while ($cursor <= $to) {
            $key = $cursor->toDateString();
            $dayEvents = $pageViews->filter(fn (AnalyticsEvent $event) => $event->occurred_at->toDateString() === $key);
            $series[] = [
                'date' => $key,
                'views' => $dayEvents->count(),
                'visitors' => $dayEvents->pluck('session_hash')->unique()->count(),
            ];
            $cursor->addDay();
        }

        $topPages = $pageViews->groupBy('path')->map(fn (Collection $group, $path) => [
            'path' => $path,
            'views' => $group->count(),
            'visitors' => $group->pluck('session_hash')->unique()->count(),
        ])->sortByDesc('views')->take(10)->values()->all();

        $sources = $pageViews->groupBy(fn (AnalyticsEvent $event) => $event->source ?: 'direct')->map(fn (Collection $group, $source) => [
            'source' => $source,
            'views' => $group->count(),
        ])->sortByDesc('views')->take(8)->values()->all();

        $locales = $pageViews->groupBy('locale')->map(fn (Collection $group, $locale) => [
            'locale' => $locale ?: 'unknown',
            'views' => $group->count(),
        ])->sortByDesc('views')->values()->all();

        $devices = $pageViews->groupBy(fn (AnalyticsEvent $event) => $event->device ?: 'unknown')->map(fn (Collection $group, $device) => [
            'device' => $device,
            'views' => $group->count(),
        ])->sortByDesc('views')->values()->all();

        $searches = $events->where('event', 'search')->map(fn (AnalyticsEvent $event) => $event->meta['query'] ?? null)
            ->filter()->countBy()->map(fn ($count, $query) => ['query' => $query, 'count' => $count])
            ->sortByDesc('count')->take(10)->values()->all();

        $articlePaths = $pageViews->filter(fn (AnalyticsEvent $event) => str_contains($event->path, '/insights/'));
        $topArticles = $articlePaths->groupBy('path')->map(function (Collection $group, $path) use ($site) {
            $slug = basename(parse_url($path, PHP_URL_PATH) ?? '');
            $translation = ArticleTranslation::where('site_id', $site->id)->where('slug', $slug)->first();

            return [
                'path' => $path,
                'title' => $translation?->title ?? $slug,
                'views' => $group->count(),
            ];
        })->sortByDesc('views')->take(5)->values()->all();

        $totalViews = $pageViews->count();
        $prevTotal = $prevPageViews->count();
        $demoCount = $events->where('is_demo', true)->count();

        return [
            'range' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'days' => $days,
            ],
            'has_data' => $events->isNotEmpty(),
            'demo_only' => $events->isNotEmpty() && $demoCount === $events->count(),
            'demo_events' => $demoCount,
            'metrics' => [
                'page_views' => $totalViews,
                'page_views_delta' => $this->delta($totalViews, $prevTotal),
                'unique_visitors' => $pageViews->pluck('session_hash')->filter()->unique()->count(),
                'form_submissions' => $submissions->count(),
                'form_submissions_delta' => $this->delta($submissions->count(), $prevSubmissions),
                'leads' => $leads->count(),
                'leads_delta' => $this->delta($leads->count(), $prevLeads),
                'conversion_rate' => $totalViews > 0 ? round($leads->count() / $totalViews * 100, 2) : 0,
                'demo_submissions' => $submissions->where('is_demo', true)->count(),
            ],
            'series' => $series,
            'top_pages' => $topPages,
            'top_articles' => $topArticles,
            'sources' => $sources,
            'locales' => $locales,
            'devices' => $devices,
            'searches' => $searches,
            'engine' => 'first_party',
            'note' => 'First-party events collected by this installation. Demo events are labelled.',
        ];
    }

    public function contentActivity(Site $site, CarbonInterface $from, CarbonInterface $to): array
    {
        return AuditLog::where('site_id', $site->id)
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('action', ['page.published', 'page.created', 'page.updated', 'page.submitted_for_review', 'page.approved'])
            ->latest('created_at')
            ->limit(12)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'user' => $log->user?->name,
                'created_at' => $log->created_at?->toIso8601String(),
            ])
            ->all();
    }

    protected function delta(int $current, int $previous): ?float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    protected function sourceFrom(?string $referrer, ?string $utm): string
    {
        if ($utm) {
            return mb_substr(strtolower($utm), 0, 120);
        }

        if (! $referrer) {
            return 'direct';
        }

        $host = strtolower((string) parse_url($referrer, PHP_URL_HOST));

        if ($host === '') {
            return 'direct';
        }

        return Str::startsWith($host, 'www.') ? substr($host, 4) : $host;
    }

    protected function deviceFrom(?string $userAgent): string
    {
        $ua = strtolower((string) $userAgent);

        if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet')) {
            return 'tablet';
        }

        if (str_contains($ua, 'mobile') || str_contains($ua, 'iphone') || str_contains($ua, 'android')) {
            return 'mobile';
        }

        return 'desktop';
    }
}
