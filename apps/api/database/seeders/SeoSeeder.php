<?php

namespace Database\Seeders;

use App\Cms\SeoAuditService;
use App\Cms\SiteContext;
use App\Models\Site;
use Illuminate\Database\Seeder;

class SeoSeeder extends Seeder
{
    public function run(): void
    {
        $site = Site::where('slug', 'meridian')->firstOrFail();
        app(SiteContext::class)->setSite($site);
        app(SeoAuditService::class)->run($site);
    }
}
