<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\ArticleDailyStat;
use App\Models\Comment;
use Illuminate\Console\Command;

class GenerateDailyStats extends Command
{
    protected $signature = 'analytics:snapshot {--date= : Data (Y-m-d), default oggi}';
    protected $description = 'Genera snapshot statistiche giornaliere';

    public function handle(): int
    {
        $date = $this->option('date')
            ? \Carbon\Carbon::parse($this->option('date'))
            : now();

        $this->info("Generando snapshot per il {$date->format('d/m/Y')}...");

        $articles = Article::where('status', 'published')->get();

        $bar = $this->output->createProgressBar($articles->count());
        $bar->start();

        foreach ($articles as $article) {
            // Commenti di quel giorno
            $commentsToday = Comment::where('article_id', $article->id)
                ->whereDate('created_at', $date)
                ->where('status', 'approved')
                ->count();

            // Views cumulative attuali (delta dallo snapshot precedente)
            $lastStat = ArticleDailyStat::where('article_id', $article->id)
                ->where('date', '<', $date->format('Y-m-d'))
                ->orderByDesc('date')
                ->first();

            $viewsToday = $article->views - ($lastStat?->views ?? 0);
            if ($viewsToday < 0) {
                $viewsToday = $article->views; // reset contatore
            }

            ArticleDailyStat::updateOrCreate(
                [
                    'date' => $date->format('Y-m-d'),
                    'article_id' => $article->id,
                ],
                [
                    'views' => $article->views,
                    'comments' => $commentsToday,
                ]
            );

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Snapshot completato per {$articles->count()} articoli.");

        return self::SUCCESS;
    }
}
