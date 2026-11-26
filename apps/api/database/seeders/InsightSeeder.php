<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\MediaAsset;
use App\Models\Site;
use App\Models\Tag;
use App\Models\User;
use App\Support\RichTextSanitizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InsightSeeder extends Seeder
{
    public function run(): void
    {
        $site = Site::where('slug', 'meridian')->firstOrFail();
        $media = MediaAsset::where('site_id', $site->id)->get()->keyBy(fn (MediaAsset $asset) => pathinfo($asset->original_name, PATHINFO_FILENAME));
        $sanitizer = app(RichTextSanitizer::class);

        $categories = [
            'operations' => ['en' => 'Manufacturing & operations', 'th' => 'การผลิตและปฏิบัติการ', 'zh-CN' => '制造与运营', 'sort' => 1],
            'digital' => ['en' => 'Data & digital', 'th' => 'ข้อมูลและดิจิทัล', 'zh-CN' => '数据与数字化', 'sort' => 2],
            'resilience' => ['en' => 'Infrastructure & resilience', 'th' => 'โครงสร้างพื้นฐานและความทนทาน', 'zh-CN' => '基础设施与韧性', 'sort' => 3],
            'news' => ['en' => 'Company news', 'th' => 'ข่าวองค์กร', 'zh-CN' => '公司新闻', 'sort' => 4],
            'reports' => ['en' => 'Industry reports', 'th' => 'รายงานอุตสาหกรรม', 'zh-CN' => '行业报告', 'sort' => 5],
        ];

        $categoryModels = [];
        foreach ($categories as $slug => $names) {
            $sort = $names['sort'];
            unset($names['sort']);
            $categoryModels[$slug] = ArticleCategory::updateOrCreate(
                ['site_id' => $site->id, 'slug' => $slug],
                ['name' => $names, 'sort' => $sort]
            );
        }

        $authors = User::whereIn('email', ['editor@meridian.example', 'reviewer@meridian.example', 'seo@meridian.example'])->get()->keyBy('email');

        $articles = [
            [
                'slug' => 'dashboard-nobody-trusts', 'type' => 'technical', 'category' => 'digital', 'author' => 'seo@meridian.example',
                'image' => 'logistics', 'featured' => true, 'days' => 12,
                'en' => [
                    'title' => 'The hidden cost of a dashboard nobody trusts',
                    'excerpt' => 'A dashboard is not a data product until someone can trace its numbers back to a verified signal. Most operational reporting fails before the first chart is drawn.',
                    'body' => $this->doc([
                        ['p', 'Every plant has one: a screen in the meeting room that everyone glances at and nobody uses to decide anything. It is usually not a design failure. It is a definition failure.'],
                        ['p', 'When a number cannot be traced to a sensor, a shift, and a calculation rule, people develop a parallel system. They keep their own spreadsheet. The official dashboard becomes decoration, and the cost shows up as repeated meetings, delayed maintenance, and arguments about whose figure is right.'],
                        ['h2', 'Start with the decision, not the chart'],
                        ['p', 'The useful question is not "what data do we have?" It is "what decision changes if this number moves?" A stop-reason distribution that changes the supervisor\'s next action is valuable. A monthly utilisation chart that confirms what everyone already knows is expensive theatre.'],
                        ['ul', ['Name the owner of each metric and the action it triggers.', 'Record the source signal, sample rate, and transformation.', 'Show data freshness and known gaps on the screen.', 'Retire metrics nobody has acted on for two quarters.']],
                        ['h2', 'Trust is an engineering property'],
                        ['p', 'Traceability is not a reporting feature; it is a data architecture decision. Once definitions, lineage, and freshness are treated as part of the product, the dashboard stops being a claim and starts being evidence.'],
                    ]),
                ],
                'th' => [
                    'title' => 'ต้นทุนที่ซ่อนอยู่ของแดชบอร์ดที่ไม่มีใครเชื่อ',
                    'excerpt' => 'แดชบอร์ดจะไม่ใช่ผลิตภัณฑ์ข้อมูลจนกว่าจะมีคนตรวจย้อนตัวเลขกลับไปยังสัญญาณที่ยืนยันได้ รายงานปฏิบัติการส่วนใหญ่ล้มเหลวก่อนวาดกราฟแรก',
                    'body' => $this->doc([
                        ['p', 'ทุกโรงงานมีหน้าจอหนึ่งในห้องประชุมที่ทุกคนมองผ่าน แต่ไม่มีใครใช้ตัดสินใจ มักไม่ใช่ปัญหาการออกแบบ แต่เป็นปัญหาการนิยาม'],
                        ['p', 'เมื่อตัวเลขไม่สามารถตรวจย้อนถึงเซนเซอร์ กะ และกฎการคำนวณได้ ผู้คนจะสร้างระบบคู่ขนานขึ้นมา เก็บสเปรดชีตของตัวเอง แดชบอร์ดทางการกลายเป็นของประดับ และต้นทุนปรากฏเป็นการประชุมซ้ำ การซ่อมล่าช้า และการเถียงกันว่าตัวเลขของใครถูก'],
                        ['h2', 'เริ่มจาก decision ไม่ใช่ chart'],
                        ['p', 'คำถามที่มีประโยชน์ไม่ใช่ "เรามีข้อมูลอะไร" แต่คือ "การตัดสินใจใดจะเปลี่ยนถ้าตัวเลขนี้ขยับ" การกระจายสาเหตุการหยุดที่เปลี่ยนการกระทำถัดไปของหัวหน้างานมีคุณค่า ส่วนกราฟการใช้กำลังการผลิตรายเดือนที่ยืนยันสิ่งที่ทุกคนรู้อยู่แล้วคือโรงละครราคาแพง'],
                        ['ul', ['ระบุเจ้าภาพของแต่ละตัวชี้วัดและการกระทำที่มันกระตุ้น', 'บันทึกสัญญาณต้นทาง อัตราการสุ่ม และการแปลงข้อมูล', 'แสดงความสดของข้อมูลและช่องว่างที่รู้อยู่บนหน้าจอ', 'เลิกใช้ตัวชี้วัดที่ไม่มีใครลงมือทำสองไตรมาสติด']],
                        ['h2', 'ความเชื่อถือคือคุณสมบัติทางวิศวกรรม'],
                        ['p', 'การตรวจสอบย้อนกลับไม่ใช่ฟีเจอร์รายงาน แต่เป็นการตัดสินใจเชิงสถาปัตยกรรมข้อมูล เมื่อนิยาม สายข้อมูล และความสดถูกมองเป็นส่วนหนึ่งของผลิตภัณฑ์ แดชบอร์ดจะเลิกเป็นคำกล่าวอ้างและเริ่มเป็นหลักฐาน'],
                    ]),
                ],
            ],
            [
                'slug' => 'designing-for-changeover', 'type' => 'insight', 'category' => 'operations', 'author' => 'editor@meridian.example',
                'image' => 'manufacturing', 'featured' => false, 'days' => 20,
                'en' => [
                    'title' => 'Designing for changeover, not just throughput',
                    'excerpt' => 'A line optimised for one product is a line that punishes every product after it. Changeover is a design constraint, not an operator problem.',
                    'body' => $this->doc([
                        ['p', 'Throughput is easy to sell. A single number, measured on a good day, with a stable product and a settled crew. Real plants rarely operate under those conditions for long.'],
                        ['p', 'Changeover exposes the quality of the whole system: tooling design, recipe management, quality gates, material staging, and the operator\'s mental model. When those are treated as separate projects, every product change becomes an escalation.'],
                        ['h2', 'The four seams of a changeover'],
                        ['p', 'We look at four seams: the physical change (tooling, fixtures, cleaning), the digital change (recipes, parameters, traceability), the quality change (first-off inspection, approvals), and the human change (who decides, who verifies, what good looks like).'],
                        ['ul', ['Design tooling for repeatable location, not just fit.', 'Make recipes versioned and testable before the line stops.', 'Move first-off inspection into the changeover standard.', 'Measure changeover from last good part to next good part, not from stop to start.']],
                        ['h2', 'Buy capability, not speed'],
                        ['p', 'A line that changes over in twenty minutes but needs an engineer every time is less capable than a slower line the team can run itself. Capability includes recoverability, documentation, and the confidence to try the next product.'],
                    ]),
                ],
                'th' => [
                    'title' => 'ออกแบบเพื่อการเปลี่ยนรุ่น ไม่ใช่แค่กำลังผลิต',
                    'excerpt' => 'สายการผลิตที่ปรับเหมาะกับสินค้าเดียว sẽลงโทษสินค้าทุกตัวถัดไป การเปลี่ยนรุ่นเป็นข้อจำกัดการออกแบบ ไม่ใช่ปัญหาของผู้ปฏิบัติงาน',
                    'body' => $this->doc([
                        ['p', 'กำลังผลิตขายง่าย ตัวเลขเดียว วัดในวันที่ดี ผลิตภัณฑ์คงที่ และทีมคุ้นเคย แต่โรงงานจริงไม่ได้อยู่ในสภาพนั้นนาน'],
                        ['p', 'การเปลี่ยนรุ่นเผยคุณภาพของทั้งระบบ ทั้งการออกแบบเครื่องมือ การจัดการสูตร การตรวจคุณภาพ การเตรียมวัตถุดิบ และโมเดลในหัวของผู้ปฏิบัติงาน เมื่อสิ่งเหล่านี้ถูกมองเป็นโครงการแยกกัน ทุกการเปลี่ยนผลิตภัณฑ์จึงกลายเป็นการยกระดับปัญหา'],
                        ['h2', 'สี่รอยต่อของการเปลี่ยนรุ่น'],
                        ['p', 'เราดูสี่รอยต่อ คือการเปลี่ยนกายภาพ (เครื่องมือ จิ๊ก การทำความสะอาด) การเปลี่ยนดิจิทัล (สูตร พารามิเตอร์ การตรวจสอบย้อนกลับ) การเปลี่ยนคุณภาพ (ตรวจชิ้นแรก การอนุมัติ) และการเปลี่ยนคน (ใครตัดสิน ใครยืนยัน อะไรคือดี)'],
                        ['ul', ['ออกแบบเครื่องมือให้วางซ้ำได้ ไม่ใช่แค่ประกอบได้', 'ทำสูตรเป็นเวอร์ชันและทดสอบได้ก่อนหยุดสาย', 'ย้ายการตรวจชิ้นแรกเข้าไปในมาตรฐานการเปลี่ยนรุ่น', 'วัดการเปลี่ยนรุ่นจากชิ้นดีสุดท้ายถึงชิ้นดีถัดไป ไม่ใช่จากหยุดถึงเริ่ม']],
                        ['h2', 'ซื้อความสามารถ ไม่ใช่ความเร็ว'],
                        ['p', 'สายที่เปลี่ยนรุ่นใน 20 นาทีแต่ต้องมีวิศวกรทุกครั้ง มีความสามารถน้อยกว่าสายที่ช้ากว่าแต่ทีมเดินเองได้ ความสามารถรวมถึงการกู้คืน เอกสาร และความมั่นใจที่จะลองผลิตภัณฑ์ถัดไป'],
                    ]),
                ],
            ],
            [
                'slug' => 'operational-data-boundaries', 'type' => 'technical', 'category' => 'digital', 'author' => 'seo@meridian.example',
                'image' => 'automation', 'featured' => false, 'days' => 28,
                'en' => [
                    'title' => 'What operational data should never do',
                    'excerpt' => 'Plant data becomes risky when it is copied without context, exposed without purpose, or trusted without lineage. Three boundaries worth designing early.',
                    'body' => $this->doc([
                        ['p', 'Operational data is not generic business data. It describes physical processes, safety conditions, and the people running them. Treating it like web analytics creates both security and decision risk.'],
                        ['h2', 'Boundary one: context travels with the value'],
                        ['p', 'A temperature of 84 means nothing without the asset, unit, timestamp, and quality flag. When data is copied into a warehouse without that context, every downstream team rebuilds its own interpretation.'],
                        ['h2', 'Boundary two: exposure follows purpose'],
                        ['p', 'Not every analyst needs raw process values. Aggregate and derived data should be the default; raw streams should be an explicit, logged exception with a retention limit.'],
                        ['h2', 'Boundary three: control networks stay controlled'],
                        ['p', 'Data collection should never create an inbound path into the control network. One-way gateways, allow-listed brokers, and documented change control are not optional.'],
                        ['ul', ['Define a canonical asset and event model before integration.', 'Separate raw, curated, and published layers with different access.', 'Log every export of personally identifiable or safety-relevant data.', 'Test the recovery path, not only the happy path.']],
                        ['p', 'These boundaries cost less to design at the start than to retrofit after the first incident or the first contradictory board report.'],
                    ]),
                ],
                'th' => [
                    'title' => 'สิ่งที่ข้อมูลปฏิบัติการไม่ควรทำเด็ดขาด',
                    'excerpt' => 'ข้อมูลโรงงานมีความเสี่ยงเมื่อถูกคัดลอกโดยไม่มีบริบท เปิดเผยโดยไม่มีวัตถุประสงค์ หรือเชื่อถือโดยไม่มีสายข้อมูล สามขอบเขตที่ควรออกแบบตั้งแต่ต้น',
                    'body' => $this->doc([
                        ['p', 'ข้อมูลปฏิบัติการไม่ใช่ข้อมูลธุรกิจทั่วไป มันอธิบายกระบวนการทางกายภาพ สภาพความปลอดภัย และผู้คนที่ดูแลสิ่งเหล่านั้น การปฏิบัติเหมือนข้อมูลเว็บสร้างทั้งความเสี่ยงด้านความปลอดภัยและการตัดสินใจ'],
                        ['h2', 'ขอบเขตหนึ่ง: บริบทต้องไปกับค่า'],
                        ['p', 'อุณหภูมิ 84 องศาไม่มีความหมายหากไม่มีสินทรัพย์ หน่วย เวลา และสถานะคุณภาพ เมื่อข้อมูลถูกคัดลอกเข้าคลังโดยไม่มีบริบท ทุกทีมปลายทางต้องสร้างการตีความของตัวเองใหม่'],
                        ['h2', 'ขอบเขตสอง: การเปิดเผยตามวัตถุประสงค์'],
                        ['p', 'นักวิเคราะห์ทุกคนไม่จำเป็นต้องเห็นค่ากระบวนการดิบ ข้อมูลรวมและข้อมูลที่ได้มาควรเป็นค่าเริ่มต้น ส่วนสตรีมดิบควรเป็นข้อยกเว้นที่ระบุและบันทึกพร้อมข้อจำกัดการเก็บ'],
                        ['h2', 'ขอบเขตสาม: เครือข่ายควบคุมต้องถูกควบคุม'],
                        ['p', 'การเก็บข้อมูลไม่ควรสร้างเส้นทางขาเข้าสู่เครือข่ายควบคุม เกตเวย์ทางเดียว โบรกเกอร์ที่อนุญาต และการควบคุมการเปลี่ยนแปลงที่จัดทำเอกสารไม่ใช่ทางเลือก'],
                        ['ul', ['นิยามโมเดลสินทรัพย์และเหตุการณ์มาตรฐานก่อนเชื่อมต่อ', 'แยกชั้นดิบ ชั้น curated และชั้นเผยแพร่ด้วยสิทธิ์ต่างกัน', 'บันทึกทุกการส่งออกข้อมูลส่วนบุคคลหรือข้อมูลที่เกี่ยวกับความปลอดภัย', 'ทดสอบเส้นทางกู้คืน ไม่ใช่แค่เส้นทางปกติ']],
                        ['p', 'การออกแบบขอบเขตเหล่านี้ตั้งแต่ต้นถูกกว่าการแก้ไขหลังเหตุการณ์แรกหรือหลังรายงานคณะกรรมการที่ขัดแย้งกันฉบับแรก'],
                    ]),
                ],
            ],
            [
                'slug' => 'brownfield-automation-case', 'type' => 'insight', 'category' => 'operations', 'author' => 'reviewer@meridian.example',
                'image' => 'precision', 'featured' => false, 'days' => 36,
                'en' => [
                    'title' => 'The case for brownfield automation',
                    'excerpt' => 'New lines are photogenic. Existing lines pay the bills. Most of the industrial opportunity in the region sits inside plants that cannot stop.',
                    'body' => $this->doc([
                        ['p', 'A greenfield factory can be designed as a system from the first drawing. A brownfield plant must be improved while it runs, with incomplete drawings, mixed vintages, and a team that remembers why the last project hurt.'],
                        ['p', 'That is exactly why brownfield work matters. The installed base produces today\'s revenue, and its constraints are usually well understood by the people closest to it.'],
                        ['h2', 'Sequence around the constraint'],
                        ['p', 'The first question is not where to put a robot. It is which constraint, when relieved, changes the economics of the plant. That may be a manual inspection step, an unreliable transfer, or a changeover that consumes a shift.'],
                        ['ul', ['Model the existing process before proposing equipment.', 'Keep a running line running; design shutdown windows as part of the plan.', 'Retain reliable legacy assets and isolate the unsafe ones.', 'Train the maintenance team before handover, not after.']],
                        ['h2', 'Accept imperfect information'],
                        ['p', 'Brownfield projects rarely begin with complete documentation. The discipline is to make assumptions visible, verify them early, and design for discovery. A two-week survey that finds the hidden constraint is cheaper than a six-month build around the wrong one.'],
                    ]),
                ],
                'th' => [
                    'title' => 'เหตุผลของระบบอัตโนมัติในโรงงานเดิม',
                    'excerpt' => 'สายการผลิตใหม่ถ่ายรูปสวย แต่สายเดิมคือแหล่งรายได้จริง โอกาสทางอุตสาหกรรมส่วนใหญ่อยู่ในโรงงานที่หยุดไม่ได้',
                    'body' => $this->doc([
                        ['p', 'โรงงานสร้างใหม่สามารถออกแบบเป็นระบบได้ตั้งแต่แบบแรก ส่วนโรงงานเดิมต้องปรับปรุงขณะเดินเครื่อง ด้วยแบบที่ไม่ครบ เครื่องรุ่นผสม และทีมที่จำได้ว่าโครงการครั้งก่อนเจ็บอย่างไร'],
                        ['p', 'นั่นคือเหตุผลที่งานโรงงานเดิมสำคัญ ฐานติดตั้งสร้างรายได้วันนี้ และข้อจำกัดมักเป็นที่เข้าใจดีโดยคนที่ใกล้ชิดที่สุด'],
                        ['h2', 'จัดลำดับรอบข้อจำกัด'],
                        ['p', 'คำถามแรกไม่ใช่จะวางหุ่นยนต์ที่ไหน แต่คือข้อจำกัดใดเมื่อถูกแก้แล้วเปลี่ยนเศรษฐศาสตร์ของโรงงาน อาจเป็นขั้นตรวจด้วยมือ การลำเลียงที่ไม่เสถียร หรือการเปลี่ยนรุ่นที่กินทั้งกะ'],
                        ['ul', ['จำลองกระบวนการเดิมก่อนเสนออุปกรณ์', 'รักษาสายที่เดินอยู่ให้เดินต่อ และออกแบบช่วงหยุดเป็นส่วนหนึ่งของแผน', 'เก็บสินทรัพย์เดิมที่เชื่อถือได้และแยกสิ่งที่ไม่ปลอดภัย', 'ฝึกทีมซ่อมบำรุงก่อนส่งมอบ ไม่ใช่หลัง']],
                        ['h2', 'ยอมรับข้อมูลที่ไม่สมบูรณ์'],
                        ['p', 'โครงการโรงงานเดิมไม่ค่อยเริ่มด้วยเอกสารครบ วินัยคือทำให้สมมติฐานมองเห็น ตรวจสอบเร็ว และออกแบบเผื่อการค้นพบ การสำรวจสองสัปดาห์ที่พบข้อจำกัดที่ซ่อนอยู่ถูกกว่าการสร้างหกเดือนรอบข้อจำกัดที่ผิด'],
                    ]),
                ],
            ],
            [
                'slug' => 'digital-twin-that-earns-its-keep', 'type' => 'technical', 'category' => 'digital', 'author' => 'seo@meridian.example',
                'image' => 'automation', 'featured' => false, 'days' => 44,
                'en' => [
                    'title' => 'How to specify a digital twin that earns its keep',
                    'excerpt' => 'Most twins are models looking for a question. A useful one is bounded, maintained, and tied to a decision someone actually makes.',
                    'body' => $this->doc([
                        ['p', 'A digital twin can mean a CAD assembly, a simulation model, a live asset register, or a statistical forecast. The word is broad enough to hide an unbounded project.'],
                        ['p', 'The specification should start with the decision: commissioning a line, planning maintenance, testing a control change, or training an operator. Each requires a different fidelity, update cadence, and owner.'],
                        ['h2', 'Four questions that bound the scope'],
                        ['ul', ['Which decision changes because the twin exists?', 'What is the minimum fidelity that makes that decision reliable?', 'Who updates the model when the plant changes?', 'How will we know the model has drifted from reality?']],
                        ['h2', 'Treat the model as an asset'],
                        ['p', 'A twin without an owner and a maintenance routine becomes a museum. Define the update trigger — a change request, a shutdown, a sensor recalibration — and test the model against measured behaviour at the same cadence.'],
                        ['p', 'The result is less impressive in a demo and far more useful on a Tuesday morning.'],
                    ]),
                ],
                'th' => [
                    'title' => 'วิธีกำหนด Digital Twin ที่คุ้มค่าจริง',
                    'excerpt' => 'ทวินส่วนใหญ่เป็นโมเดลที่ตามหาคำถาม ทวินที่มีประโยชน์มีขอบเขต มีเจ้าภาพดูแล และผูกกับการตัดสินใจที่มีคนทำจริง',
                    'body' => $this->doc([
                        ['p', 'Digital Twin อาจหมายถึงชุด CAD แบบจำลองการจำลอง ทะเบียนสินทรัพย์สด หรือการพยากรณ์เชิงสถิติ คำนี้กว้างพอที่จะซ่อนโครงการไร้ขอบเขต'],
                        ['p', 'ข้อกำหนดควรเริ่มจาก decision การทดสอบสาย การวางแผนบำรุงรักษา การทดสอบการเปลี่ยนแปลงควบคุม หรือการฝึกผู้ปฏิบัติงาน แต่ละอย่างต้องการความละเอียด รอบอัปเดต และเจ้าภาพต่างกัน'],
                        ['h2', 'สี่คำถามที่จำกัดขอบเขต'],
                        ['ul', ['การตัดสินใจใดเปลี่ยนเพราะมีทวินนี้', 'ความละเอียดขั้นต่ำใดทำให้การตัดสินใจนั้นเชื่อถือได้', 'ใครอัปเดตโมเดลเมื่อโรงงานเปลี่ยน', 'เราจะรู้ได้อย่างไรว่าโมเดลเบี่ยงจากความจริง']],
                        ['h2', 'มองโมเดลเป็นสินทรัพย์'],
                        ['p', 'ทวินที่ไม่มีเจ้าภาพและ routine บำรุงรักษาจะกลายเป็นพิพิธภัณฑ์ กำหนดตัวกระตุ้นการอัปเดต เช่น คำขอเปลี่ยนแปลง การหยุดระบบ หรือการสอบเทียบเซนเซอร์ และทดสอบโมเดลกับพฤติกรรมที่วัดได้ในรอบเดียวกัน'],
                        ['p', 'ผลลัพธ์อาจไม่น่าตื่นเต้นในการสาธิต แต่มีประโยชน์กว่ามากในเช้าวันอังคาร'],
                    ]),
                ],
            ],
            [
                'slug' => 'cybersecurity-plant-floor', 'type' => 'insight', 'category' => 'resilience', 'author' => 'reviewer@meridian.example',
                'image' => 'energy', 'featured' => false, 'days' => 52,
                'en' => [
                    'title' => 'Cybersecurity is now a plant-floor discipline',
                    'excerpt' => 'The control network is no longer isolated, and the maintenance laptop is part of the attack surface. Security has to be designed with operations, not handed to IT afterwards.',
                    'body' => $this->doc([
                        ['p', 'Industrial security used to be a perimeter conversation: keep the plant network separate and assume the rest. Remote support, vendor access, and data projects have dissolved that assumption.'],
                        ['p', 'The practical risk is not only a malicious actor. It is a compromised engineering laptop, an unpatched historian, or a shared password that turns a routine maintenance task into an outage.'],
                        ['h2', 'Design for the maintenance reality'],
                        ['ul', ['Inventory every remote access path, including vendor tools.', 'Separate engineering, operations, and enterprise zones with documented rules.', 'Use unique credentials and hardware-backed authentication for engineers.', 'Test restore procedures for historians and configuration backups.']],
                        ['h2', 'Make security an operating routine'],
                        ['p', 'A secure architecture that nobody reviews decays. Assign ownership, schedule reviews after every significant change, and include security in commissioning acceptance. The plant team should be able to explain how to isolate a compromised asset without waiting for a corporate escalation.'],
                    ]),
                ],
                'th' => [
                    'title' => 'ความปลอดภัยไซเบอร์กลายเป็นวินัยของพื้นที่ผลิต',
                    'excerpt' => 'เครือข่ายควบคุมไม่ได้แยกเดี่ยวอีกต่อไป และแล็ปท็อปของทีมซ่อมบำรุงคือส่วนหนึ่งของพื้นผิวการโจมตี ความปลอดภัยต้องออกแบบร่วมกับปฏิบัติการ ไม่ใช่ส่งให้ไอทีทีหลัง',
                    'body' => $this->doc([
                        ['p', 'ความปลอดภัยอุตสาหกรรมเคยเป็นเรื่องของขอบเขตเครือข่าย แยกเครือข่ายโรงงานและถือว่าที่เหลือปลอดภัย การสนับสนุนระยะไกล การเข้าถึงของผู้ขาย และโครงการข้อมูลได้ละลายสมมติฐานนั้น'],
                        ['p', 'ความเสี่ยงจริงไม่ใช่เพียงผู้ไม่หวังดี แต่คือแล็ปท็อปวิศวกรรมที่ถูกบุกรุก ฮิสทอเรียนที่ไม่ได้แพตช์ หรือรหัสผ่านร่วมที่ทำให้งานซ่อมประจำกลายเป็นเหตุหยุด'],
                        ['h2', 'ออกแบบตามความเป็นจริงของงานซ่อม'],
                        ['ul', ['ทำบัญชีทุกเส้นทางเข้าถึงระยะไกล รวมถึงเครื่องมือผู้ขาย', 'แยกโซนวิศวกรรม ปฏิบัติการ และองค์กรด้วยกฎที่จัดทำเอกสาร', 'ใช้ข้อมูลรับรองเฉพาะและ authentication ที่มีฮาร์ดแวร์รองรับสำหรับวิศวกร', 'ทดสอบขั้นตอนกู้คืนฮิสทอเรียนและสำรองการตั้งค่า']],
                        ['h2', 'ทำให้ความปลอดภัยเป็น routine'],
                        ['p', 'สถาปัตยกรรมที่ปลอดภัยแต่ไม่มีใครทบทวนจะเสื่อมสภาพ กำหนดเจ้าภาพ ตั้งรอบทบทวนหลังการเปลี่ยนแปลงสำคัญ และรวมความปลอดภัยในการรับมอบระบบ ทีมโรงงานควรอธิบายได้ว่าจะแยกสินทรัพย์ที่ถูกบุกรุกอย่างไรโดยไม่รอการยกระดับจากส่วนกลาง'],
                    ]),
                ],
            ],
            [
                'slug' => 'five-questions-robot-cell', 'type' => 'insight', 'category' => 'operations', 'author' => 'editor@meridian.example',
                'image' => 'automation', 'featured' => false, 'days' => 61,
                'en' => [
                    'title' => 'Five questions before your first robot cell',
                    'excerpt' => 'A robot is rarely the hard part. The hard part is the material, the fixture, the exception, and the person who has to recover the cell at 2 a.m.',
                    'body' => $this->doc([
                        ['p', 'Robot cells fail for human reasons more often than mechanical ones. Before comparing payloads and reach, answer five questions about the work around the robot.'],
                        ['h2', 'The five questions'],
                        ['ul', ['How does material arrive, and how often is it out of specification?', 'What happens when the robot cannot pick the part?', 'Who recovers the cell, and how long does recovery take?', 'Which quality checks move into the cell, and which stay manual?', 'How will the cell be cleaned, maintained, and safely isolated?']],
                        ['h2', 'Design the exception first'],
                        ['p', 'A cell that runs perfectly 95% of the time and stops the line for an hour in the other 5% is not a good investment. Put the exception path, the sensor strategy, and the recovery routine in the concept design, not the commissioning checklist.'],
                        ['p', 'The most successful first cells are usually narrow, well-bounded, and supported by a team that was involved from the start.'],
                    ]),
                ],
                'th' => [
                    'title' => 'ห้าคำถามก่อนสร้างเซลล์หุ่นยนต์แรก',
                    'excerpt' => 'หุ่นยนต์ไม่ใช่ส่วนที่ยากที่สุด ส่วนที่ยากคือวัตถุดิบ จิ๊ก ข้อยกเว้น และคนที่ต้องกู้เซลล์ตอนตีสอง',
                    'body' => $this->doc([
                        ['p', 'เซลล์หุ่นยนต์ล้มเหลวด้วยเหตุผลของคนบ่อยกว่าเหตุผลเชิงกล ก่อนเปรียบเทียบน้ำหนักบรรทุกและระยะเอื้อม ให้ตอบห้าคำถามเกี่ยวกับงานรอบหุ่นยนต์'],
                        ['h2', 'ห้าคำถาม'],
                        ['ul', ['วัตถุดิบมาถึงอย่างไร และหลุดข้อกำหนดบ่อยแค่ไหน', 'เกิดอะไรขึ้นเมื่อหุ่นยนต์หยิบชิ้นงานไม่ได้', 'ใครกู้เซลล์ และใช้เวลานานเท่าไร', 'การตรวจคุณภาพใดย้ายเข้าเซลล์ และใดยังทำด้วยมือ', 'เซลล์จะถูกทำความสะอาด บำรุงรักษา และแยกอย่างปลอดภัยอย่างไร']],
                        ['h2', 'ออกแบบข้อยกเว้นก่อน'],
                        ['p', 'เซลล์ที่ทำงานสมบูรณ์ 95% แต่ทำให้สายหยุดหนึ่งชั่วโมงในอีก 5% ไม่ใช่การลงทุนที่ดี ใส่เส้นทางข้อยกเว้น กลยุทธ์เซนเซอร์ และ routine กู้คืนไว้ใน การออกแบบแนวคิด ไม่ใช่ใน รายการตรวจสอบตอนทดสอบระบบ'],
                        ['p', 'เซลล์แรกที่ประสบความสำเร็จมักมีขอบเขตแคบ ชัดเจน และมีทีมที่เกี่ยวข้องตั้งแต่วันแรก'],
                    ]),
                ],
            ],
            [
                'slug' => 'handover-failure-infrastructure', 'type' => 'insight', 'category' => 'resilience', 'author' => 'reviewer@meridian.example',
                'image' => 'infrastructure', 'featured' => false, 'days' => 72,
                'en' => [
                    'title' => 'Why infrastructure projects fail at the handover',
                    'excerpt' => 'The asset is built, the ribbon is cut, and the operating team inherits a system nobody designed for them. Handover is a design phase, not a final meeting.',
                    'body' => $this->doc([
                        ['p', 'Infrastructure projects are usually judged at completion. In practice, most of their value or cost is determined after the project team leaves, when the operating team starts making decisions with whatever evidence it inherited.'],
                        ['p', 'Handover failures are rarely dramatic. They look like a missing as-built drawing, a spare part with a six-month lead time, or a control sequence nobody can explain.'],
                        ['h2', 'Design the operating model early'],
                        ['ul', ['Name the future owner before detailed design.', 'Make the as-built requirement part of every package, not a final deliverable.', 'Test the maintenance routine during commissioning, not after.', 'Define the evidence required to accept each system.']],
                        ['h2', 'Count the cost of ambiguity'],
                        ['p', 'A project that saves a month by deferring documentation can cost years of slower maintenance and unsafe improvisation. The handover is not administration. It is the moment the asset becomes useful.'],
                    ]),
                ],
                'th' => [
                    'title' => 'ทำไมโครงการโครงสร้างพื้นฐานล้มเหลวตอนส่งมอบ',
                    'excerpt' => 'สินทรัพย์สร้างเสร็จ ตัดริบบิ้น และทีมปฏิบัติการได้รับระบบที่ไม่มีใครออกแบบมาเพื่อ mereka การส่งมอบคือระยะออกแบบ ไม่ใช่การประชุมครั้งสุดท้าย',
                    'body' => $this->doc([
                        ['p', 'โครงการโครงสร้างพื้นฐานมักถูกตัดสินตอนเสร็จ แต่ในทางปฏิบัติ คุณค่าหรือต้นทุนส่วนใหญ่ถูกกำหนดหลังทีมโครงการจากไป เมื่อทีมปฏิบัติการเริ่มตัดสินใจด้วยหลักฐานที่ได้รับมา'],
                        ['p', 'ความล้มเหลวในการส่งมอบ แทบไม่ดราม่า มันดูเหมือนแบบ as-built ที่หายไป อะไหล่ที่มี ระยะเวลาจัดหาหกเดือน หรือลำดับการควบคุมที่ไม่มีใครอธิบายได้'],
                        ['h2', 'ออกแบบโมเดลปฏิบัติการตั้งแต่เนิ่นๆ'],
                        ['ul', ['ระบุเจ้าของในอนาคตก่อนการออกแบบรายละเอียด', 'ทำให้ข้อกำหนด as-built เป็นส่วนหนึ่งของทุกแพ็กเกจ ไม่ใช่ของส่งมอบสุดท้าย', 'ทดสอบ routine บำรุงรักษาระหว่าง commissioning ไม่ใช่หลัง', 'นิยามหลักฐานที่ต้องใช้รับมอบแต่ละระบบ']],
                        ['h2', 'นับต้นทุนของความคลุมเครือ'],
                        ['p', 'โครงการที่ประหยัดหนึ่งเดือนโดยเลื่อนเอกสารอาจต้องจ่ายหลายปีของงานซ่อมที่ช้าลงและการแก้ปัญหาเฉพาะหน้าที่ไม่ปลอดภัย การส่งมอบไม่ใช่ธุรการ แต่คือช่วงเวลาที่สินทรัพย์เริ่มมีประโยชน์'],
                    ]),
                ],
            ],
            [
                'slug' => 'rayong-smart-manufacturing-lab', 'type' => 'announcement', 'category' => 'news', 'author' => 'editor@meridian.example',
                'image' => 'manufacturing', 'featured' => false, 'days' => 6,
                'en' => [
                    'title' => 'Meridian opens a smart manufacturing lab in Rayong',
                    'excerpt' => 'The demonstration lab connects a small production cell, quality inspection, and an operating data model in one testable environment.',
                    'body' => $this->doc([
                        ['p', 'Meridian has opened a smart manufacturing lab at its Rayong engineering centre. The lab is a demonstration and testing environment for automation, traceability, and operating-data projects.'],
                        ['p', 'The facility includes a compact production cell, in-line inspection, and a data platform that mirrors the architecture used in client projects. It is intended for factory acceptance testing, operator workshops, and integration rehearsals before work begins on a live line.'],
                        ['p', 'This is illustrative portfolio content for the fictional Meridian brand. No real facility, certification, or client engagement is claimed.'],
                    ]),
                ],
                'th' => [
                    'title' => 'เมอริเดียนเปิดห้องปฏิบัติการการผลิตอัจฉริยะที่ระยอง',
                    'excerpt' => 'ห้องปฏิบัติการสาธิตเชื่อมเซลล์ผลิตขนาดเล็ก การตรวจคุณภาพ และโมเดลข้อมูลปฏิบัติการไว้ในสภาพแวดล้อมที่ทดสอบได้',
                    'body' => $this->doc([
                        ['p', 'เมอริเดียนเปิดห้องปฏิบัติการการผลิตอัจฉริยะที่ศูนย์วิศวกรรมระยอง เป็นสภาพแวดล้อมสาธิตและทดสอบสำหรับโครงการระบบอัตโนมัติ การตรวจสอบย้อนกลับ และข้อมูลปฏิบัติการ'],
                        ['p', 'สิ่งอำนวยความสะดวกประกอบด้วยเซลล์ผลิตขนาดกะทัดรัด การตรวจแบบอินไลน์ และแพลตฟอร์มข้อมูลที่สะท้อนสถาปัตยกรรมที่ใช้ในโครงการลูกค้า มีไว้สำหรับทดสอบก่อนติดตั้ง เวิร์กช็อปผู้ปฏิบัติงาน และซ้อมการเชื่อมต่อก่อนเริ่มงานบนสายจริง'],
                        ['p', 'นี่คือเนื้อหาพอร์ตโฟลิโอเพื่อการสาธิตสำหรับแบรนด์เมอริเดียนที่สร้างขึ้น ไม่ได้อ้างถึงสถานที่ การรับรอง หรือโครงการลูกค้าจริง'],
                    ]),
                ],
            ],
            [
                'slug' => '2026-industrial-outlook', 'type' => 'report', 'category' => 'reports', 'author' => 'seo@meridian.example',
                'image' => 'energy', 'featured' => false, 'days' => 15,
                'en' => [
                    'title' => 'Meridian publishes its 2026 industrial outlook',
                    'excerpt' => 'A demonstration report on the operating themes shaping industrial investment: energy resilience, brownfield capability, and the cost of untrusted data.',
                    'body' => $this->doc([
                        ['p', 'Meridian has published its illustrative 2026 industrial outlook, a demonstration document outlining three themes the team expects to shape industrial investment across the region.'],
                        ['h2', 'Energy resilience moves from project to operating model'],
                        ['p', 'The report argues that resilience is no longer a backup-power purchase. It is a continuous operating capability involving load visibility, protection, maintenance, and tested recovery.'],
                        ['h2', 'Brownfield capability beats greenfield ambition'],
                        ['p', 'Most ระยะใกล้ value sits in existing plants. The constraint is rarely technology; it is the ability to improve a running operation without creating a new dependency on scarce engineering time.'],
                        ['h2', 'Untrusted data has a cost'],
                        ['p', 'The report estimates that unclear data definitions consume a meaningful share of management attention. It recommends treating lineage and freshness as product requirements.'],
                        ['p', 'All figures and forecasts are illustrative demonstration data for this fictional brand.'],
                    ]),
                ],
                'th' => [
                    'title' => 'เมอริเดียนเผยแพร่รายงานแนวโน้มอุตสาหกรรมปี 2026',
                    'excerpt' => 'รายงานสาธิตว่าด้วยธีมการดำเนินงานที่กำหนดการลงทุนอุตสาหกรรม ความทนทานด้านพลังงาน ศักยภาพโรงงานเดิม และต้นทุนของข้อมูลที่ไม่น่าเชื่อถือ',
                    'body' => $this->doc([
                        ['p', 'เมอริเดียนเผยแพร่รายงานแนวโน้มอุตสาหกรรมปี 2026 เพื่อการสาธิต ซึ่งสรุปสามธีมที่ทีมคาดว่าจะกำหนดการลงทุนอุตสาหกรรมในภูมิภาค'],
                        ['h2', 'ความทนทานด้านพลังงานเคลื่อนจากโครงการสู่โมเดลปฏิบัติการ'],
                        ['p', 'รายงานเสนอว่าความทนทานไม่ใช่การซื้อไฟสำรองอีกต่อไป แต่เป็นความสามารถในการดำเนินงานต่อเนื่อง ซึ่งรวมการมองเห็นภาระ การป้องกัน การบำรุงรักษา และการกู้คืนที่ทดสอบแล้ว'],
                        ['h2', 'ศักยภาพโรงงานเดิมชนะความทะเยอทะยานโรงงานใหม่'],
                        ['p', 'คุณค่าใกล้-term ส่วนใหญ่อยู่ในโรงงานเดิม ข้อจำกัดไม่ค่อยอยู่ที่เทคโนโลยี แต่อยู่ที่ความสามารถในการปรับปรุงการดำเนินงานที่เดินอยู่โดยไม่สร้างการพึ่งพาใหม่ต่อเวลาวิศวกรที่หายาก'],
                        ['h2', 'ข้อมูลที่ไม่น่าเชื่อถือมีต้นทุน'],
                        ['p', 'รายงานประเมินว่าการนิยามข้อมูลที่ไม่ชัดเจนกินส่วนสำคัญของความสนใจผู้บริหาร และแนะนำให้มองสายข้อมูลและความสดเป็นข้อกำหนดของผลิตภัณฑ์'],
                        ['p', 'ตัวเลขและการคาดการณ์ทั้งหมดเป็นข้อมูลตัวอย่างเพื่อการสาธิตสำหรับแบรนด์ที่สร้างขึ้น'],
                    ]),
                ],
            ],
            [
                'slug' => 'automation-apprenticeship', 'type' => 'news', 'category' => 'news', 'author' => 'editor@meridian.example',
                'image' => 'precision', 'featured' => false, 'days' => 33,
                'en' => [
                    'title' => 'Meridian launches an automation apprenticeship',
                    'excerpt' => 'A two-year programme pairing electrical, controls, and software apprentices with working engineers on live projects.',
                    'body' => $this->doc([
                        ['p', 'Meridian has launched a two-year automation apprenticeship for early-career technicians and engineers. The programme combines structured training with supervised work on live automation and infrastructure projects.'],
                        ['p', 'Apprentices rotate through electrical, controls, and industrial software teams, with a mentor and a documented competency framework. The first cohort begins in the next academic cycle.'],
                        ['p', 'This announcement is illustrative portfolio content for the fictional Meridian brand.'],
                    ]),
                ],
                'th' => [
                    'title' => 'เมอริเดียนเปิดหลักสูตรฝึกงานระบบอัตโนมัติ',
                    'excerpt' => 'โครงการสองปีที่จับคู่นักฝึกงานไฟฟ้า ควบคุม และซอฟต์แวร์กับวิศวกรที่ทำงานจริงในโครงการจริง',
                    'body' => $this->doc([
                        ['p', 'เมอริเดียนเปิดหลักสูตรฝึกงานระบบอัตโนมัติระยะสองปีสำหรับช่างเทคนิคและวิศวกรรุ่นใหม่ โดยผสมการฝึกอบรมแบบมีโครงสร้างกับการทำงานภายใต้การดูแลในโครงการระบบอัตโนมัติและโครงสร้างพื้นฐานจริง'],
                        ['p', 'ผู้ฝึกงานหมุนเวียนผ่านทีมไฟฟ้า ควบคุม และซอฟต์แวร์อุตสาหกรรม พร้อมพี่เลี้ยงและกรอบสมรรถนะที่จัดทำเอกสาร รุ่นแรกจะเริ่มในรอบการศึกษาถัดไป'],
                        ['p', 'ประกาศนี้เป็นเนื้อหาพอร์ตโฟลิโอเพื่อการสาธิตสำหรับแบรนด์เมอริเดียนที่สร้างขึ้น'],
                    ]),
                ],
            ],
            [
                'slug' => 'director-digital-systems', 'type' => 'announcement', 'category' => 'news', 'author' => 'editor@meridian.example',
                'image' => 'hero-facility', 'featured' => false, 'days' => 80,
                'en' => [
                    'title' => 'Meridian appoints a Director of Digital Systems',
                    'excerpt' => 'The role brings industrial software, integration, and data practice under one accountable leader.',
                    'body' => $this->doc([
                        ['p', 'Meridian has appointed Marcus Chen as Director of Digital Systems, a role created to connect industrial software, integration, and data engineering under one practice.'],
                        ['p', 'คุณ Chen joins from the company\'s systems integration group and will lead the digital systems practice across the Bangkok and Rayong teams. The appointment is part of a broader operating model change intended to shorten the distance between strategy and delivery.'],
                        ['p', 'This is illustrative portfolio content; the person and appointment are fictional.'],
                    ]),
                ],
                'th' => [
                    'title' => 'เมอริเดียนแต่งตั้งผู้อำนวยการระบบดิจิทัล',
                    'excerpt' => 'บทบาทนี้รวมซอฟต์แวร์อุตสาหกรรม การเชื่อมต่อ และข้อมูลไว้ภายใต้ผู้นำที่รับผิดชอบคนเดียว',
                    'body' => $this->doc([
                        ['p', 'เมอริเดียนแต่งตั้ง Marcus Chen เป็นผู้อำนวยการระบบดิจิทัล ซึ่งเป็นบทบาทที่สร้างขึ้นเพื่อเชื่อมซอฟต์แวร์อุตสาหกรรม การเชื่อมต่อ และวิศวกรรมข้อมูลไว้ในแนวปฏิบัติเดียว'],
                        ['p', 'คุณ Chen ย้ายมาจากกลุ่มงานบูรณาการระบบของบริษัท และจะนำทีมระบบดิจิทัลในกรุงเทพฯ และระยอง การแต่งตั้งนี้เป็นส่วนหนึ่งของการเปลี่ยนโมเดลการดำเนินงานเพื่อย่นระยะระหว่างกลยุทธ์และการส่งมอบ'],
                        ['p', 'นี่คือเนื้อหาพอร์ตโฟลิโอเพื่อการสาธิต บุคคลและการแต่งตั้งเป็นเรื่องสมมติ'],
                    ]),
                ],
            ],
        ];

        foreach ($articles as $data) {
            $category = $categoryModels[$data['category']] ?? $categoryModels['news'];
            $author = $authors[$data['author']] ?? $authors->first();
            $published = now()->subDays($data['days']);

            $article = Article::where('site_id', $site->id)
                ->whereHas('translations', fn ($query) => $query->where('slug', $data['slug']))
                ->first();

            if (! $article) {
                $article = new Article([
                    'site_id' => $site->id,
                    'author_id' => $author?->id,
                    'type' => $data['type'],
                ]);
            }

            $article->fill([
                'category_id' => $category->id,
                'status' => 'published',
                'featured_media_id' => $media[$data['image']]?->id,
                'is_featured' => $data['featured'],
                'published_at' => $published,
                'updated_content_at' => $published->copy()->addDays(1),
                'created_by' => $author?->id,
                'updated_by' => $author?->id,
            ])->save();

            foreach (['en', 'th'] as $locale) {
                $translation = $data[$locale];
                $body = $translation['body'];
                $article->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'site_id' => $site->id,
                        'title' => $translation['title'],
                        'slug' => $data['slug'],
                        'excerpt' => $translation['excerpt'],
                        'body' => $body,
                        'toc' => $this->toc($body),
                    ]
                );
            }

            $article->update([
                'reading_time' => $sanitizer->readingTime($sanitizer->renderTiptapDocument($data['en']['body'])),
            ]);

            $tagNames = match ($data['category']) {
                'digital' => ['Data', 'Integration'],
                'operations' => ['Automation', 'Operations'],
                'resilience' => ['Infrastructure', 'Security'],
                'reports' => ['Industry', 'Energy'],
                default => ['Company'],
            };

            $tagIds = collect($tagNames)->map(fn ($name) => Tag::firstOrCreate(
                ['site_id' => $site->id, 'slug' => Str::slug($name)],
                ['name' => ['en' => $name, 'th' => $name, 'zh-CN' => $name]]
            )->id);

            $article->tags()->sync($tagIds);
        }
    }

    protected function doc(array $blocks): array
    {
        $content = [];

        foreach ($blocks as [$type, $value]) {
            if ($type === 'p') {
                $content[] = ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $value]]];
            } elseif ($type === 'h2') {
                $content[] = ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => $value]]];
            } elseif ($type === 'ul') {
                $content[] = [
                    'type' => 'bulletList',
                    'content' => array_map(fn ($item) => [
                        'type' => 'listItem',
                        'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $item]]]],
                    ], $value),
                ];
            }
        }

        return ['type' => 'doc', 'content' => $content];
    }

    protected function toc(array $document): array
    {
        $toc = [];

        foreach ($document['content'] ?? [] as $node) {
            if (($node['type'] ?? null) !== 'heading') {
                continue;
            }

            $text = collect($node['content'] ?? [])->pluck('text')->implode(' ');

            if ($text !== '') {
                $toc[] = [
                    'level' => (int) ($node['attrs']['level'] ?? 2),
                    'text' => $text,
                    'id' => Str::slug($text) ?: 'section-'.(count($toc) + 1),
                ];
            }
        }

        return $toc;
    }
}
