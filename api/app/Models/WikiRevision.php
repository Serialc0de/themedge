<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WikiRevision extends Model
{
    protected $fillable = [
        'wiki_page_id', 'user_id',
        'title', 'subtitle', 'summary', 'content', 'infobox',
        'change_summary', 'is_minor', 'is_ai_generated', 'ai_model',
    ];

    protected $casts = [
        'infobox' => 'array',
        'is_minor' => 'boolean',
        'is_ai_generated' => 'boolean',
    ];

    public function wikiPage(): BelongsTo
    {
        return $this->belongsTo(WikiPage::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function diffWithPrevious(): ?array
    {
        $previous = self::where('wiki_page_id', $this->wiki_page_id)
            ->where('id', '<', $this->id)
            ->orderByDesc('id')
            ->first();

        if (!$previous) return null;

        return [
            'title' => $previous->title !== $this->title,
            'summary' => $previous->summary !== $this->summary,
            'content_chars_delta' => strlen($this->content ?? '') - strlen($previous->content ?? ''),
            'previous_id' => $previous->id,
        ];
    }
}
