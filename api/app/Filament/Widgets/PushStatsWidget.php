<?php

namespace App\Filament\Widgets;

use App\Models\PushNotificationLog;
use App\Models\PushSubscription;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PushStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $active = PushSubscription::active()->count();
        $total = PushSubscription::count();
        $sent7d = PushNotificationLog::where('created_at', '>=', now()->subDays(7))->sum('sent_count');
        $last = PushNotificationLog::latest()->first();

        return [
            Stat::make('Iscritti Push', $active)
                ->description("di {$total} totali")
                ->descriptionIcon('heroicon-m-bell')
                ->color('success')
                ->chart(
                    PushSubscription::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                        ->where('created_at', '>=', now()->subDays(7))
                        ->groupBy('date')
                        ->orderBy('date')
                        ->pluck('count')
                        ->toArray()
                ),

            Stat::make('Notifiche inviate', $sent7d)
                ->description('Ultimi 7 giorni')
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color('info'),

            Stat::make('Ultimo invio', $last ? $last->created_at->diffForHumans() : 'Mai')
                ->description($last ? "{$last->sent_count} inviate · {$last->failed_count} fallite" : 'Nessun invio')
                ->descriptionIcon('heroicon-m-clock')
                ->color('gray'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasRole(['super_admin']) ?? false;
    }
}
