<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotificationEvent extends Model
{
    use HasFactory;
    public const TYPE_FACE_MATCH_CONFIRMED = 'FACE_MATCH_CONFIRMED';

    public const TYPE_CASE_APPROVED = 'CASE_APPROVED';

    public const TYPE_CASE_APPROVED_IN_PROVINCE = 'CASE_APPROVED_IN_PROVINCE';

    public const TYPE_CLUE_VERIFIED = 'CLUE_VERIFIED';

    public const TYPE_HANDOVER_READY = 'HANDOVER_READY';

    public const TYPE_ROLE_CHANGED = 'ROLE_CHANGED';

    // H-04 — Nhắc gia đình xác nhận đoàn tụ (hồ sơ HANDED_OVER quá 3 ngày).
    public const TYPE_REUNION_FAMILY_REMINDER = 'REUNION_FAMILY_REMINDER';

    // H-04 — Leo thang cho admin khi HANDED_OVER quá 7 ngày chưa xác nhận.
    public const TYPE_REUNION_ADMIN_ESCALATION = 'REUNION_ADMIN_ESCALATION';

    // ─── Vòng đời hồ sơ mất tích ────────────────────────────────────────────
    public const TYPE_CASE_SUBMITTED = 'CASE_SUBMITTED';

    public const TYPE_CASE_REJECTED = 'CASE_REJECTED';

    public const TYPE_CASE_MARKED_FOUND = 'CASE_MARKED_FOUND';

    public const TYPE_REUNION_CONFIRMED = 'REUNION_CONFIRMED';

    public const TYPE_CASE_UNREACHABLE_FAMILY = 'CASE_UNREACHABLE_FAMILY';

    // ─── Vòng đời tin tìm thấy trẻ ──────────────────────────────────────────
    public const TYPE_FOUND_SUBMITTED = 'FOUND_SUBMITTED';

    // ─── Lỗi vận hành hệ thống (AI / eKYC thất bại) ─────────────────────────
    public const TYPE_AI_PROCESSING_FAILED = 'AI_PROCESSING_FAILED';

    public const TYPE_FOUND_APPROVED = 'FOUND_APPROVED';

    public const TYPE_FOUND_REJECTED = 'FOUND_REJECTED';

    /**
     * Event dành riêng cho NGƯỜI ĐĂNG TIN TÌM THẤY TRẺ.
     *
     * Lý do tách khỏi HANDOVER_READY / REUNION_CONFIRMED: nội dung được chọn
     * theo khoá (event_type, role), mà gia đình và người đăng tin tìm thấy đều
     * mang role USER — dùng chung event thì hai bên nhận cùng một câu, sai ngữ
     * cảnh ("hãy đến nhận con" gửi cho người đi báo tin là vô nghĩa).
     */
    public const TYPE_FOUND_CASE_HANDED_OVER = 'FOUND_CASE_HANDED_OVER';

    public const TYPE_FOUND_CASE_REUNITED = 'FOUND_CASE_REUNITED';

    // ─── Manh mối & đối sánh ────────────────────────────────────────────────
    public const TYPE_CLUE_SUBMITTED = 'CLUE_SUBMITTED';

    /** Cùng lý do tách như trên: người gửi manh mối cũng là role USER. */
    public const TYPE_CLUE_VERIFIED_FOR_REPORTER = 'CLUE_VERIFIED_FOR_REPORTER';

    public const TYPE_FACE_MATCH_SUGGESTED = 'FACE_MATCH_SUGGESTED';

    /**
     * Nguồn phát sinh thông báo — nhãn để người nhận biết "tin này từ đâu ra",
     * thay vì chỉ thấy một dòng chữ không rõ ai/cái gì tạo ra nó.
     *
     * Ghi vào payload['source'] tại nơi phát event; hiển thị thành chip nhỏ
     * trong thẻ thông báo (chuông, trung tâm thông báo, trang chi tiết).
     */
    public const SOURCE_ADMIN = 'ADMIN';

    public const SOURCE_AI = 'AI';

    public const SOURCE_SYSTEM = 'SYSTEM';

    public const SOURCE_FAMILY = 'FAMILY';

    public const SOURCE_COMMUNITY = 'COMMUNITY';

    protected $fillable = [
        'event_type',
        'subject_type',
        'subject_id',
        'payload',
        'triggered_by_user_id',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Nhãn nguồn phát sinh để hiển thị cho người nhận.
     *
     * Ưu tiên payload['source']; nếu event cũ chưa có thì suy ra từ
     * triggered_by_user_id (có người bấm → do người, không có → hệ thống).
     */
    public function sourceLabel(): string
    {
        $source = $this->payload['source'] ?? null;

        return match ($source) {
            self::SOURCE_ADMIN => 'Quản trị viên',
            self::SOURCE_AI => 'AI đối sánh tự động',
            self::SOURCE_SYSTEM => 'Hệ thống tự động',
            self::SOURCE_FAMILY => 'Gia đình xác nhận',
            self::SOURCE_COMMUNITY => 'Cộng đồng gửi tin',
            default => $this->triggered_by_user_id ? 'Quản trị viên' : 'Hệ thống tự động',
        };
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(NotificationRecipient::class);
    }
}
