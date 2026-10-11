<?php

namespace App\Filament\Resources\HighlightPolicyResource\Pages;

use App\Filament\Resources\HighlightPolicyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHighlightPolicies extends ListRecords
{
    protected static string $resource = HighlightPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nuova policy'),
        ];
    }
}
