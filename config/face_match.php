<?php

return [
    'service_url' => env('FACE_MATCH_SERVICE_URL', 'http://127.0.0.1:8001'),
    'internal_key' => env('FACE_MATCH_INTERNAL_KEY'),
    'score_threshold' => (float) env('FACE_MATCH_SCORE_THRESHOLD', 0.70),
    // Ngưỡng HIỂN THỊ cho chức năng "Tìm kiếm bằng hình ảnh" (công khai).
    // Chỉ hiện hồ sơ có tỷ lệ khớp >= ngưỡng này (mặc định 0.45) để tránh bỏ sót.
    'search_threshold' => (float) env('FACE_MATCH_SEARCH_THRESHOLD', 0.45),
    'high_confidence_threshold' => (float) env('FACE_MATCH_HIGH_CONFIDENCE_THRESHOLD', 0.85),
    'timeout' => (int) env('FACE_MATCH_TIMEOUT', 30),
    'health_timeout' => (int) env('FACE_MATCH_HEALTH_TIMEOUT', 3),
    'queue' => env('FACE_MATCH_QUEUE', 'face-match'),
    // Retry NGẮN chạy TRONG hàm xử lý khi job ép sync (onConnection('sync')).
    // Laravel Queue retry ($tries/$backoff/$timeout ở class Job) KHÔNG có tác dụng
    // với sync connection (sync thực thi tại chỗ, ném lỗi chứ không re-queue), nên
    // đây là cơ chế retry THỰC TẾ: vài lần thử, backoff tính bằng ms để vượt qua AI
    // service giật tạm thời mà KHÔNG block request upload quá lâu như backoff hàng
    // phút của queue worker. Nếu cạn số lần thử, ném lại AIServiceUnavailableException
    // để lớp gọi ngoài xử lý graceful (đánh dấu ảnh pending / failed theo Bước 1).
    'sync_retry_attempts' => (int) env('FACE_MATCH_SYNC_RETRY_ATTEMPTS', 3),
    'sync_retry_delay_ms' => (int) env('FACE_MATCH_SYNC_RETRY_DELAY_MS', 250),
    'model' => env('FACE_MATCH_MODEL', 'ArcFace'),
];
