<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class MissingChild extends Model
{
    use SoftDeletes;

    protected $table = 'missing_children';

    protected $fillable = [
        'user_id',
        'case_code',
        'full_name',
        'dob',
        'age_estimate',
        'gender',
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
        'contact_name',
        'contact_phone',
        'contact_relation',
        'status',
        'approved_at',
        'approved_by',
        'found_at',
        'qr_code_path',
        'view_count',
        'share_count',
    ];

    protected $casts = [
        'dob' => 'date',
        'missing_date' => 'date',
        'approved_at' => 'datetime',
        'found_at' => 'datetime',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    // Relationships
    public function clues()
    {
        return $this->hasMany(Clue::class, 'missing_id')->orderBy('created_at', 'desc');
    }

    public function photos()
    {
        return $this->morphMany(Photo::class, 'model');
    }

    public function primaryPhoto()
    {
        return $this->morphOne(Photo::class, 'model')
            ->where('is_primary', 1)
            ->orderBy('sort_order');
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scope
    public function scopeApproved($query)
    {
        return $query->where('status', 'APPROVED');
    }

    public function scopeFound($query)
    {
        return $query->where('status', 'FOUND');
    }

    // Helpers
    /**
     * Tính tuổi từ dob hoặc trả về age_estimate
     */
    public function getAgeAttribute(): ?int
    {
        if ($this->dob) {
            return $this->dob->age;
        }
        return $this->age_estimate;
    }

    /**
     * URL ảnh thumbnail để hiển thị (không vỡ)
     * Ưu tiên: path_thumb → path_blurred → placeholder
     */
    public function getThumbUrlAttribute(): string
    {
        $photo = $this->primaryPhoto;
        if ($photo) {
            $path = $photo->path_thumb ?? $photo->path_blurred ?? $photo->path_original;
            if ($path && Storage::disk('public')->exists($path)) {
                return asset('storage/' . $path);
            }
        }
        // Fallback: DiceBear avatar (không vỡ ảnh)
        $seed = urlencode($this->full_name);
        return "https://api.dicebear.com/7.x/thumbs/svg?seed={$seed}&backgroundColor=ffdfbf";
    }
}