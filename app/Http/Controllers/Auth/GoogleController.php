<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\GoogleAuthService;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function __construct(
        private readonly GoogleAuthService $googleAuthService,
    ) {
    }

    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('auth.login')
                ->with('error', 'Đăng nhập Google thất bại. Vui lòng thử lại.');
        }

        $user = $this->googleAuthService->findOrCreate($googleUser);

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