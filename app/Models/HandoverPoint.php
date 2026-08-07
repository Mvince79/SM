<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class HandoverPoint extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'type',
        'address',
        'province_id',
        'lat',
        'lng',
        'hotline_phone',
        'responsible_org_name',
        'legal_authority_note',
        'verification_document_path',
        'priority_order',
        'is_active',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'is_active' => 'boolean',
        'priority_order' => 'integer',
    ];

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByProvince(Builder $query, int $provinceId): Builder
    {
        return $query->where('province_id', $provinceId);
    }
}