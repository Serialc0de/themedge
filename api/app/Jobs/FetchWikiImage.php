<?php

namespace App\Jobs;

use App\Models\WikiPage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FetchWikiImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries = 2;

    public function __construct(
        public WikiPage $page,
        public bool $force = false,
    ) {}

    public function handle(): void
    {
        $page = $this->page;

        // Skip se già pronta e non forzato
        if (!$this->force && $page->image_status === 'ready' && $page->featured_image) {
            Log::info('Wiki image già presente', ['wiki_id' => $page->id]);
            return;
        }

        // Se non c'è un titolo, non possiamo cercare nulla
        if (empty($page->title)) {
            $this->markStatus($page, 'skipped', 'Titolo wiki mancante');
            return;
        }

        // Costruisci le query di ricerca (title + aliases)
        $queries = $this->buildQueries($page);

        // Prova IT prima, poi EN
        foreach (['it', 'en'] as $lang) {
            $result = $this->searchWikipedia($queries, $lang);
            if ($result !== null) {
                break;
            }
        }

        if ($result === null || ($result['status'] ?? '') === 'not_found') {
            $this->markStatus($page, 'skipped', 'Pagina Wikipedia non trovata');
            return;
        }

        if (($result['status'] ?? '') === 'disambiguation') {
            $this->markStatus($page, 'skipped', 'Pagina di disambiguazione (nessuna immagine affidabile)');
            return;
        }

        if (($result['status'] ?? '') === 'no_image') {
            $this->markStatus($page, 'no_image', 'Pagina Wikipedia senza immagine principale');
            return;
        }

        // risultato valido
        $thumbUrl = $result['thumb_url'];
        $fileName = $result['file_name'];
        $lang     = $result['lang'];
        $pageUrl  = $result['page_url'];

        // Ottieni metadati da Commons
        $meta = $this->fetchCommonsMetadata($fileName);
        if ($meta === null) {
            $this->markStatus($page, 'failed', 'Impossibile recuperare metadati da Commons');
            return;
        }

        // Scarica l'immagine
        $path = $this->downloadAndStore($thumbUrl, $page);
        if ($path === null) {
            $this->markStatus($page, 'failed', 'Download immagine fallito');
            return;
        }

        // Aggiorna la wiki page
        $page->update([
            'featured_image'        => $path,
            'image_source_url'      => $meta['source_url'] ?? $pageUrl,
            'image_license'         => $meta['license_short'] ?? null,
            'image_author'          => $meta['author'] ?? null,
            'image_attribution'     => $meta['attribution'] ?? null,
            'image_fetched_at'      => now(),
            'image_status'          => 'ready',
        ]);

        Log::info('Wiki image fetch OK', [
            'wiki_id'   => $page->id,
            'file_name' => $fileName,
            'lang'      => $lang,
            'license'   => $meta['license_short'] ?? 'N/A',
        ]);
    }

    /**
     * Costruisce le query di ricerca: title, poi aliases.
     */
    protected function buildQueries(WikiPage $page): array
    {
        $queries = [trim($page->title)];
        if (is_array($page->aliases)) {
            foreach ($page->aliases as $alias) {
                if (is_string($alias) && trim($alias) !== '') {
                    $queries[] = trim($alias);
                }
            }
        }
        return array_values(array_unique($queries));
    }

    /**
     * Cerca su Wikipedia per una lista di query.
     * Ritorna array con status + dati, oppure null se nessuna query ha prodotto risultati.
     */
    protected function searchWikipedia(array $queries, string $lang): ?array
    {
        foreach ($queries as $q) {
            $data = $this->callWikipediaApi($q, $lang);
            if ($data === null) continue;

            $pages = $data['query']['pages'] ?? [];
            foreach ($pages as $pageId => $pageData) {
                if ($pageId === '-1' || isset($pageData['missing'])) {
                    continue; // pagina non trovata
                }

                // Disambiguazione?
                if (isset($pageData['pageprops']['disambiguation'])) {
                    return ['status' => 'disambiguation'];
                }

                // Senza immagine?
                if (empty($pageData['thumbnail']['source'])) {
                    return ['status' => 'no_image'];
                }

                // Costruisci URL canonico della pagina Wikipedia
                $canonicalTitle = $pageData['title'] ?? $q;
                $pageUrl = 'https://' . $lang . '.wikipedia.org/wiki/' . rawurlencode(str_replace(' ', '_', $canonicalTitle));

                return [
                    'status'    => 'ok',
                    'lang'      => $lang,
                    'thumb_url' => $this->cleanUrl($pageData['thumbnail']['source']),
                    'file_name' => $pageData['pageimage'] ?? null,
                    'page_url'  => $pageUrl,
                    'title'     => $canonicalTitle,
                ];
            }
        }
        return null;
    }

    /**
     * Chiamata all'API Wikipedia (pageimages).
     */
    protected function callWikipediaApi(string $title, string $lang): ?array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'Themedge/1.0 (https://themedge.com; bot@themedge.com)'])
                ->get("https://{$lang}.wikipedia.org/w/api.php", [
                    'action'     => 'query',
                    'titles'     => $title,
                    'prop'       => 'pageimages|pageprops',
                    'pithumbsize' => 800,
                    'format'     => 'json',
                    'redirects'  => 1,
                ]);

            if (!$response->ok()) return null;
            return $response->json();
        } catch (\Throwable $e) {
            Log::warning('Wikipedia API error', ['title' => $title, 'lang' => $lang, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Ottieni metadati da Commons (autore, licenza, URL pagina).
     */
    protected function fetchCommonsMetadata(?string $fileName): ?array
    {
        if (empty($fileName)) return null;

        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'Themedge/1.0 (https://themedge.com; bot@themedge.com)'])
                ->get('https://commons.wikimedia.org/w/api.php', [
                    'action'   => 'query',
                    'titles'   => 'File:' . $fileName,
                    'prop'     => 'imageinfo',
                    'iiprop'   => 'url|extmetadata',
                    'format'   => 'json',
                ]);

            if (!$response->ok()) return null;
            $data = $response->json();

            $pages = $data['query']['pages'] ?? [];
            foreach ($pages as $pageId => $page) {
                if ($pageId === '-1' || empty($page['imageinfo'][0])) continue;

                $info = $page['imageinfo'][0];
                $meta = $info['extmetadata'] ?? [];

                $author  = $this->stripHtml($meta['Artist']['value'] ?? '');
                $license = $this->stripHtml($meta['LicenseShortName']['value'] ?? '');
                $source  = $info['descriptionurl'] ?? null;

                // Costruisci attribuzione leggibile
                $attribution = null;
                if ($author && $license) {
                    $attribution = "{$author}, {$license}, via Wikimedia Commons";
                } elseif ($author) {
                    $attribution = "{$author}, via Wikimedia Commons";
                } elseif ($license) {
                    $attribution = "{$license}, via Wikimedia Commons";
                }

                return [
                    'author'        => $author ?: null,
                    'license_short' => $license ?: null,
                    'source_url'    => $source,
                    'attribution'   => $attribution,
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('Commons API error', ['file' => $fileName, 'error' => $e->getMessage()]);
        }
        return null;
    }

    /**
     * Scarica l'immagine e la salva in storage/app/public/wiki/.
     */
    protected function downloadAndStore(string $url, WikiPage $page): ?string
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'Themedge/1.0 (https://themedge.com; bot@themedge.com)'])
                ->get($url);

            if (!$response->ok()) return null;
            $body = $response->body();
            if (strlen($body) < 100) return null; // troppo piccola, errore

            // Estrai estensione dall'URL (default .jpg)
            $ext = 'jpg';
            $pathPart = parse_url($url, PHP_URL_PATH) ?? '';
            if (preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $pathPart, $m)) {
                $ext = strtolower($m[1]);
            }

            // Nome file univoco basato su slug
            $fileName = Str::slug($page->slug) . '-' . substr(md5($url), 0, 8) . '.' . $ext;
            $storagePath = 'wiki/' . $fileName;

            Storage::disk('public')->put($storagePath, $body);

            return $storagePath;
        } catch (\Throwable $e) {
            Log::warning('Download image error', ['url' => $url, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Pulisce un URL rimuovendo parametri utm_*.
     */
    protected function cleanUrl(string $url): string
    {
        return preg_replace('/[?&]utm_[^&]+/', '', $url);
    }

    /**
     * Rimuove HTML da una stringa.
     */
    protected function stripHtml(string $html): string
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim($text);
    }

    /**
     * Aggiorna lo stato dell'immagine e logga.
     */
    protected function markStatus(WikiPage $page, string $status, string $reason): void
    {
        $page->update([
            'image_status'     => $status,
            'image_fetched_at' => now(),
        ]);

        Log::info('Wiki image fetch: ' . $status, [
            'wiki_id' => $page->id,
            'title'   => $page->title,
            'reason'  => $reason,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('FetchWikiImage failed permanently', [
            'wiki_id' => $this->page->id,
            'error'   => $e->getMessage(),
        ]);

        $this->page->update([
            'image_status'     => 'failed',
            'image_fetched_at' => now(),
        ]);
    }
}
