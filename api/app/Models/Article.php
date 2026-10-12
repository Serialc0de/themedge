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
        'content_raw',
        'highlight_policy_id', 'highlight_settings', 'highlight_until', 'highlight_locked',
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
        'highlight_settings' => 'array',
        'highlight_until' => 'datetime',
        'highlight_locked' => 'boolean',
    ];

    // ============================================================
    // BOOT
    // ============================================================
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($article) {
            // Blocca creazione di articoli senza titolo (es. relazioni malformate)
            if (empty($article->title) && !$article->exists) {
                throw new \InvalidArgumentException(
                    'Impossibile creare un articolo senza titolo.'
                );
            }

            if (empty($article->slug)) {
                $slug = Str::slug($article->title);
                if (empty($slug)) {
                    $slug = 'articolo-' . time() . '-' . Str::random(6);
                }
                $article->slug = $slug;
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
        // Sanitizza il content al salvataggio (difesa input)
        static::saving(function ($article) {
            if ($article->isDirty('content')) {
                // Backup raw al primo salvataggio o se vuoto
                if (empty($article->content_raw)) {
                    $original = $article->getOriginal('content');
                    $article->content_raw = !empty($original) ? $original : $article->content;
                }

                $sanitizer = app(\App\Services\HtmlSanitizer::class);
                $article->content = $sanitizer->sanitize($article->content);
            }
        });

        // Purge cache Cloudflare quando articolo pubblicato/modificato
        static::saved(function ($article) {
            $article->purgeCloudflareCache();
        });
    }

    /**
     * Purge selettivo della cache Cloudflare per questo articolo.
     * Invalida: URL articolo + homepage + categoria + sitemap.
     */
    public function purgeCloudflareCache(): void
    {
        $token = config('services.cloudflare.token');
        $zoneId = config('services.cloudflare.zone_id');

        if (!$token || !$zoneId) {
            return;
        }

        // Non purgare per bozze
        if ($this->status !== 'published') {
            return;
        }

        $urls = [
            'https://themedge.com/articolo/' . $this->slug,
            'https://themedge.com/', // homepage
        ];

        // Categoria
        if ($this->category) {
            $urls[] = 'https://themedge.com/categoria/' . $this->category->slug;
        }

        // Autore
        if ($this->author_id) {
            $urls[] = 'https://themedge.com/autore/' . $this->author_id;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($token)
                ->timeout(5)
                ->post("https://api.cloudflare.com/client/v4/zones/{$zoneId}/purge_cache", [
                    'files' => $urls,
                ]);

            if ($response->failed()) {
                \Log::warning('Cloudflare purge failed', [
                    'article_id' => $this->id,
                    'urls' => $urls,
                    'response' => $response->json(),
                ]);
            }
        } catch (\Exception $e) {
            \Log::warning('Cloudflare purge exception: ' . $e->getMessage(), [
                'article_id' => $this->id,
            ]);
        }
    }

    // ============================================================
    // HIGHLIGHT POLICY
    // ============================================================
    public function highlightPolicy(): BelongsTo
    {
        return $this->belongsTo(HighlightPolicy::class, 'highlight_policy_id');
    }

    /**
     * Risolve la policy effettiva per questo articolo.
     * Ordine di priorità:
     *   1. Kill switch globale (config)
     *   2. highlight_settings JSON (override diretto)
     *   3. highlight_policy_id specifico articolo
     *   4. Ereditarietà da tag/categoria/autore (vince priority più alta)
     *   5. Policy di default "full"
     */
    public function resolveHighlightPolicy(): HighlightPolicy
    {
        // 1. Kill switch globale
        if (!config('highlights.enabled', true)) {
            return HighlightPolicy::where('name', 'disabled')->first()
                ?? $this->defaultPolicy();
        }

        // 2. Override JSON diretto su articolo
        if (!empty($this->highlight_settings) && is_array($this->highlight_settings)) {
            return $this->policyFromSettings($this->highlight_settings);
        }

        // 3. Policy esplicita articolo
        if ($this->highlight_policy_id && $this->highlightPolicy) {
            return $this->highlightPolicy;
        }

        // 4. Ereditarietà da tag/categoria/autore
        $candidates = collect();

        if ($this->relationLoaded('category') || $this->category) {
            if ($this->category?->highlight_policy_id) {
                $candidates->push($this->category->highlight_policy_id);
            }
        }

        if ($this->relationLoaded('author') || $this->author) {
            if ($this->author?->highlight_policy_id) {
                $candidates->push($this->author->highlight_policy_id);
            }
        }

        if ($this->relationLoaded('tags') || $this->tags()->exists()) {
            foreach ($this->tags()->whereNotNull('highlight_policy_id')->pluck('highlight_policy_id') as $pid) {
                $candidates->push($pid);
            }
        }

        if ($candidates->isNotEmpty()) {
            $policy = HighlightPolicy::whereIn('id', $candidates->unique())
                ->orderByDesc('priority')
                ->first();
            if ($policy) {
                return $policy;
            }
        }

        // 5. Default
        return $this->defaultPolicy();
    }

    protected function defaultPolicy(): HighlightPolicy
    {
        static $cache = null;
        if ($cache === null) {
            $cache = HighlightPolicy::where('name', 'full')->first()
                ?? new HighlightPolicy([
                    'name' => 'full',
                    'label' => 'Completo',
                    'settings' => HighlightPolicy::defaultSettings(),
                ]);
        }
        return $cache;
    }

    protected function policyFromSettings(array $settings): HighlightPolicy
    {
        // Normalizza i valori: le stringhe "true"/"false" diventano booleani
        $normalized = [];
        foreach ($settings as $key => $value) {
            if (is_string($value)) {
                $lower = strtolower(trim($value));
                if ($lower === 'true') {
                    $normalized[$key] = true;
                } elseif ($lower === 'false') {
                    $normalized[$key] = false;
                } elseif (is_numeric($value)) {
                    $normalized[$key] = str_contains($value, '.') ? (float) $value : (int) $value;
                } else {
                    $normalized[$key] = $value;
                }
            } else {
                $normalized[$key] = $value;
            }
        }

        return new HighlightPolicy([
            'name' => '_inline',
            'label' => 'Override articolo',
            'settings' => array_merge(HighlightPolicy::defaultSettings(), $normalized),
        ]);
    }

    /**
     * Scadenza temporale: se highlight_until è nel futuro, gli highlight sono bloccati.
     */
    public function getHighlightsTemporarilyDisabledAttribute(): bool
    {
        return $this->highlight_until && $this->highlight_until->isFuture();
    }

    /**
     * Highlight sono permessi su questo articolo?
     */
    public function highlightsAllowed(): bool
    {
        if ($this->highlights_temporarily_disabled) {
            return false;
        }
        return $this->resolveHighlightPolicy()->enabled;
    }

    public function highlightsCanBePublic(): bool
    {
        return $this->highlightsAllowed() && $this->resolveHighlightPolicy()->allow_public;
    }

    public function highlightsCanReact(): bool
    {
        return $this->highlightsAllowed() && $this->resolveHighlightPolicy()->allow_reactions;
    }

    public function highlightsCanNote(): bool
    {
        return $this->highlightsAllowed() && $this->resolveHighlightPolicy()->allow_notes;
    }

    public function highlightsCanHeatmap(): bool
    {
        return $this->highlightsAllowed() && $this->resolveHighlightPolicy()->allow_heatmap;
    }

    public function highlightsMaxFragmentLength(): int
    {
        return $this->resolveHighlightPolicy()->max_fragment_length;
    }

    public function highlightsRateLimit(): int
    {
        return $this->resolveHighlightPolicy()->rate_limit_per_day;
    }

    /**
     * Config completa per API.
     */
    public function highlightConfig(): array
    {
        $policy = $this->resolveHighlightPolicy();
        $source = $this->highlightPolicySource();

        return array_merge($policy->toConfigArray(), [
            'source' => $source,
            'policy_name' => $policy->name,
            'policy_label' => $policy->label,
            'expires_at' => $this->highlight_until?->toIso8601String(),
            'temporarily_disabled' => $this->highlights_temporarily_disabled,
        ]);
    }

    protected function highlightPolicySource(): string
    {
        if (!config('highlights.enabled', true)) return 'global_disabled';
        if (!empty($this->highlight_settings)) return 'article_settings';
        if ($this->highlight_policy_id) return 'article_policy';
        if ($this->category?->highlight_policy_id) return 'category';
        if ($this->author?->highlight_policy_id) return 'author';
        if ($this->tags()->whereNotNull('highlight_policy_id')->exists()) return 'tag';
        return 'default';
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

    public function sources(): HasMany
    {
        return $this->hasMany(ArticleSource::class)->orderBy('order');
    }

    /**
     * Articoli correlati curati editorialmente.
     */
    public function relatedCurated(): BelongsToMany
    {
        return $this->belongsToMany(
            Article::class,
            'article_related',
            'article_id',
            'related_article_id'
        )
            ->withPivot('note', 'order')
            ->withTimestamps()
            ->orderByPivot('order');
    }


    public function polls(): HasMany
    {
        return $this->hasMany(Poll::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id')->orderByDesc('is_pinned')->orderByDesc('created_at');
    }

    public function allComments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function approvedComments(): HasMany
    {
        return $this->hasMany(Comment::class)
            ->where('status', 'approved')
            ->whereNull('parent_id')
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at');
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
