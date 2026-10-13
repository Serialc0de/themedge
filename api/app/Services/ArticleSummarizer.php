<?php

namespace App\Services;

use App\Models\Article;
use App\Services\Llm\OllamaClient;
use Illuminate\Support\Facades\Log;

class ArticleSummarizer
{
    public function __construct(
        protected OllamaClient $ollama
    ) {}

    /**
     * Genera TL;DR + punti chiave + sommario per un articolo.
     *
     * @return array{tldr: string, key_points: array<string>, summary: string, generated_at: string}|null
     */
    public function generate(Article $article): ?array
    {
        if (!$this->ollama->isAvailable()) {
            Log::error('Ollama not available for article summary');
            return null;
        }

        $content = $this->prepareContent($article);
        if (strlen($content) < 100) {
            Log::warning('Article too short for summary', ['article_id' => $article->id]);
            return null;
        }

        $prompt = $this->buildPrompt($article, $content);

        $startTime = microtime(true);
        $response = $this->ollama->generate($prompt);
        $duration = round(microtime(true) - $startTime, 2);

        if (!$response) {
            Log::error('Failed to generate summary', ['article_id' => $article->id]);
            return null;
        }

        Log::info('Article summary generated', [
            'article_id' => $article->id,
            'duration_seconds' => $duration,
            'response_length' => strlen($response),
        ]);

        return $this->parseResponse($response);
    }

    protected function prepareContent(Article $article): string
    {
        // Rimuovi HTML e limita a ~6000 caratteri (contesto sufficiente)
        $text = strip_tags($article->content ?? '');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        return mb_substr($text, 0, 6000);
    }

    protected function buildPrompt(Article $article, string $content): string
    {
        $title = $article->title;

        return <<<PROMPT
Sei un giornalista italiano esperto. Analizza il seguente articolo e produci un riassunto strutturato in italiano.

REGOLE IMPORTANTI:
- Usa i nomi propri ESATTAMENTE come appaiono nel testo (non inventare, non modificare)
- Scrivi in italiano naturale e professionale
- Non aggiungere informazioni non presenti nel testo
- Rispondi SOLO con JSON valido, senza testo aggiuntivo

Articolo:
Titolo: {$title}
Testo: {$content}

Rispondi con questo formato JSON esatto:
{
  "tldr": "Frase di 2-3 righe che riassume l'articolo (max 200 caratteri)",
  "key_points": [
    "Punto chiave 1 (max 100 caratteri)",
    "Punto chiave 2",
    "Punto chiave 3"
  ],
  "summary": "Paragrafo di 3-4 frasi che riassume l'articolo in modo più dettagliato (max 500 caratteri)"
}

JSON:
PROMPT;
    }

    protected function parseResponse(string $response): ?array
    {
        // Estrai JSON dalla risposta (a volte il modello aggiunge testo prima/dopo)
        if (preg_match('/\{[\s\S]*\}/', $response, $matches)) {
            $json = $matches[0];
            $data = json_decode($json, true);

            if (is_array($data) && isset($data['tldr'])) {
                return [
                    'tldr' => trim($data['tldr'] ?? ''),
                    'key_points' => array_values(array_filter($data['key_points'] ?? [])),
                    'summary' => trim($data['summary'] ?? ''),
                    'generated_at' => now()->toIso8601String(),
                ];
            }
        }

        Log::warning('Failed to parse LLM response as JSON', [
            'response' => mb_substr($response, 0, 500),
        ]);

        return null;
    }
}
