<?php

namespace App\Jobs;

use App\Models\Article;
use App\Services\Rag\OllamaEmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateArticleEmbedding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries = 2;

    public function __construct(
        public Article $article,
        public bool $force = false,
    ) {}

    public function handle(OllamaEmbeddingService $embedder): void
    {
        $article = $this->article;

        // Skip se embedding già presente (e non forzato)
        if (!$this->force && $article->embedding && $article->embedding_generated_at) {
            Log::info('Embedding già presente', ['article_id' => $article->id]);
            return;
        }

        // Skip se content vuoto
        $text = $this->buildEmbeddingText($article);
        if (mb_strlen($text) < 50) {
            Log::info('Testo troppo corto per embedding', [
                'article_id' => $article->id,
                'length' => mb_strlen($text),
            ]);
            return;
        }

        try {
            $embedding = $embedder->embed($text);

            if (!$embedding) {
                throw new \RuntimeException('Embedding vuoto dalla API Ollama');
            }

            $article->update([
                'embedding' => $embedding,
                'embedding_model' => $embedder->getModel(),
                'embedding_generated_at' => now(),
            ]);

            Log::info('Article embedding generato', [
                'article_id' => $article->id,
                'dimensions' => count($embedding),
            ]);
        } catch (\Throwable $e) {
            Log::error('Article embedding failed', [
                'article_id' => $article->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Testo da embeddare: titolo + excerpt + content pulito.
     */
    protected function buildEmbeddingText(Article $article): string
    {
        $parts = [
            $article->title,
            $article->excerpt,
            strip_tags($article->content ?? ''),
        ];

        return trim(implode("\n\n", array_filter($parts)));
    }
}
