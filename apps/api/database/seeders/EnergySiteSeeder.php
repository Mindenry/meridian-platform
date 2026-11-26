<?php

namespace Database\Seeders;

use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\SeoMetadata;
use App\Models\Setting;
use App\Models\Site;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * A small but real second website. It exists to prove that navigation, pages,
 * search indexing, and access control are scoped per site rather than global.
 */
class EnergySiteSeeder extends Seeder
{
    protected Site $site;

    public function run(): void
    {
        $this->site = Site::where('slug', 'meridian-energy')->firstOrFail();

        $home = $this->page('home', 'home', true, 'Meridian Energy Systems', [
            $this->block('hero', [
                'eyebrow' => 'Bangkok · Rayong · Singapore',
                'headline' => "Energy systems that hold.\nLoads that never guess.",
                'accent_line' => 'Illustrative subsidiary site.',
                'body' => 'A demonstration website for the Meridian group, showing how a second brand keeps its own pages, navigation, and search index.',
                'primary_cta' => ['label' => 'See the platform', 'url' => '/platform', 'style' => 'primary'],
                'secondary_cta' => ['label' => 'Talk to the team', 'url' => '/contact', 'style' => 'secondary'],
                'meta' => [
                    ['label' => 'Established', 'value' => '2016'],
                    ['label' => 'Sites', 'value' => '12'],
                    ['label' => 'Discipline', 'value' => 'Power'],
                    ['label' => 'Based', 'value' => 'Bangkok'],
                ],
                'theme' => 'dark',
                'layout' => 'panel',
                'align' => 'left',
            ]),
            $this->block('statement', [
                'eyebrow' => 'Why this site exists',
                'heading' => 'One group, two operating brands, no shared content tables.',
                'body' => 'Meridian Energy Systems is a fictional subsidiary used to demonstrate multi-site isolation. Editors here can only see this site; the public API returns only this site’s pages.',
                'aside' => 'All copy, figures, and capabilities on this site are illustrative.',
                'theme' => 'light',
            ]),
            $this->block('cta', [
                'eyebrow' => 'Demonstration',
                'heading' => 'This page is seeded to prove site-scoped content.',
                'body' => 'Switch sites in Studio to compare the page lists, navigation, and analytics of each brand.',
                'primary_cta' => ['label' => 'Open the platform page', 'url' => '/platform', 'style' => 'primary'],
                'theme' => 'dark',
            ]),
        ]);

        $platform = $this->page('platform', 'default', false, 'Platform — Meridian Energy Systems', [
            $this->block('split_hero', [
                'eyebrow' => 'Platform',
                'headline' => 'A fictional grid-services platform.',
                'body' => 'This page exists so the second site has more than one route, a working nav, and searchable content.',
                'primary_cta' => ['label' => 'Contact the team', 'url' => '/contact', 'style' => 'primary'],
                'theme' => 'dark',
            ]),
            $this->block('rich_text', [
                'eyebrow' => 'What it covers',
                'heading' => 'Illustrative scope.',
                'body' => '<p>Load forecasting, substation monitoring, and demand response orchestration are described here as demonstration content only. No real utility data is used.</p><p>The page is intentionally short; its purpose is to verify routing, language fallback, and site isolation.</p>',
                'width' => 'standard',
            ]),
        ]);

        $contact = $this->page('contact', 'contact', false, 'Contact — Meridian Energy Systems', [
            $this->block('statement', [
                'eyebrow' => 'Contact',
                'heading' => 'Talk to the demonstration team.',
                'body' => 'This is a fictional contact page. Messages sent from here are stored as demonstration leads in the Studio CMS.',
                'aside' => 'hello@meridian-energy.example',
                'theme' => 'light',
            ]),
        ]);

        Setting::updateOrCreate(
            ['site_id' => $this->site->id, 'group' => 'contact', 'key' => 'details'],
            [
                'is_public' => true,
                'value' => [
                    'email' => 'hello@meridian-energy.example',
                    'careers_email' => 'careers@meridian-energy.example',
                    'phone' => '+66 2 000 0000',
                    'hours' => ['en' => 'Monday–Friday, 08:30–17:30 ICT'],
                    'hq' => ['city' => 'Bangkok', 'address' => 'Rama IV Road, Khlong Toei, Bangkok 10110', 'label' => ['en' => 'Head office']],
                    'offices' => [
                        ['city' => 'Bangkok', 'label' => ['en' => 'Head office'], 'address' => 'Rama IV Road, Khlong Toei, Bangkok 10110', 'phone' => '+66 2 000 0000', 'email' => 'hello@meridian-energy.example'],
                    ],
                    'departments' => [],
                    'social' => [],
                    'legal_note' => 'Meridian Energy Systems is a fictional subsidiary created for a portfolio demonstration.',
                ],
            ]
        );

        Setting::updateOrCreate(
            ['site_id' => $this->site->id, 'group' => 'footer', 'key' => 'statement'],
            [
                'is_public' => true,
                'value' => ['en' => 'A demonstration subsidiary site for the Meridian group.'],
            ]
        );

        $this->menu('main', 'Main navigation', [
            ['label' => ['en' => 'Home'], 'url' => '/en', 'page_id' => null],
            ['label' => ['en' => 'Platform'], 'url' => '/en/platform', 'page_id' => $platform->id],
            ['label' => ['en' => 'Contact'], 'url' => '/en/contact', 'page_id' => $contact->id],
        ]);

        $this->menu('footer-company', 'Company', [
            ['label' => ['en' => 'Home'], 'url' => '/en', 'page_id' => null],
            ['label' => ['en' => 'Platform'], 'url' => '/en/platform', 'page_id' => $platform->id],
            ['label' => ['en' => 'Contact'], 'url' => '/en/contact', 'page_id' => $contact->id],
        ]);

        $this->menu('footer-legal', 'Legal', [
            ['label' => ['en' => 'Privacy policy'], 'url' => '/en/privacy-policy', 'page_id' => null],
        ]);
    }

    protected function page(string $slug, string $template, bool $isHome, string $seoTitle, array $blocks): Page
    {
        $page = Page::updateOrCreate(
            ['site_id' => $this->site->id, 'is_home' => $isHome, 'template' => $template],
            [
                'status' => 'published',
                'published_at' => now()->subDays(20),
                'is_home' => $isHome,
                'lock_version' => 1,
            ]
        );

        $page->translations()->updateOrCreate(
            ['locale' => 'en'],
            [
                'site_id' => $this->site->id,
                'title' => $seoTitle,
                'slug' => $slug,
                'excerpt' => 'Illustrative demonstration content for the Meridian Energy Systems site.',
                'blocks_version' => 1,
            ]
        );

        $page->blocks()->where('locale', 'en')->delete();

        foreach (array_values($blocks) as $index => $block) {
            $page->blocks()->create([
                'locale' => 'en',
                'block_uid' => (string) Str::uuid(),
                'type' => $block['type'],
                'sort_order' => $index,
                'props' => $block['props'],
                'is_visible_desktop' => true,
                'is_visible_tablet' => true,
                'is_visible_mobile' => true,
                'responsive' => null,
            ]);
        }

        SeoMetadata::updateOrCreate(
            ['seoable_type' => 'page', 'seoable_id' => $page->id, 'locale' => 'en'],
            [
                'site_id' => $this->site->id,
                'title' => $seoTitle,
                'description' => 'Illustrative content for the Meridian Energy Systems demonstration site.',
                'robots' => 'index,follow',
                'schema_type' => 'WebPage',
            ]
        );

        return $page;
    }

    protected function block(string $type, array $props): array
    {
        return ['type' => $type, 'props' => $props];
    }

    protected function menu(string $key, string $name, array $items): void
    {
        $menu = NavigationMenu::updateOrCreate(
            ['site_id' => $this->site->id, 'key' => $key, 'locale' => 'en'],
            ['name' => $name]
        );

        $menu->items()->delete();

        foreach ($items as $index => $item) {
            NavigationItem::create([
                'navigation_menu_id' => $menu->id,
                'parent_id' => null,
                'label' => $item['label'],
                'url' => $item['url'],
                'page_id' => $item['page_id'],
                'sort' => $index,
                'is_visible' => true,
            ]);
        }
    }
}
