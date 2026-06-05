<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaceMatchResult extends Model
{
    protected $fillable = [
        'missing_id',
        'found_id',
        'missing_photo_id',
        'found_photo_id',
        'score',
        'source',
        'model_used',
        'status',
        'confirmed_by',
        'confirmed_at',
    ];

    protected $casts = [
        'score' => 'float',
        'confirmed_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function missingChild(): BelongsTo
    {
        return $this->belongsTo(MissingChild::class, 'missing_id');
    }

    public function foundChild(): BelongsTo
    {
        return $this->belongsTo(FoundChild::class, 'found_id');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
