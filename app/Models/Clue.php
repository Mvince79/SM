<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clue extends Model
{
    protected $table = 'clues';

    protected $fillable = [
        'missing_id',
        'parent_id',
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
        'is_hidden',
        'verified_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'gps_shared_at' => 'datetime',
        'is_verified' => 'boolean',
        'is_anonymous' => 'boolean',
        'is_hidden' => 'boolean',
        'verified_at' => 'datetime',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'is_hidden' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function missingChild()
    {
        return $this->belongsTo(MissingChild::class, 'missing_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function volunteerAssignments()
    {
        return $this->hasMany(VolunteerAssignment::class);
    }

    public function replies()
    {
        return $this->hasMany(Clue::class, 'parent_id')->with('user')->oldest();
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->is_anonymous || ! $this->user) {
            return 'Ẩn danh';
        }

        return $this->user->name;
    }

    public function getInitialsAttribute(): string
    {
        $name = $this->display_name;
        if ($name === 'Ẩn danh') {
            return 'AN';
        }
        $words = explode(' ', $name);
        $initials = '';
        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }

        return $initials ?: 'AN';
    }
}
