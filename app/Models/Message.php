<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $table = 'messages';

    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'missing_id',
        'body',
        'attachment_path',
        'attachment_type',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'from_user_id' => 'integer',
            'to_user_id' => 'integer',
            'missing_id' => 'integer',
        ];
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function missingChild()
    {
        return $this->belongsTo(MissingChild::class, 'missing_id');
    }
}
