<?php

namespace App\Services\Rag;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaEmbeddingService
{
    protected string $url;
    protected string $model;
    protected int $timeout;

    public function __construct()
    {
        $this->url = config('services.ollama.url', 'http://localhost:11434');
        $this->model = config('services.ollama.embedding_model', 'nomic-embed-text');
        $this->timeout = config('services.ollama.embedding_timeout', 60);
    }

    /**
     * Genera embedding per un testo.
     *
     * @return array<float>|null
     */
    public function embed(string $text): ?array
    {
        $text = trim($text);
        if (empty($text)) return null;

        // Tronca a 8000 caratteri (limite modello)
        $text = mb_substr($text, 0, 8000);

        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->url}/api/embeddings", [
                    'model' => $this->model,
                    'prompt' => $text,
                ]);

            if (!$response->successful()) {
                Log::error('Ollama embedding error', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 200),
                ]);
                return null;
            }

            $embedding = $response->json('embedding');

            if (!is_array($embedding) || empty($embedding)) {
                Log::warning('Ollama embedding empty');
                return null;
            }

            return $embedding;
        } catch (\Throwable $e) {
            Log::error('Ollama embedding exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Calcola similarità coseno tra due vettori.
     */
    public function cosineSimilarity(array $a, array $b): float
    {
        $count = min(count($a), count($b));
        if ($count === 0) return 0.0;

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $count; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        if ($normA == 0 || $normB == 0) return 0.0;

        return $dot / (sqrt($normA) * sqrt($normB));
    }

    /**
     * Modello usato.
     */
    public function getModel(): string
    {
        return $this->model;
    }
}
