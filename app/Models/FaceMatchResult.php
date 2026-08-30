<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class FaceMatchResult extends Model
{
    use LogsActivity;

    protected $fillable = [
        'missing_id',
        'found_id',
        'missing_photo_id',
        'found_photo_id',
        'score',
        'is_matched',
        'confidence_level',
        'source',
        'model_used',
        'status',
        'confirmed_by',
        'confirmed_at',
        'notified_at',
    ];

    protected $casts = [
        'score' => 'float',
        'is_matched' => 'boolean',
        'confirmed_at' => 'datetime',
        'notified_at' => 'datetime',
    ];

    public function getConfidenceLabelAttribute(): string
    {
        return match ($this->confidence_level) {
            'HIGH' => 'Khớp mạnh',
            'MEDIUM' => 'Cần kiểm tra',
            default => 'Thấp',
        };
    }

    public function getConfidenceColorAttribute(): string
    {
        return match ($this->confidence_level) {
            'HIGH' => 'success',
            'MEDIUM' => 'warning',
            default => 'gray',
        };
    }

    /**
     * Kết quả bị bác bỏ do cascade (xoá/từ chối hồ sơ đối diện) sẽ lưu trạng thái
     * trước khi revert vào found_child_prev_status / missing_child_prev_status.
     * Có mặt ít nhất một giá trị → đây là "hoàn tác" (revert), KHÔNG phải bác bỏ
     * thủ công (vốn không lưu prev status).
     */
    public function isRevertedViaCascade(): bool
    {
        return $this->status === 'DISMISSED'
            && ($this->found_child_prev_status !== null || $this->missing_child_prev_status !== null);
    }

    /**
     * Chuỗi tóm tắt các hồ sơ được revert về trạng thái nào khi cascade bác bỏ
     * kết quả này. Dùng làm mô tả (tooltip) cho badge "Đã hoàn tác".
     */
    public function getRevertSummaryAttribute(): ?string
    {
        if (! $this->isRevertedViaCascade()) {
            return null;
        }

        $label = fn (?string $status): string => match ($status) {
            'PENDING' => 'Chờ',
            'APPROVED' => 'Đã duyệt',
            'MATCHED' => 'Đã khớp',
            'REJECTED' => 'Từ chối',
            'CLOSED' => 'Đã đóng',
            'FOUND' => 'Đã tìm thấy',
            'HANDED_OVER' => 'Đã bàn giao',
            'REUNITED' => 'Đã đoàn tụ',
            default => (string) $status,
        };

        $parts = [];

        if ($this->found_child_prev_status !== null) {
            $parts[] = 'Trẻ tìm thấy ← ' . $label($this->found_child_prev_status);
        }

        if ($this->missing_child_prev_status !== null) {
            $parts[] = 'Trẻ mất tích ← ' . $label($this->missing_child_prev_status);
        }

        return implode(' · ', $parts);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status'])
            ->logOnlyDirty();
    }
    public static function confidenceLevelForScore(float $score): string
    {
        if ($score >= config('face_match.high_confidence_threshold', 0.85)) {
            return 'HIGH';
        }

        if ($score >= config('face_match.score_threshold', 0.70)) {
            return 'MEDIUM';
        }

        return 'LOW';
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function missingChild(): BelongsTo
    {
        return $this->belongsTo(MissingChild::class, 'missing_id');
    }

    public function foundChild(): BelongsTo
    {
        return $this->belongsTo(FoundChild::class, 'found_id');
    }

    public function missingPhoto(): BelongsTo
    {
        return $this->belongsTo(Photo::class, 'missing_photo_id');
    }

    public function foundPhoto(): BelongsTo
    {
        return $this->belongsTo(Photo::class, 'found_photo_id');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
