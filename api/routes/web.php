<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/analytics/export', [\App\Http\Controllers\AnalyticsExportController::class, 'export'])->name('analytics.export');
});
// DEBUG HTTPS - da rimuovere


