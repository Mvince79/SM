<?php

use App\Http\Controllers\Client\MapController;
use App\Http\Controllers\Client\MissingController;
use App\Http\Controllers\Client\ProfileController;
use App\Http\Controllers\Client\StatsController;
use App\Http\Controllers\Client\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\WatchAreaController;
use App\Http\Controllers\SitemapController;
use App\Models\Message;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Livewire\Chat\ChatBox;
use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Gate;
use App\Http\Controllers\HandoverPointPublicController;
use App\Http\Controllers\Verifier\HandoverConfirmationController;

// ── Auth (register, login, OTP, Google OAuth, logout) ──────────────────────
require __DIR__ . '/auth.php';

// ── Trang chủ (public) ──────────────────────────────────────────────────────
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/diem-ban-giao', [HandoverPointPublicController::class, 'index'])
    ->name('handover-points.index');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::prefix('missing')->name('missing.')->group(function () {
    Route::get('/', [MissingController::class, 'index'])->name('index');

    // Backward compat: route cũ theo id vẫn chạy
    Route::get('/detail/{id}', [MissingController::class, 'detail'])->name('detail');

    Route::get('/detail', [MissingController::class, 'detailByQuery'])->name('detail.query');
    Route::get('/detail/{id}/qr.svg', [MissingController::class, 'qr'])->name('qr');

    // Public detail by case_code for QR scan
    Route::get('/{case_code}', [MissingController::class, 'detailByCaseCode'])
        ->where('case_code', '^MC-\d{4}-\d{5}$')
        ->name('by_case_code');
    Route::get('/found/{id}', [MissingController::class, 'resolvedShow'])->name('found');

    // T-42: Public detail by slug
    Route::get('/{slug}', [MissingController::class, 'detailBySlug'])
        ->where('slug', '^(?!create$|store$|detail$|found$)[a-z0-9-]+$')
        ->name('by_slug');
});

Route::prefix('map')->name('map.')->group(function () {
    Route::get('/', [MapController::class, 'index'])->name('index');
});

Route::prefix('stats')->name('stats.')->group(function () {
    Route::get('/', [StatsController::class, 'index'])->name('index');
});
Route::view(
    '/quy-trinh-ban-giao-an-toan',
    'pages.quy-trinh-ban-giao'
)->name('safe-handover-process');


Route::post('/watch-area', [WatchAreaController::class, 'store'])->name('watch-area.store');
Route::delete('/watch-area', [WatchAreaController::class, 'destroy'])->name('watch-area.destroy');

Route::prefix('legal')->name('legal.')->group(function () {
    Route::view('/terms-of-service', 'legal.terms-of-service')->name('terms');
    Route::view('/privacy-policy', 'legal.privacy-policy')->name('privacy');
    Route::view('/child-data-policy', 'legal.child-data-policy')->name('child-data');
});

Route::middleware(['auth'])
    ->get('/volunteer/dashboard', function () {
        Gate::authorize('access-volunteer-features');

        return view('volunteer.dashboard');
    })
    ->name('volunteer.dashboard');

// ── Routes cần đăng nhập + email đã xác thực + không bị BAN ────────────────
Route::middleware(['auth', 'email.verified'])->group(function () {
    Route::prefix('/profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('index');
        Route::get('/kyc', fn() => view('profile.kyc'))->name('kyc');
    });

    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])
        ->name('notifications.read');

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

    Route::prefix('/notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'center'])->name('center');
        Route::get('/{notification}', [NotificationController::class, 'show'])->name('show');
        Route::patch('/{notification}/read', [NotificationController::class, 'read'])->name('read');
    });

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

    Route::middleware('role:THIRD_PARTY_VERIFIER')->prefix('verifier')->name('verifier.')->group(function () {
        Route::get('/dashboard', fn() => view('verifier.dashboard'))->name('dashboard');
        Route::post('/handover/{handoverable}/{type}', [HandoverConfirmationController::class, 'confirm'])
            ->name('handover.confirm')
            ->whereIn('type', ['missing', 'found']);
    });
});
