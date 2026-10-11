<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;

// PDF Download Route (Direct access for window.open)
Route::get('/report/download/{id}', [ReportController::class, 'download']);

// Serve the Vue SPA for all non-API routes
Route::get('/{any?}', function () {
    return view('welcome');
})->where('any', '.*');
