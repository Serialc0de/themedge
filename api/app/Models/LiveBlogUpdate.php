<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveBlogUpdate extends Model
{
    protected $fillable = [
        'live_blog_id', 'author_id', 'content', 'type', 'title',
        'image', 'image_caption', 'is_pinned', 'is_published',
        'notify_subscribers', 'published_at',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_published' => 'boolean',
        'notify_subscribers' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($update) {
            if (empty($update->published_at)) {
                $update->published_at = now();
            }
        });

        static::created(function ($update) {
            $update->liveBlog?->increment('updates_count');
        });

        static::deleted(function ($update) {
            $update->liveBlog?->decrement('updates_count');
        });
    }

    public function liveBlog(): BelongsTo
    {
        return $this->belongsTo(LiveBlog::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'breaking' => '🚨 Ultim\'ora',
            'analysis' => '📊 Analisi',
            'quote' => '💬 Dichiarazione',
            'photo' => '📸 Foto',
            'video' => '🎥 Video',
            'result' => '🏆 Risultato',
            default => '📝 Aggiornamento',
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            'breaking' => 'red',
            'analysis' => 'blue',
            'quote' => 'purple',
            'photo' => 'green',
            'video' => 'pink',
            'result' => 'amber',
            default => 'gray',
        };
    }

    public function getImageUrlAttribute(): ?string
    {
        if ($this->image) {
            return config('app.public_asset_url', config('app.url')) . '/storage/' . ltrim($this->image, '/');
        }

        return null;
    }
}
