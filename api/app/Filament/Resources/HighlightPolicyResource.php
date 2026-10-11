<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HighlightPolicyResource\Pages;
use App\Models\HighlightPolicy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HighlightPolicyResource extends Resource
{
    protected static ?string $model = HighlightPolicy::class;
    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationLabel = 'Policy Highlight';
    protected static ?string $modelLabel = 'Policy Highlight';
    protected static ?string $pluralModelLabel = 'Policy Highlight';
    protected static ?string $navigationGroup = 'SEO & Config';
    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informazioni base')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nome interno')
                        ->required()
                        ->maxLength(64)
                        ->unique(HighlightPolicy::class, 'name', ignoreRecord: true)
                        ->disabled(fn ($record) => $record?->is_system)
                        ->dehydrated()
                        ->helperText('Identificativo usato nel codice. Non modificabile per policy di sistema.'),

                    Forms\Components\TextInput::make('label')
                        ->label('Etichetta pubblica')
                        ->required()
                        ->maxLength(128),

                    Forms\Components\Textarea::make('description')
                        ->label('Descrizione')
                        ->rows(2)
                        ->maxLength(500)
                        ->columnSpanFull(),

                    Forms\Components\ColorPicker::make('color')
                        ->label('Colore badge')
                        ->default('#6b7280'),

                    Forms\Components\TextInput::make('priority')
                        ->label('Priorità')
                        ->numeric()
                        ->default(0)
                        ->helperText('In caso di conflitto, vince la policy con priorità più alta.'),

                    Forms\Components\Toggle::make('is_system')
                        ->label('Policy di sistema')
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Le policy di sistema non possono essere eliminate.'),
                ]),

            Forms\Components\Section::make('🎯 Comportamento highlight')
                ->description('Configura cosa gli utenti possono fare sugli articoli con questa policy.')
                ->columns(2)
                ->schema([
                    Forms\Components\Toggle::make('settings.enabled')
                        ->label('Highlight abilitati')
                        ->default(true)
                        ->inline(false)
                        ->helperText('Se disattivato, nessun highlight possibile.'),

                    Forms\Components\Toggle::make('settings.allow_public')
                        ->label('Permetti highlight pubblici')
                        ->default(true)
                        ->inline(false)
                        ->helperText('Se disattivato, gli highlight restano privati.'),

                    Forms\Components\Toggle::make('settings.allow_reactions')
                        ->label('Permetti reazioni emoji')
                        ->default(true)
                        ->inline(false),

                    Forms\Components\Toggle::make('settings.allow_notes')
                        ->label('Permetti note personali')
                        ->default(true)
                        ->inline(false),

                    Forms\Components\Toggle::make('settings.allow_heatmap')
                        ->label('Permetti heatmap')
                        ->default(true)
                        ->inline(false)
                        ->helperText('Statistiche di dove gli utenti evidenziano.'),

                    Forms\Components\Toggle::make('settings.allow_share_image')
                        ->label('Permetti condivisione come immagine')
                        ->default(false)
                        ->inline(false)
                        ->helperText('Feature in arrivo.'),

                    Forms\Components\TextInput::make('settings.max_fragment_length')
                        ->label('Lunghezza massima frammento')
                        ->numeric()
                        ->default(2000)
                        ->suffix('caratteri'),

                    Forms\Components\TextInput::make('settings.rate_limit_per_day')
                        ->label('Rate limit creazione')
                        ->numeric()
                        ->default(30)
                        ->suffix('al giorno'),
                ]),
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
                    ->weight('bold')
                    ->badge()
                    ->color(fn ($record) => $record->color ?: 'gray'),

                Tables\Columns\TextColumn::make('label')
                    ->label('Etichetta')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_system')
                    ->label('Sistema')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Priorità')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\IconColumn::make('enabled')
                    ->label('Attiva')
                    ->getStateUsing(fn ($record) => $record->enabled)
                    ->boolean(),

                Tables\Columns\TextColumn::make('articles_count')
                    ->label('Articoli')
                    ->counts('articles')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('categories_count')
                    ->label('Categorie')
                    ->counts('categories')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Autori')
                    ->counts('users')
                    ->badge()
                    ->color('info'),
            ])
            ->defaultSort('priority', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_system')
                    ->label('Policy di sistema'),

                Tables\Filters\TernaryFilter::make('enabled')
                    ->label('Abilitata')
                    ->queries(
                        true: fn ($q) => $q->whereRaw("JSON_EXTRACT(settings, '$.enabled') = true"),
                        false: fn ($q) => $q->whereRaw("JSON_EXTRACT(settings, '$.enabled') = false"),
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => ! $record->is_system),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplica')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function ($record) {
                        $new = $record->replicate();
                        $new->name = $record->name . '_copy_' . time();
                        $new->label = $record->label . ' (copia)';
                        $new->is_system = false;
                        $new->save();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            // Blocca eliminazione policy di sistema
                            $blocked = $records->where('is_system', true);
                            if ($blocked->count() > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Impossibile eliminare policy di sistema')
                                    ->body($blocked->pluck('name')->join(', '))
                                    ->danger()
                                    ->send();
                                $records = $records->where('is_system', false);
                            }
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHighlightPolicies::route('/'),
            'create' => Pages\CreateHighlightPolicy::route('/create'),
            'edit' => Pages\EditHighlightPolicy::route('/{record}/edit'),
        ];
    }
}
