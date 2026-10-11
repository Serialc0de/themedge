<?php

namespace App\Filament\Resources\EditorialRoleResource\Pages;

use App\Filament\Resources\EditorialRoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEditorialRole extends EditRecord
{
    protected static string $resource = EditorialRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Elimina'),
        ];
    }
}
