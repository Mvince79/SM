<?php

namespace App\Filament\Admin\Support;

/**
 * D-05 — Tập trung các ánh xạ sang Tiếng Việt cho giá trị hiển thị trong
 * Nhật ký hoạt động / Nhật ký kiểm toán (trạng thái, tên trường, log_name,
 * giá trị boolean) để giao diện không còn lộ mã tiếng Anh.
 */
class Labels
{
    /** Trạng thái hồ sơ / ticket / KYC → Tiếng Việt. */
    private static array $statuses = [
        'PENDING' => 'Chờ xử lý',
        'APPROVED' => 'Đã duyệt',
        'REJECTED' => 'Đã từ chối',
        'FOUND' => 'Đã tìm thấy',
        'MATCHED' => 'Đã khớp',
        'CLOSED' => 'Đã đóng',
        'ARCHIVED' => 'Đã lưu trữ',
        'HANDED_OVER' => 'Đã bàn giao',
        'REUNITED' => 'Đã đoàn tụ',
        'UNREACHABLE_FAMILY' => 'Không liên lạc được với gia đình',
        'OPEN' => 'Đang mở',
        'IN_PROGRESS' => 'Đang xử lý',
        'RESOLVED' => 'Đã giải quyết',
        'AWAITING' => 'Chờ phản hồi',
        'VERIFIED' => 'Đã xác thực',
        'REVIEWING' => 'Đang xem xét',
        'SUBMITTED' => 'Đã gửi',
    ];

    /** Tên trường (attribute) → nhãn Tiếng Việt trong diff. */
    private static array $fields = [
        'status' => 'Trạng thái',
        'title' => 'Tiêu đề',
        'slug' => 'Slug',
        'content' => 'Nội dung',
        'cover_image_path' => 'Ảnh bìa',
        'meta_title' => 'Meta tiêu đề',
        'meta_description' => 'Meta mô tả',
        'published_at' => 'Ngày xuất bản',
        'author_id' => 'Tác giả',
        'clue_type' => 'Loại manh mối',
        'description' => 'Mô tả',
        'lat' => 'Vĩ độ',
        'lng' => 'Kinh độ',
        'occurred_at' => 'Thời gian xảy ra',
        'is_verified' => 'Đã xác minh',
        'verified_by' => 'Người xác minh',
        'is_anonymous' => 'Ẩn danh',
        'is_hidden' => 'Đã ẩn',
        'user_id' => 'Người dùng',
        'category' => 'Danh mục',
        'subject' => 'Chủ đề',
        'priority' => 'Ưu tiên',
        'assigned_admin_id' => 'Admin phụ trách',
        'id_type' => 'Loại giấy tờ',
        'full_name_on_id' => 'Họ tên trên giấy tờ',
        'dob_on_id' => 'Ngày sinh trên giấy tờ',
        'ocr_provider' => 'Nhà cung cấp OCR',
        'face_match_score' => 'Điểm đối chiếu',
        'review_level' => 'Cấp duyệt',
        'reviewed_by' => 'Người duyệt',
        'rejected_reason' => 'Lý do từ chối',
        'verified_at' => 'Ngày xác thực',
        'is_active' => 'Đang hoạt động',
        'device_type' => 'Loại thiết bị',
        'device_name' => 'Tên thiết bị',
        'token' => 'Token',
        'last_used_at' => 'Dùng lần cuối',
    ];

    /** Tên log_name (spatie) → Tiếng Việt. */
    private static array $logNames = [
        'default' => 'Hệ thống',
        'photo_access' => 'Truy cập ảnh',
    ];

    /**
     * Dịch giá trị trạng thái. Nếu không có trong map, humanize (thay _ bằng空格)
     * và viết hoa chữ đầu – hiếm xảy ra vì đã bao phủ toàn bộ từ vựng.
     */
    public static function status(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return self::$statuses[$value]
            ?? ucfirst(mb_strtolower(str_replace('_', ' ', $value)));
    }

    /** Dịch tên trường. Không có trong map → giữ nguyên key. */
    public static function field(string $key): string
    {
        return self::$fields[$key] ?? $key;
    }

    /** Dịch log_name. Không có trong map → humanize. */
    public static function logName(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return self::$logNames[$value]
            ?? ucfirst(mb_strtolower(str_replace('_', ' ', $value)));
    }

    /** Giá trị boolean → Tiếng Việt. */
    public static function bool(bool $value): string
    {
        return $value ? 'Có' : 'Không';
    }
}
