<?php

namespace App\Services\Llm;

use App\Models\Article;
use Illuminate\Support\Facades\Log;

class ClaimExtractor
{
    public function __construct(
        protected OllamaClient $ollama
    ) {}

    /**
     * Estrae le affermazioni verificabili da un articolo.
     *
     * @return array<array{claim_text: string, context: string|null, ai_suggested_verdict: string, ai_suggested_source_name: string|null, ai_suggested_source_url: string|null, ai_confidence: float|null, note: string|null}>
     */
    public function extract(Article $article): array
    {
        // Kill switch
        if (!config('services.ollama.factcheck_enabled', true)) {
            Log::info('ClaimExtractor disabled by kill switch');
            return [];
        }

        $content = $this->prepareContent($article);
        if (strlen($content) < 200) {
            Log::warning('Article too short for claim extraction', ['article_id' => $article->id]);
            return [];
        }

        $prompt = $this->buildPrompt($article, $content);

        // Solo llama3.1:8b (qualità top)
        $ollama = $this->ollama->withModel('llama3.1:8b');

        if (!$ollama->isAvailable()) {
            Log::error('Ollama not available for claim extraction');
            return [];
        }

        $startTime = microtime(true);
        $response = $ollama->generate($prompt);
        $duration = round(microtime(true) - $startTime, 2);

        if (!$response) {
            Log::error('Empty response from Ollama', ['duration_seconds' => $duration]);
            return [];
        }

        Log::info('Raw response from Ollama', [
            'length' => strlen($response),
            'duration_seconds' => $duration,
        ]);

        $claims = $this->parseResponse($response, $content);

        Log::info('Claim extraction completed', [
            'article_id' => $article->id,
            'count' => count($claims),
            'duration_seconds' => $duration,
        ]);

        return $claims;
    }

    protected function prepareContent(Article $article): string
    {
        $text = strip_tags($article->content ?? '');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        return mb_substr($text, 0, 5000);
    }

    protected function buildPrompt(Article $article, string $content): string
    {
        $title = $article->title;

        return "Estrai 5-10 fatti verificabili dall'articolo. Rispondi SOLO con JSON valido: {\"claims\": [{\"claim_text\": \"...\", \"context\": \"...\", \"suggested_verdict\": \"in_review\", \"source_name\": \"...\", \"confidence\": 0.9}]}\n\nArticolo:\n{$title}\n{$content}";
    }

    /**
     * @return array<array{claim_text: string, context: string|null, ai_suggested_verdict: string, ai_suggested_source_name: string|null, ai_suggested_source_url: string|null, ai_confidence: float|null, note: string|null}>
     */
    protected function parseResponse(string $response): array
    {
        if (!preg_match('/\{[\s\S]*\}/', $response, $matches)) {
            Log::warning('Failed to parse claim extraction response', [
                'response' => mb_substr($response, 0, 500),
            ]);
            return [];
        }

        $data = json_decode($matches[0], true);

        if (!is_array($data) || !isset($data['claims']) || !is_array($data['claims'])) {
            Log::warning('Invalid JSON structure in claim extraction', [
                'parsed' => $data ? 'yes' : 'no',
                'has_claims' => isset($data['claims']) ? 'yes' : 'no',
            ]);
            return [];
        }

        $claims = [];
        $allowedVerdicts = ['verified', 'in_review', 'false'];

        foreach ($data['claims'] as $item) {
            if (empty($item['claim_text'])) continue;

            $claimText = trim($item['claim_text']);
            $verdict = strtolower(trim($item['suggested_verdict'] ?? 'in_review'));

            if (!in_array($verdict, $allowedVerdicts, true)) {
                $verdict = 'in_review';
            }

            $claims[] = [
                'claim_text' => mb_substr($claimText, 0, 500),
                'context' => isset($item['context']) ? mb_substr(trim($item['context']), 0, 500) : null,
                'ai_suggested_verdict' => $verdict,
                'ai_suggested_source_name' => isset($item['source_name']) ? mb_substr(trim($item['source_name']), 0, 200) : null,
                'ai_suggested_source_url' => isset($item['source_url']) ? mb_substr(trim($item['source_url']), 0, 500) : null,
                'ai_confidence' => isset($item['confidence']) ? max(0, min(1, (float) $item['confidence'])) : null,
                'note' => null,
            ];
        }

        return array_slice($claims, 0, 10);
    }
}
