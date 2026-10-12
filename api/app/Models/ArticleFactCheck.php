<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleFactCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'claim',
        'verdict',
        'source_url',
        'source_name',
        'note',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    // ============================================================
    // VERDICT — Costanti
    // ============================================================

    public const VERDICT_LABELS = [
        'verified' => 'Verificato',
        'in_review' => 'In verifica',
        'false' => 'Falso',
    ];

    public const VERDICT_ICONS = [
        'verified' => '✅',
        'in_review' => '⚠️',
        'false' => '❌',
    ];

    public const VERDICT_COLORS = [
        'verified' => 'green',
        'in_review' => 'amber',
        'false' => 'red',
    ];

    public const VERDICT_DESCRIPTIONS = [
        'verified' => 'Affermazione verificata con fonte attendibile.',
        'in_review' => 'Affermazione in corso di verifica. Attendiamo conferme ufficiali.',
        'false' => 'Affermazione smentita dalle fonti ufficiali.',
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

    public function getVerdictLabelAttribute(): string
    {
        return self::VERDICT_LABELS[$this->verdict] ?? 'Sconosciuto';
    }

    public function getVerdictIconAttribute(): string
    {
        return self::VERDICT_ICONS[$this->verdict] ?? '❓';
    }

    public function getVerdictColorAttribute(): string
    {
        return self::VERDICT_COLORS[$this->verdict] ?? 'gray';
    }

    public function getVerdictDescriptionAttribute(): string
    {
        return self::VERDICT_DESCRIPTIONS[$this->verdict] ?? '';
    }

    public function hasSource(): bool
    {
        return !empty($this->source_url) || !empty($this->source_name);
    }

    // ============================================================
    // SCOPE
    // ============================================================

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('id');
    }

    public function scopeVerified($query)
    {
        return $query->where('verdict', 'verified');
    }

    public function scopeFalse($query)
    {
        return $query->where('verdict', 'false');
    }
}
