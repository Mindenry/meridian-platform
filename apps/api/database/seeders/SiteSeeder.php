<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\Site;
use App\Models\SiteLocale;
use App\Models\WorkflowDefinition;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    public function run(): void
    {
        $site = Site::updateOrCreate(
            ['slug' => 'meridian'],
            [
                'name' => 'Meridian Industrial Group',
                'legal_name' => 'Meridian Industrial Solutions Co., Ltd.',
                'domain' => null,
                'default_locale' => 'th',
                'enabled_locales' => ['th', 'en', 'zh-CN'],
                'is_primary' => true,
                'brand' => [
                    'logo_text' => 'MERIDIAN',
                    'descriptor' => 'INDUSTRIAL GROUP',
                    'tagline' => 'Engineering Progress. Enabling Possibility.',
                    'accent' => '#E36D3F',
                    'ink' => '#101D2B',
                    'paper' => '#F5F3EE',
                    'steel' => '#89939D',
                ],
                'theme' => [
                    'default' => 'editorial',
                    'display_font' => 'Manrope',
                    'thai_font' => 'Noto Sans Thai',
                    'radius' => '2px',
                ],
                'seo_defaults' => [
                    'title' => 'Meridian Industrial Group — Engineering Progress. Enabling Possibility.',
                    'description' => 'Meridian is an industrial technology and engineering partner helping organisations modernise operations through automation, intelligent infrastructure, industrial software, and digital transformation.',
                    'og_image_url' => rtrim((string) config('media.public_url'), '/').'/media/og-default.jpg',
                ],
                'settings' => [
                    'demo_mode' => true,
                    'media_quota_bytes' => 5 * 1024 * 1024 * 1024,
                    'workflow' => ['require_approval' => true],
                ],
            ]
        );

        foreach (['th', 'en', 'zh-CN'] as $index => $locale) {
            SiteLocale::updateOrCreate(
                ['site_id' => $site->id, 'locale' => $locale],
                [
                    'label' => config('meridian.locales')[$locale]['label'],
                    'is_default' => $locale === 'th',
                    'is_enabled' => true,
                    'sort' => $index,
                ]
            );
        }

        Setting::updateOrCreate(
            ['site_id' => $site->id, 'group' => 'contact', 'key' => 'details'],
            [
                'is_public' => true,
                'value' => [
                    'email' => 'hello@meridian.example',
                    'careers_email' => 'careers@meridian.example',
                    'phone' => '+66 2 000 0000',
                    'hours' => ['en' => 'Monday–Friday, 08:30–17:30 ICT', 'th' => 'จันทร์–ศุกร์ 08:30–17:30 น.', 'zh-CN' => '周一至周五 08:30–17:30（泰国时间）'],
                    'hq' => [
                        'city' => 'Bangkok',
                        'address' => 'Rama IV Road, Khlong Toei, Bangkok 10110, Thailand',
                        'label' => ['en' => 'Head office', 'th' => 'สำนักงานใหญ่', 'zh-CN' => '总部'],
                    ],
                    'offices' => [
                        [
                            'city' => 'Bangkok',
                            'label' => ['en' => 'Head office', 'th' => 'สำนักงานใหญ่', 'zh-CN' => '总部'],
                            'address' => 'Rama IV Road, Khlong Toei, Bangkok 10110',
                            'phone' => '+66 2 000 0000',
                            'email' => 'hello@meridian.example',
                        ],
                        [
                            'city' => 'Rayong',
                            'label' => ['en' => 'Engineering centre', 'th' => 'ศูนย์วิศวกรรม', 'zh-CN' => '工程中心'],
                            'address' => 'Hemaraj Eastern Seaboard Industrial Estate, Pluak Daeng, Rayong 21140',
                            'phone' => '+66 38 000 000',
                            'email' => 'rayong@meridian.example',
                        ],
                        [
                            'city' => 'Singapore',
                            'label' => ['en' => 'Regional liaison', 'th' => 'สำนักงานประสานงานภูมิภาค', 'zh-CN' => '区域联络处'],
                            'address' => 'Anson Road, Tanjong Pagar, Singapore',
                            'phone' => '+65 6000 0000',
                            'email' => 'sea@meridian.example',
                        ],
                    ],
                    'departments' => [
                        [
                            'name' => ['en' => 'Business development', 'th' => 'พัฒนาธุรกิจ', 'zh-CN' => '业务发展'],
                            'email' => 'hello@meridian.example',
                            'description' => ['en' => 'New projects, partnerships, and capability briefings.', 'th' => 'โครงการใหม่ ความร่วมมือ และการนำเสนอศักยภาพ', 'zh-CN' => '新项目、合作与能力介绍。'],
                        ],
                        [
                            'name' => ['en' => 'Careers', 'th' => 'ทรัพยากรบุคคล', 'zh-CN' => '人才招聘'],
                            'email' => 'careers@meridian.example',
                            'description' => ['en' => 'Roles, internships, and application questions.', 'th' => 'ตำแหน่งงาน สหกิจศึกษา และคำถามเกี่ยวกับการสมัคร', 'zh-CN' => '职位、实习与申请咨询。'],
                        ],
                        [
                            'name' => ['en' => 'Media and investor relations', 'th' => 'สื่อสารองค์กรและนักลงทุน', 'zh-CN' => '媒体与投资者关系'],
                            'email' => 'media@meridian.example',
                            'description' => ['en' => 'Press enquiries and illustrative corporate materials.', 'th' => 'สอบถามสื่อและเอกสารองค์กรเพื่อการสาธิต', 'zh-CN' => '媒体问询与示例企业资料。'],
                        ],
                    ],
                    'social' => [],
                    'legal_note' => 'Meridian Industrial Group is a fictional brand created for a portfolio demonstration. All names, figures, projects, and outcomes are illustrative and must not be treated as real client work, certifications, or financial statements.',
                ],
            ]
        );

        Setting::updateOrCreate(
            ['site_id' => $site->id, 'group' => 'footer', 'key' => 'statement'],
            [
                'is_public' => true,
                'value' => [
                    'en' => 'An industrial technology and engineering partner for operations that cannot afford to guess.',
                    'th' => 'พันธมิตรด้านเทคโนโลยีและวิศวกรรมอุตสาหกรรม สำหรับการดำเนินงานที่ผิดพลาดไม่ได้',
                    'zh-CN' => '面向不容试错的工业运营，提供技术与工程合作伙伴。',
                ],
            ]
        );

        WorkflowDefinition::updateOrCreate(
            ['site_id' => $site->id, 'name' => 'Default editorial workflow'],
            [
                'steps' => [
                    ['name' => 'Editorial approval', 'permission' => 'pages.approve'],
                ],
                'is_default' => true,
            ]
        );

        // A second website proves multi-site isolation in tests and the site switcher.
        $energy = Site::updateOrCreate(
            ['slug' => 'meridian-energy'],
            [
                'name' => 'Meridian Energy Systems',
                'legal_name' => 'Meridian Energy Systems Co., Ltd.',
                'domain' => null,
                'default_locale' => 'en',
                'enabled_locales' => ['en'],
                'is_primary' => false,
                'brand' => [
                    'logo_text' => 'MERIDIAN ENERGY',
                    'descriptor' => 'SYSTEMS',
                    'accent' => '#607D91',
                    'ink' => '#101D2B',
                    'paper' => '#F5F3EE',
                ],
                'theme' => ['default' => 'editorial'],
                'seo_defaults' => [
                    'title' => 'Meridian Energy Systems — illustrative subsidiary site',
                    'description' => 'A second demonstration website used to prove site-scoped content and access control.',
                ],
                'settings' => ['demo_mode' => true],
            ]
        );

        SiteLocale::updateOrCreate(
            ['site_id' => $energy->id, 'locale' => 'en'],
            ['label' => 'English', 'is_default' => true, 'is_enabled' => true, 'sort' => 0]
        );

        WorkflowDefinition::updateOrCreate(
            ['site_id' => $energy->id, 'name' => 'Default editorial workflow'],
            ['steps' => [['name' => 'Editorial approval', 'permission' => 'pages.approve']], 'is_default' => true]
        );
    }
}
