<?php

namespace Database\Seeders;

use App\Models\Industry;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Site;
use App\Models\Solution;
use Illuminate\Database\Seeder;

class NavigationSeeder extends Seeder
{
    protected Site $site;

    public function run(): void
    {
        $this->site = Site::where('slug', 'meridian')->firstOrFail();

        foreach (['th', 'en', 'zh-CN'] as $locale) {
            $this->mainMenu($locale);
            $this->footerMenus($locale);
        }
    }

    protected function mainMenu(string $locale): void
    {
        $menu = NavigationMenu::updateOrCreate(
            ['site_id' => $this->site->id, 'key' => 'main', 'locale' => $locale],
            ['name' => 'Main navigation']
        );

        $menu->items()->delete();

        $solutions = Solution::where('site_id', $this->site->id)->where('status', 'published')->orderBy('sort')->with('translations')->get();
        $industries = Industry::where('site_id', $this->site->id)->where('status', 'published')->orderBy('sort')->with('translations')->get();

        $items = [
            [
                'label' => ['th' => 'โซลูชัน', 'en' => 'Solutions', 'zh-CN' => '解决方案'],
                'url' => $this->url($locale, 'solutions'),
                'children' => $solutions->map(fn ($solution) => [
                    'label' => $this->labels($solution),
                    'url' => $this->url($locale, 'solutions/'.$this->translation($solution, $locale)?->slug),
                ])->all(),
            ],
            [
                'label' => ['th' => 'อุตสาหกรรม', 'en' => 'Industries', 'zh-CN' => '行业'],
                'url' => $this->url($locale, 'industries'),
                'children' => $industries->map(fn ($industry) => [
                    'label' => $this->labels($industry),
                    'url' => $this->url($locale, 'industries/'.$this->translation($industry, $locale)?->slug),
                ])->all(),
            ],
            [
                'label' => ['th' => 'ผลงาน', 'en' => 'Case studies', 'zh-CN' => '案例'],
                'url' => $this->url($locale, 'case-studies'),
            ],
            [
                'label' => ['th' => 'บทความ', 'en' => 'Insights', 'zh-CN' => '洞察'],
                'url' => $this->url($locale, 'insights'),
            ],
            [
                'label' => ['th' => 'เกี่ยวกับเรา', 'en' => 'About', 'zh-CN' => '关于'],
                'url' => $this->pageUrl('about', $locale),
                'children' => [
                    ['label' => ['th' => 'เรื่องราวของเรา', 'en' => 'Our story', 'zh-CN' => '我们的故事'], 'url' => $this->pageUrl('about', $locale)],
                    ['label' => ['th' => 'ร่วมงานกับเรา', 'en' => 'Careers', 'zh-CN' => '加入我们'], 'url' => $this->pageUrl('careers', $locale)],
                    ['label' => ['th' => 'นักลงทุนสัมพันธ์', 'en' => 'Investor relations', 'zh-CN' => '投资者关系'], 'url' => $this->pageUrl('investor-relations', $locale)],
                    ['label' => ['th' => 'ติดต่อเรา', 'en' => 'Contact', 'zh-CN' => '联系我们'], 'url' => $this->pageUrl('contact', $locale)],
                ],
            ],
            [
                'label' => ['th' => 'ติดต่อ', 'en' => 'Contact', 'zh-CN' => '联系'],
                'url' => $this->pageUrl('contact', $locale),
            ],
        ];

        foreach ($items as $index => $item) {
            $created = NavigationItem::create([
                'navigation_menu_id' => $menu->id,
                'parent_id' => null,
                'label' => $item['label'],
                'url' => $item['url'],
                'sort' => $index,
                'is_visible' => true,
            ]);

            foreach ($item['children'] ?? [] as $childIndex => $child) {
                NavigationItem::create([
                    'navigation_menu_id' => $menu->id,
                    'parent_id' => $created->id,
                    'label' => $child['label'],
                    'url' => $child['url'],
                    'sort' => $childIndex,
                    'is_visible' => true,
                ]);
            }
        }
    }

    protected function footerMenus(string $locale): void
    {
        $this->footerMenu($locale, 'footer-company', 'Company', [
            ['label' => ['th' => 'เกี่ยวกับเรา', 'en' => 'About', 'zh-CN' => '关于'], 'url' => $this->pageUrl('about', $locale)],
            ['label' => ['th' => 'ร่วมงานกับเรา', 'en' => 'Careers', 'zh-CN' => '加入我们'], 'url' => $this->pageUrl('careers', $locale)],
            ['label' => ['th' => 'นักลงทุนสัมพันธ์', 'en' => 'Investor relations', 'zh-CN' => '投资者关系'], 'url' => $this->pageUrl('investor-relations', $locale)],
            ['label' => ['th' => 'ติดต่อเรา', 'en' => 'Contact', 'zh-CN' => '联系我们'], 'url' => $this->pageUrl('contact', $locale)],
        ]);

        $this->footerMenu($locale, 'footer-capabilities', 'Capabilities', [
            ['label' => ['th' => 'โซลูชัน', 'en' => 'Solutions', 'zh-CN' => '解决方案'], 'url' => $this->url($locale, 'solutions')],
            ['label' => ['th' => 'อุตสาหกรรม', 'en' => 'Industries', 'zh-CN' => '行业'], 'url' => $this->url($locale, 'industries')],
            ['label' => ['th' => 'ผลงาน', 'en' => 'Case studies', 'zh-CN' => '案例'], 'url' => $this->url($locale, 'case-studies')],
            ['label' => ['th' => 'บทความ', 'en' => 'Insights', 'zh-CN' => '洞察'], 'url' => $this->url($locale, 'insights')],
        ]);

        $this->footerMenu($locale, 'footer-legal', 'Legal', [
            ['label' => ['th' => 'นโยบายความเป็นส่วนตัว', 'en' => 'Privacy policy', 'zh-CN' => '隐私政策'], 'url' => $this->pageUrl('privacy-policy', $locale)],
            ['label' => ['th' => 'นโยบายคุกกี้', 'en' => 'Cookie policy', 'zh-CN' => 'Cookie 政策'], 'url' => $this->pageUrl('cookie-policy', $locale)],
            ['label' => ['th' => 'ข้อกำหนดการใช้งาน', 'en' => 'Terms of use', 'zh-CN' => '使用条款'], 'url' => $this->pageUrl('terms-of-use', $locale)],
            ['label' => ['th' => 'การคุ้มครองข้อมูล', 'en' => 'Data protection', 'zh-CN' => '数据保护'], 'url' => $this->pageUrl('data-protection', $locale)],
        ]);
    }

    protected function footerMenu(string $locale, string $key, string $name, array $items): void
    {
        $menu = NavigationMenu::updateOrCreate(
            ['site_id' => $this->site->id, 'key' => $key, 'locale' => $locale],
            ['name' => $name]
        );

        $menu->items()->delete();

        foreach ($items as $index => $item) {
            NavigationItem::create([
                'navigation_menu_id' => $menu->id,
                'parent_id' => null,
                'label' => $item['label'],
                'url' => $item['url'],
                'sort' => $index,
                'is_visible' => true,
            ]);
        }
    }

    protected function translation($model, string $locale)
    {
        return $model->translations->firstWhere('locale', $locale)
            ?? $model->translations->firstWhere('locale', 'en')
            ?? $model->translations->first();
    }

    protected function labels($model): array
    {
        return [
            'th' => $this->translation($model, 'th')?->title,
            'en' => $this->translation($model, 'en')?->title,
            'zh-CN' => $this->translation($model, 'zh-CN')?->title,
        ];
    }

    protected function pageUrl(string $referenceSlug, string $locale): string
    {
        $page = Page::where('site_id', $this->site->id)
            ->whereHas('translations', fn ($query) => $query->where('slug', $referenceSlug))
            ->with('translations')
            ->first();

        if (! $page) {
            return $this->url($locale, $referenceSlug);
        }

        $translation = $page->translations->firstWhere('locale', $locale)
            ?? $page->translations->firstWhere('locale', 'en')
            ?? $page->translations->first();

        if ($page->is_home) {
            return $this->url($locale, '');
        }

        return $this->url($locale, $translation?->slug ?? $referenceSlug);
    }

    protected function url(string $locale, string $path): string
    {
        $route = config('meridian.locales')[$locale]['route'] ?? strtolower($locale);

        return '/'.$route.($path !== '' ? '/'.ltrim($path, '/') : '');
    }
}
