<?php

namespace App\Filament\Resources\HighlightPolicyResource\Pages;

use App\Filament\Resources\HighlightPolicyResource;
use App\Models\HighlightPolicy;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHighlightPolicy extends EditRecord
{
    protected static string $resource = HighlightPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => ! $this->record->is_system),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['settings']) || !is_array($data['settings'])) {
            $data['settings'] = HighlightPolicy::defaultSettings();
        } else {
            $data['settings'] = array_merge(HighlightPolicy::defaultSettings(), $data['settings']);
        }
        return $data;
    }
}
