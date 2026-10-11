<?php

namespace App\Filament\Widgets;

use App\Models\ReaderHighlight;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HighlightStatsOverviewWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $total = ReaderHighlight::count();
        $public = ReaderHighlight::where('is_public', true)->count();
        $today = ReaderHighlight::whereDate('created_at', today())->count();
        $week = ReaderHighlight::where('created_at', '>=', now()->subDays(7))->count();

        return [
            Stat::make('Totale highlight', $total)
                ->description($public . ' pubblici')
                ->descriptionIcon('heroicon-m-eye')
                ->color('info'),

            Stat::make('Oggi', $today)
                ->description($week . ' ultimi 7 giorni')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),

            Stat::make('Con reazioni', ReaderHighlight::where('reactions_count', '>', 0)->count())
                ->description('Totali: ' . ReaderHighlight::sum('reactions_count'))
                ->descriptionIcon('heroicon-m-hand-thumb-up')
                ->color('warning'),

            Stat::make('Con note', ReaderHighlight::whereNotNull('note')->where('note', '!=', '')->count())
                ->description('Highlight annotati')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color('primary'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'editor']) ?? false;
    }
}
