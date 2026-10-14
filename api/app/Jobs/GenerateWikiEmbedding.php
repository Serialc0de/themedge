<?php

namespace App\Jobs;

use App\Models\WikiPage;
use App\Services\Rag\OllamaEmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateWikiEmbedding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries = 2;

    public function __construct(
        public WikiPage $page,
        public bool $force = false,
    ) {}

    public function handle(OllamaEmbeddingService $embedder): void
    {
        $page = $this->page;

        if (!$this->force && $page->embedding && $page->embedding_generated_at) {
            Log::info('Wiki embedding già presente', ['page_id' => $page->id]);
            return;
        }

        $text = $this->buildEmbeddingText($page);
        if (mb_strlen($text) < 30) {
            Log::info('Testo troppo corto per embedding', [
                'page_id' => $page->id,
                'length' => mb_strlen($text),
            ]);
            return;
        }

        try {
            $embedding = $embedder->embed($text);

            if (!$embedding) {
                throw new \RuntimeException('Embedding vuoto dalla API Ollama');
            }

            $page->update([
                'embedding' => $embedding,
                'embedding_model' => $embedder->getModel(),
                'embedding_generated_at' => now(),
            ]);

            Log::info('Wiki embedding generato', [
                'page_id' => $page->id,
                'dimensions' => count($embedding),
            ]);
        } catch (\Throwable $e) {
            Log::error('Wiki embedding failed', [
                'page_id' => $page->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function buildEmbeddingText(WikiPage $page): string
    {
        $parts = [
            $page->title,
            $page->subtitle,
            $page->summary,
            strip_tags($page->content ?? ''),
        ];

        return trim(implode("\n\n", array_filter($parts)));
    }
}
