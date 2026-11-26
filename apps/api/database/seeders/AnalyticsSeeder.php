<?php

namespace Database\Seeders;

use App\Models\AnalyticsEvent;
use App\Models\AuditLog;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        $site = Site::where('slug', 'meridian')->firstOrFail();
        $admin = User::where('email', 'admin@meridian.example')->first();

        if (AnalyticsEvent::forSite($site)->where('is_demo', true)->exists()) {
            return;
        }

        mt_srand(20261126);

        $paths = [
            '/th' => 26, '/en' => 18, '/zh-cn' => 8,
            '/th/solutions' => 14, '/en/solutions' => 9,
            '/th/case-studies' => 8, '/en/case-studies' => 6,
            '/th/insights' => 7, '/en/insights' => 5,
            '/th/about' => 5, '/en/about' => 4,
            '/th/contact' => 6, '/en/contact' => 4,
            '/th/careers' => 4, '/en/careers' => 3,
            '/th/case-studies/rayong-precision-line' => 4,
            '/en/insights/dashboard-nobody-trusts' => 3,
            '/th/solutions/industrial-automation' => 5,
            '/th/industries/manufacturing' => 3,
            '/th/investor-relations' => 2,
            '/zh-cn/about' => 2,
        ];

        $weighted = [];
        foreach ($paths as $path => $weight) {
            for ($i = 0; $i < $weight; $i++) {
                $weighted[] = $path;
            }
        }

        $sources = ['direct', 'google', 'linkedin.com', 'bing.com', 'facebook.com', 'line.me'];
        $devices = ['desktop', 'desktop', 'mobile', 'mobile', 'mobile', 'tablet'];
        $locales = ['th', 'th', 'th', 'en', 'en', 'zh-CN'];

        for ($day = 29; $day >= 0; $day--) {
            $date = now()->subDays($day);
            $weekday = (int) $date->format('N');
            $volume = $weekday >= 6 ? rand(14, 22) : rand(24, 38);

            for ($i = 0; $i < $volume; $i++) {
                $path = $weighted[array_rand($weighted)];
                $locale = str_starts_with($path, '/en') ? 'en' : (str_starts_with($path, '/zh-cn') ? 'zh-CN' : 'th');
                $source = $sources[array_rand($sources)];
                $event = rand(1, 30) === 1 ? 'cta_click' : 'page_view';

                AnalyticsEvent::create([
                    'site_id' => $site->id,
                    'event' => $event,
                    'path' => $path,
                    'locale' => $locale,
                    'session_hash' => hash('sha256', 'demo-session-'.rand(1, 180)),
                    'referrer' => $source === 'direct' ? null : 'https://'.$source.'/',
                    'source' => $source,
                    'device' => $devices[array_rand($devices)],
                    'country' => rand(1, 100) <= 82 ? 'TH' : 'SG',
                    'meta' => $event === 'cta_click' ? ['target' => 'contact'] : null,
                    'is_demo' => true,
                    'occurred_at' => $date->copy()->setTime(rand(7, 21), rand(0, 59), rand(0, 59)),
                    'created_at' => $date,
                ]);
            }
        }

        for ($i = 0; $i < 12; $i++) {
            AnalyticsEvent::create([
                'site_id' => $site->id,
                'event' => 'search',
                'path' => '/th/search',
                'locale' => 'th',
                'session_hash' => hash('sha256', 'demo-search-'.$i),
                'source' => 'internal',
                'device' => 'desktop',
                'country' => 'TH',
                'meta' => ['query' => ['automation', 'OEE', 'ซอฟต์แวร์', 'ระบบอัตโนมัติ', 'case study', 'พลังงาน'][$i % 6]],
                'is_demo' => true,
                'occurred_at' => now()->subDays(rand(0, 20))->setTime(rand(8, 18), rand(0, 59)),
                'created_at' => now(),
            ]);
        }

        $activities = [
            ['page.published', 'Published "Meridian Industrial Group"', 'homepage', 1],
            ['page.created', 'Created page "Our sustainability approach"', 'page', 2],
            ['page.submitted_for_review', 'Submitted "Our sustainability approach" for review', 'page', 2],
            ['page.approved', 'Approved "Industrial outlook 2027"', 'page', 3],
            ['page.scheduled', 'Scheduled "Industrial outlook 2027"', 'page', 4],
            ['media.uploaded', 'Uploaded hero photography for the corporate website', 'media', 1],
            ['settings.updated', 'Updated site settings', 'site', 6],
            ['user.invited', 'Invited a new content editor', 'user', 7],
        ];

        foreach ($activities as $index => [$action, $description, $subject, $days]) {
            AuditLog::create([
                'site_id' => $site->id,
                'user_id' => $admin?->id,
                'action' => $action,
                'description' => $description,
                'subject_type' => $subject,
                'subject_id' => $index + 1,
                'meta' => ['seeded' => true],
                'ip_address' => '203.0.113.5',
                'user_agent' => 'Meridian demo seed',
                'created_at' => now()->subDays(29 - $days)->subHours($index),
            ]);
        }
    }
}
