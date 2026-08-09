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
