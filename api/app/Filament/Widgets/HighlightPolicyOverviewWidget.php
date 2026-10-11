<?php

namespace App\Filament\Widgets;

use App\Models\Article;
use App\Models\HighlightPolicy;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HighlightPolicyOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 20;
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $totalPolicies = HighlightPolicy::count();
        $activePolicies = HighlightPolicy::get()->filter(fn ($p) => $p->enabled)->count();

        $articlesWithPolicy = Article::whereNotNull('highlight_policy_id')->count();
        $articlesWithOverride = Article::whereNotNull('highlight_settings')->count();
        $articlesTemporarilyDisabled = Article::where('highlight_until', '>', now())->count();

        return [
            Stat::make('Policy attive', $activePolicies . ' / ' . $totalPolicies)
                ->description('Policies di sistema e custom')
                ->descriptionIcon('heroicon-m-adjustments-horizontal')
                ->color('info'),

            Stat::make('Articoli con policy', $articlesWithPolicy)
                ->description('Articoli con policy esplicita')
                ->descriptionIcon('heroicon-m-newspaper')
                ->color('success'),

            Stat::make('Articoli con override', $articlesWithOverride)
                ->description('Override granulari JSON')
                ->descriptionIcon('heroicon-m-code-bracket')
                ->color('warning'),

            Stat::make('Temporaneamente off', $articlesTemporarilyDisabled)
                ->description('Disattivati fino a una data')
                ->descriptionIcon('heroicon-m-clock')
                ->color($articlesTemporarilyDisabled > 0 ? 'danger' : 'gray'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'editor']) ?? false;
    }
}
