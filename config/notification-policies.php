<?php

use App\Models\NotificationEvent;
use App\Notifications\RoleChangedNotification;
use App\Services\Notification\Strategies\CaseApprovedInProvinceStrategy;
use App\Services\Notification\Strategies\FaceMatchConfirmedForAdminStrategy;
use App\Services\Notification\Strategies\FaceMatchConfirmedForFamilyStrategy;
use App\Services\Notification\Strategies\HandoverReadyForFamilyStrategy;

/**
 * Chính sách nội dung & kênh gửi cho từng loại thông báo.
 *
 * Có 3 khối:
 *  - strategies: các loại cần logic render phức tạp → chỉ định class riêng.
 *  - content:    các loại chỉ cần ghép chuỗi → khai báo tại đây, render bởi
 *                ConfigDrivenStrategy. Tránh phải viết hàng chục class rỗng.
 *  - channels:   loại thông báo nào đi qua kênh nào, cho từng role.
 *
 * Khoá tra cứu là (EVENT_TYPE, ROLE). Role '*' là mặc định khi không khai báo
 * riêng. Chuông (database) luôn được gửi, không cần khai báo.
 *
 * Placeholder trong title/body lấy từ NotificationEvent::payload, dạng :key
 * (vd :case_code, :full_name, :reason). Thiếu key thì bị bỏ trắng chứ không nổ.
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Strategy class (dùng cho loại cần logic riêng)
    |--------------------------------------------------------------------------
    */
    'strategies' => [
        NotificationEvent::TYPE_FACE_MATCH_CONFIRMED => [
            'USER' => FaceMatchConfirmedForFamilyStrategy::class,
            'VOLUNTEER' => FaceMatchConfirmedForFamilyStrategy::class,
            'ADMIN' => FaceMatchConfirmedForAdminStrategy::class,
            'MODERATOR' => FaceMatchConfirmedForAdminStrategy::class,
        ],
        NotificationEvent::TYPE_CASE_APPROVED_IN_PROVINCE => [
            'USER' => CaseApprovedInProvinceStrategy::class,
            'VOLUNTEER' => CaseApprovedInProvinceStrategy::class,
        ],
        NotificationEvent::TYPE_HANDOVER_READY => [
            'USER' => HandoverReadyForFamilyStrategy::class,
            'VOLUNTEER' => HandoverReadyForFamilyStrategy::class,
        ],
        NotificationEvent::TYPE_ROLE_CHANGED => [
            'USER' => RoleChangedNotification::class,
            'VOLUNTEER' => RoleChangedNotification::class,
            'MODERATOR' => RoleChangedNotification::class,
            'ADMIN' => RoleChangedNotification::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Nội dung khai báo (render bởi ConfigDrivenStrategy)
    |--------------------------------------------------------------------------
    | 'url' chọn đích đến khi người nhận bấm vào thông báo:
    |   'case'       → trang công khai hồ sơ mất tích
    |   'found'      → trang tin tìm thấy trẻ
    |   'admin_case' → trang hồ sơ trong panel quản trị
    |   null         → trang chi tiết thông báo
    */
    'content' => [
        // ─── Hồ sơ mất tích: tiếp nhận & duyệt ──────────────────────────────
        NotificationEvent::TYPE_CASE_SUBMITTED => [
            'USER' => [
                'title' => 'Đã nhận tin báo :case_code',
                'body' => 'Cảm ơn bạn đã báo tin tìm :full_name. Hồ sơ đang chờ quản trị viên kiểm duyệt. '
                    . 'Chúng tôi sẽ thông báo cho bạn ngay khi hồ sơ được công khai.',
                'url' => 'case',
            ],
            'ADMIN' => [
                'title' => 'Tin mới cần duyệt: :case_code',
                'body' => 'Có hồ sơ tìm trẻ mới (:full_name) đang chờ duyệt. '
                    . 'Vui lòng kiểm tra và duyệt sớm để thông tin được lan toả kịp thời.',
                'url' => 'admin_case',
            ],
        ],

        NotificationEvent::TYPE_CASE_APPROVED => [
            '*' => [
                'title' => 'Hồ sơ :case_code đã được duyệt',
                'body' => 'Hồ sơ tìm :full_name đã được duyệt và đang hiển thị công khai. '
                    . 'Hãy chia sẻ hồ sơ để nhiều người cùng để ý giúp bạn.',
                'url' => 'case',
            ],
        ],

        NotificationEvent::TYPE_CASE_REJECTED => [
            '*' => [
                'title' => 'Hồ sơ :case_code chưa được duyệt',
                'body' => 'Lý do: :reason. Bạn có thể chỉnh sửa thông tin và gửi lại, '
                    . 'hoặc liên hệ Trung tâm hỗ trợ nếu cần trợ giúp.',
                'url' => 'case',
            ],
        ],

        NotificationEvent::TYPE_CASE_MARKED_FOUND => [
            '*' => [
                'title' => 'Đã ghi nhận tìm thấy :full_name',
                'body' => 'Hồ sơ :case_code được cập nhật là đã tìm thấy. '
                    . 'Chúng tôi sẽ hướng dẫn bạn các bước tiếp theo để đón con về.',
                'url' => 'case',
            ],
        ],

        // ─── Bàn giao & đoàn tụ ─────────────────────────────────────────────
        NotificationEvent::TYPE_FOUND_CASE_HANDED_OVER => [
            '*' => [
                'title' => 'Trẻ bạn báo tin đã được bàn giao',
                'body' => 'Tin bạn báo đã giúp xác định hồ sơ :case_code, và trẻ đã được bàn giao '
                    . 'cho cơ quan chức năng để trao lại gia đình. Cảm ơn bạn rất nhiều.',
                'url' => 'case',
            ],
        ],

        NotificationEvent::TYPE_REUNION_CONFIRMED => [
            'USER' => [
                'title' => 'Đã xác nhận đoàn tụ — hồ sơ :case_code đã đóng',
                'body' => 'Chúc mừng gia đình đã đón :full_name về nhà. Hồ sơ đã được đóng và '
                    . 'không còn hiển thị công khai. Cảm ơn bạn đã tin tưởng TìmTrẻ.vn.',
                'url' => 'case',
            ],
            'ADMIN' => [
                'title' => 'Hồ sơ :case_code đã đoàn tụ',
                'body' => 'Gia đình đã xác nhận đón :full_name về nhà. Hồ sơ chuyển sang trạng thái '
                    . 'đã đoàn tụ; các việc cần hỗ trợ liên quan đã được đóng tự động.',
                'url' => 'admin_case',
            ],
        ],

        NotificationEvent::TYPE_FOUND_CASE_REUNITED => [
            '*' => [
                'title' => 'Trẻ đã về với gia đình — cảm ơn bạn',
                'body' => 'Tin báo của bạn đã góp phần đưa :full_name trở về với gia đình. '
                    . 'Hồ sơ :case_code đã được đóng. Cảm ơn bạn vì điều tử tế này.',
                'url' => 'case',
            ],
        ],

        // ─── Yêu cầu hỗ trợ (Support Ticket) ──────────────────────────────────
        NotificationEvent::TYPE_TICKET_CREATED => [
            'ADMIN' => [
                'title' => 'Yêu cầu hỗ trợ mới #:id',
                'body' => 'Có yêu cầu hỗ trợ mới từ :full_name — ":subject". '
                    . 'Vui lòng vào xem và xử lý sớm.',
                'url' => null,
            ],
            'MODERATOR' => [
                'title' => 'Yêu cầu hỗ trợ mới #:id',
                'body' => 'Có yêu cầu hỗ trợ mới từ :full_name — ":subject". '
                    . 'Vui lòng vào xem và xử lý sớm.',
                'url' => null,
            ],
        ],

        // ─── Yêu cầu hỗ trợ: phản hồi (chỉ public reply) ───────────────────────
        NotificationEvent::TYPE_TICKET_REPLIED => [
            'USER' => [
                'title' => 'Yêu cầu hỗ trợ #:id có phản hồi mới',
                'body' => 'Đội ngũ hỗ trợ vừa phản hồi yêu cầu ":subject" của bạn. '
                    . 'Vui lòng xem chi tiết để cập nhật thông tin.',
                'url' => 'support',
            ],
        ],

        // ─── Yêu cầu hỗ trợ: thay đổi trạng thái ───────────────────────────────
        NotificationEvent::TYPE_TICKET_RESOLVED => [
            'USER' => [
                'title' => 'Yêu cầu hỗ trợ #:id đã được xử lý',
                'body' => 'Yêu cầu ":subject" của bạn đã được đánh dấu xử lý xong. '
                    . 'Cảm ơn bạn đã liên hệ với chúng tôi.',
                'url' => 'support',
            ],
        ],

        NotificationEvent::TYPE_TICKET_CLOSED => [
            'USER' => [
                'title' => 'Yêu cầu hỗ trợ #:id đã đóng',
                'body' => 'Yêu cầu ":subject" của bạn đã được đóng. '
                    . 'Nếu bạn cần hỗ trợ thêm, hãy gửi yêu cầu mới bất cứ lúc nào.',
                'url' => 'support',
            ],
        ],

        NotificationEvent::TYPE_REUNION_FAMILY_REMINDER => [
            '*' => [
                'title' => 'Vui lòng xác nhận đã nhận lại con',
                'body' => 'Hồ sơ :case_code (:full_name) đã được bàn giao. Khi bạn đã đón con về an toàn, '
                    . 'vui lòng bấm xác nhận để chúng tôi đóng hồ sơ. Nếu bạn cần hỗ trợ, hãy phản hồi ngay.',
                'url' => 'case',
            ],
        ],

        NotificationEvent::TYPE_REUNION_ADMIN_ESCALATION => [
            '*' => [
                'title' => 'Cần xác minh đoàn tụ hộ (gia đình chưa phản hồi)',
                'body' => 'Hồ sơ :case_code (:full_name) đã bàn giao :days_since_handover ngày nhưng gia đình '
                    . 'chưa xác nhận sau :reminder_count lần nhắc. Vui lòng gọi điện xác minh.',
                'url' => 'admin_case',
            ],
        ],

        // H-05 — Nhắc gia đình xác nhận "bé đã được bàn giao" (bước trước đoàn tụ).
        NotificationEvent::TYPE_HANDOVER_FAMILY_REMINDER => [
            '*' => [
                'title' => 'Vui lòng xác nhận bé đã được bàn giao',
                'body' => 'Kết quả so khớp của hồ sơ :case_code (:full_name) đã được xác nhận. Nếu cơ quan chức '
                    . 'năng đã liên hệ và bàn giao bé cho bạn, vui lòng bấm "Xác nhận bé đã được bàn giao" để '
                    . 'chúng tôi cập nhật. Nếu bạn cần hỗ trợ, hãy phản hồi ngay.',
                'url' => 'case',
            ],
        ],

        // H-05 — Leo thang admin khi FOUND quá hạn chưa gia đình xác nhận bàn giao.
        NotificationEvent::TYPE_HANDOVER_ADMIN_ESCALATION => [
            '*' => [
                'title' => 'Cần xác minh bàn giao hộ (gia đình chưa phản hồi)',
                'body' => 'Hồ sơ :case_code (:full_name) đã xác nhận khớp :days_since_handover ngày nhưng gia '
                    . 'đình chưa xác nhận bàn giao sau :reminder_count lần nhắc. Vui lòng gọi điện xác minh.',
                'url' => 'admin_case',
            ],
        ],

        NotificationEvent::TYPE_CASE_UNREACHABLE_FAMILY => [
            'USER' => [
                'title' => 'Chúng tôi chưa liên hệ được với bạn',
                'body' => 'Hồ sơ :case_code đã bàn giao nhưng chưa nhận được xác nhận từ bạn. '
                    . 'Hồ sơ tạm chuyển sang trạng thái chờ liên hệ. Vui lòng liên hệ Trung tâm hỗ trợ để tiếp tục.',
                'url' => 'case',
            ],
            'ADMIN' => [
                'title' => 'Không liên lạc được gia đình: :case_code',
                'body' => 'Hồ sơ :case_code (:full_name) đã nhắc đủ số lần nhưng gia đình không phản hồi. '
                    . 'Hồ sơ cần được xử lý thủ công.',
                'url' => 'admin_case',
            ],
        ],

        // ─── Tin tìm thấy trẻ ───────────────────────────────────────────────
        NotificationEvent::TYPE_FOUND_SUBMITTED => [
            'USER' => [
                'title' => 'Đã nhận tin báo tìm thấy trẻ',
                'body' => 'Cảm ơn bạn đã báo tin. Tin đang chờ quản trị viên kiểm duyệt, '
                    . 'đồng thời hệ thống đang tự đối chiếu với các hồ sơ đang tìm kiếm.',
                'url' => 'found',
            ],
            'ADMIN' => [
                'title' => 'Tin tìm thấy trẻ mới cần duyệt',
                'body' => 'Có tin báo tìm thấy trẻ mới đang chờ duyệt. Vui lòng kiểm tra và đối chiếu '
                    . 'với các hồ sơ đang tìm kiếm.',
                'url' => 'found',
            ],
        ],

        NotificationEvent::TYPE_FOUND_APPROVED => [
            '*' => [
                'title' => 'Tin báo tìm thấy trẻ đã được duyệt',
                'body' => 'Tin bạn báo đã được duyệt và công khai để gia đình có thể nhận ra con mình. '
                    . 'Hệ thống cũng đang tự đối chiếu tin của bạn với các hồ sơ đang tìm kiếm.',
                'url' => 'found',
            ],
        ],

        NotificationEvent::TYPE_FOUND_REJECTED => [
            '*' => [
                'title' => 'Tin báo tìm thấy trẻ chưa được duyệt',
                'body' => 'Lý do: :reason. Nếu bạn có thêm thông tin hoặc ảnh rõ hơn, '
                    . 'hãy bổ sung và gửi lại giúp chúng tôi.',
                'url' => 'found',
            ],
        ],

        // ─── Xoá hồ sơ (sự kiện riêng với Từ chối; không có :reason) ───────────
        NotificationEvent::TYPE_FOUND_DELETED => [
            '*' => [
                'title' => 'Tin báo tìm thấy trẻ đã bị xoá',
                'body' => 'Tin bạn báo tìm thấy trẻ đã bị quản trị viên xoá khỏi hệ thống. '
                    . 'Nếu bạn cho rằng đây là nhầm lẫn, hãy liên hệ Trung tâm hỗ trợ để được hỗ trợ.',
                'url' => 'found',
            ],
        ],

        NotificationEvent::TYPE_CASE_DELETED => [
            '*' => [
                'title' => 'Hồ sơ :case_code đã bị xoá',
                'body' => 'Hồ sơ tìm :full_name (:case_code) của bạn đã bị quản trị viên xoá khỏi hệ thống. '
                    . 'Nếu bạn cho rằng đây là nhầm lẫn, hãy liên hệ Trung tâm hỗ trợ để được hỗ trợ.',
                'url' => 'case',
            ],
        ],

        // ─── Khôi phục hồ sơ (từ thùng rác) ────────────────────────────────────
        NotificationEvent::TYPE_FOUND_RESTORED => [
            '*' => [
                'title' => 'Tin báo tìm thấy trẻ đã được khôi phục',
                'body' => 'Tin bạn báo tìm thấy trẻ đã được quản trị viên khôi phục và hiển thị lại trên hệ thống.',
                'url' => 'found',
            ],
        ],

        NotificationEvent::TYPE_CASE_RESTORED => [
            '*' => [
                'title' => 'Hồ sơ :case_code đã được khôi phục',
                'body' => 'Hồ sơ tìm :full_name (:case_code) của bạn đã được quản trị viên khôi phục '
                    . 'và hiển thị lại trên hệ thống.',
                'url' => 'case',
            ],
        ],

        // ─── Manh mối ───────────────────────────────────────────────────────
        NotificationEvent::TYPE_CLUE_SUBMITTED => [
            'USER' => [
                'title' => 'Có manh mối mới cho hồ sơ :case_code',
                'body' => 'Một người vừa gửi manh mối về :full_name. Hãy xem chi tiết; '
                    . 'quản trị viên cũng đang kiểm tra để xác minh thông tin này.',
                'url' => 'case',
            ],
            'ADMIN' => [
                'title' => 'Manh mối mới cần xác minh: :case_code',
                'body' => 'Có manh mối mới cho hồ sơ :case_code (:full_name) đang chờ xác minh.',
                'url' => 'admin_case',
            ],
        ],

        NotificationEvent::TYPE_CLUE_VERIFIED => [
            '*' => [
                'title' => 'Một manh mối đã được xác minh',
                'body' => 'Manh mối về hồ sơ :case_code (:full_name) đã được quản trị viên xác minh là đáng tin cậy. '
                    . 'Hãy xem chi tiết để nắm thông tin mới nhất.',
                'url' => 'case',
            ],
        ],

        NotificationEvent::TYPE_CLUE_VERIFIED_FOR_REPORTER => [
            '*' => [
                'title' => 'Manh mối bạn gửi đã được xác minh',
                'body' => 'Manh mối bạn gửi cho hồ sơ :case_code đã được quản trị viên xác minh là đáng tin cậy '
                    . 'và đang giúp ích cho việc tìm kiếm. Cảm ơn bạn.',
                'url' => 'case',
            ],
        ],

        // ─── AI đối sánh ────────────────────────────────────────────────────
        NotificationEvent::TYPE_FACE_MATCH_SUGGESTED => [
            'USER' => [
                'title' => 'AI phát hiện khả năng khớp :score_percent%',
                'body' => 'Hệ thống AI thấy một tin tìm thấy trẻ có thể khớp với hồ sơ :case_code (:full_name), '
                    . 'mức độ :confidence_label. Quản trị viên đang kiểm tra và sẽ thông báo kết quả cho bạn.',
                'url' => 'case',
            ],
            'ADMIN' => [
                'title' => 'AI gợi ý khớp :score_percent% — cần kiểm tra',
                'body' => 'AI gợi ý hồ sơ :case_code (:full_name) khớp :score_percent% (:confidence_label) '
                    . 'với một tin tìm thấy trẻ. Vui lòng vào xem và xác nhận hoặc bác bỏ.',
                'url' => 'admin_case',
            ],
        ],

        // ─── eKYC cần admin duyệt thủ công ──────────────────────────────────────
        NotificationEvent::TYPE_KYC_REVIEW_REQUIRED => [
            'ADMIN' => [
                'title' => 'Có hồ sơ eKYC cần duyệt',
                'body' => 'Hồ sơ xác thực của :full_name (mức :review_level) đã xử lý nhưng chưa tự xác thực '
                    . '— cần admin xem xét và duyệt thủ công.',
                'url' => 'kyc',
            ],
            'MODERATOR' => [
                'title' => 'Có hồ sơ eKYC cần duyệt',
                'body' => 'Hồ sơ xác thực của :full_name (mức :review_level) đã xử lý nhưng chưa tự xác thực '
                    . '— cần admin xem xét và duyệt thủ công.',
                'url' => 'kyc',
            ],
        ],

        // A-?? — Lỗi xử lý AI/eKYC (ProcessPhotoJob, TriggerFaceMatchJob, ProcessKycJob).
        // Bắn từ AIJobFailureNotifier; payload có :job và :message.
        NotificationEvent::TYPE_AI_PROCESSING_FAILED => [
            'ADMIN' => [
                'title' => 'Xử lý AI/eKYC thất bại',
                'body' => 'Tác vụ :job gặp lỗi: :message. Vui lòng kiểm tra dịch vụ AI và thử lại.',
                'url' => null,
            ],
            'MODERATOR' => [
                'title' => 'Xử lý AI/eKYC thất bại',
                'body' => 'Tác vụ :job gặp lỗi: :message. Vui lòng kiểm tra dịch vụ AI và thử lại.',
                'url' => null,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ma trận kênh gửi
    |--------------------------------------------------------------------------
    | Chuông (database) luôn bật cho mọi loại — không cần khai báo.
    | Khai báo ở đây là các kênh CỘNG THÊM: 'mail', 'push'.
    |
    | Nguyên tắc: admin nhận email cho việc CẦN HÀNH ĐỘNG (tin mới cần duyệt,
    | AI gợi ý khớp, leo thang, không liên lạc được) — không nhận email cho các
    | event mang tính thông tin, tránh biến hộp thư admin thành nơi bỏ đi.
    */
    'channels' => [
        'default' => ['mail'],

        NotificationEvent::TYPE_KYC_REVIEW_REQUIRED => [
            'ADMIN' => ['mail'],
            'MODERATOR' => ['mail'],
        ],

        NotificationEvent::TYPE_CASE_SUBMITTED => [
            'USER' => ['mail'],
            'ADMIN' => ['mail'],
            'MODERATOR' => ['mail'],
        ],

        // Cộng đồng cùng tỉnh: chuông + push, KHÔNG email.
        // Push đúng mục đích tính năng "theo dõi khu vực" (fcm_tokens có watch_lat/lng),
        // còn gửi email cho toàn bộ user một tỉnh mỗi khi có hồ sơ mới là con đường
        // ngắn nhất để bị đánh dấu spam và mất uy tín tên miền gửi.
        NotificationEvent::TYPE_CASE_APPROVED_IN_PROVINCE => [
            '*' => ['push'],
        ],

        // Khẩn cấp — bật cả push (hiện chưa có thiết bị đăng ký nên tự bỏ qua).
        NotificationEvent::TYPE_FACE_MATCH_CONFIRMED => [
            '*' => ['mail', 'push'],
        ],
        NotificationEvent::TYPE_HANDOVER_READY => [
            '*' => ['mail', 'push'],
        ],
        NotificationEvent::TYPE_REUNION_FAMILY_REMINDER => [
            '*' => ['mail', 'push'],
        ],
        NotificationEvent::TYPE_REUNION_ADMIN_ESCALATION => [
            '*' => ['mail', 'push'],
        ],
        // H-05 — Nhắc/leo thang bước bàn giao (mirror đoàn tụ).
        NotificationEvent::TYPE_HANDOVER_FAMILY_REMINDER => [
            '*' => ['mail', 'push'],
        ],
        NotificationEvent::TYPE_HANDOVER_ADMIN_ESCALATION => [
            '*' => ['mail', 'push'],
        ],
        NotificationEvent::TYPE_CASE_UNREACHABLE_FAMILY => [
            '*' => ['mail'],
        ],

        // Sự kiện bảo mật tài khoản: người dùng cần biết ngay quyền truy cập đã
        // đổi và phải đăng nhập lại → giữ cả email và push.
        NotificationEvent::TYPE_ROLE_CHANGED => [
            '*' => ['mail', 'push'],
        ],

        NotificationEvent::TYPE_FACE_MATCH_SUGGESTED => [
            'USER' => [],          // gợi ý chưa được kiểm chứng: chỉ chuông, tránh gây hy vọng hụt
            'VOLUNTEER' => [],
            'ADMIN' => ['mail'],   // admin cần biết để vào kiểm tra
            'MODERATOR' => ['mail'],
        ],

        // Thông tin, không cần email.
        NotificationEvent::TYPE_CLUE_SUBMITTED => [
            'USER' => ['mail'],
            'ADMIN' => [],
            'MODERATOR' => [],
        ],
        NotificationEvent::TYPE_CLUE_VERIFIED => [
            '*' => [],
        ],
        NotificationEvent::TYPE_CLUE_VERIFIED_FOR_REPORTER => [
            '*' => [],
        ],
        NotificationEvent::TYPE_CASE_MARKED_FOUND => [
            '*' => ['mail'],
        ],
        NotificationEvent::TYPE_FOUND_SUBMITTED => [
            'USER' => ['mail'],
            'ADMIN' => ['mail'],
            'MODERATOR' => ['mail'],
        ],
        NotificationEvent::TYPE_REUNION_CONFIRMED => [
            'USER' => ['mail'],
            'ADMIN' => [],
            'MODERATOR' => [],
        ],
        NotificationEvent::TYPE_FOUND_CASE_REUNITED => [
            '*' => ['mail'],
        ],
        NotificationEvent::TYPE_FOUND_CASE_HANDED_OVER => [
            '*' => ['mail'],
        ],

        // Ticket mới: admin/moderator nhận cả chuông lẫn email (cần hành động).
        NotificationEvent::TYPE_TICKET_CREATED => [
            'ADMIN' => ['mail'],
            'MODERATOR' => ['mail'],
        ],

        // Phản hồi ticket (public): người gửi nhận chuông + email để quay lại xem.
        NotificationEvent::TYPE_TICKET_REPLIED => [
            'USER' => ['mail'],
        ],

        // Đánh dấu xử lý / đóng ticket: người gửi nhận chuông + email.
        NotificationEvent::TYPE_TICKET_RESOLVED => [
            'USER' => ['mail'],
        ],
        NotificationEvent::TYPE_TICKET_CLOSED => [
            'USER' => ['mail'],
        ],
    ],
];
