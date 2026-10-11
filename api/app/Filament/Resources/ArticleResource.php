<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleResource\Pages;
use App\Models\Article;
use App\Services\PushNotificationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;
    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    protected static ?string $navigationLabel = 'Articoli';
    protected static ?string $modelLabel = 'Articolo';
    protected static ?string $pluralModelLabel = 'Articoli';
    protected static ?string $navigationGroup = 'Contenuti';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Contenuto principale')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Titolo')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                            if ($operation === 'create') {
                                $set('slug', Str::slug($state));
                            }
                        })
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('slug')
                        ->label('Slug (URL)')
                        ->required()
                        ->maxLength(255)
                        ->unique(Article::class, 'slug', ignoreRecord: true)
                        ->live(onBlur: true)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('excerpt')
                        ->label('Sommario')
                        ->maxLength(500)
                        ->rows(3)
                        ->live(onBlur: true)
                        ->columnSpanFull(),

                    Forms\Components\RichEditor::make('content')
                        ->label('Contenuto')
                        ->required()
                        ->columnSpanFull()
                        ->live(onBlur: true)
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsDirectory('articles/content'),
                ]),

            Forms\Components\Section::make('Classificazione')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('category_id')
                        ->label('Categoria')
                        ->relationship('category', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->label('Nome categoria')
                                ->required()
                                ->maxLength(255),
                        ]),

                    Forms\Components\Select::make('tags')
                        ->label('Tag')
                        ->relationship('tags', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->label('Nome tag')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->columnSpan(2),
                ]),

            Forms\Components\Section::make('Immagini')
                ->description('Immagine di copertina e gallery dell\'articolo')
                ->schema([
                    Forms\Components\FileUpload::make('featured_image')
                        ->label('Immagine principale')
                        ->image()
                        ->imageEditor()
                        ->imageEditorAspectRatios(['16:10', '16:9', '4:3', '1:1'])
                        ->directory('articles/featured')
                        ->disk('public')
                        ->maxSize(5120)
                        ->live()
                        ->helperText('Formato consigliato: 16:10, max 5 MB. Appare nella hero e nei social.'),

                    Forms\Components\FileUpload::make('gallery_uploads')
                        ->label('Gallery immagini')
                        ->multiple()
                        ->image()
                        ->imageEditor()
                        ->reorderable()
                        ->panelLayout('grid')
                        ->directory('articles/gallery')
                        ->disk('public')
                        ->maxFiles(10)
                        ->maxSize(5120)
                        ->helperText('Fino a 10 immagini (max 5 MB ciascuna).')
                        ->dehydrated(false),
                ]),

            Forms\Components\Section::make('Autore e stato')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('author_id')
                        ->label('Autore')
                        ->relationship('author', 'name')
                        ->default(fn () => Auth::id())
                        ->required()
                        ->searchable()
                        ->preload()
                        ->disabled(fn () => ! Auth::user()->hasRole(['super_admin', 'editor']))
                        ->dehydrated(),

                    Forms\Components\Select::make('status')
                        ->label('Stato')
                        ->options([
                            'draft' => '📝 Bozza',
                            'review' => '⏳ In revisione',
                            'approved' => '✅ Approvato',
                            'scheduled' => '🕒 Programmato',
                            'published' => '🚀 Pubblicato',
                            'archived' => '📦 Archiviato',
                        ])
                        ->default('draft')
                        ->required()
                        ->disabled(fn () => ! Auth::user()->hasRole(['super_admin', 'editor']))
                        ->dehydrated(),

                    Forms\Components\Toggle::make('is_featured')
                        ->label('In evidenza')
                        ->helperText('Appare nella hero della homepage'),
                ]),

            Forms\Components\Section::make('Pubblicazione')
                ->columns(2)
                ->visible(fn () => Auth::user()->hasRole(['super_admin', 'editor']))
                ->schema([
                    Forms\Components\DateTimePicker::make('published_at')
                        ->label('Data pubblicazione')
                        ->seconds(false)
                        ->default(now()),

                    Forms\Components\DateTimePicker::make('scheduled_for')
                        ->label('Programmato per')
                        ->seconds(false)
                        ->helperText('Compila per programmare la pubblicazione automatica'),
                ]),

            Forms\Components\Section::make('🚨 Breaking News')
                ->description('Notizia urgente che apparirà in un banner')
                ->columns(2)
                ->visible(fn () => Auth::user()->hasRole(['super_admin', 'editor']))
                ->collapsed()
                ->schema([
                    Forms\Components\Toggle::make('is_breaking')
                        ->label('Attiva Breaking News')
                        ->helperText('Mostra il banner in cima al sito')
                        ->reactive()
                        ->live(),

                    Forms\Components\Select::make('breaking_type')
                        ->label('Tipo')
                        ->options([
                            'flash' => '⚡ Flash — 1 ora (urgente)',
                            'breaking' => '🚨 Breaking — 24 ore (importante)',
                            'developing' => '🔵 Developing — 72 ore (in sviluppo)',
                        ])
                        ->default('breaking')
                        ->visible(fn (Forms\Get $get) => (bool) $get('is_breaking'))
                        ->live(),

                    Forms\Components\DateTimePicker::make('breaking_expires_at')
                        ->label('Scade il')
                        ->seconds(false)
                        ->helperText('Lascia vuoto per default in base al tipo')
                        ->visible(fn (Forms\Get $get) => (bool) $get('is_breaking')),

                    Forms\Components\TextInput::make('breaking_priority')
                        ->label('Priorità')
                        ->numeric()
                        ->default(0)
                        ->helperText('Numero più alto = più importante')
                        ->visible(fn (Forms\Get $get) => (bool) $get('is_breaking')),

                    Forms\Components\ColorPicker::make('breaking_bg_color')
                        ->label('Colore sfondo (override)')
                        ->helperText('Lascia vuoto per usare il colore globale')
                        ->visible(fn (Forms\Get $get) => (bool) $get('is_breaking')),

                    Forms\Components\ColorPicker::make('breaking_text_color')
                        ->label('Colore testo (override)')
                        ->helperText('Lascia vuoto per usare il colore globale')
                        ->visible(fn (Forms\Get $get) => (bool) $get('is_breaking')),

                    Forms\Components\Toggle::make('breaking_show_badge')
                        ->label('Mostra badge ULTIM\'ORA sulle card')
                        ->default(true)
                        ->visible(fn (Forms\Get $get) => (bool) $get('is_breaking'))
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('SEO & Anteprima')
                ->description('Controlla come apparirà il tuo articolo sui motori di ricerca e sui social')
                ->columns(2)
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('meta_title')
                        ->label('Meta title (SEO)')
                        ->maxLength(70)
                        ->live(onBlur: true)
                        ->helperText('Max 60 caratteri. Se vuoto, usa il titolo.'),

                    Forms\Components\TextInput::make('meta_description')
                        ->label('Meta description (SEO)')
                        ->maxLength(170)
                        ->live(onBlur: true)
                        ->helperText('Max 160 caratteri. Se vuoto, usa il sommario.'),

                    Forms\Components\Placeholder::make('seo_check')
                        ->label('')
                        ->columnSpanFull()
                        ->content(function (Forms\Get $get) {
                            try {
                                return view('filament.forms.components.seo-check', ['get' => $get]);
                            } catch (\Throwable $e) {
                                \Log::warning('SEO check blade error: ' . $e->getMessage());
                                return new \Illuminate\Support\HtmlString(
                                    '<p style="color:#999;font-size:12px;padding:8px;">Anteprima SEO non disponibile</p>'
                                );
                            }
                        }),

                    Forms\Components\Placeholder::make('og_preview')
                        ->label('')
                        ->columnSpanFull()
                        ->content(function (Forms\Get $get) {
                            try {
                                return view('filament.forms.components.og-preview', ['get' => $get]);
                            } catch (\Throwable $e) {
                                \Log::warning('OG preview blade error: ' . $e->getMessage());
                                return new \Illuminate\Support\HtmlString(
                                    '<p style="color:#999;font-size:12px;padding:8px;">Anteprima social non disponibile</p>'
                                );
                            }
                        }),

                    Forms\Components\Placeholder::make('seo_score')
                        ->label('')
                        ->columnSpanFull()
                        ->content(function (Forms\Get $get, ?Article $record) {
                            try {
                                if (! $record) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<p style="color:#999;font-size:12px;padding:8px;">Salva l\'articolo per vedere il punteggio SEO</p>'
                                    );
                                }

                                $record->fill([
                                    'title' => $get('title') ?: $record->title,
                                    'slug' => $get('slug') ?: $record->slug,
                                    'excerpt' => $get('excerpt') ?: $record->excerpt,
                                    'content' => $get('content') ?: $record->content,
                                    'meta_title' => $get('meta_title') ?: $record->meta_title,
                                    'meta_description' => $get('meta_description') ?: $record->meta_description,
                                ]);

                                $seoScore = $record->seo_score;

                                if (! is_array($seoScore)) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<p style="color:#999;font-size:12px;padding:8px;">Punteggio SEO non calcolabile</p>'
                                    );
                                }

                                return view('filament.forms.components.seo-score', [
                                    'score' => $seoScore['score'] ?? 0,
                                    'grade' => $seoScore['grade'] ?? 'unknown',
                                    'checks' => $seoScore['checks'] ?? [],
                                ]);
                            } catch (\Throwable $e) {
                                \Log::warning('SEO score blade error: ' . $e->getMessage());
                                return new \Illuminate\Support\HtmlString(
                                    '<p style="color:#999;font-size:12px;padding:8px;">Punteggio SEO non disponibile</p>'
                                );
                            }
                        }),
                ]),

            Forms\Components\Section::make('Opzioni avanzate')
                ->collapsed()
                ->schema([
                    Forms\Components\Toggle::make('allow_comments')
                        ->label('Permetti commenti')
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('featured_image')
                    ->label('')
                    ->disk('public')
                    ->getStateUsing(fn (Article $record) => $record->featured_image_url)
                    ->height(50)
                    ->width(80)
                    ->defaultImageUrl('https://placehold.co/80x50/e5e5e5/999?text=No+img'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Titolo')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->description(fn (Article $record) => Str::limit($record->excerpt, 60))
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('author.name')
                    ->label('Autore')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoria')
                    ->badge()
                    ->color(fn (Article $record) => $record->category?->color ?? 'gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => '📝 Bozza',
                        'review' => '⏳ In review',
                        'approved' => '✅ Approvato',
                        'scheduled' => '🕒 Programmato',
                        'published' => '🚀 Pubblicato',
                        'archived' => '📦 Archiviato',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'review' => 'warning',
                        'approved' => 'info',
                        'scheduled' => 'primary',
                        'published' => 'success',
                        'archived' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('⭐')
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_breaking')
                    ->label('🚨')
                    ->boolean()
                    ->alignCenter()
                    ->color(fn (bool $state): string => $state ? 'danger' : 'gray')
                    ->tooltip('Breaking News')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Pubblicato')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('views')
                    ->label('Views')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('revisions_count')
                    ->label('Rev.')
                    ->getStateUsing(function (Article $record) {
                        return Activity::where('log_name', 'article')
                            ->where('subject_type', Article::class)
                            ->where('subject_id', $record->id)
                            ->count();
                    })
                    ->badge()
                    ->color('info')
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Ultima modifica')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Stato')
                    ->options([
                        'draft' => '📝 Bozza',
                        'review' => '⏳ In review',
                        'approved' => '✅ Approvato',
                        'scheduled' => '🕒 Programmato',
                        'published' => '🚀 Pubblicato',
                        'archived' => '📦 Archiviato',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('author_id')
                    ->label('Autore')
                    ->relationship('author', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => Auth::user()->hasRole(['super_admin', 'editor'])),

                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Categoria')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('is_featured')
                    ->label('Solo in evidenza')
                    ->query(fn (Builder $query) => $query->where('is_featured', true))
                    ->toggle(),

                Tables\Filters\Filter::make('is_breaking')
                    ->label('Solo breaking news')
                    ->query(fn (Builder $query) => $query->breaking())
                    ->toggle(),

                Tables\Filters\Filter::make('mine')
                    ->label('Solo i miei')
                    ->query(fn (Builder $query) => $query->where('author_id', Auth::id()))
                    ->toggle()
                    ->visible(fn () => ! Auth::user()->hasRole(['super_admin', 'editor'])),
            ])
            ->actions([
                \App\Filament\Actions\InsertEmbedAction::make(),
                Tables\Actions\Action::make('preview')
                    ->label('Anteprima')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('info')
                    ->url(fn (Article $record) => config('app.public_site_url', 'https://themedge.com') . '/articolo/' . $record->slug)
                    ->openUrlInNewTab()
                    ->visible(fn (Article $record) => $record->status === 'published'),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()->label('Modifica'),

                    Tables\Actions\Action::make('toggle_breaking')
                        ->label(fn (Article $record) => $record->is_breaking ? 'Disattiva Breaking' : 'Attiva Breaking')
                        ->icon(fn (Article $record) => $record->is_breaking ? 'heroicon-o-bell-slash' : 'heroicon-o-bell-alert')
                        ->color(fn (Article $record) => $record->is_breaking ? 'gray' : 'danger')
                        ->requiresConfirmation()
                        ->modalHeading(fn (Article $record) => $record->is_breaking ? 'Disattivare Breaking News?' : 'Attivare Breaking News?')
                        ->modalDescription(fn (Article $record) => $record->is_breaking
                            ? 'Il banner smetterà di apparire.'
                            : 'Il banner apparirà in cima al sito e verrà inviata una notifica push a tutti gli iscritti.')
                        ->visible(fn () => Auth::user()->hasRole(['super_admin', 'editor']))
                        ->action(function (Article $record) {
                            if ($record->is_breaking) {
                                $record->update(['is_breaking' => false]);
                                Notification::make()
                                    ->title('Breaking News disattivata')
                                    ->success()
                                    ->send();

                                return;
                            }

                            // Attivazione
                            $type = $record->breaking_type ?? 'breaking';
                            $hours = match ($type) {
                                'flash' => 1,
                                'breaking' => 24,
                                'developing' => 72,
                                default => 24,
                            };

                            $record->update([
                                'is_breaking' => true,
                                'breaking_type' => $type,
                                'breaking_expires_at' => $record->breaking_expires_at ?? now()->addHours($hours),
                                'status' => 'published',
                                'published_at' => $record->published_at ?? now(),
                            ]);

                            // Invia notifica push
                            $pushMsg = 'Push non configurato';
                            try {
                                $service = app(PushNotificationService::class);
                                $result = $service->sendBreakingNews($record);

                                if ($result['total'] === 0) {
                                    $pushMsg = '📱 Nessun iscritto alle notifiche';
                                } else {
                                    $pushMsg = "📱 Push: {$result['success']} inviate, {$result['failed']} fallite";
                                }
                            } catch (\Throwable $e) {
                                \Log::warning('Push breaking error: ' . $e->getMessage());
                                $pushMsg = '⚠️ Push non inviato';
                            }

                            Notification::make()
                                ->title('🚨 Breaking News attivata')
                                ->body("Il banner apparirà per {$hours} ore. {$pushMsg}")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('submit_review')
                        ->label('Invia in review')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Invia in revisione?')
                        ->modalDescription('L\'articolo verrà inviato agli editor per la revisione.')
                        ->visible(fn (Article $record) =>
                            $record->isDraft()
                            && ($record->author_id === Auth::id() || Auth::user()->hasRole(['super_admin', 'editor']))
                        )
                        ->action(function (Article $record) {
                            $record->update([
                                'status' => 'review',
                                'review_requested_at' => now(),
                            ]);
                            Notification::make()
                                ->title('Articolo inviato in review')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('approve')
                        ->label('Approva')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Article $record) =>
                            $record->isInReview() && Auth::user()->hasRole(['super_admin', 'editor'])
                        )
                        ->action(function (Article $record) {
                            $record->update([
                                'status' => 'approved',
                                'approved_at' => now(),
                                'approved_by' => Auth::id(),
                                'rejection_reason' => null,
                            ]);
                            Notification::make()
                                ->title('Articolo approvato')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('reject')
                        ->label('Rifiuta')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (Article $record) =>
                            $record->isInReview() && Auth::user()->hasRole(['super_admin', 'editor'])
                        )
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->label('Motivo del rifiuto')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (Article $record, array $data) {
                            $record->update([
                                'status' => 'draft',
                                'rejection_reason' => $data['rejection_reason'],
                                'review_requested_at' => null,
                            ]);
                            Notification::make()
                                ->title('Articolo rimandato all\'autore')
                                ->warning()
                                ->send();
                        }),

                    Tables\Actions\Action::make('publish')
                        ->label('Pubblica')
                        ->icon('heroicon-o-rocket-launch')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Article $record) =>
                            in_array($record->status, ['draft', 'approved', 'scheduled'])
                            && Auth::user()->hasRole(['super_admin', 'editor'])
                        )
                        ->action(function (Article $record) {
                            $record->update([
                                'status' => 'published',
                                'published_at' => now(),
                            ]);
                            Notification::make()
                                ->title('Articolo pubblicato')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('archive')
                        ->label('Archivia')
                        ->icon('heroicon-o-archive-box')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->visible(fn (Article $record) =>
                            $record->isPublished() && Auth::user()->hasRole(['super_admin', 'editor'])
                        )
                        ->action(function (Article $record) {
                            $record->update(['status' => 'archived']);
                            Notification::make()
                                ->title('Articolo archiviato')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('publish_selected')
                        ->label('Pubblica selezionati')
                        ->icon('heroicon-o-rocket-launch')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn () => Auth::user()->hasRole(['super_admin', 'editor']))
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'status' => 'published',
                                    'published_at' => now(),
                                ]);
                            }
                            Notification::make()
                                ->title('Articoli pubblicati')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make()->label('Elimina'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user && $user->hasRole(['super_admin', 'editor'])) {
            return $query;
        }

        return $query->where('author_id', $user?->id);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'review')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
