<?php

namespace App\Filament\Widgets;

use App\Models\Article;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopArticlesTable extends BaseWidget
{
    protected static ?string $heading = '🔥 Top 5 articoli più letti';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Article::query()
                    ->where('status', 'published')
                    ->orderByDesc('views')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Titolo')
                    ->limit(40)
                    ->weight('bold')
                    ->url(fn (Article $record) => "/admin/articles/{$record->id}/edit"),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoria')
                    ->badge()
                    ->color(fn (Article $record) => $record->category?->color ?? 'gray'),

                Tables\Columns\TextColumn::make('views')
                    ->label('Views')
                    ->numeric()
                    ->badge()
                    ->color('success')
                    ->sortable(),
            ])
            ->paginated(false)
            ->defaultSort('views', 'desc');
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'editor']) ?? false;
    }
}
