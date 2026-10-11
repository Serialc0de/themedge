<?php

namespace App\Filament\Resources\PushSubscriptionResource\Pages;

use App\Filament\Resources\PushSubscriptionResource;
use App\Services\PushNotificationService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListPushSubscriptions extends ListRecords
{
    protected static string $resource = PushSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('send_notification')
                ->label('Invia notifica')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->modalHeading('Invia notifica push')
                ->modalDescription('La notifica verrà inviata a tutti gli iscritti attivi.')
                ->modalSubmitActionLabel('Invia')
                ->form([
                    Forms\Components\TextInput::make('title')
                        ->label('Titolo')
                        ->required()
                        ->maxLength(100)
                        ->helperText('Massimo 100 caratteri. Es: "🚨 Ultim\'ora"'),

                    Forms\Components\Textarea::make('body')
                        ->label('Messaggio')
                        ->required()
                        ->rows(3)
                        ->maxLength(200)
                        ->helperText('Massimo 200 caratteri. Il testo della notifica.'),

                    Forms\Components\TextInput::make('url')
                        ->label('URL di destinazione')
                        ->url()
                        ->default('https://themedge.com')
                        ->helperText('Dove verrà portato l\'utente al click'),

                    Forms\Components\Select::make('type')
                        ->label('Tipo')
                        ->options([
                            'custom' => '📢 Custom',
                            'breaking' => '🚨 Breaking News',
                            'live_blog' => '🔴 Live Blog',
                            'article' => '📰 Nuovo articolo',
                        ])
                        ->default('custom')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $service = new PushNotificationService();
                    $result = $service->sendToAll(
                        $data['title'],
                        $data['body'],
                        $data['url'],
                        null,
                        $data['type'],
                        auth()->id()
                    );

                    Notification::make()
                        ->title('Notifica inviata')
                        ->body("✅ {$result['success']} inviate · ❌ {$result['failed']} fallite · Totale {$result['total']}")
                        ->success()
                        ->send();
                }),
        ];
    }
}
