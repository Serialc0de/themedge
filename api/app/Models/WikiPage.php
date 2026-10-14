<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class WikiPage extends Model
{
    use HasFactory, SoftDeletes, Searchable, LogsActivity;

    protected $fillable = [
        'embedding', 'embedding_model', 'embedding_generated_at',
        'slug', 'title', 'subtitle', 'type', 'status',
        'summary', 'content', 'infobox', 'aliases',
        'featured_image', 'featured_image_caption', 'icon',
        'articles_count', 'revisions_count', 'views', 'order',
        'meta_title', 'meta_description',
        'is_ai_generated', 'ai_model', 'ai_generated_at',
        'created_by', 'updated_by', 'published_at', 'last_reviewed_at',
        'entity_id',
        'image_source_url', 'image_license', 'image_author',
        'image_attribution', 'image_fetched_at', 'image_status',
    ];

    protected $casts = [
        'embedding' => 'array',
        'embedding_generated_at' => 'datetime',
        'infobox' => 'array',
        'aliases' => 'array',
        'articles_count' => 'integer',
        'revisions_count' => 'integer',
        'views' => 'integer',
        'order' => 'integer',
        'is_ai_generated' => 'boolean',
        'ai_generated_at' => 'datetime',
        'published_at' => 'datetime',
        'last_reviewed_at' => 'datetime',
        'image_fetched_at' => 'datetime',
    ];

    // ============================================================
    // TYPES
    // ============================================================

    public const TYPE_LABELS = [
        'person' => '👤 Persona',
        'organization' => '🏛️ Organizzazione',
        'location' => '🌍 Luogo',
        'event' => '📅 Evento',
        'concept' => '💡 Concetto',
        'topic' => '📚 Tema',
    ];

    public const TYPE_COLORS = [
        'person' => 'blue',
        'organization' => 'purple',
        'location' => 'green',
        'event' => 'orange',
        'concept' => 'yellow',
        'topic' => 'pink',
    ];

    public const STATUS_LABELS = [
        'draft' => 'Bozza',
        'published' => 'Pubblicata',
        'archived' => 'Archiviata',
    ];

    // ============================================================
    // BOOT
    // ============================================================

    protected static function booted(): void
    {
        static::creating(function (self $page) {
            if (empty($page->slug) && !empty($page->title)) {
                $page->slug = Str::slug($page->title);
            }
        });

        static::saving(function (self $page) {
            if ($page->isDirty('title') && empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
        });
    }

    // ============================================================
    // RELAZIONI
    // ============================================================

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'wiki_page_article')
            ->withPivot('relevance', 'context')
            ->withTimestamps()
            ->orderByDesc('wiki_page_article.relevance');
    }

    public function relatedEntities(): BelongsToMany
    {
        return $this->belongsToMany(Entity::class, 'wiki_page_entity')
            ->withPivot('relation_type')
            ->withTimestamps();
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(WikiRevision::class)->orderByDesc('created_at');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeFeatured($query)
    {
        return $query->where('articles_count', '>=', 3)
            ->orderByDesc('articles_count');
    }

    // ============================================================
    // ACCESSOR
    // ============================================================

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function getTypeColorAttribute(): string
    {
        return self::TYPE_COLORS[$this->type] ?? 'gray';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getUrlAttribute(): string
    {
        return '/wiki/' . $this->slug;
    }

    public function getFullUrlAttribute(): string
    {
        return 'https://themedge.com' . $this->url;
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at && $this->published_at <= now();
    }

    // ============================================================
    // ACTIVITY LOG
    // ============================================================

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'summary', 'content'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName) => "Wiki {$eventName}: {$this->title}")
            ->useLogName('wiki');
    }

    // ============================================================
    // MEILISEARCH
    // ============================================================

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'slug' => $this->slug,
            'type' => $this->type,
            'summary' => $this->summary,
            'content' => strip_tags($this->content ?? ''),
            'aliases' => $this->aliases ?? [],
        ];
    }

    /**
     * URL completo dell'immagine featured.
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        if (!$this->featured_image) return null;
        if (str_starts_with($this->featured_image, 'http')) {
            return $this->featured_image;
        }
        return config('app.public_asset_url', config('app.url')) . '/storage/' . ltrim($this->featured_image, '/');
    }

    /**
     * Testo di attribuzione per l'immagine (autore, licenza, fonte).
     */
    public function getImageAttributionHtmlAttribute(): ?string
    {
        if (empty($this->image_attribution)) return null;
        $text = e($this->image_attribution);
        if ($this->image_source_url) {
            $text .= ' <a href="' . e($this->image_source_url) . '" target="_blank" rel="noopener nofollow">(fonte)</a>';
        }
        return $text;
    }

}
