<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleTimelineEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'event_date',
        'title',
        'description',
        'source_url',
        'source_name',
        'is_key_event',
        'order',
    ];

    protected $casts = [
        'event_date' => 'date',
        'is_key_event' => 'boolean',
        'order' => 'integer',
    ];

    // ============================================================
    // RELAZIONI
    // ============================================================

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    // ============================================================
    // HELPER
    // ============================================================

    public function getFormattedDateAttribute(): string
    {
        return $this->event_date?->format('d/m/Y') ?? '';
    }

    public function getShortDateAttribute(): string
    {
        return $this->event_date?->format('d/m') ?? '';
    }

    public function getIsoDateAttribute(): string
    {
        return $this->event_date?->toIso8601String() ?? '';
    }

    public function hasSource(): bool
    {
        return !empty($this->source_url) || !empty($this->source_name);
    }

    // ============================================================
    // SCOPE
    // ============================================================

    /**
     * Ordina gli eventi in ordine cronologico decrescente (più recente prima).
     */
    public function scopeChronological($query)
    {
        return $query->orderByDesc('event_date')->orderBy('order');
    }

    /**
     * Ordina gli eventi in ordine cronologico crescente (più vecchio prima).
     */
    public function scopeHistorical($query)
    {
        return $query->orderBy('event_date')->orderBy('order');
    }

    public function scopeKeyEvents($query)
    {
        return $query->where('is_key_event', true);
    }

    public function scopeOrdered($query)
    {
        return $this->scopeChronological($query);
    }
}
