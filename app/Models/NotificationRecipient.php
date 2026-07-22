<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\EscalationQueue;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NotificationRecipient extends Model
{
    protected $fillable = [
        'notification_event_id',
        'user_id',
        'role_at_time',
        'rendered_title',
        'rendered_body',
        'action_url',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(NotificationEvent::class, 'notification_event_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function escalationQueue(): HasOne
    {
        return $this->hasOne(EscalationQueue::class);
    }
}
