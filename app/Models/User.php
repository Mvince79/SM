<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'full_name',
        'email',
        'email_verified_at',
        'phone',
        'phone_verified_at',
        'password',
        'google_id',
        'avatar_url',
        'role',
        'status',
        'ban_reason',
        'province_id',
        'notify_lat',
        'notify_lng',
        'notify_radius_km',
        'otp_code',
        'otp_expires_at',
        'last_active_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
        'google_id',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'last_active_at' => 'datetime',
            'notify_lat' => 'decimal:7',
            'notify_lng' => 'decimal:7',
            'notify_radius_km' => 'integer',
        ];
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function scopeAdmins($query)
    {
        return $query->whereIn('role', ['ADMIN', 'MODERATOR']);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return in_array($this->role, ['ADMIN', 'MODERATOR']);
    }

    public function isBanned(): bool
    {
        return $this->status === 'BANNED';
    }

    public function isVerified(): bool
    {
        return !is_null($this->email_verified_at) && $this->status === 'ACTIVE';
    }

    /**
     * Google-only account (không có password).
     */
    public function hasPassword(): bool
    {
        return !is_null($this->password);
    }

    public function getAvatarUrlAttribute($value): string
    {
        if ($value) {
            return $value;
        }

        // UI Avatar fallback — tạo từ initials
        $name = urlencode($this->full_name ?? 'User');
        return "https://ui-avatars.com/api/?name={$name}&background=2563eb&color=fff&size=128&bold=true";
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function missingChildren()
    {
        return $this->hasMany(MissingChild::class);
    }

    public function foundChildren()
    {
        return $this->hasMany(FoundChild::class);
    }

    public function clues()
    {
        return $this->hasMany(Clue::class);
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function fcmTokens()
    {
        return $this->hasMany(FcmToken::class);
    }
}