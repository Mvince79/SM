<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * D-01 — Audit log bất biến.
 *
 * Chỉ được ghi tự động qua StatusChangeAuditObserver. Không có route/controller
 * nào cho phép UPDATE/DELETE; model không có updated_at.
 */
class AuditLog extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'actor_id',
        'actor_role',
        'action',
        'target_type',
        'target_id',
        'old_value',
        'new_value',
        'reason',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
        'created_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** Sinh UUID khi tạo mới nếu chưa có. */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $log): void {
            if (blank($log->id)) {
                $log->id = (string) Str::uuid();
            }
            if (blank($log->created_at)) {
                $log->created_at = now();
            }
        });
    }
}
