<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'claim_text',
        'context',
        'status',
        'source_url',
        'source_name',
        'note',
        'ai_suggested_verdict',
        'ai_suggested_source_name',
        'ai_suggested_source_url',
        'ai_confidence',
        'ai_model',
        'ai_generated_at',
        'approved_by',
        'approved_at',
        'fact_check_id',
        'order',
    ];

    protected $casts = [
        'ai_confidence' => 'decimal:2',
        'ai_generated_at' => 'datetime',
        'approved_at' => 'datetime',
        'order' => 'integer',
    ];

    // ============================================================
    // STATUS — Costanti
    // ============================================================

    public const STATUS_LABELS = [
        'pending' => '⏳ Da verificare',
        'verified' => '✅ Verificato',
        'in_review' => '⚠️ In verifica',
        'false' => '❌ Falso',
        'discarded' => '🗑️ Scartato',
    ];

    public const STATUS_COLORS = [
        'pending' => 'gray',
        'verified' => 'success',
        'in_review' => 'warning',
        'false' => 'danger',
        'discarded' => 'gray',
    ];

    // ============================================================
    // RELAZIONI
    // ============================================================

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function factCheck(): BelongsTo
    {
        return $this->belongsTo(ArticleFactCheck::class, 'fact_check_id');
    }

    // ============================================================
    // HELPER
    // ============================================================

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return in_array($this->status, ['verified', 'in_review', 'false']);
    }

    public function isDiscarded(): bool
    {
        return $this->status === 'discarded';
    }

    public function hasAiSuggestion(): bool
    {
        return !empty($this->ai_suggested_verdict);
    }

    public function getAiConfidencePercentAttribute(): ?int
    {
        if ($this->ai_confidence === null) return null;
        return (int) round($this->ai_confidence * 100);
    }

    // ============================================================
    // SCOPE
    // ============================================================

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->whereIn('status', ['verified', 'in_review', 'false']);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('id');
    }
}
