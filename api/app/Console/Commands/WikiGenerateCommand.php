<?php

namespace App\Console\Commands;

use App\Models\Entity;
use App\Services\Wiki\WikiGenerator;
use Illuminate\Console\Command;

class WikiGenerateCommand extends Command
{
    protected $signature = 'wiki:generate
                            {--entity= : ID di una singola entità}
                            {--type= : Filtra per tipo (person, organization, location, event, concept)}
                            {--force : Rigenera anche se esiste}
                            {--limit=10 : Numero massimo di entità da processare}
                            {--sync : Esegui in modo sincrono (no queue)}';

    protected $description = 'Genera pagine wiki da entità';

    public function handle(WikiGenerator $generator): int
    {
        $query = Entity::query()->where('is_published', true);

        if ($entityId = $this->option('entity')) {
            $query->where('id', $entityId);
        }

        if ($type = $this->option('type')) {
            $query->where('type', $type);
        }

        // Escludi entità che hanno già una wiki (se non force)
        if (!$this->option('force')) {
            $query->whereDoesntHave('wikiPages');
        }

        $entities = $query->limit((int) $this->option('limit'))->get();

        if ($entities->isEmpty()) {
            $this->info('Nessuna entità da processare.');
            return self::SUCCESS;
        }

        $this->info("🎯 Processo {$entities->count()} entità...");
        $this->newLine();

        $bar = $this->output->createProgressBar($entities->count());
        $bar->start();

        $success = 0;
        $failed = 0;

        foreach ($entities as $entity) {
            try {
                if ($this->option('sync')) {
                    $page = $generator->generateFromEntity($entity, (bool) $this->option('force'));
                    if ($page) $success++;
                    else $failed++;
                } else {
                    \App\Jobs\GenerateWikiPageFromEntity::dispatch(
                        $entity,
                        (bool) $this->option('force')
                    );
                    $success++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->newLine();
                $this->error("❌ {$entity->name}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($this->option('sync')) {
            $this->info("✅ Completato: {$success} successi, {$failed} falliti");
        } else {
            $this->info("✅ Job accodati: {$success}, falliti: {$failed}");
            $this->line("Il queue worker li processerà in background.");
        }

        return self::SUCCESS;
    }
}
