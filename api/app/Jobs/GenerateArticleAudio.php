<?php

namespace App\Jobs;

use App\Models\Article;
use App\Services\Tts\PiperTtsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateArticleAudio implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 2;

    public function __construct(
        public Article $article,
        public bool $force = false,
    ) {}

    public function handle(PiperTtsService $tts): void
    {
        $article = $this->article;

        // --- Controlli preliminari ---
        if (!config('tts.enabled', true)) {
            Log::info('TTS disabled', ['article_id' => $article->id]);
            return;
        }

        if (!config('tts.auto_generate.enabled', true) && !$this->force) {
            Log::info('TTS auto-generate disabled', ['article_id' => $article->id]);
            return;
        }

        // Controlla se già generato (e non forzato)
        if (!$this->force && $article->audio_status === 'ready' && $article->audio_path) {
            Log::info('Audio già generato', ['article_id' => $article->id]);
            return;
        }

        // --- Aggiorna stato a 'generating' ---
        $article->update([
            'audio_status' => 'generating',
            'audio_error' => null,
        ]);

        try {
            // --- Pulisci HTML → testo ---
            $text = $tts->cleanHtml($article->content ?? '');

            // Prependi titolo
            $text = "Articolo: {$article->title}. " . $text;

            // Verifica lunghezza
            $minLength = config('tts.auto_generate.min_length', 200);
            $maxLength = config('tts.auto_generate.max_length', 50000);

            if (mb_strlen($text) < $minLength) {
                throw new \RuntimeException("Articolo troppo corto (" . mb_strlen($text) . " char, min {$minLength})");
            }

            if (mb_strlen($text) > $maxLength) {
                Log::warning('Articolo troppo lungo, tronco', [
                    'article_id' => $article->id,
                    'length' => mb_strlen($text),
                    'max' => $maxLength,
                ]);
                $text = mb_substr($text, 0, $maxLength);
            }

            // --- Genera audio ---
            $filename = 'article-' . $article->id . '-' . $article->slug;
            $filename = preg_replace('/[^a-z0-9\-]/', '-', strtolower($filename));
            $filename = preg_replace('/-+/', '-', $filename);

            $result = $tts->synthesize($text, $filename);

            // --- Aggiorna articolo ---
            $article->update([
                'audio_path' => $result['path'],
                'audio_duration_seconds' => $result['duration'],
                'audio_status' => 'ready',
                'audio_model' => $result['model'],
                'audio_generated_at' => now(),
                'audio_error' => null,
            ]);

            Log::info('Audio generato', [
                'article_id' => $article->id,
                'path' => $result['path'],
                'duration' => $result['duration'],
                'size_kb' => round($result['size'] / 1024, 1),
            ]);

        } catch (\Throwable $e) {
            Log::error('Errore generazione audio', [
                'article_id' => $article->id,
                'error' => $e->getMessage(),
            ]);

            $article->update([
                'audio_status' => 'failed',
                'audio_error' => mb_substr($e->getMessage(), 0, 500),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('GenerateArticleAudio failed permanently', [
            'article_id' => $this->article->id,
            'error' => $e->getMessage(),
        ]);

        $this->article->update([
            'audio_status' => 'failed',
            'audio_error' => 'Job failed: ' . mb_substr($e->getMessage(), 0, 400),
        ]);
    }
}
