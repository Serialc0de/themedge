<?php

namespace App\Filament\Resources\EditorialRoleResource\Pages;

use App\Filament\Resources\EditorialRoleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEditorialRoles extends ListRecords
{
    protected static string $resource = EditorialRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nuovo ruolo'),
        ];
    }
}
