<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\FinancialHighlight;
use App\Models\MediaAsset;
use App\Models\Milestone;
use App\Models\Site;
use App\Models\TeamMember;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $site = Site::where('slug', 'meridian')->firstOrFail();
        $media = MediaAsset::where('site_id', $site->id)->get()->keyBy(fn (MediaAsset $asset) => $asset->original_name);

        $team = [
            ['name' => 'Anan Srisuk', 'department' => 'Executive', 'sort' => 1, 'role' => ['en' => 'Chief Executive Officer', 'th' => 'ประธานเจ้าหน้าที่บริหาร', 'zh-CN' => '首席执行官'], 'bio' => ['en' => 'Anan leads Meridian\'s operating agenda and its long-term partnership model. He has spent two decades connecting industrial operations with engineering delivery.', 'th' => 'อานันนำวาระการดำเนินงานและโมเดลความร่วมมือระยะยาวของเมอริเดียน เขามีประสบการณ์สองทศวรรษในการเชื่อมการปฏิบัติงานอุตสาหกรรมกับการส่งมอบงานวิศวกรรม', 'zh-CN' => 'Anan 负责 Meridian 的运营议程与长期合作模式，拥有二十年将工业运营与工程交付连接起来的经验。']],
            ['name' => 'Pimchanok Rattanaporn', 'department' => 'Executive', 'sort' => 2, 'role' => ['en' => 'Chief Operating Officer', 'th' => 'ประธานเจ้าหน้าที่ฝ่ายปฏิบัติการ', 'zh-CN' => '首席运营官'], 'bio' => ['en' => 'Pimchanok owns delivery quality, commercial discipline, and the systems that let distributed engineering teams work as one practice.', 'th' => 'พิมพ์ชนกดูแลคุณภาพการส่งมอบ วินัยทางการค้า และระบบที่ทำให้ทีมวิศวกรรมกระจายตัวทำงานเป็นแนวปฏิบัติเดียว', 'zh-CN' => 'Pimchanok 负责交付质量、商业纪律，以及让分布式工程团队协同工作的体系。']],
            ['name' => 'Daniel Lim', 'department' => 'Technology', 'sort' => 3, 'role' => ['en' => 'Chief Technology Officer', 'th' => 'ประธานเจ้าหน้าที่ฝ่ายเทคโนโลยี', 'zh-CN' => '首席技术官'], 'bio' => ['en' => 'Daniel sets the technical architecture for automation, integration, and operational data. He is accountable for engineering standards across the group.', 'th' => 'แดเนียลกำหนดสถาปัตยกรรมทางเทคนิคสำหรับระบบอัตโนมัติ การเชื่อมต่อ และข้อมูลปฏิบัติการ และรับผิดชอบมาตรฐานวิศวกรรมของกลุ่ม', 'zh-CN' => 'Daniel 负责自动化、集成与运营数据的技术架构，并对集团工程标准负责。']],
            ['name' => 'Nattaya Boonsong', 'department' => 'Industrial automation', 'sort' => 4, 'role' => ['en' => 'Director, Industrial Automation', 'th' => 'ผู้อำนวยการระบบอัตโนมัติอุตสาหกรรม', 'zh-CN' => '工业自动化总监'], 'bio' => ['en' => 'Nattaya leads control and robotics delivery, with a focus on brownfield upgrades that keep production running.', 'th' => 'ณัฐญานำการส่งมอบระบบควบคุมและหุ่นยนต์ โดยเน้นการอัปเกรดโรงงานเดิมที่ต้องรักษาการผลิตให้เดินต่อ', 'zh-CN' => 'Nattaya 负责控制与机器人交付，专注于保持生产运行的棕地升级。']],
            ['name' => 'Marcus Chen', 'department' => 'Digital systems', 'sort' => 5, 'role' => ['en' => 'Director, Digital Systems', 'th' => 'ผู้อำนวยการระบบดิจิทัล', 'zh-CN' => '数字系统总监'], 'bio' => ['en' => 'Marcus brings software, integration, and data engineering into one practice, with an emphasis on operator adoption.', 'th' => 'มาร์คัสรวมซอฟต์แวร์ การเชื่อมต่อ และวิศวกรรมข้อมูลไว้ในแนวปฏิบัติเดียว โดยเน้นการยอมรับของผู้ปฏิบัติงาน', 'zh-CN' => 'Marcus 将软件、集成与数据工程整合为一个团队，强调操作员采用。']],
            ['name' => 'Siriporn Khamdee', 'department' => 'People & culture', 'sort' => 6, 'role' => ['en' => 'Head of People & Culture', 'th' => 'หัวหน้าฝ่ายบุคลากรและวัฒนธรรม', 'zh-CN' => '人才与文化负责人'], 'bio' => ['en' => 'Siriporn builds the apprenticeship, safety, and capability programmes that let engineers grow without losing field discipline.', 'th' => 'ศิริพรสร้างโครงการฝึกงาน ความปลอดภัย และการพัฒนาศักยภาพที่ทำให้วิศวกรเติบโตโดยไม่สูญเสียวินัยหน้างาน', 'zh-CN' => 'Siriporn 建设学徒、安全与能力发展项目，让工程师在不丢失现场纪律的前提下成长。']],
        ];

        foreach ($team as $member) {
            TeamMember::updateOrCreate(
                ['site_id' => $site->id, 'name' => $member['name']],
                [
                    'role' => $member['role'],
                    'department' => $member['department'],
                    'bio' => $member['bio'],
                    'photo_media_id' => null,
                    'is_leadership' => true,
                    'sort' => $member['sort'],
                    'social' => [],
                ]
            );
        }

        $milestones = [
            ['year' => 2009, 'sort' => 1, 'title' => ['en' => 'Practice founded in Bangkok', 'th' => 'ก่อตั้งแนวปฏิบัติในกรุงเทพฯ', 'zh-CN' => '在曼谷创立'], 'description' => ['en' => 'A small controls and electrical engineering team begins working with Thai manufacturers.', 'th' => 'ทีมวิศวกรรมควบคุมและไฟฟ้าขนาดเล็กเริ่มทำงานกับผู้ผลิตไทย', 'zh-CN' => '一支小型控制与电气工程团队开始服务泰国制造商。']],
            ['year' => 2014, 'sort' => 2, 'title' => ['en' => 'First integrated automation programme', 'th' => 'โครงการระบบอัตโนมัติแบบบูรณาการครั้งแรก', 'zh-CN' => '首个集成自动化项目'], 'description' => ['en' => 'The team delivers a line-wide control and safety programme for a regional food manufacturer.', 'th' => 'ทีมส่งมอบโครงการควบคุมและความปลอดภัยทั้งสายให้ผู้ผลิตอาหารระดับภูมิภาค', 'zh-CN' => '团队为区域食品制造商交付整线控制与安全项目。']],
            ['year' => 2018, 'sort' => 3, 'title' => ['en' => 'Rayong engineering centre', 'th' => 'ศูนย์วิศวกรรมระยอง', 'zh-CN' => '罗勇工程中心'], 'description' => ['en' => 'A dedicated commissioning and integration facility opens near the Eastern Seaboard.', 'th' => 'เปิดศูนย์ทดสอบและบูรณาการระบบใกล้พื้นที่ชายฝั่งตะวันออก', 'zh-CN' => '在东部经济走廊附近设立调试与集成中心。']],
            ['year' => 2021, 'sort' => 4, 'title' => ['en' => 'Digital systems practice', 'th' => 'แนวปฏิบัติระบบดิจิทัล', 'zh-CN' => '数字系统业务'], 'description' => ['en' => 'Software, data, and integration capability is consolidated under one practice.', 'th' => 'รวมศักยภาพด้านซอฟต์แวร์ ข้อมูล และการเชื่อมต่อไว้ในแนวปฏิบัติเดียว', 'zh-CN' => '软件、数据与集成能力整合为一个业务。']],
            ['year' => 2024, 'sort' => 5, 'title' => ['en' => 'Regional collaboration', 'th' => 'ความร่วมมือระดับภูมิภาค', 'zh-CN' => '区域协作'], 'description' => ['en' => 'A Singapore liaison office supports cross-border industrial programmes.', 'th' => 'สำนักงานประสานงานสิงคโปร์สนับสนุนโครงการอุตสาหกรรมข้ามพรมแดน', 'zh-CN' => '新加坡联络处支持跨境工业项目。']],
            ['year' => 2026, 'sort' => 6, 'title' => ['en' => 'Smart manufacturing lab', 'th' => 'ห้องปฏิบัติการการผลิตอัจฉริยะ', 'zh-CN' => '智能制造实验室'], 'description' => ['en' => 'A demonstration and testing environment opens in Rayong for automation and data projects.', 'th' => 'เปิดสภาพแวดล้อมสาธิตและทดสอบในระยองสำหรับโครงการระบบอัตโนมัติและข้อมูล', 'zh-CN' => '在罗勇设立面向自动化与数据项目的演示与测试环境。']],
        ];

        foreach ($milestones as $milestone) {
            Milestone::updateOrCreate(
                ['site_id' => $site->id, 'year' => $milestone['year']],
                ['title' => $milestone['title'], 'description' => $milestone['description'], 'sort' => $milestone['sort']]
            );
        }

        $financials = [
            ['year' => 2025, 'label' => ['en' => 'Revenue', 'th' => 'รายได้', 'zh-CN' => '营业收入'], 'value' => '4,820', 'unit' => 'THB m', 'sort' => 1],
            ['year' => 2025, 'label' => ['en' => 'Order backlog', 'th' => 'มูลค่างานในมือ', 'zh-CN' => '订单储备'], 'value' => '1,940', 'unit' => 'THB m', 'sort' => 2],
            ['year' => 2025, 'label' => ['en' => 'Gross margin', 'th' => 'กำไรขั้นต้น', 'zh-CN' => '毛利率'], 'value' => '27.4', 'unit' => '%', 'sort' => 3],
            ['year' => 2025, 'label' => ['en' => 'Specialists', 'th' => 'ผู้เชี่ยวชาญ', 'zh-CN' => '专业人员'], 'value' => '240', 'unit' => 'people', 'sort' => 4],
            ['year' => 2024, 'label' => ['en' => 'Revenue', 'th' => 'รายได้', 'zh-CN' => '营业收入'], 'value' => '4,310', 'unit' => 'THB m', 'sort' => 1],
            ['year' => 2024, 'label' => ['en' => 'Order backlog', 'th' => 'มูลค่างานในมือ', 'zh-CN' => '订单储备'], 'value' => '1,720', 'unit' => 'THB m', 'sort' => 2],
            ['year' => 2024, 'label' => ['en' => 'Gross margin', 'th' => 'กำไรขั้นต้น', 'zh-CN' => '毛利率'], 'value' => '26.1', 'unit' => '%', 'sort' => 3],
            ['year' => 2024, 'label' => ['en' => 'Specialists', 'th' => 'ผู้เชี่ยวชาญ', 'zh-CN' => '专业人员'], 'value' => '215', 'unit' => 'people', 'sort' => 4],
            ['year' => 2023, 'label' => ['en' => 'Revenue', 'th' => 'รายได้', 'zh-CN' => '营业收入'], 'value' => '3,760', 'unit' => 'THB m', 'sort' => 1],
            ['year' => 2023, 'label' => ['en' => 'Order backlog', 'th' => 'มูลค่างานในมือ', 'zh-CN' => '订单储备'], 'value' => '1,480', 'unit' => 'THB m', 'sort' => 2],
            ['year' => 2023, 'label' => ['en' => 'Gross margin', 'th' => 'กำไรขั้นต้น', 'zh-CN' => '毛利率'], 'value' => '24.8', 'unit' => '%', 'sort' => 3],
            ['year' => 2023, 'label' => ['en' => 'Specialists', 'th' => 'ผู้เชี่ยวชาญ', 'zh-CN' => '专业人员'], 'value' => '187', 'unit' => 'people', 'sort' => 4],
        ];

        foreach ($financials as $row) {
            FinancialHighlight::updateOrCreate(
                ['site_id' => $site->id, 'year' => $row['year'], 'label->en' => $row['label']['en']],
                [
                    'label' => $row['label'],
                    'value' => $row['value'],
                    'unit' => $row['unit'],
                    'note' => 'Illustrative demonstration data. Not audited.',
                    'sort' => $row['sort'],
                    'is_illustrative' => true,
                ]
            );
        }

        $documents = [
            ['file' => 'meridian-company-profile.pdf', 'category' => 'corporate', 'year' => 2026, 'sort' => 1, 'title' => ['en' => 'Company Profile 2026', 'th' => 'โปรไฟล์บริษัท 2026', 'zh-CN' => '2026 公司简介'], 'description' => ['en' => 'An illustrative overview of the fictional practice, its capabilities, and its operating principles.', 'th' => 'ภาพรวมเพื่อการสาธิตของแนวปฏิบัติ สมรรถภาพ และหลักการดำเนินงาน', 'zh-CN' => '关于虚构公司、能力与运营原则的示例概览。']],
            ['file' => 'meridian-annual-review-2025.pdf', 'category' => 'annual_report', 'year' => 2025, 'sort' => 2, 'title' => ['en' => 'Annual Review 2025 (Illustrative)', 'th' => 'รายงานประจำปี 2025 (ตัวอย่าง)', 'zh-CN' => '2025 年度回顾（示例）'], 'description' => ['en' => 'Demonstration financial highlights and operating priorities. Not an audited statement.', 'th' => 'ข้อมูลการเงินและลำดับความสำคัญเพื่อการสาธิต ไม่ใช่รายงานที่ตรวจสอบแล้ว', 'zh-CN' => '示例财务亮点与运营重点，非审计报表。']],
            ['file' => 'meridian-governance-charter.pdf', 'category' => 'governance', 'year' => 2026, 'sort' => 3, 'title' => ['en' => 'Governance Charter (Illustrative)', 'th' => 'กฎบัตรธรรมาภิบาล (ตัวอย่าง)', 'zh-CN' => '治理章程（示例）'], 'description' => ['en' => 'Sample governance structure and delegations for demonstration purposes.', 'th' => 'โครงสร้างธรรมาภิบาลและการมอบอำนาจเพื่อการสาธิต', 'zh-CN' => '用于演示的治理结构与授权示例。']],
            ['file' => 'meridian-data-protection-summary.pdf', 'category' => 'governance', 'year' => 2026, 'sort' => 4, 'title' => ['en' => 'Data Protection Summary (Illustrative)', 'th' => 'สรุปการคุ้มครองข้อมูล (ตัวอย่าง)', 'zh-CN' => '数据保护摘要（示例）'], 'description' => ['en' => 'Sample privacy summary. Requires legal review before any real deployment.', 'th' => 'สรุปความเป็นส่วนตัวตัวอย่าง ต้องผ่านการทบทวนทางกฎหมายก่อนใช้งานจริง', 'zh-CN' => '示例隐私摘要，实际部署前需法律审查。']],
        ];

        foreach ($documents as $document) {
            $asset = $media[$document['file']] ?? null;

            Document::updateOrCreate(
                ['site_id' => $site->id, 'year' => $document['year'], 'category' => $document['category'], 'title->en' => $document['title']['en']],
                [
                    'title' => $document['title'],
                    'description' => $document['description'],
                    'media_id' => $asset?->id,
                    'published_at' => now()->subDays(20),
                    'sort' => $document['sort'],
                    'is_illustrative' => true,
                ]
            );
        }
    }
}
