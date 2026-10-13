<?php

namespace App\Services\Llm;

use App\Models\Article;
use App\Models\Entity;
use Illuminate\Support\Facades\Log;

class EntityExtractor
{
    public function __construct(
        protected OllamaClient $ollama
    ) {}

    /**
     * Estrae le entità menzionate in un articolo.
     *
     * @return array<array{name: string, type: string, mentions: int, context: string|null}>
     */
    public function extract(Article $article): array
    {
        // Usa modello veloce per estrazione (più affidabile in JSON)
        $extractionModel = config('services.ollama.model_extraction', 'qwen2.5:3b');
        $ollama = $this->ollama->withModel($extractionModel);

        if (!$ollama->isAvailable()) {
            Log::error('Ollama not available for entity extraction');
            return [];
        }

        $content = $this->prepareContent($article);
        if (strlen($content) < 100) {
            Log::warning('Article too short for entity extraction', ['article_id' => $article->id]);
            return [];
        }

        $prompt = $this->buildPrompt($article, $content);

        $startTime = microtime(true);
        $response = $this->ollama->generate($prompt);
        $duration = round(microtime(true) - $startTime, 2);

        if (!$response) {
            Log::error('Failed to extract entities', ['article_id' => $article->id]);
            return [];
        }

        Log::info('Entity extraction completed', [
            'article_id' => $article->id,
            'duration_seconds' => $duration,
        ]);

        return $this->parseResponse($response, $content);
    }

    protected function prepareContent(Article $article): string
    {
        $text = strip_tags($article->content ?? '');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        return mb_substr($text, 0, 6000);
    }

    protected function buildPrompt(Article $article, string $content): string
    {
        $title = $article->title;

        return <<<PROMPT
Sei un giornalista italiano esperto. Analizza il seguente articolo ed estrai le entità menzionate.

TIPI DI ENTITÀ:
- person: persone (Papa Leone XIV, Giorgia Meloni)
- organization: organizzazioni (NATO, Vaticano, Governo Italiano)
- location: luoghi (Roma, Ucraina, Policlinico Gemelli)
- event: eventi (Guerra in Ucraina, Intervento Papa Leone)
- concept: concetti (Inflazione, Democrazia)

REGOLE IMPORTANTI:
- Estrai SOLO entità chiaramente menzionate nel testo
- Usa i nomi propri ESATTAMENTE come appaiono nel testo
- Non inventare entità non presenti
- Per ogni entità indica: name, type, mentions (quante volte appare nel testo), context (breve frase in cui appare la prima volta, max 100 char)
- Ordina per rilevanza (mentions decrescente)

Articolo:
Titolo: {$title}
Testo: {$content}

Rispondi SOLO con JSON valido, senza testo aggiuntivo:
{
  "entities": [
    {"name": "Papa Leone XIV", "type": "person", "mentions": 5, "context": "..."},
    {"name": "Policlinico Gemelli", "type": "location", "mentions": 3, "context": "..."}
  ]
}
PROMPT;
    }

    /**
     * @return array<array{name: string, type: string, mentions: int, context: string|null}>
     */
    protected function parseResponse(string $response, string $originalContent = ''): array
    {
        if (!preg_match('/\{[\s\S]*\}/', $response, $matches)) {
            Log::warning('Failed to parse entity extraction response', [
                'response' => mb_substr($response, 0, 500),
            ]);
            return [];
        }

        $data = json_decode($matches[0], true);

        if (!is_array($data) || !isset($data['entities']) || !is_array($data['entities'])) {
            Log::warning('Invalid JSON structure in entity extraction');
            return [];
        }

        $entities = [];

        foreach ($data['entities'] as $item) {
            if (empty($item['name']) || empty($item['type'])) {
                continue;
            }

            $name = trim($item['name']);
            $mentions = (int) ($item['mentions'] ?? 1);

            // Scarta entità con mentions=0
            if ($mentions <= 0) {
                Log::info('Entity discarded (mentions=0)', ['name' => $name]);
                continue;
            }

            // Scarta entità il cui nome NON appare nel contenuto originale
            if (!empty($originalContent) && !$this->nameAppearsInContent($name, $originalContent)) {
                Log::info('Entity discarded (not in content)', ['name' => $name, 'type' => $item['type']]);
                continue;
            }

            $context = null;
            if (!empty($item['context'])) {
                $context = html_entity_decode(
                    mb_substr(trim($item['context']), 0, 200),
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                );
            }

            $entities[] = [
                'name' => $name,
                'type' => $this->normalizeType($item['type']),
                'mentions' => $mentions,
                'context' => $context,
            ];
        }

        return $entities;
    }

    /**
     * Verifica se il nome appare nel contenuto (anche solo una parte significativa).
     */
    protected function nameAppearsInContent(string $name, string $content): bool
    {
        // Match diretto
        if (mb_stripos($content, $name) !== false) {
            return true;
        }

        // Prova varianti (es. "Parolin" per "Pietro Parolin")
        if (strpos($name, ' ') !== false) {
            $parts = explode(' ', $name);
            foreach ($parts as $part) {
                if (mb_strlen($part) > 4 && mb_stripos($content, $part) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function normalizeType(string $type): string
    {
        $allowed = array_keys(Entity::TYPE_LABELS);
        $type = strtolower(trim($type));

        return in_array($type, $allowed, true) ? $type : 'concept';
    }
}
