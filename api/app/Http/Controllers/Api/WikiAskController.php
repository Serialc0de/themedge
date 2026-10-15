<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AskQuery;
use App\Models\WikiPage;
use App\Services\Rag\RagPromptBuilder;
use App\Services\Wiki\WikipediaRagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WikiAskController extends Controller
{
    public function __construct(
        protected WikipediaRagService $wikiRag,
        protected RagPromptBuilder $promptBuilder,
    ) {}

    /**
     * POST /api/wiki/{slug}/ask
     */
    public function ask(Request $request, string $slug)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'min:3', 'max:500'],
            'stream' => ['boolean'],
        ]);

        $question = trim($data['question']);
        $stream = $data['stream'] ?? true;

        // 1. Trova la wiki
        $page = WikiPage::published()
            ->where('slug', $slug)
            ->with(['entity', 'articles' => function ($q) {
                $q->select([
                        'articles.id', 'articles.title', 'articles.slug',
                        'articles.excerpt', 'articles.content',
                        'articles.published_at', 'articles.category_id',
                    ])
                    ->where('articles.status', 'published')
                    ->whereNull('articles.deleted_at')
                    ->orderByDesc('wiki_page_article.relevance')
                    ->limit(5);
            }])
            ->firstOrFail();

        // 2. Chunk Wikipedia (solo per questa entity)
        $wikiChunks = [];
        if ($page->entity) {
            $chunkLimit = $page->articles->isNotEmpty() ? 2 : 3;
            $wikiChunks = $this->wikiRag->search($page->entity, $question, limit: $chunkLimit, minScore: 0.5);
        }

        // 3. Costruisci contesto unificato
        $context = $this->buildContext($page, $wikiChunks);

        // 4. Prompt
        $prompt = $this->promptBuilder->buildForWiki($question, $context, $page->title);

        // 5. Session ID
        $sessionId = $request->header('X-Session-Id');
        $questionHash = AskQuery::hashQuestion($question);

        // 6. Streaming o risposta completa
        if ($stream) {
            return $this->streamResponse($question, $context, $prompt, $questionHash, $sessionId, $page);
        }

        return $this->fullResponse($question, $context, $prompt, $questionHash, $sessionId, $page);
    }

    /**
     * Costruisce il contesto per il prompt.
     * Formato atteso da RagPromptBuilder: array di risultati con chiavi:
     * id, kind, title, url, excerpt, score, published_at, category, icon, type, _content
     */
    protected function buildContext(WikiPage $page, array $wikiChunks): array
    {
        $context = [];

        // 1. ARTICOLI COLLEGATI (priorità: sono specifici per questa wiki)
        foreach ($page->articles as $article) {
            $context[] = [
                'id' => 'article-' . $article->id,
                'kind' => 'article',
                'title' => $article->title,
                'slug' => $article->slug,
                'url' => '/articolo/' . $article->slug,
                'excerpt' => mb_substr(strip_tags($article->excerpt ?? ''), 0, 300),
                'score' => 2.0,
                'published_at' => $article->published_at?->toIso8601String(),
                'category' => null,
                'icon' => '📰',
                'type' => 'article',
                '_content' => strip_tags($article->content ?? ''),
            ];
        }

        // 2. CHUNK WIKIPEDIA (dopo gli articoli)
        foreach ($wikiChunks as $chunk) {
            $context[] = [
                'id' => 'wiki-chunk-' . $chunk['index'],
                'kind' => 'wiki_chunk',
                'title' => 'Wikipedia: ' . $page->title . ' (parte ' . ($chunk['index'] + 1) . ')',
                'slug' => $page->slug,
                'url' => '/wiki/' . $page->slug,
                'excerpt' => mb_substr($chunk['content'], 0, 300),
                'score' => $chunk['score'],
                'published_at' => null,
                'category' => null,
                'icon' => '📚',
                'type' => 'wikipedia',
                '_content' => $chunk['content'],
            ];
        }

        // 3. INFOBOX
        if (!empty($page->infobox)) {
            $infoboxText = collect($page->infobox)
                ->map(fn ($v, $k) => "{$k}: {$v}")
                ->implode(' · ');

            $context[] = [
                'id' => 'wiki-infobox',
                'kind' => 'wiki_infobox',
                'title' => 'Scheda: ' . $page->title,
                'slug' => $page->slug,
                'url' => '/wiki/' . $page->slug,
                'excerpt' => mb_substr($infoboxText, 0, 300),
                'score' => 0.9,
                'published_at' => null,
                'category' => null,
                'icon' => '📋',
                'type' => 'infobox',
                '_content' => $infoboxText,
            ];
        }

        return $context;
    }

    /**
     * Streaming SSE (identico a AskController).
     */
    protected function streamResponse(string $question, array $context, string $prompt, string $questionHash, ?string $sessionId, WikiPage $page)
    {
        $ollamaUrl = config('services.ollama.url', 'http://localhost:11434');
        $model = config('services.ollama.model', 'qwen2.5:3b');
        $timeout = config('services.ollama.timeout', 600);

        return response()->stream(function () use ($question, $context, $prompt, $questionHash, $sessionId, $page, $ollamaUrl, $model, $timeout) {
            $startTime = microtime(true);
            $fullAnswer = '';

            // Pre-crea AskQuery
            $askQuery = null;
            try {
                $askQuery = AskQuery::create([
                    'question' => $question,
                    'question_hash' => $questionHash,
                    'answer' => '',
                    'answer_length' => 0,
                    'sources_count' => count($context),
                    'sources' => [
                        'context_type' => 'wiki',
                        'wiki_slug' => $page->slug,
                        'wiki_id' => $page->id,
                        'items' => collect($context)->map(fn ($r) => [
                            'id' => $r['id'],
                            'kind' => $r['kind'],
                            'title' => $r['title'],
                            'url' => $r['url'],
                            'score' => $r['score'],
                        ])->toArray(),
                    ],
                    'model' => $model,
                    'search_ms' => 0,
                    'total_ms' => 0,
                    'ip' => request()->ip(),
                    'session_id' => $sessionId,
                    'user_agent' => request()->userAgent(),
                    'was_cached' => false,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to pre-create WikiAskQuery', ['error' => $e->getMessage()]);
            }

            // Disabilita buffering
            @ini_set('zlib.output_compression', '0');
            @ini_set('output_buffering', '0');
            @ini_set('implicit_flush', '1');
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            ob_implicit_flush(true);

            // Invia fonti
            $this->sendSse('sources', [
                'sources' => collect($context)->map(fn ($r) => [
                    'id' => $r['id'],
                    'kind' => $r['kind'],
                    'title' => $r['title'],
                    'url' => $r['url'],
                    'excerpt' => $r['excerpt'],
                    'score' => $r['score'],
                    'icon' => $r['icon'],
                    'type' => $r['type'],
                ])->toArray(),
                'query_id' => $askQuery?->id,
            ]);

            // Payload
            $payload = json_encode([
                'model' => $model,
                'prompt' => $prompt,
                'stream' => true,
                'options' => [
                    'temperature' => 0.1,
                    'top_p' => 0.9,
                    'num_predict' => 400,
                    'num_ctx' => 8192,
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

            // Leggi NDJSON
            while (!feof($stream)) {
                $line = fgets($stream);
                if ($line === false) break;
                $line = trim($line);
                if (empty($line)) continue;

                $json = json_decode($line, true);
                if (!is_array($json)) continue;

                if (isset($json['response']) && $json['response'] !== '') {
                    $fullAnswer .= $json['response'];
                    $this->sendSse('token', ['text' => $json['response']]);
                }

                if (!empty($json['done'])) break;
            }

            @fclose($stream);

            // Aggiorna analytics
            if ($askQuery) {
                try {
                    $askQuery->update([
                        'answer' => $fullAnswer,
                        'answer_length' => mb_strlen($fullAnswer),
                        'total_ms' => (int) round((microtime(true) - $startTime) * 1000),
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Failed to update WikiAskQuery', ['error' => $e->getMessage()]);
                }
            }

            $this->sendSse('done', ['ok' => true]);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    protected function sendSse(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
        if (ob_get_level() > 0) @ob_flush();
        @flush();
    }

    /**
     * Risposta completa (non-streaming).
     */
    protected function fullResponse(string $question, array $context, string $prompt, string $questionHash, ?string $sessionId, WikiPage $page)
    {
        $startTime = microtime(true);
        $ollamaUrl = config('services.ollama.url', 'http://localhost:11434');
        $model = config('services.ollama.model', 'qwen2.5:3b');
        $timeout = config('services.ollama.timeout', 600);

        try {
            $payload = json_encode([
                'model' => $model,
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'temperature' => 0.1,
                    'top_p' => 0.9,
                    'num_predict' => 400,
                    'num_ctx' => 8192,
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

            // Salva analytics
            try {
                AskQuery::create([
                    'question' => $question,
                    'question_hash' => $questionHash,
                    'answer' => $answer,
                    'answer_length' => mb_strlen($answer),
                    'sources_count' => count($context),
                    'sources' => [
                        'context_type' => 'wiki',
                        'wiki_slug' => $page->slug,
                        'wiki_id' => $page->id,
                        'items' => collect($context)->map(fn ($r) => [
                            'id' => $r['id'],
                            'kind' => $r['kind'],
                            'title' => $r['title'],
                            'url' => $r['url'],
                            'score' => $r['score'],
                        ])->toArray(),
                    ],
                    'model' => $model,
                    'total_ms' => (int) round((microtime(true) - $startTime) * 1000),
                    'ip' => request()->ip(),
                    'session_id' => $sessionId,
                    'user_agent' => request()->userAgent(),
                    'was_cached' => false,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to save WikiAskQuery', ['error' => $e->getMessage()]);
            }

            return response()->json([
                'question' => $question,
                'answer' => $answer,
                'sources' => collect($context)->map(fn ($r) => [
                    'id' => $r['id'],
                    'kind' => $r['kind'],
                    'title' => $r['title'],
                    'url' => $r['url'],
                    'excerpt' => $r['excerpt'],
                    'score' => $r['score'],
                    'icon' => $r['icon'],
                    'type' => $r['type'],
                ])->toArray(),
                'wiki' => ['slug' => $page->slug, 'title' => $page->title],
                'model' => $model,
            ]);
        } catch (\Throwable $e) {
            Log::error('WikiAsk full error', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
