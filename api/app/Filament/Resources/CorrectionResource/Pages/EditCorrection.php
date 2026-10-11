<?php

namespace App\Filament\Resources\CorrectionResource\Pages;

use App\Filament\Resources\CorrectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCorrection extends EditRecord
{
    protected static string $resource = CorrectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Elimina'),
        ];
    }
}
