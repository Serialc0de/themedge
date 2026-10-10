<?php

namespace App\Filament\Widgets;

use App\Models\Article;
use Carbon\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ArticlesChart extends ApexChartWidget
{
    protected static ?string $chartId = 'articlesChart';
    protected static ?string $heading = 'Andamento pubblicazioni — ultimi 30 giorni';
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $pollingInterval = '60s';

    protected function getOptions(): array
    {
        $days = collect();
        for ($i = 29; $i >= 0; $i--) {
            $days->push(now()->subDays($i)->format('Y-m-d'));
        }

        $published = Article::selectRaw('DATE(COALESCE(published_at, created_at)) as date, COUNT(*) as count')
            ->whereIn('status', ['published'])
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->pluck('count', 'date');

        $drafts = Article::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('status', 'draft')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->pluck('count', 'date');

        $publishedData = $days->map(fn ($d) => $published[$d] ?? 0)->toArray();
        $draftsData = $days->map(fn ($d) => $drafts[$d] ?? 0)->toArray();
        $labels = $days->map(fn ($d) => Carbon::parse($d)->format('d/m'))->toArray();

        return [
            'chart' => [
                'type' => 'area',
                'height' => 300,
                'toolbar' => ['show' => false],
                'fontFamily' => 'inherit',
            ],
            'series' => [
                ['name' => 'Pubblicati', 'data' => $publishedData],
                ['name' => 'Bozze', 'data' => $draftsData],
            ],
            'xaxis' => [
                'categories' => $labels,
                'labels' => ['style' => ['fontSize' => '11px']],
            ],
            'yaxis' => [
                'labels' => ['style' => ['fontSize' => '11px']],
            ],
            'colors' => ['#10b981', '#9ca3af'],
            'stroke' => ['curve' => 'smooth', 'width' => 2],
            'fill' => [
                'type' => 'gradient',
                'gradient' => [
                    'shadeIntensity' => 0.3,
                    'opacityFrom' => 0.6,
                    'opacityTo' => 0.1,
                ],
            ],
            'grid' => [
                'borderColor' => '#e5e7eb',
                'strokeDashArray' => 4,
            ],
            'legend' => [
                'position' => 'top',
                'horizontalAlign' => 'right',
            ],
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'editor']) ?? false;
    }
}
