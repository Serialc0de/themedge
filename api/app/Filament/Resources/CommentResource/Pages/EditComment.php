<?php

namespace App\Filament\Resources\CommentResource\Pages;

use App\Filament\Resources\CommentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditComment extends EditRecord
{
    protected static string $resource = CommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_article')
                ->label('Vedi articolo')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('info')
                ->url(fn () => $this->record->article
                    ? config('app.public_site_url', 'https://themedge.com') . '/articolo/' . $this->record->article->slug . '#comment-' . $this->record->id
                    : null
                )
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->article !== null),

            Actions\DeleteAction::make()->label('Elimina'),
        ];
    }
}
