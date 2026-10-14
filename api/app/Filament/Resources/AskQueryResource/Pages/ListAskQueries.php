<?php

namespace App\Filament\Resources\AskQueryResource\Pages;

use App\Filament\Resources\AskQueryResource;
use App\Models\AskQuery;
use App\Models\AskFeedback;
use Filament\Resources\Pages\ListRecords;

class ListAskQueries extends ListRecords
{
    protected static string $resource = AskQueryResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\AskStatsOverview::class,
        ];
    }

    public function getTitle(): string
    {
        return 'Chiedi a Themedge — Analytics';
    }
}
