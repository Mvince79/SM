<?php

namespace App\Support;

use App\Exceptions\AIServiceUnavailableException;
use Illuminate\Support\Facades\Config;

/**
 * Retry thủ công cho các lời gọi AI service khi job chạy ĐỒNG BỘ (onConnection('sync')).
 *
 * Laravel Queue retry ($tries / $backoff / $timeout khai báo trên class Job) KHÔNG có
 * tác dụng với sync connection — sync thực thi tại chỗ và ném lỗi chứ không re-queue —,
 * nên ta tự retry trong hàm xử lý với backoff NGẮN (mặc định vài trăm ms). Backoff ngắn
 * đủ để vượt qua AI service giật tạm thời trong một request upload, nhưng không block
 * request quá lâu như backoff hàng phút của queue worker.
 *
 * Chỉ retry khi gặp AIServiceUnavailableException (AI gián đoạn / timeout). Các lỗi khác
 * (Crypt, DB, logic) không được retry mà lan truyền ngay. Nếu cạn số lần thử, ném lại
 * AIServiceUnavailableException để lớp gọi ngoài xử lý graceful (pending / failed).
 */
class AiCallRetry
{
    public static function run(callable $callable, ?int $attempts = null, ?int $delayMs = null): mixed
    {
        $attempts = $attempts ?? (int) Config::get('face_match.sync_retry_attempts', 3);
        $delayMs = $delayMs ?? (int) Config::get('face_match.sync_retry_delay_ms', 250);

        $last = null;

        for ($i = 0; $i < $attempts; $i++) {
            try {
                return $callable();
            } catch (AIServiceUnavailableException $e) {
                $last = $e;

                if ($i < $attempts - 1) {
                    usleep((int) ($delayMs * 1000));
                }
            }
        }

        throw $last ?? new AIServiceUnavailableException('AI call failed after retries.');
    }
}
