<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PushSubscriptionResource\Pages;
use App\Models\PushSubscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PushSubscriptionResource extends Resource
{
    protected static ?string $model = PushSubscription::class;
    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static ?string $navigationLabel = 'Notifiche Push';
    protected static ?string $navigationGroup = 'Sistema';
    protected static ?int $navigationSort = 50;
    protected static ?string $modelLabel = 'Subscription';
    protected static ?string $pluralModelLabel = 'Iscritti Push';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Dettagli subscription')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('endpoint')
                        ->label('Endpoint')
                        ->disabled()
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('user.name')
                        ->label('Utente')
                        ->disabled(),

                    Forms\Components\TextInput::make('ip_address')
                        ->label('IP')
                        ->disabled(),

                    Forms\Components\Textarea::make('user_agent')
                        ->label('User Agent')
                        ->disabled()
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Attivo')
                        ->disabled(),

                    Forms\Components\DateTimePicker::make('last_used_at')
                        ->label('Ultimo utilizzo')
                        ->disabled(),

                    Forms\Components\DateTimePicker::make('created_at')
                        ->label('Iscritto il')
                        ->disabled(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Attivo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utente')
                    ->placeholder('Anonimo')
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('user_agent')
                    ->label('Browser')
                    ->getStateUsing(function (PushSubscription $record) {
                        $ua = $record->user_agent ?? '';
                        if (str_contains($ua, 'Chrome')) return 'Chrome';
                        if (str_contains($ua, 'Firefox')) return 'Firefox';
                        if (str_contains($ua, 'Safari')) return 'Safari';
                        if (str_contains($ua, 'Edge')) return 'Edge';
                        return 'Altro';
                    })
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('Ultimo utilizzo')
                    ->since()
                    ->placeholder('Mai')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Iscritto')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Stato')
                    ->placeholder('Tutti')
                    ->trueLabel('Attive')
                    ->falseLabel('Disattivate'),
            ])
            ->actions([
                Tables\Actions\Action::make('deactivate')
                    ->label('Disattiva')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (PushSubscription $record) => $record->is_active)
                    ->action(function (PushSubscription $record) {
                        $record->deactivate();
                        \Filament\Notifications\Notification::make()
                            ->title('Subscription disattivata')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()->label('Elimina'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Elimina'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPushSubscriptions::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole(['super_admin']) ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::active()->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
