@extends('auth.layouts.auth')

@section('title', 'Đăng nhập')
@section('subtitle', 'Chào mừng trở lại 👋')

@section('content')

    <form method="POST" action="{{ route('auth.login.submit') }}" novalidate>
        @csrf

        {{-- Email --}}
        <div class="mb-4">
            <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">
                Email <span class="text-red-500">*</span>
            </label>
            <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}"
                placeholder="example@gmail.com" autofocus class="block w-full rounded-xl border px-4 py-2.5 text-sm transition
                          focus:outline-none focus:ring-2 focus:ring-[#1a56db] focus:border-transparent
                          {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50' }}">
            @error('email')
                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Mật khẩu --}}
        <div class="mb-4">
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-sm font-semibold text-gray-700">
                    Mật khẩu <span class="text-red-500">*</span>
                </label>
                {{-- Sprint 1: đổi thành route('password.request') khi làm reset password --}}
                <a href="#" class="text-xs text-[#1a56db] hover:underline font-medium">
                    Quên mật khẩu?
                </a>
            </div>
            <input id="password" name="password" type="password" autocomplete="current-password" placeholder="••••••••"
                class="block w-full rounded-xl border px-4 py-2.5 text-sm transition
                          focus:outline-none focus:ring-2 focus:ring-[#1a56db] focus:border-transparent
                          {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50' }}">
            @error('password')
                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Remember --}}
        <div class="flex items-center gap-2 mb-5">
            <input id="remember" name="remember" type="checkbox"
                class="h-4 w-4 rounded text-[#1a56db] border-gray-300 focus:ring-[#1a56db]" style="accent-color: #1a56db;">
            <label for="remember" class="text-sm text-gray-600 cursor-pointer select-none">
                Ghi nhớ đăng nhập
            </label>
        </div>

        {{-- Submit --}}
        <button type="submit" class="w-full py-2.5 rounded-xl text-sm font-semibold text-white
                       bg-[#1a56db] hover:bg-[#1648c0] active:scale-[0.99]
                       transition-all shadow-sm focus:outline-none focus:ring-2
                       focus:ring-offset-2 focus:ring-[#1a56db]">
            Đăng nhập
        </button>
    </form>

    {{-- Divider --}}
    <div class="my-5 flex items-center gap-3">
        <div class="flex-1 h-px bg-gray-100"></div>
        <span class="text-xs text-gray-400 font-medium">hoặc</span>
        <div class="flex-1 h-px bg-gray-100"></div>
    </div>

    {{-- Google --}}
    <a href="{{ route('auth.google') }}" class="flex items-center justify-center gap-2.5 w-full py-2.5 px-4
              rounded-xl border border-gray-200 bg-white text-sm font-medium
              text-gray-700 hover:bg-gray-50 transition shadow-sm no-underline">
        <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24">
            <path fill="#4285F4"
                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
            <path fill="#34A853"
                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
            <path fill="#FBBC05"
                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
            <path fill="#EA4335"
                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
        </svg>
        Đăng nhập với Google
    </a>

    <p class="mt-6 text-center text-sm text-gray-500">
        Chưa có tài khoản?
        <a href="{{ route('auth.register') }}" class="font-semibold text-[#1a56db] hover:underline">
            Đăng ký ngay
        </a>
    </p>

@endsection