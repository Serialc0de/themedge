<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrollySection extends Model
{
    use HasFactory;

    protected $table = 'article_scrolly_sections';

    protected $fillable = [
        'article_id',
        'order',
        'type',
        'content',
        'media_url',
        'media_alt',
        'data',
        'settings',
    ];

    protected $casts = [
        'order' => 'integer',
        'data' => 'array',
        'settings' => 'array',
    ];

    // ============================================================
    // TYPES — Costanti
    // ============================================================

    public const TYPE_LABELS = [
        'hero' => '🎬 Hero (apertura)',
        'text' => '📝 Testo',
        'image' => '🖼️ Immagine',
        'sticky-image' => '📌 Immagine sticky (testo scorre)',
        'sticky-text' => '📌 Testo sticky (immagini scorrono)',
        'video' => '🎥 Video',
        'chart' => '📊 Grafico',
        'map' => '🗺️ Mappa',
        'quote' => '💬 Citazione',
        'stat' => '🔢 Numero (count-up)',
        'timeline' => '📅 Timeline',
    ];

    public const ANIMATION_LABELS = [
        'fade' => 'Fade',
        'fade-up' => 'Fade + slide up',
        'fade-down' => 'Fade + slide down',
        'fade-left' => 'Fade + slide left',
        'fade-right' => 'Fade + slide right',
        'zoom' => 'Zoom',
        'none' => 'Nessuna',
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

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function getAnimationAttribute(): string
    {
        return $this->settings['animation'] ?? 'fade-up';
    }

    public function getDurationAttribute(): int
    {
        return (int) ($this->settings['duration'] ?? 800);
    }

    public function getDelayAttribute(): int
    {
        return (int) ($this->settings['delay'] ?? 0);
    }

    public function requiresMedia(): bool
    {
        return in_array($this->type, ['image', 'sticky-image', 'video', 'hero']);
    }

    public function requiresContent(): bool
    {
        return in_array($this->type, [
            'text', 'image', 'sticky-image', 'sticky-text',
            'video', 'quote', 'chart', 'map', 'timeline', 'hero',
        ]);
    }

    // ============================================================
    // SCOPE
    // ============================================================

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('id');
    }
}
