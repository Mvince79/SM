<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait HasRedisLock
{
    /**
     * Bọc $callback trong 1 atomic lock (Cache::lock), chặn NGAY TRƯỚC KHI
     * request chạm DB. Dùng cache store MẶC ĐỊNH của môi trường (tôn trọng
     * CACHE_STORE trong .env / phpunit.xml) thay vì ép cứng 'redis' —
     * production vẫn chạy Redis thật (CACHE_STORE=redis), còn test có thể
     * dùng driver nhẹ hơn (array, database...) mà không cần Redis thật,
     * vì mọi driver có LockProvider (array, redis, memcached, database,
     * dynamodb, file) đều hỗ trợ atomic lock.
     */
    protected function withSubmitLock(
        string $lockKey,
        callable $callback,
        int $seconds = 10,
        string $conflictMessage = 'Yêu cầu trước đó của bạn đang được xử lý, vui lòng đợi trong giây lát.'
    ) {
        $lock = Cache::lock($lockKey, $seconds);

        if (!$lock->get()) {
            abort(409, $conflictMessage);
        }

        try {
            return $callback();
        } finally {
            $lock->release();
        }
    }
}