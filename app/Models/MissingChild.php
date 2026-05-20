<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MissingChild extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'missing_children';

    protected $fillable = [
        'user_id',
        'case_code',
        'full_name',
        'dob',
        'age_estimate',
        'gender',
        'skin_tone',
        'height_cm',
        'weight_kg',
        'province_id',
        'district',
        'ward',
        'address_detail',
        'lat',
        'lng',
        'missing_date',
        'missing_time',
        'clothes_desc',
        'clothes_color',
        'special_marks',
        'circumstances',
        'reward_info',
        'contact_name',
        'contact_phone',
        'contact_relation',
        'status',
        'approved_at',
        'approved_by',
        'rejection_reason',
        'found_at',
        'auto_expire_notified_at',
        'qr_code_path',
        'view_count',
        'share_count',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'missing_date' => 'date',
            'approved_at' => 'datetime',
            'found_at' => 'datetime',
            'auto_expire_notified_at' => 'datetime',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'weight_kg' => 'decimal:2',
            'height_cm' => 'integer',
            'view_count' => 'integer',
            'share_count' => 'integer',
            'province_id' => 'integer',
            'user_id' => 'integer',
            'approved_by' => 'integer',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

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

    public function primaryPhoto()
    {
        return $this->morphOne(Photo::class, 'model')->where('is_primary', 1);
    }

    public function clues()
    {
        return $this->hasMany(Clue::class, 'missing_id');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
