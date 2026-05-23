<?php

use App\Http\Controllers\Client\MapController;
use App\Http\Controllers\Client\MissingController;
use App\Http\Controllers\Client\ProfileController;
use App\Http\Controllers\Client\StatsController;
use App\Http\Controllers\Client\HomeController;
use Illuminate\Support\Facades\Route;

// ── Auth (register, login, OTP, Google OAuth, logout) ──────────────────────
require __DIR__ . '/auth.php';

// ── Trang chủ (public) ──────────────────────────────────────────────────────
Route::get('/', [HomeController::class, 'index'])->name('home');


Route::prefix('missing')->name('missing.')->group(function () {
    Route::get('/', [MissingController::class, 'index'])->name('index');
    Route::get('/detail/{id}', [MissingController::class, 'detail'])->name('detail');
});

Route::prefix('map')->name('map.')->group(function () {
    Route::get('/', [MapController::class, 'index'])->name('index');
});

Route::prefix('stats')->name('stats.')->group(function () {
    Route::get('/', [StatsController::class, 'index'])->name('index');
});

// ── Routes cần đăng nhập + email đã xác thực + không bị BAN ────────────────
Route::middleware(['auth', 'email.verified'])->group(function () {
    Route::prefix('/profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('index');
    });

    Route::prefix('missing')->name('missing.')->group(function () {
        Route::get('/create', [MissingController::class, 'create'])->name('create');
        Route::post('/store', [MissingController::class, 'store'])->name('store');
    });
    // Profile (Sprint 1 — PB-18)
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.index');
    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');

    // Found Children CRUD
    Route::resource('found-children', \App\Http\Controllers\FoundChildController::class)->names([
        'index' => 'found-children.index',
        'create' => 'found-children.create',
        'store' => 'found-children.store',
        'show' => 'found-children.show',
        'edit' => 'found-children.edit',
        'update' => 'found-children.update',
        'destroy' => 'found-children.destroy',
    ]);
});