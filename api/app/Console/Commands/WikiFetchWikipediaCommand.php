<?php

namespace App\Console\Commands;

use App\Jobs\FetchWikipediaArticle;
use App\Models\Entity;
use Illuminate\Console\Command;

class WikiFetchWikipediaCommand extends Command
{
    protected $signature = 'wiki:fetch-wikipedia
                            {--limit=50 : Numero massimo di entity da processare}
                            {--sync : Esegui in modo sincrono (utile per debug)}
                            {--force : Riscarica anche se già presente}
                            {--id= : Processa solo una specifica entity per ID}
                            {--status= : Filtra per wikipedia_status}';

    protected $description = 'Scarica testo Wikipedia + chunk + embedding per le entità';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $sync = (bool) $this->option('sync');
        $force = (bool) $this->option('force');
        $id = $this->option('id');
        $statusFilter = $this->option('status');

        $query = Entity::query();

        if ($id) {
            $query->where('id', (int) $id);
        } elseif ($statusFilter !== null) {
            if ($statusFilter === '') {
                $query->where(function ($q) {
                    $q->whereNull('wikipedia_status')->orWhere('wikipedia_status', '');
                });
            } else {
                $query->where('wikipedia_status', $statusFilter);
            }
        } else {
            // Default: mai processate o fallite
            $query->where(function ($q) {
                $q->whereNull('wikipedia_status')
                  ->orWhere('wikipedia_status', '')
                  ->orWhereIn('wikipedia_status', ['failed', 'no_content']);
            });
        }

        if (!$force && !$id) {
            $query->where(function ($q) {
                $q->whereNull('wikipedia_text')->orWhere('wikipedia_text', '');
            });
        }

        $entities = $query->orderByDesc('id')->limit($limit)->get();

        if ($entities->isEmpty()) {
            $this->info('Nessuna entity da processare.');
            return self::SUCCESS;
        }

        $this->info("Processo {$entities->count()} entity (sync=" . ($sync ? 'sì' : 'no') . ", force=" . ($force ? 'sì' : 'no') . ")");

        $bar = $this->output->createProgressBar($entities->count());
        $bar->start();

        foreach ($entities as $entity) {
            if ($sync) {
                app(\App\Services\Wiki\WikipediaFetcherService::class)->fetchForEntity($entity, $force);
                $status = $entity->fresh()->wikipedia_status;
                $chunks = $entity->fresh()->wikipediaChunks()->count();
                $bar->advance();
                $this->newLine();
                $this->line("  [{$entity->id}] {$entity->name} → {$status} ({$chunks} chunks)");
            } else {
                FetchWikipediaArticle::dispatch($entity, $force);
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
