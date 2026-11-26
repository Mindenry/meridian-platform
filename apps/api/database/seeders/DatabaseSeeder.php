<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SiteSeeder::class,
            AccessSeeder::class,
            MediaSeeder::class,
            SolutionIndustrySeeder::class,
            CaseStudySeeder::class,
            InsightSeeder::class,
            CompanySeeder::class,
            CareersSeeder::class,
            PageSeeder::class,
            EnergySiteSeeder::class,
            NavigationSeeder::class,
            FormSeeder::class,
            OperationsSeeder::class,
            AnalyticsSeeder::class,
            SeoSeeder::class,
        ]);
    }
}
