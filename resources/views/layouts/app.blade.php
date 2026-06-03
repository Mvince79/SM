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
        // Guard: prevent non-user-gesture scripts from triggering geolocation prompts.
        (function() {
            try {
                if (!navigator.geolocation) return;

                var nativeGet = navigator.geolocation.getCurrentPosition.bind(navigator.geolocation);
                var nativeWatch = navigator.geolocation.watchPosition && navigator.geolocation.watchPosition.bind(
                    navigator.geolocation);
                var nativeClear = navigator.geolocation.clearWatch && navigator.geolocation.clearWatch.bind(navigator
                    .geolocation);
                // expose native methods for explicit user-gesture calls
                try {
                    window.__nativeGeolocationGet = nativeGet;
                    window.__nativeGeolocationWatch = nativeWatch;
                    window.__nativeGeolocationClear = nativeClear;
                } catch (e) {}

                navigator.geolocation.getCurrentPosition = function(success, error, options) {
                    if (window.__allowGeolocation__) {
                        return nativeGet(success, error, options);
                    }
                    // log stack to help identify callers, but do NOT call their error handler
                    try {
                        var stack = new Error().stack;
                        console.warn('[gps-guard] Blocked getCurrentPosition call (no user gesture). Stack:',
                            stack);
                    } catch (e) {}
                    // intentionally do not call error callback to avoid alerts on page load
                };

                if (nativeWatch) {
                    // track fake watch ids
                    var _nextWatchId = 1;
                    var _activeWatches = {};
                    navigator.geolocation.watchPosition = function(success, error, options) {
                        if (window.__allowGeolocation__) {
                            return nativeWatch(success, error, options);
                        }
                        // do not call error callback when blocked
                        var id = _nextWatchId++;
                        _activeWatches[id] = true;
                        return id;
                    };

                    navigator.geolocation.clearWatch = function(id) {
                        if (_activeWatches[id]) delete _activeWatches[id];
                        if (nativeClear) return nativeClear(id);
                    };
                }

                window.allowGeolocationForNextCall = function() {
                    var now = Date.now();
                    // require a recent real user gesture (click/keydown/touchstart) to permit
                    if (!window.__lastUserGestureAt || (now - window.__lastUserGestureAt) > 3000) {
                        console.warn('[gps-guard] allowGeolocationForNextCall ignored: no recent user gesture');
                        return false;
                    }
                    window.__allowGeolocation__ = true;
                    console.log('[gps-guard] allowGeolocationForNextCall set');
                    setTimeout(function() {
                        window.__allowGeolocation__ = false;
                        console.log('[gps-guard] allowGeolocationForNextCall expired');
                    }, 15000);
                    return true;
                };
                // track last user gesture (click/touch) so we can distinguish programmatic calls
                window.__lastUserGestureAt = 0;
                ['click', 'keydown', 'touchstart'].forEach(function(ev) {
                    window.addEventListener(ev, function() {
                        window.__lastUserGestureAt = Date.now();
                    }, {
                        passive: true,
                        capture: true
                    });
                });
            } catch (e) {
                /* ignore */
            }
        })();
    </script>
    @if (auth()->check())
        <script>
            window.authUserId = {{ auth()->id() }};
        </script>
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>


<body class="font-sans bg-slate-50 text-slate-800 antialiased selection:bg-primary/10 selection:text-primary">

    {{-- ── Top Navigation ── --}}
    @include('components.nav.topnav')

    {{-- Flash message --}}
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
                    <li><a href="{{ route('missing.index') }}" class="footer-link">Duyệt hồ sơ</a></li>
                    <li><a href="{{ route('map.index') }}" class="footer-link">Bản đồ định vị GPS</a></li>
                    <li><a href="{{ route('stats.index') }}" class="footer-link">Thống kê dữ liệu</a></li>
                    <li><a href="{{ route('found-children.create') }}" class="footer-link">Báo tin khẩn cấp</a></li>
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


    {{-- ── Chat FAB ── --}}
    @auth
        <div class="fixed bottom-10 right-10 z-50">
            <button type="button" onclick="openChat()"
                class="w-20 h-20 bg-blue-600 text-white rounded-full
               shadow-xl flex items-center justify-center
               hover:bg-blue-700 transition">
                Chat
            </button>
        </div>
    @endauth

    {{-- ── Mobile FAB ── --}}
    <div class="mobile-fab">
        <a href="{{ route('found-children.create') }}" aria-label="Báo tin khẩn cấp">
            <span class="text-2xl font-bold">🚨</span>
        </a>
    </div>

    {{-- Chat Modal --}}
    @auth
        <div id="chat-modal" class="hidden fixed inset-0 z-[9999] flex justify-end items-end p-6">

            {{-- Overlay --}}
            <div class="absolute inset-0 bg-black/40" onclick="closeChat()"></div>

            {{-- Chat Window --}}
            <div
                class="
    relative
    z-10
    w-[900px]
    h-[650px]
    bg-white
    rounded-[32px]
    shadow-2xl
    overflow-hidden
    flex
    flex-col
    ">
                @livewire('chat.chat-container')
            </div>

        </div>
    @endauth

    <script>
        function openChat() {
            document.getElementById('chat-modal').classList.remove('hidden');

            document.body.style.overflow = 'hidden';
        }

        function closeChat() {
            document.getElementById('chat-modal').classList.add('hidden');

            document.body.style.overflow = 'auto';
        }
    </script>

    @livewireScripts
    @stack('scripts')
</body>

</html>


@livewireScripts
@stack('scripts')
</body>

</html>
