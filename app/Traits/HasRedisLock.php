<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

trait HasRedisLock
{
    protected function withSubmitLock(
        string $lockKey,
        callable $callback,
        int $seconds = 10,
        string $conflictMessage = 'Yêu cầu trước đó của bạn đang được xử lý, vui lòng đợi trong giây lát.'
    ) {
        $lock = Cache::lock($lockKey, $seconds);

        if (!$lock->get()) {
            throw ValidationException::withMessages([
                'submitLock' => $conflictMessage,
            ]);
        }

        try {
            return $callback();
        } finally {
            $lock->release();
        }
    }
}