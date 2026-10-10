<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Spatie\Activitylog\Models\Activity;

class RecentActivityWidget extends BaseWidget
{
    protected static ?string $heading = '📋 Ultime attività';
    protected static ?int $sort = 10;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $pollingInterval = '30s';

    public function table(Table $table): Table
    {
        return $table
            ->query(Activity::query()->latest()->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Quando')
                    ->since(),

                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Utente')
                    ->badge()
                    ->color('gray')
                    ->placeholder('Sistema'),

                Tables\Columns\TextColumn::make('log_name')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'article' => 'success',
                        'user' => 'warning',
                        'category' => 'info',
                        'tag' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('description')
                    ->label('Azione')
                    ->limit(80)
                    ->wrap(),
            ])
            ->paginated(false);
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'editor']) ?? false;
    }
}
