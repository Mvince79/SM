<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PhotosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        $modelType = 'App\\Models\\MissingChild';

        DB::table('photos')->insert([
            // ── Case 1 (id=1): Nguyễn Văn An ──────────────────────────────────
            [
                'model_type' => $modelType,
                'model_id' => 1,
                'path_original' => 'photos/missing/1/original_001.jpg',
                'path_thumb' => 'photos/missing/1/thumb_001.jpg',
                'path_blurred' => 'photos/missing/1/blurred_001.jpg',
                'file_size' => 204800,   // 200 KB
                'mime_type' => 'image/jpeg',
                'width' => 800,
                'height' => 1000,
                'has_face' => 1,
                'face_encoding' => null,     // Để null trong seed; AI sẽ xử lý sau
                'is_primary' => 1,
                'sort_order' => 0,
                'created_at' => $now->copy()->subDays(3),
                'updated_at' => $now->copy()->subDays(3),
            ],
            [
                'model_type' => $modelType,
                'model_id' => 1,
                'path_original' => 'photos/missing/1/original_002.jpg',
                'path_thumb' => 'photos/missing/1/thumb_002.jpg',
                'path_blurred' => 'photos/missing/1/blurred_002.jpg',
                'file_size' => 153600,   // 150 KB
                'mime_type' => 'image/jpeg',
                'width' => 600,
                'height' => 800,
                'has_face' => 1,
                'face_encoding' => null,
                'is_primary' => 0,
                'sort_order' => 1,
                'created_at' => $now->copy()->subDays(3),
                'updated_at' => $now->copy()->subDays(3),
            ],
            // ── Case 2 (id=2): Trần Thị Bích Ngọc ─────────────────────────────
            [
                'model_type' => $modelType,
                'model_id' => 2,
                'path_original' => 'photos/missing/2/original_001.jpg',
                'path_thumb' => 'photos/missing/2/thumb_001.jpg',
                'path_blurred' => 'photos/missing/2/blurred_001.jpg',
                'file_size' => 307200,   // 300 KB
                'mime_type' => 'image/jpeg',
                'width' => 1024,
                'height' => 1024,
                'has_face' => 1,
                'face_encoding' => null,
                'is_primary' => 1,
                'sort_order' => 0,
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subDays(2),
            ],
            // ── Case 3 (id=3): Võ Quốc Hùng (đã tìm thấy) ────────────────────
            [
                'model_type' => $modelType,
                'model_id' => 3,
                'path_original' => 'photos/missing/3/original_001.jpg',
                'path_thumb' => 'photos/missing/3/thumb_001.jpg',
                'path_blurred' => 'photos/missing/3/blurred_001.jpg',
                'file_size' => 256000,   // 250 KB
                'mime_type' => 'image/jpeg',
                'width' => 900,
                'height' => 1200,
                'has_face' => 1,
                'face_encoding' => null,
                'is_primary' => 1,
                'sort_order' => 0,
                'created_at' => $now->copy()->subDays(25),
                'updated_at' => $now->copy()->subDays(25),
            ],
            [
                'model_type' => $modelType,
                'model_id' => 3,
                'path_original' => 'photos/missing/3/original_002.png',
                'path_thumb' => 'photos/missing/3/thumb_002.png',
                'path_blurred' => null,
                'file_size' => 512000,   // 500 KB
                'mime_type' => 'image/png',
                'width' => 1200,
                'height' => 900,
                'has_face' => 0,        // Không phát hiện mặt (ảnh toàn thân xa)
                'face_encoding' => null,
                'is_primary' => 0,
                'sort_order' => 1,
                'created_at' => $now->copy()->subDays(25),
                'updated_at' => $now->copy()->subDays(25),
            ],
            // ── Case 4 (id=4): Lý Thị Kim Ngân (PENDING — chưa có ảnh duyệt) ─
            [
                'model_type' => $modelType,
                'model_id' => 4,
                'path_original' => 'photos/missing/4/original_001.jpg',
                'path_thumb' => 'photos/missing/4/thumb_001.jpg',
                'path_blurred' => 'photos/missing/4/blurred_001.jpg',
                'file_size' => 102400,   // 100 KB
                'mime_type' => 'image/jpeg',
                'width' => 480,
                'height' => 640,
                'has_face' => 1,
                'face_encoding' => null,
                'is_primary' => 1,
                'sort_order' => 0,
                'created_at' => $now->copy()->subHours(5),
                'updated_at' => $now->copy()->subHours(5),
            ],
            // ── Case 6 (id=6): Đinh Thị Thu (CLOSED) ─────────────────────────
            [
                'model_type' => $modelType,
                'model_id' => 6,
                'path_original' => 'photos/missing/6/original_001.jpg',
                'path_thumb' => 'photos/missing/6/thumb_001.jpg',
                'path_blurred' => 'photos/missing/6/blurred_001.jpg',
                'file_size' => 409600,   // 400 KB
                'mime_type' => 'image/jpeg',
                'width' => 1080,
                'height' => 1350,
                'has_face' => 1,
                'face_encoding' => null,
                'is_primary' => 1,
                'sort_order' => 0,
                'created_at' => $now->copy()->subMonths(6)->subDays(1),
                'updated_at' => $now->copy()->subMonths(6)->subDays(1),
            ],
        ]);
    }
}
