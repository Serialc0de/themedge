<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AskQuery extends Model
{
    protected $fillable = [
        'question', 'question_hash',
        'answer', 'answer_length', 'sources_count', 'sources',
        'model', 'search_ms', 'total_ms',
        'ip', 'session_id', 'user_agent', 'user_id',
        'was_cached',
    ];

    protected $casts = [
        'sources' => 'array',
        'answer_length' => 'integer',
        'sources_count' => 'integer',
        'search_ms' => 'integer',
        'total_ms' => 'integer',
        'was_cached' => 'boolean',
    ];

    // ============================================================
    // RELAZIONI
    // ============================================================

    public function feedbacks(): HasMany
    {
        return $this->hasMany(AskFeedback::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeWithNegativeFeedback($query)
    {
        return $query->whereHas('feedbacks', function ($q) {
            $q->where('vote', -1);
        });
    }

    // ============================================================
    // HELPERS
    // ============================================================

    public static function hashQuestion(string $question): string
    {
        $normalized = mb_strtolower(trim(preg_replace('/\s+/', ' ', $question)));
        return hash('sha256', $normalized);
    }

    public function getPositiveFeedbacksCountAttribute(): int
    {
        return $this->feedbacks()->where('vote', 1)->count();
    }

    public function getNegativeFeedbacksCountAttribute(): int
    {
        return $this->feedbacks()->where('vote', -1)->count();
    }
}
