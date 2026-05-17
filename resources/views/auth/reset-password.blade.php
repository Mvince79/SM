@extends('auth.layouts.auth')

@section('title', 'Đặt lại mật khẩu')
@section('subtitle', 'Nhập mật khẩu mới cho tài khoản của bạn')

@section('content')

    <form method="POST" action="{{ route('password.update') }}" novalidate>
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        {{-- Email --}}
        <div class="mb-5">
            <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">
                Email <span class="text-red-500">*</span>
            </label>
            <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email', $email) }}" class="block w-full rounded-xl border px-4 py-2.5 text-sm transition
                               focus:outline-none focus:ring-2 focus:ring-[#1a56db] focus:border-transparent
                               {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50' }}">
            @error('email')
                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Mật khẩu mới --}}
        <div class="mb-5">
            <label for="password" class="block text-sm font-semibold text-gray-700 mb-1.5">
                Mật khẩu mới <span class="text-red-500">*</span>
            </label>
            <input id="password" name="password" type="password" autocomplete="new-password" placeholder="Ít nhất 8 ký tự"
                class="block w-full rounded-xl border px-4 py-2.5 text-sm transition
                               focus:outline-none focus:ring-2 focus:ring-[#1a56db] focus:border-transparent
                               {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50' }}">
            @error('password')
                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Xác nhận mật khẩu --}}
        <div class="mb-6">
            <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-1.5">
                Xác nhận mật khẩu <span class="text-red-500">*</span>
            </label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                placeholder="Nhập lại mật khẩu mới"
                class="block w-full rounded-xl border px-4 py-2.5 text-sm transition
                               focus:outline-none focus:ring-2 focus:ring-[#1a56db] focus:border-transparent
                               {{ $errors->has('password_confirmation') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50' }}">
            @error('password_confirmation')
                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="w-full py-2.5 rounded-xl text-sm font-semibold text-white
                           bg-[#1a56db] hover:bg-[#1648c0] active:scale-[0.99]
                           transition-all shadow-sm focus:outline-none focus:ring-2
                           focus:ring-offset-2 focus:ring-[#1a56db]">
            Đặt lại mật khẩu
        </button>
    </form>

@endsection