@extends('auth.layouts.auth')

@section('title', 'Xác thực OTP')

@section('content')

    {{-- Title block --}}
    <div class="text-center space-y-2">
        <h2 class="auth-title">Xác thực mã OTP</h2>
        <p class="auth-subtitle">
            Mã 6 chữ số đã gửi đến
            <span class="text-slate-700 font-extrabold">{{ session('otp_target') ?? '••••••' }}</span>
        </p>
    </div>

    @if(session('status'))
        <div class="flash-success">
            <span>{{ session('status') }}</span>
        </div>
    @endif

    {{-- OTP digit inputs --}}
    <form method="POST" action="{{ route('auth.otp.verify') }}" class="space-y-6" novalidate id="otp-form">
        @csrf

        <div class="flex justify-center gap-3" id="otp-boxes">
            @for($i = 0; $i < 6; $i++)
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="w-11 h-14 text-center text-xl font-black text-slate-900
                               border border-slate-200 rounded-xl bg-slate-50
                               focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent
                               transition-all otp-digit
                               {{ $errors->has('otp') ? 'border-primary/50 bg-primary-light' : '' }}" autocomplete="off">
            @endfor
            <input type="hidden" name="otp" id="otp-hidden">
        </div>

        @error('otp')
            <p class="form-error text-center">{{ $message }}</p>
        @enderror

        <button type="submit" class="btn-primary">
            Xác nhận
        </button>
    </form>

    {{-- Resend --}}
    <p class="auth-footer-link">
        Chưa nhận được mã?
    <form method="POST" action="{{ route('auth.otp.resend') }}" class="inline">
        @csrf
        <button type="submit" class="text-primary font-black hover:underline bg-transparent border-none cursor-pointer p-0">
            Gửi lại
        </button>
    </form>
    </p>

@endsection

@push('scripts')
    <script>
        // OTP digit navigation
        const digits = document.querySelectorAll('.otp-digit');
        const hidden = document.getElementById('otp-hidden');

        digits.forEach((el, idx) => {
            el.addEventListener('input', () => {
                el.value = el.value.replace(/\D/g, '').slice(-1);
                if (el.value && idx < digits.length - 1) digits[idx + 1].focus();
                syncHidden();
            });
            el.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !el.value && idx > 0) digits[idx - 1].focus();
            });
            el.addEventListener('paste', (e) => {
                e.preventDefault();
                const data = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
                digits.forEach((d, i) => d.value = data[i] || '');
                syncHidden();
                digits[Math.min(data.length, digits.length - 1)].focus();
            });
        });

        function syncHidden() {
            hidden.value = [...digits].map(d => d.value).join('');
        }
    </script>
@endpush