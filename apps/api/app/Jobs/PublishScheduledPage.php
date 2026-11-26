<?php

namespace App\Jobs;

use App\Cms\WorkflowService;
use App\Models\ScheduledPublication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PublishScheduledPage implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 300;

    public function __construct(public int $scheduledPublicationId) {}

    public function uniqueId(): string
    {
        return 'scheduled-publication-'.$this->scheduledPublicationId;
    }

    public function handle(WorkflowService $workflow): void
    {
        $schedule = ScheduledPublication::find($this->scheduledPublicationId);

        if ($schedule) {
            $workflow->publishScheduled($schedule);
        }
    }

    public function failed(\Throwable $exception): void
    {
        ScheduledPublication::whereKey($this->scheduledPublicationId)->update([
            'status' => 'failed',
            'error' => mb_substr($exception->getMessage(), 0, 2000),
        ]);
    }
}
