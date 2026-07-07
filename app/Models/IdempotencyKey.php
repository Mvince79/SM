<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdempotencyKey extends Model
{
    use HasFactory;

    protected $table = 'idempotency_keys';

    protected $fillable = [
        'request_key',
        'user_id',
        'resource_type',
        'resource_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
