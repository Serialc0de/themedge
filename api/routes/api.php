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
