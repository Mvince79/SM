<?php

use Illuminate\Support\Facades\Route;



// ── Auth (register, login, OTP, Google OAuth, logout) ──────────────────────
require __DIR__ . '/auth.php';

// ── Trang chủ (public) ──────────────────────────────────────────────────────
Route::get('/', function () {
    return view('home');
})->name('home');

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