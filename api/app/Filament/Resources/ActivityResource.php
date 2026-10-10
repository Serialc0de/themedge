<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Sistema';
    protected static ?int $navigationSort = 100;
    protected static ?string $modelLabel = 'Attività';
    protected static ?string $pluralModelLabel = 'Audit Log';
    protected static ?string $navigationLabel = 'Audit Log';

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
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->since(),

                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Utente')
                    ->badge()
                    ->color('gray')
                    ->placeholder('Sistema')
                    ->searchable(),

                Tables\Columns\TextColumn::make('log_name')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'article' => 'success',
                        'user' => 'warning',
                        'category' => 'info',
                        'tag' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'article' => '📝 Articolo',
                        'user' => '👤 Utente',
                        'category' => '📁 Categoria',
                        'tag' => '🏷️ Tag',
                        default => $state ?? '—',
                    }),

                Tables\Columns\TextColumn::make('event')
                    ->label('Evento')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'restored' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'created' => '✅ Creato',
                        'updated' => '✏️ Modificato',
                        'deleted' => '🗑️ Eliminato',
                        'restored' => '♻️ Ripristinato',
                        default => $state ?? '—',
                    }),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descrizione')
                    ->limit(60)
                    ->wrap()
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('log_name')
                    ->label('Tipo')
                    ->options([
                        'article' => '📝 Articolo',
                        'user' => '👤 Utente',
                        'category' => '📁 Categoria',
                        'tag' => '🏷️ Tag',
                    ]),

                Tables\Filters\SelectFilter::make('event')
                    ->label('Evento')
                    ->options([
                        'created' => '✅ Creato',
                        'updated' => '✏️ Modificato',
                        'deleted' => '🗑️ Eliminato',
                        'restored' => '♻️ Ripristinato',
                    ]),

                Tables\Filters\Filter::make('today')
                    ->label('Solo oggi')
                    ->query(fn ($query) => $query->whereDate('created_at', today())),

                Tables\Filters\Filter::make('last_week')
                    ->label('Ultima settimana')
                    ->query(fn ($query) => $query->where('created_at', '>=', now()->subWeek())),
            ])
            ->actions([
                Tables\Actions\Action::make('view_changes')
                    ->label('Dettagli')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (Activity $record) => $record->description)
                    ->modalContent(fn (Activity $record) => view('filament.activities.changes', ['activity' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Chiudi'),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'editor']) ?? false;
    }
}
