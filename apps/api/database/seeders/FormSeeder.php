<?php

namespace Database\Seeders;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;

class FormSeeder extends Seeder
{
    public function run(): void
    {
        $site = Site::where('slug', 'meridian')->firstOrFail();
        $owner = User::where('email', 'manager@meridian.example')->first();

        $forms = [
            'contact' => [
                'name' => 'Business enquiry',
                'description' => 'General project and capability enquiry.',
                'schema' => $this->schema([
                    $this->field('name', 'text', ['en' => 'Full name', 'th' => 'ชื่อ-นามสกุล', 'zh-CN' => '姓名'], true),
                    $this->field('email', 'email', ['en' => 'Work email', 'th' => 'อีเมลที่ทำงาน', 'zh-CN' => '工作邮箱'], true),
                    $this->field('phone', 'tel', ['en' => 'Phone', 'th' => 'โทรศัพท์', 'zh-CN' => '电话'], false),
                    $this->field('company', 'text', ['en' => 'Company', 'th' => 'บริษัท', 'zh-CN' => '公司'], false),
                    $this->field('department', 'select', ['en' => 'What is this about?', 'th' => 'เรื่องที่ต้องการติดต่อ', 'zh-CN' => '咨询主题'], true, [
                        ['value' => 'project', 'label' => ['en' => 'A new project', 'th' => 'โครงการใหม่', 'zh-CN' => '新项目']],
                        ['value' => 'partnership', 'label' => ['en' => 'Partnership', 'th' => 'ความร่วมมือ', 'zh-CN' => '合作']],
                        ['value' => 'support', 'label' => ['en' => 'Existing system support', 'th' => 'สนับสนุนระบบเดิม', 'zh-CN' => '现有系统支持']],
                        ['value' => 'careers', 'label' => ['en' => 'Careers', 'th' => 'ร่วมงาน', 'zh-CN' => '招聘']],
                    ]),
                    $this->field('message', 'textarea', ['en' => 'How can we help?', 'th' => 'เราช่วยอะไรได้บ้าง', 'zh-CN' => '我们可以如何帮助？'], true),
                    $this->field('consent', 'consent', ['en' => 'I consent to Meridian using these details to respond to my enquiry.', 'th' => 'ฉันยินยอมให้เมอริเดียนใช้ข้อมูลนี้เพื่อตอบข้อสอบถาม', 'zh-CN' => '我同意 Meridian 使用这些信息回复我的咨询。'], true),
                ]),
                'settings' => [
                    'creates_lead' => true,
                    'confirmation_message' => 'Thank you. Your enquiry has been received and our team will respond within two working days.',
                    'notification_email' => 'hello@meridian.example',
                ],
            ],
            'partnership' => [
                'name' => 'Partnership enquiry',
                'description' => 'Vendor, academic, and delivery partnership enquiries.',
                'schema' => $this->schema([
                    $this->field('name', 'text', ['en' => 'Full name', 'th' => 'ชื่อ-นามสกุล', 'zh-CN' => '姓名'], true),
                    $this->field('email', 'email', ['en' => 'Work email', 'th' => 'อีเมลที่ทำงาน', 'zh-CN' => '工作邮箱'], true),
                    $this->field('company', 'text', ['en' => 'Organisation', 'th' => 'องค์กร', 'zh-CN' => '组织'], true),
                    $this->field('partnership_type', 'select', ['en' => 'Partnership type', 'th' => 'ประเภทความร่วมมือ', 'zh-CN' => '合作类型'], true, [
                        ['value' => 'technology', 'label' => ['en' => 'Technology', 'th' => 'เทคโนโลยี', 'zh-CN' => '技术']],
                        ['value' => 'delivery', 'label' => ['en' => 'Delivery', 'th' => 'การส่งมอบ', 'zh-CN' => '交付']],
                        ['value' => 'academic', 'label' => ['en' => 'Academic', 'th' => 'การศึกษา', 'zh-CN' => '学术']],
                    ]),
                    $this->field('message', 'textarea', ['en' => 'Tell us about the fit', 'th' => 'เล่าเกี่ยวกับความเหมาะสม', 'zh-CN' => '请介绍合作契合点'], true),
                    $this->field('consent', 'consent', ['en' => 'I consent to being contacted about this enquiry.', 'th' => 'ฉันยินยอมให้ติดต่อกลับเกี่ยวกับข้อสอบถามนี้', 'zh-CN' => '我同意就此咨询被联系。'], true),
                ]),
                'settings' => ['creates_lead' => true, 'confirmation_message' => 'Thank you. Our partnership team will be in touch.'],
            ],
            'general' => [
                'name' => 'General enquiry',
                'description' => 'General questions and media enquiries.',
                'schema' => $this->schema([
                    $this->field('name', 'text', ['en' => 'Name', 'th' => 'ชื่อ', 'zh-CN' => '姓名'], true),
                    $this->field('email', 'email', ['en' => 'Email', 'th' => 'อีเมล', 'zh-CN' => '邮箱'], true),
                    $this->field('message', 'textarea', ['en' => 'Message', 'th' => 'ข้อความ', 'zh-CN' => '留言'], true),
                    $this->field('consent', 'consent', ['en' => 'I consent to being contacted.', 'th' => 'ฉันยินยอมให้ติดต่อกลับ', 'zh-CN' => '我同意被联系。'], true),
                ]),
                'settings' => ['creates_lead' => true, 'confirmation_message' => 'Thank you. We will respond as soon as possible.'],
            ],
        ];

        foreach ($forms as $key => $form) {
            Form::updateOrCreate(
                ['site_id' => $site->id, 'key' => $key],
                [
                    'name' => $form['name'],
                    'description' => $form['description'],
                    'schema' => $form['schema'],
                    'settings' => $form['settings'],
                    'is_active' => true,
                ]
            );
        }

        $contact = Form::where('site_id', $site->id)->where('key', 'contact')->firstOrFail();
        $partnership = Form::where('site_id', $site->id)->where('key', 'partnership')->firstOrFail();

        $demos = [
            ['form' => $contact, 'name' => 'Nicha Wong', 'email' => 'nicha.w@example.com', 'company' => 'Example Foods', 'status' => 'new', 'priority' => 'high', 'days' => 2, 'source' => 'contact', 'message' => 'We are planning a new packaging line in Chonburi and need help with controls and traceability.'],
            ['form' => $contact, 'name' => 'Arthit S.', 'email' => 'arthit.s@example.com', 'company' => 'Sample Logistics', 'status' => 'contacted', 'priority' => 'normal', 'days' => 5, 'source' => 'contact', 'message' => 'Our distribution centre needs better visibility between conveyor control and the warehouse system.'],
            ['form' => $partnership, 'name' => 'Lena Park', 'email' => 'lena.park@example.com', 'company' => 'Example Controls', 'status' => 'qualified', 'priority' => 'normal', 'days' => 9, 'source' => 'partnership', 'message' => 'We would like to discuss a regional delivery partnership for PLC and safety projects.'],
            ['form' => $contact, 'name' => 'Thanawat K.', 'email' => 'thanawat.k@example.com', 'company' => 'Demo Utilities', 'status' => 'new', 'priority' => 'urgent', 'days' => 12, 'source' => 'contact', 'message' => 'A water utility project needs telemetry integration across 38 pump stations.'],
            ['form' => $contact, 'name' => 'Mika Tanaka', 'email' => 'mika.t@example.com', 'company' => 'Example Property', 'status' => 'won', 'priority' => 'high', 'days' => 18, 'source' => 'contact', 'message' => 'We need a metering and controls review for a mixed-use building.'],
            ['form' => $partnership, 'name' => 'Somsak P.', 'email' => 'somsak.p@example.com', 'company' => 'Sample Engineering School', 'status' => 'lost', 'priority' => 'low', 'days' => 24, 'source' => 'partnership', 'message' => 'Exploring an academic collaboration on industrial data curricula.'],
        ];

        foreach ($demos as $demo) {
            $created = now()->subDays($demo['days']);

            $submission = FormSubmission::updateOrCreate(
                ['site_id' => $site->id, 'form_id' => $demo['form']->id, 'data->email' => $demo['email']],
                [
                    'data' => [
                        'name' => $demo['name'],
                        'email' => $demo['email'],
                        'company' => $demo['company'],
                        'message' => $demo['message'],
                        'consent' => true,
                    ],
                    'meta' => ['locale' => 'en', 'source' => 'seed', 'demo' => true],
                    'status' => 'new',
                    'ip_address' => '203.0.113.10',
                    'user_agent' => 'Meridian demo seed',
                    'spam_score' => 0,
                    'is_demo' => true,
                    'created_at' => $created,
                    'updated_at' => $created,
                ]
            );

            $lead = Lead::updateOrCreate(
                ['site_id' => $site->id, 'submission_id' => $submission->id],
                [
                    'name' => $demo['name'],
                    'email' => $demo['email'],
                    'company' => $demo['company'],
                    'message' => $demo['message'],
                    'source' => $demo['source'],
                    'status' => $demo['status'],
                    'priority' => $demo['priority'],
                    'owner_id' => $owner?->id,
                    'follow_up_at' => now()->addDays(3),
                    'last_activity_at' => $created->copy()->addDay(),
                    'is_demo' => true,
                    'created_at' => $created,
                    'updated_at' => $created,
                ]
            );

            LeadActivity::firstOrCreate(
                ['lead_id' => $lead->id, 'type' => 'created'],
                ['user_id' => null, 'note' => 'Demonstration lead created from sample website data.']
            );
        }
    }

    protected function field(string $name, string $type, array $label, bool $required = false, array $options = []): array
    {
        return [
            'name' => $name,
            'type' => $type,
            'label' => $label,
            'placeholder' => [],
            'required' => $required,
            'options' => $options,
            'width' => in_array($type, ['textarea', 'consent'], true) ? 'full' : 'half',
        ];
    }

    protected function schema(array $fields): array
    {
        return ['version' => 1, 'fields' => $fields];
    }
}
