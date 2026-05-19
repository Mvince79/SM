@extends('auth.layouts.auth')

@section('title', 'Đăng ký thành viên')
@section('card-width', 'auth-card-wide')

@section('content')

    {{-- Title block --}}
    <div class="text-center space-y-2">
        <h2 class="auth-title">Đăng ký thành viên</h2>
        <p class="auth-subtitle">Cùng chung tay lan tỏa thông tin tìm trẻ lạc</p>
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('auth.register') }}" class="space-y-4" novalidate>
        @csrf

        {{-- Name + Phone (2 cols) --}}
        <div class="grid sm:grid-cols-2 gap-4">
            <div class="form-field">
                <label class="form-label">Họ tên của bạn *</label>
                <input name="full_name" type="text" required autocomplete="name" value="{{ old('full_name') }}"
                    placeholder="Ví dụ: Nguyễn Văn Bình"
                    class="form-input {{ $errors->has('full_name') ? 'form-input-error' : '' }}" autofocus>
                @error('full_name')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label class="form-label">Số điện thoại *</label>
                <input name="phone" type="tel" required autocomplete="tel" value="{{ old('phone') }}"
                    placeholder="Ví dụ: 0905 123 456"
                    class="form-input {{ $errors->has('phone') ? 'form-input-error' : '' }}">
                @error('phone')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Email --}}
        <div class="form-field">
            <label class="form-label">Địa chỉ email</label>
            <input name="email" type="email" autocomplete="email" value="{{ old('email') }}"
                placeholder="Ví dụ: binhnguyen@gmail.com"
                class="form-input {{ $errors->has('email') ? 'form-input-error' : '' }}">
            @error('email')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password + Confirm (2 cols) --}}
        <div class="grid sm:grid-cols-2 gap-4">
            <div class="form-field">
                <label class="form-label">Mật khẩu *</label>
                <input name="password" type="password" required autocomplete="new-password" placeholder="••••••••"
                    class="form-input {{ $errors->has('password') ? 'form-input-error' : '' }}">
                @error('password')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label class="form-label">Xác nhận mật khẩu *</label>
                <input name="password_confirmation" type="password" required autocomplete="new-password"
                    placeholder="••••••••"
                    class="form-input {{ $errors->has('password_confirmation') ? 'form-input-error' : '' }}">
                @error('password_confirmation')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Privacy checkbox --}}
        <label class="flex items-start gap-2.5 cursor-pointer pt-1.5">
            <input type="checkbox" name="terms" required class="mt-1 accent-primary flex-shrink-0">
            <span class="text-[10px] text-slate-500 leading-relaxed font-bold">
                Tôi đồng ý cam kết tuân thủ
                <a href="#" class="text-primary hover:underline">Điều khoản sử dụng</a>
                và
                <a href="#" class="text-primary hover:underline">Chính sách bảo vệ dữ liệu trẻ em</a>
                của TìmTrẻ.vn. Cam kết không spam, đăng tin khống.
            </span>
        </label>
        @error('terms')
            <p class="form-error">{{ $message }}</p>
        @enderror

        {{-- Submit --}}
        <button type="submit" class="btn-primary">
            Đăng ký
        </button>
    </form>

    {{-- Divider --}}
    <div class="auth-divider">
        <span>Hoặc tạo nhanh bằng</span>
    </div>

    {{-- OAuth --}}
    <div class="grid grid-cols-2 gap-3">
        <a href="{{ route('auth.google') }}" class="btn-oauth text-slate-700">
            <svg class="w-4 h-4" viewBox="0 0 24 24">
                <path fill="#4285F4"
                    d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                <path fill="#34A853"
                    d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                <path fill="#FBBC05"
                    d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                <path fill="#EA4335"
                    d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
            </svg>
            Google
        </a>
        <button type="button" disabled class="btn-oauth text-[#1877F2] opacity-50 cursor-not-allowed">
            <svg class="w-4 h-4" fill="#1877F2" viewBox="0 0 24 24">
                <path
                    d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 4.99 3.657 9.128 8.438 9.878v-6.987h-2.54V12.07h2.54V9.845c0-2.522 1.492-3.915 3.777-3.915 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562v1.875h2.773l-.443 2.89h-2.33v6.988C20.343 21.2 24 17.062 24 12.073z" />
            </svg>
            Facebook
        </button>
    </div>

    {{-- Switch link --}}
    <p class="auth-footer-link">
        Đã có tài khoản? <a href="{{ route('auth.login') }}">Đăng nhập</a>
    </p>

@endsection