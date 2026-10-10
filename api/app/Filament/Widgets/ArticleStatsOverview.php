<?php

namespace App\Filament\Widgets;

use App\Models\Article;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ArticleStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '30s';
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $published = Article::where('status', 'published')->count();
        $draft = Article::where('status', 'draft')->count();
        $review = Article::where('status', 'review')->count();
        $approved = Article::where('status', 'approved')->count();
        $scheduled = Article::where('status', 'scheduled')->count();
        $total = Article::count();
        $views = Article::sum('views');
        $users = User::where('is_active', true)->count();

        $lastWeekPublished = Article::where('status', 'published')
            ->where('published_at', '>=', now()->subDays(7))
            ->count();

        return [
            Stat::make('Articoli totali', $total)
                ->description("{$published} pubblicati · {$draft} bozze")
                ->descriptionIcon('heroicon-m-newspaper')
                ->color('primary')
                ->chart(
                    Article::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                        ->where('created_at', '>=', now()->subDays(7))
                        ->groupBy('date')
                        ->orderBy('date')
                        ->pluck('count')
                        ->toArray()
                ),

            Stat::make('Visualizzazioni', number_format($views))
                ->description('Totale su tutti gli articoli')
                ->descriptionIcon('heroicon-m-eye')
                ->color('success'),

            Stat::make('In review', $review)
                ->description($review > 0 ? 'Richiedono attenzione' : 'Nessuna pending')
                ->descriptionIcon($review > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($review > 0 ? 'warning' : 'gray'),

            Stat::make('Autori attivi', $users)
                ->description("{$lastWeekPublished} pubbl. ultimi 7gg")
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'editor']) ?? false;
    }
}
