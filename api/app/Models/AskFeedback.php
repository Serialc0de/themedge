<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AskFeedback extends Model
{
    protected $table = 'ask_feedbacks';
    protected $fillable = [
        'ask_query_id', 'vote', 'comment',
        'ip', 'session_id',
        'is_reviewed', 'admin_note',
    ];

    protected $casts = [
        'vote' => 'integer',
        'is_reviewed' => 'boolean',
    ];

    public function askQuery(): BelongsTo
    {
        return $this->belongsTo(AskQuery::class);
    }

    public function isPositive(): bool
    {
        return $this->vote === 1;
    }

    public function isNegative(): bool
    {
        return $this->vote === -1;
    }

    public function getVoteLabelAttribute(): string
    {
        return match ($this->vote) {
            1 => '👍 Utile',
            -1 => '👎 Non utile',
            default => '—',
        };
    }
}
