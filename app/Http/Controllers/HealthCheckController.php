<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $dbStatus = 'ok';
        $redisStatus = 'ok';

        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            $dbStatus = 'fail';
        }

        try {
            Redis::ping();
        } catch (Throwable $e) {
            $redisStatus = 'fail';
        }

        $status = ($dbStatus === 'ok' && $redisStatus === 'ok')
            ? 'ok'
            : 'fail';

        return response()->json([
            'status' => $status,
            'db' => $dbStatus,
            'redis' => $redisStatus,
        ], $status === 'ok' ? 200 : 503);
    }
}