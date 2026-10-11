<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| LinkShield AI — API Routes
|--------------------------------------------------------------------------
|
| All routes here are prefixed with /api automatically by the
| RouteServiceProvider (or bootstrap/app.php in Laravel 13).
|
*/

// ── Public scan routes (no auth required) ────────────────────────────────
Route::post('/scan', [ScanController::class, 'scan']);
Route::get('/history', [ScanController::class, 'history']);
Route::get('/stats', [ScanController::class, 'stats']);

Route::get('/intel', [PostController::class, 'index']);

// ── Authentication routes ────────────────────────────────────────────────
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login',    [LoginController::class, 'login']);

// ── Protected routes (require authenticated session) ─────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user',    fn (Request $request) => $request->user());
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/my-scans', [ScanController::class, 'myScans']);
    Route::post('/intel', [PostController::class, 'store']);
    Route::get('/my-intel', [PostController::class, 'myPosts']);
    
    // User Profile Hub
    Route::get('/user/dashboard', [ProfileController::class, 'dashboard']);
    Route::put('/user/profile', [ProfileController::class, 'updateProfile']);
    Route::put('/user/password', [ProfileController::class, 'updatePassword']);
    Route::post('/user/link-scans', [ScanController::class, 'linkScans']);

    // ── Admin routes ────────────────────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/stats', [AdminController::class, 'stats']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::delete('/users/{user}', [AdminController::class, 'deleteUser']);
        Route::get('/posts/pending', [AdminController::class, 'pendingPosts']);
        Route::patch('/posts/{post}/evaluate', [AdminController::class, 'evaluate']);
    });
});
