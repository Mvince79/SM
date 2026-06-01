<?php

use App\Http\Controllers\Api\V1\MissingChildApiController;
use App\Http\Controllers\Api\V1\MapDataController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
    });

    // ────────────────────────────────────────────────────────────────────────────
    // POST missing child — 5 requests/minute (chống spam đăng tin)
    // ────────────────────────────────────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'throttle:5,1'])->group(function () {
        // Route::post('/missing-children', [MissingChildApiController::class, 'store'])
        //     ->name('missing-children.store');
    });

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