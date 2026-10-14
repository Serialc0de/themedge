<?php

namespace App\Observers;

use App\Jobs\GenerateArticleAudio;
use App\Models\Article;

class ArticleObserver
{
    /**
     * Dispatcha la generazione audio quando un articolo viene pubblicato.
     */
    public function saved(Article $article): void
    {
        // Solo se:
        // - TTS abilitato
        // - Auto-generate abilitato
        // - Articolo è pubblicato (status = published)
        // - Il content è cambiato (o l'audio non è mai stato generato)
        if (!config('tts.enabled', true)) return;
        if (!config('tts.auto_generate.enabled', true)) return;

        $isPublished = ($article->status ?? null) === 'published';
        if (!$isPublished) return;

        $contentChanged = $article->wasChanged('content') || $article->wasChanged('title');
        $neverGenerated = $article->audio_status === 'pending' || !$article->audio_generated_at;

        if (!$contentChanged && !$neverGenerated) return;

        $delay = config('tts.auto_generate.delay_seconds', 30);

        GenerateArticleAudio::dispatch($article, force: $contentChanged)
            ->delay(now()->addSeconds($delay));

        \Log::info('GenerateArticleAudio dispatched', [
            'article_id' => $article->id,
            'delay' => $delay,
            'force' => $contentChanged,
        ]);
    }
}
