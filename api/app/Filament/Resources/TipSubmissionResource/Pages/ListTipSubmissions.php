<?php

namespace App\Filament\Resources\TipSubmissionResource\Pages;

use App\Filament\Resources\TipSubmissionResource;
use Filament\Resources\Pages\ListRecords;

class ListTipSubmissions extends ListRecords
{
    protected static string $resource = TipSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
