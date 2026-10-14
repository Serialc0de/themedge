<?php

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthorController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CorrectionController;
use App\Http\Controllers\Api\PollController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\SeoController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\TipController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Contenuti
Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/{slug}', [ArticleController::class, 'show']);
Route::get('/articles/{slug}/sources', [ArticleController::class, 'sources']);
Route::get('/articles/{slug}/curated', [ArticleController::class, 'curated']);
Route::get('/articles/{slug}/fact-checks', [ArticleController::class, 'factChecks']);
Route::get('/articles/{slug}/knowledge', [ArticleController::class, 'knowledge']);
Route::get('/articles/{slug}/timeline', [ArticleController::class, 'timeline']);
Route::get('/articles/{slug}/scrolly', [ArticleController::class, 'scrolly']);
Route::get('/articles/{slug}/audio', [ArticleController::class, 'audio']);

// Entità (knowledge graph)
Route::get('/entities', [\App\Http\Controllers\Api\EntityController::class, 'index']);
Route::get('/entities/{slug}', [\App\Http\Controllers\Api\EntityController::class, 'show']);

// Glossario
Route::get('/glossary', [\App\Http\Controllers\Api\GlossaryController::class, 'index']);
Route::get('/glossary/grouped', [\App\Http\Controllers\Api\GlossaryController::class, 'grouped']);
Route::get('/glossary/{slug}', [\App\Http\Controllers\Api\GlossaryController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{slug}', [CategoryController::class, 'show']);
Route::get('/tags', [TagController::class, 'index']);
Route::get('/tags/{slug}', [TagController::class, 'show']);
Route::get('/tags/{slug}/articles', [TagController::class, 'articles']);
Route::get('/authors/{id}', [AuthorController::class, 'show']);
Route::get('/authors/{id}/articles', [AuthorController::class, 'articles']);
Route::get('/search', [SearchController::class, 'search']);

// SEO
Route::get('/seo/settings', [SeoController::class, 'settings']);

// Sondaggi
Route::get('/polls/{poll}', [PollController::class, 'show']);
Route::post('/polls/{poll}/vote', [PollController::class, 'vote']);

// Correzioni
Route::get('/corrections', [CorrectionController::class, 'index']);
Route::get('/corrections/article/{slug}', [CorrectionController::class, 'forArticle']);

// Segnalazioni
Route::post('/tips', [TipController::class, 'store']);

// Redazione
Route::get('/editorial-team', [\App\Http\Controllers\Api\EditorialTeamController::class, 'index']);

// Breaking News
Route::get('/breaking-news', [\App\Http\Controllers\Api\BreakingNewsController::class, 'index']);
Route::get('/breaking-news/archive', [\App\Http\Controllers\Api\BreakingNewsController::class, 'archive']);

// Live Blog
Route::get('/live-blogs', [\App\Http\Controllers\Api\LiveBlogController::class, 'index']);
Route::get('/live-blogs/{slug}', [\App\Http\Controllers\Api\LiveBlogController::class, 'show']);
Route::get('/live-blogs/{slug}/updates', [\App\Http\Controllers\Api\LiveBlogController::class, 'updates']);
Route::post('/live-blogs/{slug}/subscribe', [\App\Http\Controllers\Api\LiveBlogController::class, 'subscribe']);

// Push Notifications
Route::get('/push/vapid-public-key', [\App\Http\Controllers\Api\PushController::class, 'vapidPublicKey']);
Route::post('/push/subscribe', [\App\Http\Controllers\Api\PushController::class, 'subscribe']);
Route::post('/push/unsubscribe', [\App\Http\Controllers\Api\PushController::class, 'unsubscribe']);
Route::get('/push/status', [\App\Http\Controllers\Api\PushController::class, 'status']);

// Commenti
Route::get('/articles/{slug}/comments', [\App\Http\Controllers\Api\CommentController::class, 'index']);
Route::post('/articles/{slug}/comments', [\App\Http\Controllers\Api\CommentController::class, 'store']);

// Embed / oEmbed
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/oembed', [\App\Http\Controllers\Api\OEmbedController::class, 'resolve']);
    Route::get('/oembed/providers', [\App\Http\Controllers\Api\OEmbedController::class, 'providers']);
});

// ============================================================
// Reader (account anonimo device-based)
// ============================================================
Route::prefix('reader')->group(function () {
    Route::post('/register', [\App\Http\Controllers\Api\ReaderController::class, 'register']);
    Route::get('/me', [\App\Http\Controllers\Api\ReaderController::class, 'me']);

    // GDPR
    Route::get('/me/export', [\App\Http\Controllers\Api\ReaderPrivacyController::class, 'export']);
    Route::delete('/me', [\App\Http\Controllers\Api\ReaderPrivacyController::class, 'delete']);
});

// ============================================================
// Highlights
// ============================================================
// Highlights: config pubblica (no auth, no throttle pesante)
Route::get('/highlights/config', [\App\Http\Controllers\Api\HighlightController::class, 'config']);

Route::middleware('throttle:120,1')->group(function () {
    // Lista pubblici (o miei se X-Reader-Token)
    Route::get('/articles/{slug}/highlights', [\App\Http\Controllers\Api\HighlightController::class, 'forArticle']);
    Route::get('/articles/{slug}/highlights/stats', [\App\Http\Controllers\Api\HighlightController::class, 'stats']);

    // Richiedono token
    Route::post('/highlights', [\App\Http\Controllers\Api\HighlightController::class, 'store']);
    Route::get('/highlights/mine', [\App\Http\Controllers\Api\HighlightController::class, 'mine']);
    Route::patch('/highlights/{id}', [\App\Http\Controllers\Api\HighlightController::class, 'update']);
    Route::delete('/highlights/{id}', [\App\Http\Controllers\Api\HighlightController::class, 'destroy']);

    // Reazioni
    Route::post('/highlights/{id}/react', [\App\Http\Controllers\Api\HighlightController::class, 'react']);
    Route::delete('/highlights/{id}/react', [\App\Http\Controllers\Api\HighlightController::class, 'unreact']);
});
