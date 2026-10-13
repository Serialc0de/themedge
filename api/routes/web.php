<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/analytics/export', [\App\Http\Controllers\AnalyticsExportController::class, 'export'])->name('analytics.export');
});
// DEBUG HTTPS - da rimuovere



// ============================================================
// DETECTED ENTITIES — Azioni admin
// ============================================================
Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::post(
        '/articles/{record}/link-detected-entities',
        [\App\Http\Controllers\DetectedEntitiesController::class, 'linkExisting']
    )->name('filament.admin.resources.articles.link-detected-entities');

    Route::post(
        '/articles/{record}/create-detected-entities',
        [\App\Http\Controllers\DetectedEntitiesController::class, 'createNew']
    )->name('filament.admin.resources.articles.create-detected-entities');
});
