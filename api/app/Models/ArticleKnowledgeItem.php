<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleKnowledgeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'type',
        'content',
        'source',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    // ============================================================
    // TYPES — Costanti
    // ============================================================

    public const TYPE_LABELS = [
        'known' => 'Cosa sappiamo',
        'unknown' => 'Cosa non sappiamo',
        'verifying' => 'Stiamo verificando',
    ];

    public const TYPE_ICONS = [
        'known' => '✅',
        'unknown' => '❓',
        'verifying' => '🔍',
    ];

    public const TYPE_COLORS = [
        'known' => 'green',
        'unknown' => 'gray',
        'verifying' => 'amber',
    ];

    public const TYPE_DESCRIPTIONS = [
        'known' => 'Fatti confermati da fonti ufficiali o verificabili.',
        'unknown' => 'Domande aperte a cui non abbiamo ancora risposta.',
        'verifying' => 'Informazioni in corso di verifica. Attendiamo conferme.',
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
        return self::TYPE_LABELS[$this->type] ?? 'Sconosciuto';
    }

    public function getTypeIconAttribute(): string
    {
        return self::TYPE_ICONS[$this->type] ?? '❓';
    }

    public function getTypeColorAttribute(): string
    {
        return self::TYPE_COLORS[$this->type] ?? 'gray';
    }

    public function getTypeDescriptionAttribute(): string
    {
        return self::TYPE_DESCRIPTIONS[$this->type] ?? '';
    }

    public function hasSource(): bool
    {
        return !empty($this->source);
    }

    // ============================================================
    // SCOPE
    // ============================================================

    public function scopeOrdered($query)
    {
        return $query->orderBy('type')->orderBy('order')->orderBy('id');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeKnown($query)
    {
        return $query->where('type', 'known');
    }

    public function scopeUnknown($query)
    {
        return $query->where('type', 'unknown');
    }

    public function scopeVerifying($query)
    {
        return $query->where('type', 'verifying');
    }
}
