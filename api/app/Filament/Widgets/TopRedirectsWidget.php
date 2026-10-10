<?php

namespace App\Filament\Widgets;

use App\Models\Redirect;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopRedirectsWidget extends BaseWidget
{
    protected static ?string $heading = '🔀 Redirect più usati';
    protected static ?int $sort = 12;
    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Redirect::query()
                    ->where('is_active', true)
                    ->where('hits', '>', 0)
                    ->orderByDesc('hits')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('from')
                    ->label('Da')
                    ->limit(25)
                    ->color('danger')
                    ->icon('heroicon-m-arrow-right-start-on-rectangle'),

                Tables\Columns\TextColumn::make('to')
                    ->label('A')
                    ->limit(25)
                    ->color('success')
                    ->icon('heroicon-m-arrow-right-end-on-rectangle'),

                Tables\Columns\TextColumn::make('hits')
                    ->label('Hit')
                    ->badge()
                    ->color('success')
                    ->alignCenter(),
            ])
            ->paginated(false)
            ->emptyStateHeading('Nessun redirect usato')
            ->emptyStateDescription('I redirect appariranno qui quando verranno attivati.')
            ->emptyStateIcon('heroicon-o-arrow-uturn-right');
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'editor']) ?? false;
    }
}
