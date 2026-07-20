<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class KycVerification extends Model
{
    use HasFactory;

    protected $table = 'kyc_verifications';

    protected $fillable = [
        'id_type',
        'user_id',
        'id_number',
        'full_name_on_id',
        'dob_on_id',
        'front_image_path',
        'back_image_path',
        'selfie_path',
        'ocr_provider',
        'ocr_raw_response',
        'face_match_score',
        'review_level',
        'status',
        'reviewed_by',
        'verified_at',
        'rejected_reason',
    ];

    // id_number không bao giờ lộ ra mảng/JSON mặc định (toArray, API Resource...)
    protected $hidden = [
        'id_number',
    ];

    protected function casts(): array
    {
        return [
            'dob_on_id' => 'date',
            'ocr_raw_response' => 'array',
            'face_match_score' => 'float',
            'verified_at' => 'datetime',
        ];
    }

    // ─── Mã hóa/giải mã id_number ────────────────────────────────────────────
    // Tự kiểm soát bằng Crypt::encryptString() (thay vì cast 'encrypted' mặc định
    // của Laravel) để nhất quán với cách face_encoding đã được mã hóa ở Photo (Sprint 3).

    public function setIdNumberAttribute(string $value): void
    {
        $this->attributes['id_number'] = Crypt::encryptString($value);
    }

    public function getDecryptedIdNumberAttribute(): ?string
    {
        return $this->attributes['id_number'] ?? null
            ? Crypt::decryptString($this->attributes['id_number'])
            : null;
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isVerified(): bool
    {
        return $this->status === 'VERIFIED';
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
