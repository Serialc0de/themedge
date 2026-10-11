<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EditorialRoleResource\Pages;
use App\Models\EditorialRole;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class EditorialRoleResource extends Resource
{
    protected static ?string $model = EditorialRole::class;
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationLabel = 'Ruoli editoriali';
    protected static ?string $navigationGroup = 'Redazione';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Ruolo editoriale';
    protected static ?string $pluralModelLabel = 'Ruoli editoriali';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nome ruolo')
                ->required()
                ->maxLength(100)
                ->placeholder('es. Direttore, Caporedattore, Redattore'),

            Forms\Components\TextInput::make('slug')
                ->label('Slug')
                ->maxLength(100)
                ->helperText('Auto-generato dal nome se vuoto'),

            Forms\Components\Textarea::make('description')
                ->label('Descrizione')
                ->rows(3)
                ->columnSpanFull(),

            Forms\Components\TextInput::make('sort_order')
                ->label('Ordine di visualizzazione')
                ->numeric()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Ruolo')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descrizione')
                    ->limit(60)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Utenti')
                    ->counts('users')
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifica'),
                Tables\Actions\DeleteAction::make()->label('Elimina'),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEditorialRoles::route('/'),
            'create' => Pages\CreateEditorialRole::route('/create'),
            'edit' => Pages\EditEditorialRole::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole(['super_admin']) ?? false;
    }
}
