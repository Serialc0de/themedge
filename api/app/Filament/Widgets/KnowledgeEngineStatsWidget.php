<?php

namespace App\Filament\Widgets;

use App\Models\Entity;
use App\Models\WikiPage;
use App\Models\WikipediaChunk;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class KnowledgeEngineStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '60s';
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        // ============================================================
        // WIKI
        // ============================================================
        $wikiTotal = WikiPage::count();
        $wikiPublished = WikiPage::where('status', 'published')->count();
        $wikiDraft = WikiPage::where('status', 'draft')->count();
        $wikiWithImage = WikiPage::where('image_status', 'ready')->count();

        // ============================================================
        // WIKIPEDIA
        // ============================================================
        $entityTotal = Entity::count();
        $entityWithWikipedia = Entity::where('wikipedia_status', 'ready')->count();
        $entityNoPage = Entity::where('wikipedia_status', 'no_page')->count();
        $chunkTotal = WikipediaChunk::count();

        // ============================================================
        // JOB
        // ============================================================
        $jobsPending = DB::table('jobs')->count();
        $jobsFailed = DB::table('failed_jobs')->count();

        // ============================================================
        // ULTIMA GENERAZIONE
        // ============================================================
        $lastWiki = WikiPage::whereNotNull('ai_generated_at')
            ->orderByDesc('ai_generated_at')
            ->first();

        $lastGenerated = $lastWiki?->ai_generated_at;
        $lastModel = $lastWiki?->ai_model ?? config('services.ollama.model');

        // ============================================================
        // WIKI STALE (con nuovi articoli dopo ai_generated_at)
        // ============================================================
        $staleCount = 0;
        try {
            $staleCount = WikiPage::query()
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
                ->count();
        } catch (\Throwable $e) {
            // ignora
        }

        return [
            // === WIKI ===
            Stat::make('Wiki totali', $wikiTotal)
                ->description("{$wikiPublished} pubblicate · {$wikiDraft} bozze")
                ->descriptionIcon('heroicon-m-book-open')
                ->color('primary'),

            Stat::make('Wiki con immagine', "{$wikiWithImage}/{$wikiTotal}")
                ->description($wikiTotal > 0 ? round(($wikiWithImage / $wikiTotal) * 100) . '% del totale' : '—')
                ->descriptionIcon('heroicon-m-photo')
                ->color($wikiWithImage > 0 ? 'success' : 'gray'),

            // === WIKIPEDIA ===
            Stat::make('Entity con Wikipedia', "{$entityWithWikipedia}/{$entityTotal}")
                ->description($entityNoPage > 0 ? "{$entityNoPage} senza pagina" : 'Tutte con pagina')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('info'),

            Stat::make('Chunk Wikipedia', number_format($chunkTotal))
                ->description('Embedding indicizzati per RAG')
                ->descriptionIcon('heroicon-m-circle-stack')
                ->color('info'),

            // === JOB ===
            Stat::make('Job in coda', $jobsPending)
                ->description($jobsPending > 10 ? 'Coda lunga — attenzione' : 'Coda regolare')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($jobsPending > 10 ? 'warning' : 'success'),

            Stat::make('Job falliti', $jobsFailed)
                ->description($jobsFailed > 0 ? 'Verifica failed_jobs' : 'Nessun errore')
                ->descriptionIcon($jobsFailed > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($jobsFailed > 0 ? 'danger' : 'success'),

            // === STALE ===
            Stat::make('Wiki stale', $staleCount)
                ->description($staleCount > 0 ? 'Nuovi articoli — cron 3:00' : 'Tutte aggiornate')
                ->descriptionIcon($staleCount > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-badge')
                ->color($staleCount > 0 ? 'warning' : 'success'),

            // === ULTIMA GENERAZIONE ===
            Stat::make('Ultima generazione', $lastGenerated ? $lastGenerated->diffForHumans() : 'Mai')
                ->description("Modello: {$lastModel}")
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color('gray'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'editor']) ?? false;
    }
}
