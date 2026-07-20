<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotSession extends Model
{
    protected $fillable = [
        'session_token',
        'user_id',
        'intent',
        'current_step',
        'collected_data',
        'status',
        'completed_action',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'collected_data' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
