<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    use HasFactory;

    protected $table = 'provinces';

    protected $fillable = [
        'name',
        'code',
        'short_code',
        'region',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'integer',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class, 'province_id');
    }

    public function missingChildren()
    {
        return $this->hasMany(MissingChild::class, 'province_id');
    }
    public function handoverPoints()
    {
        return $this->hasMany(HandoverPoint::class, 'province_id');
    }
}
