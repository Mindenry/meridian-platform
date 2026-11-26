<?php

namespace App\Cms;

use App\Models\Site;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class SearchIndexer
{
    public const INDEX = 'meridian_content';

    public function __construct(protected SeoAuditService $documents) {}

    /**
     * Rebuild the Meilisearch documents for one site.
     *
     * @return int Number of indexed documents.
     */
    public function indexSite(Site $site): int
    {
        $this->ensureIndex();

        $locales = $site->activeLocales();
        $documents = collect($this->documents->documents($site, $locales))
            ->map(fn (array $document) => [
                'id' => $document['seoable_type'].'_'.$document['seoable_id'].'_'.$document['locale'],
                'site_id' => $site->id,
                'locale' => $document['locale'],
                'type' => $document['seoable_type'],
                'title' => $document['title'],
                'excerpt' => Str::limit(strip_tags((string) $document['description']), 220),
                'url' => $document['url'],
                'meta' => [],
                'updated_at' => now()->toIso8601String(),
            ])
            ->values()
            ->all();

        $this->deleteSiteDocuments($site->id);

        if ($documents === []) {
            return 0;
        }

        $response = $this->client()
            ->post('/indexes/'.self::INDEX.'/documents?primaryKey=id', $documents)
            ->throw();

        $this->waitForTask((int) $response->json('taskUid'));

        return count($documents);
    }

    protected function ensureIndex(): void
    {
        $index = $this->client()->post('/indexes', [
            'uid' => self::INDEX,
            'primaryKey' => 'id',
        ]);

        if ($index->failed() && $index->status() !== 409) {
            throw new RuntimeException('Meilisearch index creation failed: '.$index->body());
        }

        $settings = $this->client()
            ->put('/indexes/'.self::INDEX.'/settings/filterable-attributes', ['site_id', 'locale', 'type'])
            ->throw();

        $this->waitForTask((int) $settings->json('taskUid'));
    }

    protected function deleteSiteDocuments(int $siteId): void
    {
        $response = $this->client()
            ->post('/indexes/'.self::INDEX.'/documents/delete', ['filter' => 'site_id = '.$siteId]);

        if ($response->successful()) {
            $this->waitForTask((int) $response->json('taskUid'));
        }
    }

    protected function waitForTask(int $taskUid, int $attempts = 40): void
    {
        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            $task = $this->client()->get('/tasks/'.$taskUid)->json();
            $status = $task['status'] ?? null;

            if ($status === 'succeeded') {
                return;
            }

            if ($status === 'failed') {
                throw new RuntimeException('Meilisearch task failed: '.json_encode($task['error'] ?? $task));
            }

            usleep(100_000);
        }

        throw new RuntimeException('Meilisearch task timed out.');
    }

    protected function client()
    {
        return Http::baseUrl(rtrim((string) config('meridian.search.meilisearch_host'), '/'))
            ->withToken((string) config('meridian.search.meilisearch_key'))
            ->acceptJson()
            ->asJson()
            ->timeout(5);
    }
}
