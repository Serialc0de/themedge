<?php

namespace App\Filament\Widgets;

use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EditorialKpiWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $last7 = now()->subDays(7);
        $last30 = now()->subDays(30);

        $published7d = Article::where('status', 'published')->where('published_at', '>=', $last7)->count();
        $published30d = Article::where('status', 'published')->where('published_at', '>=', $last30)->count();
        $totalViews = Article::sum('views');
        $pendingComments = Comment::where('status', 'pending')->count();
        $approvedComments = Comment::where('status', 'approved')->count();
        $activeAuthors = User::whereHas('articles')->count();

        return [
            Stat::make('Pubblicati', $published30d)
                ->description("{$published7d} ultimi 7gg · {$published30d} ultimi 30gg")
                ->descriptionIcon('heroicon-m-newspaper')
                ->color('primary')
                ->chart(
                    Article::selectRaw('DATE(published_at) as date, COUNT(*) as count')
                        ->where('status', 'published')
                        ->where('published_at', '>=', now()->subDays(7))
                        ->groupBy('date')
                        ->orderBy('date')
                        ->pluck('count')
                        ->toArray()
                ),

            Stat::make('Views totali', number_format($totalViews))
                ->description('Su tutti gli articoli pubblicati')
                ->descriptionIcon('heroicon-m-eye')
                ->color('success'),

            Stat::make('Commenti', $approvedComments)
                ->description($pendingComments > 0 ? "{$pendingComments} da moderare" : 'Tutti moderati')
                ->descriptionIcon($pendingComments > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($pendingComments > 0 ? 'warning' : 'gray'),

            Stat::make('Autori attivi', $activeAuthors)
                ->description('Con almeno 1 articolo')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'editor']) ?? false;
    }
}
