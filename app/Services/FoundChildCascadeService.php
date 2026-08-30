<?php

namespace App\Services;

use App\Models\FaceMatchResult;
use App\Models\FoundChild;
use App\Models\MissingChild;
use App\Support\CacheVersion;
use Illuminate\Support\Facades\DB;

/**
 * Xử lý cascade khi một FoundChild (tin trẻ tìm thấy) bị từ chối hoặc xoá.
 *
 * Quyết định #1 (cả Từ chối & Xoá): nếu FoundChild có FaceMatchResult ở trạng
 * thái CONFIRMED (tức MissingChild từng bị đẩy sang FOUND, FoundChild sang
 * MATCHED) thì tự động revert để gia đình không thấy "Đã tìm thấy" giả:
 *   - MissingChild → trả về trạng thái trước khi confirm (missing_child_prev_status,
 *     fallback APPROVED), xoá found_at.
 *   - FaceMatchResult CONFIRMED → DISMISSED.
 *
 * Quyết định #2 (CHỈ Xoá): chuyển các FaceMatchResult PENDING liên quan sang
 * DISMISSED để không còn kết quả đối chiếu "treo" tham chiếu found child đã xoá.
 *
 * MissingChild revert dùng saveQuietly() để KHÔNG kích hoạt MissingChildObserver
 * (tránh phát thông báo APPROVED/REUNITED/HANDOVER giả và log activity trùng);
 * thay bằng bump cache thủ công để hồ sơ hiện lại trên bản đồ / trang chủ.
 *
 * Mọi thay đổi nằm trong 1 DB::transaction để không lưu trạng thái nửa vời
 * (đã DISMISSED result mà MissingChild chưa revert).
 */
class FoundChildCascadeService
{
    public function handleRemoval(FoundChild $foundChild, bool $includePending): void
    {
        DB::transaction(function () use ($foundChild, $includePending) {
            $foundChild->faceMatchResults()
                ->where('status', 'CONFIRMED')
                ->with('missingChild')
                ->get()
                ->each(function (FaceMatchResult $result) {
                    $missing = $result->missingChild;

                    if ($missing) {
                        $prev = $result->missing_child_prev_status ?? MissingChild::STATUS_APPROVED;
                        $missing->status = $prev;
                        $missing->found_at = null;
                        $missing->saveQuietly(); // không kích hoạt MissingChildObserver
                    }

                    // FaceMatchResultObserver chỉ notify khi CONFIRMED → DISMISSED an toàn.
                    $result->status = 'DISMISSED';
                    $result->save();
                });

            if ($includePending) {
                $foundChild->faceMatchResults()
                    ->where('status', 'PENDING')
                    ->update(['status' => 'DISMISSED']);
            }
        });

        CacheVersion::bump(CacheVersion::MAP_DATA);
        CacheVersion::bump(CacheVersion::HOME_HIGHLIGHTS);
    }
}
