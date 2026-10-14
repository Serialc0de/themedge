<?php

namespace App\Console\Commands;

use App\Jobs\FetchWikiImage;
use App\Models\WikiPage;
use Illuminate\Console\Command;

class WikiFetchImagesCommand extends Command
{
    protected $signature = 'wiki:fetch-images
                            {--limit=50 : Numero massimo di wiki da processare}
                            {--sync : Esegui in modo sincrono (utile per debug)}
                            {--force : Riscarica anche se l\'immagine è già presente}
                            {--status= : Filtra per image_status (es. "", "failed", "no_image")}
                            {--id= : Processa solo una specifica wiki page per ID}';

    protected $description = 'Recupera immagini da Wikipedia/Commons per le pagine Wiki';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $sync = (bool) $this->option('sync');
        $force = (bool) $this->option('force');
        $statusFilter = $this->option('status');
        $id = $this->option('id');

        $query = WikiPage::query();

        if ($id) {
            $query->where('id', (int) $id);
        } elseif ($statusFilter !== null) {
            if ($statusFilter === '') {
                $query->where(function ($q) {
                    $q->whereNull('image_status')->orWhere('image_status', '');
                });
            } else {
                $query->where('image_status', $statusFilter);
            }
        } else {
            // Default: wiki senza immagine o con stato vuoto/failed/no_image
            $query->where(function ($q) {
                $q->whereNull('image_status')
                  ->orWhere('image_status', '')
                  ->orWhereIn('image_status', ['failed', 'no_image']);
            });
        }

        if (!$force && !$id) {
            $query->where(function ($q) {
                $q->whereNull('featured_image')->orWhere('featured_image', '');
            });
        }

        $pages = $query->orderByDesc('articles_count')->limit($limit)->get();

        if ($pages->isEmpty()) {
            $this->info('Nessuna wiki page da processare.');
            return self::SUCCESS;
        }

        $this->info("Processo {$pages->count()} wiki page (sync=" . ($sync ? 'sì' : 'no') . ", force=" . ($force ? 'sì' : 'no') . ")");

        $bar = $this->output->createProgressBar($pages->count());
        $bar->start();

        foreach ($pages as $page) {
            if ($sync) {
                (new FetchWikiImage($page, $force))->handle();
                $status = $page->fresh()->image_status;
                $bar->advance();
                $this->newLine();
                $this->line("  [{$page->id}] {$page->title} → {$status}");
            } else {
                FetchWikiImage::dispatch($page, $force);
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        if ($sync) {
            $this->info('Completato (sync).');
        } else {
            $this->info('Job accodati. Verifica con: php artisan queue:monitor default --max=100');
        }

        return self::SUCCESS;
    }
}
