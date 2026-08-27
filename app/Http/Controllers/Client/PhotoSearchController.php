<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\MissingChild;
use App\Models\Photo;
use App\Services\FaceMatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

/**
 * S — Tìm kiếm bằng hình ảnh (AI Photo Search, công khai).
 *
 * - index():  trang /tim-kiem-bang-anh (S-01, public).
 * - search(): POST /api/photo-search (S-02, throttle:5,60). So khớp ảnh upload
 *             với toàn bộ hồ sơ trẻ mất tích chưa đoàn tụ, trả tối đa 5 kết quả
 *             score >= 0.5 (cấu hình face_match), không trả địa chỉ/SĐT.
 */
class PhotoSearchController extends Controller
{
    public function __construct(
        private FaceMatchService $faceMatchService
    ) {
    }

    /** S-01 — Trang tìm kiếm bằng ảnh công khai. */
    public function index()
    {
        return view('photo-search.index');
    }

    /**
     * S-02 — API so khớp ảnh với toàn bộ hồ sơ.
     * Response mỗi kết quả: case_code, tên, tuổi ước tính, ảnh đại diện, score (%) —
     * KHÔNG chứa địa chỉ/SĐT gia đình.
     */
    public function search(Request $request)
    {
        $data = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $tmpPath = null;

        try {
            // Lưu ảnh tạm để gửi sang AI encode.
            $tmpPath = $data['photo']->store('photo-search-tmp', 'public');

            $encoded = $this->faceMatchService->encode(
                $tmpPath,
                'public'
            );

            if (empty($encoded['encoding']) || $encoded['has_face'] === false) {
                return response()->json([
                    'status' => 'NO_FACE',
                    'message' => 'Không tìm thấy khuôn mặt trong ảnh. Vui lòng thử ảnh khác.',
                    'results' => [],
                ]);
            }

            $queryEncoding = $encoded['encoding'];
            $threshold = (float) config('face_match.search_threshold', 0.5);

            // Lấy toàn bộ ảnh hồ sơ trẻ mất tích chưa đoàn tù (có face_encoding).
            $candidatePhotos = Photo::query()
                ->where('model_type', MissingChild::class)
                ->where('has_face', 1)
                ->whereNotNull('face_encoding')
                ->with('model')
                ->get();

            $results = [];

            foreach ($candidatePhotos as $photo) {
                $missing = $photo->model;

                if (! $missing instanceof MissingChild) {
                    continue;
                }

                // Bỏ qua hồ sơ đã đóng/đoàn tụ.
                if (in_array($missing->status, MissingChild::CLOSED_STATUSES, true)) {
                    continue;
                }

                try {
                    $candidateEncoding = json_decode(Crypt::decryptString($photo->face_encoding), true);
                } catch (\Throwable) {
                    continue;
                }

                // face_encoding lưu dưới dạng Crypt::encryptString(json_encode([...])).
                // Giải mã xong vẫn là chuỗi JSON → phải json_decode thành array trước
                // khi truyền cho compare() (hàm yêu cầu array).
                if (! is_array($candidateEncoding)) {
                    continue;
                }

                $comparison = $this->faceMatchService->compare($queryEncoding, $candidateEncoding);
                $score = (float) ($comparison['score'] ?? 0);

                if ($score < $threshold) {
                    continue;
                }

                $results[] = [
                    'case_code' => $missing->case_code,
                    'name' => $missing->full_name,
                    'age' => $missing->age,
                    'photo_url' => $missing->thumb_url,
                    'missing_child_id' => $missing->id,
                    'score' => round($score * 100, 2),
                ];
            }

            // Sắp theo score giảm dần, lấy tối đa 5.
            usort($results, fn ($a, $b) => $b['score'] <=> $a['score']);
            $results = array_slice($results, 0, 5);

            return response()->json([
                'status' => 'OK',
                'results' => $results,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Đã xảy ra lỗi khi so khớp. Vui lòng thử lại sau.',
                'results' => [],
            ], 502);
        } finally {
            // Xóa file ảnh tạm ngay sau khi xử lý xong.
            if ($tmpPath && Storage::disk('public')->exists($tmpPath)) {
                Storage::disk('public')->delete($tmpPath);
            }
        }
    }
}
