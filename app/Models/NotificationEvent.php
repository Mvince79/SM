<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationEvent extends Model
{
    public const TYPE_FACE_MATCH_CONFIRMED = 'FACE_MATCH_CONFIRMED';

    public const TYPE_CASE_APPROVED = 'CASE_APPROVED';

    public const TYPE_CASE_APPROVED_IN_PROVINCE = 'CASE_APPROVED_IN_PROVINCE';

    public const TYPE_CLUE_VERIFIED = 'CLUE_VERIFIED';

    public const TYPE_HANDOVER_READY = 'HANDOVER_READY';

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
