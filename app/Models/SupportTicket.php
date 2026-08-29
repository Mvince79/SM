<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SupportTicket extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'category',
        'subject',
        'description',
        'priority',
        'status',
        'assigned_admin_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SupportTicketReply::class);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['OPEN', 'IN_PROGRESS']);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['OPEN', 'IN_PROGRESS'], true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['user_id', 'category', 'subject', 'priority', 'status', 'assigned_admin_id'])
            ->logOnlyDirty();
    }
}