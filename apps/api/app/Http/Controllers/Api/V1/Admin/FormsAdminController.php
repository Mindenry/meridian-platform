<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Cms\SiteContext;
use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormsAdminController extends Controller
{
    public function __construct(protected SiteContext $context) {}

    public function index(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $forms = Form::forSite($site)->withCount('submissions')->orderBy('name')->get();

        return response()->json(['data' => $forms->map(fn (Form $form) => [
            'id' => $form->id,
            'key' => $form->key,
            'name' => $form->name,
            'description' => $form->description,
            'is_active' => $form->is_active,
            'fields_count' => count($form->schema['fields'] ?? []),
            'submissions_count' => $form->submissions_count,
            'last_submission_at' => $form->submissions()->latest()->value('created_at'),
            'creates_lead' => data_get($form->settings, 'creates_lead', true),
        ])->all()]);
    }

    public function show(Request $request, Form $form): JsonResponse
    {
        abort_unless($form->site_id === $this->context->id(), 404);

        return response()->json(['data' => [
            'id' => $form->id,
            'key' => $form->key,
            'name' => $form->name,
            'description' => $form->description,
            'is_active' => $form->is_active,
            'schema' => $form->schema,
            'settings' => $form->settings,
            'submissions_count' => $form->submissions()->count(),
        ]]);
    }

    public function update(Request $request, Form $form): JsonResponse
    {
        abort_unless($form->site_id === $this->context->id(), 404);
        abort_unless($request->user()->hasPermission('forms.manage', $form->site_id), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
            'schema' => ['sometimes', 'array'],
            'schema.fields' => ['required_with:schema', 'array', 'max:40'],
            'schema.fields.*.name' => ['required', 'string', 'max:60', 'regex:/^[a-z][a-z0-9_]*$/'],
            'schema.fields.*.type' => ['required', 'in:text,textarea,email,tel,phone,number,select,radio,checkbox,date,file,hidden,consent'],
            'schema.fields.*.required' => ['nullable', 'boolean'],
            'schema.fields.*.label' => ['required', 'array'],
            'settings' => ['nullable', 'array'],
        ]);

        if (isset($data['schema']['fields'])) {
            foreach ($data['schema']['fields'] as $index => $field) {
                if (in_array($field['type'], ['select', 'radio'], true) && empty($field['options'])) {
                    throw ValidationException::withMessages(["schema.fields.{$index}.options" => 'Select and radio fields need options.']);
                }
            }
        }

        $form->update(array_filter([
            'name' => $data['name'] ?? null,
            'description' => array_key_exists('description', $data) ? $data['description'] : null,
            'is_active' => $data['is_active'] ?? null,
            'schema' => $data['schema'] ?? null,
            'settings' => $data['settings'] ?? null,
        ], fn ($value) => $value !== null));

        return response()->json(['message' => 'Form saved.']);
    }

    public function submissions(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $perPage = min(100, max(5, (int) $request->query('per_page', 20)));

        $query = FormSubmission::forSite($site)->with('form')
            ->when($request->query('form_id'), fn ($q, $form) => $q->where('form_id', $form))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('q'), fn ($q, $search) => $q->where('data', 'like', '%'.$search.'%'))
            ->when($request->query('from'), fn ($q, $from) => $q->where('created_at', '>=', Carbon::parse($from)->startOfDay()))
            ->when($request->query('to'), fn ($q, $to) => $q->where('created_at', '<=', Carbon::parse($to)->endOfDay()))
            ->latest();

        $paginator = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'data' => collect($paginator->items())->map(fn (FormSubmission $submission) => [
                'id' => $submission->id,
                'form' => $submission->form?->name,
                'form_key' => $submission->form?->key,
                'summary' => $submission->data['name'] ?? $submission->data['email'] ?? 'Submission #'.$submission->id,
                'data' => $submission->data,
                'status' => $submission->status,
                'spam_score' => $submission->spam_score,
                'is_demo' => $submission->is_demo,
                'created_at' => $submission->created_at?->toIso8601String(),
            ])->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
            ],
        ]);
    }

    public function submission(Request $request, FormSubmission $submission): JsonResponse
    {
        abort_unless($submission->site_id === $this->context->id(), 404);

        return response()->json(['data' => [
            'id' => $submission->id,
            'form' => $submission->form?->name,
            'data' => $submission->data,
            'meta' => $submission->meta,
            'status' => $submission->status,
            'is_demo' => $submission->is_demo,
            'created_at' => $submission->created_at?->toIso8601String(),
        ]]);
    }

    public function updateSubmission(Request $request, FormSubmission $submission): JsonResponse
    {
        abort_unless($submission->site_id === $this->context->id(), 404);
        abort_unless($request->user()->hasPermission('leads.manage', $submission->site_id), 403);
        $data = $request->validate(['status' => ['required', 'in:new,read,archived,spam']]);
        $submission->update($data);

        return response()->json(['message' => 'Submission updated.']);
    }

    public function exportSubmissions(Request $request): StreamedResponse
    {
        $site = $this->context->siteOrFail();
        abort_unless($request->user()->hasPermission('leads.export', $site->id), 403);

        $submissions = FormSubmission::forSite($site)->with('form')->latest()->limit(5000)->get();
        $columns = ['id', 'form', 'name', 'email', 'phone', 'company', 'message', 'status', 'created_at'];

        return response()->streamDownload(function () use ($submissions, $columns) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);

            foreach ($submissions as $submission) {
                fputcsv($out, [
                    $submission->id,
                    $submission->form?->name,
                    $submission->data['name'] ?? '',
                    $submission->data['email'] ?? '',
                    $submission->data['phone'] ?? '',
                    $submission->data['company'] ?? '',
                    $submission->data['message'] ?? '',
                    $submission->status,
                    $submission->created_at?->toIso8601String(),
                ]);
            }

            fclose($out);
        }, 'submissions-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function leads(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $perPage = min(100, max(5, (int) $request->query('per_page', 20)));

        $query = Lead::forSite($site)->with(['owner', 'submission'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('priority'), fn ($q, $priority) => $q->where('priority', $priority))
            ->when($request->query('owner_id'), fn ($q, $owner) => $q->where('owner_id', $owner))
            ->when($request->query('q'), fn ($q, $search) => $q->where(fn ($inner) => $inner
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')
                ->orWhere('company', 'like', '%'.$search.'%')))
            ->latest('created_at');

        $paginator = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'data' => collect($paginator->items())->map(fn (Lead $lead) => $this->leadSummary($lead))->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
            ],
            'owners' => User::whereHas('roles', fn ($q) => $q->where('role_user.site_id', $site->id))
                ->orWhereHas('roles', fn ($q) => $q->where('slug', 'super_admin'))
                ->orderBy('name')->get(['id', 'name', 'email'])
                ->map(fn ($user) => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email])->all(),
        ]);
    }

    public function lead(Request $request, Lead $lead): JsonResponse
    {
        abort_unless($lead->site_id === $this->context->id(), 404);
        $lead->load(['owner', 'submission.form', 'activities.user']);

        return response()->json(['data' => array_merge($this->leadSummary($lead), [
            'message_text' => $lead->message,
            'notes' => $lead->notes,
            'submission' => $lead->submission?->data,
            'activities' => $lead->activities->map(fn (LeadActivity $activity) => [
                'id' => $activity->id,
                'type' => $activity->type,
                'note' => $activity->note,
                'user' => $activity->user?->name,
                'created_at' => $activity->created_at?->toIso8601String(),
            ])->values()->all(),
        ])]);
    }

    public function updateLead(Request $request, Lead $lead): JsonResponse
    {
        abort_unless($lead->site_id === $this->context->id(), 404);
        abort_unless($request->user()->hasPermission('leads.manage', $lead->site_id), 403);

        $data = $request->validate([
            'status' => ['nullable', 'in:new,contacted,qualified,proposal,won,lost,archived'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'owner_id' => ['nullable', 'integer'],
            'follow_up_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! empty($data['owner_id'])) {
            abort_unless(User::whereHas('roles', fn ($q) => $q->where('role_user.site_id', $lead->site_id))->whereKey($data['owner_id'])->exists()
                || User::whereKey($data['owner_id'])->whereHas('roles', fn ($q) => $q->where('slug', 'super_admin'))->exists(), 422, 'The selected owner cannot access this website.');
        }

        $lead->update(array_filter([
            'status' => $data['status'] ?? null,
            'priority' => $data['priority'] ?? null,
            'owner_id' => $data['owner_id'] ?? null,
            'follow_up_at' => $data['follow_up_at'] ?? null,
            'notes' => $data['notes'] ?? null,
        ], fn ($value) => $value !== null) + ['last_activity_at' => now()]);

        if (! empty($data['note'])) {
            LeadActivity::create([
                'lead_id' => $lead->id,
                'user_id' => $request->user()->id,
                'type' => 'note',
                'note' => $data['note'],
            ]);
        }

        return response()->json(['message' => 'Lead updated.']);
    }

    public function addActivity(Request $request, Lead $lead): JsonResponse
    {
        abort_unless($lead->site_id === $this->context->id(), 404);
        abort_unless($request->user()->hasPermission('leads.manage', $lead->site_id), 403);
        $data = $request->validate([
            'type' => ['required', 'in:note,call,email,meeting,follow_up'],
            'note' => ['required', 'string', 'max:2000'],
        ]);

        $activity = LeadActivity::create($data + ['lead_id' => $lead->id, 'user_id' => $request->user()->id]);
        $lead->update(['last_activity_at' => now()]);

        return response()->json(['data' => ['id' => $activity->id], 'message' => 'Activity recorded.'], 201);
    }

    public function destroyLead(Request $request, Lead $lead): JsonResponse
    {
        abort_unless($lead->site_id === $this->context->id(), 404);
        abort_unless($request->user()->hasPermission('leads.manage', $lead->site_id), 403);
        $lead->delete();

        return response()->json(['message' => 'Lead deleted.']);
    }

    public function exportLeads(Request $request): StreamedResponse
    {
        $site = $this->context->siteOrFail();
        abort_unless($request->user()->hasPermission('leads.export', $site->id), 403);
        $leads = Lead::forSite($site)->with('owner')->latest()->limit(5000)->get();

        return response()->streamDownload(function () use ($leads) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'name', 'email', 'phone', 'company', 'source', 'status', 'priority', 'owner', 'follow_up_at', 'created_at']);

            foreach ($leads as $lead) {
                fputcsv($out, [
                    $lead->id, $lead->name, $lead->email, $lead->phone, $lead->company,
                    $lead->source, $lead->status, $lead->priority, $lead->owner?->name,
                    $lead->follow_up_at?->toIso8601String(), $lead->created_at?->toIso8601String(),
                ]);
            }

            fclose($out);
        }, 'leads-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    protected function leadSummary(Lead $lead): array
    {
        return [
            'id' => $lead->id,
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'company' => $lead->company,
            'source' => $lead->source,
            'status' => $lead->status,
            'priority' => $lead->priority,
            'owner' => $lead->owner ? ['id' => $lead->owner->id, 'name' => $lead->owner->name] : null,
            'follow_up_at' => $lead->follow_up_at?->toIso8601String(),
            'last_activity_at' => $lead->last_activity_at?->toIso8601String(),
            'is_demo' => $lead->is_demo,
            'created_at' => $lead->created_at?->toIso8601String(),
        ];
    }
}
