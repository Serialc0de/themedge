<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrokenLink extends Model
{
    protected $fillable = [
        'url',
        'url_hash',
        'status_code',
        'error_message',
        'article_id',
        'article_title',
        'last_checked_at',
        'check_count',
        'is_resolved',
    ];

    protected $casts = [
        'last_checked_at' => 'datetime',
        'is_resolved' => 'boolean',
        'check_count' => 'integer',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public static function hashUrl(string $url): string
    {
        return hash('sha256', $url);
    }
}
