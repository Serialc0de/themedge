<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushNotificationLog extends Model
{
    protected $table = 'push_notifications_log';

    protected $fillable = [
        'title', 'body', 'url', 'icon', 'type',
        'article_id', 'live_blog_id',
        'sent_count', 'failed_count', 'total_subscriptions',
        'metadata', 'sent_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'total_subscriptions' => 'integer',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function liveBlog(): BelongsTo
    {
        return $this->belongsTo(LiveBlog::class);
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function getSuccessRateAttribute(): float
    {
        if ($this->total_subscriptions === 0) {
            return 0;
        }

        return round(($this->sent_count / $this->total_subscriptions) * 100, 1);
    }
}
