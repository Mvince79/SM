<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

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

    // Các route Sprint 1+ sẽ thêm vào đây...
});