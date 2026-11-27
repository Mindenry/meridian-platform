<?php

namespace Database\Seeders;

use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\SeoMetadata;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PageSeeder extends Seeder
{
    protected Site $site;

    protected $media;

    protected ?User $author;

    public function run(): void
    {
        $this->site = Site::where('slug', 'meridian')->firstOrFail();
        $this->media = MediaAsset::where('site_id', $this->site->id)->get()->keyBy(fn (MediaAsset $asset) => pathinfo($asset->original_name, PATHINFO_FILENAME));
        $this->author = User::where('email', 'editor@meridian.example')->first();

        $this->home();
        $this->about();
        $this->contact();
        $this->investor();
        $this->careers();
        $this->legalPages();
    }

    protected function home(): void
    {
        $pages = [
            'th' => [
                'title' => 'เมอริเดียน อินดัสเทรียล กรุ๊ป',
                'slug' => 'home',
                'excerpt' => 'พันธมิตรด้านเทคโนโลยีและวิศวกรรมอุตสาหกรรม ที่เชื่อมความเชี่ยวชาญ ระบบอัจฉริยะ และผลลัพธ์ทางธุรกิจที่วัดได้',
                'seo_title' => 'เมอริเดียน อินดัสเทรียล กรุ๊ป — วิศวกรรมที่ก้าวหน้า เปิดความเป็นไปได้',
                'seo_description' => 'เมอริเดียนช่วยองค์กรอุตสาหกรรมสมัยใหม่ผ่านระบบอัตโนมัติ โรงงานอัจฉริยะ ซอฟต์แวร์อุตสาหกรรม โครงสร้างพื้นฐาน และการเปลี่ยนผ่านดิจิทัล',
                'blocks' => $this->homeBlocks('th'),
            ],
            'en' => [
                'title' => 'Meridian Industrial Group',
                'slug' => 'home',
                'excerpt' => 'An industrial technology and engineering partner connecting expertise, intelligent systems, and measurable business outcomes.',
                'seo_title' => 'Meridian Industrial Group — Engineering Progress. Enabling Possibility.',
                'seo_description' => 'Meridian helps industrial organisations modernise operations through automation, smart manufacturing, industrial software, infrastructure engineering, and digital transformation.',
                'blocks' => $this->homeBlocks('en'),
            ],
            'zh-CN' => [
                'title' => 'Meridian 工业集团',
                'slug' => 'home',
                'excerpt' => '连接工程专业、智能系统与可衡量业务成果的工业技术与工程合作伙伴。',
                'seo_title' => 'Meridian 工业集团 — 工程进步，开启可能',
                'seo_description' => 'Meridian 通过自动化、智能制造、工业软件、基础设施工程与数字化转型，帮助工业组织实现运营现代化。',
                'blocks' => $this->homeBlocks('zh-CN'),
            ],
        ];

        $this->makePage('home', 'home', true, $pages, [
            'focus_keyword' => 'industrial engineering Thailand',
            'schema_type' => 'WebPage',
        ]);
    }

    protected function about(): void
    {
        $pages = [
            'th' => [
                'title' => 'เกี่ยวกับเรา',
                'slug' => 'เกี่ยวกับเรา',
                'excerpt' => 'เมอริเดียนเป็นพันธมิตรด้านวิศวกรรมอุตสาหกรรม เราอยู่ใกล้กับเนื้องานจริง ตั้งแต่แบบก่อสร้าง การทดสอบระบบ ไปจนถึงทีมที่ดูแลโรงงานหลังจากนั้น',
                'seo_title' => 'เกี่ยวกับเมอริเดียน — พันธมิตรวิศวกรรมอุตสาหกรรม',
                'seo_description' => 'รู้จักเมอริเดียน วิสัยทัศน์ พันธกิจ ค่านิยม ผู้นำ และเส้นทางการเติบโตของพันธมิตรวิศวกรรมอุตสาหกรรมจากกรุงเทพฯ',
                'blocks' => $this->aboutBlocks('th'),
            ],
            'en' => [
                'title' => 'About Meridian',
                'slug' => 'about',
                'excerpt' => 'Meridian is an industrial engineering partner. We stay close to the work: the drawings, the commissioning, and the team that runs the plant afterwards.',
                'seo_title' => 'About Meridian — an industrial engineering partner',
                'seo_description' => 'Meet Meridian: our story, mission, values, leadership, and the capabilities we bring to industrial operations across the region.',
                'blocks' => $this->aboutBlocks('en'),
            ],
            'zh-CN' => [
                'title' => '关于 Meridian',
                'slug' => '关于我们',
                'excerpt' => 'Meridian 是一家工业工程合作伙伴。我们贴近实际工作：图纸、调试，以及此后运营工厂的团队。',
                'seo_title' => '关于 Meridian — 工业工程合作伙伴',
                'seo_description' => '了解 Meridian 的故事、使命、价值观、领导团队，以及我们为区域工业运营带来的能力。',
                'blocks' => $this->aboutBlocks('zh-CN'),
            ],
        ];

        $this->makePage('about', 'editorial', false, $pages, [
            'focus_keyword' => 'industrial engineering partner',
            'schema_type' => 'AboutPage',
        ]);
    }

    protected function contact(): void
    {
        $pages = [
            'th' => [
                'title' => 'ติดต่อเรา',
                'slug' => 'ติดต่อเรา',
                'excerpt' => 'พูดคุยกับทีมที่ใกล้ชิดโจทย์ของคุณมากที่สุด ทั้งโครงการใหม่ ความร่วมมือ และการร่วมงาน',
                'seo_title' => 'ติดต่อเมอริเดียน — ปรึกษาโครงการอุตสาหกรรม',
                'seo_description' => 'ติดต่อเมอริเดียนเพื่อปรึกษาโครงการระบบอัตโนมัติ โครงสร้างพื้นฐาน ซอฟต์แวร์อุตสาหกรรม หรือความร่วมมือทางธุรกิจ',
                'blocks' => $this->contactBlocks('th'),
            ],
            'en' => [
                'title' => 'Contact',
                'slug' => 'contact',
                'excerpt' => 'Talk to the team closest to the problem — new projects, partnerships, or a career conversation.',
                'seo_title' => 'Contact Meridian — industrial project enquiries',
                'seo_description' => 'Contact Meridian about automation, infrastructure, industrial software, digital transformation, partnerships, or careers.',
                'blocks' => $this->contactBlocks('en'),
            ],
            'zh-CN' => [
                'title' => '联系我们',
                'slug' => 'contact',
                'excerpt' => '与最贴近问题的团队沟通——新项目、合作或职业机会。',
                'seo_title' => '联系 Meridian — 工业项目咨询',
                'seo_description' => '就自动化、基础设施、工业软件、数字化转型、合作或招聘事宜联系 Meridian。',
                'blocks' => $this->contactBlocks('zh-CN'),
            ],
        ];

        $this->makePage('contact', 'contact', false, $pages, [
            'focus_keyword' => 'contact industrial engineering',
            'schema_type' => 'ContactPage',
        ]);
    }

    protected function investor(): void
    {
        $pages = [
            'th' => [
                'title' => 'นักลงทุนสัมพันธ์',
                'slug' => 'นักลงทุนสัมพันธ์',
                'excerpt' => 'ภาพรวมองค์กร ผลประกอบการ และเอกสารสำหรับนักลงทุน ซึ่งทั้งหมดเป็นข้อมูลตัวอย่างสำหรับแบรนด์สมมติ',
                'seo_title' => 'นักลงทุนสัมพันธ์ — เมอริเดียน (ข้อมูลตัวอย่าง)',
                'seo_description' => 'ภาพรวมองค์กร ตัวเลขทางการเงิน และเอกสารธรรมาภิบาลเพื่อการสาธิต ข้อมูลทั้งหมดเป็นตัวอย่างและไม่ใช่รายงานที่ตรวจสอบแล้ว',
                'blocks' => $this->investorBlocks('th'),
            ],
            'en' => [
                'title' => 'Investor relations',
                'slug' => 'investor-relations',
                'excerpt' => 'An illustrative corporate and financial overview for a fictional private company. All figures are demonstration data.',
                'seo_title' => 'Investor relations — Meridian (illustrative)',
                'seo_description' => 'Illustrative corporate overview, financial highlights, and governance documents. The company is fictional and the figures are not audited.',
                'blocks' => $this->investorBlocks('en'),
            ],
        ];

        $this->makePage('investor-relations', 'investor', false, $pages, [
            'focus_keyword' => 'industrial company investor relations',
            'schema_type' => 'WebPage',
            'robots' => 'noindex,follow',
        ]);
    }

    protected function careers(): void
    {
        $pages = [
            'th' => [
                'title' => 'ร่วมงานกับเรา',
                'slug' => 'ร่วมงานกับเรา',
                'excerpt' => 'สร้างระบบที่คนอื่นต้องพึ่งพา กับทีมที่ให้ความสำคัญกับงานฝีมือ ความชัดเจน และการเรียนรู้ตลอดชีวิต',
                'seo_title' => 'ร่วมงานกับเมอริเดียน — งานวิศวกรรมอุตสาหกรรม',
                'seo_description' => 'สำรวจโอกาสงานด้านระบบอัตโนมัติ ซอฟต์แวร์อุตสาหกรรม โครงสร้างพื้นฐาน และการปฏิบัติการ ที่เมอริเดียน',
                'blocks' => $this->careersBlocks('th'),
            ],
            'en' => [
                'title' => 'Careers',
                'slug' => 'careers',
                'excerpt' => 'Build systems other people depend on, with a team that values craft, clarity, and continuous learning.',
                'seo_title' => 'Careers at Meridian — industrial engineering roles',
                'seo_description' => 'Explore automation, industrial software, infrastructure, and operations roles at Meridian. View open positions and apply.',
                'blocks' => $this->careersBlocks('en'),
            ],
        ];

        $this->makePage('careers', 'careers', false, $pages, [
            'focus_keyword' => 'industrial engineering jobs Thailand',
            'schema_type' => 'WebPage',
        ]);
    }

    protected function legalPages(): void
    {
        $legal = [
            'privacy-policy' => ['th' => 'นโยบายความเป็นส่วนตัว', 'en' => 'Privacy policy', 'sort' => 1, 'title_key' => 'privacy'],
            'cookie-policy' => ['th' => 'นโยบายคุกกี้', 'en' => 'Cookie policy', 'sort' => 2, 'title_key' => 'cookie'],
            'terms-of-use' => ['th' => 'ข้อกำหนดการใช้งาน', 'en' => 'Terms of use', 'sort' => 3, 'title_key' => 'terms'],
            'data-protection' => ['th' => 'การคุ้มครองข้อมูลส่วนบุคคล', 'en' => 'Data protection', 'sort' => 4, 'title_key' => 'data'],
        ];

        $legalDescriptions = [
            'privacy' => [
                'th' => 'ตัวอย่างนโยบายความเป็นส่วนตัวสำหรับเว็บไซต์สาธิต ครอบคลุมข้อมูลที่เก็บ วิธีใช้ และสิทธิของเจ้าของข้อมูล',
                'en' => 'Sample privacy policy for this demonstration website, covering the data collected, how it is used, and individual rights.',
            ],
            'cookie' => [
                'th' => 'ตัวอย่างนโยบายคุกกี้สำหรับเว็บไซต์สาธิต อธิบายคุกกี้ที่จำเป็นและการวัดผลแบบไม่ระบุตัวตน',
                'en' => 'Sample cookie policy for this demonstration website, explaining essential cookies and privacy-conscious analytics.',
            ],
            'terms' => [
                'th' => 'ตัวอย่างข้อกำหนดการใช้งานสำหรับเว็บไซต์สาธิต อธิบายขอบเขตการใช้งานและข้อจำกัดความรับผิด',
                'en' => 'Sample terms of use for this demonstration website, explaining permitted use and liability boundaries.',
            ],
            'data' => [
                'th' => 'ตัวอย่างเอกสารการคุ้มครองข้อมูลสำหรับเว็บไซต์สาธิต อธิบายฐานทางกฎหมายและการเก็บรักษาข้อมูล',
                'en' => 'Sample data protection document for this demonstration website, explaining lawful basis and retention.',
            ],
        ];

        foreach ($legal as $slug => $meta) {
            $pages = [];

            foreach (['th', 'en'] as $locale) {
                $pages[$locale] = [
                    'title' => $meta[$locale],
                    'slug' => $locale === 'th' ? $this->legalThaiSlug($slug) : $slug,
                    'excerpt' => $legalDescriptions[$meta['title_key']][$locale],
                    'seo_title' => ($locale === 'th' ? $meta['th'] : $meta['en']).' — Meridian (sample)',
                    'seo_description' => $legalDescriptions[$meta['title_key']][$locale],
                    'blocks' => $this->legalBlocks($locale, $meta['title_key']),
                ];
            }

            $this->makePage($slug, 'legal', false, $pages, [
                'focus_keyword' => 'legal',
                'schema_type' => 'WebPage',
                'robots' => 'noindex,follow',
            ]);
        }
    }

    protected function makePage(string $key, string $template, bool $isHome, array $pages, array $seoDefaults = []): void
    {
        $first = reset($pages);
        $page = Page::where('site_id', $this->site->id)
            ->whereHas('translations', fn ($query) => $query->where('slug', $first['slug']))
            ->first();

        if (! $page) {
            $page = new Page([
                'site_id' => $this->site->id,
                'author_id' => $this->author?->id,
                'created_by' => $this->author?->id,
            ]);
        }

        $page->fill([
            'template' => $template,
            'status' => 'published',
            'published_at' => now()->subDays(30),
            'is_home' => $isHome,
            'lock_version' => 1,
            'updated_by' => $this->author?->id,
        ])->save();

        foreach ($pages as $locale => $data) {
            $page->translations()->updateOrCreate(
                ['locale' => $locale],
                [
                    'site_id' => $this->site->id,
                    'title' => $data['title'],
                    'slug' => $data['slug'],
                    'excerpt' => $data['excerpt'],
                    'blocks_version' => 1,
                ]
            );

            $page->blocks()->where('locale', $locale)->delete();

            foreach (array_values($data['blocks']) as $index => $block) {
                $page->blocks()->create([
                    'locale' => $locale,
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
                ['seoable_type' => 'page', 'seoable_id' => $page->id, 'locale' => $locale],
                [
                    'site_id' => $this->site->id,
                    'title' => $data['seo_title'],
                    'description' => $data['seo_description'],
                    'focus_keyword' => $seoDefaults['focus_keyword'] ?? null,
                    'og_title' => $data['seo_title'],
                    'og_description' => $data['seo_description'],
                    'og_image_id' => $this->media['hero-facility']?->id,
                    'robots' => $seoDefaults['robots'] ?? 'index,follow',
                    'schema_type' => $seoDefaults['schema_type'] ?? 'WebPage',
                ]
            );
        }
    }

    protected function image(string $name, string $locale): ?array
    {
        $asset = $this->media[$name] ?? null;

        if (! $asset) {
            return null;
        }

        return ['media_id' => $asset->id, 'url' => $asset->url, 'alt' => $asset->altFor($locale)];
    }

    protected function link(string $label, string $url, string $style = 'primary'): array
    {
        return ['label' => $label, 'url' => $url, 'style' => $style];
    }

    protected function block(string $type, array $props): array
    {
        return ['type' => $type, 'props' => $props];
    }

    protected function homeBlocks(string $locale): array
    {
        $copy = [
            'th' => [
                'eyebrow' => 'กรุงเทพฯ · สิงคโปร์ · ระยอง',
                'headline' => "วิศวกรรมที่ก้าวหน้า\nเปิดความเป็นไปได้",
                'accent' => 'ระบบอุตสาหกรรมอัจฉริยะ ที่วัดผลได้จริง',
                'body' => 'เราเชื่อมความเชี่ยวชาญทางวิศวกรรม ระบบอัจฉริยะ และผลลัพธ์ทางธุรกิจที่วัดได้ สำหรับองค์กรที่การดำเนินงานผิดพลาดไม่ได้',
                'primary' => 'ปรึกษาโครงการ', 'secondary' => 'สำรวจโซลูชัน',
                'meta' => [['label' => 'ก่อตั้ง', 'value' => '2552'], ['label' => 'ผู้เชี่ยวชาญ', 'value' => '240+'], ['label' => 'อุตสาหกรรม', 'value' => '05'], ['label' => 'ฐาน', 'value' => 'กรุงเทพฯ']],
                'scroll' => 'เลื่อนเพื่อดู',
                'statement_eyebrow' => 'เราคือใคร',
                'statement_heading' => 'ระบบอุตสาหกรรมมีคุณค่าเมื่อคนที่ดูแลมันเชื่อมั่นในสิ่งที่เกิดขึ้นต่อไป',
                'statement_body' => 'งานของเราคือทำให้โรงงาน ระบบ และทีมงานทำงานเป็นระบบเดียวที่ตรวจสอบได้ ซึ่งมักหมายถึงการเปลี่ยนวิธีแลกเปลี่ยนข้อมูลและการตัดสินใจระหว่างกัน',
                'statement_aside' => 'เราทำงานตั้งแต่การตรวจวินิจฉัยกระบวนการไปจนถึงการทดสอบระบบ การส่งมอบ และการสนับสนุนระยะยาว ทีมเดียวกันรับผิดชอบตลอดเส้นทาง',
                'cap_eyebrow' => 'ขีดความสามารถ', 'cap_heading' => 'จากการตรวจสอบกระบวนการ สู่ระบบปฏิบัติการ',
                'cap_intro' => 'หกสาขาวิชาที่ทำงานร่วมกันเป็นแนวปฏิบัติเดียว ตั้งแต่อุปกรณ์หน้างานจนถึงข้อมูลระดับผู้บริหาร',
                'cap_items' => [
                    ['number' => '01', 'title' => 'ระบบอัตโนมัติอุตสาหกรรม', 'description' => 'สถาปัตยกรรมควบคุม หุ่นยนต์ และการเชื่อมต่อสายการผลิตที่ออกแบบเพื่อความพร้อมใช้งาน', 'image' => 'automation', 'url' => '/solutions'],
                    ['number' => '02', 'title' => 'โรงงานอัจฉริยะ', 'description' => 'การตรวจสอบย้อนกลับ OEE และข้อมูลคุณภาพที่สร้างจากพื้นที่ผลิตจริง', 'image' => 'manufacturing', 'url' => '/solutions'],
                    ['number' => '03', 'title' => 'ซอฟต์แวร์อุตสาหกรรม', 'description' => 'แพลตฟอร์มปฏิบัติการที่เปลี่ยนข้อมูลเครื่องจักรเป็นการตัดสินใจ', 'image' => 'precision', 'url' => '/solutions'],
                    ['number' => '04', 'title' => 'โครงสร้างพื้นฐาน', 'description' => 'ระบบไฟฟ้า สาธารณูปโภค และอาคารที่ทนทานสำหรับการดำเนินงานสำคัญ', 'image' => 'infrastructure', 'url' => '/solutions'],
                ],
                'solutions_eyebrow' => 'โซลูชัน', 'solutions_heading' => 'ขีดความสามารถทางวิศวกรรมที่จัดเรียงตามผลลัพธ์',
                'solutions_intro' => 'ทุกโปรเจกต์เริ่มจากข้อจำกัดทางธุรกิจ การเลือกเทคโนโลยีจึงเกิดหลังการวินิจฉัยปัญหา',
                'project_eyebrow' => 'ผลงานที่เลือก', 'project_heading' => 'สายการผลิตที่เรียนรู้ก่อนหยุด',
                'projects_heading' => 'งานที่วัดผลจากการดำเนินงาน',
                'industries_eyebrow' => 'อุตสาหกรรม', 'industries_heading' => 'แต่ละอุตสาหกรรมมีข้อจำกัดต่างกัน เราออกแบบให้ตรงกับข้อจำกัดนั้น',
                'industries_intro' => 'เราเข้าใจบริบทของแต่ละอุตสาหกรรม ทั้งข้อจำกัดด้านกฎระเบียบ ฤดูกาลผลิต และความคาดหวังของผู้มีส่วนได้ส่วนเสีย',
                'stats_eyebrow' => 'ภาพรวม', 'stats_heading' => 'สร้างมาเพื่อการวัดผล',
                'stats_disclaimer' => 'ตัวเลขทั้งหมดเป็นข้อมูลตัวอย่างเพื่อการสาธิตสำหรับแบรนด์สมมติ ไม่ใช่ผลการดำเนินงานจริง',
                'tech_eyebrow' => 'เทคโนโลยี', 'tech_heading' => 'แพลตฟอร์มที่ทีมของเราทำงานด้วย',
                'tech_note' => 'การกล่าวถึงเทคโนโลยีแสดงถึงความคุ้นเคยในการทำงานเท่านั้น ไม่ได้หมายถึงความร่วมมือหรือการรับรองจากผู้ผลิต',
                'insights_eyebrow' => 'บทความ', 'insights_heading' => 'บันทึกภาคสนามจากงานอุตสาหกรรม',
                'cta_eyebrow' => 'เริ่มต้นบทสนทนา', 'cta_heading' => 'นำการดำเนินงานที่ต้องทำงานได้จริงมาให้เรา',
                'cta_body' => 'บอกเราว่าอะไรคือข้อจำกัด อะไรกำลังเปลี่ยน และอะไรหยุดไม่ได้ เราจะกลับมาพร้อมมุมมองที่ชัดเจน',
                'cta_primary' => 'คุยกับทีมของเรา', 'cta_secondary' => 'ดาวน์โหลดโปรไฟล์บริษัท',
            ],
            'en' => [
                'eyebrow' => 'Bangkok · Singapore · Rayong',
                'headline' => "Engineering progress.\nEnabling possibility.",
                'accent' => 'Industrial intelligence, delivered.',
                'body' => 'We connect industrial expertise, intelligent systems, and measurable business outcomes for organisations that cannot afford to guess.',
                'primary' => 'Discuss a project', 'secondary' => 'Explore solutions',
                'meta' => [['label' => 'Established', 'value' => '2009'], ['label' => 'Specialists', 'value' => '240+'], ['label' => 'Sectors', 'value' => '05'], ['label' => 'Based', 'value' => 'Bangkok']],
                'scroll' => 'Scroll to explore',
                'statement_eyebrow' => 'Who we are',
                'statement_heading' => 'Industrial systems are only valuable when the people running them can trust what happens next.',
                'statement_body' => 'Our job is to make plants, systems, and teams behave as one accountable operation, which usually means changing how they share data and decisions.',
                'statement_aside' => 'Our work runs from process diagnosis through commissioning, handover, and long-term support. The same team remains accountable across the whole path.',
                'cap_eyebrow' => 'Capabilities', 'cap_heading' => 'From process audit to operating system.',
                'cap_intro' => 'Six disciplines working as one practice, from the shop floor to the executive view.',
                'cap_items' => [
                    ['number' => '01', 'title' => 'Industrial automation', 'description' => 'Control architecture, robotics, and line integration engineered for uptime.', 'image' => 'automation', 'url' => '/solutions'],
                    ['number' => '02', 'title' => 'Smart manufacturing', 'description' => 'Traceability, OEE, and quality intelligence built on the plant floor.', 'image' => 'manufacturing', 'url' => '/solutions'],
                    ['number' => '03', 'title' => 'Industrial software', 'description' => 'Operational platforms that turn machine data into decisions.', 'image' => 'precision', 'url' => '/solutions'],
                    ['number' => '04', 'title' => 'Infrastructure engineering', 'description' => 'Resilient electrical, utility, and facility systems for critical operations.', 'image' => 'infrastructure', 'url' => '/solutions'],
                ],
                'solutions_eyebrow' => 'Solutions', 'solutions_heading' => 'Engineering capability, organised around outcomes.',
                'solutions_intro' => 'Every engagement starts with the business constraint, so the technology choice comes after the diagnosis.',
                'project_eyebrow' => 'Selected work', 'project_heading' => 'A production line that learns before it stops.',
                'projects_heading' => 'Work measured in operating outcomes.',
                'industries_eyebrow' => 'Industries', 'industries_heading' => 'Each sector is constrained differently. We design for that.',
                'industries_intro' => 'We understand the context of each sector: regulatory limits, production seasons, and what stakeholders actually expect.',
                'stats_eyebrow' => 'At a glance', 'stats_heading' => 'Built to be measured.',
                'stats_disclaimer' => 'All figures are illustrative demonstration data for this fictional brand, not actual operating results.',
                'tech_eyebrow' => 'Technology', 'tech_heading' => 'Platforms our teams work with.',
                'tech_note' => 'Technology references describe working familiarity only. No vendor partnership or certification is implied.',
                'insights_eyebrow' => 'Insights', 'insights_heading' => 'Field notes from industrial practice.',
                'cta_eyebrow' => 'Start a conversation', 'cta_heading' => 'Bring us the operation that has to work.',
                'cta_body' => 'Tell us what is constrained, what is changing, and what cannot fail. We will come back with a clear point of view.',
                'cta_primary' => 'Talk to our team', 'cta_secondary' => 'Download company profile',
            ],
            'zh-CN' => [
                'eyebrow' => '曼谷 · 新加坡 · 罗勇',
                'headline' => "工程进步\n开启可能",
                'accent' => '让工业智能真正落地。',
                'body' => '我们为不容试错的工业组织连接工程专业、智能系统与可衡量的业务成果。',
                'primary' => '洽谈项目', 'secondary' => '探索解决方案',
                'meta' => [['label' => '成立', 'value' => '2009'], ['label' => '专业人员', 'value' => '240+'], ['label' => '行业', 'value' => '05'], ['label' => '总部', 'value' => '曼谷']],
                'scroll' => '向下浏览',
                'statement_eyebrow' => '我们是谁',
                'statement_heading' => '只有当运营者能够信任下一步会发生什么，工业系统才有价值。',
                'statement_body' => '我们的任务是把工厂、系统与团队变成一套可问责的整体运营，这通常意味着改变它们共享数据和做决定的方式。',
                'statement_aside' => '我们的工作从工艺诊断延伸到调试、移交与长期支持，同一支团队对全过程负责。',
                'cap_eyebrow' => '能力', 'cap_heading' => '从工艺审计到运营系统。',
                'cap_intro' => '六个专业作为一个团队协作，从车间现场到管理层视角。',
                'cap_items' => [
                    ['number' => '01', 'title' => '工业自动化', 'description' => '以稳定运行为目标设计控制架构、机器人与产线集成。', 'image' => 'automation', 'url' => '/solutions'],
                    ['number' => '02', 'title' => '智能制造', 'description' => '在车间现场构建追溯、OEE 与质量智能。', 'image' => 'manufacturing', 'url' => '/solutions'],
                    ['number' => '03', 'title' => '工业软件', 'description' => '把设备数据转化为决策的运营平台。', 'image' => 'precision', 'url' => '/solutions'],
                    ['number' => '04', 'title' => '基础设施工程', 'description' => '面向关键运营的可靠电气、公用工程与设施系统。', 'image' => 'infrastructure', 'url' => '/solutions'],
                ],
                'solutions_eyebrow' => '解决方案', 'solutions_heading' => '围绕成果组织的工程能力。',
                'solutions_intro' => '每个项目都从业务约束开始，技术选型在诊断之后。',
                'project_eyebrow' => '精选项目', 'project_heading' => '在停机之前学习的产线。',
                'projects_heading' => '以运营成果衡量的工作。',
                'industries_eyebrow' => '行业', 'industries_heading' => '每个行业的约束不同，设计也要随之不同。',
                'industries_intro' => '我们理解每个行业的语境：监管边界、生产季节与利益相关方的真实期望。',
                'stats_eyebrow' => '概览', 'stats_heading' => '为衡量而建。',
                'stats_disclaimer' => '所有数字均为该虚构品牌的示例演示数据，并非实际运营结果。',
                'tech_eyebrow' => '技术', 'tech_heading' => '团队熟悉的平台。',
                'tech_note' => '技术名称仅表示工作熟悉度，不构成供应商合作或认证声明。',
                'insights_eyebrow' => '洞察', 'insights_heading' => '来自工业实践的现场笔记。',
                'cta_eyebrow' => '开始对话', 'cta_heading' => '把必须可靠运行的业务交给我们。',
                'cta_body' => '告诉我们约束在哪里、什么正在变化、什么不能失败。我们会带着清晰观点回来。',
                'cta_primary' => '联系团队', 'cta_secondary' => '下载公司简介',
            ],
        ][$locale];

        $capItems = collect($copy['cap_items'])->map(fn ($item) => [
            'number' => $item['number'],
            'title' => $item['title'],
            'description' => $item['description'],
            'image' => $this->image($item['image'], $locale),
            'url' => $item['url'],
        ])->all();

        return [
            $this->block('hero', [
                'eyebrow' => $copy['eyebrow'],
                'headline' => $copy['headline'],
                'accent_line' => $copy['accent'],
                'body' => $copy['body'],
                'primary_cta' => $this->link($copy['primary'], '/contact'),
                'secondary_cta' => $this->link($copy['secondary'], '/solutions', 'secondary'),
                'image' => $this->image('manufacturing', $locale),
                'layout' => 'panel',
                'show_scroll' => true,
                'meta' => $copy['meta'],
                'theme' => 'dark',
                'align' => 'left',
                'scroll_label' => $copy['scroll'],
            ]),
            $this->block('statement', [
                'eyebrow' => $copy['statement_eyebrow'],
                'heading' => $copy['statement_heading'],
                'body' => $copy['statement_body'],
                'aside' => $copy['statement_aside'],
                'link_label' => $locale === 'th' ? 'แนวทางของเรา' : ($locale === 'en' ? 'Our approach' : '我们的方法'),
                'link_url' => '/about',
                'theme' => 'light',
            ]),
            $this->block('capabilities', [
                'eyebrow' => $copy['cap_eyebrow'],
                'heading' => $copy['cap_heading'],
                'intro' => $copy['cap_intro'],
                'items' => $capItems,
                'theme' => 'light',
            ]),
            $this->block('services_grid', [
                'eyebrow' => $copy['solutions_eyebrow'],
                'heading' => $copy['solutions_heading'],
                'intro' => $copy['solutions_intro'],
                'limit' => 6,
                'layout' => 'editorial',
                'theme' => 'muted',
            ]),
            $this->block('featured_project', [
                'eyebrow' => $copy['project_eyebrow'],
                'heading' => $copy['project_heading'],
                'project_slug' => 'rayong-precision-line',
                'theme' => 'dark',
            ]),
            $this->block('project_grid', [
                'eyebrow' => '',
                'heading' => $copy['projects_heading'],
                'industry' => '',
                'limit' => 3,
                'show_filters' => false,
                'theme' => 'light',
            ]),
            $this->block('industry_grid', [
                'eyebrow' => $copy['industries_eyebrow'],
                'heading' => $copy['industries_heading'],
                'intro' => $copy['industries_intro'],
                'limit' => 5,
                'theme' => 'dark',
            ]),
            $this->block('statistics', [
                'eyebrow' => $copy['stats_eyebrow'],
                'heading' => $copy['stats_heading'],
                'items' => [
                    ['value' => '240+', 'label' => $locale === 'th' ? 'วิศวกรและผู้เชี่ยวชาญ' : ($locale === 'zh-CN' ? '工程师与专业人员' : 'Engineers and specialists'), 'note' => 'Illustrative'],
                    ['value' => '06', 'label' => $locale === 'th' ? 'ประเทศที่ให้บริการ' : ($locale === 'zh-CN' ? '服务国家' : 'Countries served'), 'note' => 'Illustrative'],
                    ['value' => '18', 'label' => $locale === 'th' ? 'ปีของประสบการณ์' : ($locale === 'zh-CN' ? '年实践' : 'Years of practice'), 'note' => 'Illustrative'],
                    ['value' => '99.2%', 'label' => $locale === 'th' ? 'ความพร้อมของระบบสำคัญ' : ($locale === 'zh-CN' ? '关键系统可用率' : 'Critical-system availability'), 'note' => 'Illustrative'],
                ],
                'disclaimer' => $copy['stats_disclaimer'],
                'theme' => 'light',
            ]),
            $this->block('technology_ecosystem', [
                'eyebrow' => $copy['tech_eyebrow'],
                'heading' => $copy['tech_heading'],
                'note' => $copy['tech_note'],
                'items' => [
                    ['name' => 'Siemens', 'category' => 'Automation'],
                    ['name' => 'Rockwell', 'category' => 'Automation'],
                    ['name' => 'Ignition', 'category' => 'SCADA'],
                    ['name' => 'OPC UA', 'category' => 'Standard'],
                    ['name' => 'MQTT', 'category' => 'Standard'],
                    ['name' => 'PostgreSQL', 'category' => 'Data'],
                ],
                'theme' => 'muted',
            ]),
            $this->block('latest_insights', [
                'eyebrow' => $copy['insights_eyebrow'],
                'heading' => $copy['insights_heading'],
                'category' => '',
                'limit' => 3,
                'theme' => 'light',
            ]),
            $this->block('cta', [
                'eyebrow' => $copy['cta_eyebrow'],
                'heading' => $copy['cta_heading'],
                'body' => $copy['cta_body'],
                'primary_cta' => $this->link($copy['cta_primary'], '/contact'),
                'secondary_cta' => $this->link($copy['cta_secondary'], '/documents/meridian-company-profile.pdf', 'secondary'),
                'image' => $this->image('infrastructure', $locale),
                'theme' => 'dark',
            ]),
        ];
    }

    protected function aboutBlocks(string $locale): array
    {
        $copy = [
            'th' => [
                'eyebrow' => 'เกี่ยวกับเมอริเดียน',
                'headline' => 'พันธมิตรวิศวกรรมไทยที่สร้างขึ้นสำหรับทศวรรษของอุตสาหกรรม',
                'body' => 'เราเริ่มจากทีมวิศวกรรมควบคุมและไฟฟ้าขนาดเล็กในกรุงเทพฯ วันนี้เราทำงานข้ามระบบอัตโนมัติ ซอฟต์แวร์ โครงสร้างพื้นฐาน และข้อมูล โดยยังยึดหลักเดิม: งานวิศวกรรมต้องใช้งานได้จริงในกะจริง',
                'cta' => 'พบผู้นำของเรา',
                'values_heading' => 'ความแม่นยำในที่ทำงานจริง',
                'values_body' => 'เราวัดคุณภาพของระบบจากความสามารถของทีมลูกค้าในการดูแลมันหลังส่งมอบ',
                'values' => [
                    ['title' => 'ความแม่นยำที่ไม่ต้องแสดง', 'description' => 'งานที่ดีที่สุดคือระบบที่เงียบ เชื่อถือได้ และอธิบายได้'],
                    ['title' => 'การตัดสินใจที่ตรวจสอบได้', 'description' => 'เราเขียนสมมติฐานและหลักฐานไว้ เพื่อให้การตัดสินใจอยู่ได้นานกว่าตัวบุคคล'],
                    ['title' => 'การถ่ายทอดศักยภาพ', 'description' => 'เราวัดความสำเร็จจากการที่ทีมลูกค้าดูแลระบบได้เอง'],
                    ['title' => 'ความร่วมมือระยะยาว', 'description' => 'เราเลือกความสัมพันธ์ที่ทนต่อการเปลี่ยนแปลง มากกว่ากำไรระยะสั้น'],
                ],
                'timeline_heading' => 'แนวปฏิบัติที่สร้างขึ้นปีแล้วปีเล่า',
                'timeline' => [
                    ['year' => '2009', 'title' => 'ก่อตั้งในกรุงเทพฯ', 'description' => 'ทีมควบคุมและไฟฟ้าขนาดเล็กเริ่มทำงานกับผู้ผลิตไทย'],
                    ['year' => '2014', 'title' => 'โครงการอัตโนมัติแบบบูรณาการ', 'description' => 'ส่งมอบโครงการควบคุมและความปลอดภัยทั้งสายให้ผู้ผลิตอาหารระดับภูมิภาค'],
                    ['year' => '2018', 'title' => 'ศูนย์วิศวกรรมระยอง', 'description' => 'เปิดศูนย์ทดสอบและบูรณาการระบบใกล้พื้นที่ชายฝั่งตะวันออก'],
                    ['year' => '2021', 'title' => 'แนวปฏิบัติระบบดิจิทัล', 'description' => 'รวมซอฟต์แวร์ ข้อมูล และการเชื่อมต่อไว้ในทีมเดียว'],
                    ['year' => '2024', 'title' => 'ความร่วมมือระดับภูมิภาค', 'description' => 'สำนักงานประสานงานสิงคโปร์สนับสนุนโครงการข้ามพรมแดน'],
                    ['year' => '2026', 'title' => 'ห้องปฏิบัติการการผลิตอัจฉริยะ', 'description' => 'เปิดสภาพแวดล้อมสาธิตและทดสอบที่ระยอง'],
                ],
                'team_heading' => 'รับผิดชอบได้ในทุกระดับ',
                'team_intro' => 'ทีมผู้นำตัวอย่างสำหรับแบรนด์สมมติในพอร์ตโฟลิโอ บทบาทและประวัติย่อเป็นข้อมูลสาธิต',
                'docs_heading' => 'ข้อมูลองค์กร',
                'cta_heading' => 'สร้างระบบถัดไปไปกับเรา',
                'cta_body' => 'เรามองหาวิศวกรและผู้จัดการที่ใส่ใจทั้งรายละเอียดทางเทคนิคและผลลัพธ์ที่มันสร้างขึ้น',
            ],
            'en' => [
                'eyebrow' => 'About Meridian',
                'headline' => 'A Thai engineering partner built for an industrial decade.',
                'body' => 'We began as a small controls and electrical team in Bangkok. Today we work across automation, software, infrastructure, and data, with the same discipline: engineering has to work in a real shift.',
                'cta' => 'Meet the leadership',
                'values_heading' => 'What precision looks like here.',
                'values_body' => 'We judge system quality by whether the client team can run the system after handover.',
                'values' => [
                    ['title' => 'Precision without theatre', 'description' => 'The best work is quiet, reliable, and explainable.'],
                    ['title' => 'Traceable decisions', 'description' => 'We write down assumptions and evidence so decisions outlive the people who made them.'],
                    ['title' => 'Capability transfer', 'description' => 'Success is measured by the client team running the system without us.'],
                    ['title' => 'Long-view partnership', 'description' => 'We choose relationships that survive change over short-term margin.'],
                ],
                'timeline_heading' => 'A practice built year by year.',
                'timeline' => [
                    ['year' => '2009', 'title' => 'Founded in Bangkok', 'description' => 'A small controls and electrical engineering team begins working with Thai manufacturers.'],
                    ['year' => '2014', 'title' => 'First integrated automation programme', 'description' => 'A line-wide control and safety programme for a regional food manufacturer.'],
                    ['year' => '2018', 'title' => 'Rayong engineering centre', 'description' => 'A dedicated commissioning and integration facility opens near the Eastern Seaboard.'],
                    ['year' => '2021', 'title' => 'Digital systems practice', 'description' => 'Software, data, and integration capability consolidated under one team.'],
                    ['year' => '2024', 'title' => 'Regional collaboration', 'description' => 'A Singapore liaison office supports cross-border industrial programmes.'],
                    ['year' => '2026', 'title' => 'Smart manufacturing lab', 'description' => 'A demonstration and testing environment opens in Rayong.'],
                ],
                'team_heading' => 'Accountable at every layer.',
                'team_intro' => 'Sample leadership profiles for this fictional portfolio brand. Roles and biographies are demonstration content.',
                'docs_heading' => 'Corporate information',
                'cta_heading' => 'Build the next system with us.',
                'cta_body' => 'We look for engineers and managers who care about the technical detail and the result it produces.',
            ],
            'zh-CN' => [
                'eyebrow' => '关于 Meridian',
                'headline' => '为工业十年而建的泰国工程合作伙伴。',
                'body' => '我们从曼谷一支小型控制与电气团队起步。今天，我们横跨自动化、软件、基础设施与数据，坚守同一原则：工程必须在真实班次中可用。',
                'cta' => '认识领导团队',
                'values_heading' => '精确在实际工作中的样子',
                'values_body' => '我们以客户团队在移交后能否独立运行系统来衡量质量。',
                'values' => [
                    ['title' => '不表演的精确', 'description' => '最好的工作是安静、可靠且可解释的。'],
                    ['title' => '可追溯决策', 'description' => '我们记录假设与证据，让决策比个人更长久。'],
                    ['title' => '能力转移', 'description' => '成功以客户团队能独立运行系统来衡量。'],
                    ['title' => '长期伙伴', 'description' => '我们选择能穿越变化的关系，而非短期利润。'],
                ],
                'timeline_heading' => '一年一年建成的实践。',
                'timeline' => [
                    ['year' => '2009', 'title' => '曼谷创立', 'description' => '小型控制与电气工程团队开始服务泰国制造商。'],
                    ['year' => '2014', 'title' => '首个集成自动化项目', 'description' => '为区域食品制造商交付整线控制与安全项目。'],
                    ['year' => '2018', 'title' => '罗勇工程中心', 'description' => '在东部经济走廊附近设立调试与集成中心。'],
                    ['year' => '2021', 'title' => '数字系统业务', 'description' => '软件、数据与集成能力整合为一个团队。'],
                    ['year' => '2024', 'title' => '区域协作', 'description' => '新加坡联络处支持跨境工业项目。'],
                    ['year' => '2026', 'title' => '智能制造实验室', 'description' => '在罗勇设立演示与测试环境。'],
                ],
                'team_heading' => '每一层都可问责。',
                'team_intro' => '该虚构品牌的示例领导团队。角色与简介为演示内容。',
                'docs_heading' => '企业信息',
                'cta_heading' => '与我们共建下一个系统。',
                'cta_body' => '我们寻找既在意技术细节、也在意它带来什么结果的工程师与管理者。',
            ],
        ][$locale];

        return [
            $this->block('split_hero', [
                'eyebrow' => $copy['eyebrow'],
                'headline' => $copy['headline'],
                'body' => $copy['body'],
                'primary_cta' => $this->link($copy['cta'], '/about#leadership'),
                'image' => $this->image('automation', $locale),
                'reverse' => false,
                'theme' => 'dark',
            ]),
            $this->block('mission_values', [
                'eyebrow' => $locale === 'th' ? 'สิ่งที่นำทางเรา' : ($locale === 'zh-CN' ? '我们的准则' : 'What guides us'),
                'heading' => $copy['values_heading'],
                'body' => $copy['values_body'],
                'items' => $copy['values'],
                'theme' => 'light',
            ]),
            $this->block('statistics', [
                'eyebrow' => $locale === 'th' ? 'ภาพรวม' : ($locale === 'zh-CN' ? '概览' : 'At a glance'),
                'heading' => $locale === 'th' ? 'ประสบการณ์ที่วัดได้' : ($locale === 'zh-CN' ? '可衡量的经验' : 'Experience that can be measured.'),
                'items' => [
                    ['value' => '2009', 'label' => $locale === 'th' ? 'ปีที่ก่อตั้ง' : ($locale === 'zh-CN' ? '成立年份' : 'Founded'), 'note' => 'Illustrative'],
                    ['value' => '240+', 'label' => $locale === 'th' ? 'ผู้เชี่ยวชาญ' : ($locale === 'zh-CN' ? '专业人员' : 'Specialists'), 'note' => 'Illustrative'],
                    ['value' => '03', 'label' => $locale === 'th' ? 'สำนักงาน' : ($locale === 'zh-CN' ? '办公室' : 'Offices'), 'note' => 'Illustrative'],
                    ['value' => '06', 'label' => $locale === 'th' ? 'ประเทศที่ให้บริการ' : ($locale === 'zh-CN' ? '服务国家' : 'Countries'), 'note' => 'Illustrative'],
                ],
                'disclaimer' => $locale === 'th' ? 'ตัวเลขเป็นข้อมูลตัวอย่างสำหรับแบรนด์สมมติ' : ($locale === 'zh-CN' ? '数字为该虚构品牌的示例数据。' : 'Figures are illustrative data for this fictional brand.'),
                'theme' => 'muted',
            ]),
            $this->block('timeline', [
                'eyebrow' => $locale === 'th' ? 'เส้นทาง' : ($locale === 'zh-CN' ? '历程' : 'Milestones'),
                'heading' => $copy['timeline_heading'],
                'items' => $copy['timeline'],
                'theme' => 'light',
            ]),
            $this->block('team_grid', [
                'eyebrow' => $locale === 'th' ? 'ผู้นำ' : ($locale === 'zh-CN' ? '领导团队' : 'Leadership'),
                'heading' => $copy['team_heading'],
                'intro' => $copy['team_intro'],
                'leadership_only' => true,
                'limit' => 8,
                'theme' => 'dark',
            ]),
            $this->block('document_list', [
                'eyebrow' => $locale === 'th' ? 'เอกสาร' : ($locale === 'zh-CN' ? '文件' : 'Corporate'),
                'heading' => $copy['docs_heading'],
                'category' => 'corporate',
                'limit' => 4,
                'theme' => 'light',
            ]),
            $this->block('cta', [
                'eyebrow' => $locale === 'th' ? 'ร่วมงานกับเรา' : ($locale === 'zh-CN' ? '加入我们' : 'Careers'),
                'heading' => $copy['cta_heading'],
                'body' => $copy['cta_body'],
                'primary_cta' => $this->link($locale === 'th' ? 'ดูตำแหน่งงาน' : ($locale === 'zh-CN' ? '查看职位' : 'View open roles'), '/careers'),
                'secondary_cta' => $this->link($locale === 'th' ? 'ติดต่อเรา' : ($locale === 'zh-CN' ? '联系我们' : 'Contact us'), '/contact', 'secondary'),
                'image' => $this->image('engineering-team', $locale) ?? $this->image('precision', $locale),
                'theme' => 'dark',
            ]),
        ];
    }

    protected function contactBlocks(string $locale): array
    {
        $copy = [
            'th' => [
                'eyebrow' => 'ติดต่อ',
                'headline' => 'คุยกับทีมที่ใกล้ชิดโจทย์ของคุณ',
                'body' => 'ไม่ว่าคุณกำลังวางแผนระบบใหม่ กำลังเผชิญข้อจำกัดในการผลิต หรือมองหาความร่วมมือระยะยาว เราจะเชื่อมคุณกับคนที่เข้าใจงานนั้นจริง',
                'offices_heading' => 'ใกล้ชิดกับการดำเนินงาน',
                'form_heading' => 'บอกเราว่าคุณกำลังสร้างอะไร',
                'form_body' => 'กรอกข้อมูลด้านล่าง ทีมธุรกิจจะตอบกลับภายในสองวันทำการ ข้อมูลส่วนบุคคลถูกใช้เพื่อตอบข้อสอบถามนี้เท่านั้น',
            ],
            'en' => [
                'eyebrow' => 'Contact',
                'headline' => 'Talk to the team closest to the problem.',
                'body' => 'Whether you are planning a new system, facing a production constraint, or exploring a long-term partnership, we will connect you with someone who understands the work.',
                'offices_heading' => 'Close to the operation.',
                'form_heading' => 'Tell us what you are building.',
                'form_body' => 'Complete the form and our business team will respond within two working days. Your personal data is used only to respond to this enquiry.',
            ],
            'zh-CN' => [
                'eyebrow' => '联系',
                'headline' => '与最贴近问题的团队沟通。',
                'body' => '无论您正在规划新系统、面对生产约束，还是探索长期合作，我们都会为您连接真正理解这项工作的人。',
                'offices_heading' => '贴近运营。',
                'form_heading' => '告诉我们您正在建设什么。',
                'form_body' => '填写下方表单，业务团队将在两个工作日内回复。您的个人数据仅用于回复本次咨询。',
            ],
        ][$locale];

        return [
            $this->block('split_hero', [
                'eyebrow' => $copy['eyebrow'],
                'headline' => $copy['headline'],
                'body' => $copy['body'],
                'primary_cta' => $this->link($locale === 'th' ? 'โทรหาเรา' : ($locale === 'zh-CN' ? '致电我们' : 'Call us'), 'tel:+6620000000'),
                'image' => $this->image('energy', $locale),
                'reverse' => true,
                'theme' => 'light',
            ]),
            $this->block('contact_cards', [
                'eyebrow' => $locale === 'th' ? 'สำนักงาน' : ($locale === 'zh-CN' ? '办公室' : 'Offices'),
                'heading' => $copy['offices_heading'],
                'items' => [
                    ['city' => 'Bangkok', 'address' => 'Rama IV Road, Khlong Toei, Bangkok 10110', 'phone' => '+66 2 000 0000', 'email' => 'hello@meridian.example', 'label' => $locale === 'th' ? 'สำนักงานใหญ่' : ($locale === 'zh-CN' ? '总部' : 'Head office')],
                    ['city' => 'Rayong', 'address' => 'Hemaraj Eastern Seaboard Industrial Estate, Pluak Daeng, Rayong 21140', 'phone' => '+66 38 000 000', 'email' => 'rayong@meridian.example', 'label' => $locale === 'th' ? 'ศูนย์วิศวกรรม' : ($locale === 'zh-CN' ? '工程中心' : 'Engineering centre')],
                    ['city' => 'Singapore', 'address' => 'Anson Road, Tanjong Pagar, Singapore', 'phone' => '+65 6000 0000', 'email' => 'sea@meridian.example', 'label' => $locale === 'th' ? 'สำนักงานภูมิภาค' : ($locale === 'zh-CN' ? '区域联络处' : 'Regional liaison')],
                ],
                'theme' => 'muted',
            ]),
            $this->block('contact_form', [
                'eyebrow' => $locale === 'th' ? 'แบบฟอร์มติดต่อ' : ($locale === 'zh-CN' ? '联系表单' : 'Enquiry'),
                'heading' => $copy['form_heading'],
                'body' => $copy['form_body'],
                'form_key' => 'contact',
                'departments' => [
                    ['name' => $locale === 'th' ? 'พัฒนาธุรกิจ' : ($locale === 'zh-CN' ? '业务发展' : 'Business development'), 'email' => 'hello@meridian.example', 'description' => $locale === 'th' ? 'โครงการใหม่และความร่วมมือ' : ($locale === 'zh-CN' ? '新项目与合作' : 'New projects and partnerships')],
                    ['name' => $locale === 'th' ? 'ทรัพยากรบุคคล' : ($locale === 'zh-CN' ? '人才招聘' : 'Careers'), 'email' => 'careers@meridian.example', 'description' => $locale === 'th' ? 'ตำแหน่งงานและการสมัคร' : ($locale === 'zh-CN' ? '职位与申请' : 'Roles and applications')],
                ],
                'theme' => 'light',
            ]),
        ];
    }

    protected function investorBlocks(string $locale): array
    {
        $copy = [
            'th' => [
                'eyebrow' => 'นักลงทุนสัมพันธ์',
                'headline' => 'ธุรกิจอุตสาหกรรมที่มองระยะยาว',
                'body' => 'หน้านี้เป็นตัวอย่างประสบการณ์นักลงทุนสัมพันธ์สำหรับบริษัทสมมติ ตัวเลขและเอกสารทั้งหมดเป็นข้อมูลสาธิต ไม่ใช่รายงานทางการเงินที่ตรวจสอบแล้ว และไม่ใช่ข้อเสนอซื้อขายหลักทรัพย์',
                'cta' => 'พูดคุยกับทีมสื่อสารองค์กร',
            ],
            'en' => [
                'eyebrow' => 'Investor relations',
                'headline' => 'A long-view industrial business.',
                'body' => 'This page demonstrates an investor-relations experience for a fictional private company. All figures and documents are illustrative demonstration data, not audited statements or an offer of securities.',
                'cta' => 'Speak with corporate affairs',
            ],
        ][$locale];

        return [
            $this->block('split_hero', [
                'eyebrow' => $copy['eyebrow'],
                'headline' => $copy['headline'],
                'body' => $copy['body'],
                'primary_cta' => $this->link($copy['cta'], '/contact'),
                'image' => $this->image('infrastructure', $locale),
                'reverse' => false,
                'theme' => 'dark',
            ]),
            $this->block('investor_highlights', [
                'eyebrow' => $locale === 'th' ? 'ภาพรวมการเงิน' : 'Financial highlights',
                'heading' => $locale === 'th' ? 'ภาพรวมผลประกอบการเพื่อการสาธิต' : 'Illustrative performance overview.',
                'note' => $locale === 'th'
                    ? 'ตัวเลขทั้งหมดในหน้านี้เป็นข้อมูลตัวอย่างสำหรับบริษัทสมมติ ไม่ใช่รายงานการเงินที่ตรวจสอบแล้ว และไม่ควรใช้เพื่อการตัดสินใจลงทุน'
                    : 'All figures on this page are illustrative demonstration data for a fictional private company. They are not audited financial statements and must not be used for investment decisions.',
                'years' => 3,
                'theme' => 'light',
            ]),
            $this->block('document_list', [
                'eyebrow' => $locale === 'th' ? 'เอกสาร' : 'Documents',
                'heading' => $locale === 'th' ? 'เอกสารองค์กรและธรรมาภิบาล' : 'Corporate and governance documents.',
                'category' => '',
                'limit' => 12,
                'theme' => 'muted',
            ]),
            $this->block('cta', [
                'eyebrow' => $locale === 'th' ? 'สอบถาม' : 'Enquiries',
                'heading' => $locale === 'th' ? 'ต้องการข้อมูลเพิ่มเติมหรือไม่' : 'Need a document or a conversation?',
                'body' => $locale === 'th'
                    ? 'ทีมสื่อสารองค์กรยินดีให้ข้อมูลเกี่ยวกับเนื้อหาสาธิตนี้ และจะระบุชัดเจนว่าสิ่งใดเป็นข้อมูลตัวอย่าง'
                    : 'Our corporate affairs team can explain the demonstration content and will be explicit about what is illustrative.',
                'primary_cta' => $this->link($locale === 'th' ? 'ติดต่อทีมสื่อสาร' : 'Contact corporate affairs', '/contact'),
                'secondary_cta' => null,
                'image' => null,
                'theme' => 'dark',
            ]),
        ];
    }

    protected function careersBlocks(string $locale): array
    {
        $copy = [
            'th' => [
                'eyebrow' => 'ร่วมงานกับเรา',
                'headline' => 'สร้างระบบที่คนอื่นต้องพึ่งพา',
                'body' => 'งานของเราทางเทคนิค และผลลัพธ์ปรากฏที่หน้างาน ถ้าคุณชอบแก้ปัญหาจริง ลงพื้นที่ และเรียนรู้จากคนที่เก่งที่สุดในงานของเขา นี่คือที่ที่คุณจะเติบโต',
                'culture_heading' => 'วัฒนธรรมการทำงานที่ให้ความสำคัญกับงานฝีมือ',
                'culture_body' => '<h2>ลงมือทำจริง</h2><p>เราเชื่อว่าวิศวกรที่ดีต้องเห็นหน้างาน เข้าใจผู้ปฏิบัติงาน และยอมรับว่าความจริงมักไม่ตรงกับแบบ</p><h2>ความชัดเจนคือความเมตตา</h2><p>เราเขียนสมมติฐาน การตัดสินใจ และความเสี่ยงให้ชัดเจน เพราะทีมที่ดีที่สุดไม่ต้องเดา</p><h2>เรียนรู้ตลอดชีวิต</h2><p>เรามีระบบพี่เลี้ยง งบการเรียนรู้ และเวลาให้ทดลองสิ่งใหม่โดยไม่ลงโทษความล้มเหลวที่ดี</p>',
                'values_heading' => 'สิ่งที่เรามอบให้',
                'values' => [
                    ['title' => 'พี่เลี้ยงทางเทคนิค', 'description' => 'ทำงานร่วมกับวิศวกรอาวุโสในโครงการจริง ไม่ใช่แค่ในห้องเรียน'],
                    ['title' => 'ความสมดุลที่ยั่งยืน', 'description' => 'เราวางแผนงานให้คุณภาพชีวิตและคุณภาพงานไปด้วยกัน'],
                    ['title' => 'เส้นทางที่ชัดเจน', 'description' => 'กรอบสมรรถนะและบทสนทนาความก้าวหน้าที่ตรงไปตรงมา'],
                    ['title' => 'ผลกระทบที่มองเห็น', 'description' => 'งานของคุณทำให้โรงงาน ระบบน้ำ หรือโครงข่ายพลังงานทำงานได้ดีขึ้น'],
                ],
                'jobs_heading' => 'ตำแหน่งที่เปิดอยู่',
                'cta_heading' => 'ไม่พบตำแหน่งที่ตรง?',
                'cta_body' => 'แนะนำตัวกับเราได้เสมอ เราอ่านทุกข้อความและจะติดต่อกลับเมื่อมีโอกาสที่เหมาะสม',
            ],
            'en' => [
                'eyebrow' => 'Careers',
                'headline' => 'Build systems other people depend on.',
                'body' => 'Our work is technical, and the result shows up on the floor. If you like real problems, site time, and learning from people who are excellent at their craft, this is a place to grow.',
                'culture_heading' => 'A working culture that values craft.',
                'culture_body' => '<h2>Do the work</h2><p>We believe good engineers see the site, understand the operator, and accept that reality rarely matches the drawing.</p><h2>Clarity is a kindness</h2><p>We write down assumptions, decisions, and risks because the best teams should not have to guess.</p><h2>Learn continuously</h2><p>We provide mentoring, a learning budget, and room to try new things without punishing good failure.</p>',
                'values_heading' => 'What we offer',
                'values' => [
                    ['title' => 'Technical mentoring', 'description' => 'Work alongside senior engineers on real projects, not only in a classroom.'],
                    ['title' => 'Sustainable balance', 'description' => 'We plan work so quality of life and quality of work reinforce each other.'],
                    ['title' => 'A visible path', 'description' => 'Competency frameworks and honest career conversations.'],
                    ['title' => 'Visible impact', 'description' => 'Your work helps a factory, water network, or energy system run better.'],
                ],
                'jobs_heading' => 'Open roles',
                'cta_heading' => 'No matching role?',
                'cta_body' => 'Introduce yourself anyway. We read every message and will get in touch when there is a good fit.',
            ],
        ][$locale];

        return [
            $this->block('split_hero', [
                'eyebrow' => $copy['eyebrow'],
                'headline' => $copy['headline'],
                'body' => $copy['body'],
                'primary_cta' => $this->link($locale === 'th' ? 'ดูตำแหน่งงาน' : 'View open roles', '#open-roles'),
                'image' => $this->image('engineering-team', $locale) ?? $this->image('manufacturing', $locale),
                'reverse' => false,
                'theme' => 'dark',
            ]),
            $this->block('rich_text', [
                'eyebrow' => '',
                'heading' => $copy['culture_heading'],
                'body' => $copy['culture_body'],
                'width' => 'standard',
                'theme' => 'light',
            ]),
            $this->block('mission_values', [
                'eyebrow' => $locale === 'th' ? 'ผลตอบแทน' : 'Benefits',
                'heading' => $copy['values_heading'],
                'body' => '',
                'items' => $copy['values'],
                'theme' => 'muted',
            ]),
            $this->block('job_list', [
                'eyebrow' => $locale === 'th' ? 'โอกาส' : 'Opportunities',
                'heading' => $copy['jobs_heading'],
                'department' => '',
                'limit' => 20,
                'theme' => 'light',
            ]),
            $this->block('cta', [
                'eyebrow' => $locale === 'th' ? 'แนะนำตัว' : 'Introduce yourself',
                'heading' => $copy['cta_heading'],
                'body' => $copy['cta_body'],
                'primary_cta' => $this->link($locale === 'th' ? 'ติดต่อฝ่ายบุคคล' : 'Contact people team', '/contact'),
                'secondary_cta' => null,
                'image' => null,
                'theme' => 'dark',
            ]),
        ];
    }

    protected function legalBlocks(string $locale, string $key): array
    {
        $content = [
            'privacy' => [
                'th' => '<p class="legal-notice">ข้อความตัวอย่างสำหรับการสาธิต ไม่ใช่คำปรึกษาทางกฎหมาย และต้องผ่านการทบทวนก่อนใช้งานจริง</p><h2>ข้อมูลที่เราเก็บ</h2><p>เว็บไซต์นี้อาจเก็บข้อมูลที่คุณส่งผ่านแบบฟอร์มติดต่อ ได้แก่ ชื่อ อีเมล เบอร์โทร บริษัท และข้อความ รวมถึงข้อมูลทางเทคนิคที่จำเป็นต่อความปลอดภัยของระบบ</p><h2>วัตถุประสงค์</h2><p>เราใช้ข้อมูลเพื่อตอบข้อสอบถาม ดำเนินการสรรหา และปรับปรุงประสบการณ์ของเว็บไซต์ เราไม่ขายข้อมูลส่วนบุคคล</p><h2>ระยะเวลาเก็บรักษา</h2><p>ข้อมูลข้อสอบถามเก็บไม่เกิน 24 เดือนหลังการติดต่อครั้งล่าสุด เว้นแต่มีข้อผูกพันทางกฎหมายหรือสัญญาที่กำหนดไว้เป็นอย่างอื่น</p><h2>สิทธิของคุณ</h2><p>คุณอาจขอเข้าถึง แก้ไข หรือลบข้อมูลส่วนบุคคลของคุณได้ผ่าน hello@meridian.example</p>',
                'en' => '<p class="legal-notice">Sample copy for demonstration. This is not legal advice and must be reviewed before production use.</p><h2>Information we collect</h2><p>This website may collect information you submit through enquiry forms, including your name, email, phone, company, and message, plus technical information needed to secure the service.</p><h2>How we use it</h2><p>We use enquiry data to respond, manage recruitment, and improve the website experience. We do not sell personal data.</p><h2>Retention</h2><p>Enquiry data is retained for no more than 24 months after the last contact unless a legal or contractual obligation requires otherwise.</p><h2>Your rights</h2><p>You may request access, correction, or deletion by writing to hello@meridian.example.</p>',
            ],
            'cookie' => [
                'th' => '<p class="legal-notice">ข้อความตัวอย่างสำหรับการสาธิต ต้องผ่านการทบทวนก่อนใช้งานจริง</p><h2>คุกกี้ที่จำเป็น</h2><p>เราใช้คุกกี้ที่จำเป็นสำหรับความปลอดภัย การเข้าสู่ระบบของทีมจัดการเนื้อหา และการจดจำการตั้งค่าพื้นฐาน</p><h2>การวิเคราะห์</h2><p>เหตุการณ์วิเคราะห์ของบุคคลที่หนึ่งจะถูกบันทึกเมื่อคุณให้ความยินยอมเท่านั้น และไม่ใช้เพื่อการโฆษณาแบบติดตามข้ามเว็บไซต์</p><h2>การจัดการ</h2><p>คุณสามารถล้างคุกกี้ผ่านการตั้งค่าเบราว์เซอร์ และเลือกไม่ให้ความยินยอมต่อการวิเคราะห์ได้โดยไม่กระทบการใช้งานหลักของเว็บไซต์</p>',
                'en' => '<p class="legal-notice">Sample copy for demonstration. Requires review before production use.</p><h2>Essential cookies</h2><p>We use essential cookies for security, CMS sign-in sessions, and remembering basic preferences.</p><h2>Analytics</h2><p>First-party analytics events are recorded only when you consent, and are not used for cross-site advertising.</p><h2>Managing cookies</h2><p>You can clear cookies through your browser settings and decline analytics consent without losing access to the core website.</p>',
            ],
            'terms' => [
                'th' => '<p class="legal-notice">ข้อความตัวอย่างสำหรับการสาธิต ต้องผ่านการทบทวนก่อนใช้งานจริง</p><h2>การใช้เว็บไซต์</h2><p>เนื้อหาบนเว็บไซต์นี้จัดทำขึ้นเพื่อการสาธิตแบรนด์สมมติ คุณต้องไม่ใช้เว็บไซต์ในทางที่ผิดกฎหมายหรือรบกวนการทำงานของระบบ</p><h2>ทรัพย์สินทางปัญญา</h2><p>เครื่องหมายการค้า ข้อความ และการออกแบบเป็นตัวอย่างสำหรับพอร์ตโฟลิโอ รูปภาพบางส่วนมาจาก Wikimedia Commons ภายใต้สัญญาอนุญาตที่ระบุไว้ในหน้าเครดิต</p><h2>ความรับผิด</h2><p>เนื้อหา ตัวเลข และผลลัพธ์ทั้งหมดเป็นข้อมูลตัวอย่าง ไม่มีการรับประกันความถูกต้องหรือความเหมาะสมสำหรับการตัดสินใจทางธุรกิจ</p><h2>การเปลี่ยนแปลง</h2><p>เราอาจปรับปรุงข้อกำหนดเหล่านี้เมื่อโครงสร้างการสาธิตเปลี่ยนไป</p>',
                'en' => '<p class="legal-notice">Sample copy for demonstration. Requires review before production use.</p><h2>Using this website</h2><p>This website is a demonstration for a fictional brand. You must not use it unlawfully or interfere with its operation.</p><h2>Intellectual property</h2><p>Brand marks, copy, and design are portfolio samples. Some photography is sourced from Wikimedia Commons under the licences listed on the credits page.</p><h2>Liability</h2><p>All content, figures, and outcomes are illustrative. No warranty is made about their accuracy or suitability for business decisions.</p><h2>Changes</h2><p>We may update these terms as the demonstration evolves.</p>',
            ],
            'data' => [
                'th' => '<p class="legal-notice">ข้อความตัวอย่างสำหรับการสาธิต ต้องผ่านการทบทวนทางกฎหมายก่อนใช้งานจริง</p><h2>หลักการ</h2><p>เราเก็บข้อมูลส่วนบุคคลเท่าที่จำเป็นสำหรับวัตถุประสงค์ที่ระบุไว้ และจำกัดการเข้าถึงตามบทบาทหน้าที่</p><h2>ฐานทางกฎหมาย</h2><p>การประมวลผลอาศัยความยินยอมสำหรับการวิเคราะห์ ความจำเป็นเพื่อตอบข้อสอบถาม และความจำเป็นตามสัญญาสำหรับการสรรหา</p><h2>ความปลอดภัย</h2><p>เราใช้การควบคุมการเข้าถึง การเข้ารหัสข้อมูลลับ บันทึกการตรวจสอบ และการจำกัดอัตราการเรียกใช้ระบบ</p><h2>การถ่ายโอน</h2><p>หากมีการใช้ผู้ให้บริการภายนอก เราจะกำหนดข้อผูกพันด้านการคุ้มครองข้อมูลและจำกัดการถ่ายโอนข้ามพรมแดนตามความเหมาะสม</p><h2>ติดต่อ</h2><p>คำขอเกี่ยวกับข้อมูลส่วนบุคคลสามารถส่งถึง hello@meridian.example</p>',
                'en' => '<p class="legal-notice">Sample copy for demonstration. Requires legal review before production use.</p><h2>Principles</h2><p>We collect only the personal data needed for stated purposes and restrict access by role.</p><h2>Lawful basis</h2><p>Processing relies on consent for analytics, necessity to respond to enquiries, and contract necessity for recruitment.</p><h2>Security</h2><p>We use access controls, encryption of stored secrets, audit logging, and rate limiting.</p><h2>Transfers</h2><p>Where service providers are used, we impose data protection obligations and limit cross-border transfers as appropriate.</p><h2>Contact</h2><p>Data subject requests can be sent to hello@meridian.example.</p>',
            ],
        ][$key][$locale];

        $title = match ($key) {
            'privacy' => $locale === 'th' ? 'นโยบายความเป็นส่วนตัว' : 'Privacy policy',
            'cookie' => $locale === 'th' ? 'นโยบายคุกกี้' : 'Cookie policy',
            'terms' => $locale === 'th' ? 'ข้อกำหนดการใช้งาน' : 'Terms of use',
            default => $locale === 'th' ? 'การคุ้มครองข้อมูลส่วนบุคคล' : 'Data protection',
        };

        return [
            $this->block('statement', [
                'eyebrow' => $locale === 'th' ? 'เอกสารตัวอย่าง' : 'Sample document',
                'heading' => $locale === 'th' ? 'ข้อความทางกฎหมายเพื่อการสาธิต' : 'Sample legal copy for a demonstration.',
                'body' => $locale === 'th'
                    ? 'เอกสารนี้จัดทำขึ้นเพื่อแสดงความสามารถของระบบจัดการเนื้อหา ไม่ใช่คำปรึกษาทางกฎหมาย และต้องได้รับการทบทวนโดยผู้เชี่ยวชาญก่อนนำไปใช้จริง'
                    : 'This document demonstrates the content management system. It is not legal advice and must be reviewed by a qualified professional before real-world use.',
                'aside' => '',
                'link_label' => '',
                'link_url' => '',
                'theme' => 'dark',
            ]),
            $this->block('rich_text', [
                'eyebrow' => '',
                'heading' => $title,
                'body' => $content,
                'width' => 'narrow',
                'theme' => 'light',
            ]),
        ];
    }

    protected function legalThaiSlug(string $slug): string
    {
        return match ($slug) {
            'privacy-policy' => 'นโยบายความเป็นส่วนตัว',
            'cookie-policy' => 'นโยบายคุกกี้',
            'terms-of-use' => 'ข้อกำหนดการใช้งาน',
            'data-protection' => 'การคุ้มครองข้อมูลส่วนบุคคล',
            default => $slug,
        };
    }
}
