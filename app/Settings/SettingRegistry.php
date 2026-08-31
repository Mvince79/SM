<?php

namespace App\Settings;

/**
 * SettingRegistry — nguồn định nghĩa DUY NHẤT cho mọi thông số admin được phép chỉnh.
 *
 * Mỗi mục khai báo đầy đủ metadata để vừa dựng form (label/help/min/max/step/
 * options) vừa validate (type/min/max/options) và clamp (kéo về giới hạn) ở
 * SettingService. Giá trị `default` PHẢI khớp với giá trị mặc định trong
 * config/notification.php & config/face_match.php để hành vi không đổi khi chưa
 * có bản ghi nào trong DB.
 *
 * Mục đích "rào chắn": admin KHÔNG thể nhập giá trị phi logic vì mọi input đều
 * bị giới hạn bởi min/max/options và các ràng buộc chéo (xem SettingService).
 */
final class SettingRegistry
{
    public const GROUP_NOTIFICATION = 'notification';

    public const GROUP_FACE_MATCH = 'face_match';

    public const GROUP_PHOTO_SEARCH = 'photo_search';

    /** @var array<string, array<string, mixed>> */
    private static array $definitions = [
        // ───────────── Nhắc nhở & thông báo (config/notification.php) ─────────────
        'reminder_time_unit' => [
            'group' => self::GROUP_NOTIFICATION,
            'key' => 'reminder_time_unit',
            'label' => 'Đơn vị thời gian áp dụng',
            'help' => 'Áp dụng cho các mốc nhắc, mức leo thang và đóng hồ sơ bên dưới. Chọn "Giây" hoặc "Phút" để test nhanh (chưa deploy).',
            'type' => 'select',
            'default' => 'days',
            'options' => [
                'seconds' => 'Giây',
                'minutes' => 'Phút',
                'hours' => 'Giờ',
                'days' => 'Ngày',
            ],
        ],
        'reminder_milestones' => [
            'group' => self::GROUP_NOTIFICATION,
            'key' => 'reminder_milestones',
            'label' => 'Các mốc nhắc (theo đơn vị đã chọn)',
            'help' => 'VD: 5, 10 nghĩa là nhắc vào mốc 5 và 10 (theo đơn vị). Tự động sắp tăng dần, không trùng, mỗi mốc ≥ 1 và nhỏ hơn mức đóng hồ sơ.',
            'type' => 'milestones',
            'default' => [5, 10],
        ],
        'escalate_after_days' => [
            'group' => self::GROUP_NOTIFICATION,
            'key' => 'escalate_after_days',
            'label' => 'Leo thang sau (theo đơn vị)',
            'help' => 'Sau mốc này mà gia đình chưa xác nhận → đẩy thông báo leo thang cho admin.',
            'type' => 'int',
            'default' => 15,
            'min' => 1,
            'max' => 365,
        ],
        'close_after_days' => [
            'group' => self::GROUP_NOTIFICATION,
            'key' => 'close_after_days',
            'label' => 'Đóng hồ sơ sau (theo đơn vị)',
            'help' => 'Sau mốc này, nếu đã nhắc ít nhất 1 lần mà gia đình không phản hồi → chuyển "Không liên lạc được". Phải LỚN HƠN mức leo thang.',
            'type' => 'int',
            'default' => 20,
            'min' => 2,
            'max' => 730,
        ],
        'escalation_threshold_minutes' => [
            'group' => self::GROUP_NOTIFICATION,
            'key' => 'escalation_threshold_minutes',
            'label' => 'Ngưỡng đẩy thông báo khẩn cấp (phút)',
            'help' => 'Thông báo loại khẩn cấp chưa đọc quá số phút này → đưa vào hàng đợi leo thang.',
            'type' => 'int',
            'default' => 30,
            'min' => 1,
            'max' => 1440,
            'unit' => 'phút',
        ],

        // ───────────── Tham số AI so khớp (config/face_match.php) ─────────────
        'score_threshold' => [
            'group' => self::GROUP_FACE_MATCH,
            'key' => 'score_threshold',
            'label' => 'Ngưỡng khớp (match) — gợi ý AI',
            'help' => 'Tỷ lệ cosin ≥ ngưỡng này mới coi là khớp và gợi ý admin xác minh. Quá thấp = nhiều kết quả rác, quá cao = bỏ sót.',
            'type' => 'float',
            'default' => 0.70,
            'min' => 0.30,
            'max' => 0.95,
            'step' => 0.01,
        ],
        'search_threshold' => [
            'group' => self::GROUP_FACE_MATCH,
            'key' => 'search_threshold',
            'label' => 'Ngưỡng hiển thị tìm kiếm công khai',
            'help' => 'Kết quả "Tìm kiếm bằng hình ảnh" chỉ hiện khi ≥ ngưỡng này (để tránh bỏ sót). Phải NHỎ HƠN hoặc BẰNG ngưỡng khớp.',
            'type' => 'float',
            'default' => 0.45,
            'min' => 0.20,
            'max' => 0.95,
            'step' => 0.01,
        ],
        'high_confidence_threshold' => [
            'group' => self::GROUP_FACE_MATCH,
            'key' => 'high_confidence_threshold',
            'label' => 'Ngưỡng tin cậy cao',
            'help' => 'Tỷ lệ ≥ ngưỡng này được đánh dấu "tin cậy cao". Phải LỚN HƠN ngưỡng khớp.',
            'type' => 'float',
            'default' => 0.85,
            'min' => 0.50,
            'max' => 0.99,
            'step' => 0.01,
        ],
        'timeout' => [
            'group' => self::GROUP_FACE_MATCH,
            'key' => 'timeout',
            'label' => 'Timeout gọi AI (giây)',
            'help' => 'Thời gian chờ tối đa mỗi lần gọi AI service. Quá thấp dễ timeout, quá cao làm treo request.',
            'type' => 'int',
            'default' => 30,
            'min' => 5,
            'max' => 60,
            'unit' => 'giây',
        ],
        'health_timeout' => [
            'group' => self::GROUP_FACE_MATCH,
            'key' => 'health_timeout',
            'label' => 'Timeout kiểm tra sức khỏe AI (giây)',
            'help' => 'Thời gian chờ khi ping /health của AI service.',
            'type' => 'int',
            'default' => 3,
            'min' => 1,
            'max' => 10,
            'unit' => 'giây',
        ],
        'sync_retry_attempts' => [
            'group' => self::GROUP_FACE_MATCH,
            'key' => 'sync_retry_attempts',
            'label' => 'Số lần thử lại (chế độ đồng bộ)',
            'help' => 'Khi AI service giật tạm thời, thử lại tối đa N lần trước khi báo lỗi. 0 = không thử lại.',
            'type' => 'int',
            'default' => 3,
            'min' => 0,
            'max' => 10,
        ],
        'sync_retry_delay_ms' => [
            'group' => self::GROUP_FACE_MATCH,
            'key' => 'sync_retry_delay_ms',
            'label' => 'Độ trễ giữa các lần thử (ms)',
            'help' => 'Khoảng nghỉ giữa hai lần thử lại (miligiây).',
            'type' => 'int',
            'default' => 250,
            'min' => 0,
            'max' => 2000,
            'unit' => 'ms',
        ],

        // ───────────── Tìm kiếm bằng hình ảnh (giới hạn tốc độ) ─────────────
        'photo_search_rate_limit' => [
            'group' => self::GROUP_PHOTO_SEARCH,
            'key' => 'rate_limit',
            'label' => 'Số lần tìm kiếm tối đa',
            'help' => 'Mỗi địa chỉ IP được thực hiện tối đa bao nhiêu lần tìm kiếm bằng hình ảnh trong khoảng thời gian bên dưới. Vượt quá sẽ bị tạm khóa đến hết cửa sổ.',
            'type' => 'int',
            'default' => 5,
            'min' => 1,
            'max' => 100,
            'unit' => 'lần',
        ],
        'photo_search_rate_window' => [
            'group' => self::GROUP_PHOTO_SEARCH,
            'key' => 'rate_window',
            'label' => 'Khoảng thời gian giới hạn (phút)',
            'help' => 'Cửa sổ thời gian áp dụng giới hạn trên. Mặc định 60 phút (= 1 giờ). Quá ngắn dễ gây phiền, quá dài thì giảm bảo vệ quota AI.',
            'type' => 'int',
            'default' => 60,
            'min' => 1,
            'max' => 1440,
            'unit' => 'phút',
        ],
    ];

    /**
     * Toàn bộ định nghĩa, keyed bởi field (tên dùng trong form & service).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return self::$definitions;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function group(string $group): array
    {
        return collect(self::$definitions)
            ->filter(fn (array $d): bool => $d['group'] === $group)
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(string $field): ?array
    {
        return self::$definitions[$field] ?? null;
    }

    public static function defaultValue(string $group, string $key): mixed
    {
        foreach (self::$definitions as $def) {
            if ($def['group'] === $group && $def['key'] === $key) {
                return $def['default'];
            }
        }

        return null;
    }
}
