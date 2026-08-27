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

    public function recipients(): HasMany
    {
        return $this->hasMany(NotificationRecipient::class);
    }
}
