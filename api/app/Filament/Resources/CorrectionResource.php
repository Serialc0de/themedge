<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CorrectionResource\Pages;
use App\Models\Correction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CorrectionResource extends Resource
{
    protected static ?string $model = Correction::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Correzioni';
    protected static ?string $navigationGroup = 'Contenuti';
    protected static ?int $navigationSort = 7;
    protected static ?string $modelLabel = 'Correzione';
    protected static ?string $pluralModelLabel = 'Correzioni';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Correzione')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('article_id')
                        ->label('Articolo')
                        ->relationship('article', 'title')
                        ->required()
                        ->searchable()
                        ->preload(),

                    Forms\Components\Select::make('severity')
                        ->label('Tipo')
                        ->options([
                            'minor' => 'Correzione minore',
                            'major' => 'Correzione importante',
                            'retraction' => 'Ritrattazione',
                        ])
                        ->default('minor')
                        ->required(),

                    Forms\Components\Textarea::make('original_text')
                        ->label('Testo originale (errato)')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('corrected_text')
                        ->label('Testo corretto')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('note')
                        ->label('Nota / spiegazione')
                        ->rows(2)
                        ->helperText('Spiega cosa era sbagliato e perché')
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('is_published')
                        ->label('Pubblicata')
                        ->default(true),

                    Forms\Components\DateTimePicker::make('published_at')
                        ->label('Data pubblicazione')
                        ->default(now()),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('severity')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'minor' => 'gray',
                        'major' => 'warning',
                        'retraction' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'minor' => 'Correzione',
                        'major' => 'Importante',
                        'retraction' => 'Ritrattazione',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('article.title')
                    ->label('Articolo')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('note')
                    ->label('Nota')
                    ->limit(50)
                    ->placeholder('—'),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('Pubbl.')
                    ->boolean(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Pubblicata')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('severity')
                    ->label('Tipo')
                    ->options([
                        'minor' => 'Correzione',
                        'major' => 'Importante',
                        'retraction' => 'Ritrattazione',
                    ]),

                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Pubblicata'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifica'),
                Tables\Actions\DeleteAction::make()->label('Elimina'),
            ])
            ->defaultSort('published_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCorrections::route('/'),
            'create' => Pages\CreateCorrection::route('/create'),
            'edit' => Pages\EditCorrection::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole(['super_admin', 'editor']) ?? false;
    }
}
