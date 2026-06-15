{{--
┌─────────────────────────────────────────────────────────────────┐
│ @component('components.nav.topnav') │
│ Props (via $attributes or parent $page / auth state): │
│ $activeRoute — current named route (e.g. 'home') │
└─────────────────────────────────────────────────────────────────┘
--}}

@php
    try {
        $req = request();
        if (!$req->hasSession()) {
            $sess = app('session')->driver();
            $sess->setId($req->cookies->get($sess->getName()));
            $sess->start();
            $req->setLaravelSession($sess);
            app('auth')->shouldUse('web');
        }
    } catch (\Throwable) {
    }

    $navLinks = [
        ['route' => 'home', 'label' => 'Trang chủ'],
        ['route' => 'missing.index', 'label' => 'Hồ sơ mất tích'],
        ['route' => 'map.index', 'label' => 'Bản đồ tìm kiếm'],
        ['route' => 'stats.index', 'label' => 'Thống kê'],
    ];
@endphp

<nav class="topnav" id="topnav">
    <div class="topnav-inner">

        {{-- ── Brand ── --}}
        <a href="{{ route('home') }}" class="brand-logo">
            <div class="brand-logo-mark">
                <span class="text-white font-black text-base">T</span>
            </div>
            <span class="brand-logo-text">TìmTrẻ<span class="text-primary">.vn</span></span>
        </a>

        {{-- ── Desktop Nav Links ── --}}
        <div class="hidden md:flex items-center gap-8">
            @foreach ($navLinks as $link)
                    @php $isActive = request()->routeIs($link['route']); @endphp
                    <a href="{{ route($link['route']) }}" class="text-sm py-1 relative transition-colors
                                   {{ $isActive
                ? 'font-bold text-primary after:content-[\'\'] after:absolute after:bottom-0 after:left-0 after:w-full after:h-0.5 after:bg-primary after:rounded-full'
                : 'font-medium text-slate-600 hover:text-primary' }}">
                        {{ $link['label'] }}
                    </a>
            @endforeach
        </div>

        {{-- ── Desktop CTA ── --}}
        <div class="hidden md:flex items-center gap-4">
            @auth
                @if (Auth::user()->isAdmin())
                    <a href="/admin"
                        class="text-sm font-bold text-brand hover:text-brand-dark transition-colors mr-2 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-brand" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                        Quản trị
                    </a>
                @endif

                <x-notification-bell />

                <a href="{{ route('profile.index') }}" class="flex items-center gap-2 group">
                    <div class="w-8 h-8 rounded-full overflow-hidden bg-slate-100 border border-slate-200">
                        <img src="{{ Auth::user()->avatar_url }}" class="w-full h-full object-cover"
                            alt="{{ Auth::user()->full_name }}" />
                    </div>
                    <span
                        class="text-sm font-bold text-slate-800 group-hover:text-primary transition-colors hidden lg:block">
                        {{ Auth::user()->full_name }}
                    </span>
                </a>

                <form method="POST" action="{{ route('auth.logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-sm font-semibold text-slate-400 hover:text-primary transition-colors">
                        Đăng xuất
                    </button>
                </form>
            @else
                <a href="{{ route('auth.login') }}"
                    class="text-sm font-semibold text-slate-600 hover:text-primary transition-colors">
                    Đăng nhập
                </a>
            @endauth

            <a href="{{ route('missing.create') }}" class="bg-primary text-white text-sm font-bold px-5 py-2.5 rounded-xl
                       hover:bg-primary-dark shadow-lg shadow-primary/10
                       hover:shadow-primary/25 hover:-translate-y-0.5 transition-all duration-300">
                + Báo tin tìm con
            </a>
        </div>

        {{-- ── Mobile: Bell + Hamburger cùng hàng bên phải ── --}}
        <div class="ml-auto flex items-center gap-1 md:hidden">
            @auth
                <x-notification-bell />
            @endauth

            <button type="button" id="mobile-hamburger" class="relative flex h-10 w-10 items-center justify-center rounded-full
                       text-slate-600 transition-all duration-200 hover:bg-slate-100 active:scale-90"
                aria-label="Mở menu" aria-expanded="false">
                <span class="flex flex-col items-center justify-center gap-[5px]">
                    <span id="bar1"
                        class="block h-0.5 w-5 rounded-full bg-slate-700 transition-all duration-300 origin-center"></span>
                    <span id="bar2"
                        class="block h-0.5 w-4 rounded-full bg-slate-700 transition-all duration-300"></span>
                    <span id="bar3"
                        class="block h-0.5 w-5 rounded-full bg-slate-700 transition-all duration-300 origin-center"></span>
                </span>
            </button>
        </div>

    </div>{{-- /topnav-inner --}}

    {{-- ── Mobile Drawer ── --}}
    <div id="mobile-menu" data-open="false"
        class="md:hidden hidden border-t border-slate-100 bg-white px-4 py-5 space-y-4 shadow-xl">
        {{-- Nav links --}}
        @foreach ($navLinks as $link)
            <a href="{{ route($link['route']) }}"
                class="block text-base font-semibold transition-colors
                           {{ request()->routeIs($link['route']) ? 'text-primary font-bold' : 'text-slate-700 hover:text-primary' }}">
                {{ $link['label'] }}
            </a>
        @endforeach

        <hr class="border-slate-100">

        {{-- Auth actions --}}
        <div class="grid grid-cols-2 gap-3">
            @auth
                @if (Auth::user()->isAdmin())
                    <a href="/admin" class="col-span-2 flex items-center justify-center gap-2 text-center font-bold
                                       bg-brand text-white py-3 rounded-xl hover:bg-brand-dark transition-all">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                        Bảng điều khiển Quản trị
                    </a>
                @endif

                <a href="{{ route('profile.index') }}"
                    class="text-center font-bold text-slate-700 hover:bg-slate-50 py-3 rounded-xl border border-slate-200 transition-all">
                    Hồ sơ
                </a>

                <form method="POST" action="{{ route('auth.logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-full text-center font-bold bg-primary text-white py-3 rounded-xl hover:bg-primary-dark transition-all">
                        Đăng xuất
                    </button>
                </form>
            @else
                <a href="{{ route('auth.login') }}"
                    class="text-center font-bold text-slate-700 hover:bg-slate-50 py-3 rounded-xl border border-slate-200 transition-all">
                    Đăng nhập
                </a>
                <a href="{{ route('missing.create') }}"
                    class="text-center font-bold bg-primary text-white py-3 rounded-xl hover:bg-primary-dark transition-all">
                    + Báo tin
                </a>
            @endauth
        </div>
    </div>

</nav>