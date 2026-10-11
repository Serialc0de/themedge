<?php

namespace App\Filament\Resources\LiveBlogResource\Pages;

use App\Filament\Resources\LiveBlogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLiveBlog extends EditRecord
{
    protected static string $resource = LiveBlogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_public')
                ->label('Apri pagina pubblica')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('info')
                ->url(fn () => config('app.public_site_url', 'https://themedge.com') . '/diretta/' . $this->record->slug)
                ->openUrlInNewTab()
                ->visible(fn () => in_array($this->record->status, ['live', 'ended'])),

            Actions\DeleteAction::make()->label('Elimina'),
        ];
    }
}
