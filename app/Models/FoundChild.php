<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FoundChild extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'found_children';

    protected $fillable = [
        'user_id',
        'province_id',
        'district',
        'ward',
        'address_detail',
        'lat',
        'lng',
        'found_date',
        'found_time',
        'gender_estimate',
        'age_estimate',
        'condition_desc',
        'clothes_desc',
        'special_marks',
        'current_location',
        'handover_agency',
        'status',
        'approved_at',
        'approved_by',
        'ai_processed_at',
    ];

    protected function casts(): array
    {
        return [
            'found_date' => 'date',
            'approved_at' => 'datetime',
            'ai_processed_at' => 'datetime',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'age_estimate' => 'integer',
            'province_id' => 'integer',
            'user_id' => 'integer',
            'approved_by' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function photos()
    {
        return $this->morphMany(Photo::class, 'model');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
