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

    /**
     * Registra una reazione immutabile.
     * Un reader può reagire UNA SOLA VOLTA a un highlight.
     * Se ha già reagito (con qualsiasi emoji), ritorna null.
     *
     * @return self|null
     */
    public static function react(int $highlightId, int $readerId, string $emoji): ?self
    {
        // Verifica se esiste già una reazione di questo reader
        $existing = self::where('highlight_id', $highlightId)
            ->where('reader_id', $readerId)
            ->first();

        if ($existing) {
            return null; // già reagito → non permesso
        }

        return self::create([
            'highlight_id' => $highlightId,
            'reader_id' => $readerId,
            'emoji' => $emoji,
        ]);
    }

    /**
     * Verifica se un reader ha già reagito.
     */
    public static function hasReacted(int $highlightId, int $readerId): bool
    {
        return self::where('highlight_id', $highlightId)
            ->where('reader_id', $readerId)
            ->exists();
    }

    /**
     * Reazione di un reader (se esiste).
     */
    public static function forReader(int $highlightId, int $readerId): ?self
    {
        return self::where('highlight_id', $highlightId)
            ->where('reader_id', $readerId)
            ->first();
    }
}
