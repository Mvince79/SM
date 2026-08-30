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
        'CONFIRMED' => 'Đã xác nhận',
        'DISMISSED' => 'Đã bác bỏ',
        'FOUND' => 'Đã tìm thấy',
        'MATCHED' => 'Đã khớp',
        'CLOSED' => 'Đã đóng',
        'ARCHIVED' => 'Đã lưu trữ',
        'HANDED_OVER' => 'Đã bàn giao',
        'REUNITED' => 'Đã đoàn tụ',
        'UNREACHABLE_FAMILY' => 'Không liên lạc được với gia đình',
        'DELETED' => 'Đã xoá',
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

    /**
     * Giá trị enum (attribute) → nhãn Tiếng Việt, dùng cho diff Nhật ký hoạt động.
     * Chỉ áp dụng cho các cột là enum THẬT SỰ; trường tự do (vd subject) KHÔNG
     * đưa vào đây. Khớp với bản dịch đã dùng ở SupportTicketsTable để nhất quán
     * (BUG = "Báo lỗi", không dùng "Lỗi kỹ thuật" để tránh 2 nhãn khác nhau
     * cùng 1 giá trị trong admin).
     */
    private static array $attributeValues = [
        'category' => [
            'BUG' => 'Báo lỗi',
            'COMPLAINT' => 'Khiếu nại',
            'FEEDBACK' => 'Góp ý',
            'FRAUD_SUSPECT' => 'Nghi ngờ lừa đảo',
        ],
        'priority' => [
            'LOW' => 'Thấp',
            'MEDIUM' => 'Trung bình',
            'HIGH' => 'Cao',
        ],
    ];

    /** Tên log_name (spatie) → Tiếng Việt. */
    private static array $logNames = [
        'default' => 'Hệ thống',
        'photo_access' => 'Truy cập ảnh',
    ];

    /**
     * Tên sự kiện activity log (cột `event` / nội dung mặc định cột `description`
     * do trait LogsActivity sinh ra) → Tiếng Việt.
     * Khớp chính tả với cột "Sự kiện" của ActivitiesTable (Tạo mới / Cập nhật / Xóa)
     * để hai cột không dịch lệch nhau. Giá trị không phải token sự kiện (vd mô tả
     * tự do do dev log bằng activity()->log('...')) → giữ nguyên, không humanize.
     */
    private static array $events = [
        'created' => 'Tạo mới',
        'updated' => 'Cập nhật',
        'deleted' => 'Xóa',
        'restored' => 'Khôi phục',
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

    /** Dịch giá trị enum của một trường (category/priority...). Không map → giữ nguyên. */
    public static function attributeValue(string $key, ?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return self::$attributeValues[$key][$value] ?? $value;
    }

    /**
     * Rút gọn User-Agent thành mô tả dễ hiểu (HĐH + trình duyệt) cho Nhật ký
     * kiểm toán. Chuỗi lạ/rỗng → 'Không xác định' (không để trống hay lỗi).
     */
    public static function userAgent(?string $ua): string
    {
        if ($ua === null || $ua === '') {
            return 'Không xác định';
        }

        $os = 'Không xác định';
        if (stripos($ua, 'windows') !== false) {
            $os = 'Windows';
        } elseif (stripos($ua, 'mac os') !== false || stripos($ua, 'macintosh') !== false) {
            $os = 'Mac';
        } elseif (stripos($ua, 'android') !== false) {
            $os = 'Android';
        } elseif (stripos($ua, 'iphone') !== false || stripos($ua, 'ipad') !== false || stripos($ua, 'ios') !== false) {
            $os = 'iOS';
        } elseif (stripos($ua, 'linux') !== false) {
            $os = 'Linux';
        }

        $browser = 'Không xác định';
        if (stripos($ua, 'edg/') !== false || stripos($ua, 'edge') !== false) {
            $browser = 'Edge';
        } elseif (stripos($ua, 'chrome') !== false) {
            $browser = 'Chrome';
        } elseif (stripos($ua, 'firefox') !== false) {
            $browser = 'Firefox';
        } elseif (stripos($ua, 'safari') !== false) {
            $browser = 'Safari';
        }

        if ($os === 'Không xác định' && $browser === 'Không xác định') {
            return 'Không xác định';
        }
        if ($os === 'Không xác định') {
            return "Trình duyệt {$browser}";
        }
        if ($browser === 'Không xác định') {
            return "Hệ điều hành {$os}";
        }

        return "Trình duyệt {$browser} trên {$os}";
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

    /**
     * Dịch tên sự kiện activity log. Token đã biết → Tiếng Việt (khớp cột "Sự kiện").
     * Token lạ (mô tả tự do) → giữ nguyên. Rỗng → '—'.
     */
    public static function event(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return self::$events[$value] ?? $value;
    }

    /** Giá trị boolean → Tiếng Việt. */
    public static function bool(bool $value): string
    {
        return $value ? 'Có' : 'Không';
    }
}
