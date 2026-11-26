<?php

namespace App\Http\Controllers\Api\V1\PublicApi;

use App\Cms\BlockResolver;
use App\Cms\SiteContext;
use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use App\Notifications\ContentWorkflowNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class PublicFormController extends Controller
{
    public function __construct(
        protected SiteContext $context,
        protected BlockResolver $blocks,
    ) {}

    public function submit(Request $request, string $key): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $locale = $this->context->locale();
        $form = Form::forSite($site)->where('key', $key)->where('is_active', true)->first();

        abort_unless($form, 404, 'This form is not available.');

        $schema = $this->blocks->formSchema($form, $locale);
        $rules = $this->rulesFromSchema($schema['fields'], $request);
        $validated = $request->validate($rules);

        $honeypot = trim((string) $request->input('website', ''));
        $spamScore = $honeypot !== '' ? 5 : 0;
        $message = (string) ($validated['message'] ?? '');

        if (preg_match_all('/https?:\/\//i', $message) > 2) {
            $spamScore += 2;
        }

        if ((int) $request->input('elapsed_ms', 5000) < 1200) {
            $spamScore += 2;
        }

        $data = collect($validated)->except(['consent', 'website', 'elapsed_ms'])->all();
        $data['consent'] = true;

        $submission = FormSubmission::create([
            'site_id' => $site->id,
            'form_id' => $form->id,
            'data' => $data,
            'meta' => [
                'locale' => $locale,
                'page' => $request->input('page'),
                'elapsed_ms' => $request->input('elapsed_ms'),
                'source' => $request->input('source', 'website'),
            ],
            'status' => $spamScore >= 5 ? 'spam' : 'new',
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'spam_score' => $spamScore,
            'is_demo' => false,
        ]);

        $lead = null;

        if ($spamScore < 5 && (data_get($form->settings, 'creates_lead', true))) {
            $lead = Lead::create([
                'site_id' => $site->id,
                'submission_id' => $submission->id,
                'name' => (string) ($data['name'] ?? 'Website visitor'),
                'email' => (string) ($data['email'] ?? 'unknown@example.com'),
                'phone' => $data['phone'] ?? null,
                'company' => $data['company'] ?? null,
                'message' => $data['message'] ?? null,
                'source' => $form->key,
                'status' => 'new',
                'priority' => ($data['department'] ?? '') === 'support' ? 'high' : 'normal',
                'last_activity_at' => now(),
            ]);

            LeadActivity::create([
                'lead_id' => $lead->id,
                'user_id' => null,
                'type' => 'created',
                'note' => 'Lead created from a public website submission.',
            ]);

            $this->notifyTeam($site, $lead);
        }

        return response()->json([
            'message' => data_get($form->settings, 'confirmation_message', 'Thank you. Your message has been received.'),
            'reference' => 'MRD-'.str_pad((string) $submission->id, 6, '0', STR_PAD_LEFT),
        ], 201);
    }

    protected function rulesFromSchema(array $fields, Request $request): array
    {
        $rules = ['website' => ['nullable', 'string', 'max:200'], 'elapsed_ms' => ['nullable', 'integer']];

        foreach ($fields as $field) {
            $name = $field['name'] ?? null;
            $type = $field['type'] ?? 'text';

            if (! $name) {
                continue;
            }

            $fieldRules = [];
            $fieldRules[] = ! empty($field['required']) ? 'required' : 'nullable';

            $fieldRules = match ($type) {
                'email' => array_merge($fieldRules, ['email', 'max:254']),
                'tel', 'phone' => array_merge($fieldRules, ['string', 'max:40']),
                'number' => array_merge($fieldRules, ['numeric']),
                'date' => array_merge($fieldRules, ['date']),
                'textarea' => array_merge($fieldRules, ['string', 'max:6000']),
                'select', 'radio' => array_merge($fieldRules, ['string', 'in:'.implode(',', array_column($field['options'] ?? [], 'value'))]),
                'checkbox' => array_merge($fieldRules, ['boolean']),
                'consent' => array_merge($fieldRules, ['accepted']),
                'file' => array_merge($fieldRules, ['file', 'max:5120']),
                default => array_merge($fieldRules, ['string', 'max:500']),
            };

            $rules[$name] = array_values(array_unique($fieldRules));
        }

        return $rules;
    }

    protected function notifyTeam($site, Lead $lead): void
    {
        $recipients = User::where('is_active', true)
            ->where(function ($query) use ($site) {
                $query->whereHas('roles', fn ($role) => $role->where('role_user.site_id', $site->id)
                    ->whereHas('permissions', fn ($permission) => $permission->where('slug', 'leads.manage')))
                    ->orWhereHas('roles', fn ($role) => $role->where('slug', 'super_admin'));
            })
            ->get();

        Notification::send($recipients, new ContentWorkflowNotification(
            'New website enquiry',
            "{$lead->name} submitted a {$lead->source} enquiry.",
            rtrim((string) config('meridian.studio_url'), '/').'/leads/'.$lead->id,
            'lead',
        ));
    }
}
