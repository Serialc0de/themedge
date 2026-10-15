<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Entity extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'slug',
        'bio',
        'photo_url',
        'wikipedia_url', 'wikipedia_text', 'wikipedia_lang', 'wikipedia_fetched_at', 'wikipedia_status',
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
        'wikipedia_fetched_at' => 'datetime',
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
                $model->slug = \Illuminate\Support\Str::slug($model->name);
            }
        });

        static::updating(function (self $model) {
            if ($model->isDirty('name') && !$model->isDirty('slug')) {
                $model->slug = \Illuminate\Support\Str::slug($model->name);
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

    public function wikipediaChunks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WikipediaChunk::class)->orderBy('chunk_index');
    }

    /**
     * WikiPage collegate (relazione 1:N — in pratica 1:1).
     */
    public function wikiPages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\WikiPage::class);
    }

    /**
     * WikiPage principale (relazione 1:1).
     * Usa la prima wiki pubblicata, se disponibile.
     */
    public function wikiPage(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\WikiPage::class)
            ->where('status', 'published')
            ->latest();
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
     * Nome + tutti gli alias (per match nel contenuto).
     */
    public function getMatchableNamesAttribute(): array
    {
        $names = [$this->name];
        if (is_array($this->aliases)) {
            $names = array_merge($names, $this->aliases);
        }
        return array_values(array_unique(array_filter($names)));
    }

    /**
     * URL canonico: preferisci la wiki se esiste, altrimenti /entita/.
     */
    public function getUrlAttribute(): string
    {
        $wiki = $this->relationLoaded('wikiPage')
            ? $this->wikiPage
            : $this->wikiPage()->first();

        if ($wiki) {
            return '/wiki/' . $wiki->slug;
        }

        return '/entita/' . $this->slug;
    }

    /**
     * URL wiki se esiste (per uso esplicito nel frontend).
     */
    public function getWikiUrlAttribute(): ?string
    {
        $wiki = $this->relationLoaded('wikiPage')
            ? $this->wikiPage
            : $this->wikiPage()->first();

        return $wiki ? '/wiki/' . $wiki->slug : null;
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
}
