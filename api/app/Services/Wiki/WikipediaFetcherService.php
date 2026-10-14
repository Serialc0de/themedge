<?php

namespace App\Services\Wiki;

use App\Models\Entity;
use App\Models\WikipediaChunk;
use App\Services\Rag\OllamaEmbeddingService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WikipediaFetcherService
{
    protected string $userAgent = 'Themedge/1.0 (https://themedge.com; bot@themedge.com)';

    public function __construct(
        protected OllamaEmbeddingService $embedder,
    ) {}

    /**
     * Fetch Wikipedia per un'entity: cerca, scarica, chunk+embed, salva.
     *
     * @return array{status: string, url: ?string, lang: ?string, chunks: int, chars: int}
     */
    public function fetchForEntity(Entity $entity, bool $force = false): array
    {
        if (!$force && $entity->wikipedia_status === 'ready' && $entity->wikipedia_text) {
            return [
                'status' => 'already_ready',
                'url' => $entity->wikipedia_url,
                'lang' => $entity->wikipedia_lang,
                'chunks' => $entity->wikipediaChunks()->count(),
                'chars' => mb_strlen($entity->wikipedia_text),
            ];
        }

        // 1. Cerca su Wikipedia
        $found = $this->searchWikipedia($entity->name, $entity->aliases ?? []);
        if (!$found) {
            $this->markStatus($entity, 'no_page', null, null, null);
            return ['status' => 'no_page', 'url' => null, 'lang' => null, 'chunks' => 0, 'chars' => 0];
        }

        if (($found['status'] ?? '') === 'disambiguation') {
            $this->markStatus($entity, 'skipped', null, null, 'Pagina di disambiguazione');
            return ['status' => 'skipped', 'url' => null, 'lang' => null, 'chunks' => 0, 'chars' => 0];
        }

        // 2. Scarica il testo plain
        $text = $this->fetchArticlePlainText($found['title'], $found['lang']);
        if (!$text || mb_strlen($text) < 300) {
            $this->markStatus($entity, 'no_content', $found['url'], $found['lang'], null);
            return ['status' => 'no_content', 'url' => $found['url'], 'lang' => $found['lang'], 'chunks' => 0, 'chars' => $text ? mb_strlen($text) : 0];
        }

        // 3. Salva il testo su entity
        $entity->update([
            'wikipedia_url' => $found['url'],
            'wikipedia_text' => $text,
            'wikipedia_lang' => $found['lang'],
            'wikipedia_fetched_at' => now(),
            'wikipedia_status' => 'fetching',
        ]);

        // 4. Chunking + embedding
        $chunksCount = $this->chunkAndEmbed($entity);

        // 5. Marca come ready
        $entity->update(['wikipedia_status' => 'ready']);

        Log::info('Wikipedia fetched', [
            'entity_id' => $entity->id,
            'lang' => $found['lang'],
            'chars' => mb_strlen($text),
            'chunks' => $chunksCount,
        ]);

        return [
            'status' => 'ready',
            'url' => $found['url'],
            'lang' => $found['lang'],
            'chunks' => $chunksCount,
            'chars' => mb_strlen($text),
        ];
    }

    /**
     * Cerca su Wikipedia IT → EN.
     */
    protected function searchWikipedia(string $name, array $aliases = []): ?array
    {
        $queries = array_filter(array_merge([$name], $aliases));

        foreach (['it', 'en'] as $lang) {
            foreach ($queries as $query) {
                $result = $this->searchWikipediaByTitle($query, $lang);
                if ($result) return $result;
            }
        }
        return null;
    }

    /**
     * Cerca una specifica query su Wikipedia in una lingua.
     */
    protected function searchWikipediaByTitle(string $title, string $lang): ?array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => $this->userAgent])
                ->get("https://{$lang}.wikipedia.org/w/api.php", [
                    'action' => 'query',
                    'titles' => $title,
                    'prop' => 'pageprops|info',
                    'inprop' => 'url',
                    'redirects' => 1,
                    'format' => 'json',
                ]);

            if (!$response->ok()) return null;
            $data = $response->json();
            $pages = $data['query']['pages'] ?? [];

            foreach ($pages as $pageId => $page) {
                if ($pageId === '-1' || isset($page['missing'])) continue;

                if (isset($page['pageprops']['disambiguation'])) {
                    return ['status' => 'disambiguation', 'lang' => $lang];
                }

                $canonicalTitle = $page['title'] ?? $title;
                $url = $page['fullurl'] ?? ('https://' . $lang . '.wikipedia.org/wiki/' . rawurlencode(str_replace(' ', '_', $canonicalTitle)));

                return [
                    'status' => 'ok',
                    'title' => $canonicalTitle,
                    'url' => $url,
                    'lang' => $lang,
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('Wikipedia search error', ['title' => $title, 'lang' => $lang, 'error' => $e->getMessage()]);
        }
        return null;
    }

    /**
     * Scarica il testo plain di una pagina Wikipedia.
     */
    protected function fetchArticlePlainText(string $title, string $lang): ?string
    {
        try {
            $response = Http::timeout(20)
                ->withHeaders(['User-Agent' => $this->userAgent])
                ->get("https://{$lang}.wikipedia.org/w/api.php", [
                    'action' => 'query',
                    'titles' => $title,
                    'prop' => 'extracts',
                    'explaintext' => 1,
                    'exsectionformat' => 'plain',
                    'redirects' => 1,
                    'format' => 'json',
                ]);

            if (!$response->ok()) return null;
            $data = $response->json();
            $pages = $data['query']['pages'] ?? [];

            foreach ($pages as $pageId => $page) {
                if ($pageId === '-1' || isset($page['missing'])) continue;
                $extract = $page['extract'] ?? null;
                if ($extract && mb_strlen($extract) > 100) {
                    // Pulisci: rimuovi sezioni finali standard (Note, Bibliografia, Collegamenti esterni)
                    $extract = $this->stripBoilerplate($extract);
                    return trim($extract);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Wikipedia extract error', ['title' => $title, 'lang' => $lang, 'error' => $e->getMessage()]);
        }
        return null;
    }

    /**
     * Rimuove sezioni finali non enciclopediche.
     */
    protected function stripBoilerplate(string $text): string
    {
        // Taglia a "Note", "Bibliografia", "Collegamenti esterni", "Voci correlate", "Altri progetti"
        $stopMarkers = [
            "\n== Note ==",
            "\n== Bibliografia ==",
            "\n== Collegamenti esterni ==",
            "\n== Voci correlate ==",
            "\n== Altri progetti ==",
            "\n== Collegamenti esterni ==",
            "\n== Fonti ==",
            "\n== Riferimenti ==",
        ];

        $earliest = null;
        foreach ($stopMarkers as $marker) {
            $pos = mb_strpos($text, $marker);
            if ($pos !== false && ($earliest === null || $pos < $earliest)) {
                $earliest = $pos;
            }
        }

        if ($earliest !== null && $earliest > 500) {
            $text = mb_substr($text, 0, $earliest);
        }

        return $text;
    }

    /**
     * Chunking + embedding: divide il testo in chunk ~500 token con overlap.
     */
    protected function chunkAndEmbed(Entity $entity): int
    {
        // Rimuovi chunk esistenti
        WikipediaChunk::where('entity_id', $entity->id)->delete();

        $text = $entity->wikipedia_text;
        if (!$text) return 0;

        // Chunking per paragrafi
        $paragraphs = preg_split('/\n{2,}/', $text);
        $paragraphs = array_filter(array_map('trim', $paragraphs));

        $chunks = [];
        $current = '';
        $maxChars = 2000;  // ~500 token
        $overlapChars = 300;

        foreach ($paragraphs as $p) {
            if (mb_strlen($p) < 50) continue; // ignora paragrafi troppo corti

            // Se il singolo paragrafo è troppo lungo da solo, spezzalo
            if (mb_strlen($p) > $maxChars) {
                if (!empty(trim($current))) {
                    $chunks[] = trim($current);
                    $current = '';
                }
                $subChunks = mb_str_split($p, $maxChars - 200);
                foreach ($subChunks as $sub) {
                    $sub = trim($sub);
                    if (mb_strlen($sub) >= 100) {
                        $chunks[] = $sub;
                    }
                }
                continue;
            }

            if (mb_strlen($current) + mb_strlen($p) + 2 > $maxChars) {
                if (!empty($current)) {
                    $chunks[] = trim($current);
                    // Overlap: prendi ultimi N char
                    $current = mb_substr($current, -$overlapChars) . "\n\n" . $p;
                } else {
                    $chunks[] = $p;
                    $current = '';
                }
            } else {
                $current .= ($current ? "\n\n" : '') . $p;
            }
        }

        if (!empty(trim($current))) {
            $chunks[] = trim($current);
        }

        // Salva chunk + embedding
        $count = 0;
        $model = $this->embedder->getModel();

        foreach ($chunks as $i => $chunkText) {
            if (mb_strlen($chunkText) < 100) continue;

            $embedding = $this->embedder->embed($chunkText);

            WikipediaChunk::create([
                'entity_id' => $entity->id,
                'chunk_index' => $i,
                'content' => $chunkText,
                'embedding' => $embedding,
                'embedding_model' => $model,
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Aggiorna lo stato di wikipedia sull'entity.
     */
    protected function markStatus(Entity $entity, string $status, ?string $url, ?string $lang, ?string $note = null): void
    {
        $entity->update([
            'wikipedia_status' => $status,
            'wikipedia_url' => $url,
            'wikipedia_lang' => $lang,
            'wikipedia_fetched_at' => now(),
        ]);

        Log::info('Wikipedia fetch: ' . $status, [
            'entity_id' => $entity->id,
            'entity_name' => $entity->name,
            'note' => $note,
        ]);
    }
}
