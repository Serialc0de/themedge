<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Services\HtmlSanitizer;
use Illuminate\Console\Command;

class ResanitizeArticles extends Command
{
    protected $signature = 'articles:resanitize
                            {--dry-run : Mostra solo i diff, non salva}
                            {--force : Salva senza conferma}
                            {--id= : Processa solo un articolo per ID}';

    protected $description = 'Risanitizza il content degli articoli e popola content_raw';

    public function handle(HtmlSanitizer $sanitizer): int
    {
        $query = Article::query();
        if ($this->option('id')) {
            $query->where('id', (int) $this->option('id'));
        }

        $dry = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $total = $query->count();
        $this->info("Articoli da processare: {$total}");
        if ($dry) {
            $this->warn('Modalità DRY-RUN: nessuna modifica al DB');
        }

        if (!$dry && !$force && !$this->confirm('Procedo con la risanitizzazione?', true)) {
            return self::SUCCESS;
        }

        $changed = 0;
        $skipped = 0;

        $query->chunkById(50, function ($articles) use ($sanitizer, $dry, &$changed, &$skipped) {
            foreach ($articles as $article) {
                $original = (string) $article->content;
                $clean = $sanitizer->sanitize($original);

                if ($clean === $original) {
                    $skipped++;
                    continue;
                }

                $this->line("  #{$article->id} {$article->title}");
                if ($dry) {
                    $this->line('    - ' . substr($original, 0, 120));
                    $this->line('    + ' . substr($clean, 0, 120));
                }

                if (!$dry) {
                    if (empty($article->content_raw)) {
                        $article->content_raw = $original;
                    }
                    $article->content = $clean;
                    $article->saveQuietly();
                }
                $changed++;
            }
        });

        $this->newLine();
        $this->info("Modificati: {$changed}");
        $this->info("Già puliti: {$skipped}");

        return self::SUCCESS;
    }
}
