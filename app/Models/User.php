<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, LogsActivity;

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
        'kyc_status',
        'status',
        'ban_reason',
        'province_id',
        'notify_lat',
        'notify_lng',
        'notify_radius_km',
        'otp_code',
        'otp_expires_at',
        'last_active_at',
        'theme',
    ];
    protected $guarded = ['role', 'kyc_status', 'status'];

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['role', 'status', 'kyc_status'])
            ->logOnlyDirty();
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

    public function hasVerifiedKyc(): bool
    {
        return $this->kyc_status === 'VERIFIED';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin();
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
            // URL đầy đủ (Google OAuth, UI Avatars)
            if (str_starts_with($value, 'http')) {
                return $value;
            }

            // Path local → trả về URL storage
            return asset('storage/' . $value);
        }

        // Fallback: UI Avatar từ tên
        $name = urlencode($this->full_name ?? 'User');

        return "https://ui-avatars.com/api/?name={$name}&background=FF3B30&color=fff&size=128&bold=true";
    }

    public function getNameAttribute(): ?string
    {
        return $this->full_name;
    }

    /**
     * Giao diện sáng/tối đã lưu của user, mặc định 'light' nếu chưa từng chọn.
     */
    public function getThemePreferenceAttribute(): string
    {
        return in_array($this->theme, ['light', 'dark'], true) ? $this->theme : 'light';
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

    public function assignedAdminChats(): HasMany
    {
        return $this->hasMany(AdminChatAssignment::class, 'admin_user_id');
    }

    public function requestedAdminChats(): HasMany
    {
        return $this->hasMany(AdminChatAssignment::class, 'requester_user_id');
    }

    public function fcmTokens()
    {
        return $this->hasMany(FcmToken::class);
    }

    public function volunteerAssignments(): HasMany
    {
        return $this->hasMany(VolunteerAssignment::class);
    }

    public function isVolunteer(): bool
    {
        return $this->role === 'VOLUNTEER';
    }

    public function notificationRecipients()
    {
        return $this->hasMany(NotificationRecipient::class);
    }

    /**
     * Mọi role khác USER (VOLUNTEER, MODERATOR, ADMIN) đều bắt buộc
     * kyc_status = VERIFIED mới được cấp quyền tương ứng với role đó.
     * Riêng ADMIN/MODERATOR đã có canAccessPanel() xử lý qua Filament,
     * helper này chỉ dùng để gate tính năng dành riêng cho VOLUNTEER.
     */
    public function canAccessVolunteerFeatures(): bool
    {
        return $this->isVolunteer()
            && !$this->isBanned()
            && $this->kyc_status === 'VERIFIED';
    }

    public function kycVerification()
    {
        return $this->hasOne(KycVerification::class);
    }
    public function thirdPartyVerifierProfile()
    {
        return $this->hasOne(ThirdPartyVerifierProfile::class);
    }

    public function isThirdPartyVerifier(): bool
    {
        return $this->role === 'THIRD_PARTY_VERIFIER';
    }

    public function verifierProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\ThirdPartyVerifierProfile::class, 'user_id');
    }
}