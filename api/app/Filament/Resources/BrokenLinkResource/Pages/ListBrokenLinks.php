<?php

namespace App\Filament\Resources\BrokenLinkResource\Pages;

use App\Filament\Resources\BrokenLinkResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListBrokenLinks extends ListRecords
{
    protected static string $resource = BrokenLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('scan')
                ->label('Scansiona articoli')
                ->icon('heroicon-o-magnifying-glass')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Avviare la scansione?')
                ->modalDescription('La scansione può richiedere alcuni minuti.')
                ->action(function () {
                    Notification::make()
                        ->title('Scansione avviata')
                        ->body('Attendi qualche minuto e ricarica la pagina.')
                        ->info()
                        ->send();

                    \Illuminate\Support\Facades\Artisan::call('seo:scan-links', ['--limit' => 100]);
                }),
        ];
    }
}
