<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\OtpVerificationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    // ─── Register ────────────────────────────────────────────────────────────

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc,dns', 'max:191', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ], [
            'full_name.required' => 'Vui lòng nhập họ và tên.',
            'full_name.max' => 'Họ và tên tối đa 100 ký tự.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'email.unique' => 'Email này đã được đăng ký.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.mixed' => 'Mật khẩu phải có cả chữ hoa và chữ thường.',
            'password.numbers' => 'Mật khẩu phải chứa ít nhất một chữ số.',
        ]);

        $user = User::create([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => 'PENDING',
            'role' => 'USER',
        ]);

        $this->sendOtp($user);

        // Lưu user id vào session để dùng trong trang verify-otp
        session(['otp_user_id' => $user->id]);

        return redirect()->route('auth.otp.form')
            ->with('success', 'Tài khoản đã được tạo! Vui lòng kiểm tra email để lấy mã OTP.');
    }

    // ─── OTP ─────────────────────────────────────────────────────────────────

    public function showOtpForm()
    {
        // Nếu không có session → redirect về đăng ký
        if (!session('otp_user_id')) {
            return redirect()->route('auth.register');
        }

        return view('auth.verify-otp');
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.required' => 'Vui lòng nhập mã OTP.',
            'otp.digits' => 'Mã OTP phải là 6 chữ số.',
        ]);

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

        // Kiểm tra OTP hết hạn
        if (!$user->otp_expires_at || now()->isAfter($user->otp_expires_at)) {
            return back()->withErrors(['otp' => 'Mã OTP đã hết hạn. Vui lòng gửi lại mã mới.']);
        }

        // Kiểm tra OTP đúng
        if (!Hash::check($request->otp, $user->otp_code)) {
            return back()->withErrors(['otp' => 'Mã OTP không đúng. Vui lòng thử lại.']);
        }

        // Xác thực thành công
        $user->update([
            'email_verified_at' => now(),
            'status' => 'ACTIVE',
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

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

        // Rate limit: tối đa 3 lần/phút per user
        $rateLimitKey = 'otp-resend:' . $user->id;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            return back()->with('error', "Bạn đã gửi quá nhiều lần. Vui lòng thử lại sau {$seconds} giây.");
        }

        RateLimiter::hit($rateLimitKey, 60);

        $this->sendOtp($user);

        return back()->with('success', 'Mã OTP mới đã được gửi đến email của bạn.');
    }

    // ─── Login ───────────────────────────────────────────────────────────────

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        // Rate limit login: 5 lần/phút per IP+email
        $rateLimitKey = 'login:' . Str::lower($validated['email']) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            return back()
                ->withInput($request->only('email'))
                ->with('error', "Quá nhiều lần đăng nhập thất bại. Vui lòng thử lại sau {$seconds} giây.");
        }

        $user = User::where('email', $validated['email'])->first();

        // Kiểm tra user tồn tại và password đúng
        if (!$user || !Hash::check($validated['password'], $user->password ?? '')) {
            RateLimiter::hit($rateLimitKey, 60);
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email hoặc mật khẩu không đúng.']);
        }

        // Kiểm tra trạng thái tài khoản
        if ($user->status === 'PENDING') {
            // Gửi lại OTP nếu chưa xác thực
            session(['otp_user_id' => $user->id]);
            $this->sendOtp($user);
            return redirect()->route('auth.otp.form')
                ->with('error', 'Tài khoản chưa được xác thực. Mã OTP mới đã được gửi đến email của bạn.');
        }

        if ($user->status === 'BANNED') {
            $reason = $user->ban_reason ?? 'Vi phạm điều khoản sử dụng.';
            return back()
                ->withInput($request->only('email'))
                ->with('error', "Tài khoản đã bị khóa. Lý do: {$reason}");
        }

        RateLimiter::clear($rateLimitKey);

        Auth::login($user, $request->boolean('remember'));

        // Cập nhật last_active_at
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

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Tạo OTP 6 chữ số, hash và lưu vào DB, gửi notification.
     */
    private function sendOtp(User $user): void
    {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'otp_code' => Hash::make($otp),
            'otp_expires_at' => now()->addMinutes(15),
        ]);

        $user->notify(new OtpVerificationNotification($otp));
    }
}