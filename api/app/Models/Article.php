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
	'is_breaking', 'breaking_expires_at', 'breaking_priority',
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
	'is_breaking' => 'boolean',
        'breaking_expires_at' => 'datetime',
        'breaking_priority' => 'integer',
    ];

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
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Backend Filament (/admin/*) usa ID, frontend usa slug.
     */
    public function getRouteKeyName(): string
    {
        return request()->is('admin/*') ? 'id' : 'slug';
    }

    /**
     * Fallback per resolveRouteBinding: accetta sia ID che slug.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if (is_numeric($value)) {
            return $this->where('id', $value)->first();
        }

        return $this->where('slug', $value)->first();
    }

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
	    /**
     * Calcola il punteggio SEO (0-100) dell'articolo.
     */
    public function getSeoScoreAttribute(): array
    {
        $checks = [];
        $totalScore = 0;

        // 1. Meta title (15 punti)
        $metaTitle = $this->meta_title ?: $this->title;
        $titleLen = mb_strlen($metaTitle);
        $titleScore = 0;
        if ($titleLen >= 40 && $titleLen <= 60) {
            $titleScore = 15;
            $titleStatus = 'good';
        } elseif ($titleLen >= 30 || $titleLen <= 70) {
            $titleScore = 10;
            $titleStatus = 'warning';
        } elseif ($titleLen > 0) {
            $titleScore = 5;
            $titleStatus = 'warning';
        } else {
            $titleStatus = 'error';
        }
        $totalScore += $titleScore;
        $checks[] = [
            'label' => 'Meta title',
            'value' => "{$titleLen} caratteri",
            'hint' => 'Consigliato: 40-60 caratteri',
            'status' => $titleStatus,
            'score' => $titleScore,
            'max' => 15,
        ];

        // 2. Meta description (15 punti)
        $metaDesc = $this->meta_description ?: $this->excerpt ?: '';
        $descLen = mb_strlen($metaDesc);
        $descScore = 0;
        if ($descLen >= 120 && $descLen <= 160) {
            $descScore = 15;
            $descStatus = 'good';
        } elseif ($descLen >= 80 || $descLen <= 180) {
            $descScore = 10;
            $descStatus = 'warning';
        } elseif ($descLen > 0) {
            $descScore = 5;
            $descStatus = 'warning';
        } else {
            $descStatus = 'error';
        }
        $totalScore += $descScore;
        $checks[] = [
            'label' => 'Meta description',
            'value' => "{$descLen} caratteri",
            'hint' => 'Consigliato: 120-160 caratteri',
            'status' => $descStatus,
            'score' => $descScore,
            'max' => 15,
        ];

        // 3. Lunghezza contenuto (15 punti)
        $content = strip_tags($this->content ?? '');
        $wordCount = str_word_count($content);
        $contentScore = 0;
        if ($wordCount >= 300) {
            $contentScore = 15;
            $contentStatus = 'good';
        } elseif ($wordCount >= 150) {
            $contentScore = 10;
            $contentStatus = 'warning';
        } elseif ($wordCount > 0) {
            $contentScore = 5;
            $contentStatus = 'warning';
        } else {
            $contentStatus = 'error';
        }
        $totalScore += $contentScore;
        $checks[] = [
            'label' => 'Lunghezza contenuto',
            'value' => "{$wordCount} parole",
            'hint' => 'Minimo consigliato: 300 parole',
            'status' => $contentStatus,
            'score' => $contentScore,
            'max' => 15,
        ];

        // 4. Slug (10 punti)
        $slugLen = mb_strlen($this->slug ?? '');
        $slugScore = 0;
        if ($slugLen > 0 && $slugLen <= 60) {
            $slugScore = 10;
            $slugStatus = 'good';
        } elseif ($slugLen > 0 && $slugLen <= 80) {
            $slugScore = 5;
            $slugStatus = 'warning';
        } else {
            $slugStatus = 'error';
        }
        $totalScore += $slugScore;
        $checks[] = [
            'label' => 'Slug URL',
            'value' => $this->slug ?: 'non impostato',
            'hint' => 'Max 60 caratteri',
            'status' => $slugStatus,
            'score' => $slugScore,
            'max' => 10,
        ];

        // 5. Immagine copertina (10 punti)
        $hasImage = ! empty($this->featured_image) || $this->hasMedia('featured_image');
        $imgScore = $hasImage ? 10 : 0;
        $totalScore += $imgScore;
        $checks[] = [
            'label' => 'Immagine di copertina',
            'value' => $hasImage ? 'presente' : 'assente',
            'hint' => 'Consigliata per social e hero',
            'status' => $hasImage ? 'good' : 'warning',
            'score' => $imgScore,
            'max' => 10,
        ];

        // 6. Alt text (10 punti)
        $altText = '';
        if ($this->hasMedia('featured_image')) {
            $altText = $this->getFirstMedia('featured_image')->getCustomProperty('alt', '');
        }
        $altScore = 0;
        if (! empty($altText)) {
            $altScore = 10;
            $altStatus = 'good';
        } elseif (! $hasImage) {
            $altScore = 5;
            $altStatus = 'warning';
        } else {
            $altStatus = 'warning';
        }
        $totalScore += $altScore;
        $checks[] = [
            'label' => 'Alt text immagine',
            'value' => ! empty($altText) ? 'presente' : 'assente',
            'hint' => 'Importante per accessibilità e SEO',
            'status' => $altStatus,
            'score' => $altScore,
            'max' => 10,
        ];

        // 7. Link interni (10 punti)
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\']/i', $this->content ?? '', $matches);
        $allLinks = $matches[1] ?? [];
        $internalLinks = array_filter($allLinks, function ($url) {
            return ! str_starts_with($url, 'http') || str_contains($url, 'themedge.com');
        });
        $internalScore = count($internalLinks) > 0 ? 10 : 0;
        $totalScore += $internalScore;
        $checks[] = [
            'label' => 'Link interni',
            'value' => count($internalLinks) . ' link',
            'hint' => 'Almeno 1 link a un altro articolo',
            'status' => count($internalLinks) > 0 ? 'good' : 'warning',
            'score' => $internalScore,
            'max' => 10,
        ];

        // 8. Link esterni (5 punti)
        $externalLinks = array_filter($allLinks, function ($url) {
            return str_starts_with($url, 'http') && ! str_contains($url, 'themedge.com');
        });
        $externalScore = count($externalLinks) > 0 ? 5 : 0;
        $totalScore += $externalScore;
        $checks[] = [
            'label' => 'Link esterni',
            'value' => count($externalLinks) . ' link',
            'hint' => 'Almeno 1 fonte esterna',
            'status' => count($externalLinks) > 0 ? 'good' : 'warning',
            'score' => $externalScore,
            'max' => 5,
        ];

        // 9. Heading H2 (10 punti)
        $h2Count = preg_match_all('/<h2[^>]*>/i', $this->content ?? '');
        $h2Score = $h2Count > 0 ? 10 : 0;
        $totalScore += $h2Score;
        $checks[] = [
            'label' => 'Sottotitoli H2',
            'value' => "{$h2Count} heading",
            'hint' => 'Almeno 1 H2 per strutturare',
            'status' => $h2Count > 0 ? 'good' : 'warning',
            'score' => $h2Score,
            'max' => 10,
        ];

        // Voto finale (0-100)
        $grade = match (true) {
            $totalScore >= 90 => 'excellent',
            $totalScore >= 70 => 'good',
            $totalScore >= 50 => 'fair',
            default => 'poor',
        };

        return [
            'score' => $totalScore,
            'grade' => $grade,
            'checks' => $checks,
        ];
    }
}
