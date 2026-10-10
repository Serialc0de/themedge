<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MediaResource\Pages;
use App\Models\MediaAsset;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class MediaResource extends Resource
{
    protected static ?string $model = MediaAsset::class;
    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationLabel = 'Media';
    protected static ?string $navigationGroup = 'Contenuti';
    protected static ?int $navigationSort = 5;
    protected static ?string $modelLabel = 'Media';
    protected static ?string $pluralModelLabel = 'Libreria Media';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informazioni file')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Titolo')
                        ->maxLength(255),

                    Forms\Components\Textarea::make('description')
                        ->label('Descrizione')
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\SpatieMediaLibraryFileUpload::make('library')
                        ->label('File')
                        ->collection('library')
                        ->image()
                        ->imageEditor()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('library')
                    ->label('')
                    ->collection('library')
                    ->height(60)
                    ->width(80)
                    ->square(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Titolo')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->description(fn (MediaAsset $record) => $record->getFirstMedia('library')?->human_readable_size ?? '—'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descrizione')
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Caricato')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('recent')
                    ->label('Ultima settimana')
                    ->query(fn (Builder $query) => $query->where('created_at', '>=', now()->subWeek())),
            ])
            ->actions([
                Tables\Actions\Action::make('copy_url')
                    ->label('URL')
                    ->icon('heroicon-o-link')
                    ->color('info')
                    ->modalHeading('URL del file')
                    ->modalDescription('Seleziona l\'URL e copialo con Ctrl+C (o Cmd+C su Mac)')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Chiudi')
                    ->modalContent(function (MediaAsset $record) {
                        $url = $record->getFirstMediaUrl('library') ?? 'Nessun file associato';

                        $html = '
                            <div class="space-y-3">
                                <div>
                                    <p class="text-sm font-semibold mb-2">URL completo:</p>
                                    <input
                                        type="text"
                                        readonly
                                        value="' . e($url) . '"
                                        onclick="this.select()"
                                        class="w-full px-3 py-2 text-sm font-mono bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded focus:outline-none focus:ring-2 focus:ring-primary-500"
                                        style="cursor: pointer;"
                                    />
                                    <p class="text-xs text-gray-500 mt-2">
                                        👆 Clicca sull\'input per selezionare tutto, poi premi <strong>Ctrl+C</strong>
                                    </p>
                                </div>

                                <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                                    <p class="text-xs text-gray-500">
                                        <strong>Percorso file:</strong><br>
                                        <code class="text-xs">' . e($record->getFirstMedia('library')?->getPath() ?? 'N/A') . '</code>
                                    </p>
                                </div>
                            </div>
                        ';

                        return new HtmlString($html);
                    }),

                Tables\Actions\Action::make('view')
                    ->label('Apri')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (MediaAsset $record) => $record->getFirstMediaUrl('library'))
                    ->openUrlInNewTab()
                    ->visible(fn (MediaAsset $record) => (bool) $record->getFirstMedia('library')),

                Tables\Actions\EditAction::make()->label('Modifica'),
                Tables\Actions\DeleteAction::make()->label('Elimina'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Elimina selezionati'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMedia::route('/'),
            'create' => Pages\CreateMedia::route('/create'),
            'edit' => Pages\EditMedia::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole(['super_admin', 'editor', 'author']) ?? false;
    }
}
