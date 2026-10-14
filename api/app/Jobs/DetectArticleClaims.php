<?php

namespace App\Jobs;

use App\Models\Article;
use App\Models\ArticleClaim;
use App\Services\Llm\ClaimExtractor;
use App\Services\Llm\OllamaClient;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DetectArticleClaims implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 600;
    public array $backoff = [30, 120];

    public function __construct(
        public Article $article,
        public ?int $userId = null
    ) {}

    public function handle(): void
    {
        Log::info('DetectArticleClaims started', [
            'article_id' => $this->article->id,
        ]);

        // Rimuovi claim precedenti in stato 'pending' (preserva approvati/scartati)
        $this->article->claims()->where('status', 'pending')->delete();

        $ollama = new OllamaClient();
        $extractor = new ClaimExtractor($ollama);

        $claims = $extractor->extract($this->article);

        if (empty($claims)) {
            Log::warning('No claims detected', ['article_id' => $this->article->id]);

            $this->notify(
                '❌ Nessun claim rilevato',
                'L\'AI non ha trovato affermazioni verificabili. Riprova o verifica il contenuto.',
                'warning'
            );

            return;
        }

        // Salva i claim in DB
        DB::transaction(function () use ($claims) {
            foreach ($claims as $index => $claim) {
                ArticleClaim::create([
                    'article_id' => $this->article->id,
                    'claim_text' => $claim['claim_text'],
                    'context' => $claim['context'] ?? null,
                    'status' => 'pending',
                    'ai_suggested_verdict' => $claim['ai_suggested_verdict'],
                    'ai_suggested_source_name' => $claim['ai_suggested_source_name'] ?? null,
                    'ai_suggested_source_url' => $claim['ai_suggested_source_url'] ?? null,
                    'ai_confidence' => $claim['ai_confidence'] ?? null,
                    'ai_model' => 'llama3.1:8b',
                    'ai_generated_at' => now(),
                    'note' => $claim['note'] ?? null,
                    'order' => $index,
                ]);
            }
        });

        Log::info('DetectArticleClaims completed', [
            'article_id' => $this->article->id,
            'count' => count($claims),
        ]);

        $this->notify(
            '✅ Claim rilevati',
            'Trovate ' . count($claims) . ' affermazioni da verificare. Apri l\'articolo per revisionarle.',
            'success'
        );
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

    public function failed(\Throwable $exception): void
    {
        Log::error('DetectArticleClaims failed', [
            'article_id' => $this->article->id,
            'message' => $exception->getMessage(),
        ]);
    }
}
