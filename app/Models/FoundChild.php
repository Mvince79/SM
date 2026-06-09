<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

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
            'found_time' => 'datetime:H:i',
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

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function photos()
    {
        return $this->morphMany(Photo::class, 'model');
    }

    public function faceMatchResults()
    {
        return $this->hasMany(FaceMatchResult::class, 'found_id');
    }

    public function primaryPhoto()
    {
        return $this->morphOne(Photo::class, 'model')
            ->where('is_primary', 1)
            ->orderBy('sort_order');
    }

    public function getPrimaryPhotoUrlAttribute(): ?string
    {
        $photo = $this->relationLoaded('primaryPhoto')
            ? $this->getRelation('primaryPhoto')
            : $this->primaryPhoto()->first();

        if (!$photo) {
            $photo = $this->relationLoaded('photos')
                ? $this->photos->first()
                : $this->photos()->orderBy('sort_order')->first();
        }

        if (!$photo) {
            return null;
        }

        foreach (['path_thumb', 'path_original'] as $field) {
            $path = $photo->{$field} ?? null;
            if ($path && Storage::disk('public')->exists($path)) {
                return asset('storage/' . $path);
            }
        }

        return null;
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\Models\Comment::class, 'commentable');
    }
}
