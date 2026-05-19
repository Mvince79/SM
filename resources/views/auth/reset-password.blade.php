@extends('auth.layouts.auth')

@section('title', 'Đặt lại mật khẩu')

@section('content')

    {{-- Title block --}}
    <div class="text-center space-y-2">
        <h2 class="auth-title">Đặt lại mật khẩu</h2>
        <p class="auth-subtitle">Tạo mật khẩu mới cho tài khoản của bạn</p>
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('password.store') }}" class="space-y-4" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        {{-- Email (hidden / readonly) --}}
        <div class="form-field">
            <label class="form-label">Địa chỉ email</label>
            <input name="email" type="email" required autocomplete="email" value="{{ old('email', $request->email) }}"
                class="form-input {{ $errors->has('email') ? 'form-input-error' : '' }}" readonly>
            @error('email')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        {{-- New password --}}
        <div class="form-field">
            <label class="form-label">Mật khẩu mới</label>
            <input name="password" type="password" required autocomplete="new-password" placeholder="••••••••"
                class="form-input {{ $errors->has('password') ? 'form-input-error' : '' }}" autofocus>
            @error('password')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        {{-- Confirm password --}}
        <div class="form-field">
            <label class="form-label">Xác nhận mật khẩu mới</label>
            <input name="password_confirmation" type="password" required autocomplete="new-password" placeholder="••••••••"
                class="form-input {{ $errors->has('password_confirmation') ? 'form-input-error' : '' }}">
            @error('password_confirmation')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn-primary">
            Đặt lại mật khẩu
        </button>
    </form>

@endsection