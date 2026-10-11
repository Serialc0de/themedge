<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HighlightPolicy extends Model
{
    protected $fillable = [
        'name',
        'label',
        'description',
        'settings',
        'is_system',
        'color',
        'priority',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_system' => 'boolean',
        'priority' => 'integer',
    ];

    // ============================================================
    // RELATIONS
    // ============================================================

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // ============================================================
    // SETTINGS ACCESSOR (con default sensati)
    // ============================================================

    public function setting(string $key, mixed $default = null): mixed
    {
        $settings = $this->settings ?? [];
        return $settings[$key] ?? $default;
    }

    public function getEnabledAttribute(): bool
    {
        return (bool) $this->setting('enabled', true);
    }

    public function getAllowPublicAttribute(): bool
    {
        return (bool) $this->setting('allow_public', true);
    }

    public function getAllowReactionsAttribute(): bool
    {
        return (bool) $this->setting('allow_reactions', true);
    }

    public function getAllowNotesAttribute(): bool
    {
        return (bool) $this->setting('allow_notes', true);
    }

    public function getAllowShareImageAttribute(): bool
    {
        return (bool) $this->setting('allow_share_image', false);
    }

    public function getAllowHeatmapAttribute(): bool
    {
        return (bool) $this->setting('allow_heatmap', true);
    }

    public function getMaxFragmentLengthAttribute(): int
    {
        return (int) $this->setting('max_fragment_length', config('highlights.limits.max_fragment_length', 2000));
    }

    public function getRateLimitPerDayAttribute(): int
    {
        return (int) $this->setting('rate_limit_per_day', config('highlights.limits.create_rate.attempts', 30));
    }

    // ============================================================
    // FACTORY: settings default
    // ============================================================

    public static function defaultSettings(): array
    {
        return [
            'enabled' => true,
            'allow_public' => true,
            'allow_reactions' => true,
            'allow_notes' => true,
            'allow_share_image' => false,
            'allow_heatmap' => true,
            'max_fragment_length' => 2000,
            'rate_limit_per_day' => 30,
        ];
    }

    // ============================================================
    // TO ARRAY (per API config)
    // ============================================================

    public function toConfigArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'allow_public' => $this->allow_public,
            'allow_reactions' => $this->allow_reactions,
            'allow_notes' => $this->allow_notes,
            'allow_share_image' => $this->allow_share_image,
            'allow_heatmap' => $this->allow_heatmap,
            'max_fragment_length' => $this->max_fragment_length,
            'rate_limit_per_day' => $this->rate_limit_per_day,
        ];
    }
}
