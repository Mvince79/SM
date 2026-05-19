{{--
┌─────────────────────────────────────────────────────────────────┐
│ @component('components.nav.topnav') │
│ Props (via $attributes or parent $page / auth state): │
│ $activeRoute — current named route (e.g. 'home') │
└─────────────────────────────────────────────────────────────────┘
--}}

@php
    // $active = request()->routeIs(...func_get_args()) ? 'active' : '';
    $navLinks = [
        ['route' => 'home', 'label' => 'Trang chủ', 'href' => route('home')],
        ['route' => 'cases.index', 'label' => 'Hồ sơ mất tích', 'href' => '#'],
        ['route' => 'map.index', 'label' => 'Bản đồ tìm kiếm', 'href' => '#'],
        ['route' => 'stats.index', 'label' => 'Thống kê', 'href' => '#'],
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
            @foreach($navLinks as $link)
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
                @if(Auth::user()->isAdmin())
                    <a href="/admin" class="text-sm font-bold text-brand hover:text-brand-dark transition-colors mr-2 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-brand" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                        Quản trị
                    </a>
                @endif
                <a href="{{ route('profile.index') }}"
                    class="text-sm font-semibold text-slate-600 hover:text-primary transition-colors">
                    {{ Auth::user()->full_name }}
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

            <a href="#" class="bg-primary text-white text-sm font-bold px-5 py-2.5 rounded-xl
                      hover:bg-primary-dark shadow-lg shadow-primary/10
                      hover:shadow-primary/25 hover:-translate-y-0.5 transition-all duration-300">
                + Báo tin tìm con
            </a>
        </div>

        {{-- ── Mobile Hamburger ── --}}
        <button type="button" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')"
            class="md:hidden p-2 rounded-xl text-slate-600 hover:bg-slate-50 transition-colors"
            aria-label="Toggle menu">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

    </div>

    {{-- ── Mobile Drawer ── --}}
    <div id="mobile-menu" class="md:hidden hidden border-t border-slate-100 bg-white px-4 py-5 space-y-4 shadow-xl">
        @foreach($navLinks as $link)
            <a href="{{ route($link['route']) }}"
                class="block text-base font-semibold transition-colors
                                  {{ request()->routeIs($link['route']) ? 'text-primary font-bold' : 'text-slate-700 hover:text-primary' }}">
                {{ $link['label'] }}
            </a>
        @endforeach

        <hr class="border-slate-100">

        <div class="grid grid-cols-2 gap-3">
            @auth
                @if(Auth::user()->isAdmin())
                    <a href="/admin"
                        class="col-span-2 text-center font-bold bg-brand text-white py-3 rounded-xl hover:bg-brand-dark transition-all flex items-center justify-center gap-2">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
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
                <a href="#"
                    class="text-center font-bold bg-primary text-white py-3 rounded-xl hover:bg-primary-dark transition-all">
                    + Báo tin
                </a>
            @endauth
        </div>
    </div>
</nav>