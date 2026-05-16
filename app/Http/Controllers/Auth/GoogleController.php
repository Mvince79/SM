<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Redirect đến Google OAuth.
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }


    /**
     * Xử lý callback từ Google.
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('auth.login')
                ->with('error', 'Đăng nhập Google thất bại. Vui lòng thử lại.');
        }

        // Tìm user theo google_id trước (tránh duplicate khi email bị đổi)
        $user = User::where('google_id', $googleUser->getId())->first();

        if (!$user) {
            // Tìm theo email — có thể user đã đăng ký bằng email trước đó
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // Merge: cập nhật google_id vào tài khoản email cũ
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar_url' => $googleUser->getAvatar() ?? $user->avatar_url,
                    // Nếu chưa verify email → verify luôn vì Google đã xác thực
                    'email_verified_at' => $user->email_verified_at ?? now(),
                    'status' => $user->status === 'PENDING' ? 'ACTIVE' : $user->status,
                ]);
            } else {
                // Tạo user mới — không có password (Google-only account)
                $user = User::create([
                    'full_name' => $googleUser->getName() ?? $googleUser->getEmail(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar_url' => $googleUser->getAvatar(),
                    'password' => null,
                    'email_verified_at' => now(),
                    'status' => 'ACTIVE',
                    'role' => 'USER',
                ]);
            }
        } else {
            // Cập nhật avatar nếu có thay đổi
            $user->update([
                'avatar_url' => $googleUser->getAvatar() ?? $user->avatar_url,
                'last_active_at' => now(),
            ]);
        }

        // Kiểm tra tài khoản bị BAN
        if ($user->status === 'BANNED') {
            $reason = $user->ban_reason ?? 'Vi phạm điều khoản sử dụng.';
            return redirect()->route('auth.login')
                ->with('error', "Tài khoản đã bị khóa. Lý do: {$reason}");
        }

        Auth::login($user, remember: true);

        $user->update(['last_active_at' => now()]);

        session()->regenerate();

        return redirect()->intended(route('home'))
            ->with('success', 'Đăng nhập bằng Google thành công!');
    }
}