<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleTimelineEventResource\Pages;
use App\Models\ArticleTimelineEvent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ArticleTimelineEventResource extends Resource
{
    protected static ?string $model = ArticleTimelineEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Timeline';

    protected static ?string $modelLabel = 'Evento timeline';

    protected static ?string $pluralModelLabel = 'Timeline';

    protected static ?string $navigationGroup = 'Credibilità';

    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Evento')
                ->schema([
                    Forms\Components\Select::make('article_id')
                        ->label('Articolo')
                        ->relationship('article', 'title')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\DatePicker::make('event_date')
                        ->label('Data evento')
                        ->required()
                        ->displayFormat('d/m/Y')
                        ->native(false),

                    Forms\Components\TextInput::make('title')
                        ->label('Titolo')
                        ->required()
                        ->maxLength(300)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('description')
                        ->label('Descrizione')
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('is_key_event')
                        ->label('Evento chiave')
                        ->helperText('Evidenzia questo evento nella timeline.')
                        ->default(false),

                    Forms\Components\TextInput::make('order')
                        ->label('Ordine (stessa data)')
                        ->numeric()
                        ->default(0),
                ])
                ->columns(2),

            Forms\Components\Section::make('Fonte')
                ->schema([
                    Forms\Components\TextInput::make('source_name')
                        ->label('Nome fonte')
                        ->maxLength(200),

                    Forms\Components\TextInput::make('source_url')
                        ->label('URL fonte')
                        ->url()
                        ->maxLength(500),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('event_date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('article.title')
                    ->label('Articolo')
                    ->searchable()
                    ->limit(40)
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Titolo')
                    ->searchable()
                    ->limit(60)
                    ->wrap(),

                Tables\Columns\IconColumn::make('is_key_event')
                    ->label('Chiave')
                    ->boolean(),

                Tables\Columns\TextColumn::make('source_name')
                    ->label('Fonte')
                    ->limit(30)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('order')
                    ->label('Ord.')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creato')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('event_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('article_id')
                    ->label('Articolo')
                    ->relationship('article', 'title')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_key_event')
                    ->label('Evento chiave')
                    ->placeholder('Tutti')
                    ->trueLabel('Solo eventi chiave')
                    ->falseLabel('Solo eventi normali'),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['article']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticleTimelineEvents::route('/'),
            'create' => Pages\CreateArticleTimelineEvent::route('/create'),
            'edit' => Pages\EditArticleTimelineEvent::route('/{record}/edit'),
        ];
    }
}
