<?php

namespace App\Jobs;

use App\Models\Article;
use App\Services\ArticleSummarizer;
use App\Services\Llm\OllamaClient;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateArticleSummary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Numero di tentativi massimi.
     */
    public int $tries = 2;

    /**
     * Timeout del job (llama3.1:8b in CPU-only può richiedere 5 min).
     */
    public int $timeout = 600;

    /**
     * Attesa tra i tentativi (in secondi).
     */
    public array $backoff = [30, 120];

    public function __construct(
        public Article $article,
        public ?int $userId = null
    ) {}

    public function handle(): void
    {
        Log::info('GenerateArticleSummary started', [
            'article_id' => $this->article->id,
            'user_id' => $this->userId,
        ]);

        $ollama = new OllamaClient();
        $summarizer = new ArticleSummarizer($ollama);

        $result = $summarizer->generate($this->article);

        if (!$result) {
            Log::error('GenerateArticleSummary failed', [
                'article_id' => $this->article->id,
            ]);

            $this->notify(
                '❌ Errore generazione TL;DR',
                'Non è stato possibile generare il riassunto. Riprova più tardi.',
                'danger'
            );

            return;
        }

        $this->article->update([
            'ai_summary' => $result,
            'ai_summary_generated_at' => now(),
            'ai_summary_model' => config('services.ollama.model'),
        ]);

        Log::info('GenerateArticleSummary completed', [
            'article_id' => $this->article->id,
        ]);

        $this->notify(
            '✅ TL;DR generato',
            'Il riassunto è pronto. Ricarica la pagina per vederlo.',
            'success'
        );
    }

    /**
     * Notifica l'utente tramite Filament.
     */
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

    /**
     * Gestione del fallimento.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateArticleSummary exception', [
            'article_id' => $this->article->id,
            'message' => $exception->getMessage(),
        ]);

        $this->notify(
            '❌ Errore TL;DR',
            'Errore: ' . $exception->getMessage(),
            'danger'
        );
    }
}
