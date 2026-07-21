<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EscalationQueue extends Model
{
    public const STATUS_PENDING = 'PENDING';

    public const STATUS_ESCALATED = 'ESCALATED';

    public const STATUS_RESOLVED = 'RESOLVED';

    protected $table = 'escalation_queue';

    protected $fillable = [
        'notification_recipient_id',
        'due_at',
        'status',
        'resolved_by_admin_id',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function notificationRecipient(): BelongsTo
    {
        return $this->belongsTo(
            NotificationRecipient::class,
            'notification_recipient_id'
        );
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'resolved_by_admin_id'
        );
    }
}