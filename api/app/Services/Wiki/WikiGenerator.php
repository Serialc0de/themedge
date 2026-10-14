<?php

namespace App\Services\Wiki;

use App\Models\Article;
use App\Models\Entity;
use App\Models\WikiPage;
use App\Services\Llm\OllamaClient;
use App\Services\Wiki\WikipediaRagService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WikiGenerator
{
    public function __construct(
        protected OllamaClient $ollama,
        protected WikipediaRagService $wikiRag,
    ) {}

    public function generateFromEntity(Entity $entity, bool $force = false): ?WikiPage
    {
        $existing = WikiPage::where('entity_id', $entity->id)->first();
        if ($existing && !$force) {
            Log::info('Wiki page già esistente', ['entity_id' => $entity->id, 'wiki_id' => $existing->id]);
            return $existing;
        }

        $articles = $this->findRelatedArticles($entity);

        if ($articles->isEmpty()) {
            Log::info('Nessun articolo trovato per entity', ['entity_id' => $entity->id]);
            return null;
        }

        try {
            $generated = $this->generateContent($entity, $articles);

            $data = [
                'slug' => $this->buildSlug($entity),
                'title' => $entity->name,
                'subtitle' => $this->buildSubtitle($entity),
                'type' => $this->mapEntityTypeToWikiType($entity->type),
                'summary' => $generated['summary'] ?? ($existing->summary ?? null),
                'content' => $this->pickBestContent($existing, $generated),
                'infobox' => $this->pickBestInfobox($existing, $generated),
                'aliases' => $entity->aliases ?? ($existing->aliases ?? []),
                'icon' => $this->getTypeIcon($entity->type),
                'is_ai_generated' => true,
                'ai_model' => config('services.ollama.model', 'qwen2.5:3b'),
                'ai_generated_at' => now(),
                'entity_id' => $entity->id,
                'articles_count' => $articles->count(),
            ];

            if ($existing) {
                if (empty($entity->photo_url) && !empty($existing->featured_image)) {
                    $data['featured_image'] = $existing->featured_image;
                }
                $existing->update($data);
                $page = $existing;
            } else {
                $data['status'] = 'draft';
                if ($entity->photo_url) {
                    $data['featured_image'] = $entity->photo_url;
                }
                $page = WikiPage::create($data);
            }

            $this->linkArticles($page, $articles, $entity);

            Log::info('Wiki page generata', [
                'entity_id' => $entity->id,
                'wiki_id' => $page->id,
                'articles_count' => $articles->count(),
            ]);

            return $page;
        } catch (\Throwable $e) {
            Log::error('Errore generazione wiki', [
                'entity_id' => $entity->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function findRelatedArticles(Entity $entity)
    {
        return Article::whereHas('entities', function ($q) use ($entity) {
                $q->where('entities.id', $entity->id);
            })
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->limit(20)
            ->get();
    }

    protected function generateContent(Entity $entity, $articles): array
    {
        $articlesContext = $articles->map(function ($a) {
            $excerpt = mb_substr(strip_tags($a->excerpt ?? $a->content ?? ''), 0, 300);
            return "- [{$a->published_at->format('d/m/Y')}] {$a->title}: {$excerpt}";
        })->implode("\n");

        $typeLabels = [
            'person' => 'persona',
            'organization' => 'organizzazione',
            'location' => 'luogo',
            'event' => 'evento',
            'concept' => 'concetto',
        ];
        $typeLabel = $typeLabels[$entity->type] ?? 'entità';

        $wikipediaSection = $this->buildWikipediaSection($entity);

        $prompt = <<<PROMPT
Sei un redattore enciclopedico. Devi scrivere una voce di wiki per un giornale online italiano.

ENTITÀ: {$entity->name}
TIPO: {$typeLabel}
BIO ESISTENTE: {$entity->bio}

{$wikipediaSection}

ARTICOLI DEL GIORNALE (eventi recenti):
{$articlesContext}

Scrivi una voce wiki in italiano, stile enciclopedico ma giornalistico, con:
1. Un SOMMARIO di 1-2 frasi (summary)
2. Un CONTENUTO in markdown con sezioni pertinenti al tipo (es. per persona: Biografia, Ruolo, Eventi recenti; per luogo: Descrizione, Rilevanza, Eventi)
3. Un INFOBOX con dati strutturati (JSON) pertinenti al tipo

Rispondi SOLO con JSON valido (nessun testo prima o dopo):
{
  "summary": "Breve sommario in 1-2 frasi...",
  "content": "## Sezione 1\n\nTesto...\n\n## Sezione 2\n\nTesto...",
  "infobox": {
    "Nome": "Valore leggibile",
    "Data di nascita": "25 gennaio 1978",
    "Luogo di nascita": "Roma"
  }
}

REGOLE:
- Usa WIKIPEDIA come fonte primaria per i fatti (nascita, carriera, ruolo)
- Usa ARTICOLI DEL GIORNALE per eventi recenti e attualità
- NON copiare Wikipedia letteralmente: riscrivi con parole tue, sintetizza
- NON inventare fatti non presenti in Wikipedia o negli articoli
- Tono neutro, enciclopedico
- Il content DEVE essere tra 1500 e 2500 CARATTERI (non parole)
- Il content DEVE avere almeno 3 sezioni (##)
- Ogni sezione DEVE essere 400-800 caratteri
- Sii dettagliato: includi date, nomi, luoghi, numeri quando disponibili in Wikipedia
- Se Wikipedia menziona eventi storici o dettagli, includili
- Il content DEVE essere markdown valido

REGOLE IMPORTANTI PER L'INFOBOX:
- Le chiavi dell'infobox DEVONO essere in italiano, leggibili, con l'iniziale maiuscola e spazi tra le parole
- ESEMPIO CORRETTO: "Data di nascita", "Luogo di nascita", "Carica", "Partito politico"
- ESEMPIO SBAGLIATO: "data_di_nascita", "luogo_nascita", "chiave1"
- NON inserire chiavi con valore "sconosciuto", "sconosciuta", "N/A", "-", "non disponibile", "non noto"
- Se un dato non è disponibile, OMETTI completamente la chiave dall'infobox
- Massimo 5-7 chiavi pertinenti al tipo di entità:
  * persona: Nome, Data di nascita, Luogo di nascita, Carica, Partito politico
  * luogo: Nome, Paese, Regione, Popolazione, Superficie
  * organizzazione: Nome, Tipo, Fondazione, Sede, Leader
  * evento: Nome, Data, Luogo, Partecipanti
PROMPT;

        $ollama = $this->ollama->withModel(config('services.ollama.model', 'qwen2.5:3b'));
        $response = $ollama->generate($prompt);

        if (!$response) {
            Log::warning('Ollama vuoto per wiki', ['entity_id' => $entity->id]);
            return [
                'summary' => $entity->bio ? mb_substr($entity->bio, 0, 200) : null,
                'content' => $entity->bio ? "## Descrizione\n\n{$entity->bio}" : null,
                'infobox' => null,
            ];
        }

        return $this->parseResponse($response, $entity);
    }

    protected function parseResponse(string $response, Entity $entity): array
    {
        // Estrai JSON (multiline)
        if (!preg_match('/\{[\s\S]{10,50000}\}/', $response, $matches)) {
            Log::warning('WikiGenerator: JSON non trovato', [
                'entity_id' => $entity->id,
                'response_preview' => mb_substr($response, 0, 500),
            ]);
            return [
                'summary' => null,
                'content' => $response,
                'infobox' => null,
            ];
        }

        $json = $matches[0];
        $data = json_decode($json, true);

        if (!is_array($data)) {
            Log::warning('WikiGenerator: json_decode fallito, provo a pulire', [
                'entity_id' => $entity->id,
                'json_error' => json_last_error_msg(),
                'json_length' => strlen($json),
            ]);

            $json = preg_replace('/,\s*([\}\]])/', '', $json);
            $json = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $json);
            $data = json_decode($json, true);
        }

        if (!is_array($data)) {
            Log::error('WikiGenerator: impossibile parsare JSON', [
                'entity_id' => $entity->id,
            ]);
            return [
                'summary' => null,
                'content' => $response,
                'infobox' => null,
            ];
        }

        $infobox = $data['infobox'] ?? null;
        if (is_array($infobox)) {
            $infobox = array_filter($infobox, fn ($v) => $v !== null && $v !== '' && $v !== []);
        }

        Log::info('WikiGenerator parseResponse OK', [
            'entity_id' => $entity->id,
            'has_summary' => !empty($data['summary']),
            'content_length' => strlen($data['content'] ?? ''),
            'has_infobox' => is_array($infobox) && !empty($infobox),
            'infobox_keys' => is_array($infobox) ? count($infobox) : 0,
        ]);

        return [
            'summary' => $data['summary'] ?? null,
            'content' => $data['content'] ?? null,
            'infobox' => is_array($infobox) && !empty($infobox) ? $infobox : null,
        ];
    }

    protected function buildWikipediaSection(Entity $entity): string
    {
        if ($entity->wikipedia_status !== 'ready') {
            return "WIKIPEDIA: non disponibile per questa entità.\n";
        }

        $typeLabels = [
            'person' => 'persona',
            'organization' => 'organizzazione',
            'location' => 'luogo',
            'event' => 'evento',
            'concept' => 'concetto',
        ];
        $typeLabel = $typeLabels[$entity->type] ?? 'entità';

        $query = trim($entity->name . ' ' . $typeLabel . ' ' . mb_substr($entity->bio ?? '', 0, 300));
        $context = $this->wikiRag->buildContext($entity, $query, limit: 2);

        if (empty($context)) {
            Log::warning('WikiGenerator: Wikipedia context vuoto', ['entity_id' => $entity->id]);
            return "WIKIPEDIA: non disponibile per questa entità.\n";
        }

        Log::info('WikiGenerator: Wikipedia context costruito', [
            'entity_id' => $entity->id,
            'chars' => mb_strlen($context),
        ]);

        return "WIKIPEDIA (fonte verificata, usa per i fatti principali):\n{$context}\n";
    }

    protected function linkArticles(WikiPage $page, $articles, Entity $entity): void
    {
        $page->articles()->detach();

        foreach ($articles as $article) {
            $relevance = $this->computeRelevance($article, $entity);

            $page->articles()->attach($article->id, [
                'relevance' => $relevance,
                'context' => null,
            ]);
        }

        $page->update(['articles_count' => $articles->count()]);
    }

    protected function computeRelevance(Article $article, Entity $entity): int
    {
        $score = 0;

        if (mb_stripos($article->title, $entity->name) !== false) {
            $score += 50;
        }

        if ($article->excerpt && mb_stripos($article->excerpt, $entity->name) !== false) {
            $score += 20;
        }

        $mentions = substr_count(mb_strtolower($article->content ?? ''), mb_strtolower($entity->name));
        $score += min($mentions * 5, 30);

        return min($score, 100);
    }

    protected function mapEntityTypeToWikiType(string $entityType): string
    {
        return match ($entityType) {
            'person' => 'person',
            'organization' => 'organization',
            'location' => 'location',
            'event' => 'event',
            'concept' => 'concept',
            default => 'topic',
        };
    }

    protected function getTypeIcon(string $type): string
    {
        return match ($type) {
            'person' => '👤',
            'organization' => '🏛️',
            'location' => '🌍',
            'event' => '📅',
            'concept' => '💡',
            default => '📚',
        };
    }

    protected function buildSlug(Entity $entity): string
    {
        $base = \Illuminate\Support\Str::slug($entity->name);
        $slug = $base;
        $i = 1;

        while (WikiPage::where('slug', $slug)->where('entity_id', '!=', $entity->id)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    protected function buildSubtitle(Entity $entity): ?string
    {
        return match ($entity->type) {
            'person' => 'Persona',
            'organization' => 'Organizzazione',
            'location' => 'Luogo',
            'event' => 'Evento',
            default => null,
        };
    }

    /**
     * Guard: rifiuta content vuoto o troppo corto.
     */
    protected function pickBestContent(?WikiPage $existing, array $generated): ?string
    {
        $new = $generated['content'] ?? null;
        $newLen = $new ? mb_strlen($new) : 0;

        // Guard 1: nuovo > 0 ma < 100 char → rifiuta
        if ($newLen > 0 && $newLen < 100) {
            Log::warning('WikiGenerator: content nuovo troppo corto (< 100 char)', [
                'wiki_id' => $existing?->id,
                'new_len' => $newLen,
            ]);

            if ($existing && !empty($existing->content)) {
                return $existing->content;
            }
            return $new;
        }

        if (!$existing || empty($existing->content)) {
            return $new;
        }

        if (empty($new)) {
            Log::info('WikiGenerator: content nuovo vuoto, uso esistente', [
                'wiki_id' => $existing->id,
            ]);
            return $existing->content;
        }

        $oldLen = mb_strlen($existing->content);

        if ($newLen < $oldLen * 0.5) {
            Log::warning('WikiGenerator: content nuovo troppo corto, uso esistente', [
                'wiki_id' => $existing->id,
                'old_len' => $oldLen,
                'new_len' => $newLen,
                'ratio' => round($newLen / $oldLen, 2),
            ]);
            return $existing->content;
        }

        return $new;
    }

    protected function pickBestInfobox(?WikiPage $existing, array $generated): ?array
    {
        $new = $generated['infobox'] ?? null;

        if (is_array($new) && !empty($new)) {
            return $new;
        }

        if ($existing && !empty($existing->infobox)) {
            Log::info('WikiGenerator: infobox preservato', [
                'wiki_id' => $existing->id,
                'keys_count' => count($existing->infobox),
            ]);
            return $existing->infobox;
        }

        return null;
    }
}
