<?php

namespace App\Http\Controllers;

use App\Http\Requests\WatchAreaRequest;
use App\Models\FcmToken;
use Illuminate\Http\JsonResponse;

class WatchAreaController extends Controller
{
    /**
     * POST /watch-area
     *
     * Cho phép tình nguyện viên vãng lai (chưa đăng nhập) đăng ký nhận thông báo
     * khi có hồ sơ mất tích/tìm được mới xuất hiện trong bán kính họ chọn trên bản đồ.
     *
     * Không yêu cầu middleware auth — user_id sẽ là null nếu là guest,
     * cột fcm_tokens.user_id đã nullable từ migration gốc.
     */
    public function store(WatchAreaRequest $request): JsonResponse
    {
        $data = $request->validated();

        FcmToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => auth()->id(), // null nếu guest
                'device_type' => 'WEB',
                'watch_lat' => $data['lat'],
                'watch_lng' => $data['lng'],
                'watch_radius_km' => $data['radius_km'] ?? 20,
                'is_active' => 1,
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Đã theo dõi khu vực này. Bạn sẽ nhận thông báo khi có tin mới gần đây.',
        ]);
    }

    /**
     * DELETE /watch-area
     *
     * Tắt theo dõi — gọi khi người dùng không muốn nhận thông báo nữa.
     */
    public function destroy(WatchAreaRequest $request): JsonResponse
    {
        $token = $request->input('token');

        FcmToken::where('token', $token)->update(['is_active' => 0]);

        return response()->json(['message' => 'Đã tắt theo dõi khu vực này.']);
    }
}