<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Services\OtpService;
use App\Services\RateLimiterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(
        private readonly OtpService $otpService,
        private readonly RateLimiterService $rateLimiter,
    ) {
    }

    // ─── Register ────────────────────────────────────────────────────────────

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => 'PENDING',
            'role' => 'USER',
        ]);

        $this->otpService->send($user);

        session(['otp_user_id' => $user->id]);

        return redirect()->route('auth.otp.form')
            ->with('success', 'Tài khoản đã được tạo! Vui lòng kiểm tra email để lấy mã OTP.');
    }

    // ─── OTP ─────────────────────────────────────────────────────────────────

    public function showOtpForm()
    {
        if (!session('otp_user_id')) {
            return redirect()->route('auth.register');
        }

        return view('auth.verify-otp');
    }

    public function verifyOtp(VerifyOtpRequest $request)
    {
        $userId = session('otp_user_id');
        if (!$userId) {
            return redirect()->route('auth.register')
                ->with('error', 'Phiên đăng ký đã hết hạn. Vui lòng đăng ký lại.');
        }

        $user = User::find($userId);
        if (!$user) {
            return redirect()->route('auth.register')
                ->with('error', 'Không tìm thấy tài khoản. Vui lòng đăng ký lại.');
        }

        if (!$this->otpService->verify($user, $request->otp)) {
            // Phân biệt hết hạn vs sai mã
            if (!$user->otp_expires_at || now()->isAfter($user->otp_expires_at)) {
                return back()->withErrors(['otp' => 'Mã OTP đã hết hạn. Vui lòng gửi lại mã mới.']);
            }
            return back()->withErrors(['otp' => 'Mã OTP không đúng. Vui lòng thử lại.']);
        }

        $this->otpService->markVerified($user);

        session()->forget('otp_user_id');

        Auth::login($user);

        return redirect()->route('home')
            ->with('success', 'Xác thực thành công! Chào mừng bạn đến với hệ thống.');
    }

    public function resendOtp(Request $request)
    {
        $userId = session('otp_user_id');
        if (!$userId) {
            return redirect()->route('auth.register')
                ->with('error', 'Phiên đăng ký đã hết hạn.');
        }

        $user = User::find($userId);
        if (!$user) {
            return redirect()->route('auth.register');
        }

        if ($this->rateLimiter->tooManyOtpResends($user->id)) {
            $seconds = $this->rateLimiter->otpResendAvailableIn($user->id);
            return back()->with('error', "Bạn đã gửi quá nhiều lần. Vui lòng thử lại sau {$seconds} giây.");
        }

        $this->rateLimiter->hitOtpResend($user->id);
        $this->otpService->send($user);

        return back()->with('success', 'Mã OTP mới đã được gửi đến email của bạn.');
    }

    // ─── Login ───────────────────────────────────────────────────────────────

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        if ($this->rateLimiter->tooManyLoginAttempts($request->email, $request->ip())) {
            $seconds = $this->rateLimiter->loginAvailableIn($request->email, $request->ip());
            return back()
                ->withInput($request->only('email'))
                ->with('error', "Quá nhiều lần đăng nhập thất bại. Vui lòng thử lại sau {$seconds} giây.");
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password ?? '')) {
            $this->rateLimiter->hitLoginAttempt($request->email, $request->ip());
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email hoặc mật khẩu không đúng.']);
        }

        if ($user->status === 'PENDING') {
            session(['otp_user_id' => $user->id]);
            $this->otpService->send($user);
            return redirect()->route('auth.otp.form')
                ->with('error', 'Tài khoản chưa được xác thực. Mã OTP mới đã được gửi đến email của bạn.');
        }

        if ($user->status === 'BANNED') {
            $reason = $user->ban_reason ?? 'Vi phạm điều khoản sử dụng.';
            return back()
                ->withInput($request->only('email'))
                ->with('error', "Tài khoản đã bị khóa. Lý do: {$reason}");
        }

        $this->rateLimiter->clearLoginAttempts($request->email, $request->ip());

        Auth::login($user, $request->boolean('remember'));

        $user->update(['last_active_at' => now()]);

        $request->session()->regenerate();

        return redirect()->intended(route('home'))
            ->with('success', 'Đăng nhập thành công!');
    }

    // ─── Logout ──────────────────────────────────────────────────────────────

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth.login')
            ->with('success', 'Đã đăng xuất thành công.');
    }

    // ─── Forgot Password ─────────────────────────────────────────────────────

    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(ForgotPasswordRequest $request)
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', 'Link đặt lại mật khẩu đã được gửi đến email của bạn.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }

    // ─── Reset Password ───────────────────────────────────────────────────────

    public function showResetForm(string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => request('email'),
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('auth.login')
                ->with('success', 'Mật khẩu đã được đặt lại thành công! Vui lòng đăng nhập.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}