<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TipSubmissionResource\Pages;
use App\Models\TipSubmission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class TipSubmissionResource extends Resource
{
    protected static ?string $model = TipSubmission::class;
    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';
    protected static ?string $navigationLabel = 'Segnalazioni';
    protected static ?string $navigationGroup = 'Redazione';
    protected static ?int $navigationSort = 20;
    protected static ?string $modelLabel = 'Segnalazione';
    protected static ?string $pluralModelLabel = 'Segnalazioni';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Segnalazione')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')->label('Nome')->disabled(),
                    Forms\Components\TextInput::make('email')->label('Email')->disabled(),
                    Forms\Components\TextInput::make('phone')->label('Telefono')->disabled(),
                    Forms\Components\TextInput::make('subject')->label('Oggetto')->disabled()->columnSpanFull(),
                    Forms\Components\Textarea::make('message')->label('Messaggio')->rows(6)->disabled()->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Gestione')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Stato')
                        ->options([
                            'new' => 'Nuova',
                            'in_review' => 'In revisione',
                            'published' => 'Pubblicata',
                            'rejected' => 'Rifiutata',
                        ])
                        ->required(),

                    Forms\Components\Select::make('assigned_to')
                        ->label('Assegnata a')
                        ->relationship('assignedUser', 'name')
                        ->searchable()
                        ->preload(),

                    Forms\Components\Textarea::make('internal_notes')
                        ->label('Note interne')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'danger',
                        'in_review' => 'warning',
                        'published' => 'success',
                        'rejected' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'new' => '🔴 Nuova',
                        'in_review' => '🟡 In revisione',
                        'published' => '🟢 Pubblicata',
                        'rejected' => '⚫ Rifiutata',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('subject')
                    ->label('Oggetto')
                    ->searchable()
                    ->limit(50)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Da')
                    ->placeholder('Anonimo')
                    ->searchable(),

                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Assegnata a')
                    ->placeholder('—')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ricevuta')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Stato')
                    ->options([
                        'new' => '🔴 Nuova',
                        'in_review' => '🟡 In revisione',
                        'published' => '🟢 Pubblicata',
                        'rejected' => '⚫ Rifiutata',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Gestisci'),
                Tables\Actions\DeleteAction::make()->label('Elimina'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTipSubmissions::route('/'),
            'edit' => Pages\EditTipSubmission::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole(['super_admin', 'editor']) ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'new')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
