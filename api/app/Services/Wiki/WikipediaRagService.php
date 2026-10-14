<?php

namespace App\Services\Wiki;

use App\Models\Entity;
use App\Services\Rag\OllamaEmbeddingService;

class WikipediaRagService
{
    public function __construct(
        protected OllamaEmbeddingService $embedder,
    ) {}

    /**
     * Cerca i chunk più rilevanti per una query.
     *
     * @return array<int, array{index: int, content: string, score: float}>
     */
    public function search(Entity $entity, string $query, int $limit = 5, float $minScore = 0.3): array
    {
        $queryEmbedding = $this->embedder->embed($query);
        if (!$queryEmbedding) return [];

        $chunks = $entity->wikipediaChunks()->get();
        if ($chunks->isEmpty()) return [];

        $results = [];

        foreach ($chunks as $chunk) {
            if (!is_array($chunk->embedding) || empty($chunk->embedding)) continue;

            $score = $this->embedder->cosineSimilarity($queryEmbedding, $chunk->embedding);

            if ($score >= $minScore) {
                $results[] = [
                    'index' => $chunk->chunk_index,
                    'content' => $chunk->content,
                    'score' => round($score, 4),
                ];
            }
        }

        // Ordina per score decrescente
        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($results, 0, $limit);
    }

    /**
     * Costruisce un contesto Wikipedia per il prompt, dai chunk più rilevanti.
     * Se non trova nulla di rilevante, restituisce i primi N chunk (introduzione).
     */
    public function buildContext(Entity $entity, ?string $query = null, int $limit = 5): string
    {
        $query = $query ?: $entity->name;

        $chunks = $this->search($entity, $query, $limit, minScore: 0.2);

        // Fallback: primi chunk (intro Wikipedia)
        if (empty($chunks)) {
            $chunks = $entity->wikipediaChunks()
                ->orderBy('chunk_index')
                ->limit($limit)
                ->get()
                ->map(fn($c) => [
                    'index' => $c->chunk_index,
                    'content' => $c->content,
                    'score' => 0.0,
                ])
                ->toArray();
        }

        if (empty($chunks)) return '';

        $parts = [];
        foreach ($chunks as $chunk) {
            $parts[] = trim($chunk['content']);
        }

        return implode("\n\n---\n\n", $parts);
    }

    /**
     * Statistiche di copertura per un'entity.
     */
    public function stats(Entity $entity): array
    {
        $chunks = $entity->wikipediaChunks();
        $total = $chunks->count();
        $withEmbedding = $chunks->whereNotNull('embedding')->count();
        $totalChars = $entity->wikipedia_text ? mb_strlen($entity->wikipedia_text) : 0;

        return [
            'chunks_total' => $total,
            'chunks_with_embedding' => $withEmbedding,
            'text_chars' => $totalChars,
            'wikipedia_url' => $entity->wikipedia_url,
            'wikipedia_lang' => $entity->wikipedia_lang,
            'status' => $entity->wikipedia_status,
        ];
    }
}
