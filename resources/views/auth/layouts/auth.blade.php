<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Xác thực') — TìmTrẻ.vn</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

<body class="auth-wrapper">

    {{-- ── Topnav (auth variant: shorter h-14, back link) ── --}}
    <nav class="topnav-auth">
        <div class="topnav-auth-inner">

            {{-- Brand --}}
            <a href="{{ route('home') }}" class="brand-logo brand-logo-auth">
                <div class="brand-logo-mark">
                    <span class="text-white font-black text-sm">T</span>
                </div>
                <span class="brand-logo-text font-black text-base tracking-tight text-slate-900">
                    TìmTrẻ<span class="text-primary">.vn</span>
                </span>
            </a>

            {{-- Back link --}}
            <a href="{{ route('home') }}"
                class="text-xs font-bold text-slate-500 hover:text-primary transition-colors flex items-center gap-1">
                ← Về trang chủ
            </a>

        </div>
    </nav>

    {{-- ── Main content ── --}}
    <main class="auth-main">
        <div class="auth-card @yield('card-width') auth-card-inner">

            {{-- Flash messages --}}
            @if(session('success'))
                <div class="flash-success">
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="flash-error">
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            {{-- Page content --}}
            @yield('content')

        </div>
    </main>

    {{-- ── Footer ── --}}
    <footer class="footer-simple">
        © 2026 TìmTrẻ.vn · Đường dây nóng: 1800 1234
    </footer>

    @stack('scripts')
</body>

</html>