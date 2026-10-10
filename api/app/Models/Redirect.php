<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Redirect extends Model
{
    use LogsActivity;

    protected $fillable = [
        'from',
        'to',
        'status_code',
        'hits',
        'last_hit_at',
        'is_active',
        'note',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'hits' => 'integer',
        'last_hit_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['from', 'to', 'status_code', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName) => "Redirect {$eventName}: {$this->from} → {$this->to}")
            ->useLogName('redirect');
    }

    /**
     * Normalizza il path: rimuove slash iniziale/finale, query string, forza minuscole.
     */
    public static function normalizePath(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?: $path;
        $path = '/' . ltrim($path, '/');
        return rtrim($path, '/') ?: '/';
    }

    /**
     * Trova un redirect attivo per il path dato.
     */
    public static function findForPath(string $path): ?self
    {
        $normalized = self::normalizePath($path);

        return static::query()
            ->where('is_active', true)
            ->where(function ($query) use ($normalized, $path) {
                $query->where('from', $normalized)
                    ->orWhere('from', $path)
                    ->orWhere('from', ltrim($normalized, '/'));
            })
            ->first();
    }
}
