<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cập nhật last_active_at của người dùng đã đăng nhập, tối đa 1 lần / 5 phút,
 * để indicator "online" và các luồng cần biết ai đang hoạt động (vd gán chat)
 * phản ánh đúng thực tế — thay vì chỉ cập nhật lúc đăng nhập.
 *
 * Dùng truy vấn DB trực tiếp (không qua model) để không kích hoạt UserObserver
 * và không đụng updated_at. Chỉ chạy với người dùng đã xác thực.
 */
class TouchLastActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            $threshold = now()->subMinutes(5);

            if ($user->last_active_at === null || $user->last_active_at->lt($threshold)) {
                DB::table('users')
                    ->where('id', $user->getKey())
                    ->update(['last_active_at' => now()]);
            }
        }

        return $next($request);
    }
}
