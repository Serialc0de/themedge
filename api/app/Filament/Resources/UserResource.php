<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Redazione';
    protected static ?int $navigationSort = 10;
    protected static ?string $modelLabel = 'Utente';
    protected static ?string $pluralModelLabel = 'Utenti';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Anagrafica')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nome completo')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),

                    Forms\Components\TextInput::make('password')
                        ->label('Password')
                        ->password()
                        ->revealable()
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $context) => $context === 'create')
                        ->minLength(8)
                        ->helperText('Minimo 8 caratteri. Lascia vuoto per non cambiare.'),

                    Forms\Components\TextInput::make('job_title')
                        ->label('Ruolo redazionale')
                        ->placeholder('es. Caporedattore, Giornalista, Fotografo')
                        ->maxLength(255),
                ]),

            Forms\Components\Section::make('Profilo pubblico')
                ->columns(2)
                ->schema([
                    Forms\Components\FileUpload::make('avatar')
                        ->label('Avatar')
                        ->image()
                        ->imageEditor()
                        ->imageEditorAspectRatios(['1:1'])
                        ->directory('avatars')
                        ->disk('public')
                        ->maxSize(2048)
                        ->columnSpan(1),

                    Forms\Components\Textarea::make('bio')
                        ->label('Biografia')
                        ->rows(4)
                        ->maxLength(500)
                        ->helperText('Appare sulla pagina autore del frontend.')
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('twitter_handle')
                        ->label('Twitter/X handle')
                        ->prefix('@')
                        ->maxLength(50)
                        ->columnSpan(2),
                ]),

            Forms\Components\Section::make('Permessi e stato')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('roles')
                        ->label('Ruoli')
                        ->relationship('roles', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->helperText('Un utente può avere più ruoli.'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Account attivo')
                        ->default(true)
                        ->helperText('Se disattivato, l\'utente non può accedere al pannello.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(fn (User $record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=dc2626&color=fff')
                    ->size(40),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->description(fn (User $record) => $record->job_title),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-m-envelope'),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Ruoli')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'editor' => 'warning',
                        'author' => 'success',
                        'contributor' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('articles_count')
                    ->label('Articoli')
                    ->counts('articles')
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Attivo')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Ultimo accesso')
                    ->dateTime('d/m/Y H:i')
                    ->since()
                    ->placeholder('Mai')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrato')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Ruolo')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Stato account')
                    ->placeholder('Tutti')
                    ->trueLabel('Solo attivi')
                    ->falseLabel('Solo disattivati'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('impersonate')
                    ->label('Impersona')
                    ->icon('heroicon-o-user-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Impersona utente')
                    ->modalDescription(fn (User $record) => "Stai per accedere come {$record->name}. Verrai reindirizzato al frontend.")
                    ->action(function (User $record) {
                        // Impersona solo se hai il pacchetto lab404/laravel-impersonate
                        // oppure tramite login manuale
                        auth()->login($record);
                        return redirect('/admin');
                    })
                    ->visible(fn (User $record) => $record->id !== auth()->id()),

                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (User $record) => $record->is_active ? 'Disattiva' : 'Attiva')
                    ->icon(fn (User $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (User $record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->update(['is_active' => ! $record->is_active]);
                    })
                    ->visible(fn (User $record) => $record->id !== auth()->id()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('articles');
    }
}
