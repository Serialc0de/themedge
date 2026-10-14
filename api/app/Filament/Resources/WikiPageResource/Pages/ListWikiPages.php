<?php

namespace App\Filament\Resources\WikiPageResource\Pages;

use App\Filament\Resources\WikiPageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWikiPages extends ListRecords
{
    protected static string $resource = WikiPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_batch')
                ->label('Genera da entità')
                ->icon('heroicon-o-sparkles')
                ->color('warning')
                ->form([
                    \Filament\Forms\Components\Select::make('type')
                        ->label('Tipo entità')
                        ->options([
                            'person' => '👤 Persona',
                            'organization' => '🏛️ Organizzazione',
                            'location' => '🌍 Luogo',
                            'event' => '📅 Evento',
                            'concept' => '💡 Concetto',
                        ])
                        ->placeholder('Tutti i tipi'),

                    \Filament\Forms\Components\TextInput::make('limit')
                        ->label('Numero massimo')
                        ->numeric()
                        ->default(10)
                        ->minValue(1)
                        ->maxValue(100),
                ])
                ->action(function (array $data) {
                    $args = ['--limit' => $data['limit'] ?? 10];
                    if (!empty($data['type'])) {
                        $args['--type'] = $data['type'];
                    }

                    \Illuminate\Support\Facades\Artisan::call('wiki:generate', $args);

                    \Filament\Notifications\Notification::make()
                        ->title('Generazione avviata')
                        ->body($data['limit'] . ' job accodati. Verranno processati in background.')
                        ->success()
                        ->send();
                }),

            Actions\CreateAction::make()
                ->label('Nuova pagina'),
        ];
    }
}
