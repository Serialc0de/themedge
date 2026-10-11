<?php

namespace App\Filament\Resources\CorrectionResource\Pages;

use App\Filament\Resources\CorrectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCorrections extends ListRecords
{
    protected static string $resource = CorrectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nuova correzione'),
        ];
    }
}
