@extends('auth.layouts.auth')

@section('title', 'Xác thực OTP')
@section('subtitle', 'Nhập mã 6 chữ số đã gửi đến email của bạn')

@section('content')

    <form method="POST" action="{{ route('auth.otp.verify') }}" novalidate>
        @csrf

        {{-- Icon email --}}
        <div class="flex justify-center mb-6">
            <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center">
                <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
        </div>

        <p class="text-center text-sm text-gray-500 mb-6">
            Mã OTP đã được gửi đến email của bạn.<br>
            Mã có hiệu lực trong <span class="font-semibold text-gray-700">15 phút</span>.
        </p>

        {{-- OTP input --}}
        <div class="mb-6">
            <label for="otp" class="block text-sm font-medium text-gray-700 mb-1.5">
                Mã OTP <span class="text-red-500">*</span>
            </label>
            <input id="otp" name="otp" type="text" inputmode="numeric" maxlength="6" autocomplete="one-time-code"
                placeholder="_ _ _ _ _ _" autofocus class="block w-full rounded-xl border px-4 py-3 text-2xl font-bold tracking-[0.5em]
                       text-center shadow-sm transition
                       focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                       {{ $errors->has('otp') ? 'border-red-400 bg-red-50' : 'border-gray-300' }}">
            @error('otp')
                <p class="mt-1.5 text-xs text-red-600 text-center">{{ $message }}</p>
            @enderror
        </div>

        {{-- Submit --}}
        <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-xl
                   text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700
                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
                   transition shadow-sm">
            Xác thực
        </button>
    </form>

    {{-- Gửi lại OTP --}}
    <div class="mt-5 text-center">
        <p class="text-sm text-gray-500 mb-2">Không nhận được mã?</p>
        <form method="POST" action="{{ route('auth.otp.resend') }}">
            @csrf
            <button type="submit"
                class="text-sm font-medium text-blue-600 hover:text-blue-500 underline underline-offset-2">
                Gửi lại mã OTP
            </button>
        </form>
    </div>

    {{-- Link quay lại --}}
    <p class="mt-4 text-center text-sm text-gray-400">
        <a href="{{ route('auth.register') }}" class="hover:text-gray-600">
            ← Quay lại đăng ký
        </a>
    </p>

@endsection