<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview')
                ->label('Anteprima frontend')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('info')
                ->url(fn () => config('app.public_site_url', 'https://themedge.com') . '/articolo/' . $this->record->slug)
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->status === 'published'),

            Actions\DeleteAction::make()->label('Elimina'),
        ];
    }
}
