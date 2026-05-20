<?php

use App\Http\Controllers\Client\MapController;
use App\Http\Controllers\Client\MissingController;
use App\Http\Controllers\Client\StatsController;
use Illuminate\Support\Facades\Route;



// ── Auth (register, login, OTP, Google OAuth, logout) ──────────────────────
require __DIR__ . '/auth.php';

// ── Trang chủ (public) ──────────────────────────────────────────────────────
Route::get('/', function () {
    return view('home');
})->name('home');

Route::prefix('missing')->name('missing.')->group(function () {
    Route::get('/', [MissingController::class, 'index'])->name('index');
    Route::get('/detail', [MissingController::class, 'detail'])->name('detail');
});

Route::prefix('map')->name('map.')->group(function () {
    Route::get('/', [MapController::class, 'index'])->name('index');
});

Route::prefix('stats')->name('stats.')->group(function () {
    Route::get('/', [StatsController::class, 'index'])->name('index');
});

// ── Routes cần đăng nhập + email đã xác thực + không bị BAN ────────────────
Route::middleware(['auth', 'email.verified'])->group(function () {
    // Profile (Sprint 1 — PB-18)
    Route::get('/profile', function () {
        return view('profile.index');
    })->name('profile.index');
});


Route::get('/ho-so', fn() => abort(404))->name('cases.index');
Route::get('/ho-so/tao', fn() => abort(404))->name('cases.create');
Route::get('/ban-do', fn() => abort(404))->name('map.index');
Route::get('/thong-ke', fn() => abort(404))->name('stats.index');
Route::get('/ho-so-ca-nhan', fn() => abort(404))->name('profile.index');