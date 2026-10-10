<?php

namespace App\Filament\Widgets;

use App\Models\MediaAsset;
use Filament\Widgets\Widget;

class RecentMediaWidget extends Widget
{
    protected static string $view = 'filament.widgets.recent-media';
    protected static ?int $sort = 11;
    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'media' => MediaAsset::query()
                ->with('media')
                ->latest()
                ->limit(12)
                ->get(),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'editor', 'author']) ?? false;
    }
}
