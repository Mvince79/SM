<?php

use App\Http\Controllers\Api\Internal\FaceMatchController;
use App\Http\Controllers\Api\V1\ChatbotController;
use App\Http\Controllers\Api\V1\KycController;
use App\Http\Controllers\Api\V1\MapDataController;
use App\Http\Controllers\Api\V1\MissingChildApiController;
use App\Http\Controllers\Api\V1\StatsApiController;
use App\Http\Controllers\Api\V1\VolunteerAssignmentController;
use App\Http\Controllers\Api\GeoLookupController;
use App\Http\Controllers\Client\PhotoSearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('internal')
    ->middleware('internal.key')
    ->group(function () {
        Route::post('/face-match/trigger', [FaceMatchController::class, 'trigger']);
    });
// ────────────────────────────────────────────────────────────────────────────────
// Public API — 60 requests/minute
// ────────────────────────────────────────────────────────────────────────────────

Route::prefix('v1')->name('api.v1.')->group(function () {

    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/missing-children', [MissingChildApiController::class, 'index'])
            ->name('missing-children.index');

        Route::get('/missing-children/{id}', [MissingChildApiController::class, 'show'])
            ->name('missing-children.show');

        Route::get('/map-data', [MapDataController::class, 'index'])
            ->name('map-data.index');
        Route::get('/stats', [StatsApiController::class, 'index'])
            ->name('stats.index');
    });

    // Bước 6 — Chatbot: throttle riêng 20/giờ khách (IP), 50/giờ tài khoản (sanctum).
    Route::middleware('throttle:chatbot')
        ->post('/chatbot/message', [ChatbotController::class, 'reply'])
        ->name('chatbot.message');

    Route::middleware(['auth:sanctum', 'throttle:5,1'])->group(function () {
        Route::post('/kyc/submit', [KycController::class, 'store'])
            ->name('kyc.submit');
    });

    // ────────────────────────────────────────────────────────────────────────────
    // POST missing child — 5 requests/minute (chống spam đăng tin)
    // ────────────────────────────────────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'throttle:5,1'])->group(function () {
        // Route::post('/missing-children', [MissingChildApiController::class, 'store'])
        //     ->name('missing-children.store');
    });
    // Public test endpoint (development only) to exercise clue creation without auth.
    // Remove or protect this in production.
    if (!app()->environment('production')) {
        Route::post('/clues-test', [\App\Http\Controllers\Api\V1\ClueApiController::class, 'store'])
            ->name('clues.test')
            ->middleware('throttle:10,1');
    }

    // ────────────────────────────────────────────────────────────────────────────
    // POST clues — 10 requests/minute (chống spam manh mối)
    // ────────────────────────────────────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'throttle:10,1'])->group(function () {
        // Route::post('/clues', [ClueApiController::class, 'store'])
        //     ->name('clues.store');
    });

    // ────────────────────────────────────────────────────────────────────────────
    // Authenticated — 120 requests/minute
    // ────────────────────────────────────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
        Route::delete('/missing-children/{missingChild}', [MissingChildApiController::class, 'destroy'])
            ->name('missing-children.destroy');
        // Create a clue (authenticated - Bearer token)
        Route::post('/clues', [\App\Http\Controllers\Api\V1\ClueApiController::class, 'store'])
            ->name('clues.store')
            ->middleware('throttle:60,1');

        Route::get('/volunteer/nearby-clues', [VolunteerAssignmentController::class, 'nearbyClues'])
            ->name('volunteer.nearby-clues');
        Route::post('/volunteer/assignments', [VolunteerAssignmentController::class, 'store'])
            ->name('volunteer.assignments.store');
        Route::patch('/volunteer/assignments/{assignment}', [VolunteerAssignmentController::class, 'update'])
            ->name('volunteer.assignments.update');
    });
});

// ────────────────────────────────────────────────────────────────────────────────
// Health check
// ────────────────────────────────────────────────────────────────────────────────

Route::get('/health', function (Request $request) {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'version' => 'v1',
    ]);
})->middleware('throttle:60,1');

// ────────────────────────────────────────────────────────────────────────────────
// V-02 — Reverse-geocode (public, 100% nội bộ, không gọi API ngoài)
// ────────────────────────────────────────────────────────────────────────────────
Route::post('/geo/lookup', [GeoLookupController::class, 'lookup'])
    ->middleware('throttle:60,1')
    ->name('api.geo.lookup');

// ────────────────────────────────────────────────────────────────────────────────
// S-02 — AI photo search (public). Giới hạn tốc độ lấy từ Cài đặt hệ thống
// (photo_search.rate_limit lần / rate_window phút), mặc định 5 lần/giờ/IP.
// Xem AppServiceProvider: RateLimiter::for('photo-search', ...).
// ────────────────────────────────────────────────────────────────────────────────
Route::post('/photo-search', [PhotoSearchController::class, 'search'])
    ->middleware('throttle:photo-search')
    ->name('api.photo-search');
