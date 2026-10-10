<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrokenLinkResource\Pages;
use App\Models\BrokenLink;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class BrokenLinkResource extends Resource
{
    protected static ?string $model = BrokenLink::class;
    protected static ?string $navigationIcon = 'heroicon-o-link-slash';
    protected static ?string $navigationLabel = 'Link rotti';
    protected static ?string $navigationGroup = 'SEO';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Link rotto';
    protected static ?string $pluralModelLabel = 'Link rotti';

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
                Tables\Columns\TextColumn::make('status_code')
                    ->label('Codice')
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state === 0 => 'danger',
                        $state >= 500 => 'danger',
                        $state >= 400 => 'warning',
                        default => 'gray',
                    })
                    ->placeholder('Errore'),

                Tables\Columns\TextColumn::make('url')
                    ->label('URL')
                    ->limit(60)
                    ->copyable()
                    ->wrap()
                    ->searchable()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('article_title')
                    ->label('Articolo')
                    ->limit(30)
                    ->placeholder('—')
                    ->searchable(),

                Tables\Columns\TextColumn::make('error_message')
                    ->label('Errore')
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('last_checked_at')
                    ->label('Ultimo check')
                    ->since()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('check_count')
                    ->label('Volte')
                    ->badge()
                    ->color('gray')
                    ->alignCenter()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_code')
                    ->label('Codice HTTP')
                    ->options([
                        '0' => 'Errore di rete',
                        '404' => '404 Not Found',
                        '403' => '403 Forbidden',
                        '500' => '500 Server Error',
                        '502' => '502 Bad Gateway',
                    ]),

                Tables\Filters\TernaryFilter::make('is_resolved')
                    ->label('Stato')
                    ->placeholder('Tutti')
                    ->trueLabel('Risolti')
                    ->falseLabel('Non risolti')
                    ->default(false),
            ])
            ->actions([
                Tables\Actions\Action::make('recheck')
                    ->label('Ricontrolla')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function (BrokenLink $record) {
                        try {
                            $response = \Illuminate\Support\Facades\Http::timeout(10)->get($record->url);
                            $status = $response->status();

                            if ($status < 400) {
                                $record->update([
                                    'is_resolved' => true,
                                    'status_code' => $status,
                                    'last_checked_at' => now(),
                                ]);

                                \Filament\Notifications\Notification::make()
                                    ->title('Link ora funzionante')
                                    ->success()
                                    ->send();
                            } else {
                                $record->update([
                                    'status_code' => $status,
                                    'last_checked_at' => now(),
                                    'check_count' => $record->check_count + 1,
                                ]);

                                \Filament\Notifications\Notification::make()
                                    ->title("Ancora rotto (HTTP {$status})")
                                    ->warning()
                                    ->send();
                            }
                        } catch (\Throwable $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Errore di rete')
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\DeleteAction::make()->label('Elimina'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Elimina selezionati'),
                ]),
            ])
            ->defaultSort('last_checked_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBrokenLinks::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole(['super_admin', 'editor']) ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('is_resolved', false)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
