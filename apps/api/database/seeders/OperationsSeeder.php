<?php

namespace Database\Seeders;

use App\Cms\PageService;
use App\Cms\SiteContext;
use App\Cms\WorkflowService;
use App\Models\Page;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class OperationsSeeder extends Seeder
{
    public function run(): void
    {
        $site = Site::where('slug', 'meridian')->firstOrFail();
        app(SiteContext::class)->setSite($site);

        $editor = User::where('email', 'editor@meridian.example')->firstOrFail();
        $reviewer = User::where('email', 'reviewer@meridian.example')->firstOrFail();
        $manager = User::where('email', 'manager@meridian.example')->firstOrFail();

        $pages = app(PageService::class);
        $workflow = app(WorkflowService::class);

        // A page awaiting review gives the dashboard a real pending approval.
        $draft = Page::where('site_id', $site->id)
            ->whereHas('translations', fn ($q) => $q->where('slug', 'sustainability-approach'))
            ->first();

        if (! $draft) {
            $draft = $pages->create($site, $editor, [
                'locale' => 'en',
                'title' => 'Our sustainability approach',
                'slug' => 'sustainability-approach',
                'template' => 'editorial',
                'excerpt' => 'How we think about energy, material use, and the long life of industrial assets.',
                'blocks' => [
                    ['type' => 'split_hero', 'props' => [
                        'eyebrow' => 'Sustainability',
                        'headline' => 'Design for the life of the asset, not the life of the project.',
                        'body' => 'This draft demonstrates the editorial review workflow. It is sample content for a fictional brand.',
                        'primary_cta' => ['label' => 'Talk to our team', 'url' => '/contact', 'style' => 'primary'],
                        'image' => null,
                        'reverse' => false,
                        'theme' => 'dark',
                    ]],
                ],
                'seo' => [
                    'title' => 'Our sustainability approach — Meridian (draft)',
                    'description' => 'A sample draft page used to demonstrate the review workflow.',
                ],
            ]);

            $workflow->submit($draft, $editor, 'Please review the structure and claims before we add the full case evidence.');
        }

        // An approved page scheduled for future publication demonstrates the scheduler.
        $scheduled = Page::where('site_id', $site->id)
            ->whereHas('translations', fn ($q) => $q->where('slug', 'industrial-outlook-2027'))
            ->first();

        if (! $scheduled) {
            $scheduled = $pages->create($site, $editor, [
                'locale' => 'en',
                'title' => 'Industrial outlook 2027',
                'slug' => 'industrial-outlook-2027',
                'template' => 'editorial',
                'excerpt' => 'A sample scheduled article page demonstrating publication planning.',
                'blocks' => [
                    ['type' => 'rich_text', 'props' => [
                        'eyebrow' => 'Outlook',
                        'heading' => 'Industrial outlook 2027',
                        'body' => '<p>This page is scheduled for future publication to demonstrate the CMS scheduler. It contains illustrative demonstration content only.</p>',
                        'width' => 'narrow',
                        'theme' => 'light',
                    ]],
                ],
            ]);

            $workflow->submit($scheduled, $editor, 'Scheduled publication demonstration.');
            $workflow->approve($scheduled, $reviewer, 'Approved for scheduled publication.');
            $workflow->schedule($scheduled, $manager, Carbon::now()->addDays(2)->setTime(9, 0));
        }
    }
}
