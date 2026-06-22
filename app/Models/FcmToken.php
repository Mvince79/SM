<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FcmToken extends Model
{
    use HasFactory;

    protected $table = 'fcm_tokens';

    protected $fillable = [
        'user_id',
        'token',
        'device_type',
        'device_name',
        'is_active',
        'last_used_at',
        'watch_lat',
        'watch_lng',
        'watch_radius_km',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'integer',
            'last_used_at' => 'datetime',
            'user_id' => 'integer',
            'watch_lat' => 'decimal:7',
            'watch_lng' => 'decimal:7',
            'watch_radius_km' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
