<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleSource extends Model
{
    protected $fillable = [
        'article_id',
        'url',
        'title',
        'publisher',
        'type',
        'note',
        'published_at',
        'archived_url',
        'is_verified',
        'order',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'published_at' => 'date',
        'order' => 'integer',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Icona per tipo fonte.
     */
    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'official' => '📄',
            'study' => '🎓',
            'news' => '📰',
            'social' => '🐦',
            'archive' => '🗄️',
            'video' => '🎥',
            'dataset' => '📊',
            default => '🔗',
        };
    }

    /**
     * Label per tipo fonte.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'official' => 'Fonte ufficiale',
            'study' => 'Studio / Ricerca',
            'news' => 'Testata giornalistica',
            'social' => 'Social media',
            'archive' => 'Archivio',
            'video' => 'Video',
            'dataset' => 'Dataset',
            default => 'Altro',
        };
    }

    /**
     * Dominio dell'URL (per display).
     */
    public function getDomainAttribute(): string
    {
        $host = parse_url($this->url, PHP_URL_HOST) ?? '';
        return preg_replace('/^www\./', '', $host);
    }
}
