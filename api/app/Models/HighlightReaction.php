<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HighlightReaction extends Model
{
    protected $fillable = [
        'highlight_id',
        'reader_id',
        'emoji',
    ];

    protected $hidden = [
        'reader_id',
    ];

    /**
     * Fallback se config non è caricata.
     */
    public const FALLBACK_EMOJIS = ['👍', '❤️', '🤔', '🔥', '👏'];

    /**
     * Emoji supportate (da config).
     */
    public static function allowedEmojis(): array
    {
        $emojis = config('highlights.emojis', self::FALLBACK_EMOJIS);
        return is_array($emojis) && !empty($emojis) ? $emojis : self::FALLBACK_EMOJIS;
    }

    public function highlight(): BelongsTo
    {
        return $this->belongsTo(ReaderHighlight::class, 'highlight_id');
    }

    public function reader(): BelongsTo
    {
        return $this->belongsTo(ReaderIdentity::class, 'reader_id');
    }
}
