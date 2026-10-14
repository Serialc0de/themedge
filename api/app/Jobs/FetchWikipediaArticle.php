<?php

namespace App\Jobs;

use App\Models\Entity;
use App\Services\Wiki\WikipediaFetcherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchWikipediaArticle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;  // 10 min (chunking + embedding può essere lento)
    public int $tries = 2;

    public function __construct(
        public Entity $entity,
        public bool $force = false,
    ) {}

    public function handle(WikipediaFetcherService $fetcher): void
    {
        Log::info('FetchWikipediaArticle started', [
            'entity_id' => $this->entity->id,
            'entity_name' => $this->entity->name,
        ]);

        $result = $fetcher->fetchForEntity($this->entity, $this->force);

        Log::info('FetchWikipediaArticle done', [
            'entity_id' => $this->entity->id,
            'status' => $result['status'],
            'chunks' => $result['chunks'],
            'chars' => $result['chars'],
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('FetchWikipediaArticle failed permanently', [
            'entity_id' => $this->entity->id,
            'error' => $e->getMessage(),
        ]);

        $this->entity->update([
            'wikipedia_status' => 'failed',
            'wikipedia_fetched_at' => now(),
        ]);
    }
}
