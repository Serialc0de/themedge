<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WikiPageResource\Pages;
use App\Models\WikiPage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WikiPageResource extends Resource
{
    protected static ?string $model = WikiPage::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Wiki';

    protected static ?string $navigationLabel = 'Pagine Wiki';

    protected static ?string $modelLabel = 'Pagina Wiki';

    protected static ?string $pluralModelLabel = 'Pagine Wiki';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identità')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Titolo')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug($state))),

                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(191)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('subtitle')
                            ->label('Sottotitolo')
                            ->maxLength(255),

                        Forms\Components\Select::make('type')
                            ->label('Tipo')
                            ->required()
                            ->options(WikiPage::TYPE_LABELS)
                            ->default('person'),

                        Forms\Components\Select::make('status')
                            ->label('Stato')
                            ->required()
                            ->options(WikiPage::STATUS_LABELS)
                            ->default('draft'),

                        Forms\Components\TextInput::make('icon')
                            ->label('Icona (emoji)')
                            ->maxLength(10),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Data pubblicazione'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Contenuto')
                    ->schema([
                        Forms\Components\Textarea::make('summary')
                            ->label('Sommario')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('content')
                            ->label('Contenuto')
                            ->columnSpanFull()
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('wiki'),

                        Forms\Components\KeyValue::make('infobox')
                            ->label('Infobox')
                            ->columnSpanFull(),

                        Forms\Components\TagsInput::make('aliases')
                            ->label('Alias')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Media')
                    ->schema([
                        Forms\Components\FileUpload::make('featured_image')
                            ->label('Immagine principale')
                            ->image()
                            ->directory('wiki')
                            ->maxSize(5120),

                        Forms\Components\TextInput::make('featured_image_caption')
                            ->label('Didascalia immagine'),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Forms\Components\Section::make('SEO')
                    ->schema([
                        Forms\Components\TextInput::make('meta_title')->maxLength(255),
                        Forms\Components\Textarea::make('meta_description')->maxLength(500)->rows(2),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Forms\Components\Section::make('Info AI & Sistema')
                    ->schema([
                        Forms\Components\Placeholder::make('ai_info')
                            ->label('Generato da AI')
                            ->content(fn ($record) => $record?->is_ai_generated
                                ? "✅ Sì — {$record->ai_model} — {$record->ai_generated_at?->format('d/m/Y H:i')}"
                                : '❌ No'),

                        Forms\Components\Placeholder::make('stats')
                            ->label('Statistiche')
                            ->content(fn ($record) => $record
                                ? "📄 {$record->articles_count} articoli · 👁 {$record->views} viste"
                                : '—'),

                        Forms\Components\Select::make('entity_id')
                            ->label('Entità collegata')
                            ->relationship('entity', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(3)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('icon')->label('')->width('40px'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Titolo')
                    ->searchable()
                    ->sortable()
                    ->description(fn (WikiPage $record) => $record->subtitle)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => WikiPage::TYPE_LABELS[$state] ?? $state)
                    ->color(fn (string $state) => WikiPage::TYPE_COLORS[$state] ?? 'gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => WikiPage::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state) => match($state) {
                        'published' => 'success',
                        'draft' => 'warning',
                        'archived' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('articles_count')
                    ->label('Articoli')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_ai_generated')
                    ->label('AI')
                    ->boolean()
                    ->trueIcon('heroicon-o-sparkles')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('entity.wikipedia_status')
                    ->label('Wikipedia')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'ready' => '✅ ready',
                        'fetching' => '⏳',
                        'failed' => '❌',
                        'no_page' => '🚫',
                        'skipped' => '⚪',
                        'no_content' => '⚠️',
                        default => '—',
                    })
                    ->color(fn (?string $state) => match ($state) {
                        'ready' => 'success',
                        'fetching' => 'warning',
                        'failed' => 'danger',
                        'no_page', 'skipped', 'no_content' => 'gray',
                        default => 'gray',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('image_status')
                    ->label('Immagine')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'ready' => '✅ img',
                        'no_image' => '🚫 img',
                        'skipped' => '⚪ img',
                        'failed' => '❌ img',
                        default => '— img',
                    })
                    ->color(fn (?string $state) => match ($state) {
                        'ready' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aggiornata')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options(WikiPage::TYPE_LABELS),
                Tables\Filters\SelectFilter::make('status')->options(WikiPage::STATUS_LABELS),
                Tables\Filters\TernaryFilter::make('is_ai_generated')->label('Generata da AI'),
                Tables\Filters\SelectFilter::make('image_status')
                    ->label('Immagine')
                    ->options([
                        'ready' => '✅ ready',
                        '' => '(vuoto)',
                        'no_image' => '🚫 no image',
                        'skipped' => '⚪ skipped',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_frontend')
                    ->label('Vedi sito')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (WikiPage $record) => 'https://themedge.com/wiki/' . $record->slug)
                    ->openUrlInNewTab()
                    ->color('gray')
                    ->visible(fn (WikiPage $record) => $record->status === 'published'),

                Tables\Actions\Action::make('publish')
                    ->label('Pubblica')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (WikiPage $record) => $record->status !== 'published')
                    ->action(function (WikiPage $record) {
                        $record->update(['status' => 'published', 'published_at' => now()]);
                        Notification::make()->title('Wiki pubblicata')->success()->send();
                    }),

                Tables\Actions\Action::make('regenerate')
                    ->label('Rigenera AI')
                    ->icon('heroicon-o-sparkles')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (WikiPage $record) => $record->entity_id !== null)
                    ->action(function (WikiPage $record) {
                        if (!$record->entity) {
                            Notification::make()->title('Entità non collegata')->danger()->send();
                            return;
                        }
                        \App\Jobs\GenerateWikiPageFromEntity::dispatch($record->entity, force: true);
                        Notification::make()->title('Rigenerazione avviata')->success()->send();
                    }),

                Tables\Actions\Action::make('fetch_wikipedia')
                    ->label('📚 Fetch Wikipedia')
                    ->icon('heroicon-o-book-open')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (WikiPage $record) => $record->entity_id !== null)
                    ->action(function (WikiPage $record) {
                        if (!$record->entity) {
                            Notification::make()->title('Entità non collegata')->danger()->send();
                            return;
                        }
                        \App\Jobs\FetchWikipediaArticle::dispatch($record->entity, force: true);
                        Notification::make()->title('Fetch Wikipedia avviato')->success()->send();
                    }),

                Tables\Actions\Action::make('fetch_image')
                    ->label('🖼️ Fetch image')
                    ->icon('heroicon-o-photo')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Cerca immagine su Wikipedia/Commons')
                    ->modalDescription('Scarica l\'immagine principale della pagina Wikipedia dell\'entità collegata.')
                    ->visible(fn (WikiPage $record) => $record->entity_id !== null)
                    ->action(function (WikiPage $record) {
                        if (!$record->entity) {
                            Notification::make()->title('Entità non collegata')->danger()->send();
                            return;
                        }
                        \App\Jobs\FetchWikiImage::dispatch($record, force: true);
                        Notification::make()
                            ->title('Fetch immagine avviato')
                            ->body('Job accodato. Controlla tra 1-2 minuti.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('fetch_and_regenerate')
                    ->label('🔄 Fetch + Rigenera')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (WikiPage $record) => $record->entity_id !== null)
                    ->action(function (WikiPage $record) {
                        if (!$record->entity) {
                            Notification::make()->title('Entità non collegata')->danger()->send();
                            return;
                        }
                        \App\Jobs\FetchWikipediaArticle::dispatch($record->entity, force: false);
                        \App\Jobs\GenerateWikiPageFromEntity::dispatch($record->entity, force: true)
                            ->delay(now()->addMinutes(5));
                        Notification::make()->title('Fetch + Rigenera avviati')->success()->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('publish_all')
                        ->label('Pubblica tutte')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['status' => 'published', 'published_at' => $record->published_at ?? now()]);
                            }
                            Notification::make()->title(count($records) . ' wiki pubblicate')->success()->send();
                        }),

                    Tables\Actions\BulkAction::make('fetch_images_batch')
                        ->label('🖼️ Fetch images (batch)')
                        ->icon('heroicon-o-photo')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Cerca immagini per le wiki selezionate')
                        ->modalDescription('Dispatcha FetchWikiImage per ogni wiki con entità collegata.')
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->entity_id) {
                                    \App\Jobs\FetchWikiImage::dispatch($record, force: true);
                                    $count++;
                                }
                            }
                            Notification::make()
                                ->title($count . ' job accodati')
                                ->body('Controlla tra 2-5 minuti.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('fetch_wikipedia_all')
                        ->label('📚 Fetch Wikipedia (batch)')
                        ->icon('heroicon-o-book-open')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->entity) {
                                    \App\Jobs\FetchWikipediaArticle::dispatch($record->entity, force: false);
                                    $count++;
                                }
                            }
                            Notification::make()->title($count . ' job accodati')->success()->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWikiPages::route('/'),
            'create' => Pages\CreateWikiPage::route('/create'),
            'edit' => Pages\EditWikiPage::route('/{record}/edit'),
        ];
    }
}
