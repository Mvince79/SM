<?php

use App\Http\Controllers\Api\V1\MissingChildApiController;
use App\Http\Controllers\Api\V1\MapDataController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// ────────────────────────────────────────────────────────────────────────────────
// Public API (no authentication required)
// Rate limit: 60 requests/minute
// ────────────────────────────────────────────────────────────────────────────────

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Missing Children - Public endpoints
    Route::get('/missing-children', [MissingChildApiController::class, 'index'])
        ->name('missing-children.index')
        ->middleware('throttle:60,1');

    Route::get('/missing-children/{id}', [MissingChildApiController::class, 'show'])
        ->name('missing-children.show')
        ->middleware('throttle:60,1');

    // Map Data - Public endpoint (for Leaflet map)
    Route::get('/map-data', [MapDataController::class, 'index'])
        ->name('map-data.index')
        ->middleware('throttle:60,1');

    // ────────────────────────────────────────────────────────────────────────────
    // Authenticated API routes (future use for Sprint 2+)
    // Rate limit: 120 requests/minute for authenticated users
    // ────────────────────────────────────────────────────────────────────────────

    Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
        // TODO: Sprint 2 - Authenticated endpoints for create, update, delete
        // Route::post('/missing-children', [MissingChildApiController::class, 'store']);
        // Route::put('/missing-children/{id}', [MissingChildApiController::class, 'update']);
        // Route::delete('/missing-children/{id}', [MissingChildApiController::class, 'destroy']);
    });
});

// ────────────────────────────────────────────────────────────────────────────────
// Health check endpoint
// ────────────────────────────────────────────────────────────────────────────────

Route::get('/health', function (Request $request) {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'version' => 'v1',
    ]);
})->middleware('throttle:60,1');
