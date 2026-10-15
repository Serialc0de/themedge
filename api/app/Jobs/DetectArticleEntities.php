<?php

namespace App\Jobs;

use App\Models\Article;
use App\Models\Entity;
use App\Services\Llm\EntityExtractor;
use App\Services\Llm\OllamaClient;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DetectArticleEntities implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 1200;

    public function __construct(
        public Article $article,
        public ?int $userId = null
    ) {}

    /**
     * Impedisce estrazioni parallele per lo stesso articolo.
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('detect-entities-' . $this->article->id))
                ->dontRelease()
                ->expireAfter(1500),
        ];
    }

    public function handle(): void
    {
        Log::info('DetectArticleEntities started', ['article_id' => $this->article->id]);

	$models = [config('services.ollama.model_extraction', 'qwen2.5:3b')];
        $detected = [];
        $successModel = null;

        foreach ($models as $model) {
            Log::info('Trying model for extraction', ['model' => $model, 'article_id' => $this->article->id]);
            $ollama = (new OllamaClient())->withModel($model);
            $extractor = new EntityExtractor($ollama);
            $detected = $extractor->extract($this->article);

            if (!empty($detected)) {
                $successModel = $model;
                Log::info('Extraction succeeded', ['model' => $model, 'count' => count($detected)]);
                break;
            }
        }

        if (empty($detected)) {
            Log::warning('No entities detected', ['article_id' => $this->article->id]);
            $this->notify('❌ Nessuna entità rilevata', 'L\'AI non ha trovato entità.', 'warning');
            return;
        }

        // Match con entità esistenti
        $matched = $this->matchWithExisting($detected);

        // CREA le entità mancanti come bozze
        $matched = $this->createMissingEntities($matched);

        // Salva il risultato
        $this->article->update([
            'detected_entities' => [
                'detected_at' => now()->toIso8601String(),
                'model' => $successModel ?? config('services.ollama.model'),
                'entities' => $matched,
            ],
        ]);

        // Collega tutte le entità (esistenti + nuove) all'articolo
        $this->attachExistingEntities($matched);

        $newCount = collect($matched)->where('created', true)->count();
        $existingCount = count($matched) - $newCount;

        Log::info('DetectArticleEntities completed', [
            'article_id' => $this->article->id,
            'total' => count($matched),
            'existing' => $existingCount,
            'created' => $newCount,
        ]);

        $this->notify(
            '✅ Entità rilevate',
            "Trovate " . count($matched) . " entità: {$existingCount} esistenti, {$newCount} nuove (bozze).",
            'success'
        );
    }

    /**
     * Crea le entità mancanti come bozze (is_published = false).
     */
    protected function createMissingEntities(array $matched): array
    {
        foreach ($matched as &$item) {
            if ($item['exists']) {
                $item['created'] = false;
                continue;
            }

            $entity = Entity::create([
                'name' => $item['name'],
                'slug' => Str::slug($item['name']),
                'type' => $item['type'],
                'is_published' => false,
                'bio' => null,
            ]);

            $item['exists'] = true;
            $item['created'] = true;
            $item['entity_id'] = $entity->id;
            $item['entity_slug'] = $entity->slug;

            Log::info('Entity created', [
                'entity_id' => $entity->id,
                'name' => $entity->name,
                'type' => $entity->type,
            ]);
        }

        return $matched;
    }

    /**
     * Collega le entità all'articolo via pivot article_entity.
     */
    protected function attachExistingEntities(array $matched): void
    {
        $entityIds = collect($matched)
            ->where('exists', true)
            ->pluck('entity_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($entityIds)) return;

        $this->article->entities()->syncWithoutDetaching($entityIds);

        Log::info('Entities attached to article', [
            'article_id' => $this->article->id,
            'count' => count($entityIds),
        ]);
    }

    /**
     * Match entità rilevate con entità esistenti nel DB.
     */
    protected function matchWithExisting(array $detected): array
    {
        $result = [];

        foreach ($detected as $item) {
            $name = $item['name'];
            $slug = Str::slug($name);

            $existing = Entity::where('slug', $slug)
                ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->first();

            if (!$existing && strpos($name, ' ') !== false) {
                $parts = explode(' ', $name);
                foreach ($parts as $part) {
                    if (mb_strlen($part) > 4) {
                        $existing = Entity::whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($part) . '%'])->first();
                        if ($existing) break;
                    }
                }
            }

            $result[] = [
                'name' => $name,
                'type' => $item['type'],
                'mentions' => $item['mentions'],
                'context' => $item['context'],
                'exists' => $existing !== null,
                'entity_id' => $existing?->id,
                'entity_slug' => $existing?->slug,
                'entity_icon' => Entity::TYPE_ICONS[$item['type']] ?? '❓',
                'entity_color' => Entity::TYPE_COLORS[$item['type']] ?? 'gray',
            ];
        }

        return $result;
    }

    protected function notify(string $title, string $body, string $status = 'info'): void
    {
        if (!$this->userId) return;

        $user = \App\Models\User::find($this->userId);
        if (!$user) return;

        Notification::make()
            ->title($title)
            ->body($body)
            ->status($status)
            ->persistent()
            ->sendToDatabase($user);
    }
}
