<?php

use App\Http\Controllers\Client\MapController;
use App\Http\Controllers\Client\MissingController;
use App\Http\Controllers\Client\ProfileController;
use App\Http\Controllers\Client\StatsController;
use App\Http\Controllers\Client\HomeController;
use App\Models\Message;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Livewire\Chat\ChatBox;
use Livewire\Volt\Volt;
// ── Auth (register, login, OTP, Google OAuth, logout) ──────────────────────
require __DIR__ . '/auth.php';

// ── Trang chủ (public) ──────────────────────────────────────────────────────
Route::get('/', [HomeController::class, 'index'])->name('home');


Route::prefix('missing')->name('missing.')->group(function () {
    Route::get('/', [MissingController::class, 'index'])->name('index');
    Route::get('/detail/{id}', [MissingController::class, 'detail'])->name('detail');
    Route::get('/detail', [MissingController::class, 'detailByQuery'])->name('detail.query');
    Route::get('/detail/{id}/qr.svg', [MissingController::class, 'qr'])->name('qr');
    // Public detail by case_code for QR scan (e.g. /missing/MC-2026-00001)
    // Restrict pattern so it doesn't catch other routes like /create
    Route::get('/{case_code}', [MissingController::class, 'detailByCaseCode'])
        ->where('case_code', '^MC-\d{4}-\d{5}$')
        ->name('by_case_code');
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

    // Upload photo for a FoundChild
    Route::post('found-children/{foundChild}/photos', [\App\Http\Controllers\FoundChildController::class, 'storePhoto'])
        ->name('found-children.photos.store');

    Route::middleware(['auth', 'email.verified'])
        ->get(
            '/chat/dev/{missing}/{user}',
            ChatBox::class
        )
        ->name('chat.dev');
    Route::view('/chat', 'chat.index')
        ->middleware('auth')
        ->name('chat.index');

    Route::get('/chat/attachment/{filename}', function (string $filename) {
        abort_if(str_contains($filename, '/') || str_contains($filename, '\\'), 404);

        $path = 'chat-attachments/' . $filename;

        $message = Message::query()
            ->where('attachment_path', $path)
            ->where('attachment_type', 'IMAGE')
            ->where(function ($query) {
                $query
                    ->where('from_user_id', auth()->id())
                    ->orWhere('to_user_id', auth()->id());
            })
            ->firstOrFail();

        abort_unless(Storage::disk('private')->exists($message->attachment_path), 404);

        return Storage::disk('private')->response($message->attachment_path);
    })->name('chat.attachment');
});
