<?php

namespace App\Traits;

use App\Models\IdempotencyKey;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait HasIdempotency
{
    /**
     * Sinh 1 UUID mới dùng làm idempotency key. Gọi ở mount() (Livewire)
     * hoặc khi render form (Controller truyền thống — xem E-01b/E-01d).
     */
    protected function generateIdempotencyKey(): string
    {
        return (string) Str::uuid();
    }

    /**
     * Chạy $callback (logic tạo record thật) bên trong 1 DB::transaction(),
     * đảm bảo KHÔNG tạo trùng record khi client gửi lại cùng 1 $requestKey
     * (double-click, network retry, mở 2 tab, double-submit API).
     *
     * $callback PHẢI trả về 1 Eloquent Model có thuộc tính `id`.
     *
     * @return array{resource: \Illuminate\Database\Eloquent\Model|null, resource_id: int, was_duplicate: bool}
     */
    protected function resolveIdempotent(
        string $requestKey,
        string $resourceType,
        ?int $userId,
        callable $callback
    ): array {
        // Bước 1: check nhanh TRƯỚC khi mở transaction — tránh mở transaction
        // không cần thiết cho trường hợp phổ biến nhất (submit lần 2 sau khi lần 1 đã xong).
        if ($existing = IdempotencyKey::where('request_key', $requestKey)->first()) {
            return $this->duplicateResult($existing);
        }

        try {
            return DB::transaction(function () use ($requestKey, $resourceType, $userId, $callback) {
                $resource = $callback();

                // Insert idempotency_key CÙNG transaction với record chính -> atomic.
                IdempotencyKey::create([
                    'request_key' => $requestKey,
                    'user_id' => $userId,
                    'resource_type' => $resourceType,
                    'resource_id' => $resource->id,
                ]);

                return [
                    'resource' => $resource,
                    'resource_id' => $resource->id,
                    'was_duplicate' => false,
                ];
            });
        } catch (QueryException $e) {
            // Race condition thật sự: 2 request gần như đồng thời cùng vượt qua
            // check ở Bước 1 (cùng thấy null). UNIQUE INDEX trên request_key
            // chặn insert thứ 2 ở tầng MySQL -> toàn bộ transaction thứ 2 rollback
            // (kể cả record vừa tạo trong callback), không có dữ liệu rác.
            if ($this->isUniqueConstraintViolation($e)) {
                $existing = IdempotencyKey::where('request_key', $requestKey)->firstOrFail();
                return $this->duplicateResult($existing);
            }

            throw $e;
        }
    }

    private function duplicateResult(IdempotencyKey $existing): array
    {
        return [
            'resource' => null,
            'resource_id' => $existing->resource_id,
            'was_duplicate' => true,
        ];
    }

    private function isUniqueConstraintViolation(QueryException $e): bool
    {
        // 23000 = SQLSTATE Integrity constraint violation (MySQL/SQLite)
        return (int) $e->getCode() === 23000;
    }
}