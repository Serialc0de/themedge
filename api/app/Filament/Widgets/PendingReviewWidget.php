<?php

namespace App\Filament\Widgets;

use App\Models\Article;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class PendingReviewWidget extends BaseWidget
{
    protected static ?string $heading = '⏳ Articoli in attesa di revisione';
    protected static ?int $sort = 5;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $pollingInterval = '30s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Article::query()
                    ->where('status', 'review')
                    ->with(['author', 'category'])
                    ->orderBy('review_requested_at', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Titolo')
                    ->weight('bold')
                    ->limit(50)
                    ->url(fn (Article $record) => "/admin/articles/{$record->id}/edit"),

                Tables\Columns\TextColumn::make('author.name')
                    ->label('Autore')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoria')
                    ->badge()
                    ->color(fn (Article $record) => $record->category?->color ?? 'gray'),

                Tables\Columns\TextColumn::make('review_requested_at')
                    ->label('Inviato il')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approva')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Article $record) {
                        $record->update([
                            'status' => 'approved',
                            'approved_at' => now(),
                            'approved_by' => Auth::id(),
                            'rejection_reason' => null,
                        ]);
                        Notification::make()
                            ->title("Approvato: {$record->title}")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Rifiuta')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
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
                            ->title("Rimandato: {$record->title}")
                            ->warning()
                            ->send();
                    }),

                Tables\Actions\Action::make('edit')
                    ->label('Apri')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Article $record) => "/admin/articles/{$record->id}/edit"),
            ])
            ->paginated(false)
            ->emptyStateHeading('Nessun articolo in review')
            ->emptyStateDescription('Tutti gli articoli sono stati revisionati. Ottimo lavoro!')
            ->emptyStateIcon('heroicon-o-check-badge');
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'editor']) ?? false;
    }
}
