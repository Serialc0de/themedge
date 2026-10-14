<?php

namespace App\Observers;

use App\Jobs\GenerateArticleAudio;
use App\Models\Article;

class ArticleObserver
{
    /**
     * Dispatcha la generazione audio quando un articolo viene pubblicato.
     *
     * Stati di audio_status:
     *   ''           → mai generato (default DB) → dispatchabile
     *   'pending'    → job già dispatchato, in attesa di partire → skip
     *   'generating' → job in esecuzione → skip
     *   'ready'      → audio pronto → skip
     *   'skipped'    → articolo troppo corto → skip
     *   'failed'     → job fallito → skip
     *
     * Si dispatcha SOLO se audio_status è '' o null.
     * Subito dopo il dispatch, l'articolo viene marcato 'pending'
     * (updateQuietly, per non ritriggerare l'observer) così che
     * eventuali save() successivi non ri-dispatchino.
     */
    public function saved(Article $article): void
    {
        // 1. Feature flag globali
        if (!config('tts.enabled', true)) return;
        if (!config('tts.auto_generate.enabled', true)) return;

        // 2. Solo articoli pubblicati
        if (($article->status ?? null) !== 'published') return;

        // 3. Dispatch SOLO se non è mai stato generato
        $neverGenerated = ($article->audio_status === '' || is_null($article->audio_status));
        if (!$neverGenerated) {
            return;
        }

        // 4. Marca subito come 'pending' per evitare ri-dispatch da save() successivi.
        //    updateQuietly: non triggera observer, non tocca updated_at.
        $article->updateQuietly(['audio_status' => 'pending']);

        // 5. Dispatch con delay per coalescing
        $delay = config('tts.auto_generate.delay_seconds', 30);

        GenerateArticleAudio::dispatch($article)
            ->delay(now()->addSeconds($delay));

        \Log::info('GenerateArticleAudio dispatched', [
            'article_id' => $article->id,
            'delay' => $delay,
            'reason' => 'never_generated',
        ]);
    }
}
