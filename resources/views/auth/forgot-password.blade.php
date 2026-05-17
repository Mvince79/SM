@extends('auth.layouts.auth')

@section('title', 'Quên mật khẩu')
@section('subtitle', 'Nhập email để nhận link đặt lại mật khẩu')

@section('content')

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf

        <div class="mb-5">
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

        <button type="submit" class="w-full py-2.5 rounded-xl text-sm font-semibold text-white
                       bg-[#1a56db] hover:bg-[#1648c0] active:scale-[0.99]
                       transition-all shadow-sm focus:outline-none focus:ring-2
                       focus:ring-offset-2 focus:ring-[#1a56db]">
            Gửi link đặt lại mật khẩu
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500">
        <a href="{{ route('auth.login') }}" class="font-semibold text-[#1a56db] hover:underline">
            ← Quay lại đăng nhập
        </a>
    </p>

@endsection