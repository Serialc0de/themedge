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
	$response = $ollama->generate($prompt, ['temperature' => 0.1]);
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
Sei un estrattore di entità di un giornale italiano. Estrai SOLO entità con NOME PROPRIO ESPLICITO.

TIPI AMMESSI:
- person: persone con nome e cognome (Giorgia Meloni, Donald Trump, Luciano Spalletti)
- organization: partiti, enti, aziende, squadre (NATO, Pd, M5s, Lega, Ue, Juventus)
- location: paesi, città, regioni, luoghi (Ucraina, Roma, Bruxelles, Cagliari)
- event: eventi storici/specifici con nome proprio (Guerra in Ucraina, Europa League)
- concept: SOLO se è un concetto nominato esplicitamente (inflazione, democrazia)

REGOLE FONDAMENTALI:
1. Estrai SOLO entità MENZIONATE ESPLICITAMENTE nel testo con nome proprio
2. NON inventare entità non presenti
3. NON estrarre:
   - Parole comuni o verbi (nato, padre, cosa, fatto)
   - Verbi e participi passati (nato, detto, fatto)
   - Parole generiche (squadra, partita, allenatore)
4. Per acronimi/sigle (NATO, NEC, UE): estrai SOLO se scritti in MAIUSCOLO nel testo
5. Usa il NOME COMPLETO quando possibile (es. Luciano Spalletti)
6. Massimo 10-15 entità per articolo

Articolo:
Titolo: {$title}
Testo: {$content}

ESEMPIO CORRETTO:
Testo: Meloni ha incontrato Trump a Roma. Salvini critica la NATO.
Risposta: {"entities": [{"name": "Giorgia Meloni", "type": "person", "mentions": 1}, {"name": "Donald Trump", "type": "person", "mentions": 1}, {"name": "Roma", "type": "location", "mentions": 1}, {"name": "Matteo Salvini", "type": "person", "mentions": 1}, {"name": "NATO", "type": "organization", "mentions": 1}]}

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
        // Per acronimi/sigle (tutti maiuscoli), usa match case-sensitive
        $isAcronym = mb_strlen($name) <= 5
            && $name === mb_strtoupper($name)
            && preg_match('/^[A-Z]+$/', $name);

        if ($isAcronym) {
            // "NATO" deve apparire come "NATO" (maiuscolo), non "nato"
            return strpos($content, $name) !== false;
        }

        // Per nomi normali: case-insensitive
        if (mb_stripos($content, $name) !== false) {
            return true;
        }

        // Per nomi composti: verifica che almeno una parte significativa compaia
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
