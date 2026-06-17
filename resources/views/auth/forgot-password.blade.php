@extends('auth.layouts.auth')

@section('title', 'Quên mật khẩu')

@section('content')

    {{-- Title block --}}
    <div class="text-center space-y-2">
        <h2 class="auth-title">Quên mật khẩu?</h2>
        <p class="auth-subtitle">Nhập email để nhận hướng dẫn đặt lại mật khẩu</p>
    </div>

    @if(session('status'))
        <div class="flash-success">
            <x-heroicon-o-information-circle class="w-4 h-4 flex-shrink-0" />
            <span>{{ session('status') }}</span>
        </div>
    @endif

    {{-- Form --}}
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4" novalidate>
        @csrf

        <div class="form-field">
            <label class="form-label">Địa chỉ email</label>
            <input name="email" type="email" required autocomplete="email" value="{{ old('email') }}"
                placeholder="Ví dụ: binhnguyen@gmail.com"
                class="form-input {{ $errors->has('email') ? 'form-input-error' : '' }}" autofocus>
            @error('email')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn-primary">
            Gửi hướng dẫn đặt lại mật khẩu
        </button>
    </form>

    {{-- Back to login --}}
    <p class="auth-footer-link">
        Nhớ lại rồi? <a href="{{ route('auth.login') }}">Quay lại đăng nhập</a>
    </p>

@endsection