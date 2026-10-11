<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Comment extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'article_id', 'parent_id', 'author_name', 'author_email',
        'author_website', 'content', 'status', 'ip_address', 'user_agent',
        'moderation_note', 'moderated_by', 'moderated_at', 'is_pinned', 'likes_count', 'approved_at',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'likes_count' => 'integer',
        'moderated_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'is_pinned'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName) => "Commento {$eventName}")
            ->useLogName('comment');
    }

    // ============================================================
    // RELAZIONI
    // ============================================================
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at');
    }

    public function approvedReplies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')
            ->where('status', 'approved')
            ->orderBy('created_at');
    }

    public function moderatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    // ============================================================
    // SCOPES
    // ============================================================
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSpam($query)
    {
        return $query->where('status', 'spam');
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    // ============================================================
    // HELPERS
    // ============================================================
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSpam(): bool
    {
        return $this->status === 'spam';
    }

    public function getGravatarUrlAttribute(): string
    {
        $hash = md5(strtolower(trim($this->author_email)));
        return 'https://www.gravatar.com/avatar/' . $hash . '?d=mp&s=80';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => '⏳ In attesa',
            'approved' => '✅ Approvato',
            'rejected' => '❌ Rifiutato',
            'spam' => '🚫 Spam',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'spam' => 'gray',
            default => 'gray',
        };
    }

    // ============================================================
    // ACTIONS
    // ============================================================
    public function approve(?int $userId = null): void
    {
        $this->update([
            'status' => 'approved',
            'moderated_by' => $userId,
            'moderated_at' => now(),
            'approved_at' => now(),
        ]);
    }

    public function reject(?int $userId = null, ?string $note = null): void
    {
        $this->update([
            'status' => 'rejected',
            'moderated_by' => $userId,
            'moderated_at' => now(),
            'moderation_note' => $note,
        ]);
    }

    public function markAsSpam(?int $userId = null): void
    {
        $this->update([
            'status' => 'spam',
            'moderated_by' => $userId,
            'moderated_at' => now(),
        ]);
    }
}
