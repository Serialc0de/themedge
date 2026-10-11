<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReaderHighlight extends Model
{
    protected $table = 'reader_highlights';

    protected $fillable = [
        'reader_id',
        'article_id',
        'text_fragment',
        'context_before',
        'context_after',
        'start_offset',
        'end_offset',
        'color',
        'note',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'start_offset' => 'integer',
        'end_offset' => 'integer',
        'reactions_count' => 'integer',
    ];

    protected $hidden = [
        'reader_id',
    ];

    public function reader(): BelongsTo
    {
        return $this->belongsTo(ReaderIdentity::class, 'reader_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(HighlightReaction::class, 'highlight_id');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeForArticle($query, int $articleId)
    {
        return $query->where('article_id', $articleId);
    }

    public function scopeByReader($query, int $readerId)
    {
        return $query->where('reader_id', $readerId);
    }

    public function refreshReactionsCount(): void
    {
        $this->reactions_count = $this->reactions()->count();
        $this->saveQuietly();
    }
}
