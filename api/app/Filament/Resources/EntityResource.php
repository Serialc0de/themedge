<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EntityResource\Pages;
use App\Models\Entity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class EntityResource extends Resource
{
    protected static ?string $model = Entity::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Entità';

    protected static ?string $modelLabel = 'Entità';

    protected static ?string $pluralModelLabel = 'Entità';

    protected static ?string $navigationGroup = 'Credibilità';

    protected static ?int $navigationSort = 13;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identità')
                ->schema([
                    Forms\Components\Select::make('type')
                        ->label('Tipo')
                        ->options(Entity::TYPE_LABELS)
                        ->required()
                        ->default('person')
                        ->live(),

                    Forms\Components\TextInput::make('name')
                        ->label('Nome')
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
                        ->helperText('Generato automaticamente dal nome.'),

                    Forms\Components\Textarea::make('bio')
                        ->label('Biografia / Descrizione')
                        ->rows(4)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('photo_url')
                        ->label('URL foto')
                        ->url()
                        ->maxLength(500)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Alias e metadati')
                ->schema([
                    Forms\Components\TagsInput::make('aliases')
                        ->label('Alias / varianti del nome')
                        ->placeholder('Aggiungi un alias e premi Invio')
                        ->helperText('Es. "Meloni", "La Premier". Usati per il match automatico nel contenuto.')
                        ->columnSpanFull(),

                    Forms\Components\KeyValue::make('metadata')
                        ->label('Metadati aggiuntivi')
                        ->keyLabel('Chiave')
                        ->valueLabel('Valore')
                        ->addActionLabel('Aggiungi metadato')
                        ->helperText('Es. birth_date, party, country, website.')
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->collapsed(),

            Forms\Components\Section::make('Pubblicazione')
                ->schema([
                    Forms\Components\Toggle::make('is_published')
                        ->label('Pubblicata')
                        ->helperText('Se disattivata, l\'entità non appare pubblicamente.')
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
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Entity::TYPE_LABELS[$state] ?? $state)
                    ->color(fn (string $state) => Entity::TYPE_COLORS[$state] ?? 'gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('bio')
                    ->label('Bio')
                    ->limit(60)
                    ->wrap()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('articles_count')
                    ->counts('articles')
                    ->label('Articoli')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('Pub.')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aggiornata')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(Entity::TYPE_LABELS),

                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Pubblicata')
                    ->placeholder('Tutte')
                    ->trueLabel('Solo pubblicate')
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
            'index' => Pages\ListEntities::route('/'),
            'create' => Pages\CreateEntity::route('/create'),
            'edit' => Pages\EditEntity::route('/{record}/edit'),
        ];
    }
}
