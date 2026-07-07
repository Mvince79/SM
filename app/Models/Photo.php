<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    use HasFactory;

    protected $table = 'photos';

    protected $fillable = [
        'model_type',
        'model_id',
        'path_original',
        'path_thumb',
        'path_blurred',
        'file_size',
        'mime_type',
        'width',
        'height',
        'has_face',
        'face_encoding',
        'face_bounding_box',
        'face_crop_path',
        'detected_gender',
        'ai_status',
        'is_primary',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'has_face' => 'integer',
            'face_bounding_box' => 'array',
            'is_primary' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function model()
    {
        return $this->morphTo();
    }
}
