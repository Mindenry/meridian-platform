<?php

namespace Database\Seeders;

use App\Models\CaseStudy;
use App\Models\Industry;
use App\Models\MediaAsset;
use App\Models\Site;
use App\Models\Solution;
use App\Models\User;
use Illuminate\Database\Seeder;

class CaseStudySeeder extends Seeder
{
    public function run(): void
    {
        $site = Site::where('slug', 'meridian')->firstOrFail();
        $media = MediaAsset::where('site_id', $site->id)->get()->keyBy(fn (MediaAsset $asset) => pathinfo($asset->original_name, PATHINFO_FILENAME));
        $industries = Industry::where('site_id', $site->id)->get()->keyBy('key');
        $solutions = Solution::where('site_id', $site->id)->get()->keyBy('key');
        $author = User::where('email', 'editor@meridian.example')->first();

        $studies = [
            'rayong-precision-line' => [
                'year' => 2025, 'industry' => 'manufacturing', 'image' => 'precision', 'featured' => true,
                'solutions' => ['industrial-automation', 'smart-manufacturing'],
                'en' => [
                    'title' => 'A precision line that learns before it stops',
                    'slug' => 'rayong-precision-line',
                    'summary' => 'A Rayong precision-components plant rebuilt its final assembly and inspection line around traceability and changeover speed.',
                    'challenge' => 'The plant ran three product families on one line with changeovers that consumed most of a shift. Inspection results were recorded on paper, so a quality deviation could take days to trace to a machine or batch.',
                    'strategy' => 'We mapped the changeover as a system rather than a sequence of tasks: tooling, recipes, quality gates, and operator decisions. The control architecture was designed around one traceable product record, with a staged migration that kept the old line running during build.',
                    'implementation' => 'A new robotic assembly cell, in-line vision inspection, and a supervisor-facing OEE view were commissioned over two shutdown windows. Existing PLCs were retained where reliable; obsolete safety controls were replaced with a documented, tested architecture.',
                    'results' => [
                        ['value' => '38%', 'label' => 'Less unplanned downtime', 'note' => 'Illustrative outcome'],
                        ['value' => '6.8×', 'label' => 'Faster product changeover', 'note' => 'Illustrative outcome'],
                        ['value' => '99.2%', 'label' => 'Traceability coverage', 'note' => 'Illustrative outcome'],
                    ],
                    'gallery' => [
                        ['image' => 'manufacturing', 'caption' => 'Assembly environment before the line upgrade.'],
                        ['image' => 'automation', 'caption' => 'Robotic cell integration and end-of-arm tooling.'],
                    ],
                ],
                'th' => [
                    'title' => 'สายการผลิตความแม่นยำที่เรียนรู้ก่อนหยุด',
                    'slug' => 'rayong-precision-line',
                    'summary' => 'โรงงานชิ้นส่วนความแม่นยำในระยองปรับสายประกอบและตรวจสอบขั้นสุดท้ายใหม่ โดยยึดการตรวจสอบย้อนกลับและความเร็วในการเปลี่ยนรุ่น',
                    'challenge' => 'โรงงานผลิตสามกลุ่มผลิตภัณฑ์บนสายเดียว โดยการเปลี่ยนรุ่นกินเวลาเกือบทั้งกะ ผลตรวจสอบถูกบันทึกบนกระดาษ ทำให้การตามหาความเบี่ยงเบนคุณภาพไปยังเครื่องจักรหรือล็อตใช้เวลาหลายวัน',
                    'strategy' => 'เรามองการเปลี่ยนรุ่นเป็นระบบ ไม่ใช่ลำดับงานแยกส่วน ทั้งเครื่องมือ สูตรการผลิต จุดตรวจคุณภาพ และการตัดสินใจของผู้ปฏิบัติงาน สถาปัตยกรรมควบคุมออกแบบรอบบันทึกผลิตภัณฑ์ที่ตรวจสอบได้หนึ่งระเบียน พร้อมการย้ายระบบเป็นระยะที่ให้สายเดิมเดินต่อได้ระหว่างก่อสร้าง',
                    'implementation' => 'ติดตั้งเซลล์ประกอบหุ่นยนต์ ระบบตรวจด้วยภาพแบบอินไลน์ และมุมมอง OEE สำหรับหัวหน้างาน ภายในช่วงหยุดสองครั้ง เก็บ PLC เดิมที่ยังเชื่อถือได้ และเปลี่ยนระบบความปลอดภัยที่ล้าสมัยด้วยสถาปัตยกรรมที่จัดทำเอกสารและทดสอบแล้ว',
                    'results' => [
                        ['value' => '38%', 'label' => 'เวลาหยุดโดยไม่วางแผนลดลง', 'note' => 'ผลลัพธ์ตัวอย่าง'],
                        ['value' => '6.8×', 'label' => 'เปลี่ยนรุ่นเร็วขึ้น', 'note' => 'ผลลัพธ์ตัวอย่าง'],
                        ['value' => '99.2%', 'label' => 'ความครอบคลุมการตรวจสอบย้อนกลับ', 'note' => 'ผลลัพธ์ตัวอย่าง'],
                    ],
                    'gallery' => [
                        ['image' => 'manufacturing', 'caption' => 'พื้นที่ประกอบก่อนปรับปรุงสายการผลิต'],
                        ['image' => 'automation', 'caption' => 'การเชื่อมต่อเซลล์หุ่นยนต์และเครื่องมือปลายแขน'],
                    ],
                ],
            ],
            'laem-chabang-port-flow' => [
                'year' => 2024, 'industry' => 'logistics-distribution', 'image' => 'infrastructure', 'featured' => true,
                'solutions' => ['system-integration', 'digital-transformation'],
                'en' => [
                    'title' => 'Moving a terminal from peak recovery to peak readiness',
                    'slug' => 'laem-chabang-port-flow',
                    'summary' => 'A Laem Chabang container terminal connected gate, yard, and crane data so peak planning started before the queue formed.',
                    'challenge' => 'Terminal teams relied on separate gate, yard, and crane systems. Planners saw the consequences of congestion after it appeared, and every peak shift ended with manual reconciliation.',
                    'strategy' => 'We defined one operational event model across the three systems, then built a controlled integration layer rather than more point-to-point links. A small planning cockpit was designed with dispatchers and tested against a live peak window.',
                    'implementation' => 'Gate OCR events, yard moves, and crane cycles were streamed into a shared event bus with observability and replay. Dispatch received a fourteen-hour look-ahead, and exception alerts replaced end-of-shift reconciliation.',
                    'results' => [
                        ['value' => '31%', 'label' => 'Higher peak throughput', 'note' => 'Illustrative outcome'],
                        ['value' => '99.4%', 'label' => 'Inventory accuracy', 'note' => 'Illustrative outcome'],
                        ['value' => '18%', 'label' => 'Lower energy per move', 'note' => 'Illustrative outcome'],
                    ],
                    'gallery' => [
                        ['image' => 'hero-facility', 'caption' => 'Container operations at the terminal.'],
                        ['image' => 'logistics', 'caption' => 'Warehouse and yard coordination environment.'],
                    ],
                ],
                'th' => [
                    'title' => 'จากกู้วิกฤตช่วงพีค สู่ความพร้อมล่วงหน้าของท่าเรือ',
                    'slug' => 'laem-chabang-port-flow',
                    'summary' => 'ท่าเรือคอนเทนเนอร์แหลมฉบังเชื่อมข้อมูลประตู ลานจอด และเครน เพื่อให้การวางแผนช่วงพีคเริ่มก่อนคิวจะก่อตัว',
                    'challenge' => 'ทีมท่าเรือใช้ระบบประตู ลานจอด และเครนแยกกัน ผู้วางแผนเห็นผลของความแออัดหลังเกิดขึ้นแล้ว และทุกกะพีคจบด้วยการกระทบยอดด้วยมือ',
                    'strategy' => 'เรานิยามโมเดลเหตุการณ์ปฏิบัติการเดียวสำหรับสามระบบ แล้วสร้างชั้นเชื่อมต่อที่ควบคุมแทนการเพิ่มลิงก์แบบจุดต่อจุด ค็อกพิตการวางแผนขนาดเล็กออกแบบร่วมกับเจ้าหน้าที่และทดสอบกับช่วงพีคจริง',
                    'implementation' => 'เหตุการณ์ OCR ที่ประตู การเคลื่อนย้ายในลาน และรอบเครนถูกสตรีมเข้าบัสเหตุการณ์ร่วมที่มีการสังเกตและการเล่นซ้ำ ฝ่ายจัดส่งได้รับมุมมองล่วงหน้าสิบสี่ชั่วโมง และการแจ้งเตือนข้อยกเว้นแทนการกระทบยอดท้ายกะ',
                    'results' => [
                        ['value' => '31%', 'label' => 'กำลังการผลิตช่วงพีคสูงขึ้น', 'note' => 'ผลลัพธ์ตัวอย่าง'],
                        ['value' => '99.4%', 'label' => 'ความแม่นยำสินค้าคงคลัง', 'note' => 'ผลลัพธ์ตัวอย่าง'],
                        ['value' => '18%', 'label' => 'พลังงานต่อการเคลื่อนย้ายลดลง', 'note' => 'ผลลัพธ์ตัวอย่าง'],
                    ],
                    'gallery' => [
                        ['image' => 'hero-facility', 'caption' => 'การปฏิบัติงานตู้คอนเทนเนอร์ที่ท่าเรือ'],
                        ['image' => 'logistics', 'caption' => 'สภาพแวดล้อมการประสานงานคลังและลานจอด'],
                    ],
                ],
            ],
            'bangkok-tower-energy' => [
                'year' => 2024, 'industry' => 'commercial-industrial-property', 'image' => 'energy', 'featured' => false,
                'solutions' => ['infrastructure-engineering', 'digital-transformation'],
                'en' => [
                    'title' => 'Making a Bangkok tower\'s energy visible floor by floor',
                    'slug' => 'bangkok-tower-energy',
                    'summary' => 'A mixed-use Bangkok property replaced building-level guesses with system-level metering and a maintainable controls strategy.',
                    'challenge' => 'Energy was measured at the main incomer, so disputes about tenant comfort and cost allocation had no evidence. Critical power testing was annual and largely documentary.',
                    'strategy' => 'We surveyed the MEP systems, corrected the metering topology, and prioritised the systems that drove both cost and complaints. The controls strategy was simplified before any new dashboard was added.',
                    'implementation' => 'Sub-metering was installed across chillers, AHUs, and tenant risers; control sequences were retuned and documented. A resilience test plan now exercises backup power under load twice a year, with captured evidence.',
                    'results' => [
                        ['value' => '23%', 'label' => 'Lower energy intensity', 'note' => 'Illustrative outcome'],
                        ['value' => '38%', 'label' => 'Fewer comfort complaints', 'note' => 'Illustrative outcome'],
                        ['value' => '99.9%', 'label' => 'Critical power availability', 'note' => 'Illustrative outcome'],
                    ],
                    'gallery' => [
                        ['image' => 'energy', 'caption' => 'Electrical infrastructure supporting the property.'],
                        ['image' => 'site-plan', 'caption' => 'Illustrative systems plan used during the survey.'],
                    ],
                ],
                'th' => [
                    'title' => 'ทำให้พลังงานของอาคารในกรุงเทพฯ มองเห็นได้รายชั้น',
                    'slug' => 'bangkok-tower-energy',
                    'summary' => 'อาคารใช้งานผสมในกรุงเทพฯ เปลี่ยนจากการเดาระดับอาคารมาเป็นการวัดระดับระบบและกลยุทธ์ควบคุมที่ดูแลได้',
                    'challenge' => 'วัดพลังงานที่จุดเข้าหลัก ทำให้ ข้อโต้แย้งเรื่องความสบายและ การปันส่วนต้นทุนไม่มีหลักฐาน การทดสอบไฟสำรองทำปีละครั้งและเน้นเอกสาร',
                    'strategy' => 'เราสำรวจระบบ MEP แก้โทโพโลยีมาตรวัด และจัดลำดับระบบที่ขับเคลื่อนทั้งต้นทุนและข้อร้องเรียน กลยุทธ์ควบคุมถูกทำให้เรียบง่ายก่อนเพิ่มแดชบอร์ดใหม่',
                    'implementation' => 'ติดตั้งมิเตอร์ย่อยสำหรับชิลเลอร์ AHU และไรเซอร์ผู้เช่า ปรับแต่งและจัดทำเอกสารลำดับการควบคุม แผนทดสอบความทนทานทดสอบไฟสำรองภายใต้ภาระปีละสองครั้งพร้อมเก็บหลักฐาน',
                    'results' => [
                        ['value' => '23%', 'label' => 'ความเข้มการใช้พลังงานต่ำลง', 'note' => 'ผลลัพธ์ตัวอย่าง'],
                        ['value' => '38%', 'label' => 'ข้อร้องเรียนเรื่องความสบายลดลง', 'note' => 'ผลลัพธ์ตัวอย่าง'],
                        ['value' => '99.9%', 'label' => 'ความพร้อมไฟฟ้าสำคัญ', 'note' => 'ผลลัพธ์ตัวอย่าง'],
                    ],
                    'gallery' => [
                        ['image' => 'energy', 'caption' => 'โครงสร้างพื้นฐานไฟฟ้าที่รองรับอาคาร'],
                        ['image' => 'site-plan', 'caption' => 'แปลนระบบเพื่อการสาธิตที่ใช้ระหว่างสำรวจ'],
                    ],
                ],
            ],
            'chonburi-smart-factory' => [
                'year' => 2023, 'industry' => 'manufacturing', 'image' => 'manufacturing', 'featured' => false,
                'solutions' => ['smart-manufacturing', 'industrial-software'],
                'en' => [
                    'title' => 'Giving a Chonburi plant a shift-level view of loss',
                    'slug' => 'chonburi-smart-factory',
                    'summary' => 'A consumer-goods plant moved OEE from a weekly spreadsheet to a supervisor routine that acted inside the shift.',
                    'challenge' => 'Machine data existed but stop reasons were entered inconsistently. The plant could explain last month but not the last hour.',
                    'strategy' => 'We instrumented the constraint line first and co-designed a small set of stop reasons with supervisors. The data model was built to extend to other lines without redefinition.',
                    'implementation' => 'Edge gateways collected cycle and stop signals without touching the control network. A supervisor screen and shift handover routine were introduced, then extended to two more lines after eight weeks.',
                    'results' => [
                        ['value' => '42%', 'label' => 'Less unplanned downtime', 'note' => 'Illustrative outcome'],
                        ['value' => '14%', 'label' => 'OEE improvement', 'note' => 'Illustrative outcome'],
                        ['value' => '21%', 'label' => 'Less scrap', 'note' => 'Illustrative outcome'],
                    ],
                    'gallery' => [
                        ['image' => 'manufacturing', 'caption' => 'Production line where the constraint model was piloted.'],
                        ['image' => 'precision', 'caption' => 'Machined component quality inspection context.'],
                    ],
                ],
                'th' => [
                    'title' => 'มุมมองความสูญเสียรายกะสำหรับโรงงานในชลบุรี',
                    'slug' => 'chonburi-smart-factory',
                    'summary' => 'โรงงานสินค้าอุปโภคบริโภคเปลี่ยน OEE จากสเปรดชีตรายสัปดาห์เป็น routine ของหัวหน้างานที่ลงมือภายในกะ',
                    'challenge' => 'มีข้อมูลเครื่องจักรแต่การบันทึกสาเหตุการหยุดไม่สม่ำเสมอ โรงงานอธิบายเดือนที่แล้วได้ แต่ชั่วโมงที่แล้วไม่ได้',
                    'strategy' => 'เราเริ่มวัดที่สายคอขวดและร่วมออกแบบสาเหตุการหยุดชุดเล็กกับหัวหน้างาน โมเดลข้อมูลออกแบบให้ขยายไปสายอื่นได้โดยไม่ต้องนิยามใหม่',
                    'implementation' => 'เกตเวย์ Edge เก็บสัญญาณรอบเวลาและการหยุดโดยไม่แตะเครือข่ายควบคุม เปิดใช้หน้าจอหัวหน้างานและ routine ส่งมอบกะ แล้วขยายอีกสองสายภายในแปดสัปดาห์',
                    'results' => [
                        ['value' => '42%', 'label' => 'เวลาหยุดโดยไม่วางแผนลดลง', 'note' => 'ผลลัพธ์ตัวอย่าง'],
                        ['value' => '14%', 'label' => 'OEE ดีขึ้น', 'note' => 'ผลลัพธ์ตัวอย่าง'],
                        ['value' => '21%', 'label' => 'ของเสียลดลง', 'note' => 'ผลลัพธ์ตัวอย่าง'],
                    ],
                    'gallery' => [
                        ['image' => 'manufacturing', 'caption' => 'สายการผลิตที่นำร่องโมเดลคอขวด'],
                        ['image' => 'precision', 'caption' => 'บริบทการตรวจคุณภาพชิ้นส่วน machining'],
                    ],
                ],
            ],
            'khon-kaen-water-network' => [
                'year' => 2023, 'industry' => 'infrastructure', 'image' => 'site-plan', 'featured' => false,
                'solutions' => ['infrastructure-engineering', 'system-integration'],
                'en' => [
                    'title' => 'A water network that finds its own losses',
                    'slug' => 'khon-kaen-water-network',
                    'summary' => 'A regional water utility connected pump stations and district meters to cut restoration time and technical losses.',
                    'challenge' => 'Operators learned about failures from customer calls. District losses were estimated annually, and pump stations used inconsistent telemetry.',
                    'strategy' => 'We defined a district metered area model and a common telemetry standard before adding sensors. The operations team helped choose the alarms that would actually change a night shift.',
                    'implementation' => 'Thirty-eight stations were connected through a resilient telemetry layer with local buffering. Leak alarms were correlated with pressure and flow, and restoration procedures were rewritten around the new signals.',
                    'results' => [
                        ['value' => '27%', 'label' => 'Fewer supply interruptions', 'note' => 'Illustrative outcome'],
                        ['value' => '3.1×', 'label' => 'Faster fault restoration', 'note' => 'Illustrative outcome'],
                        ['value' => '14%', 'label' => 'Lower technical losses', 'note' => 'Illustrative outcome'],
                    ],
                    'gallery' => [
                        ['image' => 'site-plan', 'caption' => 'Illustrative network plan used for district metering.'],
                        ['image' => 'energy', 'caption' => 'Electrical and telemetry infrastructure context.'],
                    ],
                ],
                'th' => [
                    'title' => 'โครงข่ายน้ำที่ค้นหาความสูญเสียได้ด้วยตัวเอง',
                    'slug' => 'khon-kaen-water-network',
                    'summary' => 'การประปาภูมิภาคเชื่อมสถานีสูบน้ำและมาตรวัดเขตเพื่อลดเวลากู้คืนและความสูญเสียทางเทคนิค',
                    'challenge' => 'ผู้ปฏิบัติงานรู้ปัญหาจากสายลูกค้า ความสูญเสียรายเขตประมาณปีละครั้ง และสถานีสูบน้ำใช้ระบบเทเลเมตรีไม่สม่ำเสมอ',
                    'strategy' => 'เรานิยามโมเดลพื้นที่วัดเขตและมาตรฐานเทเลเมตรีร่วมก่อนติดเซนเซอร์ ทีมปฏิบัติการมีส่วนเลือกสัญญาณเตือนที่จะเปลี่ยนกะกลางคืนได้จริง',
                    'implementation' => 'เชื่อม 38 สถานีผ่านชั้นเทเลเมตรีที่ทนทานพร้อมบัฟเฟอร์ในพื้นที่ สัญญาณรั่วถูก correlate กับแรงดันและการไหล และขั้นตอนกู้คืนถูกเขียนใหม่รอบสัญญาณใหม่',
                    'results' => [
                        ['value' => '27%', 'label' => 'เหตุจ่ายน้ำขัดข้องลดลง', 'note' => 'ผลลัพธ์ตัวอย่าง'],
                        ['value' => '3.1×', 'label' => 'กู้คืนระบบเร็วขึ้น', 'note' => 'ผลลัพธ์ตัวอย่าง'],
                        ['value' => '14%', 'label' => 'ความสูญเสียทางเทคนิคต่ำลง', 'note' => 'ผลลัพธ์ตัวอย่าง'],
                    ],
                    'gallery' => [
                        ['image' => 'site-plan', 'caption' => 'แปลนโครงข่ายเพื่อการสาธิตที่ใช้สำหรับมาตรวัดเขต'],
                        ['image' => 'energy', 'caption' => 'บริบทโครงสร้างพื้นฐานไฟฟ้าและเทเลเมตรี'],
                    ],
                ],
            ],
            'ayutthaya-distribution-hub' => [
                'year' => 2022, 'industry' => 'logistics-distribution', 'image' => 'logistics', 'featured' => false,
                'solutions' => ['system-integration', 'industrial-automation'],
                'en' => [
                    'title' => 'A distribution hub that stopped improvising at peak',
                    'slug' => 'ayutthaya-distribution-hub',
                    'summary' => 'An Ayutthaya distribution centre integrated conveyors, sortation, and WES events to stabilise peak throughput.',
                    'challenge' => 'Conveyor and sortation controls were commissioned separately from the warehouse system. At peak, exceptions were resolved by radio and manual re-scanning.',
                    'strategy' => 'We designed an exception-first integration model: every scan, divert, and failure had one owner and one visible state. The team rehearsed peak scenarios before the season.',
                    'implementation' => 'A middleware layer connected the WES and PLCs with replayable events. Operator screens showed blocked lanes and exception queues, and a peak control room was added for the eight busiest weeks.',
                    'results' => [
                        ['value' => '1.31×', 'label' => 'Peak throughput', 'note' => 'Illustrative outcome'],
                        ['value' => '99.4%', 'label' => 'Inventory accuracy', 'note' => 'Illustrative outcome'],
                        ['value' => '18%', 'label' => 'Lower energy per order', 'note' => 'Illustrative outcome'],
                    ],
                    'gallery' => [
                        ['image' => 'logistics', 'caption' => 'High-bay storage and picking environment.'],
                        ['image' => 'infrastructure', 'caption' => 'Terminal and distribution infrastructure context.'],
                    ],
                ],
                'th' => [
                    'title' => 'ศูนย์กระจายสินค้าที่เลิกแก้ปัญหาเฉพาะหน้าช่วงพีค',
                    'slug' => 'ayutthaya-distribution-hub',
                    'summary' => 'ศูนย์กระจายสินค้าอยุธยาเชื่อมสายพาน ระบบคัดแยก และเหตุการณ์ WES เพื่อทำให้กำลังการผลิตช่วงพีคมั่นคง',
                    'challenge' => 'ระบบควบคุมสายพานและคัดแยกถูกส่งมอบแยกจากระบบคลัง ช่วงพีคแก้ข้อยกเว้นด้วยวิทยุและสแกนซ้ำด้วยมือ',
                    'strategy' => 'เราออกแบบโมเดลเชื่อมต่อที่ให้ข้อยกเว้นมาก่อน ทุกการสแกน การเปลี่ยนเส้นทาง และความล้มเหลวมีเจ้าภาพเดียวและสถานะที่มองเห็นได้ ทีมซ้อมสถานการณ์พีคก่อนฤดูกาล',
                    'implementation' => 'ชั้นมิดเดิลแวร์เชื่อม WES และ PLC ด้วยเหตุการณ์ที่เล่นซ้ำได้ หน้าจอผู้ปฏิบัติงานแสดงเลนที่ติดขัดและคิวข้อยกเว้น พร้อมห้องควบคุมช่วงพีคแปดสัปดาห์ที่หนาแน่นที่สุด',
                    'results' => [
                        ['value' => '1.31×', 'label' => 'กำลังการผลิตช่วงพีค', 'note' => 'ผลลัพธ์ตัวอย่าง'],
                        ['value' => '99.4%', 'label' => 'ความแม่นยำสินค้าคงคลัง', 'note' => 'ผลลัพธ์ตัวอย่าง'],
                        ['value' => '18%', 'label' => 'พลังงานต่อออเดอร์ลดลง', 'note' => 'ผลลัพธ์ตัวอย่าง'],
                    ],
                    'gallery' => [
                        ['image' => 'logistics', 'caption' => 'พื้นที่จัดเก็บสูงและหยิบสินค้า'],
                        ['image' => 'infrastructure', 'caption' => 'บริบทโครงสร้างพื้นฐานท่าเรือและกระจายสินค้า'],
                    ],
                ],
            ],
        ];

        foreach ($studies as $key => $data) {
            $study = CaseStudy::updateOrCreate(
                ['site_id' => $site->id, 'year' => $data['year'], 'client_label' => 'Illustrative project - '.$key],
                [
                    'industry_id' => $industries[$data['industry']]?->id,
                    'status' => 'published',
                    'featured_media_id' => $media[$data['image']]?->id,
                    'published_at' => now()->subDays(30),
                    'is_featured' => $data['featured'],
                    'sort' => $data['year'],
                    'created_by' => $author?->id,
                    'updated_by' => $author?->id,
                ]
            );

            foreach (['en', 'th'] as $locale) {
                $translation = $data[$locale];
                $gallery = collect($translation['gallery'])->map(function ($item) use ($media, $locale) {
                    $asset = $media[$item['image']] ?? null;

                    return [
                        'url' => $asset?->url,
                        'alt' => $asset?->altFor($locale),
                        'caption' => $item['caption'],
                    ];
                })->filter(fn ($item) => $item['url'])->values()->all();

                $study->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'site_id' => $site->id,
                        'title' => $translation['title'],
                        'slug' => $translation['slug'],
                        'summary' => $translation['summary'],
                        'challenge' => $translation['challenge'],
                        'strategy' => $translation['strategy'],
                        'implementation' => $translation['implementation'],
                        'results' => $translation['results'],
                        'gallery' => $gallery,
                        'illustrative_note' => 'Illustrative portfolio content. Outcomes are demonstration data and are not client-verified results.',
                    ]
                );
            }

            $study->solutions()->sync(
                collect($data['solutions'])->map(fn ($solutionKey) => $solutions[$solutionKey]?->id)->filter()->all()
            );
        }
    }
}
