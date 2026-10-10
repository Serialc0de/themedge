<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleResource\Pages;
use App\Models\Article;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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

            Forms\Components\Section::make('SEO & Anteprima')
                ->description('Controlla come apparirà il tuo articolo sui motori di ricerca')
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
                        ->content(fn (Forms\Get $get) => view('filament.forms.components.seo-check', ['get' => $get])),
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

                Tables\Filters\Filter::make('mine')
                    ->label('Solo i miei')
                    ->query(fn (Builder $query) => $query->where('author_id', Auth::id()))
                    ->toggle()
                    ->visible(fn () => ! Auth::user()->hasRole(['super_admin', 'editor'])),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Anteprima')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('info')
                    ->url(fn (Article $record) => config('app.public_site_url', 'https://themedge.com') . '/articolo/' . $record->slug)
                    ->openUrlInNewTab()
                    ->visible(fn (Article $record) => $record->status === 'published'),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()->label('Modifica'),

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
