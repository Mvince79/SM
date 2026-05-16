<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('auth.login');
        }

        if ($user->status === 'PENDING') {
            Auth::logout();
            session(['otp_user_id' => $user->id]);

            return redirect()->route('auth.otp.form')
                ->with('error', 'Vui lòng xác thực email trước khi tiếp tục.');
        }

        if ($user->status === 'BANNED') {
            Auth::logout();
            $reason = $user->ban_reason ?? 'Vi phạm điều khoản sử dụng.';

            return redirect()->route('auth.login')
                ->with('error', "Tài khoản đã bị khóa. Lý do: {$reason}");
        }

        return $next($request);
    }
}