<?php

namespace App\Console\Commands;

use App\Cms\WorkflowService;
use App\Jobs\PublishScheduledPage;
use App\Models\ScheduledPublication;
use Illuminate\Console\Command;

class PublishScheduledContent extends Command
{
    protected $signature = 'content:publish-scheduled {--sync : Publish immediately instead of queueing}';

    protected $description = 'Publish content whose scheduled publication time has arrived';

    public function handle(): int
    {
        $due = ScheduledPublication::where('status', 'pending')
            ->where('publish_at', '<=', now())
            ->orderBy('publish_at')
            ->limit(100)
            ->get();

        if ($due->isEmpty()) {
            $this->info('No scheduled publications are due.');

            return self::SUCCESS;
        }

        foreach ($due as $schedule) {
            if ($this->option('sync')) {
                app(WorkflowService::class)->publishScheduled($schedule);
            } else {
                PublishScheduledPage::dispatch($schedule->id);
            }
        }

        $this->info("Processed {$due->count()} scheduled publication(s).");

        return self::SUCCESS;
    }
}
