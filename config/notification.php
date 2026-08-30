<?php

return [
    // Ngưỡng (phút) để ScanUnreadCriticalNotifications đẩy thông báo quá hạn
    // thuộc loại critical vào escalation_queue.
    'escalation_threshold_minutes' => (int) env('NOTIFICATION_ESCALATION_THRESHOLD_MINUTES', 30),

    /*
    |--------------------------------------------------------------------------
    | Nhịp nhắc xác nhận đoàn tụ (H-04)
    |--------------------------------------------------------------------------
    | - reminder_milestones: các mốc ngày (D+n) kể từ khi bàn giao mà gia đình
    |   được nhắc. Mỗi mốc là 1 lần liên hệ, gửi đúng 1 thông báo (không lặp).
    | - escalate_after_days: sau mốc này chưa xác nhận → leo thang cho admin.
    | - close_after_days: sau mốc này, nếu đã từng gửi ít nhất 1 lần nhắc mà
    |   gia đình không phản hồi → chuyển UNREACHABLE_FAMILY.
    |
    | Bảo vệ "cron sập": hồ sơ chưa từng được gửi nhắc nào (vd cron chết từ lúc
    | bàn giao, hoặc hồ sơ không có chủ sở hữu) KHÔNG được đóng oan ở close_after_days.
    */
    'reminder_milestones' => [1, 3, 5],

    'escalate_after_days' => 5,

    'close_after_days' => 7,

    /*
    |--------------------------------------------------------------------------
    | Đơn vị thời gian áp dụng cho nhịp nhắc/leo thang (H-04/H-05)
    |--------------------------------------------------------------------------
    | Đơn vị hiện tại là 'days' (mặc định) — HÀNH VI PRODUCTION KHÔNG ĐỔI so
    | với diffInDays() cũ. Đổi NOTIFICATION_REMINDER_TIME_UNIT trong .env để
    | test nhanh (ví dụ 'minutes' để nhắc sau vài phút thay vì vài ngày, hoặc
    | 'hours' / 'seconds'). Các khóa reminder_milestones / escalate_after_days
    | / close_after_days GIỮ NGUYÊN, chỉ đổi đơn vị áp dụng cho chúng.
    |
    | Giá trị hợp lệ: 'days' | 'hours' | 'minutes' | 'seconds'.
    */
    'reminder_time_unit' => env('NOTIFICATION_REMINDER_TIME_UNIT', 'days'),

    /*
    |--------------------------------------------------------------------------
    | Event type khẩn cấp (đẩy vào escalation_queue khi quá hạn chưa đọc)
    |--------------------------------------------------------------------------
    */
    'critical_event_types' => [
        'FACE_MATCH_CONFIRMED',
        'HANDOVER_READY',
        'REUNION_FAMILY_REMINDER',
        'REUNION_ADMIN_ESCALATION',
        'CASE_UNREACHABLE_FAMILY',
    ],
];
