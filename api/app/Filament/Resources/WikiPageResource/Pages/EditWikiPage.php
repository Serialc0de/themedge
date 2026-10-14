<?php

namespace App\Filament\Resources\WikiPageResource\Pages;

use App\Filament\Resources\WikiPageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWikiPage extends EditRecord
{
    protected static string $resource = WikiPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_frontend')
                ->label('Vedi sito')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn () => 'https://themedge.com/wiki/' . $this->record->slug)
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->status === 'published'),

            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();
        return $data;
    }
}
