@extends('auth.layouts.auth')

@section('title', 'Đăng ký tài khoản')
@section('subtitle', 'Tạo tài khoản để tham gia tìm kiếm')

@section('content')

    <form method="POST" action="{{ route('auth.register.submit') }}" novalidate>
        @csrf

        {{-- Họ tên --}}
        <div class="mb-5">
            <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1.5">
                Họ và tên <span class="text-red-500">*</span>
            </label>
            <input id="full_name" name="full_name" type="text" autocomplete="name" value="{{ old('full_name') }}"
                placeholder="Nguyễn Văn A" class="block w-full rounded-xl border px-4 py-2.5 text-sm shadow-sm transition
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                               {{ $errors->has('full_name') ? 'border-red-400 bg-red-50' : 'border-gray-300' }}">
            @error('full_name')
                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Email --}}
        <div class="mb-5">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">
                Email <span class="text-red-500">*</span>
            </label>
            <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}"
                placeholder="example@gmail.com" class="block w-full rounded-xl border px-4 py-2.5 text-sm shadow-sm transition
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                               {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-300' }}">
            @error('email')
                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Mật khẩu --}}
        <div class="mb-5">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">
                Mật khẩu <span class="text-red-500">*</span>
            </label>
            <input id="password" name="password" type="password" autocomplete="new-password" placeholder="Ít nhất 8 ký tự"
                class="block w-full rounded-xl border px-4 py-2.5 text-sm shadow-sm transition
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                               {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-gray-300' }}">
            @error('password')
                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Xác nhận mật khẩu --}}
        <div class="mb-6">
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">
                Xác nhận mật khẩu <span class="text-red-500">*</span>
            </label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                placeholder="Nhập lại mật khẩu"
                class="block w-full rounded-xl border px-4 py-2.5 text-sm shadow-sm transition
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                               {{ $errors->has('password_confirmation') ? 'border-red-400 bg-red-50' : 'border-gray-300' }}">
            @error('password_confirmation')
                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Submit --}}
        <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-xl
                           text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700
                           focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
                           transition shadow-sm">
            Tạo tài khoản
        </button>
    </form>

    {{-- Divider --}}
    <div class="mt-6">
        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-200"></div>
            </div>
            <div class="relative flex justify-center text-xs">
                <span class="bg-white px-3 text-gray-400 font-medium">hoặc</span>
            </div>
        </div>

        {{-- Google OAuth --}}
        <div class="mt-4">
            <a href="{{ route('auth.google') }}" class="w-full flex items-center justify-center gap-3 py-2.5 px-4 border border-gray-300
                               rounded-xl text-sm font-medium text-gray-700 bg-white hover:bg-gray-50
                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300
                               transition shadow-sm">
                <svg class="w-5 h-5" viewBox="0 0 24 24">
                    <path fill="#4285F4"
                        d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                    <path fill="#34A853"
                        d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                    <path fill="#FBBC05"
                        d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                    <path fill="#EA4335"
                        d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                </svg>
                Đăng ký với Google
            </a>
        </div>
    </div>

    {{-- Link đăng nhập --}}
    <p class="mt-6 text-center text-sm text-gray-500">
        Đã có tài khoản?
        <a href="{{ route('auth.login') }}" class="font-medium text-blue-600 hover:text-blue-500">
            Đăng nhập
        </a>
    </p>

@endsection