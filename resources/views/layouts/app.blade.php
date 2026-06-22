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

    <script>
        (function () {
            try {
                if (!navigator.geolocation) return;

                var nativeGet = navigator.geolocation.getCurrentPosition.bind(navigator.geolocation);
                var nativeWatch = navigator.geolocation.watchPosition && navigator.geolocation.watchPosition.bind(navigator.geolocation);
                var nativeClear = navigator.geolocation.clearWatch && navigator.geolocation.clearWatch.bind(navigator.geolocation);

                try {
                    window.__nativeGeolocationGet = nativeGet;
                    window.__nativeGeolocationWatch = nativeWatch;
                    window.__nativeGeolocationClear = nativeClear;
                } catch (e) { }

                navigator.geolocation.getCurrentPosition = function (success, error, options) {
                    if (window.__allowGeolocation__) return nativeGet(success, error, options);
                    try {
                        console.warn('[gps-guard] Blocked getCurrentPosition call (no user gesture). Stack:', new Error().stack);
                    } catch (e) { }
                };

                if (nativeWatch) {
                    var _nextWatchId = 1;
                    var _activeWatches = {};
                    navigator.geolocation.watchPosition = function (success, error, options) {
                        if (window.__allowGeolocation__) return nativeWatch(success, error, options);
                        var id = _nextWatchId++;
                        _activeWatches[id] = true;
                        return id;
                    };
                    navigator.geolocation.clearWatch = function (id) {
                        if (_activeWatches[id]) delete _activeWatches[id];
                        if (nativeClear) return nativeClear(id);
                    };
                }

                window.allowGeolocationForNextCall = function () {
                    var now = Date.now();
                    if (!window.__lastUserGestureAt || (now - window.__lastUserGestureAt) > 3000) {
                        console.warn('[gps-guard] allowGeolocationForNextCall ignored: no recent user gesture');
                        return false;
                    }
                    window.__allowGeolocation__ = true;
                    setTimeout(function () { window.__allowGeolocation__ = false; }, 15000);
                    return true;
                };

                window.__lastUserGestureAt = 0;
                ['click', 'keydown', 'touchstart'].forEach(function (ev) {
                    window.addEventListener(ev, function () {
                        window.__lastUserGestureAt = Date.now();
                    }, { passive: true, capture: true });
                });
            } catch (e) { }
        })();
    </script>

    @if (auth()->check())
        <script>window.authUserId = {{ auth()->id() }};</script>
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>

<body class="font-sans bg-slate-50 text-slate-800 antialiased selection:bg-primary/10 selection:text-primary">

    {{-- ── Top Navigation ── --}}
    @include('components.nav.topnav')

    {{-- ── Flash Messages ── --}}
    @include('components.ui.success-message')
    @include('components.ui.error-message')

    {{-- ── Page Content ── --}}
    @if (isset($slot))
        {{ $slot }}
    @else
        @yield('content')
    @endif

    {{-- ── Footer ── --}}
    <footer class="site-footer">
        <div class="site-footer-main">

            {{-- Brand --}}
            <div class="space-y-4">
                <div class="flex items-center gap-2.5">
                    <div
                        class="w-8 h-8 bg-gradient-to-tr from-primary to-primary-dark rounded-lg flex items-center justify-center shadow-md shadow-primary/20">
                        <span class="text-white font-black text-sm">T</span>
                    </div>
                    <span class="text-white font-extrabold text-lg tracking-tight">
                        TìmTrẻ<span class="text-primary">.vn</span>
                    </span>
                </div>
                <p class="text-xs leading-relaxed text-slate-400">
                    Nền tảng trực tuyến hỗ trợ tìm kiếm trẻ em mất tích khẩn cấp,
                    kết nối xã hội và ứng dụng phân tích đối chiếu khuôn mặt AI tiên tiến.
                </p>
            </div>

            {{-- Tính năng chính --}}
            <div class="space-y-3">
                <h4 class="footer-heading">Tính năng chính</h4>
                <ul class="space-y-2 text-xs">
                    <li><a href="{{ route('missing.index') }}" class="footer-link">Duyệt hồ sơ mất tích</a></li>
                    <li><a href="{{ route('map.index') }}" class="footer-link">Bản đồ định vị GPS</a></li>
                    <li><a href="{{ route('stats.index') }}" class="footer-link">Thống kê dữ liệu</a></li>
                    <li><a href="{{ route('found-children.create') }}" class="footer-link">Báo tin khẩn cấp</a></li>
                </ul>
            </div>

            {{-- Hệ thống --}}
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

            {{-- Liên hệ --}}
            <div class="space-y-3">
                <h4 class="footer-heading">Liên hệ hỗ trợ</h4>
                <ul class="space-y-2.5 text-xs leading-relaxed">
                    <li class="flex items-center gap-2">
                        <span class="text-slate-500"><x-heroicon-o-envelope class="w-4 h-4 text-slate-500" /></span>
                        <span>hotro@timtre.vn</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="text-slate-500"><x-heroicon-o-phone class="w-4 h-4 text-slate-500" /></span>
                        <span>1800 1234 <span class="text-slate-500">(miễn phí)</span></span>
                    </li>
                    <li class="flex items-center gap-2 pt-1 border-t border-slate-800 mt-1">
                        <span><x-heroicon-o-exclamation-triangle class="w-4 h-4 text-red-500" /></span>
                        <strong class="text-white font-black">Cảnh sát Cứu Hộ: <span
                                class="text-primary">113</span></strong>
                    </li>
                </ul>
            </div>

        </div>

        <div class="site-footer-copy">
            © 2026 TìmTrẻ.vn — Vì mỗi đứa trẻ đều xứng đáng được về nhà
        </div>
    </footer>

    <div class="fixed bottom-44 right-5 z-40 md:hidden flex flex-col items-end gap-1">
        <span class="bg-slate-900 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">Gọi 113</span>
        <a href="tel:113" aria-label="Gọi 113 — Cảnh sát cứu hộ" class="flex items-center justify-center w-14 h-14
           bg-white border-2 border-primary rounded-full
           transition-all duration-300 active:scale-95" style="box-shadow: 0 0 20px 2px rgba(255,59,48,0.25);">
            <x-heroicon-s-phone class="w-6 h-6 text-primary" />
        </a>
    </div>

    <div class="fixed bottom-24 right-5 z-40 md:hidden flex flex-col items-end gap-1">
        <span class="bg-primary text-white text-[10px] font-bold px-2 py-0.5 rounded-full">Báo tin</span>
        <a href="{{ route('found-children.create') }}" aria-label="Báo tin khẩn cấp tìm trẻ" class="flex items-center justify-center w-14 h-14
               bg-gradient-to-br from-primary to-primary-dark
               rounded-full border-2 border-white
               transition-all duration-300 active:scale-95" style="box-shadow: 0 0 20px 2px rgba(255,59,48,0.4);">
            <x-heroicon-s-megaphone class="w-6 h-6 text-white" />
        </a>
    </div>

    @auth
        <div class="fixed bottom-6 right-5 z-50 md:bottom-8 md:right-8">
            <button type="button" onclick="openChat()" class="group flex items-center gap-2
                                       bg-slate-900 text-white
                                       rounded-full px-5 py-3
                                       text-sm font-bold
                                       shadow-xl shadow-slate-900/25
                                       transition-all duration-300
                                       hover:bg-slate-700
                                       hover:-translate-y-0.5
                                       hover:shadow-2xl
                                       active:scale-95">
                <x-heroicon-o-chat-bubble-left-right class="w-4 h-4 shrink-0" />
                <span>Chat</span>
            </button>
        </div>
    @endauth

    {{-- ── Chat Modal ── --}}
    @auth
        <div id="chat-modal"
            class="hidden fixed inset-0 z-[9999] flex items-stretch justify-center sm:items-end sm:justify-end sm:p-6">

            {{-- Overlay --}}
            <div id="chat-modal-overlay"
                class="absolute inset-0 bg-black/40 opacity-0 backdrop-blur-sm transition-opacity duration-300 ease-out"
                onclick="closeChat()"></div>

            {{-- Chat Window --}}
            <div id="chat-modal-window" class="relative z-10 w-full h-[100dvh]
                                         sm:w-[min(900px,calc(100vw-3rem))]
                                         sm:h-[min(650px,calc(100vh-3rem))]
                                         bg-white sm:rounded-[28px] shadow-2xl
                                         overflow-hidden flex flex-col
                                         translate-y-8 scale-[0.98] opacity-0
                                         transition-all duration-300 ease-out
                                         sm:translate-y-10">

                <button type="button" onclick="closeChat()" class="absolute right-3 top-3 z-20
                                           flex h-9 w-9 items-center justify-center
                                           rounded-full border border-slate-200 bg-white/95
                                           text-slate-500 shadow-sm transition
                                           hover:bg-slate-50 hover:text-slate-900" aria-label="Đóng khung chat">
                    <x-heroicon-o-x-mark class="h-5 w-5" />
                </button>

                @livewire('chat.chat-container')
            </div>
        </div>
    @endauth

    <script>
        let chatModalCloseTimer = null;

        function openChat() {
            const modal = document.getElementById('chat-modal');
            const overlay = document.getElementById('chat-modal-overlay');
            const windowEl = document.getElementById('chat-modal-window');

            if (!modal || !overlay || !windowEl) return;

            clearTimeout(chatModalCloseTimer);
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            requestAnimationFrame(() => {
                overlay.classList.remove('opacity-0');
                overlay.classList.add('opacity-100');

                windowEl.classList.remove('translate-y-8', 'scale-[0.98]', 'opacity-0', 'sm:translate-y-10');
                windowEl.classList.add('translate-y-0', 'scale-100', 'opacity-100', 'sm:translate-y-0');
            });
        }

        function closeChat() {
            const modal = document.getElementById('chat-modal');
            const overlay = document.getElementById('chat-modal-overlay');
            const windowEl = document.getElementById('chat-modal-window');

            if (!modal || !overlay || !windowEl) return;

            overlay.classList.remove('opacity-100');
            overlay.classList.add('opacity-0');

            windowEl.classList.remove('translate-y-0', 'scale-100', 'opacity-100', 'sm:translate-y-0');
            windowEl.classList.add('translate-y-8', 'scale-[0.98]', 'opacity-0', 'sm:translate-y-10');

            chatModalCloseTimer = setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);

            document.body.style.overflow = 'auto';
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeChat();
        });
    </script>

    @livewireScripts
    @stack('scripts')
</body>

</html>