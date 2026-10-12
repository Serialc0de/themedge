<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GlossaryTermResource\Pages;
use App\Models\GlossaryTerm;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class GlossaryTermResource extends Resource
{
    protected static ?string $model = GlossaryTerm::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Glossario';

    protected static ?string $modelLabel = 'Termine';

    protected static ?string $pluralModelLabel = 'Glossario';

    protected static ?string $navigationGroup = 'Credibilità';

    protected static ?int $navigationSort = 12;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Termine')
                ->schema([
                    Forms\Components\TextInput::make('term')
                        ->label('Termine')
                        ->required()
                        ->maxLength(200)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                            if ($operation === 'create') {
                                $set('slug', Str::slug($state ?? ''));
                            }
                        }),

                    Forms\Components\TextInput::make('slug')
                        ->label('Slug (URL)')
                        ->required()
                        ->maxLength(220)
                        ->unique(ignoreRecord: true)
                        ->helperText('Generato automaticamente dal termine. Modificabile manualmente.'),

                    Forms\Components\TextInput::make('category')
                        ->label('Categoria')
                        ->maxLength(100)
                        ->placeholder('Es. Ferrovia, Politica, Economia')
                        ->helperText('Raggruppa i termini per categoria.'),

                    Forms\Components\Textarea::make('definition')
                        ->label('Definizione')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull()
                        ->helperText('Spiegazione chiara e concisa del termine.'),

                    Forms\Components\TagsInput::make('aliases')
                        ->label('Alias / sinonimi')
                        ->placeholder('Aggiungi un alias e premi Invio')
                        ->helperText('Varianti del termine da matchare nel contenuto (es. "deragliare", "deragliato").')
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('is_published')
                        ->label('Pubblicato')
                        ->helperText('Se disattivato, il termine non appare nel glossario pubblico.')
                        ->default(true),

                    Forms\Components\TextInput::make('order')
                        ->label('Ordine')
                        ->numeric()
                        ->default(0),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('term')
                    ->label('Termine')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('category')
                    ->label('Categoria')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('definition')
                    ->label('Definizione')
                    ->limit(60)
                    ->wrap()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('aliases')
                    ->label('Alias')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : '')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('Pub.')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('order')
                    ->label('Ord.')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aggiornato')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('term', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Categoria')
                    ->options(fn () => GlossaryTerm::query()
                        ->whereNotNull('category')
                        ->distinct()
                        ->pluck('category', 'category')
                        ->toArray())
                    ->searchable(),

                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Pubblicato')
                    ->placeholder('Tutti')
                    ->trueLabel('Solo pubblicati')
                    ->falseLabel('Solo bozze'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGlossaryTerms::route('/'),
            'create' => Pages\CreateGlossaryTerm::route('/create'),
            'edit' => Pages\EditGlossaryTerm::route('/{record}/edit'),
        ];
    }
}
