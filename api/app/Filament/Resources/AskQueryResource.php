<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AskQueryResource\Pages;
use App\Models\AskQuery;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class AskQueryResource extends Resource
{
    protected static ?string $model = AskQuery::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'AI';

    protected static ?string $navigationLabel = 'Chiedi a Themedge';

    protected static ?string $modelLabel = 'Query AI';

    protected static ?string $pluralModelLabel = 'Query AI';

    protected static ?int $navigationSort = 20;

    public static function canCreate(): bool
    {
        return false; // Solo read-only
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\TextEntry::make('question')
                    ->label('Domanda')
                    ->columnSpanFull(),

                Infolists\Components\TextEntry::make('answer')
                    ->label('Risposta')
                    ->columnSpanFull()
                    ->formatStateUsing(fn ($state) => $state ?: '(nessuna)'),

                Infolists\Components\TextEntry::make('model')
                    ->label('Modello'),

                Infolists\Components\TextEntry::make('total_ms')
                    ->label('Tempo')
                    ->formatStateUsing(fn ($state) => $state ? round($state / 1000, 1) . 's' : '—'),

                Infolists\Components\TextEntry::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('question')
                    ->label('Domanda')
                    ->searchable()
                    ->limit(60)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('sources_count')
                    ->label('Fonti')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('feedbacks_sum_vote')
                    ->label('Feedback')
                    ->getStateUsing(function ($record) {
                        $pos = $record->feedbacks()->where('vote', 1)->count();
                        $neg = $record->feedbacks()->where('vote', -1)->count();
                        if ($pos === 0 && $neg === 0) return '—';
                        return "👍 {$pos} | 👎 {$neg}";
                    })
                    ->badge()
                    ->color(fn ($state) => $state === '—' ? 'gray' : 'warning'),

                Tables\Columns\TextColumn::make('total_ms')
                    ->label('Tempo')
                    ->formatStateUsing(fn ($state) => $state ? round($state / 1000, 1) . 's' : '—')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ip')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('has_negative_feedback')
                    ->label('Con feedback negativo')
                    ->query(fn ($query) => $query->whereHas('feedbacks', fn ($q) => $q->where('vote', -1))),

                Tables\Filters\Filter::make('has_feedback')
                    ->label('Con feedback')
                    ->query(fn ($query) => $query->whereHas('feedbacks')),

                Tables\Filters\Filter::make('last_24h')
                    ->label('Ultime 24 ore')
                    ->query(fn ($query) => $query->where('created_at', '>=', now()->subDay())),

                Tables\Filters\Filter::make('last_7_days')
                    ->label('Ultimi 7 giorni')
                    ->query(fn ($query) => $query->where('created_at', '>=', now()->subDays(7))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAskQueries::route('/'),
            'view' => Pages\ViewAskQuery::route('/{record}'),
        ];
    }
}
