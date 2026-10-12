<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GlossaryTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'term',
        'slug',
        'definition',
        'category',
        'aliases',
        'is_published',
        'order',
    ];

    protected $casts = [
        'aliases' => 'array',
        'is_published' => 'boolean',
        'order' => 'integer',
    ];

    // ============================================================
    // BOOT — Auto-genera slug dal termine
    // ============================================================

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->slug) && !empty($model->term)) {
                $model->slug = Str::slug($model->term);
            }
        });

        static::updating(function (self $model) {
            // Rigenera slug solo se il termine è cambiato e lo slug non è stato sovrascritto manualmente
            if ($model->isDirty('term') && !$model->isDirty('slug')) {
                $model->slug = Str::slug($model->term);
            }
        });
    }

    // ============================================================
    // ROUTE KEY
    // ============================================================

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ============================================================
    // HELPER
    // ============================================================

    /**
     * Ritorna tutti i termini da matchare nel contenuto:
     * il termine principale + tutti gli alias.
     *
     * @return array<string>
     */
    public function getMatchableTermsAttribute(): array
    {
        $terms = [$this->term];
        if (is_array($this->aliases)) {
            $terms = array_merge($terms, $this->aliases);
        }

        // Rimuovi duplicati e termini vuoti
        return array_values(array_unique(array_filter($terms)));
    }

    /**
     * Ritorna l'URL della pagina glossario per questo termine.
     */
    public function getUrlAttribute(): string
    {
        return '/glossario/' . $this->slug;
    }

    // ============================================================
    // SCOPE
    // ============================================================

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('term');
    }

    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
