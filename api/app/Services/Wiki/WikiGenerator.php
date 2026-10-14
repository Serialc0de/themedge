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

    /**
     * Genera una bozza di pagina wiki da una Entity.
     */
    public function generateFromEntity(Entity $entity, bool $force = false): ?WikiPage
    {
        // Verifica se esiste già
        $existing = WikiPage::where('entity_id', $entity->id)->first();
        if ($existing && !$force) {
            Log::info('Wiki page già esistente', ['entity_id' => $entity->id, 'wiki_id' => $existing->id]);
            return $existing;
        }

        // Trova articoli che menzionano questa entità
        $articles = $this->findRelatedArticles($entity);

        if ($articles->isEmpty()) {
            Log::info('Nessun articolo trovato per entity', ['entity_id' => $entity->id]);
            return null;
        }

        try {
            // Genera contenuto con Ollama
            $generated = $this->generateContent($entity, $articles);

            // Crea o aggiorna la pagina
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
                // Preserva featured_image se nuova è vuota
                if (empty($entity->photo_url) && !empty($existing->featured_image)) {
                    $data['featured_image'] = $existing->featured_image;
                }
                // Status preservato automaticamente (non incluso in $data)
                $existing->update($data);
                $page = $existing;
            } else {
                $data['status'] = 'draft';
                if ($entity->photo_url) {
                    $data['featured_image'] = $entity->photo_url;
                }
                $page = WikiPage::create($data);
            }

            // Collega articoli
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

    /**
     * Trova articoli che menzionano l'entità.
     */
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

    /**
     * Genera contenuto wiki con Ollama.
     */
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
- Massimo 600 parole per il content
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

        $ollama = $this->ollama->withModel(config('services.ollama.model', 'llama3.1:8b'));
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

    /**
     * Parsa la risposta JSON di Ollama.
     */
    protected function parseResponse(string $response, Entity $entity): array
{
    // Log raw response per debug
    \Log::info('WikiGenerator parseResponse START', [
        'entity_id' => $entity->id,
        'response_length' => strlen($response),
        'response_start' => mb_substr($response, 0, 200),
    ]);

    // Estrai JSON (multiline)
    if (!preg_match('/\{[\s\S]{10,50000}\}/', $response, $matches)) {
        \Log::warning('WikiGenerator: JSON non trovato', [
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

    // Se json_decode fallisce, prova a pulire
    if (!is_array($data)) {
        \Log::warning('WikiGenerator: json_decode fallito, provo a pulire', [
            'entity_id' => $entity->id,
            'json_error' => json_last_error_msg(),
            'json_length' => strlen($json),
        ]);

        // Rimuovi trailing commas
        $json = preg_replace('/,\s*([\}\]])/', '', $json);

        // Rimuovi caratteri di controllo (eccetto newline/tab)
        $json = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $json);

        $data = json_decode($json, true);
    }

    if (!is_array($data)) {
        \Log::error('WikiGenerator: impossibile parsare JSON', [
            'entity_id' => $entity->id,
        ]);
        return [
            'summary' => null,
            'content' => $response,
            'infobox' => null,
        ];
    }

    // Estrai infobox
    $infobox = $data['infobox'] ?? null;
    if (is_array($infobox)) {
        // Filtra valori nulli/vuoti
        $infobox = array_filter($infobox, fn ($v) => $v !== null && $v !== '' && $v !== []);
    }

    \Log::info('WikiGenerator parseResponse OK', [
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

    /**
     * Prova a riparare un JSON troncato (chiuso a metà da Ollama).
     * Restituisce l'array parsato o null se impossibile.
     */
    protected function tryRepairTruncatedJson(string $json): ?array
    {
        // Strategia 1: chiudi le graffe mancanti
        $openBraces = substr_count($json, '{');
        $closeBraces = substr_count($json, '}');
        $repaired = $json;
        for ($i = 0; $i < ($openBraces - $closeBraces); $i++) {
            $repaired .= '}';
        }

        // Se il numero di doppi apici è dispari, c'è una stringa aperta: chiudila
        $quotes = substr_count($repaired, '"');
        if ($quotes % 2 !== 0) {
            $repaired .= '"';
            // Poi chiudi graffe di nuovo (fino a 3 livelli di annidamento)
            for ($i = 0; $i < 3; $i++) {
                $repaired .= '}';
            }
        }

        // Prova il decode
        $data = json_decode($repaired, true);
        if (is_array($data) && isset($data['summary'])) {
            return $data;
        }

        // Strategia 2: rimuovi l'ultimo campo troncato (dopo l'ultima virgola valida)
        // e chiudi il JSON
        if (preg_match('/^(.*"[a-z_]+"\s*:\s*"(?:[^"\\\\]|\\\\.)*)",?\s*$/', $repaired, $m)) {
            // Tronca al campo incompleto
            $truncated = rtrim($m[1], ',') . '"' . str_repeat('}', 3);
            $data = json_decode($truncated, true);
            if (is_array($data) && isset($data['summary'])) {
                return $data;
            }
        }

        // Strategia 3: prova a estrarre summary e content con regex dal JSON troncato
        $summary = null;
        $content = null;
        if (preg_match('/"summary"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/', $json, $m)) {
            $summary = json_decode('"' . $m[1] . '"');
        }
        if (preg_match('/"content"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/', $json, $m)) {
            $content = json_decode('"' . $m[1] . '"');
        }
        if ($summary || $content) {
            return [
                'summary' => $summary,
                'content' => $content,
                'infobox' => null,
            ];
        }

        return null;
    }

    /**
     * Costruisce la sezione Wikipedia per il prompt (con RAG se disponibile).
     */
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
        $context = $this->wikiRag->buildContext($entity, $query, limit: 5);

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

    /**
     * Collega articoli alla pagina wiki con calcolo di rilevanza.
     */
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

    /**
     * Calcola rilevanza articolo ↔ entità (0-100).
     */
    protected function computeRelevance(Article $article, Entity $entity): int
    {
        $score = 0;

        // Titolo contiene il nome?
        if (mb_stripos($article->title, $entity->name) !== false) {
            $score += 50;
        }

        // Excerpt contiene il nome?
        if ($article->excerpt && mb_stripos($article->excerpt, $entity->name) !== false) {
            $score += 20;
        }

        // Content: quante volte appare?
        $mentions = substr_count(mb_strtolower($article->content ?? ''), mb_strtolower($entity->name));
        $score += min($mentions * 5, 30);

        return min($score, 100);
    }

    /**
     * Mappa tipo Entity → tipo Wiki.
     */
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

    /**
     * Icona per tipo.
     */
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

    /**
     * Slug unico per la pagina.
     */
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

    /**
     * Subtitle contestuale.
     */
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
     * Sceglie il content migliore: nuovo se valido, esistente se nuovo è troppo corto.
     */
    protected function pickBestContent(?WikiPage $existing, array $generated): ?string
    {
        $new = $generated['content'] ?? null;

        // Se non c'è esistente, usa nuovo
        if (!$existing || empty($existing->content)) {
            return $new;
        }

        // Se nuovo è vuoto, usa esistente
        if (empty($new)) {
            Log::info('WikiGenerator: content nuovo vuoto, uso esistente', [
                'wiki_id' => $existing->id,
            ]);
            return $existing->content;
        }

        // Se nuovo è < 50% dell'esistente, usa esistente
        $oldLen = mb_strlen($existing->content);
        $newLen = mb_strlen($new);

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

    /**
     * Sceglie l'infobox migliore: nuovo se valido, esistente se nuovo è vuoto.
     */
    protected function pickBestInfobox(?WikiPage $existing, array $generated): ?array
    {
        $new = $generated['infobox'] ?? null;

        // Se nuovo valido, usa nuovo
        if (is_array($new) && !empty($new)) {
            return $new;
        }

        // Altrimenti, preserva esistente
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
