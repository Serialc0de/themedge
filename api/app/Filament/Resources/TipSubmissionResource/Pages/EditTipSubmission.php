<?php

namespace App\Filament\Resources\TipSubmissionResource\Pages;

use App\Filament\Resources\TipSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTipSubmission extends EditRecord
{
    protected static string $resource = TipSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Elimina'),
        ];
    }
}
