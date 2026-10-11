<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PollResource\Pages;
use App\Models\Poll;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PollResource extends Resource
{
    protected static ?string $model = Poll::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Sondaggi';
    protected static ?string $navigationGroup = 'Contenuti';
    protected static ?int $navigationSort = 6;
    protected static ?string $modelLabel = 'Sondaggio';
    protected static ?string $pluralModelLabel = 'Sondaggi';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Sondaggio')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('question')
                        ->label('Domanda')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('description')
                        ->label('Descrizione')
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\Select::make('article_id')
                        ->label('Collega ad articolo (opzionale)')
                        ->relationship('article', 'title')
                        ->searchable()
                        ->preload(),

                    Forms\Components\DateTimePicker::make('ends_at')
                        ->label('Termina il')
                        ->seconds(false),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Attivo')
                        ->default(true),

                    Forms\Components\Toggle::make('allow_multiple')
                        ->label('Permetti scelta multipla')
                        ->default(false),
                ]),

            Forms\Components\Section::make('Opzioni')
                ->schema([
                    Forms\Components\Repeater::make('options')
                        ->label('')
                        ->relationship()
                        ->schema([
                            Forms\Components\TextInput::make('option_text')
                                ->label('Testo opzione')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('sort_order')
                                ->label('Ordine')
                                ->numeric()
                                ->default(0),
                        ])
                        ->columns(2)
                        ->reorderable()
                        ->orderColumn('sort_order')
                        ->addActionLabel('Aggiungi opzione')
                        ->minItems(2)
                        ->defaultItems(2),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('question')
                    ->label('Domanda')
                    ->searchable()
                    ->limit(60)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('options_count')
                    ->label('Opzioni')
                    ->counts('options')
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('votes_count')
                    ->label('Voti')
                    ->counts('votes')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Attivo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Termina')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creato')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Attivo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifica'),
                Tables\Actions\DeleteAction::make()->label('Elimina'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Elimina'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPolls::route('/'),
            'create' => Pages\CreatePoll::route('/create'),
            'edit' => Pages\EditPoll::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole(['super_admin', 'editor']) ?? false;
    }
}
