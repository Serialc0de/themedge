<?php

namespace App\Filament\Widgets;

use App\Models\Comment;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class PendingCommentsWidget extends BaseWidget
{
    protected static ?string $heading = '💬 Commenti in attesa di moderazione';
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $pollingInterval = '60s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Comment::query()
                    ->where('status', 'pending')
                    ->with(['article'])
                    ->orderBy('created_at', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('author_name')
                    ->label('Autore')
                    ->weight('bold')
                    ->description(fn (Comment $record) => $record->author_email),

                Tables\Columns\TextColumn::make('content')
                    ->label('Commento')
                    ->limit(80)
                    ->wrap(),

                Tables\Columns\TextColumn::make('article.title')
                    ->label('Articolo')
                    ->limit(30)
                    ->url(fn (Comment $record) => $record->article_id
                        ? route('filament.admin.resources.articles.edit', $record->article_id)
                        : null
                    ),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Inviato')
                    ->since(),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approva')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (Comment $record) {
                        $record->approve(Auth::id());
                        Notification::make()
                            ->title('Commento approvato')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('spam')
                    ->label('Spam')
                    ->icon('heroicon-o-no-symbol')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function (Comment $record) {
                        $record->markAsSpam(Auth::id());
                        Notification::make()
                            ->title('Commento marcato come spam')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('edit')
                    ->label('Gestisci')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Comment $record) => "/admin/comments/{$record->id}/edit"),
            ])
            ->paginated(false)
            ->emptyStateHeading('Nessun commento in attesa')
            ->emptyStateDescription('Tutti i commenti sono stati moderati.')
            ->emptyStateIcon('heroicon-o-check-badge');
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'editor']) ?? false;
    }
}
