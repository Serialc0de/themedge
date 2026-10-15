<?php

namespace App\Jobs;

use App\Models\Entity;
use App\Services\Wiki\WikiGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateWikiPageFromEntity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;  // 15 min: Wikipedia RAG + Ollama può essere lento
    public int $tries = 2;

    public function __construct(
        public Entity $entity,
        public bool $force = false,
    ) {}

    /**
     * Fix P1 (race condition su wiki_pages.slug):
     * impedisce job paralleli per la stessa entity.
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('wiki-entity-' . $this->entity->id))
                ->dontRelease()
                ->expireAfter(1200),
        ];
    }

    public function handle(WikiGenerator $generator): void
    {
        Log::info('GenerateWikiPageFromEntity started', [
            'entity_id' => $this->entity->id,
            'entity_name' => $this->entity->name,
        ]);

        try {
            $page = $generator->generateFromEntity($this->entity, $this->force);

            if ($page) {
                Log::info('Wiki page generata', [
                    'entity_id' => $this->entity->id,
                    'wiki_id' => $page->id,
                ]);
            } else {
                Log::info('Wiki page non generata (no articoli)', [
                    'entity_id' => $this->entity->id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('GenerateWikiPageFromEntity failed', [
                'entity_id' => $this->entity->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
