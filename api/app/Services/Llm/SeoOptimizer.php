<?php

namespace App\Services\Llm;

use App\Models\Article;
use Illuminate\Support\Facades\Log;

class SeoOptimizer
{
    public function __construct(
        protected OllamaClient $ollama
    ) {}

    /**
     * Genera suggerimenti SEO per un articolo.
     *
     * @return array{seo_title: string, meta_description: string, slug_suggestion: string, primary_keywords: array, hashtags: array, seo_score: int, suggestions: array}|null
     */
    public function optimize(Article $article): ?array
    {
        $model = config('services.ollama.model_extraction', 'qwen2.5:3b');
        $ollama = $this->ollama->withModel($model);

        if (!$ollama->isAvailable()) {
            Log::error('Ollama not available for SEO optimization');
            return null;
        }

        $content = $this->prepareContent($article);
        $prompt = $this->buildPrompt($article, $content);

        $startTime = microtime(true);
        $response = $ollama->generate($prompt);
        $duration = round(microtime(true) - $startTime, 2);

        if (!$response) {
            Log::error('Failed to generate SEO suggestions', ['article_id' => $article->id]);
            return null;
        }

        Log::info('SEO suggestions generated', [
            'article_id' => $article->id,
            'duration_seconds' => $duration,
        ]);

        return $this->parseResponse($response);
    }

    protected function prepareContent(Article $article): string
    {
        $text = strip_tags($article->content ?? '');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        return mb_substr($text, 0, 4000);
    }

    protected function buildPrompt(Article $article, string $content): string
    {
        $title = $article->title;
        $excerpt = $article->excerpt ?? '';

        return <<<PROMPT
Sei un SEO expert per un giornale online italiano. Analizza il seguente articolo e produci suggerimenti SEO in italiano.

REGOLE:
- seo_title: max 60 caratteri, clickable, con keyword principale
- meta_description: max 160 caratteri, persuasiva, invoglia al click
- slug_suggestion: max 60 caratteri, minuscolo, trattini, keyword principale
- primary_keywords: 3-5 keyword principali (max 30 char ciascuna)
- hashtags: 5-8 hashtag social (con #)
- seo_score: punteggio 0-100 di quanto è già ottimizzato l'articolo
- suggestions: 3-5 suggerimenti pratici per migliorare il SEO

Articolo:
Titolo: {$title}
Sommario: {$excerpt}
Contenuto: {$content}

Rispondi SOLO con JSON valido:
{
  "seo_title": "...",
  "meta_description": "...",
  "slug_suggestion": "...",
  "primary_keywords": ["...", "..."],
  "hashtags": ["#...", "#..."],
  "seo_score": 85,
  "suggestions": ["...", "..."]
}
PROMPT;
    }

    protected function parseResponse(string $response): ?array
    {
        if (!preg_match('/\{[\s\S]*\}/', $response, $matches)) {
            Log::warning('Failed to parse SEO response', ['response' => mb_substr($response, 0, 500)]);
            return null;
        }

        $data = json_decode($matches[0], true);

        if (!is_array($data) || empty($data['seo_title'])) {
            Log::warning('Invalid SEO JSON structure');
            return null;
        }

        return [
            'seo_title' => mb_substr(trim($data['seo_title'] ?? ''), 0, 60),
            'meta_description' => mb_substr(trim($data['meta_description'] ?? ''), 0, 160),
            'slug_suggestion' => mb_substr(trim($data['slug_suggestion'] ?? ''), 0, 70),
            'primary_keywords' => array_slice(array_values(array_filter($data['primary_keywords'] ?? [])), 0, 8),
            'hashtags' => array_slice(array_values(array_filter($data['hashtags'] ?? [])), 0, 10),
            'seo_score' => max(0, min(100, (int) ($data['seo_score'] ?? 0))),
            'suggestions' => array_slice(array_values(array_filter($data['suggestions'] ?? [])), 0, 8),
        ];
    }
}
