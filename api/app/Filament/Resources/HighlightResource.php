<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HighlightResource\Pages;
use App\Models\ReaderHighlight;
use App\Models\ReaderIdentity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HighlightResource extends Resource
{
    protected static ?string $model = ReaderHighlight::class;
    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';
    protected static ?string $navigationLabel = 'Highlight';
    protected static ?string $modelLabel = 'Highlight';
    protected static ?string $pluralModelLabel = 'Highlight';
    protected static ?string $navigationGroup = 'Contenuti';
    protected static ?int $navigationSort = 10;

    // ============================================================
    // FORM
    // ============================================================

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Contenuto (sola lettura)')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('article.title')
                        ->label('Articolo')
                        ->disabled(),

                    Forms\Components\TextInput::make('reader.display_name')
                        ->label('Reader')
                        ->disabled()
                        ->placeholder('Anonimo'),

                    Forms\Components\Textarea::make('text_fragment')
                        ->label('Testo evidenziato')
                        ->rows(3)
                        ->disabled()
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Moderazione')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('color')
                        ->label('Colore')
                        ->options([
                            'yellow' => 'Giallo',
                            'green' => 'Verde',
                            'pink' => 'Rosa',
                        ])
                        ->default('yellow'),

                    Forms\Components\Toggle::make('is_public')
                        ->label('Pubblico')
                        ->helperText('Se disattivato, visibile solo all autore'),

                    Forms\Components\Textarea::make('note')
                        ->label('Nota personale')
                        ->rows(2)
                        ->disabled()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('article.title')
                    ->label('Articolo')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->url(fn ($record) => $record->article ? '/admin/articles/' . $record->article->id . '/edit' : null)
                    ->openUrlInNewTab()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('text_fragment')
                    ->label('Testo')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->text_fragment)
                    ->searchable(),

                Tables\Columns\TextColumn::make('color')
                    ->label('Colore')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'yellow' => 'warning',
                        'green' => 'success',
                        'pink' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'yellow' => 'Giallo',
                        'green' => 'Verde',
                        'pink' => 'Rosa',
                        default => $state,
                    }),

                Tables\Columns\IconColumn::make('is_public')
                    ->label('Pub.')
                    ->boolean(),

                Tables\Columns\IconColumn::make('has_note')
                    ->label('Nota')
                    ->getStateUsing(fn ($record) => !empty($record->note))
                    ->boolean(),

                Tables\Columns\TextColumn::make('reactions_count')
                    ->label('React')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reader.display_name')
                    ->label('Reader')
                    ->limit(20)
                    ->placeholder('Anonimo')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_public')
                    ->label('Pubblico'),

                Tables\Filters\SelectFilter::make('color')
                    ->label('Colore')
                    ->options([
                        'yellow' => 'Giallo',
                        'green' => 'Verde',
                        'pink' => 'Rosa',
                    ]),

                Tables\Filters\TernaryFilter::make('has_note')
                    ->label('Con nota')
                    ->queries(
                        true: fn ($q) => $q->whereNotNull('note')->where('note', '!=', ''),
                        false: fn ($q) => $q->whereNull('note')->orWhere('note', ''),
                    ),

                Tables\Filters\TernaryFilter::make('has_reactions')
                    ->label('Con reazioni')
                    ->queries(
                        true: fn ($q) => $q->where('reactions_count', '>', 0),
                        false: fn ($q) => $q->where('reactions_count', '=', 0),
                    ),

                Tables\Filters\Filter::make('created_at')
                    ->label('Data')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Da'),
                        Forms\Components\DatePicker::make('until')->label('A'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Modifica'),

                Tables\Actions\Action::make('hide')
                    ->label('Nascondi')
                    ->icon('heroicon-o-eye-slash')
                    ->color('warning')
                    ->visible(fn ($record) => $record->is_public)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['is_public' => false]);
                        Notification::make()
                            ->title('Highlight reso privato')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('ban_reader')
                    ->label('Ban reader')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn ($record) => $record->reader_id !== null)
                    ->requiresConfirmation()
                    ->modalHeading('Bloccare questo reader?')
                    ->modalDescription('Tutti i suoi highlight pubblici verranno nascosti.')
                    ->action(function ($record) {
                        ReaderHighlight::where('reader_id', $record->reader_id)
                            ->update(['is_public' => false]);

                        Notification::make()
                            ->title('Reader bloccato')
                            ->body('Tutti i suoi highlight pubblici sono stati nascosti.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('Elimina'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('hide_selected')
                        ->label('Rendi privati')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(fn ($r) => $r->update(['is_public' => false]));
                            Notification::make()
                                ->title($records->count() . ' highlight resi privati')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ============================================================
    // QUERY
    // ============================================================

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['article:id,title,slug', 'reader:id,display_name']);
    }

    // ============================================================
    // PAGES
    // ============================================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHighlights::route('/'),
            'edit' => Pages\EditHighlight::route('/{record}/edit'),
        ];
    }
}
