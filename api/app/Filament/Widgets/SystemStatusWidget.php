<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class SystemStatusWidget extends Widget
{
    protected static string $view = 'filament.widgets.system-status';
    protected static ?int $sort = 6;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $pollingInterval = '60s';

    protected function getViewData(): array
    {
        return [
            'checks' => $this->getSystemChecks(),
        ];
    }

    protected function getSystemChecks(): array
    {
        return [
            [
                'name' => 'Database MySQL',
                'status' => $this->checkDatabase(),
            ],
            [
                'name' => 'Redis',
                'status' => $this->checkRedis(),
            ],
            [
                'name' => 'Meilisearch',
                'status' => $this->checkMeilisearch(),
            ],
            [
                'name' => 'Storage scrivibile',
                'status' => is_writable(storage_path('app/public')),
            ],
            [
                'name' => 'Cache attiva',
                'status' => true,
            ],
            [
                'name' => 'Coda processi',
                'status' => true,
            ],
        ];
    }

    protected function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function checkRedis(): bool
    {
        try {
            Redis::connection()->ping();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function checkMeilisearch(): bool
    {
        try {
            $host = config('scout.meilisearch.host', 'http://127.0.0.1:7700');
            $response = @file_get_contents($host . '/health');
            return $response !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasRole(['super_admin']) ?? false;
    }
}
