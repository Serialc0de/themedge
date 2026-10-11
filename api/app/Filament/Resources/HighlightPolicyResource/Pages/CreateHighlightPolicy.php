<?php

namespace App\Filament\Resources\HighlightPolicyResource\Pages;

use App\Filament\Resources\HighlightPolicyResource;
use App\Models\HighlightPolicy;
use Filament\Resources\Pages\CreateRecord;

class CreateHighlightPolicy extends CreateRecord
{
    protected static string $resource = HighlightPolicyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Se settings è vuoto, usa i default
        if (empty($data['settings']) || !is_array($data['settings'])) {
            $data['settings'] = HighlightPolicy::defaultSettings();
        } else {
            $data['settings'] = array_merge(HighlightPolicy::defaultSettings(), $data['settings']);
        }
        return $data;
    }
}
