<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LiveBlogResource\Pages;
use App\Filament\Resources\LiveBlogResource\RelationManagers;
use App\Models\LiveBlog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class LiveBlogResource extends Resource
{
    protected static ?string $model = LiveBlog::class;
    protected static ?string $navigationIcon = 'heroicon-o-bolt';
    protected static ?string $navigationLabel = 'Dirette';
    protected static ?string $navigationGroup = 'Contenuti';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Diretta';
    protected static ?string $pluralModelLabel = 'Dirette Live';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informazioni generali')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Titolo')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                            if ($operation === 'create') {
                                $set('slug', \Illuminate\Support\Str::slug($state));
                            }
                        })
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('slug')
                        ->label('Slug (URL)')
                        ->required()
                        ->maxLength(255)
                        ->unique(LiveBlog::class, 'slug', ignoreRecord: true)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('description')
                        ->label('Descrizione')
                        ->rows(3)
                        ->maxLength(500)
                        ->columnSpanFull(),

                    Forms\Components\FileUpload::make('featured_image')
                        ->label('Immagine di copertina')
                        ->image()
                        ->imageEditor()
                        ->directory('live-blogs')
                        ->disk('public')
                        ->maxSize(5120),
                ]),

            Forms\Components\Section::make('Pubblicazione')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Stato')
                        ->options([
                            'draft' => '📝 Bozza',
                            'live' => '🔴 In diretta',
                            'ended' => '✅ Conclusa',
                            'archived' => '📦 Archiviata',
                        ])
                        ->default('draft')
                        ->required()
                        ->live(),

                    Forms\Components\Select::make('category_id')
                        ->label('Categoria')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload(),

                    Forms\Components\Select::make('author_id')
                        ->label('Autore')
                        ->relationship('author', 'name')
                        ->default(fn () => Auth::id())
                        ->searchable()
                        ->preload(),

                    Forms\Components\DateTimePicker::make('started_at')
                        ->label('Iniziata il')
                        ->seconds(false),

                    Forms\Components\DateTimePicker::make('ended_at')
                        ->label('Conclusa il')
                        ->seconds(false)
                        ->visible(fn (Forms\Get $get) => $get('status') === 'ended'),

                    Forms\Components\Toggle::make('is_featured')
                        ->label('In evidenza'),

                    Forms\Components\Toggle::make('is_breaking')
                        ->label('Mostra come Breaking News')
                        ->helperText('Appare nel banner breaking news quando è live'),
                ]),

            Forms\Components\Section::make('SEO')
                ->collapsed()
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('meta_title')
                        ->label('Meta title')
                        ->maxLength(70),

                    Forms\Components\TextInput::make('meta_description')
                        ->label('Meta description')
                        ->maxLength(170),
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
                    ->getStateUsing(fn (LiveBlog $record) => $record->featured_image_url)
                    ->height(50)
                    ->width(80),

                Tables\Columns\TextColumn::make('title')
                    ->label('Titolo')
                    ->searchable()
                    ->limit(50)
                    ->weight('bold')
                    ->description(fn (LiveBlog $record) => $record->description ? \Illuminate\Support\Str::limit($record->description, 60) : null),

                Tables\Columns\TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => '📝 Bozza',
                        'live' => '🔴 IN DIRETTA',
                        'ended' => '✅ Conclusa',
                        'archived' => '📦 Archiviata',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'live' => 'danger',
                        'ended' => 'success',
                        'archived' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('updates_count')
                    ->label('Update')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('views')
                    ->label('Views')
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_breaking')
                    ->label('🚨')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('started_at')
                    ->label('Iniziata')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creata')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Stato')
                    ->options([
                        'draft' => '📝 Bozza',
                        'live' => '🔴 In diretta',
                        'ended' => '✅ Conclusa',
                        'archived' => '📦 Archiviata',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('go_live')
                    ->label('Vai in diretta')
                    ->icon('heroicon-o-play')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Avviare la diretta?')
                    ->modalDescription('Lo stato diventerà "In diretta" e sarà visibile pubblicamente.')
                    ->visible(fn (LiveBlog $record) => in_array($record->status, ['draft', 'ended']) && Auth::user()->hasRole(['super_admin', 'editor']))
                    ->action(function (LiveBlog $record) {
                        $record->update([
                            'status' => 'live',
                            'started_at' => $record->started_at ?? now(),
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('🔴 Diretta avviata')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('end_live')
                    ->label('Chiudi diretta')
                    ->icon('heroicon-o-stop')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Chiudere la diretta?')
                    ->modalDescription('Gli utenti non potranno più seguire gli aggiornamenti in tempo reale.')
                    ->visible(fn (LiveBlog $record) => $record->status === 'live' && Auth::user()->hasRole(['super_admin', 'editor']))
                    ->action(function (LiveBlog $record) {
                        $record->update([
                            'status' => 'ended',
                            'ended_at' => now(),
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('✅ Diretta conclusa')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('view')
                    ->label('Apri')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('info')
                    ->url(fn (LiveBlog $record) => config('app.public_site_url', 'https://themedge.com') . '/diretta/' . $record->slug)
                    ->openUrlInNewTab()
                    ->visible(fn (LiveBlog $record) => in_array($record->status, ['live', 'ended'])),

                Tables\Actions\EditAction::make()->label('Modifica'),
                Tables\Actions\DeleteAction::make()->label('Elimina'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\UpdatesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLiveBlogs::route('/'),
            'create' => Pages\CreateLiveBlog::route('/create'),
            'edit' => Pages\EditLiveBlog::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole(['super_admin', 'editor', 'author']) ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'live')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
