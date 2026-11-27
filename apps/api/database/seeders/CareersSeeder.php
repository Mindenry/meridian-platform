<?php

namespace Database\Seeders;

use App\Models\JobPosting;
use App\Models\Site;
use Illuminate\Database\Seeder;

class CareersSeeder extends Seeder
{
    public function run(): void
    {
        $site = Site::where('slug', 'meridian')->firstOrFail();

        $jobs = [
            [
                'key' => 'senior-automation-engineer', 'department' => 'Industrial automation', 'location' => 'Rayong', 'type' => 'full_time', 'sort' => 1,
                'en' => [
                    'title' => 'Senior Automation Engineer',
                    'slug' => 'senior-automation-engineer',
                    'summary' => 'Lead the control architecture and commissioning of brownfield automation projects across the Eastern Seaboard.',
                    'description' => $this->doc('You will own the technical shape of automation projects from survey to handover, working closely with plant teams and vendors. This is a hands-on role: design reviews, factory acceptance testing, commissioning, and the difficult conversations that make a system reliable.'),
                    'responsibilities' => ['Define PLC, safety, and network architectures for multi-vendor projects.', 'Lead factory acceptance testing and staged commissioning.', 'Review vendor designs and protect the integrity of the agreed interface.', 'Mentor junior engineers and technicians on live sites.', 'Document decisions, assumptions, and residual risks.'],
                    'requirements' => ['6+ years in industrial automation or controls engineering.', 'Strong PLC and HMI experience across at least two platforms.', 'Working knowledge of functional safety and machine guarding.', 'Comfortable working in active production environments.', 'Professional Thai and working English.'],
                    'benefits' => ['Structured technical mentoring', 'Project completion leave', 'Health and accident insurance', 'Annual capability budget'],
                ],
                'th' => [
                    'title' => 'วิศวกรระบบอัตโนมัติอาวุโส',
                    'slug' => 'senior-automation-engineer',
                    'summary' => 'นำการออกแบบสถาปัตยกรรมควบคุมและทดสอบระบบในโครงการอัตโนมัติโรงงานเดิมทั่วพื้นที่ชายฝั่งตะวันออก',
                    'description' => $this->doc('คุณจะรับผิดชอบรูปร่างทางเทคนิคของโครงการอัตโนมัติตั้งแต่การสำรวจจนถึงส่งมอบ ทำงานใกล้ชิดกับทีมโรงงานและผู้ขาย บทบาทนี้ลงมือปฏิบัติจริง ทั้งการทบทวนแบบ ทดสอบที่โรงงาน ทดสอบระบบ และบทสนทนาที่ยากซึ่งทำให้ระบบเชื่อถือได้'),
                    'responsibilities' => ['กำหนดสถาปัตยกรรม PLC ความปลอดภัย และเครือข่ายสำหรับโครงการหลายผู้ขาย', 'นำการทดสอบที่โรงงานและการทดสอบระบบเป็นระยะ', 'ทบทวนแบบผู้ขายและรักษาความสมบูรณ์ของอินเทอร์เฟซที่ตกลง', 'ให้คำแนะนำวิศวกรและช่างเทคนิครุ่นใหม่ในพื้นที่จริง', 'จัดทำเอกสารการตัดสินใจ สมมติฐาน และความเสี่ยงคงเหลือ'],
                    'requirements' => ['ประสบการณ์ 6 ปีขึ้นไปด้านระบบอัตโนมัติหรือวิศวกรรมควบคุม', 'ประสบการณ์ PLC และ HMI ที่แข็งแกร่งอย่างน้อยสองแพลตฟอร์ม', 'ความรู้ด้าน functional safety และการป้องกันเครื่องจักร', 'ทำงานในสภาพแวดล้อมการผลิตที่เดินเครื่องได้', 'ภาษาไทยprofessional และอังกฤษใช้งานได้'],
                    'benefits' => ['การเป็นพี่เลี้ยงทางเทคนิคอย่างเป็นระบบ', 'วันลาหลังจบโครงการ', 'ประกันสุขภาพและอุบัติเหตุ', 'งบพัฒนาศักยภาพรายปี'],
                ],
            ],
            [
                'key' => 'industrial-software-engineer', 'department' => 'Digital systems', 'location' => 'Bangkok', 'type' => 'full_time', 'sort' => 2,
                'en' => [
                    'title' => 'Industrial Software Engineer',
                    'slug' => 'industrial-software-engineer',
                    'summary' => 'Build operational software that operators actually use, from data model to interface and deployment.',
                    'description' => $this->doc('You will work across the stack on production portals, quality workflows, and integration services. The role suits an engineer who cares about domain modelling and interface clarity as much as framework choice, and who is willing to spend time on the plant floor.'),
                    'responsibilities' => ['Model operational domains and design APIs with the team.', 'Build Vue and Laravel applications for production and quality teams.', 'Integrate OT data sources with clear contracts and observability.', 'Test with operators and iterate on real shift constraints.', 'Contribute to deployment, monitoring, and support routines.'],
                    'requirements' => ['4+ years building production web applications.', 'Strong TypeScript and either Laravel or a comparable backend framework.', 'Experience with SQL, APIs, and automated testing.', 'Interest in industrial operations and human factors.', 'Professional Thai and working English.'],
                    'benefits' => ['Hybrid working with site time', 'Learning budget', 'Health insurance', 'Paid volunteer day'],
                ],
                'th' => [
                    'title' => 'วิศวกรซอฟต์แวร์อุตสาหกรรม',
                    'slug' => 'industrial-software-engineer',
                    'summary' => 'สร้างซอฟต์แวร์ปฏิบัติการที่ผู้ใช้งานจริงเลือกใช้ ตั้งแต่โมเดลข้อมูลถึงอินเทอร์เฟซและการติดตั้ง',
                    'description' => $this->doc('คุณจะทำงานทั้งสแตกบนพอร์ทัลการผลิต เวิร์กโฟลว์คุณภาพ และบริการเชื่อมต่อ บทบาทนี้เหมาะกับวิศวกรที่ใส่ใจการสร้างโมเดลโดเมนและความชัดเจนของอินเทอร์เฟซพอๆ กับการเลือกเฟรมเวิร์ก และยินดีลงพื้นที่โรงงาน'),
                    'responsibilities' => ['สร้างโมเดลโดเมนปฏิบัติการและออกแบบ API ร่วมกับทีม', 'พัฒนาแอปพลิเคชัน Vue และ Laravel สำหรับทีมผลิตและคุณภาพ', 'เชื่อมแหล่งข้อมูล OT ด้วยสัญญาและการสังเกตที่ชัดเจน', 'ทดสอบกับผู้ปฏิบัติงานและปรับตามข้อจำกัดของกะจริง', 'มีส่วนร่วมในขั้นตอนติดตั้ง ตรวจสอบ และสนับสนุน'],
                    'requirements' => ['ประสบการณ์ 4 ปีขึ้นไปในการสร้างเว็บแอปพลิเคชันใช้งานจริง', 'TypeScript ที่แข็งแกร่ง และ Laravel หรือแบ็กเอนด์ที่เทียบเท่า', 'ประสบการณ์ SQL, API และการทดสอบอัตโนมัติ', 'สนใจการดำเนินงานอุตสาหกรรมและปัจจัยมนุษย์', 'ภาษาไทยprofessional และอังกฤษใช้งานได้'],
                    'benefits' => ['ทำงานไฮบริดพร้อมเวลาลงพื้นที่', 'งบการเรียนรู้', 'ประกันสุขภาพ', 'วันอาสาสมัครแบบได้รับค่าจ้าง'],
                ],
            ],
            [
                'key' => 'project-manager-infrastructure', 'department' => 'Infrastructure', 'location' => 'Bangkok', 'type' => 'full_time', 'sort' => 3,
                'en' => [
                    'title' => 'Project Manager, Infrastructure',
                    'slug' => 'project-manager-infrastructure',
                    'summary' => 'Run multi-package infrastructure projects with disciplined interfaces, live operations, and a clean handover.',
                    'description' => $this->doc('You will manage electrical and utility projects where the plant cannot stop. Success means predictable shutdown windows, honest reporting, coordinated vendors, and an operating team that is ready on day one.'),
                    'responsibilities' => ['Own scope, schedule, cost, and interface management.', 'Coordinate vendors and internal engineering disciplines.', 'Plan shutdowns around live operations and contingency.', 'Report progress with evidence, not optimism.', 'Lead commissioning and handover acceptance.'],
                    'requirements' => ['7+ years managing industrial or infrastructure projects.', 'Experience with live-plant shutdown planning.', 'Strong commercial and contract awareness.', 'Clear written and verbal communication in Thai and English.', 'Engineering or construction management qualification.'],
                    'benefits' => ['Project bonus scheme', 'Vehicle allowance', 'Health insurance', 'Structured project leadership coaching'],
                ],
                'th' => [
                    'title' => 'ผู้จัดการโครงการ โครงสร้างพื้นฐาน',
                    'slug' => 'project-manager-infrastructure',
                    'summary' => 'บริหารโครงการโครงสร้างพื้นฐานหลายแพ็กเกจด้วยการจัดการอินเทอร์เฟซที่เข้มงวด การดำเนินงานที่เดินอยู่ และการส่งมอบที่เรียบร้อย',
                    'description' => $this->doc('คุณจะบริหารโครงการไฟฟ้าและสาธารณูปโภคที่โรงงานหยุดไม่ได้ ความสำเร็จหมายถึงช่วงหยุดที่คาดการณ์ได้ รายงานที่ซื่อตรง ผู้ขายที่ประสานกัน และทีมปฏิบัติการที่พร้อมตั้งแต่วันแรก'),
                    'responsibilities' => ['รับผิดชอบขอบเขต ตารางเวลา ต้นทุน และการจัดการอินเทอร์เฟซ', 'ประสานผู้ขายและสาขาวิชาวิศวกรรมภายใน', 'วางแผนการหยุดระบบรอบการดำเนินงานจริงและแผนสำรอง', 'รายงานความคืบหน้าด้วยหลักฐานไม่ใช่ความหวัง', 'นำการทดสอบระบบและการรับมอบ'],
                    'requirements' => ['ประสบการณ์ 7 ปีขึ้นไปในการบริหารโครงการอุตสาหกรรมหรือโครงสร้างพื้นฐาน', 'ประสบการณ์วางแผนหยุดระบบในโรงงานที่เดินอยู่', 'ความเข้าใจเชิงพาณิชย์และสัญญาที่แข็งแกร่ง', 'สื่อสารไทยและอังกฤษได้ชัดเจนทั้งเขียนและพูด', 'วุฒิวิศวกรรมหรือการบริหารงานก่อสร้าง'],
                    'benefits' => ['โบนัสโครงการ', 'ค่าเบี้ยรถ', 'ประกันสุขภาพ', 'การโค้ชผู้นำโครงการอย่างเป็นระบบ'],
                ],
            ],
            [
                'key' => 'data-analytics-consultant', 'department' => 'Digital systems', 'location' => 'Bangkok', 'type' => 'full_time', 'sort' => 4,
                'en' => [
                    'title' => 'Data & Analytics Consultant',
                    'slug' => 'data-analytics-consultant',
                    'summary' => 'Turn messy operational data into definitions, models, and decisions that survive contact with a real shift.',
                    'description' => $this->doc('You will work between operations and engineering: defining metrics, tracing lineage, building models, and helping teams change the way they decide. The work is as much facilitation as it is SQL.'),
                    'responsibilities' => ['Run metric definition workshops with operations.', 'Design curated data models and lineage documentation.', 'Build analyses and decision support for production and quality.', 'Test data quality assumptions and surface gaps honestly.', 'Support adoption through training and operating routines.'],
                    'requirements' => ['4+ years in analytics, data engineering, or industrial data.', 'Advanced SQL and a modern BI or notebook workflow.', 'Experience with operational or manufacturing data.', 'Ability to explain uncertainty to non-specialists.', 'Professional Thai and working English.'],
                    'benefits' => ['Hybrid working', 'Conference budget', 'Health insurance', 'Mentoring from senior practice leads'],
                ],
                'th' => [
                    'title' => 'ที่ปรึกษาข้อมูลและการวิเคราะห์',
                    'slug' => 'data-analytics-consultant',
                    'summary' => 'เปลี่ยนข้อมูลปฏิบัติการที่กระจัดกระจายเป็นนิยาม โมเดล และการตัดสินใจที่ทนต่อกะจริง',
                    'description' => $this->doc('คุณจะทำงานระหว่างปฏิบัติการและวิศวกรรม ทั้งการนิยามตัวชี้วัด การติดตามสายข้อมูล การสร้างโมเดล และช่วยทีมเปลี่ยนวิธีตัดสินใจ งานนี้ใช้การอำนวยความสะดวกพอๆ กับ SQL'),
                    'responsibilities' => ['อำนวยเวิร์กช็อปนิยามตัวชี้วัดร่วมกับปฏิบัติการ', 'ออกแบบโมเดลข้อมูล curated และเอกสารสายข้อมูล', 'สร้างการวิเคราะห์และตัวช่วยตัดสินใจสำหรับการผลิตและคุณภาพ', 'ทดสอบสมมติฐานคุณภาพข้อมูลและเปิดเผยช่องว่างอย่างซื่อตรง', 'สนับสนุนการนำไปใช้ผ่านการฝึกอบรมและ routine'],
                    'requirements' => ['ประสบการณ์ 4 ปีขึ้นไปด้านการวิเคราะห์ วิศวกรรมข้อมูล หรือข้อมูลอุตสาหกรรม', 'SQL ระดับสูงและเครื่องมือ BI หรือ notebook สมัยใหม่', 'ประสบการณ์ข้อมูลการผลิตหรือปฏิบัติการ', 'อธิบายความไม่แน่นอนให้ผู้ที่ไม่ใช่ผู้เชี่ยวชาญได้', 'ภาษาไทยprofessional และอังกฤษใช้งานได้'],
                    'benefits' => ['ทำงานไฮบริด', 'งบประชุมวิชาการ', 'ประกันสุขภาพ', 'การเป็นพี่เลี้ยงจากหัวหน้าฝ่ายอาวุโส'],
                ],
            ],
            [
                'key' => 'hse-engineer', 'department' => 'People & culture', 'location' => 'Rayong', 'type' => 'full_time', 'sort' => 5,
                'en' => [
                    'title' => 'HSE Engineer',
                    'slug' => 'hse-engineer',
                    'summary' => 'Keep commissioning and live-site work safe without slowing the team down with paperwork theatre.',
                    'description' => $this->doc('You will lead health, safety, and environment practice across project sites, working with engineers, contractors, and plant teams. We are looking for someone who can make risk visible and practical, not someone who owns a folder of forms.'),
                    'responsibilities' => ['Lead site risk assessments and method statements.', 'Coach engineers and contractors on safe work practice.', 'Investigate incidents and near misses with clear actions.', 'Maintain legal and client HSE requirements.', 'Improve emergency and isolation routines.'],
                    'requirements' => ['5+ years in HSE within industrial or construction environments.', 'Knowledge of Thai HSE regulation and contractor management.', 'Strong field presence and calm communication.', 'Incident investigation and root-cause training.', 'Professional Thai and working English.'],
                    'benefits' => ['Site allowance', 'Health and accident insurance', 'Professional certification support', 'Five-day wellbeing leave'],
                ],
                'th' => [
                    'title' => 'วิศวกรความปลอดภัยอาชีวอนามัยและสิ่งแวดล้อม',
                    'slug' => 'hse-engineer',
                    'summary' => 'รักษาความปลอดภัยของงานทดสอบระบบและงานหน้างาน โดยไม่ทำให้ทีมช้าลงด้วยพิธีกรรมเอกสาร',
                    'description' => $this->doc('คุณจะนำแนวปฏิบัติด้านสุขภาพ ความปลอดภัย และสิ่งแวดล้อมในไซต์โครงการ ทำงานกับวิศวกร ผู้รับเหมา และทีมโรงงาน เรามองหาคนที่ทำให้ความเสี่ยงมองเห็นได้และปฏิบัติได้จริง ไม่ใช่แค่เจ้าของแฟ้มแบบฟอร์ม'),
                    'responsibilities' => ['นำการประเมินความเสี่ยงและ method statement ในไซต์', 'โค้ชวิศวกรและผู้รับเหมาเรื่องการทำงานอย่างปลอดภัย', 'สอบสวนเหตุการณ์และ near miss พร้อมการดำเนินการชัดเจน', 'รักษาข้อกำหนด HSE ทางกฎหมายและของลูกค้า', 'ปรับปรุง routine ฉุกเฉินและการแยกพลังงาน'],
                    'requirements' => ['ประสบการณ์ 5 ปีขึ้นไปด้าน HSE ในสภาพแวดล้อมอุตสาหกรรมหรือก่อสร้าง', 'ความรู้กฎหมาย HSE ไทยและการจัดการผู้รับเหมา', ' présence หน้างานที่แข็งแกร่งและการสื่อสารที่ calm', 'การฝึกอบรมสอบสวนเหตุและ root cause', 'ภาษาไทยprofessional และอังกฤษใช้งานได้'],
                    'benefits' => ['ค่าเบี้ยไซต์', 'ประกันสุขภาพและอุบัติเหตุ', 'สนับสนุนการรับรองวิชาชีพ', 'วันลาสุขภาพห้าวัน'],
                ],
            ],
        ];

        foreach ($jobs as $data) {
            $job = JobPosting::updateOrCreate(
                ['site_id' => $site->id, 'department' => $data['department'], 'location' => $data['location'], 'sort' => $data['sort']],
                [
                    'employment_type' => $data['type'],
                    'status' => 'open',
                    'posted_at' => now()->subDays(10 + $data['sort']),
                    'closes_at' => now()->addDays(50),
                ]
            );

            foreach (['en', 'th'] as $locale) {
                $translation = $data[$locale];
                $job->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'site_id' => $site->id,
                        'title' => $translation['title'],
                        'slug' => $translation['slug'],
                        'summary' => $translation['summary'],
                        'description' => $translation['description'],
                        'responsibilities' => $translation['responsibilities'],
                        'requirements' => $translation['requirements'],
                        'benefits' => $translation['benefits'],
                    ]
                );
            }
        }
    }

    protected function doc(string $paragraph): array
    {
        return [
            'type' => 'doc',
            'content' => [
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $paragraph]]],
            ],
        ];
    }
}
