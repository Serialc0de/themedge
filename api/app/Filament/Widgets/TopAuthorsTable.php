<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopAuthorsTable extends BaseWidget
{
    protected static ?string $heading = '✍️ Top 5 autori';
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->withCount(['articles' => function ($q) {
                        $q->where('status', 'published');
                    }])
                    ->withSum('articles', 'views')
                    ->whereHas('articles')
                    ->orderByDesc('articles_count')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('')
                    ->disk('public')
                    ->circular()
                    ->size(36)
                    ->defaultImageUrl(fn (User $record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=dc2626&color=fff'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Autore')
                    ->weight('bold')
                    ->description(fn (User $record) => $record->job_title),

                Tables\Columns\TextColumn::make('articles_count')
                    ->label('Pubbl.')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('articles_sum_views')
                    ->label('Views')
                    ->numeric()
                    ->badge()
                    ->color('success')
                    ->alignCenter(),
            ])
            ->paginated(false);
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'editor']) ?? false;
    }
}
