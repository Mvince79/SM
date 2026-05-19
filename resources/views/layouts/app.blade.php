<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'TìmTrẻ.vn') — Vì con xứng đáng được về nhà</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

<body class="font-sans bg-slate-50 text-slate-800 antialiased selection:bg-primary/10 selection:text-primary">

    {{-- ── Top Navigation ── --}}
    @include('components.nav.topnav')

    {{-- ── Page Content ── --}}
    @yield('content')

    {{-- ── Footer ── --}}
    <footer class="site-footer">
        <div class="site-footer-main">

            {{-- Brand column --}}
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                        <span class="text-white font-extrabold text-sm">T</span>
                    </div>
                    <span class="text-white font-extrabold text-lg tracking-tight">
                        TìmTrẻ<span class="text-primary">.vn</span>
                    </span>
                </div>
                <p class="text-xs leading-relaxed">
                    Nền tảng trực tuyến hỗ trợ tìm kiếm trẻ em mất tích khẩn cấp, kết nối xã hội và ứng dụng phân tích
                    đối chiếu khuôn mặt AI tiên tiến.
                </p>
            </div>

            {{-- Features --}}
            <div class="space-y-3">
                <h4 class="footer-heading">Tính năng chính</h4>
                <ul class="space-y-2 text-xs">
                    <li><a href="{{ route('cases.index') }}" class="footer-link">Duyệt hồ sơ</a></li>
                    <li><a href="{{ route('map.index') }}" class="footer-link">Bản đồ định vị GPS</a></li>
                    <li><a href="{{ route('stats.index') }}" class="footer-link">Thống kê dữ liệu</a></li>
                    <li><a href="{{ route('cases.create') }}" class="footer-link">Báo tin khẩn cấp</a></li>
                </ul>
            </div>

            {{-- System --}}
            <div class="space-y-3">
                <h4 class="footer-heading">Hệ thống</h4>
                <ul class="space-y-2 text-xs">
                    <li><a href="{{ route('auth.register') }}" class="footer-link">Tạo tài khoản</a></li>
                    <li><a href="{{ route('auth.login') }}" class="footer-link">Đăng nhập tài khoản</a></li>
                    @auth
                        <li><a href="{{ route('profile.index') }}" class="footer-link">Quản lý hồ sơ cá nhân</a></li>
                    @endauth
                </ul>
            </div>

            {{-- Contact --}}
            <div class="space-y-3">
                <h4 class="footer-heading">Liên hệ hỗ trợ</h4>
                <ul class="space-y-2 text-xs leading-relaxed">
                    <li>📧 hotro@timtre.vn</li>
                    <li>📞 1800 1234 (Đường dây nóng miễn phí)</li>
                    <li class="pt-1">
                        <strong class="text-white">🚨 Tổng đài Cảnh sát Cứu Hộ: 113</strong>
                    </li>
                </ul>
            </div>
        </div>

        <div class="site-footer-copy">
            © 2026 TìmTrẻ.vn — Vì mỗi đứa trẻ đều xứng đáng được về nhà
        </div>
    </footer>

    {{-- ── Mobile FAB ── --}}
    <div class="mobile-fab">
        <a href="{{ route('cases.create') }}" aria-label="Báo tin khẩn cấp">
            <span class="text-2xl font-bold">🚨</span>
        </a>
    </div>

    @stack('scripts')
</body>

</html>