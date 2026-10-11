<?php

namespace App\Filament\Resources\ArticleResource\Widgets;

use App\Models\Article;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Spatie\Activitylog\Models\Activity;

class ArticleRevisionsWidget extends BaseWidget
{
    public ?Article $record = null;

    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = '📜 Cronologia revisioni';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Activity::query()
                    ->where('log_name', 'article')
                    ->where('subject_type', Article::class)
                    ->where('subject_id', $this->record?->id)
                    ->latest()
            )
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
                    ->placeholder('Sistema'),

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

                Tables\Columns\TextColumn::make('properties')
                    ->label('Modifiche')
                    ->getStateUsing(function (Activity $record) {
                        $props = $record->properties->toArray();
                        $attrs = $props['attributes'] ?? [];
                        $old = $props['old'] ?? [];

                        $changes = [];
                        foreach ($attrs as $key => $newVal) {
                            $oldVal = $old[$key] ?? '—';
                            $changes[] = $key;
                        }

                        if (empty($changes)) {
                            return '—';
                        }

                        return implode(', ', array_slice($changes, 0, 3)) . (count($changes) > 3 ? ' +' . (count($changes) - 3) : '');
                    })
                    ->badge()
                    ->color('info')
                    ->wrap(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descrizione')
                    ->limit(50)
                    ->toggleable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_diff')
                    ->label('Diff')
                    ->icon('heroicon-o-code-bracket')
                    ->color('info')
                    ->modalHeading('Dettagli modifica')
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Chiudi')
                    ->modalContent(fn (Activity $record) => view('filament.articles.revision-diff', ['activity' => $record])),

                Tables\Actions\Action::make('restore')
                    ->label('Ripristina')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Ripristinare questa versione?')
                    ->modalDescription('I valori attuali dell\'articolo verranno sovrascritti con quelli di questa revisione.')
                    ->visible(fn (Activity $record) => $record->event === 'updated' && ! empty($record->properties->toArray()['old'] ?? []))
                    ->action(function (Activity $record) {
                        $oldValues = $record->properties->toArray()['old'] ?? [];

                        if (empty($oldValues)) {
                            Notification::make()
                                ->title('Nessun valore da ripristinare')
                                ->warning()
                                ->send();
                            return;
                        }

                        // Ripristina solo i campi consentiti
                        $allowedFields = [
                            'title', 'slug', 'excerpt', 'content', 'status',
                            'published_at', 'category_id', 'author_id', 'is_featured',
                        ];

                        $toRestore = array_intersect_key($oldValues, array_flip($allowedFields));

                        if (empty($toRestore)) {
                            Notification::make()
                                ->title('Nessun campo ripristinabile')
                                ->warning()
                                ->send();
                            return;
                        }

                        $this->record->update($toRestore);

                        Notification::make()
                            ->title('Versione ripristinata')
                            ->body('Sono stati ripristinati ' . count($toRestore) . ' campi.')
                            ->success()
                            ->send();

                        // Redirect per ricaricare
                        redirect(request()->header('Referer') ?? url()->current());
                    }),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Nessuna revisione')
            ->emptyStateDescription('Le modifiche appariranno qui quando l\'articolo verrà aggiornato.')
            ->emptyStateIcon('heroicon-o-clock');
    }
}
