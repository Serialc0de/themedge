<?php

namespace App\Filament\Widgets;

use App\Models\AskFeedback;
use App\Models\AskQuery;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AskStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalQueries = AskQuery::count();
        $last24h = AskQuery::where('created_at', '>=', now()->subDay())->count();
        $totalFeedback = AskFeedback::count();
        $positive = AskFeedback::where('vote', 1)->count();
        $negative = AskFeedback::where('vote', -1)->count();
        $positiveRate = $totalFeedback > 0 ? round(($positive / $totalFeedback) * 100) : 0;

        $avgTime = AskQuery::whereNotNull('total_ms')->avg('total_ms');
        $avgTimeSec = $avgTime ? round($avgTime / 1000, 1) : 0;

        return [
            Stat::make('Query totali', $totalQueries)
                ->description("{$last24h} nelle ultime 24h")
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('primary'),

            Stat::make('Feedback positivi', $positiveRate . '%')
                ->description("👍 {$positive} | 👎 {$negative}")
                ->descriptionIcon('heroicon-m-hand-thumb-up')
                ->color($positiveRate >= 70 ? 'success' : ($positiveRate >= 50 ? 'warning' : 'danger')),

            Stat::make('Tempo medio risposta', $avgTimeSec . 's')
                ->description('Elaborazione completa')
                ->descriptionIcon('heroicon-m-clock')
                ->color($avgTimeSec < 60 ? 'success' : ($avgTimeSec < 120 ? 'warning' : 'danger')),
        ];
    }
}
