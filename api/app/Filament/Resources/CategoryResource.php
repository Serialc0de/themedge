<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationLabel = 'Categorie';
    protected static ?string $modelLabel = 'Categoria';
    protected static ?string $pluralModelLabel = 'Categorie';
    protected static ?string $navigationGroup = 'Contenuti';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dettagli categoria')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug (URL)')
                            ->required()
                            ->maxLength(255)
                            ->unique(Category::class, 'slug', ignoreRecord: true),
                        Forms\Components\TextInput::make('emoji')
                            ->label('Emoji')
                            ->maxLength(10)
                            ->helperText('Emoji della categoria (es. 🏛️, 📈, 🎭)'),

                        Forms\Components\Textarea::make('description')
                            ->label('Descrizione')
                            ->maxLength(500)
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('parent_id')
                            ->label('Categoria padre')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\ColorPicker::make('color')
                            ->label('Colore')
                            ->default('#3b82f6'),
                        Forms\Components\Select::make('highlight_policy_id')
                            ->label('Policy Highlight')
                            ->relationship('highlightPolicy', 'label')
                            ->searchable()
                            ->preload()
                            ->placeholder('Nessuna — eredita dal default')
                            ->helperText('Policy di default per tutti gli articoli di questa categoria.')
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('image')
                            ->label('Immagine')
                            ->image()
                            ->directory('categories')
                            ->imageEditor(),
                        Forms\Components\TextInput::make('order')
                            ->label('Ordinamento')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Attiva')
                            ->default(true),
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
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Padre')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('articles_count')
                    ->label('Articoli')
                    ->counts('articles')
                    ->badge()
                    ->color('info'),
                Tables\Columns\ColorColumn::make('color')
                    ->label('Colore'),
                Tables\Columns\TextColumn::make('order')
                    ->label('Ord.')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Attiva')
                    ->boolean(),
            ])
            ->defaultSort('order', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Attiva'),
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
