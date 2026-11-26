<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Cms\SiteContext;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowAdminController extends Controller
{
    public function __construct(protected SiteContext $context) {}

    public function index(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();

        $definitions = WorkflowDefinition::forSite($site)->withCount('instances')->get();
        $instances = WorkflowInstance::forSite($site)
            ->with(['approvals.user', 'definition'])
            ->where('workflowable_type', 'page')
            ->latest()
            ->limit(50)
            ->get()
            ->map(function (WorkflowInstance $instance) {
                $page = Page::find($instance->workflowable_id);
                $title = $page?->translations()->first()?->title;

                return [
                    'id' => $instance->id,
                    'page_id' => $instance->workflowable_id,
                    'title' => $title,
                    'status' => $instance->status,
                    'current_step' => $instance->current_step,
                    'steps' => $instance->definition?->steps ?? [],
                    'submitted_at' => $instance->submitted_at?->toIso8601String(),
                    'completed_at' => $instance->completed_at?->toIso8601String(),
                    'approvals' => $instance->approvals->map(fn ($approval) => [
                        'step' => $approval->step,
                        'step_name' => $approval->step_name,
                        'decision' => $approval->decision,
                        'note' => $approval->note,
                        'user' => $approval->user?->name,
                        'decided_at' => $approval->decided_at?->toIso8601String(),
                    ])->values()->all(),
                ];
            });

        return response()->json([
            'data' => [
                'definitions' => $definitions->map(fn (WorkflowDefinition $definition) => [
                    'id' => $definition->id,
                    'name' => $definition->name,
                    'steps' => $definition->steps,
                    'is_default' => $definition->is_default,
                    'instances_count' => $definition->instances_count,
                ])->all(),
                'instances' => $instances,
                'pending' => $instances->where('status', 'in_review')->count(),
            ],
        ]);
    }

    public function updateDefinition(Request $request, WorkflowDefinition $definition): JsonResponse
    {
        abort_unless($definition->site_id === $this->context->id(), 404);
        abort_unless($request->user()->hasPermission('workflow.manage', $definition->site_id), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:160'],
            'steps' => ['required', 'array', 'min:1', 'max:6'],
            'steps.*.name' => ['required', 'string', 'max:120'],
            'steps.*.permission' => ['nullable', 'string', 'max:80'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if (! empty($data['is_default'])) {
            WorkflowDefinition::forSite($definition->site_id)->update(['is_default' => false]);
        }

        $definition->update([
            'name' => $data['name'] ?? $definition->name,
            'steps' => $data['steps'],
            'is_default' => $data['is_default'] ?? $definition->is_default,
        ]);

        return response()->json(['message' => 'Workflow saved.']);
    }
}
