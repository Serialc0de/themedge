<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LiveBlogSubscriber extends Model
{
    protected $fillable = [
        'live_blog_id', 'email', 'name', 'ip_address',
        'subscribed_at', 'unsubscribed_at', 'token',
    ];

    protected $casts = [
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($subscriber) {
            if (empty($subscriber->token)) {
                $subscriber->token = Str::random(64);
            }
            if (empty($subscriber->subscribed_at)) {
                $subscriber->subscribed_at = now();
            }
        });
    }

    public function liveBlog(): BelongsTo
    {
        return $this->belongsTo(LiveBlog::class);
    }

    public function isActive(): bool
    {
        return is_null($this->unsubscribed_at);
    }
}
