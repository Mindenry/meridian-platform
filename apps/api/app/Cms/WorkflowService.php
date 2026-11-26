<?php

namespace App\Cms;

use App\Models\Page;
use App\Models\ScheduledPublication;
use App\Models\Site;
use App\Models\User;
use App\Models\WorkflowApproval;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use App\Notifications\ContentWorkflowNotification;
use App\Support\AuditLogger;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class WorkflowService
{
    public function __construct(protected AuditLogger $audit) {}

    public function submit(Page $page, User $user, ?string $note = null): Page
    {
        if (! in_array($page->status, [Page::STATUS_DRAFT, Page::STATUS_CHANGES_REQUESTED], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only drafts or pages with requested changes can be submitted for review.',
            ]);
        }

        if (! $page->translations()->exists()) {
            throw ValidationException::withMessages(['locale' => 'Add at least one translation before requesting review.']);
        }

        $definition = $this->definitionFor($page->site);

        return DB::transaction(function () use ($page, $user, $note, $definition) {
            $page->status = Page::STATUS_IN_REVIEW;
            $page->updated_by = $user->id;
            $page->lock_version++;
            $page->save();

            WorkflowInstance::where('workflowable_type', 'page')
                ->where('workflowable_id', $page->id)
                ->where('status', 'in_review')
                ->update(['status' => 'cancelled']);

            $instance = WorkflowInstance::create([
                'site_id' => $page->site_id,
                'workflow_definition_id' => $definition?->id,
                'workflowable_type' => 'page',
                'workflowable_id' => $page->id,
                'status' => 'in_review',
                'current_step' => 0,
                'submitted_by' => $user->id,
                'submitted_at' => now(),
            ]);

            $step = $definition?->steps[0] ?? ['name' => 'Editorial approval'];

            WorkflowApproval::create([
                'workflow_instance_id' => $instance->id,
                'step' => 0,
                'step_name' => 'Submitted for review',
                'user_id' => $user->id,
                'decision' => 'submitted',
                'note' => $note,
                'decided_at' => now(),
            ]);

            $this->audit->log('page.submitted_for_review', "Submitted \"{$this->title($page)}\" for review", [
                'subject_type' => 'page',
                'subject_id' => $page->id,
            ]);

            $this->notifyApprovers($page, $user, 'Review requested', "\"{$this->title($page)}\" is waiting for {$step['name']}.");

            return $page->fresh();
        });
    }

    public function approve(Page $page, User $user, ?string $note = null): Page
    {
        abort_unless($user->hasPermission('pages.approve', $page->site_id), 403, 'You are not allowed to approve this page.');

        if ($page->status !== Page::STATUS_IN_REVIEW) {
            throw ValidationException::withMessages(['status' => 'This page is not awaiting review.']);
        }

        return DB::transaction(function () use ($page, $user, $note) {
            $instance = $this->activeInstance($page);

            if (! $instance) {
                throw ValidationException::withMessages(['workflow' => 'No active review could be found for this page.']);
            }

            $definition = $instance->definition;
            $steps = $definition?->steps ?? [['name' => 'Editorial approval']];
            $currentStep = $instance->current_step;
            $step = $steps[$currentStep] ?? ['name' => 'Editorial approval'];

            WorkflowApproval::create([
                'workflow_instance_id' => $instance->id,
                'step' => $currentStep,
                'step_name' => $step['name'],
                'user_id' => $user->id,
                'decision' => 'approved',
                'note' => $note,
                'decided_at' => now(),
            ]);

            if (isset($steps[$currentStep + 1])) {
                $instance->current_step = $currentStep + 1;
                $instance->save();
                $next = $steps[$currentStep + 1];

                $this->notifyApprovers($page, $user, 'Additional approval required', "\"{$this->title($page)}\" now needs {$next['name']}.");
            } else {
                $instance->status = 'completed';
                $instance->completed_at = now();
                $instance->save();

                $page->status = Page::STATUS_APPROVED;
                $page->updated_by = $user->id;
                $page->lock_version++;
                $page->save();
            }

            $this->audit->log('page.approved', "Approved \"{$this->title($page)}\" at step {$step['name']}", [
                'subject_type' => 'page',
                'subject_id' => $page->id,
                'meta' => ['step' => $step['name'], 'note' => $note],
            ]);

            $this->notify($page, [$page->author_id], 'Content approved', "\"{$this->title($page)}\" has been approved and is ready to publish.");

            return $page->fresh();
        });
    }

    public function requestChanges(Page $page, User $user, string $note): Page
    {
        abort_unless($user->hasPermission('pages.approve', $page->site_id), 403, 'You are not allowed to review this page.');

        if (trim($note) === '') {
            throw ValidationException::withMessages(['note' => 'Explain what needs to change.']);
        }

        if ($page->status !== Page::STATUS_IN_REVIEW) {
            throw ValidationException::withMessages(['status' => 'This page is not awaiting review.']);
        }

        return DB::transaction(function () use ($page, $user, $note) {
            $instance = $this->activeInstance($page);

            if ($instance) {
                WorkflowApproval::create([
                    'workflow_instance_id' => $instance->id,
                    'step' => $instance->current_step,
                    'step_name' => 'Changes requested',
                    'user_id' => $user->id,
                    'decision' => 'changes_requested',
                    'note' => $note,
                    'decided_at' => now(),
                ]);

                $instance->status = 'changes_requested';
                $instance->save();
            }

            $page->status = Page::STATUS_CHANGES_REQUESTED;
            $page->updated_by = $user->id;
            $page->lock_version++;
            $page->save();

            $this->audit->log('page.changes_requested', "Requested changes on \"{$this->title($page)}\"", [
                'subject_type' => 'page',
                'subject_id' => $page->id,
                'meta' => ['note' => $note],
            ]);

            $this->notify($page, [$page->author_id], 'Changes requested', $note);

            return $page->fresh();
        });
    }

    public function publish(Page $page, User $user, bool $scheduled = false): Page
    {
        abort_unless($user->hasPermission('pages.publish', $page->site_id), 403, 'You are not allowed to publish this page.');

        if (in_array($page->status, [Page::STATUS_IN_REVIEW, Page::STATUS_CHANGES_REQUESTED], true)) {
            throw ValidationException::withMessages([
                'status' => 'The page must complete review before it can be published.',
            ]);
        }

        if (! $page->translations()->exists()) {
            throw ValidationException::withMessages(['locale' => 'A page needs at least one translation before publishing.']);
        }

        return DB::transaction(function () use ($page, $user, $scheduled) {
            $page->status = Page::STATUS_PUBLISHED;
            $page->published_at = $page->published_at ?? now();
            $page->scheduled_at = null;
            $page->updated_by = $user->id;
            $page->lock_version++;
            $page->save();

            $page->scheduledPublications()
                ->whereIn('status', ['pending', 'processing'])
                ->update(['status' => $scheduled ? 'published' : 'cancelled', 'published_at' => now()]);

            $this->audit->log('page.published', "Published \"{$this->title($page)}\"", [
                'subject_type' => 'page',
                'subject_id' => $page->id,
                'meta' => ['scheduled' => $scheduled],
            ]);

            $this->notify($page, [$page->author_id], 'Page published', "\"{$this->title($page)}\" is now live.");

            return $page->fresh();
        });
    }

    public function schedule(Page $page, User $user, CarbonInterface $publishAt): Page
    {
        abort_unless($user->hasPermission('pages.publish', $page->site_id), 403, 'You are not allowed to schedule publications.');

        if ($publishAt->isPast()) {
            throw ValidationException::withMessages(['publish_at' => 'Choose a time in the future.']);
        }

        if (in_array($page->status, [Page::STATUS_IN_REVIEW, Page::STATUS_CHANGES_REQUESTED], true)) {
            throw ValidationException::withMessages(['status' => 'The page must complete review before it can be scheduled.']);
        }

        return DB::transaction(function () use ($page, $user, $publishAt) {
            $page->status = Page::STATUS_SCHEDULED;
            $page->scheduled_at = $publishAt;
            $page->updated_by = $user->id;
            $page->lock_version++;
            $page->save();

            $page->scheduledPublications()
                ->whereIn('status', ['pending', 'processing'])
                ->update(['status' => 'cancelled']);

            ScheduledPublication::create([
                'site_id' => $page->site_id,
                'page_id' => $page->id,
                'publish_at' => $publishAt,
                'status' => 'pending',
                'created_by' => $user->id,
            ]);

            $this->audit->log('page.scheduled', "Scheduled \"{$this->title($page)}\"", [
                'subject_type' => 'page',
                'subject_id' => $page->id,
                'meta' => ['publish_at' => $publishAt->toIso8601String()],
            ]);

            return $page->fresh();
        });
    }

    public function cancelSchedule(Page $page, User $user): Page
    {
        abort_unless($user->hasPermission('pages.publish', $page->site_id), 403, 'You are not allowed to change publishing schedules.');

        return DB::transaction(function () use ($page, $user) {
            $page->scheduledPublications()
                ->whereIn('status', ['pending', 'processing'])
                ->update(['status' => 'cancelled']);

            $page->status = Page::STATUS_APPROVED;
            $page->scheduled_at = null;
            $page->updated_by = $user->id;
            $page->lock_version++;
            $page->save();

            $this->audit->log('page.schedule_cancelled', "Cancelled the scheduled publication of \"{$this->title($page)}\"", [
                'subject_type' => 'page',
                'subject_id' => $page->id,
            ]);

            return $page->fresh();
        });
    }

    public function unpublish(Page $page, User $user): Page
    {
        abort_unless($user->hasPermission('pages.publish', $page->site_id), 403, 'You are not allowed to unpublish this page.');

        return DB::transaction(function () use ($page, $user) {
            $page->status = Page::STATUS_DRAFT;
            $page->published_at = null;
            $page->scheduled_at = null;
            $page->updated_by = $user->id;
            $page->lock_version++;
            $page->save();

            $this->audit->log('page.unpublished', "Unpublished \"{$this->title($page)}\"", [
                'subject_type' => 'page',
                'subject_id' => $page->id,
            ]);

            return $page->fresh();
        });
    }

    /**
     * Idempotent scheduled publication. Safe to call twice: the second call
     * sees a non-pending row and returns false without side effects.
     */
    public function publishScheduled(ScheduledPublication $schedule): bool
    {
        return DB::transaction(function () use ($schedule) {
            /** @var ScheduledPublication $locked */
            $locked = ScheduledPublication::lockForUpdate()->find($schedule->id);

            if (! $locked || $locked->status !== 'pending') {
                return false;
            }

            $page = Page::where('site_id', $locked->site_id)->find($locked->page_id);

            if (! $page) {
                $locked->update(['status' => 'failed', 'error' => 'The page no longer exists.']);

                return false;
            }

            if ($locked->publish_at->isFuture()) {
                return false;
            }

            try {
                $publisher = User::find($locked->created_by) ?? User::whereHas('roles', fn ($q) => $q->where('slug', 'super_admin'))->first();
                $page->status = Page::STATUS_PUBLISHED;
                $page->published_at = $locked->publish_at;
                $page->scheduled_at = null;
                $page->updated_by = $publisher?->id;
                $page->lock_version++;
                $page->save();

                $locked->update(['status' => 'published', 'published_at' => now()]);

                $this->audit->log('page.published_scheduled', "Scheduled publication completed for \"{$this->title($page)}\"", [
                    'subject_type' => 'page',
                    'subject_id' => $page->id,
                    'user_id' => $publisher?->id,
                    'meta' => ['scheduled_publication_id' => $locked->id],
                ]);

                return true;
            } catch (\Throwable $e) {
                $locked->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 2000)]);
                report($e);

                return false;
            }
        });
    }

    public function definitionFor(Site $site): ?WorkflowDefinition
    {
        return WorkflowDefinition::where('site_id', $site->id)->where('is_default', true)->first()
            ?? WorkflowDefinition::where('site_id', $site->id)->first();
    }

    protected function activeInstance(Page $page): ?WorkflowInstance
    {
        return WorkflowInstance::where('workflowable_type', 'page')
            ->where('workflowable_id', $page->id)
            ->where('status', 'in_review')
            ->latest()
            ->first();
    }

    protected function approvers(Site $site): Collection
    {
        $byRole = User::where('is_active', true)
            ->whereHas('roles', function ($query) use ($site) {
                $query->where('role_user.site_id', $site->id)
                    ->whereHas('permissions', fn ($permissions) => $permissions->where('slug', 'pages.approve'));
            })
            ->get();

        $superAdmins = User::where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('slug', 'super_admin'))
            ->get();

        return $byRole->merge($superAdmins)->unique('id')->values();
    }

    protected function notifyApprovers(Page $page, User $actor, string $title, string $message): void
    {
        $recipients = $this->approvers($page->site)->reject(fn (User $user) => $user->id === $actor->id);

        Notification::send($recipients, new ContentWorkflowNotification(
            $title,
            $message,
            rtrim((string) config('meridian.studio_url', 'http://localhost:3001'), '/').'/pages/'.$page->id,
            'workflow',
        ));
    }

    protected function notify(Page $page, array $userIds, string $title, string $message): void
    {
        $recipients = User::whereIn('id', array_filter($userIds))->get();

        Notification::send($recipients, new ContentWorkflowNotification(
            $title,
            $message,
            rtrim((string) config('meridian.studio_url', 'http://localhost:3001'), '/').'/pages/'.$page->id,
            'workflow',
        ));
    }

    protected function title(Page $page): string
    {
        return $page->translations()->first()?->title ?? 'Untitled page';
    }
}
