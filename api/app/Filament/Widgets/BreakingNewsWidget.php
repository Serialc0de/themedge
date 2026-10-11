<?php

namespace App\Filament\Widgets;

use App\Models\Article;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class BreakingNewsWidget extends BaseWidget
{
    protected static ?string $heading = '🚨 Breaking News attive';
    protected static ?int $sort = 0;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Article::query()
                    ->breaking()
                    ->orderByDesc('breaking_priority')
                    ->orderByDesc('published_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Titolo')
                    ->weight('bold')
                    ->limit(60)
                    ->url(fn (Article $record) => "/admin/articles/{$record->id}/edit"),

                Tables\Columns\TextColumn::make('breaking_priority')
                    ->label('Priorità')
                    ->badge()
                    ->color('danger')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('breaking_expires_at')
                    ->label('Scade')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Mai')
                    ->badge()
                    ->color(fn (Article $record) => $record->breaking_expires_at && $record->breaking_expires_at->diffInHours(now()) < 2
                        ? 'warning'
                        : 'gray'
                    ),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Pubblicato')
                    ->since(),
            ])
            ->paginated(false)
            ->emptyStateHeading('Nessuna breaking news attiva')
            ->emptyStateDescription('Attiva una breaking news dalla tabella articoli.')
            ->emptyStateIcon('heroicon-o-bell-slash');
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'editor']) ?? false;
    }
}
