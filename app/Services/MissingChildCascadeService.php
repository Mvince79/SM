<?php

namespace App\Services;

use App\Models\FaceMatchResult;
use App\Models\FoundChild;
use App\Models\MissingChild;
use App\Support\CacheVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Xử lý cascade ĐỐI XỨNG khi một MissingChild (hồ sơ mất tích) bị từ chối hoặc xoá.
 *
 * Quyết định #1 (cả Từ chối & Xoá): nếu MissingChild có FaceMatchResult ở trạng
 * thái CONFIRMED (tức FoundChild liên quan từng bị đẩy sang MATCHED), thì tự động
 * revert để người báo tin không thấy "Đã tìm thấy" giả:
 *   - FoundChild → trả về trạng thái trước khi confirm (found_child_prev_status,
 *     fallback APPROVED), xoá trạng thái MATCHED.
 *   - FaceMatchResult CONFIRMED → DISMISSED.
 *
 * Quyết định #2 (CHỈ Xoá): chuyển các FaceMatchResult PENDING liên quan sang
 * DISMISSED để không còn kết quả đối chiếu "treo" tham chiếu missing child đã xoá.
 *
 * FoundChild revert dùng saveQuietly() để KHÔNG kích hoạt FoundChildObserver
 * (tránh phát thông báo FOUND_APPROVED giả và log activity trùng); thay bằng
 * bump cache thủ công để hồ sơ hiện lại trên bản đồ / trang chủ.
 *
 * FaceMatchResult::save() ở đây AN TOÀN: FaceMatchResultObserver chỉ bắn khi
 * transition sang CONFIRMED, nên CONFIRMED → DISMISSED không sinh thông báo giả.
 *
 * Mọi thay đổi nằm trong 1 DB::transaction để không lưu trạng thái nửa vời.
 *
 * Đối xứng hoàn toàn với FoundChildCascadeService (xử lý chiều ngược lại).
 */
class MissingChildCascadeService
{
    public function handleRemoval(MissingChild $missingChild, bool $includePending): void
    {
        DB::transaction(function () use ($missingChild, $includePending) {
            $missingChild->faceMatchResults()
                ->where('status', 'CONFIRMED')
                ->with('foundChild')
                ->get()
                ->each(function (FaceMatchResult $result) {
                    $found = $result->foundChild;

                    if ($found) {
                        // Revert FoundChild về trạng thái TRƯỚC khi bị đẩy MATCHED —
                        // lấy từ found_child_prev_status đã lưu tại bước Xác nhận match
                        // (đối xứng với missing_child_prev_status). KHÔNG hardcode APPROVED.
                        $prev = $result->found_child_prev_status;

                        if ($prev === null) {
                            // Match cũ được confirm TRƯỚC khi có cột found_child_prev_status
                            // → không biết trạng thái thật trước MATCHED. Dùng APPROVED làm
                            // fallback an toàn (FoundChild chỉ sang MATCHED sau khi APPROVED
                            // trong luồng thực tế) và ghi log rõ ràng để dễ truy vết.
                            $prev = FoundChild::STATUS_APPROVED;
                            Log::warning(
                                'MissingChildCascadeService: found_child_prev_status null cho result #'
                                . $result->getKey() . ' (match cũ confirm trước khi có cột) — dùng fallback APPROVED.'
                            );
                        }

                        $found->status = $prev;
                        $found->saveQuietly(); // không kích hoạt FoundChildObserver
                    }

                    // FaceMatchResultObserver chỉ notify khi CONFIRMED → DISMISSED an toàn.
                    $result->status = 'DISMISSED';
                    $result->save();
                });

            if ($includePending) {
                $missingChild->faceMatchResults()
                    ->where('status', 'PENDING')
                    ->update(['status' => 'DISMISSED']);
            }
        });

        CacheVersion::bump(CacheVersion::MAP_DATA);
        CacheVersion::bump(CacheVersion::HOME_HIGHLIGHTS);
    }

    /**
     * Khi một MissingChild chuyển sang trạng thái ĐÃ KẾT THÚC (REUNITED / CLOSED /
     * REJECTED / ARCHIVED / UNREACHABLE_FAMILY) MÀ KHÔNG xoá (đối lập với
     * handleRemoval dành cho xoá/từ chối), các FaceMatchResult còn PENDING (chưa
     * duyệt) liên quan trở nên vô nghĩa — admin không cần "so khớp" lại hồ sơ đã
     * "xong việc". Chuyển chúng sang DISMISSED để hàng đợi "Đối chiếu khuôn mặt AI"
     * không còn hiện gợi ý treo cho hồ sơ đã đoàn tụ/đóng.
     *
     * KHÔNG revert các CONFIRMED (khác handleRemoval): match đã xác nhận dẫn đến
     * đoàn tụ là hợp lệ, giữ nguyên làm lịch sử. Hành vi idempotent: PENDING đã
     * DISMISSED không đổi trạng thái.
     */
    public function dismissPendingMatches(MissingChild $missingChild): void
    {
        $missingChild->faceMatchResults()
            ->where('status', 'PENDING')
            ->update(['status' => 'DISMISSED']);

        // Phản ánh ngay trên badge "Đối chiếu khuôn mặt AI" (không đợi TTL 5 phút).
        Cache::forget('badge_face_match_pending');
    }
}
