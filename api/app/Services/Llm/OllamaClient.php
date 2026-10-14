<?php

namespace App\Services\Llm;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaClient
{
    protected string $url;
    protected string $model;
    protected int $timeout;

    public function __construct()
    {
        $this->url = config('services.ollama.url', 'http://localhost:11434');
        $this->model = config('services.ollama.model', 'llama3.1:8b');
        $this->timeout = config('services.ollama.timeout', 300);
    }

    /**
     * Ritorna una nuova istanza con il modello specificato.
     */
    public function withModel(string $model): self
    {
        $clone = clone $this;
        $clone->model = $model;
        return $clone;
    }

    /**
     * Genera una risposta dal modello.
     */
    public function generate(string $prompt, array $options = []): ?string
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->url}/api/generate", [
                    'model' => $this->model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'format' => 'json',
                    'options' => array_merge([
                        'temperature' => 0.6,        // Più creatività, meno loop
                        'top_p' => 0.9,
                        'top_k' => 40,
                        'repeat_penalty' => 1.15,    // Penalizza ripetizioni (evita loop)
                        'repeat_last_n' => 128,
                        'num_ctx' => 8192,           // Context window (era 4096 default)
                        'num_predict' => 2500,     // Da 2048
                    ], $options),
                ]);

            if (!$response->successful()) {
                Log::error('Ollama API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            return $response->json('response');
        } catch (\Throwable $e) {
            Log::error('Ollama exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Verifica se Ollama è raggiungibile.
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->url}/api/tags");
            return $response->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Lista modelli disponibili.
     */
    public function listModels(): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->url}/api/tags");
            return $response->json('models', []);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
