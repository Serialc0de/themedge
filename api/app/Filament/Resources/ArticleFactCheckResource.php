<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleFactCheckResource\Pages;
use App\Models\Article;
use App\Models\ArticleFactCheck;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ArticleFactCheckResource extends Resource
{
    protected static ?string $model = ArticleFactCheck::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationLabel = 'Fact-check';

    protected static ?string $modelLabel = 'Fact-check';

    protected static ?string $pluralModelLabel = 'Fact-check';

    protected static ?string $navigationGroup = 'Credibilità';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Affermazione')
                ->schema([
                    Forms\Components\Select::make('article_id')
                        ->label('Articolo')
                        ->relationship('article', 'title')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\Textarea::make('claim')
                        ->label('Affermazione verificata')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\Select::make('verdict')
                        ->label('Verdetto')
                        ->options(ArticleFactCheck::VERDICT_LABELS)
                        ->required()
                        ->live()
                        ->helperText(fn (Forms\Get $get) => ArticleFactCheck::VERDICT_DESCRIPTIONS[$get('verdict')] ?? ''),

                    Forms\Components\TextInput::make('order')
                        ->label('Ordine')
                        ->numeric()
                        ->default(0)
                        ->helperText('Ordine di visualizzazione (0 = primo)'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Fonte')
                ->schema([
                    Forms\Components\TextInput::make('source_name')
                        ->label('Nome fonte')
                        ->placeholder('Es. Ministero dell\'Interno')
                        ->maxLength(200),

                    Forms\Components\TextInput::make('source_url')
                        ->label('URL fonte')
                        ->url()
                        ->maxLength(500)
                        ->placeholder('https://...'),

                    Forms\Components\Textarea::make('note')
                        ->label('Nota editoriale (opzionale)')
                        ->rows(3)
                        ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('article.title')
                    ->label('Articolo')
                    ->searchable()
                    ->limit(40)
                    ->sortable(),

                Tables\Columns\TextColumn::make('claim')
                    ->label('Affermazione')
                    ->searchable()
                    ->limit(60)
                    ->wrap(),

                Tables\Columns\BadgeColumn::make('verdict')
                    ->label('Verdetto')
                    ->formatStateUsing(fn (string $state) => ArticleFactCheck::VERDICT_ICONS[$state] . ' ' . ArticleFactCheck::VERDICT_LABELS[$state])
                    ->colors([
                        'success' => 'verified',
                        'warning' => 'in_review',
                        'danger' => 'false',
                    ]),

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
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('verdict')
                    ->label('Verdetto')
                    ->options(ArticleFactCheck::VERDICT_LABELS),

                Tables\Filters\SelectFilter::make('article_id')
                    ->label('Articolo')
                    ->relationship('article', 'title')
                    ->searchable()
                    ->preload(),
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
            'index' => Pages\ListArticleFactChecks::route('/'),
            'create' => Pages\CreateArticleFactCheck::route('/create'),
            'edit' => Pages\EditArticleFactCheck::route('/{record}/edit'),
        ];
    }
}
