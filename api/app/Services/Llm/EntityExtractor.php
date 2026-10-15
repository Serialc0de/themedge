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

    public function extract(Article $article): array
    {
        $ollama = $this->ollama;

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
        $response = $ollama->generate($prompt);
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
Analizza questo articolo di giornale ed estrai TUTTE le entità nominate.

TIPI:
- person: persone (Giorgia Meloni, Donald Trump, Vladimir Putin)
- organization: partiti, enti, aziende (NATO, Pd, M5s, Lega, Ue, ANSA)
- location: paesi, città, regioni (Ucraina, Russia, Roma, Bruxelles, Gaza)
- event: eventi storici/specifici (Guerra in Ucraina, Consiglio Ue)
- concept: concetti astratti (inflazione, democrazia)

Articolo:
Titolo: {$title}
Testo: {$content}

ISTRUZIONI:
- Estrai almeno 10-15 entità se presenti nel testo
- Usa il nome completo (es. "Giorgia Meloni" non "Meloni")
- Ogni entità una sola volta
- mentions = numero di occorrenze nel testo
- context = breve frase (max 100 char) dove appare

ESEMPIO:
Testo: "Meloni ha incontrato Trump a Roma. Salvini e Vannacci hanno criticato l'Ue."
Risposta: {"entities": [
  {"name": "Giorgia Meloni", "type": "person", "mentions": 1, "context": "Meloni ha incontrato Trump a Roma"},
  {"name": "Donald Trump", "type": "person", "mentions": 1, "context": "Meloni ha incontrato Trump a Roma"},
  {"name": "Roma", "type": "location", "mentions": 1, "context": "Meloni ha incontrato Trump a Roma"},
  {"name": "Matteo Salvini", "type": "person", "mentions": 1, "context": "Salvini e Vannacci hanno criticato l'Ue"},
  {"name": "Roberto Vannacci", "type": "person", "mentions": 1, "context": "Salvini e Vannacci hanno criticato l'Ue"},
  {"name": "Unione Europea", "type": "organization", "mentions": 1, "context": "Salvini e Vannacci hanno criticato l'Ue"}
]}

Rispondi SOLO con JSON valido:
PROMPT;
    }

    protected function parseResponse(string $response, string $originalContent = ''): array
    {
        if (!preg_match('/\{[\s\S]{10,50000}\}/', $response, $matches)) {
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
        $seen = [];

        foreach ($data['entities'] as $item) {
            if (empty($item['name']) || empty($item['type'])) {
                continue;
            }

            $name = trim($item['name']);
            $mentions = max(1, (int) ($item['mentions'] ?? 1));

            $nameKey = mb_strtolower($name);
            if (isset($seen[$nameKey])) continue;
            $seen[$nameKey] = true;

            if (!empty($originalContent) && !$this->nameAppearsInContent($name, $originalContent)) {
                Log::info('Entity discarded (not in content)', ['name' => $name]);
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

    protected function nameAppearsInContent(string $name, string $content): bool
    {
        if (mb_stripos($content, $name) !== false) {
            return true;
        }

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
