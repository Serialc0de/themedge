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
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DetectArticleEntities implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600;

    public function __construct(
        public Article $article,
        public ?int $userId = null
    ) {}

    public function handle(): void
    {
        Log::info('DetectArticleEntities started', [
            'article_id' => $this->article->id,
        ]);

        // Strategia: prova llama3.1:8b (qualità) → fallback qwen2.5:3b (affidabilità)
        $models = ['llama3.1:8b', 'qwen2.5:3b'];
        $detected = [];
        $successModel = null;

        foreach ($models as $model) {
            Log::info('Trying model for extraction', [
                'model' => $model,
                'article_id' => $this->article->id,
            ]);

            $ollama = (new OllamaClient())->withModel($model);
            $extractor = new EntityExtractor($ollama);

            $detected = $extractor->extract($this->article);

            if (!empty($detected)) {
                $successModel = $model;
                Log::info('Extraction succeeded', [
                    'model' => $model,
                    'count' => count($detected),
                ]);
                break;
            }

            Log::warning('Extraction failed with model, trying next', [
                'model' => $model,
            ]);
        }

        if (empty($detected)) {
            Log::warning('No entities detected after all models', ['article_id' => $this->article->id]);

            $this->notify(
                '❌ Nessuna entità rilevata',
                'L\'AI non ha trovato entità. Riprova più tardi.',
                'warning'
            );

            return;
        }

        // Match con entità esistenti
        $matched = $this->matchWithExisting($detected);

        // Salva il risultato nell'articolo (campo temporaneo)
        $this->article->update([
            'detected_entities' => [
                'detected_at' => now()->toIso8601String(),
                'model' => $successModel ?? config('services.ollama.model'),
                'entities' => $matched,
            ],
        ]);

        $existingCount = collect($matched)->where('exists', true)->count();
        $newCount = collect($matched)->where('exists', false)->count();

        Log::info('DetectArticleEntities completed', [
            'article_id' => $this->article->id,
            'total' => count($matched),
            'existing' => $existingCount,
            'new' => $newCount,
        ]);

        $this->notify(
            '✅ Entità rilevate',
            "Trovate " . count($matched) . " entità: {$existingCount} esistenti, {$newCount} nuove. Apri l'articolo per collegarle.",
            'success'
        );
    }

    /**
     * Match entità rilevate con entità esistenti nel DB.
     *
     * @param array<array{name: string, type: string, mentions: int, context: string|null}> $detected
     * @return array
     */
    protected function matchWithExisting(array $detected): array
    {
        $result = [];

        foreach ($detected as $item) {
            $name = $item['name'];
            $slug = Str::slug($name);

            // Cerca entità esistente per slug o nome (case-insensitive)
            $existing = Entity::where('slug', $slug)
                ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->first();

            // Cerca anche per nome parziale (es. "Parolin" per "Pietro Parolin")
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
