<?php

namespace App\Services\Wiki;

use App\Models\Article;
use App\Models\Entity;
use App\Models\WikiPage;
use App\Services\Llm\OllamaClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WikiGenerator
{
    public function __construct(
        protected OllamaClient $ollama
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
                'summary' => $generated['summary'] ?? null,
                'content' => $generated['content'] ?? null,
                'infobox' => $generated['infobox'] ?? null,
                'aliases' => $entity->aliases ?? [],
                'icon' => $this->getTypeIcon($entity->type),
                'featured_image' => $entity->photo_url,
                'is_ai_generated' => true,
                'ai_model' => config('services.ollama.model', 'llama3.1:8b'),
                'ai_generated_at' => now(),
                'entity_id' => $entity->id,
                'articles_count' => $articles->count(),
            ];

            if ($existing) {
                $existing->update($data);
                $page = $existing;
            } else {
                $data['status'] = 'draft';
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

        $prompt = <<<PROMPT
Sei un redattore enciclopedico. Devi scrivere una voce di wiki per un giornale online italiano.

ENTITÀ: {$entity->name}
TIPO: {$typeLabel}
BIO ESISTENTE: {$entity->bio}

ARTICOLI CHE NE PARLANO (estratti):
{$articlesContext}

Scrivi una voce wiki in italiano, stile enciclopedico ma giornalistico, con:
1. Un SOMMARIO di 1-2 frasi (summary)
2. Un CONTENUTO in markdown con sezioni pertinenti al tipo (es. per persona: Biografia, Ruolo, Eventi recenti; per luogo: Descrizione, Rilevanza, Eventi)
3. Un INFOBOX con dati strutturati (JSON) pertinenti al tipo

Rispondi SOLO con JSON valido:
{
  "summary": "Breve sommario in 1-2 frasi...",
  "content": "## Sezione 1\n\nTesto...\n\n## Sezione 2\n\nTesto...",
  "infobox": {
    "chiave1": "valore1",
    "chiave2": "valore2"
  }
}

REGOLE:
- Non inventare fatti non presenti negli articoli o nella bio
- Se un dato non è disponibile, ometti la chiave dall'infobox
- Tono neutro, enciclopedico
- Massimo 500 parole per il content
- Il content DEVE essere markdown valido
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
        // Caso 1: JSON completo valido
        if (preg_match('/\{[\s\S]*\}/', $response, $matches)) {
            $data = json_decode($matches[0], true);
            if (is_array($data)) {
                return [
                    'summary' => $data['summary'] ?? null,
                    'content' => $data['content'] ?? null,
                    'infobox' => $data['infobox'] ?? null,
                ];
            }
        }

        // Caso 2: risposta sembra JSON ma è troncata → prova repair
        if (str_starts_with(trim($response), '{')) {
            Log::warning('Wiki response sembra JSON troncato, provo repair', [
                'entity_id' => $entity->id,
                'response_len' => mb_strlen($response),
            ]);

            $repaired = $this->tryRepairTruncatedJson($response);
            if (is_array($repaired) && !empty($repaired['summary'])) {
                Log::info('Wiki JSON troncato riparato con successo', [
                    'entity_id' => $entity->id,
                ]);
                return [
                    'summary' => $repaired['summary'] ?? null,
                    'content' => $repaired['content'] ?? null,
                    'infobox' => $repaired['infobox'] ?? null,
                ];
            }

            // Repair fallito: NON salvare JSON grezzo come content
            Log::warning('Wiki repair fallito, uso fallback bio', [
                'entity_id' => $entity->id,
            ]);
            return [
                'summary' => $entity->bio ? mb_substr(strip_tags($entity->bio), 0, 200) : null,
                'content' => $entity->bio ? "## Descrizione\n\n{$entity->bio}" : null,
                'infobox' => null,
            ];
        }

        // Caso 3: risposta non-JSON → salva come content (ma logga)
        Log::warning('Wiki response non-JSON', [
            'entity_id' => $entity->id,
            'response' => mb_substr($response, 0, 300),
        ]);
        return [
            'summary' => $entity->bio ? mb_substr(strip_tags($entity->bio), 0, 200) : null,
            'content' => $response,
            'infobox' => null,
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
}
