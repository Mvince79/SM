<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clue extends Model
{
    use HasFactory;

    protected $table = 'clues';

    protected $fillable = [
        'missing_id',
        'user_id',
        'clue_type',
        'description',
        'lat',
        'lng',
        'gps_shared_at',
        'location_accuracy_m',
        'occurred_at',
        'image_path',
        'is_verified',
        'verified_by',
        'is_anonymous',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'gps_shared_at' => 'datetime',
            'occurred_at' => 'datetime',
            'location_accuracy_m' => 'integer',
            'is_verified' => 'integer',
            'is_anonymous' => 'integer',
            'missing_id' => 'integer',
            'user_id' => 'integer',
            'verified_by' => 'integer',
        ];
    }

    public function missingChild()
    {
        return $this->belongsTo(MissingChild::class, 'missing_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
