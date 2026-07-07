<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VolunteerAssignment extends Model
{
    use HasFactory;

    protected $table = 'volunteer_assignments';

    protected $fillable = [
        'user_id',
        'clue_id',
        'status',
        'notes',
        'assigned_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function volunteer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function clue(): BelongsTo
    {
        return $this->belongsTo(Clue::class);
    }
}
