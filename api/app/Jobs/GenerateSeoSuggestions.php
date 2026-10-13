<?php

namespace App\Jobs;

use App\Models\Article;
use App\Services\Llm\OllamaClient;
use App\Services\Llm\SeoOptimizer;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateSeoSuggestions implements ShouldQueue
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
        Log::info('GenerateSeoSuggestions started', [
            'article_id' => $this->article->id,
        ]);

        $ollama = new OllamaClient();
        $optimizer = new SeoOptimizer($ollama);

        $suggestions = $optimizer->optimize($this->article);

        if (!$suggestions) {
            Log::error('Failed to generate SEO suggestions', [
                'article_id' => $this->article->id,
            ]);

            $this->notify(
                '❌ Errore SEO',
                'Non è stato possibile generare i suggerimenti SEO. Riprova.',
                'danger'
            );

            return;
        }

        $this->article->update([
            'ai_seo_suggestions' => $suggestions,
            'ai_seo_generated_at' => now(),
            'ai_seo_model' => config('services.ollama.model_extraction', 'qwen2.5:3b'),
        ]);

        Log::info('GenerateSeoSuggestions completed', [
            'article_id' => $this->article->id,
            'seo_score' => $suggestions['seo_score'] ?? 0,
        ]);

        $this->notify(
            '✅ Suggerimenti SEO pronti',
            'SEO score: ' . ($suggestions['seo_score'] ?? 0) . '/100. Apri l\'articolo per applicarli.',
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
        Log::error('GenerateSeoSuggestions failed', [
            'article_id' => $this->article->id,
            'message' => $exception->getMessage(),
        ]);
    }
}
