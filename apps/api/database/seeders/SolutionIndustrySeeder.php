<?php

namespace Database\Seeders;

use App\Models\Industry;
use App\Models\MediaAsset;
use App\Models\Site;
use App\Models\Solution;
use Illuminate\Database\Seeder;

class SolutionIndustrySeeder extends Seeder
{
    public function run(): void
    {
        $site = Site::where('slug', 'meridian')->firstOrFail();
        $media = MediaAsset::where('site_id', $site->id)->get()
            ->keyBy(fn (MediaAsset $asset) => pathinfo($asset->original_name, PATHINFO_FILENAME));

        $industries = [
            'manufacturing' => ['icon' => 'factory', 'image' => 'manufacturing', 'sort' => 1],
            'logistics-distribution' => ['icon' => 'container', 'image' => 'logistics', 'sort' => 2],
            'energy-utilities' => ['icon' => 'bolt', 'image' => 'energy', 'sort' => 3],
            'infrastructure' => ['icon' => 'bridge', 'image' => 'infrastructure', 'sort' => 4],
            'commercial-industrial-property' => ['icon' => 'building', 'image' => 'site-plan', 'sort' => 5],
        ];

        $solutions = [
            'industrial-automation' => ['icon' => 'robot', 'image' => 'automation', 'sort' => 1],
            'smart-manufacturing' => ['icon' => 'pulse', 'image' => 'manufacturing', 'sort' => 2],
            'industrial-software' => ['icon' => 'code', 'image' => 'site-plan', 'sort' => 3],
            'infrastructure-engineering' => ['icon' => 'grid', 'image' => 'infrastructure', 'sort' => 4],
            'digital-transformation' => ['icon' => 'nodes', 'image' => 'site-plan', 'sort' => 5],
            'system-integration' => ['icon' => 'link', 'image' => 'precision', 'sort' => 6],
        ];

        foreach ($industries as $key => $config) {
            $industry = Industry::updateOrCreate(
                ['site_id' => $site->id, 'key' => $key],
                [
                    'icon' => $config['icon'],
                    'status' => 'published',
                    'sort' => $config['sort'],
                    'featured_media_id' => $media[$config['image']]?->id,
                ]
            );

            foreach ($this->industryTranslations($key) as $locale => $translation) {
                $industry->translations()->updateOrCreate(
                    ['locale' => $locale],
                    $translation + ['site_id' => $site->id]
                );
            }
        }

        foreach ($solutions as $key => $config) {
            $solution = Solution::updateOrCreate(
                ['site_id' => $site->id, 'key' => $key],
                [
                    'icon' => $config['icon'],
                    'status' => 'published',
                    'sort' => $config['sort'],
                    'featured_media_id' => $media[$config['image']]?->id,
                ]
            );

            foreach ($this->solutionTranslations($key) as $locale => $translation) {
                $solution->translations()->updateOrCreate(
                    ['locale' => $locale],
                    $translation + ['site_id' => $site->id]
                );
            }
        }

        $map = [
            'manufacturing' => ['industrial-automation', 'smart-manufacturing', 'industrial-software'],
            'logistics-distribution' => ['system-integration', 'digital-transformation', 'industrial-software'],
            'energy-utilities' => ['infrastructure-engineering', 'digital-transformation', 'system-integration'],
            'infrastructure' => ['infrastructure-engineering', 'system-integration', 'industrial-automation'],
            'commercial-industrial-property' => ['infrastructure-engineering', 'smart-manufacturing', 'digital-transformation'],
        ];

        foreach ($map as $industryKey => $solutionKeys) {
            $industry = Industry::where('site_id', $site->id)->where('key', $industryKey)->firstOrFail();
            $ids = Solution::where('site_id', $site->id)->whereIn('key', $solutionKeys)->pluck('id');
            $industry->solutions()->sync($ids);
        }
    }

    protected function solutions(): array
    {
        return [
            'industrial-automation' => [
                'en' => [
                    'title' => 'Industrial Automation',
                    'slug' => 'industrial-automation',
                    'tagline' => 'Control architecture, robotics, and line integration engineered for uptime.',
                    'overview' => 'Meridian designs and commissions automation systems that connect machines, safety, and production data into one dependable operating layer. We work backwards from the process: cycle time, quality, and maintainability come before hardware selection.',
                    'problems' => [
                        ['title' => 'Islands of automation', 'description' => 'Machines from different vendors run in isolation, so operators reconcile performance by hand.'],
                        ['title' => 'Fragile changeovers', 'description' => 'Product changes require engineering intervention and hours of unplanned downtime.'],
                        ['title' => 'Data without trust', 'description' => 'Dashboards exist, but no one can trace a number back to a verified signal.'],
                    ],
                    'approach' => [
                        ['title' => 'Process and risk assessment', 'description' => 'We map the real production flow, constraints, and failure modes before proposing equipment.'],
                        ['title' => 'Control architecture', 'description' => 'A documented PLC, safety, network, and data architecture that vendors can build against.'],
                        ['title' => 'Build and commissioning', 'description' => 'Factory acceptance testing, staged commissioning, and operator training with measurable acceptance criteria.'],
                        ['title' => 'Operate and improve', 'description' => 'Performance reviews and controlled improvements after handover, not a departure at switch-on.'],
                    ],
                    'capabilities' => [
                        ['title' => 'PLC, HMI, SCADA and safety', 'description' => 'Multi-vendor control and safety integration with documented interfaces.'],
                        ['title' => 'Robotic cells', 'description' => 'Cell design, end-of-arm tooling, guarding, and cycle-time validation.'],
                        ['title' => 'Line simulation', 'description' => 'Virtual commissioning to test logic before the line stops for installation.'],
                        ['title' => 'Legacy migration', 'description' => 'Phased replacement of obsolete controls without a full-plant shutdown.'],
                    ],
                    'benefits' => [
                        ['title' => 'Stable cycle times', 'description' => 'Control logic tuned to the real operating envelope, not the brochure.'],
                        ['title' => 'Faster changeovers', 'description' => 'Recipes, tooling, and operator guidance designed as one system.'],
                        ['title' => 'Traceable quality', 'description' => 'Every result linked to machine, batch, and operator context.'],
                        ['title' => 'Lower maintenance burden', 'description' => 'Spare-part strategy, diagnostics, and documentation that local teams can use.'],
                    ],
                ],
                'th' => [
                    'title' => 'ระบบอัตโนมัติอุตสาหกรรม',
                    'slug' => 'ระบบอัตโนมัติอุตสาหกรรม',
                    'tagline' => 'สถาปัตยกรรมควบคุม หุ่นยนต์ และการเชื่อมต่อสายการผลิตที่ออกแบบเพื่อความพร้อมใช้งาน',
                    'overview' => 'เมอริเดียนออกแบบและทดสอบระบบอัตโนมัติที่เชื่อมเครื่องจักร ความปลอดภัย และข้อมูลการผลิตให้เป็นชั้นปฏิบัติการเดียวที่เชื่อถือได้ เราทำงานย้อนจากกระบวนการผลิต โดยให้ความสำคัญกับรอบเวลา คุณภาพ และความง่ายในการบำรุงรักษาก่อนการเลือกฮาร์ดแวร์',
                    'problems' => [
                        ['title' => 'ระบบอัตโนมัติแยกส่วน', 'description' => 'เครื่องจักรจากผู้ผลิตต่างรายทำงานแยกจากกัน ผู้ปฏิบัติงานต้องกระทบยอดประสิทธิภาพด้วยมือ'],
                        ['title' => 'การเปลี่ยนรุ่นผลิตภัณฑ์ที่เปราะบาง', 'description' => 'การเปลี่ยนผลิตภัณฑ์ต้องให้วิศวกรเข้าแก้ไขและทำให้สายการผลิตหยุดโดยไม่ได้วางแผน'],
                        ['title' => 'ข้อมูลที่ยังไม่น่าเชื่อถือ', 'description' => 'มีแดชบอร์ดแต่ไม่มีใครตรวจย้อนตัวเลขกลับไปยังสัญญาณที่ผ่านการยืนยันได้'],
                    ],
                    'approach' => [
                        ['title' => 'ประเมินกระบวนการและความเสี่ยง', 'description' => 'เราวิเคราะห์การไหลจริง ข้อจำกัด และโหมดความล้มเหลวก่อนเสนออุปกรณ์'],
                        ['title' => 'ออกแบบสถาปัตยกรรมควบคุม', 'description' => 'จัดทำสถาปัตยกรรม PLC ความปลอดภัย เครือข่าย และข้อมูลที่ผู้ขายใช้สร้างได้'],
                        ['title' => 'สร้างและทดสอบระบบ', 'description' => 'ทดสอบที่โรงงาน ติดตั้งเป็นระยะ และฝึกอบรมผู้ปฏิบัติงานด้วยเกณฑ์ยอมรับที่วัดได้'],
                        ['title' => 'ดูแลและพัฒนา', 'description' => 'ทบทวนผลการทำงานและปรับปรุงอย่างควบคุมหลังส่งมอบ ไม่ทิ้งหลังจากเปิดเดินเครื่อง'],
                    ],
                    'capabilities' => [
                        ['title' => 'PLC, HMI, SCADA และความปลอดภัย', 'description' => 'เชื่อมต่อระบบควบคุมและความปลอดภัยหลายยี่ห้อพร้อมอินเทอร์เฟซที่จัดทำเอกสาร'],
                        ['title' => 'เซลล์หุ่นยนต์', 'description' => 'ออกแบบเซลล์ เครื่องมือปลายแขน อุปกรณ์ป้องกัน และตรวจสอบรอบเวลา'],
                        ['title' => 'จำลองสายการผลิต', 'description' => 'ทดสอบแบบเสมือนก่อนหยุดสายจริงเพื่อติดตั้ง'],
                        ['title' => 'อัปเกรดระบบเดิม', 'description' => 'เปลี่ยนระบบควบคุมที่ล้าสมัยเป็นระยะโดยไม่ต้องหยุดโรงงานทั้งแห่ง'],
                    ],
                    'benefits' => [
                        ['title' => 'รอบเวลาที่มั่นคง', 'description' => 'ตรรกะควบคุมปรับให้เข้ากับขอบเขตการทำงานจริง ไม่ใช่ข้อมูลจากโบรชัวร์'],
                        ['title' => 'เปลี่ยนรุ่นได้เร็วขึ้น', 'description' => 'สูตรการผลิต เครื่องมือ และคำแนะนำผู้ปฏิบัติงานออกแบบเป็นระบบเดียว'],
                        ['title' => 'คุณภาพที่ตรวจสอบได้', 'description' => 'ทุกผลลัพธ์เชื่อมโยงกับเครื่องจักร ล็อต และบริบทของผู้ปฏิบัติงาน'],
                        ['title' => 'ภาระบำรุงรักษาต่ำลง', 'description' => 'แผนอะไหล่ การวินิจฉัย และเอกสารที่ทีมในพื้นที่ใช้ได้จริง'],
                    ],
                ],
                'zh-CN' => [
                    'title' => '工业自动化',
                    'slug' => 'industrial-automation',
                    'tagline' => '以稳定运行为目标设计控制架构、机器人与产线集成。',
                    'overview' => 'Meridian 设计并调试将设备、安全与生产数据连接为可靠运营层的自动化系统。我们从工艺出发，先确定节拍、质量与可维护性，再选择硬件。',
                    'problems' => [
                        ['title' => '自动化孤岛', 'description' => '不同厂商的设备彼此隔离，绩效数据依赖人工核对。'],
                        ['title' => '换型脆弱', 'description' => '产品切换需要工程介入，并造成数小时非计划停机。'],
                        ['title' => '数据缺乏可信度', 'description' => '虽然有大屏，但无法追溯到经过验证的信号。'],
                    ],
                    'approach' => [
                        ['title' => '工艺与风险评估', 'description' => '在提出设备方案前，梳理真实生产流、约束与失效模式。'],
                        ['title' => '控制架构设计', 'description' => '形成可供多方实施的 PLC、安全、网络与数据架构文档。'],
                        ['title' => '制造与调试', 'description' => '工厂验收测试、分阶段调试与可衡量验收标准的操作培训。'],
                        ['title' => '运营与改进', 'description' => '交付后持续复盘并受控优化，而不是开机即离场。'],
                    ],
                    'capabilities' => [
                        ['title' => 'PLC、HMI、SCADA 与安全', 'description' => '多品牌控制与安全集成，接口文档完整。'],
                        ['title' => '机器人工作站', 'description' => '工作站设计、末端工具、防护与节拍验证。'],
                        ['title' => '产线仿真', 'description' => '在停产安装前通过虚拟调试验证逻辑。'],
                        ['title' => '旧系统迁移', 'description' => '分阶段替换老旧控制，无需整厂停机。'],
                    ],
                    'benefits' => [
                        ['title' => '稳定节拍', 'description' => '控制逻辑按真实工况调校，而非样本参数。'],
                        ['title' => '更快换型', 'description' => '配方、工装与操作指引作为一个系统设计。'],
                        ['title' => '可追溯质量', 'description' => '每个结果关联设备、批次与操作背景。'],
                        ['title' => '更低维护负担', 'description' => '本地团队可用的备件策略、诊断与文档。'],
                    ],
                ],
            ],

            'smart-manufacturing' => [
                'en' => [
                    'title' => 'Smart Manufacturing',
                    'slug' => 'smart-manufacturing',
                    'tagline' => 'Traceability, OEE, and quality intelligence built on the plant floor.',
                    'overview' => 'We turn production data into operating decisions. Our teams connect machines, quality systems, and people with a data model that reflects how the plant actually runs — then prove the value on one line before scaling.',
                    'problems' => [
                        ['title' => 'OEE calculated by spreadsheet', 'description' => 'Performance is reviewed weekly, after the losses have already happened.'],
                        ['title' => 'Quality data disconnected', 'description' => 'Inspection results sit apart from process parameters, so root cause takes days.'],
                        ['title' => 'Reactive maintenance', 'description' => 'Assets are serviced on calendar intervals rather than condition and criticality.'],
                    ],
                    'approach' => [
                        ['title' => 'Value-stream diagnostic', 'description' => 'Identify the two or three losses worth instrumenting first.'],
                        ['title' => 'Operating data model', 'description' => 'Define assets, shifts, products, and quality events once, consistently.'],
                        ['title' => 'Edge connectivity', 'description' => 'Collect signals safely without exposing control networks to the enterprise.'],
                        ['title' => 'Adoption on the floor', 'description' => 'Design screens and routines for supervisors, not only for analysts.'],
                    ],
                    'capabilities' => [
                        ['title' => 'OEE and downtime intelligence', 'description' => 'Reason-coded stops, shift reporting, and loss trees operators trust.'],
                        ['title' => 'Traceability', 'description' => 'Batch, component, and process genealogy across lines and suppliers.'],
                        ['title' => 'Quality analytics', 'description' => 'SPC, defect correlation, and closed-loop corrective actions.'],
                        ['title' => 'Energy and utilities monitoring', 'description' => 'Consumption per product and per shift with actionable anomalies.'],
                    ],
                    'benefits' => [
                        ['title' => 'Losses visible within a shift', 'description' => 'Supervisors act while the cause is still recoverable.'],
                        ['title' => 'Faster root-cause analysis', 'description' => 'Process and quality context in one timeline.'],
                        ['title' => 'Better capacity decisions', 'description' => 'Actual constraint data instead of estimates.'],
                        ['title' => 'Scalable foundation', 'description' => 'A repeatable model for the next line and the next site.'],
                    ],
                ],
                'th' => [
                    'title' => 'โรงงานอัจฉริยะ',
                    'slug' => 'โรงงานอัจฉริยะ',
                    'tagline' => 'การตรวจสอบย้อนกลับ OEE และข้อมูลคุณภาพที่สร้างจากพื้นที่ผลิตจริง',
                    'overview' => 'เราเปลี่ยนข้อมูลการผลิตให้เป็นการตัดสินใจเชิงปฏิบัติ ทีมของเราเชื่อมเครื่องจักร ระบบคุณภาพ และบุคลากรด้วยโมเดลข้อมูลที่สะท้อนการทำงานจริงของโรงงาน แล้วพิสูจน์คุณค่าบนสายการผลิตหนึ่งก่อนขยายผล',
                    'problems' => [
                        ['title' => 'คำนวณ OEE ด้วยสเปรดชีต', 'description' => 'ประสิทธิภาพถูกทบทวนรายสัปดาห์ หลังจากความสูญเสียเกิดขึ้นแล้ว'],
                        ['title' => 'ข้อมูลคุณภาพแยกจากกัน', 'description' => 'ผลตรวจสอบอยู่ห่างจากพารามิเตอร์กระบวนการ ทำให้หาสาเหตุใช้เวลาหลายวัน'],
                        ['title' => 'บำรุงรักษาเชิงรับ', 'description' => 'ซ่อมตามรอบปฏิทินมากกว่าตามสภาพและความสำคัญของเครื่อง'],
                    ],
                    'approach' => [
                        ['title' => 'วินิจฉัยสายคุณค่า', 'description' => 'เลือกความสูญเสียสองถึงสามจุดที่คุ้มค่าที่จะวัดก่อน'],
                        ['title' => 'โมเดลข้อมูลการปฏิบัติงาน', 'description' => 'นิยามเครื่องจักร กะ ผลิตภัณฑ์ และเหตุการณ์คุณภาพให้เป็นมาตรฐานเดียว'],
                        ['title' => 'เชื่อมต่อที่ขอบเครือข่าย', 'description' => 'เก็บสัญญาณอย่างปลอดภัยโดยไม่เปิดเครือข่ายควบคุมสู่ระบบองค์กร'],
                        ['title' => 'การนำไปใช้หน้างาน', 'description' => 'ออกแบบหน้าจอและ routines สำหรับหัวหน้างาน ไม่ใช่เฉพาะนักวิเคราะห์'],
                    ],
                    'capabilities' => [
                        ['title' => 'ข้อมูล OEE และการหยุดเครื่อง', 'description' => 'บันทึกเหตุผลการหยุด รายงานรายกะ และผังความสูญเสียที่ผู้ปฏิบัติงานเชื่อถือ'],
                        ['title' => 'ตรวจสอบย้อนกลับ', 'description' => 'ลำดับล็อต ชิ้นส่วน และกระบวนการข้ามสายการผลิตและซัพพลายเออร์'],
                        ['title' => 'วิเคราะห์คุณภาพ', 'description' => 'SPC ความสัมพันธ์ของข้อบกพร่อง และการปิด CAPA'],
                        ['title' => 'ติดตามพลังงานและสาธารณูปโภค', 'description' => 'การใช้พลังงานต่อผลิตภัณฑ์และต่อกะ พร้อมความผิดปกติที่ดำเนินการได้'],
                    ],
                    'benefits' => [
                        ['title' => 'เห็นความสูญเสียภายในกะ', 'description' => 'หัวหน้างานแก้ไขได้ทันในขณะที่สาเหตุยังแก้ได้'],
                        ['title' => 'วิเคราะห์สาเหตุเร็วขึ้น', 'description' => 'บริบทกระบวนการและคุณภาพอยู่ในไทม์ไลน์เดียว'],
                        ['title' => 'ตัดสินใจกำลังการผลิตดีขึ้น', 'description' => 'ใช้ข้อมูลข้อจำกัดจริงแทนการประมาณ'],
                        ['title' => 'รากฐานที่ขยายได้', 'description' => 'โมเดลที่นำไปใช้ซ้ำกับสายการผลิตและโรงงานถัดไปได้'],
                    ],
                ],
                'zh-CN' => [
                    'title' => '智能制造',
                    'slug' => 'smart-manufacturing',
                    'tagline' => '在车间现场构建追溯、OEE 与质量智能。',
                    'overview' => '我们把生产数据转化为运营决策。团队以反映工厂真实运行方式的数据模型连接设备、质量系统与人员，先单线验证价值，再规模复制。',
                    'problems' => [
                        ['title' => '用表格计算 OEE', 'description' => '绩效按周复盘，损失已经发生。'],
                        ['title' => '质量数据割裂', 'description' => '检测结果与工艺参数分离，根因分析耗时数日。'],
                        ['title' => '被动维护', 'description' => '按日历而非状态与关键度维护设备。'],
                    ],
                    'approach' => [
                        ['title' => '价值流诊断', 'description' => '优先选择两到三项值得先数字化的损失。'],
                        ['title' => '运营数据模型', 'description' => '统一资产、班次、产品与质量事件的定义。'],
                        ['title' => '边缘连接', 'description' => '安全采集信号，不将控制网络暴露给企业网。'],
                        ['title' => '现场采用', 'description' => '为班组长而非仅分析师设计界面与例行工作。'],
                    ],
                    'capabilities' => [
                        ['title' => 'OEE 与停机智能', 'description' => '可被现场信任的停机原因、班次报告与损失树。'],
                        ['title' => '追溯', 'description' => '跨产线与供应商的批次、部件与工艺谱系。'],
                        ['title' => '质量分析', 'description' => 'SPC、缺陷关联与闭环纠正措施。'],
                        ['title' => '能源与公用工程监测', 'description' => '按产品与班次计量，输出可执行的异常。'],
                    ],
                    'benefits' => [
                        ['title' => '班内看见损失', 'description' => '在原因仍可挽回时采取行动。'],
                        ['title' => '更快根因分析', 'description' => '工艺与质量上下文进入同一时间线。'],
                        ['title' => '更好的产能决策', 'description' => '以真实瓶颈数据替代估算。'],
                        ['title' => '可扩展基础', 'description' => '可复用到下一条产线与下一个工厂。'],
                    ],
                ],
            ],

            'industrial-software' => [
                'en' => [
                    'title' => 'Industrial Software',
                    'slug' => 'industrial-software',
                    'tagline' => 'Operational platforms that turn machine data into decisions.',
                    'overview' => 'We build focused operational software where off-the-shelf tools stop short: production portals, quality workflows, asset registers, and integration layers. Every interface is designed for the shift, not for a demo.',
                    'problems' => [
                        ['title' => 'Spreadsheet operations', 'description' => 'Critical routines depend on files only one person understands.'],
                        ['title' => 'Integration debt', 'description' => 'Every new system adds another point-to-point connection and another reconciliation.'],
                        ['title' => 'Software nobody uses', 'description' => 'Tools are bought for managers but ignore the operator\'s actual task.'],
                    ],
                    'approach' => [
                        ['title' => 'Domain modelling', 'description' => 'Define the real entities, states, and permissions before writing screens.'],
                        ['title' => 'API-first architecture', 'description' => 'One contract for web, mobile, and machine integration.'],
                        ['title' => 'Operator-centred UX', 'description' => 'Test with the people who will use it at 3 a.m., not only in a workshop.'],
                        ['title' => 'Boring deployment', 'description' => 'Observability, backups, and upgrade paths from day one.'],
                    ],
                    'capabilities' => [
                        ['title' => 'Production portals', 'description' => 'Shift handover, work instructions, and performance in one place.'],
                        ['title' => 'Quality workflows', 'description' => 'Deviation capture, CAPA, and supplier quality with clear ownership.'],
                        ['title' => 'Asset and maintenance registers', 'description' => 'Criticality, condition, and history connected to operations.'],
                        ['title' => 'Integration services', 'description' => 'Secure APIs and event streams between OT and enterprise systems.'],
                    ],
                    'benefits' => [
                        ['title' => 'One version of the truth', 'description' => 'Shared definitions instead of competing spreadsheets.'],
                        ['title' => 'Faster onboarding', 'description' => 'Process knowledge captured in the system, not only in people.'],
                        ['title' => 'Audit readiness', 'description' => 'Traceable actions, approvals, and data lineage.'],
                        ['title' => 'Lower integration cost', 'description' => 'Reusable contracts and observable interfaces.'],
                    ],
                ],
                'th' => [
                    'title' => 'ซอฟต์แวร์อุตสาหกรรม',
                    'slug' => 'ซอฟต์แวร์อุตสาหกรรม',
                    'tagline' => 'แพลตฟอร์มปฏิบัติการที่เปลี่ยนข้อมูลเครื่องจักรเป็นการตัดสินใจ',
                    'overview' => 'เราสร้างซอฟต์แวร์ปฏิบัติการที่มุ่งเป้าในจุดที่เครื่องมือสำเร็จรูปไปไม่ถึง ทั้งพอร์ทัลการผลิต เวิร์กโฟลว์คุณภาพ ทะเบียนสินทรัพย์ และชั้นเชื่อมต่อระบบ ทุกอินเทอร์เฟซออกแบบสำหรับการทำงานจริงในกะ ไม่ใช่เพื่อการสาธิต',
                    'problems' => [
                        ['title' => 'การทำงานบนสเปรดชีต', 'description' => 'ขั้นตอนสำคัญพึ่งพาไฟล์ที่คนเดียวเข้าใจ'],
                        ['title' => 'หนี้การเชื่อมต่อระบบ', 'description' => 'ทุกระบบใหม่เพิ่มการเชื่อมแบบจุดต่อจุดและการกระทบยอดอีกชั้น'],
                        ['title' => 'ซอฟต์แวร์ที่ไม่มีคนใช้', 'description' => 'ซื้อมาเพื่อผู้จัดการแต่ไม่ตอบโจทย์งานจริงของผู้ปฏิบัติงาน'],
                    ],
                    'approach' => [
                        ['title' => 'สร้างโมเดลโดเมน', 'description' => 'นิยามเอนทิตี สถานะ และสิทธิ์จริงก่อนสร้างหน้าจอ'],
                        ['title' => 'สถาปัตยกรรม API-first', 'description' => 'สัญญาเดียวสำหรับเว็บ มือถือ และการเชื่อมเครื่องจักร'],
                        ['title' => 'UX ที่ยึดผู้ปฏิบัติงาน', 'description' => 'ทดสอบกับคนที่ใช้งานจริงตอนตีสาม ไม่ใช่แค่ในห้องประชุม'],
                        ['title' => 'การติดตั้งที่เรียบง่าย', 'description' => 'Observability สำรองข้อมูล และเส้นทางอัปเกรดตั้งแต่วันแรก'],
                    ],
                    'capabilities' => [
                        ['title' => 'พอร์ทัลการผลิต', 'description' => 'ส่งมอบกะ คำแนะนำงาน และประสิทธิภาพในที่เดียว'],
                        ['title' => 'เวิร์กโฟลว์คุณภาพ', 'description' => 'บันทึกส่วนเบี่ยงเบน CAPA และคุณภาพซัพพลายเออร์พร้อมเจ้าภาพชัดเจน'],
                        ['title' => 'ทะเบียนสินทรัพย์และบำรุงรักษา', 'description' => 'ความสำคัญ สภาพ และประวัติเชื่อมกับการปฏิบัติงาน'],
                        ['title' => 'บริการเชื่อมต่อ', 'description' => 'API และ event stream ที่ปลอดภัยระหว่าง OT และระบบองค์กร'],
                    ],
                    'benefits' => [
                        ['title' => 'ข้อมูลจริงชุดเดียว', 'description' => 'นิยามร่วมแทนสเปรดชีตที่แข่งขันกัน'],
                        ['title' => 'เริ่มงานได้เร็วขึ้น', 'description' => 'ความรู้กระบวนการถูกเก็บในระบบ ไม่ใช่แค่ในตัวคน'],
                        ['title' => 'พร้อมต่อการตรวจประเมิน', 'description' => 'การกระทำ การอนุมัติ และสายข้อมูลที่ตรวจสอบได้'],
                        ['title' => 'ต้นทุนเชื่อมต่อต่ำลง', 'description' => 'สัญญาที่ใช้ซ้ำและอินเทอร์เฟซที่สังเกตได้'],
                    ],
                ],
                'zh-CN' => [
                    'title' => '工业软件',
                    'slug' => 'industrial-software',
                    'tagline' => '把设备数据转化为决策的运营平台。',
                    'overview' => '我们打造聚焦的运营软件，填补标准产品无法覆盖的空白：生产门户、质量流程、资产台账与集成层。每个界面都为真实班次设计，而不是为演示设计。',
                    'problems' => [
                        ['title' => '依赖电子表格', 'description' => '关键流程依赖只有一个人理解的表格。'],
                        ['title' => '集成债务', 'description' => '每套新系统都增加点对点连接与人工核对。'],
                        ['title' => '无人使用的软件', 'description' => '为管理者采购，却忽视操作员的真实任务。'],
                    ],
                    'approach' => [
                        ['title' => '领域建模', 'description' => '先定义真实实体、状态与权限，再设计界面。'],
                        ['title' => 'API 优先架构', 'description' => '用一套契约服务 Web、移动与设备集成。'],
                        ['title' => '以操作员为中心', 'description' => '与凌晨三点真正使用系统的人一起测试。'],
                        ['title' => '可运维的部署', 'description' => '从第一天就有可观测性、备份与升级路径。'],
                    ],
                    'capabilities' => [
                        ['title' => '生产门户', 'description' => '班次交接、作业指导与绩效集中管理。'],
                        ['title' => '质量流程', 'description' => '偏差、CAPA 与供应商质量，责任清晰。'],
                        ['title' => '资产与维护台账', 'description' => '关键度、状态与历史连接运营。'],
                        ['title' => '集成服务', 'description' => 'OT 与企业系统之间的安全 API 与事件流。'],
                    ],
                    'benefits' => [
                        ['title' => '单一事实来源', 'description' => '共享定义，取代互相冲突的表格。'],
                        ['title' => '更快上岗', 'description' => '工艺知识沉淀在系统中，而非只在人脑里。'],
                        ['title' => '审计就绪', 'description' => '可追溯的操作、审批与数据血缘。'],
                        ['title' => '更低集成成本', 'description' => '可复用契约与可观测接口。'],
                    ],
                ],
            ],

            'infrastructure-engineering' => [
                'en' => [
                    'title' => 'Infrastructure Engineering',
                    'slug' => 'infrastructure-engineering',
                    'tagline' => 'Resilient electrical, utility, and facility systems for critical operations.',
                    'overview' => 'From load studies to commissioning, we engineer the physical systems that keep production and services running. Our work is judged by resilience, maintainability, and a handover package the client\'s own team can operate.',
                    'problems' => [
                        ['title' => 'Capacity constraints', 'description' => 'Growth is limited by distribution capacity nobody has modelled end to end.'],
                        ['title' => 'Ageing assets', 'description' => 'Critical equipment is past its design life with no credible replacement sequence.'],
                        ['title' => 'Resilience gaps', 'description' => 'Single points of failure are known but not quantified or prioritised.'],
                    ],
                    'approach' => [
                        ['title' => 'Assessment', 'description' => 'Load, condition, protection, and failure-mode studies against real operating scenarios.'],
                        ['title' => 'Design', 'description' => 'Coordinated electrical, mechanical, and controls design with constructability review.'],
                        ['title' => 'Delivery', 'description' => 'Phased execution around live operations and agreed shutdown windows.'],
                        ['title' => 'Handover', 'description' => 'As-built documentation, spares, training, and a defensible maintenance plan.'],
                    ],
                    'capabilities' => [
                        ['title' => 'Electrical systems', 'description' => 'MV/LV distribution, protection coordination, and power quality.'],
                        ['title' => 'Utilities', 'description' => 'Compressed air, chilled water, steam, and wastewater systems.'],
                        ['title' => 'Controls and monitoring', 'description' => 'Metering, SCADA, and alarm strategies that operations can use.'],
                        ['title' => 'Commissioning', 'description' => 'Witnessed testing, defect management, and safe energisation.'],
                    ],
                    'benefits' => [
                        ['title' => 'Predictable capacity', 'description' => 'A documented basis for growth and capital decisions.'],
                        ['title' => 'Fewer unplanned outages', 'description' => 'Protection and condition insight aligned to criticality.'],
                        ['title' => 'Safer operations', 'description' => 'Design and documentation that support safe isolation and work.'],
                        ['title' => 'Cleaner handover', 'description' => 'An operating team that is ready on day one.'],
                    ],
                ],
                'th' => [
                    'title' => 'วิศวกรรมโครงสร้างพื้นฐาน',
                    'slug' => 'วิศวกรรมโครงสร้างพื้นฐาน',
                    'tagline' => 'ระบบไฟฟ้า สาธารณูปโภค และอาคารที่ทนทานสำหรับการดำเนินงานสำคัญ',
                    'overview' => 'ตั้งแต่การศึกษาภาระไฟฟ้าถึงการทดสอบระบบ เราออกแบบระบบกายภาพที่ทำให้การผลิตและบริการดำเนินต่อได้ งานของเราวัดจากความทนทาน ความง่ายในการบำรุงรักษา และชุดส่งมอบที่ทีมของลูกค้าดูแลเองได้',
                    'problems' => [
                        ['title' => 'ข้อจำกัดกำลังการผลิต', 'description' => 'การเติบโตถูกจำกัดด้วยกำลังจ่ายไฟที่ไม่มีใครจำลองทั้งระบบ'],
                        ['title' => 'สินทรัพย์เสื่อมสภาพ', 'description' => 'อุปกรณ์สำคัญเกินอายุออกแบบและไม่มีลำดับการเปลี่ยนที่น่าเชื่อถือ'],
                        ['title' => 'ช่องว่างความทนทาน', 'description' => 'จุดล้มเหลวจุดเดียวเป็นที่รู้แต่ยังไม่ถูกประเมินหรือจัดลำดับ'],
                    ],
                    'approach' => [
                        ['title' => 'การประเมิน', 'description' => 'ศึกษาภาระ สภาพ การป้องกัน และโหมดความล้มเหลวกับสถานการณ์จริง'],
                        ['title' => 'การออกแบบ', 'description' => 'ออกแบบไฟฟ้า เครื่องกล และควบคุมไปด้วยกันพร้อมทบทวนความสามารถในการก่อสร้าง'],
                        ['title' => 'การส่งมอบงาน', 'description' => 'ดำเนินการเป็นระยะโดยไม่หยุดปฏิบัติการและใช้ช่วงหยุดที่ตกลงไว้'],
                        ['title' => 'การส่งมอบ', 'description' => 'เอกสาร as-built อะไหล่ การฝึกอบรม และแผนบำรุงรักษาที่ป้องกันได้'],
                    ],
                    'capabilities' => [
                        ['title' => 'ระบบไฟฟ้า', 'description' => 'จ่ายไฟแรงกลาง/แรงต่ำ ประสานการป้องกัน และคุณภาพไฟฟ้า'],
                        ['title' => 'สาธารณูปโภค', 'description' => 'ลมอัด น้ำเย็น ไอน้ำ และระบบบำบัดน้ำเสีย'],
                        ['title' => 'ควบคุมและตรวจวัด', 'description' => 'มาตรวัด SCADA และกลยุทธ์แจ้งเตือนที่ใช้งานได้จริง'],
                        ['title' => 'ทดสอบระบบ', 'description' => 'ทดสอบต่อหน้าผู้ว่าจ้าง จัดการข้อบกพร่อง และจ่ายไฟอย่างปลอดภัย'],
                    ],
                    'benefits' => [
                        ['title' => 'กำลังการผลิตที่คาดการณ์ได้', 'description' => 'ฐานข้อมูลที่จัดทำเอกสารสำหรับการเติบโตและการลงทุน'],
                        ['title' => 'เหตุไฟดับโดยไม่วางแผนลดลง', 'description' => 'ข้อมูลการป้องกันและสภาพที่สอดคล้องกับความสำคัญ'],
                        ['title' => 'ปฏิบัติงานปลอดภัยขึ้น', 'description' => 'การออกแบบและเอกสารที่สนับสนุนการแยกวงจรและทำงานอย่างปลอดภัย'],
                        ['title' => 'ส่งมอบที่เรียบร้อย', 'description' => 'ทีมปฏิบัติการพร้อมตั้งแต่วันแรก'],
                    ],
                ],
                'zh-CN' => [
                    'title' => '基础设施工程',
                    'slug' => 'infrastructure-engineering',
                    'tagline' => '面向关键运营的可靠电气、公用工程与设施系统。',
                    'overview' => '从负荷研究到调试，我们设计支撑生产与服务持续运行的物理系统。衡量标准是可靠性、可维护性，以及客户团队能独立运营的交付包。',
                    'problems' => [
                        ['title' => '容量受限', 'description' => '增长受制于从未端到端建模的配电容量。'],
                        ['title' => '资产老化', 'description' => '关键设备超期服役，缺乏可信的更换序列。'],
                        ['title' => '韧性缺口', 'description' => '单点故障已知，但未量化或排序。'],
                    ],
                    'approach' => [
                        ['title' => '评估', 'description' => '基于真实工况研究负荷、状态、保护与失效模式。'],
                        ['title' => '设计', 'description' => '电气、机械与控制协同设计，并进行可施工性评审。'],
                        ['title' => '交付', 'description' => '围绕不停产运行与约定停机窗口分阶段实施。'],
                        ['title' => '移交', 'description' => '竣工文档、备件、培训与可辩护的维护计划。'],
                    ],
                    'capabilities' => [
                        ['title' => '电气系统', 'description' => '中低压配电、保护配合与电能质量。'],
                        ['title' => '公用工程', 'description' => '压缩空气、冷冻水、蒸汽与废水系统。'],
                        ['title' => '控制与监测', 'description' => '计量、SCADA 与运营可用的报警策略。'],
                        ['title' => '调试', 'description' => '见证测试、缺陷管理与安全送电。'],
                    ],
                    'benefits' => [
                        ['title' => '可预测容量', 'description' => '为增长与资本决策提供有据可依的基础。'],
                        ['title' => '更少非计划停机', 'description' => '保护与状态洞察匹配关键度。'],
                        ['title' => '更安全运营', 'description' => '设计与文档支持安全隔离作业。'],
                        ['title' => '更干净的移交', 'description' => '运营团队第一天即就绪。'],
                    ],
                ],
            ],

            'digital-transformation' => [
                'en' => [
                    'title' => 'Digital Transformation',
                    'slug' => 'digital-transformation',
                    'tagline' => 'A practical route from fragmented systems to a connected operation.',
                    'overview' => 'Transformation fails when it starts with technology. We help leadership sequence change around a clear operating problem, then deliver the data, systems, and habits that make the change durable.',
                    'problems' => [
                        ['title' => 'Pilot purgatory', 'description' => 'Many proofs of concept, no production deployment, and a tired organisation.'],
                        ['title' => 'Unclear ownership', 'description' => 'OT and IT both care, but no one owns the operating outcome.'],
                        ['title' => 'Data silos', 'description' => 'Every function has a number, and none of them agree.'],
                    ],
                    'approach' => [
                        ['title' => 'Diagnostic', 'description' => 'A short, evidence-led review of operations, systems, and decision latency.'],
                        ['title' => 'Sequenced roadmap', 'description' => 'Value cases, dependencies, and a realistic delivery order.'],
                        ['title' => 'Delivery with operators', 'description' => 'Joint teams, short increments, and adoption built into the plan.'],
                        ['title' => 'Governance that lasts', 'description' => 'Decision rights, standards, and a model for continuous improvement.'],
                    ],
                    'capabilities' => [
                        ['title' => 'Digital strategy', 'description' => 'A roadmap tied to margin, risk, and capacity — not a technology catalogue.'],
                        ['title' => 'Architecture', 'description' => 'Reference architecture for data, integration, and identity.'],
                        ['title' => 'Change and adoption', 'description' => 'Role-based training, comms, and operating routines.'],
                        ['title' => 'Analytics', 'description' => 'Decision-grade reporting with clear definitions and lineage.'],
                    ],
                    'benefits' => [
                        ['title' => 'Fewer stalled pilots', 'description' => 'Each initiative has an owner, a metric, and a route to scale.'],
                        ['title' => 'Faster decisions', 'description' => 'Data arrives where the decision is made.'],
                        ['title' => 'Lower technology risk', 'description' => 'Architecture and standards that survive vendor change.'],
                        ['title' => 'A capable organisation', 'description' => 'Internal teams able to run and extend the platform.'],
                    ],
                ],
                'th' => [
                    'title' => 'การเปลี่ยนผ่านดิจิทัล',
                    'slug' => 'การเปลี่ยนผ่านดิจิทัล',
                    'tagline' => 'เส้นทางปฏิบัติจากระบบที่กระจัดกระจายสู่การดำเนินงานที่เชื่อมต่อกัน',
                    'overview' => 'การเปลี่ยนผ่านล้มเหลวเมื่อเริ่มจากเทคโนโลยี เราช่วยผู้นำจัดลำดับการเปลี่ยนแปลงรอบปัญหาการดำเนินงานที่ชัดเจน แล้วส่งมอบข้อมูล ระบบ และพฤติกรรมการทำงานที่ทำให้การเปลี่ยนแปลงยั่งยืน',
                    'problems' => [
                        ['title' => 'ติดอยู่ในระยะนำร่อง', 'description' => 'มี Proof of Concept หลายโครงการแต่ไม่มีการใช้งานจริง และทีมเริ่มเหนื่อยล้า'],
                        ['title' => 'เจ้าภาพไม่ชัดเจน', 'description' => 'ทั้ง OT และ IT ให้ความสนใจ แต่ไม่มีใครรับผิดชอบผลลัพธ์การดำเนินงาน'],
                        ['title' => 'ข้อมูลแยกส่วน', 'description' => 'ทุกฝ่ายมีตัวเลขของตัวเอง และไม่ตรงกัน'],
                    ],
                    'approach' => [
                        ['title' => 'วินิจฉัย', 'description' => 'ทบทวนการดำเนินงาน ระบบ และความล่าช้าในการตัดสินใจอย่างสั้นและอิงหลักฐาน'],
                        ['title' => 'แผนที่จัดลำดับ', 'description' => 'กรณีคุณค่า การพึ่งพา และลำดับการส่งมอบที่สมจริง'],
                        ['title' => 'ส่งมอบร่วมกับผู้ปฏิบัติงาน', 'description' => 'ทีมร่วม ช่วงงานสั้น และการนำไปใช้ที่ฝังในแผน'],
                        ['title' => 'ธรรมาภิบาลที่ยั่งยืน', 'description' => 'สิทธิ์การตัดสินใจ มาตรฐาน และโมเดลการปรับปรุงต่อเนื่อง'],
                    ],
                    'capabilities' => [
                        ['title' => 'กลยุทธ์ดิจิทัล', 'description' => 'แผนที่ผูกกับกำไร ความเสี่ยง และกำลังการผลิต ไม่ใช่แคตตาล็อกเทคโนโลยี'],
                        ['title' => 'สถาปัตยกรรม', 'description' => 'สถาปัตยกรรมอ้างอิงสำหรับข้อมูล การเชื่อมต่อ และตัวตน'],
                        ['title' => 'การเปลี่ยนแปลงและการยอมรับ', 'description' => 'ฝึกอบรมตามบทบาท สื่อสาร และ routines การทำงาน'],
                        ['title' => 'การวิเคราะห์', 'description' => 'รายงานระดับการตัดสินใจพร้อมนิยามและสายข้อมูลชัดเจน'],
                    ],
                    'benefits' => [
                        ['title' => 'โครงการนำร่องที่ติดขัดน้อยลง', 'description' => 'ทุกโครงการมีเจ้าภาพ ตัวชี้วัด และเส้นทางขยายผล'],
                        ['title' => 'ตัดสินใจเร็วขึ้น', 'description' => 'ข้อมูลไปถึงจุดที่ตัดสินใจ'],
                        ['title' => 'ความเสี่ยงเทคโนโลยีต่ำลง', 'description' => 'สถาปัตยกรรมและมาตรฐานที่ทนต่อการเปลี่ยนผู้ขาย'],
                        ['title' => 'องค์กรที่พร้อม', 'description' => 'ทีมภายในสามารถดูแลและต่อยอดแพลตฟอร์มได้'],
                    ],
                ],
                'zh-CN' => [
                    'title' => '数字化转型',
                    'slug' => 'digital-transformation',
                    'tagline' => '从碎片化系统走向互联运营的务实路径。',
                    'overview' => '以技术开头的转型往往失败。我们帮助管理层围绕明确的运营问题安排变革顺序，再交付让变革持续的数据、系统与习惯。',
                    'problems' => [
                        ['title' => '试点困局', 'description' => '概念验证很多，却没有生产部署，组织逐渐疲惫。'],
                        ['title' => '责任不清', 'description' => 'OT 与 IT 都关心，但没有人对运营结果负责。'],
                        ['title' => '数据孤岛', 'description' => '每个部门都有自己的数字，彼此不一致。'],
                    ],
                    'approach' => [
                        ['title' => '诊断', 'description' => '以证据为导向，快速审视运营、系统与决策延迟。'],
                        ['title' => '排序路线图', 'description' => '价值论证、依赖关系与现实的交付顺序。'],
                        ['title' => '与操作员共同交付', 'description' => '联合团队、短迭代，把采用写进计划。'],
                        ['title' => '可持续治理', 'description' => '决策权、标准与持续改进模型。'],
                    ],
                    'capabilities' => [
                        ['title' => '数字战略', 'description' => '与利润、风险和产能挂钩的路线图，而非技术目录。'],
                        ['title' => '架构', 'description' => '数据、集成与身份识别的参考架构。'],
                        ['title' => '变革与采用', 'description' => '基于角色的培训、沟通与运营例行。'],
                        ['title' => '分析', 'description' => '定义清晰、血缘可查的决策级报告。'],
                    ],
                    'benefits' => [
                        ['title' => '更少停滞试点', 'description' => '每项举措都有负责人、指标与扩展路径。'],
                        ['title' => '更快决策', 'description' => '数据抵达决策发生的地方。'],
                        ['title' => '更低技术风险', 'description' => '架构与标准经得起供应商更替。'],
                        ['title' => '更有能力的组织', 'description' => '内部团队能够运营并扩展平台。'],
                    ],
                ],
            ],

            'system-integration' => [
                'en' => [
                    'title' => 'System Integration',
                    'slug' => 'system-integration',
                    'tagline' => 'One accountable team across vendors, protocols, and sites.',
                    'overview' => 'We take responsibility for the seams. Meridian coordinates vendors, designs the integration layer, tests the whole system, and stays accountable when something does not behave as specified.',
                    'problems' => [
                        ['title' => 'Vendor finger-pointing', 'description' => 'Each supplier proves its own box works while the line still fails.'],
                        ['title' => 'Protocol gaps', 'description' => 'Legacy equipment and modern platforms speak different languages.'],
                        ['title' => 'Inconsistent handover', 'description' => 'Every site receives a different standard of documentation and support.'],
                    ],
                    'approach' => [
                        ['title' => 'Interface map', 'description' => 'Every dependency, protocol, owner, and test written down before build.'],
                        ['title' => 'Integration layer', 'description' => 'A controlled boundary rather than hundreds of point-to-point links.'],
                        ['title' => 'End-to-end test', 'description' => 'Scenario-based testing across vendors, not only factory acceptance of individual packages.'],
                        ['title' => 'Operate and support', 'description' => 'A single support model with defined escalation and change control.'],
                    ],
                    'capabilities' => [
                        ['title' => 'OT/IT integration', 'description' => 'Secure, observable data exchange between control and enterprise systems.'],
                        ['title' => 'Middleware', 'description' => 'MQTT, OPC UA, APIs, and event orchestration with clear contracts.'],
                        ['title' => 'Migration', 'description' => 'Phased transition from legacy interfaces with rollback plans.'],
                        ['title' => 'Managed support', 'description' => 'Monitoring, patching, and improvement under an agreed service model.'],
                    ],
                    'benefits' => [
                        ['title' => 'One accountable owner', 'description' => 'A single team responsible for end-to-end behaviour.'],
                        ['title' => 'Faster fault isolation', 'description' => 'Observable interfaces show where the problem actually is.'],
                        ['title' => 'Reusable standards', 'description' => 'The next site starts from a proven integration pattern.'],
                        ['title' => 'Lower lifecycle cost', 'description' => 'Documented contracts reduce rework and vendor lock-in.'],
                    ],
                ],
                'th' => [
                    'title' => 'การบูรณาการระบบ',
                    'slug' => 'การบูรณาการระบบ',
                    'tagline' => 'ทีมเดียวที่รับผิดชอบข้ามผู้ขาย โปรโตคอล และโรงงาน',
                    'overview' => 'เรารับผิดชอบในรอยต่อ เมอริเดียนประสานผู้ขาย ออกแบบชั้นเชื่อมต่อ ทดสอบทั้งระบบ และยังรับผิดชอบเมื่อสิ่งใดไม่ทำงานตามข้อกำหนด',
                    'problems' => [
                        ['title' => 'ผู้ขายผลักความรับผิดชอบ', 'description' => 'แต่ละรายพิสูจน์ว่ากล่องของตนทำงานได้ แต่สายการผลิตยังล้มเหลว'],
                        ['title' => 'ช่องว่างโปรโตคอล', 'description' => 'อุปกรณ์เก่าและแพลตฟอร์มใหม่สื่อสารกันไม่ได้'],
                        ['title' => 'การส่งมอบไม่สม่ำเสมอ', 'description' => 'แต่ละโรงงานได้มาตรฐานเอกสารและการสนับสนุนต่างกัน'],
                    ],
                    'approach' => [
                        ['title' => 'แผนผังอินเทอร์เฟซ', 'description' => 'เขียนการพึ่งพา โปรโตคอล เจ้าภาพ และการทดสอบทั้งหมดก่อนสร้าง'],
                        ['title' => 'ชั้นเชื่อมต่อ', 'description' => 'ขอบเขตที่ควบคุมแทนการเชื่อมแบบจุดต่อจุดหลายร้อยจุด'],
                        ['title' => 'ทดสอบทั้งระบบ', 'description' => 'ทดสอบตามสถานการณ์ข้ามผู้ขาย ไม่ใช่แค่รับทดสอบแต่ละแพ็กเกจ'],
                        ['title' => 'ปฏิบัติงานและสนับสนุน', 'description' => 'โมเดลสนับสนุนเดียวพร้อมการยกระดับและการควบคุมการเปลี่ยนแปลง'],
                    ],
                    'capabilities' => [
                        ['title' => 'เชื่อมต่อ OT/IT', 'description' => 'แลกเปลี่ยนข้อมูลที่ปลอดภัยและสังเกตได้ระหว่างระบบควบคุมและองค์กร'],
                        ['title' => 'มิดเดิลแวร์', 'description' => 'MQTT, OPC UA, API และการจัดการเหตุการณ์ด้วยสัญญาที่ชัดเจน'],
                        ['title' => 'การย้ายระบบ', 'description' => 'เปลี่ยนผ่านจากอินเทอร์เฟซเดิมเป็นระยะพร้อมแผนย้อนกลับ'],
                        ['title' => 'บริการสนับสนุน', 'description' => 'ตรวจสอบ แพตช์ และปรับปรุงภายใต้ระดับบริการที่ตกลง'],
                    ],
                    'benefits' => [
                        ['title' => 'เจ้าภาพรับผิดชอบคนเดียว', 'description' => 'ทีมเดียวรับผิดชอบพฤติกรรมตั้งแต่ต้นจนจบ'],
                        ['title' => 'แยกสาเหตุเร็วขึ้น', 'description' => 'อินเทอร์เฟซที่สังเกตได้ชี้ว่าปัญหาอยู่ที่ใดจริง'],
                        ['title' => 'มาตรฐานใช้ซ้ำได้', 'description' => 'โรงงานถัดไปเริ่มจากรูปแบบที่พิสูจน์แล้ว'],
                        ['title' => 'ต้นทุนตลอดอายุต่ำลง', 'description' => 'สัญญาที่จัดทำเอกสารลดงานแก้และ vendor lock-in'],
                    ],
                ],
                'zh-CN' => [
                    'title' => '系统集成',
                    'slug' => 'system-integration',
                    'tagline' => '一个团队跨供应商、协议与站点承担责任。',
                    'overview' => '我们对接缝负责。Meridian 协调供应商、设计集成层、测试整体系统，并在不符合规格时继续负责。',
                    'problems' => [
                        ['title' => '供应商相互推诿', 'description' => '每家都证明自己的盒子正常，产线仍然失败。'],
                        ['title' => '协议鸿沟', 'description' => '老旧设备与现代平台无法对话。'],
                        ['title' => '移交不一致', 'description' => '每个站点拿到的文档与支持标准都不同。'],
                    ],
                    'approach' => [
                        ['title' => '接口地图', 'description' => '构建前写明每项依赖、协议、责任人与测试。'],
                        ['title' => '集成层', 'description' => '用受控边界取代数百条点对点连接。'],
                        ['title' => '端到端测试', 'description' => '跨供应商场景测试，而非只做单包工厂验收。'],
                        ['title' => '运营与支持', 'description' => '单一支持模型，明确升级与变更控制。'],
                    ],
                    'capabilities' => [
                        ['title' => 'OT/IT 集成', 'description' => '控制与企业系统之间安全、可观测的数据交换。'],
                        ['title' => '中间件', 'description' => 'MQTT、OPC UA、API 与事件编排，契约清晰。'],
                        ['title' => '迁移', 'description' => '从旧接口分阶段过渡，具备回滚计划。'],
                        ['title' => '托管支持', 'description' => '按约定服务模式进行监控、补丁与改进。'],
                    ],
                    'benefits' => [
                        ['title' => '单一责任主体', 'description' => '一个团队对端到端行为负责。'],
                        ['title' => '更快定位故障', 'description' => '可观测接口指出问题真正所在。'],
                        ['title' => '可复用标准', 'description' => '下一个站点从已验证模式开始。'],
                        ['title' => '更低全生命周期成本', 'description' => '有文档的契约减少返工与供应商锁定。'],
                    ],
                ],
            ],
        ];
    }

    protected function solutionTranslations(string $key): array
    {
        return $this->solutions()[$key];
    }

    protected function industryTranslations(string $key): array
    {
        $data = [
            'manufacturing' => [
                'en' => [
                    'title' => 'Manufacturing',
                    'slug' => 'manufacturing',
                    'tagline' => 'Stable output, traceable quality, and changeovers that do not cost a shift.',
                    'overview' => 'Discrete and process manufacturers are being asked to increase variety, reduce inventory, and prove quality — often on equipment that was designed for a narrower world. We help teams make the existing plant more capable before replacing it.',
                    'challenges' => [
                        ['title' => 'High-mix demand', 'description' => 'More variants and shorter runs expose changeover and scheduling weaknesses.'],
                        ['title' => 'Skills concentration', 'description' => 'Critical knowledge sits with a small number of experienced operators and engineers.'],
                        ['title' => 'Quality escapes', 'description' => 'Inspection is disconnected from process context, so containment is slow.'],
                    ],
                    'capabilities' => ['Process automation and robotics', 'OEE and traceability', 'Quality analytics', 'Energy and utility optimisation'],
                    'stats' => [
                        ['value' => '42%', 'label' => 'Less unplanned downtime', 'note' => 'Illustrative portfolio metric'],
                        ['value' => '6.8×', 'label' => 'Faster changeover', 'note' => 'Illustrative portfolio metric'],
                        ['value' => '99.2%', 'label' => 'Traceability coverage', 'note' => 'Illustrative portfolio metric'],
                    ],
                ],
                'th' => [
                    'title' => 'การผลิต',
                    'slug' => 'การผลิต',
                    'tagline' => 'ผลผลิตที่มั่นคง คุณภาพที่ตรวจสอบได้ และการเปลี่ยนรุ่นที่ไม่ต้องเสียทั้งกะ',
                    'overview' => 'ผู้ผลิตทั้งแบบชิ้นส่วนและกระบวนการต้องเพิ่มความหลากหลาย ลดสินค้าคงคลัง และพิสูจน์คุณภาพ บ่อยครั้งบนเครื่องจักรที่ออกแบบมาสำหรับโลกที่แคบกว่าเรา เราช่วยให้ทีมเพิ่มศักยภาพโรงงานเดิมก่อนลงทุนทดแทน',
                    'challenges' => [
                        ['title' => 'ความต้องการหลากหลายสูง', 'description' => 'รุ่นสินค้าที่มากขึ้นและล็อตที่สั้นลงเผยจุดอ่อนของการเปลี่ยนรุ่นและการจัดตารางผลิต'],
                        ['title' => 'ความรู้กระจุกตัว', 'description' => 'ความรู้สำคัญอยู่ในผู้ปฏิบัติงานและวิศวกรที่มีประสบการณ์เพียงไม่กี่คน'],
                        ['title' => 'ข้อบกพร่องหลุดถึงลูกค้า', 'description' => 'การตรวจสอบแยกจากบริบทกระบวนการ ทำให้ควบคุมขอบเขตได้ช้า'],
                    ],
                    'capabilities' => ['ระบบอัตโนมัติและหุ่นยนต์', 'OEE และการตรวจสอบย้อนกลับ', 'การวิเคราะห์คุณภาพ', 'การเพิ่มประสิทธิภาพพลังงาน'],
                    'stats' => [
                        ['value' => '42%', 'label' => 'เวลาหยุดโดยไม่วางแผนลดลง', 'note' => 'ตัวเลขตัวอย่างเพื่อการสาธิต'],
                        ['value' => '6.8×', 'label' => 'เปลี่ยนรุ่นเร็วขึ้น', 'note' => 'ตัวเลขตัวอย่างเพื่อการสาธิต'],
                        ['value' => '99.2%', 'label' => 'ความครอบคลุมการตรวจสอบย้อนกลับ', 'note' => 'ตัวเลขตัวอย่างเพื่อการสาธิต'],
                    ],
                ],
                'zh-CN' => [
                    'title' => '制造业',
                    'slug' => 'manufacturing',
                    'tagline' => '稳定产出、可追溯质量，以及不占用整班的换型。',
                    'overview' => '离散与流程制造企业被要求提升品种、降低库存并证明质量，而设备往往是为更单一的世界设计。我们帮助团队先提升现有工厂能力，再考虑替换。',
                    'challenges' => [
                        ['title' => '多品种需求', 'description' => '更多型号与更短批量暴露换型与排程弱点。'],
                        ['title' => '技能集中', 'description' => '关键知识集中在少数资深操作员与工程师身上。'],
                        ['title' => '质量逸出', 'description' => '检测与工艺上下文割裂，遏制缓慢。'],
                    ],
                    'capabilities' => ['工艺自动化与机器人', 'OEE 与追溯', '质量分析', '能源与公用工程优化'],
                    'stats' => [
                        ['value' => '42%', 'label' => '非计划停机减少', 'note' => '示例组合指标'],
                        ['value' => '6.8×', 'label' => '换型更快', 'note' => '示例组合指标'],
                        ['value' => '99.2%', 'label' => '追溯覆盖率', 'note' => '示例组合指标'],
                    ],
                ],
            ],
            'logistics-distribution' => [
                'en' => [
                    'title' => 'Logistics & Distribution',
                    'slug' => 'logistics-distribution',
                    'tagline' => 'Throughput that holds under peak, with inventory you can actually see.',
                    'overview' => 'Distribution operations succeed or fail at the seams: inbound receipt, put-away, picking, dispatch, and reconciliation. We engineer those seams so peak volume does not turn into permanent improvisation.',
                    'challenges' => [
                        ['title' => 'Peak variability', 'description' => 'Seasonal volume exposes labour, dock, and conveyor constraints.'],
                        ['title' => 'Manual reconciliation', 'description' => 'Inventory accuracy depends on nightly correction cycles.'],
                        ['title' => 'Space constraints', 'description' => 'Growth requires denser storage without slowing picking.'],
                    ],
                    'capabilities' => ['Conveyor and sortation control', 'Warehouse execution integration', 'Inventory visibility', 'Dock and yard orchestration'],
                    'stats' => [
                        ['value' => '31%', 'label' => 'Higher peak throughput', 'note' => 'Illustrative portfolio metric'],
                        ['value' => '99.4%', 'label' => 'Inventory accuracy', 'note' => 'Illustrative portfolio metric'],
                        ['value' => '18%', 'label' => 'Lower energy per order', 'note' => 'Illustrative portfolio metric'],
                    ],
                ],
                'th' => [
                    'title' => 'โลจิสติกส์และการกระจายสินค้า',
                    'slug' => 'โลจิสติกส์และการกระจายสินค้า',
                    'tagline' => 'กำลังการผลิตที่รับช่วงพีคได้ พร้อมสินค้าคงคลังที่มองเห็นจริง',
                    'overview' => 'การกระจายสินค้าสำเร็จหรือล้มเหลวที่รอยต่อ ตั้งแต่รับสินค้าเข้าคลัง เก็บเข้าที่ หยิบสินค้า จัดส่ง และกระทบยอด เราเข้ามาออกแบบรอยต่อเหล่านี้เพื่อให้ช่วงพีคไม่กลายเป็นการแก้ปัญหาเฉพาะหน้าถาวร',
                    'challenges' => [
                        ['title' => 'ปริมาณผันผวนตามฤดูกาล', 'description' => 'ปริมาณที่พุ่งสูงเผยข้อจำกัดของแรงงาน ท่าเทียบ และสายพาน'],
                        ['title' => 'กระทบยอดด้วยมือ', 'description' => 'ความแม่นยำสินค้าคงคลังพึ่งพาการแก้ไขทุกคืน'],
                        ['title' => 'พื้นที่จำกัด', 'description' => 'การเติบโตต้องใช้พื้นที่จัดเก็บหนาแน่นขึ้นโดยไม่ทำให้หยิบช้าลง'],
                    ],
                    'capabilities' => ['ควบคุมสายพานและระบบคัดแยก', 'เชื่อมระบบบริหารคลัง', 'มองเห็นสินค้าคงคลัง', 'จัดการท่าเทียบและลานจอด'],
                    'stats' => [
                        ['value' => '31%', 'label' => 'กำลังการผลิตช่วงพีคสูงขึ้น', 'note' => 'ตัวเลขตัวอย่างเพื่อการสาธิต'],
                        ['value' => '99.4%', 'label' => 'ความแม่นยำสินค้าคงคลัง', 'note' => 'ตัวเลขตัวอย่างเพื่อการสาธิต'],
                        ['value' => '18%', 'label' => 'พลังงานต่อออเดอร์ลดลง', 'note' => 'ตัวเลขตัวอย่างเพื่อการสาธิต'],
                    ],
                ],
                'zh-CN' => [
                    'title' => '物流与配送',
                    'slug' => 'logistics-distribution',
                    'tagline' => '峰值下依然稳定的吞吐，以及真正可见的库存。',
                    'overview' => '配送运营成败在接缝：收货、上架、拣选、发运与核对。我们设计这些接缝，让峰值不再变成长期临时应对。',
                    'challenges' => [
                        ['title' => '峰值波动', 'description' => '季节性货量暴露人力、月台与输送线约束。'],
                        ['title' => '人工核对', 'description' => '库存准确率依赖夜间修正。'],
                        ['title' => '空间受限', 'description' => '增长需要更密集存储，同时不能拖慢拣选。'],
                    ],
                    'capabilities' => ['输送与分拣控制', '仓储执行集成', '库存可视化', '月台与场院调度'],
                    'stats' => [
                        ['value' => '31%', 'label' => '峰值吞吐提升', 'note' => '示例组合指标'],
                        ['value' => '99.4%', 'label' => '库存准确率', 'note' => '示例组合指标'],
                        ['value' => '18%', 'label' => '单订单能耗下降', 'note' => '示例组合指标'],
                    ],
                ],
            ],
            'energy-utilities' => [
                'en' => [
                    'title' => 'Energy & Utilities',
                    'slug' => 'energy-utilities',
                    'tagline' => 'Reliable supply, visible losses, and a credible path to lower emissions.',
                    'overview' => 'Energy and utility operators carry two mandates at once: keep supply secure today and decarbonise over time. We instrument the network, improve control, and prioritise capital so both mandates use the same evidence.',
                    'challenges' => [
                        ['title' => 'Ageing infrastructure', 'description' => 'Protection and control systems outlive their supportability.'],
                        ['title' => 'Outage risk', 'description' => 'Failure modes are known but not quantified at the feeder or asset level.'],
                        ['title' => 'Decarbonisation pressure', 'description' => 'Targets exist without a sequenced, measurable investment plan.'],
                    ],
                    'capabilities' => ['Substation and protection engineering', 'SCADA and grid monitoring', 'Loss and demand analytics', 'Renewable integration'],
                    'stats' => [
                        ['value' => '27%', 'label' => 'Fewer supply interruptions', 'note' => 'Illustrative portfolio metric'],
                        ['value' => '14%', 'label' => 'Lower technical losses', 'note' => 'Illustrative portfolio metric'],
                        ['value' => '3.1×', 'label' => 'Faster fault restoration', 'note' => 'Illustrative portfolio metric'],
                    ],
                ],
                'th' => [
                    'title' => 'พลังงานและสาธารณูปโภค',
                    'slug' => 'พลังงานและสาธารณูปโภค',
                    'tagline' => 'จ่ายไฟที่เชื่อถือได้ เห็นความสูญเสียชัดเจน และเส้นทางลดคาร์บอนที่น่าเชื่อถือ',
                    'overview' => 'ผู้ประกอบการพลังงานและสาธารณูปโภคแบกสองพันธกิจพร้อมกัน คือรักษาความมั่นคงของการจ่ายวันนี้และลดคาร์บอนในระยะยาว เราติดตั้งการวัด ปรับปรุงการควบคุม และจัดลำดับการลงทุนให้ทั้งสองพันธกิจใช้หลักฐานชุดเดียวกัน',
                    'challenges' => [
                        ['title' => 'โครงสร้างพื้นฐานเสื่อมอายุ', 'description' => 'ระบบป้องกันและควบคุมเกินอายุที่ยังได้รับการสนับสนุน'],
                        ['title' => 'ความเสี่ยงไฟดับ', 'description' => 'รู้โหมดความล้มเหลวแต่ยังไม่วัดเชิงปริมาณในระดับฟีดเดอร์หรือสินทรัพย์'],
                        ['title' => 'แรงกดดันลดคาร์บอน', 'description' => 'มีเป้าหมายแต่ไม่มีแผนลงทุนที่จัดลำดับและวัดผลได้'],
                    ],
                    'capabilities' => ['วิศวกรรมสถานีไฟฟ้าและการป้องกัน', 'SCADA และการตรวจสอบโครงข่าย', 'วิเคราะห์ความสูญเสียและความต้องการ', 'เชื่อมต่อพลังงานหมุนเวียน'],
                    'stats' => [
                        ['value' => '27%', 'label' => 'เหตุจ่ายไฟขัดข้องลดลง', 'note' => 'ตัวเลขตัวอย่างเพื่อการสาธิต'],
                        ['value' => '14%', 'label' => 'ความสูญเสียทางเทคนิคต่ำลง', 'note' => 'ตัวเลขตัวอย่างเพื่อการสาธิต'],
                        ['value' => '3.1×', 'label' => 'กู้คืนระบบเร็วขึ้น', 'note' => 'ตัวเลขตัวอย่างเพื่อการสาธิต'],
                    ],
                ],
                'zh-CN' => [
                    'title' => '能源与公用事业',
                    'slug' => 'energy-utilities',
                    'tagline' => '可靠供应、可见损耗，以及可信的减排路径。',
                    'overview' => '能源与公用事业同时承担两项任务：保障今天的供应安全，并逐步脱碳。我们部署计量、改进控制并排序资本投入，让两项任务共享同一套证据。',
                    'challenges' => [
                        ['title' => '基础设施老化', 'description' => '保护与控制系统超出可支持寿命。'],
                        ['title' => '停电风险', 'description' => '失效模式已知，但未在馈线或资产层面量化。'],
                        ['title' => '脱碳压力', 'description' => '目标存在，却缺少可排序、可衡量的投资计划。'],
                    ],
                    'capabilities' => ['变电站与保护工程', 'SCADA 与电网监测', '损耗与需求分析', '可再生能源接入'],
                    'stats' => [
                        ['value' => '27%', 'label' => '供电中断减少', 'note' => '示例组合指标'],
                        ['value' => '14%', 'label' => '技术损耗降低', 'note' => '示例组合指标'],
                        ['value' => '3.1×', 'label' => '故障恢复更快', 'note' => '示例组合指标'],
                    ],
                ],
            ],
            'infrastructure' => [
                'en' => [
                    'title' => 'Infrastructure',
                    'slug' => 'infrastructure',
                    'tagline' => 'Critical assets designed for the handover, not only the opening.',
                    'overview' => 'Infrastructure projects are judged decades after commissioning. We focus on interface management, commissioning evidence, and the operating model that keeps an asset useful long after the project team has left.',
                    'challenges' => [
                        ['title' => 'Interface complexity', 'description' => 'Many packages, one operating outcome, and unclear accountability at the seams.'],
                        ['title' => 'Long asset life', 'description' => 'Design decisions lock in maintenance cost for decades.'],
                        ['title' => 'Handover gaps', 'description' => 'Documentation and training arrive too late to shape safe operation.'],
                    ],
                    'capabilities' => ['Interface and integration management', 'Electrical and utility systems', 'Commissioning and assurance', 'Operating model design'],
                    'stats' => [
                        ['value' => '94%', 'label' => 'First-time commissioning pass', 'note' => 'Illustrative portfolio metric'],
                        ['value' => '21%', 'label' => 'Shorter handover period', 'note' => 'Illustrative portfolio metric'],
                        ['value' => '100%', 'label' => 'As-built documentation coverage', 'note' => 'Illustrative portfolio metric'],
                    ],
                ],
                'th' => [
                    'title' => 'โครงสร้างพื้นฐาน',
                    'slug' => 'โครงสร้างพื้นฐาน',
                    'tagline' => 'สินทรัพย์สำคัญที่ออกแบบเพื่อการส่งมอบ ไม่ใช่แค่พิธีเปิด',
                    'overview' => 'โครงการโครงสร้างพื้นฐานถูกตัดสินหลายทศวรรษหลังการทดสอบระบบ เราจึงให้ความสำคัญกับการจัดการอินเทอร์เฟซ หลักฐานการทดสอบ และโมเดลการดำเนินงานที่ทำให้สินทรัพย์ยังมีประโยชน์หลังทีมโครงการจากไป',
                    'challenges' => [
                        ['title' => 'อินเทอร์เฟซซับซ้อน', 'description' => 'หลายแพ็กเกจ แต่ผลลัพธ์การดำเนินงานเดียว และความรับผิดชอบที่รอยต่อไม่ชัด'],
                        ['title' => 'อายุสินทรัพย์ยาวนาน', 'description' => 'การตัดสินใจออกแบบล็อกต้นทุนบำรุงรักษาไปหลายทศวรรษ'],
                        ['title' => 'ช่องว่างการส่งมอบ', 'description' => 'เอกสารและการฝึกอบรมมาช้าเกินกว่าจะกำหนดการปฏิบัติงานที่ปลอดภัย'],
                    ],
                    'capabilities' => ['จัดการอินเทอร์เฟซและการบูรณาการ', 'ระบบไฟฟ้าและสาธารณูปโภค', 'ทดสอบและประกันคุณภาพ', 'ออกแบบโมเดลการดำเนินงาน'],
                    'stats' => [
                        ['value' => '94%', 'label' => 'ทดสอบผ่านครั้งแรก', 'note' => 'ตัวเลขตัวอย่างเพื่อการสาธิต'],
                        ['value' => '21%', 'label' => 'ระยะส่งมอบสั้นลง', 'note' => 'ตัวเลขตัวอย่างเพื่อการสาธิต'],
                        ['value' => '100%', 'label' => 'ความครบของเอกสาร as-built', 'note' => 'ตัวเลขตัวอย่างเพื่อการสาธิต'],
                    ],
                ],
                'zh-CN' => [
                    'title' => '基础设施',
                    'slug' => 'infrastructure',
                    'tagline' => '为移交而非仅剪彩设计的关键资产。',
                    'overview' => '基础设施在调试数十年后才被真正评判。我们聚焦接口管理、调试证据，以及项目团队离开后仍让资产有用的运营模型。',
                    'challenges' => [
                        ['title' => '接口复杂', 'description' => '多个合同包、一个运营结果，接缝处责任不清。'],
                        ['title' => '资产寿命长', 'description' => '设计决策锁定数十年的维护成本。'],
                        ['title' => '移交缺口', 'description' => '文档与培训来得太晚，无法塑造安全运营。'],
                    ],
                    'capabilities' => ['接口与集成管理', '电气与公用工程系统', '调试与保证', '运营模型设计'],
                    'stats' => [
                        ['value' => '94%', 'label' => '一次调试通过率', 'note' => '示例组合指标'],
                        ['value' => '21%', 'label' => '移交周期缩短', 'note' => '示例组合指标'],
                        ['value' => '100%', 'label' => '竣工文档覆盖', 'note' => '示例组合指标'],
                    ],
                ],
            ],
            'commercial-industrial-property' => [
                'en' => [
                    'title' => 'Commercial & Industrial Property',
                    'slug' => 'commercial-industrial-property',
                    'tagline' => 'Buildings that stay comfortable, efficient, and resilient under real load.',
                    'overview' => 'Property owners and operators need tenants to stay, energy costs to behave, and systems to remain maintainable. We connect building services, metering, and controls so performance is visible and responsibility is clear.',
                    'challenges' => [
                        ['title' => 'Energy cost exposure', 'description' => 'Consumption is measured at the building level, not at the system that drives it.'],
                        ['title' => 'Tenant comfort complaints', 'description' => 'Controls respond to sensors that no longer reflect the occupied space.'],
                        ['title' => 'Reactive maintenance', 'description' => 'Critical plant fails before anyone sees the trend.'],
                    ],
                    'capabilities' => ['MEP systems engineering', 'Building controls and metering', 'Energy performance', 'Resilience and backup power'],
                    'stats' => [
                        ['value' => '23%', 'label' => 'Lower energy intensity', 'note' => 'Illustrative portfolio metric'],
                        ['value' => '38%', 'label' => 'Fewer comfort complaints', 'note' => 'Illustrative portfolio metric'],
                        ['value' => '99.9%', 'label' => 'Critical power availability', 'note' => 'Illustrative portfolio metric'],
                    ],
                ],
                'th' => [
                    'title' => 'อสังหาริมทรัพย์เชิงพาณิชย์และอุตสาหกรรม',
                    'slug' => 'อสังหาริมทรัพย์เชิงพาณิชย์และอุตสาหกรรม',
                    'tagline' => 'อาคารที่สบาย มีประสิทธิภาพ และทนทานภายใต้ภาระจริง',
                    'overview' => 'เจ้าของและผู้ดูแลอสังหาริมทรัพย์ต้องการให้ผู้เช่าอยู่ต่อ ต้นทุนพลังงานอยู่ในกรอบ และระบบยังบำรุงรักษาได้ เราเชื่อมระบบอาคาร มาตรวัด และการควบคุมให้ประสิทธิภาพมองเห็นได้และความรับผิดชอบชัดเจน',
                    'challenges' => [
                        ['title' => 'ความเสี่ยงต้นทุนพลังงาน', 'description' => 'วัดการใช้พลังงานระดับอาคาร ไม่ใช่ระดับระบบที่ทำให้เกิด'],
                        ['title' => 'ข้อร้องเรียนเรื่องความสบาย', 'description' => 'ระบบควบคุมตอบสนองเซนเซอร์ที่ไม่สะท้อนพื้นที่ใช้งานจริง'],
                        ['title' => 'บำรุงรักษาเชิงรับ', 'description' => 'ระบบสำคัญเสียก่อนที่ใครจะเห็นแนวโน้ม'],
                    ],
                    'capabilities' => ['วิศวกรรมระบบ MEP', 'ควบคุมอาคารและมาตรวัด', 'ประสิทธิภาพพลังงาน', 'ความทนทานและไฟสำรอง'],
                    'stats' => [
                        ['value' => '23%', 'label' => 'ความเข้มการใช้พลังงานต่ำลง', 'note' => 'ตัวเลขตัวอย่างเพื่อการสาธิต'],
                        ['value' => '38%', 'label' => 'ข้อร้องเรียนลดลง', 'note' => 'ตัวเลขตัวอย่างเพื่อการสาธิต'],
                        ['value' => '99.9%', 'label' => 'ความพร้อมไฟฟ้าสำคัญ', 'note' => 'ตัวเลขตัวอย่างเพื่อการสาธิต'],
                    ],
                ],
                'zh-CN' => [
                    'title' => '商业与工业物业',
                    'slug' => 'commercial-industrial-property',
                    'tagline' => '在真实负荷下保持舒适、高效与韧性的建筑。',
                    'overview' => '业主与运营方需要留住租户、控制能源成本，并让系统可维护。我们连接楼宇服务、计量与控制，让绩效可见、责任清晰。',
                    'challenges' => [
                        ['title' => '能源成本敞口', 'description' => '只在建筑层面计量，未细分到驱动系统。'],
                        ['title' => '租户舒适投诉', 'description' => '控制响应的是不再代表实际空间的传感器。'],
                        ['title' => '被动维护', 'description' => '关键设备在有人看到趋势前已失效。'],
                    ],
                    'capabilities' => ['MEP 系统工程', '楼宇控制与计量', '能源绩效', '韧性与备用电源'],
                    'stats' => [
                        ['value' => '23%', 'label' => '能耗强度降低', 'note' => '示例组合指标'],
                        ['value' => '38%', 'label' => '舒适投诉减少', 'note' => '示例组合指标'],
                        ['value' => '99.9%', 'label' => '关键电源可用率', 'note' => '示例组合指标'],
                    ],
                ],
            ],
        ];

        return $data[$key];
    }
}
