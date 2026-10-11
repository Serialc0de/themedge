<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PushNotificationLogResource\Pages;
use App\Models\PushNotificationLog;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PushNotificationLogResource extends Resource
{
    protected static ?string $model = PushNotificationLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Storico Push';
    protected static ?string $navigationGroup = 'Sistema';
    protected static ?int $navigationSort = 51;
    protected static ?string $modelLabel = 'Invio';
    protected static ?string $pluralModelLabel = 'Storico Invii Push';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'breaking' => '🚨 Breaking',
                        'live_blog' => '🔴 Live Blog',
                        'article' => '📰 Articolo',
                        default => '📢 Custom',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'breaking' => 'danger',
                        'live_blog' => 'warning',
                        'article' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('title')
                    ->label('Titolo')
                    ->searchable()
                    ->limit(40)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('body')
                    ->label('Messaggio')
                    ->limit(50)
                    ->wrap(),

                Tables\Columns\TextColumn::make('sent_count')
                    ->label('Inviate')
                    ->badge()
                    ->color('success')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('failed_count')
                    ->label('Fallite')
                    ->badge()
                    ->color('danger')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('success_rate')
                    ->label('Successo')
                    ->suffix('%')
                    ->badge()
                    ->color(fn (PushNotificationLog $record) => $record->success_rate > 90 ? 'success' : ($record->success_rate > 70 ? 'warning' : 'danger'))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('sentBy.name')
                    ->label('Da')
                    ->placeholder('Sistema')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Inviata')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'breaking' => '🚨 Breaking',
                        'live_blog' => '🔴 Live Blog',
                        'article' => '📰 Articolo',
                        'custom' => '📢 Custom',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->label('Dettagli')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (PushNotificationLog $record) => $record->title)
                    ->modalContent(fn (PushNotificationLog $record) => view('filament.push-details', ['log' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Chiudi'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPushNotificationLogs::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole(['super_admin']) ?? false;
    }
}
