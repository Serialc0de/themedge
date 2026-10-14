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
                        ->fileAttachmentsDirectory('articles/content')
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

            Forms\Components\Section::make('📖 Approfondimenti curati')
                ->description('Articoli selezionati dalla redazione come letture correlate.')
                ->collapsed()
                ->schema([
                    Forms\Components\Repeater::make('relatedCurated')
                        ->label('Articoli correlati')
                        ->addActionLabel('Aggiungi articolo')
                        ->relationship()
                        ->saveRelationshipsUsing(function ($component, $state) {
                            // Scarta item senza related_article_id
                            $clean = collect($state)->filter(function ($item) {
                                return !empty($item['related_article_id']);
                            })->map(function ($item) {
                                return [
                                    'related_article_id' => $item['related_article_id'],
                                    'note' => $item['note'] ?? null,
                                    'order' => $item['order'] ?? 0,
                                ];
                            })->values()->toArray();

                            $component->getRecord()->relatedCurated()->sync($clean);
                        })
                        ->reorderable()
                        ->collapsible()
                        ->defaultItems(0)
                        ->schema([
                            Forms\Components\Select::make('related_article_id')
                                ->label('Articolo')
                                ->options(fn () => \App\Models\Article::query()
                                    ->orderByDesc('published_at')
                                    ->limit(100)
                                    ->pluck('title', 'id')
                                    ->toArray())
                                ->searchable()
                                ->required(),

                            Forms\Components\Textarea::make('note')
                                ->label('Nota editoriale (opzionale)')
                                ->rows(2)
                                ->maxLength(500)
                                ->placeholder('Perche consigliamo questo articolo?'),
                        ])
                        ->columns(1)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('📚 Fonti e riferimenti')
                ->description('Documenti, studi, articoli e link che supportano le affermazioni dell\'articolo.')
                ->collapsed()
                ->schema([
                    Forms\Components\Repeater::make('sources')
                        ->relationship()
                        ->label('Fonti')
                        ->addActionLabel('Aggiungi fonte')
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                        ->defaultItems(0)
                        ->schema([
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\TextInput::make('url')
                                        ->label('URL')
                                        ->url()
                                        ->required()
                                        ->maxLength(2048)
                                        ->columnSpan(2),

                                    Forms\Components\Select::make('type')
                                        ->label('Tipo')
                                        ->options([
                                            'official' => '📄 Fonte ufficiale',
                                            'study' => '🎓 Studio / Ricerca',
                                            'news' => '📰 Testata giornalistica',
                                            'social' => '🐦 Social media',
                                            'archive' => '🗄️ Archivio',
                                            'video' => '🎥 Video',
                                            'dataset' => '📊 Dataset',
                                            'other' => '🔗 Altro',
                                        ])
                                        ->default('news')
                                        ->required(),

                                    Forms\Components\TextInput::make('title')
                                        ->label('Titolo')
                                        ->required()
                                        ->maxLength(500)
                                        ->columnSpan(2),

                                    Forms\Components\TextInput::make('publisher')
                                        ->label('Editore / Autore')
                                        ->maxLength(255)
                                        ->placeholder('Es. Il Sole 24 Ore, ISTAT'),

                                    Forms\Components\DatePicker::make('published_at')
                                        ->label('Data pubblicazione fonte'),

                                    Forms\Components\Textarea::make('note')
                                        ->label('Nota redazione')
                                        ->rows(2)
                                        ->maxLength(1000)
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('archived_url')
                                        ->label('URL archiviato (archive.org)')
                                        ->url()
                                        ->maxLength(2048)
                                        ->columnSpan(2)
                                        ->helperText('Link alla versione archiviata'),

                                    Forms\Components\Toggle::make('is_verified')
                                        ->label('Verificata')
                                        ->helperText('La redazione ha verificato la fonte'),
                                ]),
                        ])
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('✅ Fact-check')
                ->description('Affermazioni verificate con fonti attendibili.')
                ->icon('heroicon-o-check-badge')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\Repeater::make('factChecks')
                        ->relationship()
                        ->label('Affermazioni verificate')
                        ->schema([
                            Forms\Components\Textarea::make('claim')
                                ->label('Affermazione')
                                ->required()
                                ->rows(2)
                                ->columnSpanFull(),

                            Forms\Components\Select::make('verdict')
                                ->label('Verdetto')
                                ->options(\App\Models\ArticleFactCheck::VERDICT_LABELS)
                                ->required()
                                ->default('in_review'),

                            Forms\Components\TextInput::make('source_name')
                                ->label('Fonte')
                                ->maxLength(200),

                            Forms\Components\TextInput::make('source_url')
                                ->label('URL fonte')
                                ->url()
                                ->maxLength(500),

                            Forms\Components\Textarea::make('note')
                                ->label('Nota editoriale')
                                ->rows(2)
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('order')
                                ->label('Ordine')
                                ->numeric()
                                ->default(0),
                        ])
                        ->columns(2)
                        ->reorderable('order')
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['claim'] ?? 'Nuovo fact-check')
                        ->addActionLabel('Aggiungi fact-check')
                        ->defaultItems(0),
                ])
                ->columns(1),
            Forms\Components\Section::make('🧠 Cosa sappiamo / non sappiamo')
                ->description('Struttura l\'informazione in 3 blocchi: fatti confermati, domande aperte, verifiche in corso.')
                ->icon('heroicon-o-light-bulb')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\Repeater::make('knowledgeItems')
                        ->relationship()
                        ->label('Punti di conoscenza')
                        ->schema([
                            Forms\Components\Select::make('type')
                                ->label('Tipo')
                                ->options(\App\Models\ArticleKnowledgeItem::TYPE_LABELS)
                                ->required()
                                ->default('known'),

                            Forms\Components\TextInput::make('order')
                                ->label('Ordine')
                                ->numeric()
                                ->default(0),

                            Forms\Components\Textarea::make('content')
                                ->label('Contenuto')
                                ->required()
                                ->rows(2)
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('source')
                                ->label('Fonte (opzionale)')
                                ->maxLength(200)
                                ->columnSpanFull()
                                ->placeholder('Es. Comunicato ufficiale, Ministero, testimone...'),
                        ])
                        ->columns(2)
                        ->reorderable('order')
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => isset($state['content']) && $state['content'] !== '' ? mb_substr($state['content'], 0, 60) . (mb_strlen($state['content']) > 60 ? '…' : '') : 'Nuovo punto')
                        ->addActionLabel('Aggiungi punto')
                        ->defaultItems(0),
                ])
                ->columns(1),
            Forms\Components\Section::make('📅 Timeline contesto')
                ->description('Eventi storici collegati all\'articolo, in ordine cronologico.')
                ->icon('heroicon-o-clock')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\Repeater::make('timelineEvents')
                        ->relationship()
                        ->label('Eventi della timeline')
                        ->schema([
                            Forms\Components\DatePicker::make('event_date')
                                ->label('Data evento')
                                ->required()
                                ->displayFormat('d/m/Y')
                                ->native(false),

                            Forms\Components\TextInput::make('title')
                                ->label('Titolo')
                                ->required()
                                ->maxLength(300),

                            Forms\Components\Textarea::make('description')
                                ->label('Descrizione (opzionale)')
                                ->rows(2)
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('source_name')
                                ->label('Fonte (opzionale)')
                                ->maxLength(200),

                            Forms\Components\TextInput::make('source_url')
                                ->label('URL fonte (opzionale)')
                                ->url()
                                ->maxLength(500),

                            Forms\Components\Toggle::make('is_key_event')
                                ->label('Evento chiave')
                                ->helperText('Evidenzia questo evento nella timeline.')
                                ->default(false),

                            Forms\Components\TextInput::make('order')
                                ->label('Ordine (stessa data)')
                                ->numeric()
                                ->default(0),
                        ])
                        ->columns(2)
                        ->reorderable('order')
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => isset($state['title']) && $state['title'] !== '' ? mb_substr($state['title'], 0, 60) . (mb_strlen($state['title']) > 60 ? '…' : '') : 'Nuovo evento')
                        ->addActionLabel('Aggiungi evento')
                        ->defaultItems(0),
                ])
                ->columns(1),
            Forms\Components\Section::make('🎬 Scrollytelling')
                ->description('Storia visiva con animazioni allo scroll. Ogni sezione appare quando l\'utente scrolla.')
                ->icon('heroicon-o-sparkles')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\Repeater::make('scrollySections')
                        ->relationship()
                        ->label('Sezioni scrollytelling')
                        ->schema([
                            Forms\Components\Select::make('type')
                                ->label('Tipo sezione')
                                ->options(\App\Models\ScrollySection::TYPE_LABELS)
                                ->required()
                                ->default('text')
                                ->live(),

                            Forms\Components\TextInput::make('order')
                                ->label('Ordine')
                                ->numeric()
                                ->default(0),

                            Forms\Components\Textarea::make('content')
                                ->label('Contenuto (HTML)')
                                ->rows(4)
                                ->columnSpanFull()
                                ->helperText('Testo della sezione. Supporta HTML: <p>, <strong>, <em>, <br>.'),

                            Forms\Components\TextInput::make('media_url')
                                ->label('Media URL')
                                ->url()
                                ->maxLength(500)
                                ->columnSpanFull()
                                ->helperText('URL immagine/video (per tipi: image, sticky-image, video, hero).'),

                            Forms\Components\TextInput::make('media_alt')
                                ->label('Alt text')
                                ->maxLength(300)
                                ->columnSpanFull(),

                            Forms\Components\KeyValue::make('data')
                                ->label('Dati aggiuntivi (JSON)')
                                ->keyLabel('Chiave')
                                ->valueLabel('Valore')
                                ->addActionLabel('Aggiungi dato')
                                ->columnSpanFull()
                                ->helperText('Per chart: data=[{x,y}...], per stat: value=1000, per timeline: events=[...]'),

                            Forms\Components\Select::make('settings.animation')
                                ->label('Animazione')
                                ->options(\App\Models\ScrollySection::ANIMATION_LABELS)
                                ->default('fade-up'),

                            Forms\Components\TextInput::make('settings.duration')
                                ->label('Durata (ms)')
                                ->numeric()
                                ->default(800)
                                ->minValue(100)
                                ->maxValue(3000),

                            Forms\Components\TextInput::make('settings.delay')
                                ->label('Delay (ms)')
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->maxValue(2000),
                        ])
                        ->columns(2)
                        ->reorderable('order')
                        ->collapsible()
                        ->itemLabel(function (array $state): ?string {
                            $type = \App\Models\ScrollySection::TYPE_LABELS[$state['type'] ?? 'text'] ?? 'Sezione';
                            $content = $state['content'] ?? '';
                            $preview = $content ? mb_substr(strip_tags($content), 0, 50) : '';
                            return $type . ($preview ? ' — ' . $preview : '');
                        })
                        ->addActionLabel('Aggiungi sezione scrollytelling')
                        ->defaultItems(0),
                ])
                ->columns(1),
            Forms\Components\Section::make('🏷️ Entità collegate')
                ->description('Persone, organizzazioni, luoghi citati nell\'articolo. Diventeranno link cliccabili.')
                ->icon('heroicon-o-tag')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\Repeater::make('entities')
                        ->relationship()
                        ->label('Entità')
                        ->schema([
                            Forms\Components\Select::make('entity_id')
                                ->label('Entità')
                                ->options(fn () => \App\Models\Entity::query()->published()->ordered()->pluck('name', 'id')->toArray())
                                ->searchable()
                                ->required()
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('mentions')
                                ->label('Menzioni')
                                ->numeric()
                                ->default(1)
                                ->minValue(1),

                            Forms\Components\TextInput::make('order')
                                ->label('Ordine')
                                ->numeric()
                                ->default(0),

                            Forms\Components\Textarea::make('context')
                                ->label('Contesto (opzionale)')
                                ->rows(2)
                                ->columnSpanFull()
                                ->helperText('Frase in cui appare l\'entità (per riferimento editoriale).'),
                        ])
                        ->columns(3)
                        ->reorderable('order')
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => '🏷️ ' . ($state['entity_id'] ?? 'Entità'))
                        ->addActionLabel('Collega entità')
                        ->defaultItems(0),
                ])
                ->columns(1),
            Forms\Components\Section::make('✅ Fact-check automatico (claim rilevati)')
                ->description('Affermazioni estratta dall\'AI. Verifica manualmente e approva.')
                ->icon('heroicon-o-check-badge')
                ->collapsible()
                ->visible(fn ($record) => $record && $record->hasClaims())
                ->schema([
                    Forms\Components\Livewire::make('claims_panel')
                        ->component(\App\Livewire\ClaimsPanel::class)
                        ->columnSpanFull(),
                ])
                ->columns(1),
            Forms\Components\Section::make('🎯 SEO AI (suggerimenti)')
                ->description('Suggerimenti SEO generati dall\'AI. Clicca "Applica" per usarli.')
                ->icon('heroicon-o-sparkles')
                ->collapsible()
                ->collapsed()
                ->visible(fn ($record) => $record && $record->hasSeoSuggestions())
                ->schema([
                    Forms\Components\Livewire::make('seo_suggestions_panel')
                        ->component(\App\Livewire\SeoSuggestionsPanel::class)
                        ->columnSpanFull(),
                ])
                ->columns(1),
            Forms\Components\Section::make('🔍 Entità rilevate (AI)')
                ->description('Entità trovate dall\'AI. Collega quelle esistenti o creane di nuove.')
                ->icon('heroicon-o-sparkles')
                ->collapsible()
                ->visible(fn ($record) => $record && $record->hasDetectedEntities())
                ->schema([
                    Forms\Components\Livewire::make('detected_entities_panel')
                        ->component(\App\Livewire\DetectedEntitiesPanel::class)
                        ->columnSpanFull(),
                ])
                ->columns(1),
            Forms\Components\Section::make('🎯 Highlight Policy')
                ->description('Controlla come gli utenti possono evidenziare il testo di questo articolo.')
                ->columns(2)
                ->visible(fn () => Auth::user()->hasRole(['super_admin', 'editor', 'author']))
                ->collapsed()
                ->schema([
                    Forms\Components\Select::make('highlight_policy_id')
                        ->label('Policy')
                        ->relationship('highlightPolicy', 'label')
                        ->searchable()
                        ->preload()
                        ->placeholder('Eredita da categoria/autore')
                        ->helperText("Se non specificata, eredita la policy dalla categoria o dall'autore.")
                        ->columnSpanFull(),

                    Forms\Components\KeyValue::make('highlight_settings')
                        ->label('Override granulari (JSON)')
                        ->keyLabel('Chiave')
                        ->valueLabel('Valore')
                        ->addActionLabel('Aggiungi override')
                        ->reorderable()
                        ->helperText('Es. enabled, false / allow_public, false. Vuoto = eredita dalla policy.')
                        ->columnSpanFull(),

                    Forms\Components\DateTimePicker::make('highlight_until')
                        ->label('Disattiva fino a')
                        ->seconds(false)
                        ->helperText('Se valorizzato nel futuro, gli highlight sono temporaneamente disattivati.')
                        ->columnSpan(1),

                    Forms\Components\Toggle::make('highlight_locked')
                        ->label('Lock policy')
                        ->helperText('Se attivo, gli autori non possono modificare la policy.')
                        ->columnSpan(1),
                ]),

            Forms\Components\Section::make('Opzioni avanzate')
                ->collapsed()
                ->schema([
                    Forms\Components\Toggle::make('allow_comments')
                        ->label('Permetti commenti')
                        ->default(true),
                ]),

            Forms\Components\Section::make('🤖 Trasparenza AI')
                ->description("Dichiara in modo trasparente l'uso di intelligenza artificiale in questo articolo.")
                ->icon('heroicon-o-sparkles')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\Select::make('ai_disclosure')
                        ->label('Livello di uso AI')
                        ->options(\App\Models\Article::AI_DISCLOSURE_LABELS)
                        ->default('none')
                        ->required()
                        ->live()
                        ->helperText(fn (\Filament\Forms\Get $get) => \App\Models\Article::AI_DISCLOSURE_DESCRIPTIONS[$get('ai_disclosure')] ?? ''),

                    Forms\Components\Textarea::make('ai_disclosure_note')
                        ->label('Nota integrativa (opzionale)')
                        ->placeholder('Es. "Bozza generata con GPT-4, fact-checking e riscrittura umana."')
                        ->rows(3)
                        ->visible(fn (\Filament\Forms\Get $get) => $get('ai_disclosure') !== 'none')
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Forms\Components\Section::make('🤖 AI Summary (TL;DR)')
                ->description('Riassunto generato automaticamente dall\'AI. Puoi modificarlo manualmente se necessario.')
                ->icon('heroicon-o-sparkles')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\Placeholder::make('ai_summary_status')
                        ->label('Stato')
                        ->content(fn ($record) => $record && $record->hasAiSummary()
                            ? new \Illuminate\Support\HtmlString(
                                '<span style="color: #16a34a; font-weight: bold;">✅ Generato</span> '
                                . '<span style="color: #6b7280; font-size: 0.85em;">'
                                . 'il ' . $record->ai_summary_generated_at?->format('d/m/Y H:i')
                                . ' con ' . e($record->ai_summary_model ?? 'N/A')
                                . ($record->isAiSummaryStale() ? ' ⚠️ <em>da rigenerare</em>' : '')
                                . '</span>'
                            )
                            : new \Illuminate\Support\HtmlString(
                                '<span style="color: #dc2626;">❌ Non generato</span> '
                                . '<span style="color: #6b7280; font-size: 0.85em;">Clicca "✨ Genera TL;DR" in alto a destra.</span>'
                            ))
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('ai_summary.tldr')
                        ->label('📌 TL;DR')
                        ->rows(3)
                        ->columnSpanFull()
                        ->helperText('Frase breve (2-3 righe) che riassume l\'articolo.')
                        ->visible(fn ($record) => $record && $record->hasAiSummary()),

                    Forms\Components\TagsInput::make('ai_summary.key_points')
                        ->label('🔑 Punti chiave')
                        ->placeholder('Aggiungi un punto e premi Invio')
                        ->columnSpanFull()
                        ->helperText('3-5 punti chiave dell\'articolo.')
                        ->visible(fn ($record) => $record && $record->hasAiSummary()),

                    Forms\Components\Textarea::make('ai_summary.summary')
                        ->label('📄 Sommario esteso')
                        ->rows(5)
                        ->columnSpanFull()
                        ->helperText('Paragrafo di 3-4 frasi che riassume l\'articolo in modo più dettagliato.')
                        ->visible(fn ($record) => $record && $record->hasAiSummary()),
                ])
                ->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('audio_status_col')
                    ->label('Audio')
                    ->getStateUsing(fn (\App\Models\Article $record) => match($record->audio_status) {
                        'ready' => '✅ ' . ($record->audio_duration_formatted ?? 'pronto'),
                        'generating' => '⏳ in corso',
                        'failed' => '❌ errore',
                        default => '—',
                    })
                    ->badge()
                    ->color(fn (\App\Models\Article $record) => match($record->audio_status) {
                        'ready' => 'success',
                        'generating' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable()
                    ->sortable(false),

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
                    \Filament\Tables\Actions\Action::make('genera_audio')
                        ->label('🎙️ Genera audio')
                        ->icon('heroicon-o-microphone')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Genera audio TTS')
                        ->modalDescription('Verrà generato un file MP3 con la voce Paola (it-IT). Il processo richiede ~30s.')
                        ->modalSubmitActionLabel('Genera')
                        ->action(function (\App\Models\Article $record) {
                            if ($record->audio_status === 'generating') {
                                \Filament\Notifications\Notification::make()
                                    ->title('Già in generazione')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            \App\Jobs\GenerateArticleAudio::dispatch($record, force: true);

                            \Filament\Notifications\Notification::make()
                                ->title('Generazione avviata')
                                ->body('Il file audio sarà pronto tra ~30 secondi.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (\App\Models\Article $record) => $record->status === 'published'),

                // RIMOSSO: InsertEmbedAction (spostato nel RichEditor)
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
