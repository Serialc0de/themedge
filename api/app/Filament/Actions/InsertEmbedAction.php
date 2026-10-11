<?php

namespace App\Filament\Actions;

use App\Services\OEmbedResolver;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

class InsertEmbedAction
{
    public static function make(): Action
    {
        return Action::make('insert_embed')
            ->label('Inserisci embed')
            ->icon('heroicon-o-code-bracket-square')
            ->color('info')
            ->form([
                TextInput::make('url')
                    ->label('URL embed')
                    ->placeholder('https://www.youtube.com/watch?v=...')
                    ->required()
                    ->url()
                    ->maxLength(2048)
                    ->helperText('Supportati: YouTube, Vimeo, Spotify, Twitter/X, Instagram, TikTok'),
            ])
            ->action(function (array $data) {
                /** @var OEmbedResolver $resolver */
                $resolver = app(OEmbedResolver::class);
                $result = $resolver->resolve($data['url']);

                if (!$result) {
                    Notification::make()
                        ->title('URL non supportato')
                        ->body("Il provider non è riconosciuto o l'URL non è valido.")
                        ->danger()
                        ->send();
                    return;
                }

                // Salva l'HTML generato in sessione per recupero successivo
                session()->flash('last_embed_html', $result['html']);
                session()->flash('last_embed_provider', $result['name']);

                Notification::make()
                    ->title('Embed pronto: ' . $result['name'])
                    ->body("Apri il file /tmp/last_embed.html sul server per copiarlo, oppure usa il pulsante qui sotto.")
                    ->persistent()
                    ->actions([
                        NotificationAction::make('copy')
                            ->label('Copia HTML')
                            ->button()
                            ->extraAttributes([
                                'onclick' => "navigator.clipboard.writeText(" . json_encode($result['html']) . "); alert('HTML copiato negli appunti!');",
                            ]),
                    ])
                    ->success()
                    ->send();

                // Scrivi anche su file temporaneo per comodità
                @file_put_contents('/tmp/last_embed.html', $result['html']);

                logger()->info('InsertEmbedAction resolved', [
                    'url' => $data['url'],
                    'provider' => $result['provider'],
                    'user_id' => auth()->id(),
                ]);
            })
            ->modalHeading('Inserisci embed')
            ->modalDescription('Genera l\'HTML dell\'embed e incollalo nel RichEditor dell\'articolo.')
            ->modalSubmitActionLabel('Genera HTML')
            ->modalWidth('lg');
    }
}
