<?php

namespace App\Console\Commands;

use App\Cms\SearchIndexer;
use App\Models\Site;
use Illuminate\Console\Command;

class IndexSearchContent extends Command
{
    protected $signature = 'search:index {--site= : Index a single site by slug}';

    protected $description = 'Rebuild the Meilisearch index for published content';

    public function handle(SearchIndexer $indexer): int
    {
        $sites = Site::query()
            ->when($this->option('site'), fn ($query, $slug) => $query->where('slug', $slug))
            ->get();

        if ($sites->isEmpty()) {
            $this->error('No matching sites.');

            return self::FAILURE;
        }

        foreach ($sites as $site) {
            try {
                $count = $indexer->indexSite($site);
                $this->info("Indexed {$count} documents for {$site->slug}.");
            } catch (\Throwable $exception) {
                $this->error("Could not index {$site->slug}: {$exception->getMessage()}");

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
