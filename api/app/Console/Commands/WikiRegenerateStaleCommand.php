<?php

namespace App\Console\Commands;

use App\Jobs\GenerateWikiPageFromEntity;
use App\Models\WikiPage;
use Illuminate\Console\Command;

class WikiRegenerateStaleCommand extends Command
{
    protected $signature = 'wiki:regenerate-stale
                            {--entity= : Forza la rigenerazione di una singola entity (bypassa il check stale)}
                            {--limit=10 : Numero massimo di wiki da rigenerare}
                            {--min-articles=1 : Numero minimo di articoli nuovi per considerare una wiki stale}
                            {--dry-run : Mostra quali wiki sarebbero rigenerate senza farlo}
                            {--sync : Esegui in modo sincrono (no queue)}';

    protected $description = 'Rigenera le wiki che hanno nuovi articoli pubblicati dopo l\'ultima generazione';

    public function handle(): int
    {
        // Caso 1: --entity=ID forza la rigenerazione di una entity specifica
        if ($entityId = $this->option('entity')) {
            return $this->handleSingleEntity((int) $entityId);
        }

        // Caso 2: trova tutte le wiki stale
        $stale = $this->findStaleWikis();

        if ($stale->isEmpty()) {
            $this->info('✅ Nessuna wiki stale trovata. Tutto aggiornato.');
            return self::SUCCESS;
        }

        $this->info("🔍 Trovate {$stale->count()} wiki stale.");
        $this->newLine();

        // Tabella riepilogativa
        $this->table(
            ['Wiki ID', 'Entity ID', 'Titolo', 'Articoli nuovi', 'Ultima generazione'],
            $stale->map(fn($w) => [
                $w->id,
                $w->entity_id,
                mb_substr($w->title, 0, 40),
                $w->new_articles_count,
                $w->ai_generated_at?->format('d/m/Y H:i') ?? '-',
            ])->toArray()
        );

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->warn('🔸 DRY-RUN: nessuna wiki è stata rigenerata.');
            return self::SUCCESS;
        }

        $this->newLine();
        if (!$this->confirm("Procedere con la rigenerazione di {$stale->count()} wiki?", true)) {
            $this->info('Operazione annullata.');
            return self::SUCCESS;
        }

        return $this->regenerate($stale);
    }

    /**
     * Forza la rigenerazione di una singola entity.
     */
    protected function handleSingleEntity(int $entityId): int
    {
        $wiki = WikiPage::where('entity_id', $entityId)->first();

        if (!$wiki) {
            $this->error("❌ Nessuna wiki trovata per entity_id={$entityId}");
            return self::FAILURE;
        }

        $this->info("🎯 Forzo rigenerazione: {$wiki->title} (wiki_id={$wiki->id}, entity_id={$entityId})");

        if ($this->option('dry-run')) {
            $this->warn('🔸 DRY-RUN: nessuna azione eseguita.');
            return self::SUCCESS;
        }

        try {
            if ($this->option('sync')) {
                $generator = app(\App\Services\Wiki\WikiGenerator::class);
                $page = $generator->generateFromEntity($wiki->entity, true);
                $this->info($page ? "✅ Wiki rigenerata (wiki_id={$page->id})" : '⚠️  Nessun contenuto generato.');
            } else {
                GenerateWikiPageFromEntity::dispatch($wiki->entity, true);
                $this->info('✅ Job accodato. Il queue worker lo processerà a breve.');
            }
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("❌ Errore: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Trova tutte le wiki stale (con nuovi articoli pubblicati dopo ai_generated_at).
     */
    protected function findStaleWikis()
    {
        $minArticles = max(1, (int) $this->option('min-articles'));

        return WikiPage::query()
            ->whereNotNull('entity_id')
            ->where('status', 'published')
            ->whereNotNull('ai_generated_at')
            ->whereHas('entity', function ($q) {
                $q->whereHas('articles', function ($q2) {
                    $q2->where('status', 'published')
                        ->where('published_at', '<=', now())
                        ->whereColumn('articles.published_at', '>', 'wiki_pages.ai_generated_at');
                });
            })
            ->withCount(['entity as new_articles_count' => function ($q) {
                // Nota: questo withCount non filtra per data, quindi lo correggo sotto
            }])
            ->limit((int) $this->option('limit'))
            ->get()
            ->filter(function ($wiki) use ($minArticles) {
                // Calcola il numero reale di articoli nuovi
                $count = $wiki->entity->articles()
                    ->where('status', 'published')
                    ->where('published_at', '<=', now())
                    ->where('published_at', '>', $wiki->ai_generated_at)
                    ->count();
                $wiki->new_articles_count = $count;
                return $count >= $minArticles;
            })
            ->values();
    }

    /**
     * Rigenera le wiki stale (sync o queue).
     */
    protected function regenerate($stale): int
    {
        $bar = $this->output->createProgressBar($stale->count());
        $bar->start();

        $success = 0;
        $failed = 0;

        foreach ($stale as $wiki) {
            try {
                if ($this->option('sync')) {
                    $generator = app(\App\Services\Wiki\WikiGenerator::class);
                    $page = $generator->generateFromEntity($wiki->entity, true);
                    if ($page) $success++;
                    else $failed++;
                } else {
                    GenerateWikiPageFromEntity::dispatch($wiki->entity, true);
                    $success++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->newLine();
                $this->error("❌ {$wiki->title}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($this->option('sync')) {
            $this->info("✅ Completato: {$success} successi, {$failed} falliti");
        } else {
            $this->info("✅ Job accodati: {$success}, falliti: {$failed}");
            $this->line('Il queue worker li processerà in background.');
        }

        return self::SUCCESS;
    }
}
