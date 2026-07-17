<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminChatAssignment extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_CLOSED = 'CLOSED';

    protected $fillable = [
        'admin_user_id',
        'requester_user_id',
        'status',
        'assigned_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }
}
