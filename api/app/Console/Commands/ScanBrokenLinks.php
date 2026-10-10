<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\BrokenLink;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ScanBrokenLinks extends Command
{
    protected $signature = 'seo:scan-links {--limit=50 : Numero massimo di articoli da scansionare}';
    protected $description = 'Scansiona i link negli articoli e salva quelli rotti';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $this->info("Scansione link in {$limit} articoli...");

        $articles = Article::where('status', 'published')
            ->latest()
            ->limit($limit)
            ->get();

        $bar = $this->output->createProgressBar($articles->count());
        $bar->start();

        $found = 0;
        $checked = 0;

        foreach ($articles as $article) {
            $links = $this->extractLinks($article->content ?? '');

            foreach ($links as $url) {
                if ($this->isInternal($url)) {
                    continue;
                }

                $checked++;
                $result = $this->checkUrl($url);

                if ($result !== null) {
                    $found++;
                    BrokenLink::updateOrCreate(
                        ['url_hash' => BrokenLink::hashUrl($url)],
                        [
                            'url' => $url,
                            'status_code' => $result['status'],
                            'error_message' => $result['error'],
                            'article_id' => $article->id,
                            'article_title' => $article->title,
                            'last_checked_at' => now(),
                            'is_resolved' => false,
                        ]
                    );
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Completato. Link controllati: {$checked}. Link rotti trovati: {$found}.");

        return self::SUCCESS;
    }

    protected function extractLinks(string $html): array
    {
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\']/i', $html, $matches);
        return array_unique($matches[1] ?? []);
    }

    protected function isInternal(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        return $host === null || str_contains($host, 'themedge.com');
    }

    protected function checkUrl(string $url): ?array
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return ['status' => 0, 'error' => 'URL non valido'];
        }

        try {
            $response = Http::timeout(10)
                ->withOptions(['allow_redirects' => ['max' => 5]])
                ->get($url);

            $status = $response->status();

            if ($status >= 400) {
                return ['status' => $status, 'error' => "HTTP {$status}"];
            }

            return null;
        } catch (\Throwable $e) {
            return ['status' => 0, 'error' => substr($e->getMessage(), 0, 200)];
        }
    }
}
