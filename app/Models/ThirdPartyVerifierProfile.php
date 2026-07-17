<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThirdPartyVerifierProfile extends Model
{
    protected $fillable = [
        'user_id',
        'organization_type',
        'organization_name',
        'badge_or_staff_id',
        'handover_point_id',
        'verified_by_admin_id',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handoverPoint(): BelongsTo
    {
        return $this->belongsTo(HandoverPoint::class);
    }

    public function verifiedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_admin_id');
    }
}
