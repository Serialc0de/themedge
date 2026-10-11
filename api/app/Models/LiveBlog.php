<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class LiveBlog extends Model
{
    use LogsActivity;

    protected $fillable = [
        'title', 'slug', 'description', 'featured_image',
        'category_id', 'author_id', 'status',
        'is_breaking', 'is_featured',
        'started_at', 'ended_at',
        'views', 'updates_count',
        'meta_title', 'meta_description',
    ];

    protected $casts = [
        'is_breaking' => 'boolean',
        'is_featured' => 'boolean',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'views' => 'integer',
        'updates_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($blog) {
            if (empty($blog->slug)) {
                $blog->slug = Str::slug($blog->title);
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'slug', 'status', 'started_at', 'ended_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName) => "Live blog {$eventName}: {$this->title}")
            ->useLogName('live_blog');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(LiveBlogUpdate::class)->where('is_published', true)->orderByDesc('published_at');
    }

    public function allUpdates(): HasMany
    {
        return $this->hasMany(LiveBlogUpdate::class);
    }

    public function subscribers(): HasMany
    {
        return $this->hasMany(LiveBlogSubscriber::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if (is_numeric($value)) {
            return $this->where('id', $value)->first();
        }

        return $this->where('slug', $value)->first();
    }

    public function isLive(): bool
    {
        return $this->status === 'live';
    }

    public function isEnded(): bool
    {
        return $this->status === 'ended';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function scopeLive($query)
    {
        return $query->where('status', 'live');
    }

    public function scopeEnded($query)
    {
        return $query->where('status', 'ended');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        if ($this->featured_image) {
            return config('app.public_asset_url', config('app.url')) . '/storage/' . ltrim($this->featured_image, '/');
        }

        return null;
    }

    public function getUrlAttribute(): string
    {
        return config('app.public_site_url', 'https://themedge.com') . '/diretta/' . $this->slug;
    }
}
