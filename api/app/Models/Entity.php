<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Entity extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'slug',
        'bio',
        'photo_url',
        'aliases',
        'metadata',
        'is_published',
        'order',
    ];

    protected $casts = [
        'aliases' => 'array',
        'metadata' => 'array',
        'is_published' => 'boolean',
        'order' => 'integer',
    ];

    // ============================================================
    // TYPES — Costanti
    // ============================================================

    public const TYPE_LABELS = [
        'person' => '🧑 Persona',
        'organization' => '🏛️ Organizzazione',
        'location' => '🌍 Luogo',
        'event' => '📅 Evento',
        'concept' => '💡 Concetto',
        'product' => '📦 Prodotto',
        'publication' => '📰 Pubblicazione',
        'work' => '🎬 Opera',
    ];

    public const TYPE_ICONS = [
        'person' => '🧑',
        'organization' => '🏛️',
        'location' => '🌍',
        'event' => '📅',
        'concept' => '💡',
        'product' => '📦',
        'publication' => '📰',
        'work' => '🎬',
    ];

    public const TYPE_COLORS = [
        'person' => 'blue',
        'organization' => 'purple',
        'location' => 'green',
        'event' => 'red',
        'concept' => 'amber',
        'product' => 'cyan',
        'publication' => 'gray',
        'work' => 'pink',
    ];

    // ============================================================
    // BOOT — Auto-genera slug
    // ============================================================

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->slug) && !empty($model->name)) {
                $model->slug = Str::slug($model->name);
            }
        });

        static::updating(function (self $model) {
            if ($model->isDirty('name') && !$model->isDirty('slug')) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    // ============================================================
    // ROUTE KEY
    // ============================================================

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ============================================================
    // RELAZIONI
    // ============================================================

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_entity')
            ->withPivot('mentions', 'context', 'order')
            ->withTimestamps();
    }

    // ============================================================
    // HELPER
    // ============================================================

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function getTypeIconAttribute(): string
    {
        return self::TYPE_ICONS[$this->type] ?? '❓';
    }

    public function getTypeColorAttribute(): string
    {
        return self::TYPE_COLORS[$this->type] ?? 'gray';
    }

    /**
     * Ritorna il nome + tutti gli alias (per match nel contenuto).
     */
    public function getMatchableNamesAttribute(): array
    {
        $names = [$this->name];
        if (is_array($this->aliases)) {
            $names = array_merge($names, $this->aliases);
        }
        return array_values(array_unique(array_filter($names)));
    }

    public function getUrlAttribute(): string
    {
        return '/entita/' . $this->slug;
    }

    // ============================================================
    // SCOPE
    // ============================================================

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ============================================================
    // WIKI
    // ============================================================

    public function wikiPages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\WikiPage::class);
    }

}
