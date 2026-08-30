<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * H-04/H-05 — Khoảng thời gian đã trôi qua, tính theo ĐƠN VỊ cấu hình
 * (notification.reminder_time_unit: 'days' | 'hours' | 'minutes' | 'seconds').
 *
 * Dùng chung cho CẢ HAI luồng nhắc/leo thang:
 *  - HANDOVER: hồ sơ FOUND chờ gia đình xác nhận bàn giao.
 *  - REUNION:  hồ sơ HANDED_OVER chờ gia đình xác nhận đoàn tụ.
 * -> Tránh lặp code và cho phép đổi đơn vị thời gian linh hoạt khi test
 *   (vd set NOTIFICATION_REMINDER_TIME_UNIT=minutes để nhắc sau vài phút
 *   thay vì vài ngày).
 *
 * Mặc định 'days' → HÀNH VI PRODUCTION KHÔNG ĐỔI so với diffInDays() cũ.
 */
final class TimeUnit
{
    /** Map đơn vị cấu hình → method Carbon tương ứng (cùng signature: date, absolute). */
    private const DIFF_METHODS = [
        'days'    => 'diffInDays',
        'hours'   => 'diffInHours',
        'minutes' => 'diffInMinutes',
        'seconds' => 'diffInSeconds',
    ];

    /** Đơn vị mặc định — PHẢI là 'days' để giữ nguyên hành vi cũ. */
    public const DEFAULT_UNIT = 'days';

    /**
     * Đơn vị hiện tại (từ config, đã validate). Về DEFAULT_UNIT nếu cấu hình
     * sai/trống/rỗng để luôn an toàn.
     */
    public static function unit(): string
    {
        $unit = config('notification.reminder_time_unit', self::DEFAULT_UNIT);

        return \array_key_exists($unit, self::DIFF_METHODS) ? $unit : self::DEFAULT_UNIT;
    }

    /**
     * Số lượng đơn vị (ngày/giờ/phút/giây) đã trôi qua kể từ $since đến $now.
     * Thay thế trực tiếp cho `(int) Carbon::parse($since)->diffInDays($now)`.
     *
     * @param \DateTimeInterface|string|null $since
     */
    public static function elapsedSince($since, ?Carbon $now = null): int
    {
        if (! $since) {
            return 0;
        }

        $now ??= now();
        $method = self::DIFF_METHODS[self::unit()];

        return (int) Carbon::parse($since)->{$method}($now);
    }
}
