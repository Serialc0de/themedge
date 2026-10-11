<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Article extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia, Searchable, LogsActivity;

    protected $fillable = [
        'title', 'slug', 'excerpt', 'content', 'featured_image',
        'status', 'published_at', 'author_id', 'category_id',
        'views', 'is_featured', 'allow_comments',
        'meta_title', 'meta_description',
        'review_requested_at', 'approved_at', 'approved_by',
        'rejection_reason', 'scheduled_for', 'revision_count',
        // Breaking News
        'is_breaking', 'breaking_expires_at', 'breaking_priority',
        'breaking_type', 'breaking_bg_color', 'breaking_text_color', 'breaking_show_badge',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'review_requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'scheduled_for' => 'datetime',
        'is_featured' => 'boolean',
        'allow_comments' => 'boolean',
        'views' => 'integer',
        'revision_count' => 'integer',
        // Breaking News
        'is_breaking' => 'boolean',
        'breaking_expires_at' => 'datetime',
        'breaking_priority' => 'integer',
        'breaking_show_badge' => 'boolean',
    ];

    // ============================================================
    // BOOT
    // ============================================================
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);
            }
        });

        static::updating(function ($article) {
            if ($article->isDirty('slug') && $article->getOriginal('slug')) {
                \App\Models\Redirect::firstOrCreate(
                    ['from' => '/articolo/' . $article->getOriginal('slug')],
                    [
                        'to' => '/articolo/' . $article->slug,
                        'status_code' => 301,
                        'is_active' => true,
                        'note' => 'Auto-generato: cambio slug articolo #' . $article->id,
                    ]
                );
            }
        });
    }

    // ============================================================
    // ACTIVITY LOG
    // ============================================================
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'title', 'slug', 'excerpt', 'content', 'status',
                'published_at', 'category_id', 'author_id', 'is_featured',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName) => "Articolo {$eventName}: {$this->title}")
            ->useLogName('article');
    }

    // ============================================================
    // RELAZIONI
    // ============================================================
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(Correction::class)
            ->where('is_published', true)
            ->orderByDesc('published_at');
    }

    public function polls(): HasMany
    {
        return $this->hasMany(Poll::class);
    }

    // ============================================================
    // ROUTING
    // ============================================================
    /**
     * Backend Filament (/admin/*) usa ID, frontend usa slug.
     */
    public function getRouteKeyName(): string
    {
        return request()->is('admin/*') ? 'id' : 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if (is_numeric($value)) {
            return $this->where('id', $value)->first();
        }

        return $this->where('slug', $value)->first();
    }

    // ============================================================
    // SCOPES
    // ============================================================
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where('published_at', '<=', now());
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeInReview($query)
    {
        return $query->where('status', 'review');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeBreaking($query)
    {
        return $query->where('is_breaking', true)
            ->where(function ($q) {
                $q->whereNull('breaking_expires_at')
                    ->orWhere('breaking_expires_at', '>', now());
            })
            ->where('status', 'published');
    }

    // ============================================================
    // HELPERS — STATO
    // ============================================================
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isInReview(): bool
    {
        return $this->status === 'review';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function canBeEditedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->hasRole(['super_admin', 'editor'])) {
            return true;
        }
        if ($this->author_id !== $user->id) {
            return false;
        }

        return in_array($this->status, ['draft', 'review']);
    }

    // ============================================================
    // HELPERS — BREAKING NEWS
    // ============================================================
    public function isBreakingActive(): bool
    {
        if (! $this->is_breaking) {
            return false;
        }

        if ($this->status !== 'published') {
            return false;
        }

        if ($this->breaking_expires_at && $this->breaking_expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function getBreakingDuration(): int
    {
        return match ($this->breaking_type) {
            'flash' => 10,
            'breaking' => 120,
            'developing' => 1440,
            default => 1440,
        };
    }

    public function getBreakingLabel(): string
    {
        return match ($this->breaking_type) {
            'flash' => '⚡ FLASH',
            'developing' => '🔵 IN SVILUPPO',
            default => "🚨 ULTIM'ORA",
        };
    }

    public function shouldShowBadge(): bool
    {
        if (! $this->breaking_show_badge) {
            return false;
        }

        return $this->isBreakingActive();
    }

    public function getBreakingBgColor(): string
    {
        if ($this->breaking_bg_color) {
            return $this->breaking_bg_color;
        }

        return match ($this->breaking_type) {
            'flash' => '#dc2626',
            'developing' => '#2563eb',
            default => '#b91c1c',
        };
    }

    // ============================================================
    // SEARCH
    // ============================================================
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => strip_tags($this->content ?? ''),
            'status' => $this->status,
            'published_at' => $this->published_at?->timestamp,
            'category' => $this->category?->name,
            'category_slug' => $this->category?->slug,
            'author' => $this->author?->name,
            'views' => $this->views,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status === 'published';
    }

    // ============================================================
    // MEDIA
    // ============================================================
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured_image')->singleFile();
        $this->addMediaCollection('gallery');
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        $media = $this->getFirstMediaUrl('featured_image');
        if ($media) {
            return $media;
        }

        if ($this->featured_image) {
            return config('app.public_asset_url', config('app.url')) . '/storage/' . ltrim($this->featured_image, '/');
        }

        return null;
    }
}
