<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Rag\RagPromptBuilder;
use App\Services\Rag\RagSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AskController extends Controller
{
    public function __construct(
        protected RagSearchService $search,
        protected RagPromptBuilder $promptBuilder,
    ) {}

    /**
     * POST /api/ask
     */
    public function ask(Request $request)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'min:3', 'max:500'],
            'stream' => ['boolean'],
        ]);

        $question = trim($data['question']);
        $stream = $data['stream'] ?? true;

        // 1. Ricerca vettoriale
        $startTime = microtime(true);
        $context = $this->search->search($question, limit: 1, minScore: 0.5);
        $searchDuration = round((microtime(true) - $startTime) * 1000);

        Log::info('Ask query', [
            'question' => mb_substr($question, 0, 100),
            'results_count' => count($context),
            'search_ms' => $searchDuration,
        ]);

        // 2. Costruzione prompt
        $prompt = $this->promptBuilder->build($question, $context);

        // 3. Streaming o risposta completa
        if ($stream) {
            return $this->streamResponse($question, $context, $prompt);
        }

        return $this->fullResponse($question, $context, $prompt);
    }

    /**
     * Streaming SSE con fopen (più affidabile di Guzzle stream).
     */
    protected function streamResponse(string $question, array $context, string $prompt)
    {
        return response()->stream(function () use ($question, $context, $prompt) {
            // Disabilita buffering
            @ini_set('zlib.output_compression', '0');
            @ini_set('output_buffering', '0');
            @ini_set('implicit_flush', '1');
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            ob_implicit_flush(true);

            // 1. Invia le fonti
            $sources = collect($context)->map(function ($r) {
                return [
                    'id' => $r['id'],
                    'kind' => $r['kind'],
                    'title' => $r['title'],
                    'url' => $r['url'],
                    'excerpt' => $r['excerpt'],
                    'score' => $r['score'],
                    'icon' => $r['icon'],
                    'type' => $r['type'],
                    'category' => $r['category'],
                    'published_at' => $r['published_at'],
                ];
            })->toArray();

            $this->sendSse('sources', ['sources' => $sources]);

            // 2. Stream da Ollama via fopen
            $ollamaUrl = config('services.ollama.url', 'http://localhost:11434');
            $model = config('services.ollama.model', 'llama3.1:8b');
            $timeout = config('services.ollama.timeout', 300);

            $payload = json_encode([
                'model' => $model,
                'prompt' => $prompt,
                'stream' => true,
                'options' => [
                    'temperature' => 0.3,
                    'top_p' => 0.9,
                    'num_predict' => 200,
                ],
            ], JSON_UNESCAPED_UNICODE);

            $ctx = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n",
                    'content' => $payload,
                    'timeout' => $timeout,
                    'ignore_errors' => true,
                ],
            ]);

            $stream = @fopen($ollamaUrl . '/api/generate', 'r', false, $ctx);

            if (!$stream) {
                $this->sendSse('error', ['message' => 'Impossibile connettersi a Ollama']);
                return;
            }

            // 3. Leggi NDJSON riga per riga
            while (!feof($stream)) {
                $line = fgets($stream);
                if ($line === false) break;

                $line = trim($line);
                if (empty($line)) continue;

                $json = json_decode($line, true);
                if (!is_array($json)) continue;

                if (isset($json['response']) && $json['response'] !== '') {
                    $this->sendSse('token', ['text' => $json['response']]);
                }

                if (!empty($json['done'])) {
                    break;
                }
            }

            @fclose($stream);

            $this->sendSse('done', ['ok' => true]);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Invia evento SSE.
     */
    protected function sendSse(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";

        if (ob_get_level() > 0) {
            @ob_flush();
        }
        @flush();
    }

    /**
     * Risposta completa (non-streaming).
     */
    protected function fullResponse(string $question, array $context, string $prompt)
    {
        $ollamaUrl = config('services.ollama.url', 'http://localhost:11434');
        $model = config('services.ollama.model', 'llama3.1:8b');
        $timeout = config('services.ollama.timeout', 300);

        try {
            $payload = json_encode([
                'model' => $model,
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'temperature' => 0.3,
                    'top_p' => 0.9,
                    'num_predict' => 200,
                ],
            ], JSON_UNESCAPED_UNICODE);

            $ctx = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n",
                    'content' => $payload,
                    'timeout' => $timeout,
                    'ignore_errors' => true,
                ],
            ]);

            $response = @file_get_contents($ollamaUrl . '/api/generate', false, $ctx);

            if ($response === false) {
                return response()->json(['error' => 'Ollama non raggiungibile'], 500);
            }

            $json = json_decode($response, true);
            $answer = $json['response'] ?? '';

            $sources = collect($context)->map(function ($r) {
                return [
                    'id' => $r['id'],
                    'kind' => $r['kind'],
                    'title' => $r['title'],
                    'url' => $r['url'],
                    'excerpt' => $r['excerpt'],
                    'score' => $r['score'],
                    'icon' => $r['icon'],
                    'type' => $r['type'],
                    'category' => $r['category'],
                    'published_at' => $r['published_at'],
                ];
            })->toArray();

            return response()->json([
                'question' => $question,
                'answer' => $answer,
                'sources' => $sources,
                'model' => $model,
            ]);
        } catch (\Throwable $e) {
            Log::error('Ask full error', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
