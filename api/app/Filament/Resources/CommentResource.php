<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommentResource\Pages;
use App\Models\Comment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Commenti';
    protected static ?string $navigationGroup = 'Contenuti';
    protected static ?int $navigationSort = 8;
    protected static ?string $modelLabel = 'Commento';
    protected static ?string $pluralModelLabel = 'Commenti';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Commento')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('author_name')
                        ->label('Nome')
                        ->disabled(),

                    Forms\Components\TextInput::make('author_email')
                        ->label('Email')
                        ->disabled(),

                    Forms\Components\TextInput::make('author_website')
                        ->label('Sito web')
                        ->disabled()
                        ->placeholder('—'),

                    Forms\Components\TextInput::make('ip_address')
                        ->label('IP')
                        ->disabled()
                        ->placeholder('—'),

                    Forms\Components\Textarea::make('content')
                        ->label('Contenuto')
                        ->rows(5)
                        ->disabled()
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('article.title')
                        ->label('Articolo')
                        ->disabled(),

                    Forms\Components\TextInput::make('created_at')
                        ->label('Data')
                        ->disabled(),
                ]),

            Forms\Components\Section::make('Moderazione')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Stato')
                        ->options([
                            'pending' => '⏳ In attesa',
                            'approved' => '✅ Approvato',
                            'rejected' => '❌ Rifiutato',
                            'spam' => '🚫 Spam',
                        ])
                        ->required()
                        ->reactive(),

                    Forms\Components\Toggle::make('is_pinned')
                        ->label('Fissa in alto')
                        ->helperText('Il commento apparirà in cima'),

                    Forms\Components\Textarea::make('moderation_note')
                        ->label('Nota di moderazione (interna)')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('gravatar')
                    ->label('')
                    ->getStateUsing(fn (Comment $record) => $record->gravatar_url)
                    ->circular()
                    ->size(40),

                Tables\Columns\TextColumn::make('author_name')
                    ->label('Autore')
                    ->searchable()
                    ->description(fn (Comment $record) => $record->author_email)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('content')
                    ->label('Commento')
                    ->limit(80)
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('article.title')
                    ->label('Articolo')
                    ->limit(30)
                    ->url(fn (Comment $record) => $record->article_id
                        ? route('filament.admin.resources.articles.edit', $record->article_id)
                        : null
                    )
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => '⏳ In attesa',
                        'approved' => '✅ Approvato',
                        'rejected' => '❌ Rifiutato',
                        'spam' => '🚫 Spam',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'spam' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('is_pinned')
                    ->label('📌')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Stato')
                    ->options([
                        'pending' => '⏳ In attesa',
                        'approved' => '✅ Approvato',
                        'rejected' => '❌ Rifiutato',
                        'spam' => '🚫 Spam',
                    ])
                    ->default('pending'),

                Tables\Filters\Filter::make('today')
                    ->label('Solo oggi')
                    ->query(fn ($query) => $query->whereDate('created_at', today())),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approva')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Comment $record) => $record->status !== 'approved')
                    ->action(function (Comment $record) {
                        $record->approve(Auth::id());
                        \Filament\Notifications\Notification::make()
                            ->title('Commento approvato')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Rifiuta')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Comment $record) => $record->status !== 'rejected')
                    ->form([
                        Forms\Components\Textarea::make('note')
                            ->label('Motivo (interno)')
                            ->rows(2),
                    ])
                    ->action(function (Comment $record, array $data) {
                        $record->reject(Auth::id(), $data['note'] ?? null);
                        \Filament\Notifications\Notification::make()
                            ->title('Commento rifiutato')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('spam')
                    ->label('Spam')
                    ->icon('heroicon-o-no-symbol')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (Comment $record) => $record->status !== 'spam')
                    ->action(function (Comment $record) {
                        $record->markAsSpam(Auth::id());
                        \Filament\Notifications\Notification::make()
                            ->title('Commento marcato come spam')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reply')
                    ->label('Rispondi')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('info')
                    ->visible(fn (Comment $record) => $record->status === 'approved')
                    ->form([
                        Forms\Components\Textarea::make('content')
                            ->label('Risposta')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (Comment $record, array $data) {
                        $admin = Auth::user();
                        Comment::create([
                            'article_id' => $record->article_id,
                            'parent_id' => $record->id,
                            'author_name' => $admin->name . ' (Redazione)',
                            'author_email' => $admin->email,
                            'content' => $data['content'],
                            'status' => 'approved',
                            'approved_at' => now(),
                            'moderated_by' => $admin->id,
                            'moderated_at' => now(),
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Risposta pubblicata')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()->label('Modifica'),
                Tables\Actions\DeleteAction::make()->label('Elimina'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_all')
                        ->label('Approva selezionati')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->approve(Auth::id());
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('spam_all')
                        ->label('Segna come spam')
                        ->icon('heroicon-o-no-symbol')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->markAsSpam(Auth::id());
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make()->label('Elimina'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComments::route('/'),
            'edit' => Pages\EditComment::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole(['super_admin', 'editor']) ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'pending')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
