{{--
┌─────────────────────────────────────────────────────────────────┐
│ @component('components.nav.topnav')                             │
│ Props (via $attributes or parent $page / auth state):           │
│   $activeRoute — current named route (e.g. 'home')              │
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
    <div class="topnav-inner gap-6">

        {{-- ── Brand ── --}}
        <a href="{{ route('home') }}" class="brand-logo shrink-0">
            <div class="brand-logo-mark">
                <span class="text-white font-black text-base">T</span>
            </div>
            <span class="brand-logo-text whitespace-nowrap">TìmTrẻ<span class="text-primary">.vn</span></span>
        </a>

        {{-- ── Desktop Nav Links ── --}}
        <div class="hidden lg:flex items-center gap-8">
            @foreach ($navLinks as $link)
                @php $isActive = request()->routeIs($link['route']); @endphp
                <a href="{{ route($link['route']) }}"
                    class="text-sm py-1 relative transition-colors whitespace-nowrap
                                                           {{ $isActive
                                                               ? 'font-bold text-primary after:content-[\'\'] after:absolute after:bottom-0 after:left-0 after:w-full after:h-0.5 after:bg-primary after:rounded-full'
                                                               : 'font-medium text-slate-600 hover:text-primary' }}">
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>

        {{-- ── Desktop CTA ── --}}
        <div class="hidden lg:flex items-center gap-4">
            @auth
                <x-notification-bell />

                {{-- Avatar dropdown: gộp Quản trị / Xác thực KYC / Hồ sơ / Đăng xuất --}}
                <div x-data="{ open: false }" @click.outside="open = false" class="relative">
                    <button type="button" @click="open = !open"
                        class="flex items-center gap-2 group min-w-0 rounded-full pl-1 pr-2.5 py-1 hover:bg-slate-50 transition-colors">
                        <div class="relative w-8 h-8 rounded-full overflow-hidden bg-slate-100 border border-slate-200 shrink-0">
                            <img src="{{ Auth::user()->avatar_url }}" class="w-full h-full object-cover"
                                alt="{{ Auth::user()->full_name }}" />
                            @if (Auth::user()->kyc_status !== 'VERIFIED')
                                <span
                                    class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-amber-500 border-2 border-white"
                                    title="Cần xác thực CCCD"></span>
                            @endif
                        </div>
                        <span
                            class="text-sm font-bold text-slate-800 group-hover:text-primary transition-colors truncate max-w-[110px]">
                            {{ Auth::user()->full_name }}
                        </span>
                        <x-heroicon-o-chevron-down
                            class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform"
                            x-bind:class="open && 'rotate-180'" />
                    </button>

                    <div x-show="open" x-cloak x-transition.origin.top.right
                        class="absolute right-0 mt-2 w-56 bg-white rounded-2xl border border-slate-200/85 shadow-premium py-2 z-50">

                        @if (Auth::user()->isAdmin())
                            <a href="/admin"
                                class="flex items-center gap-2.5 px-4 py-2.5 text-sm font-bold text-brand hover:bg-slate-50 transition-colors">
                                <x-heroicon-o-shield-check class="w-4 h-4 text-brand shrink-0" /> Quản trị
                            </a>
                        @endif

                        <a href="{{ route('profile.kyc') }}"
                            class="flex items-center justify-between gap-2.5 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">
                            <span class="flex items-center gap-2.5">
                                <x-heroicon-o-identification class="w-4 h-4 text-slate-400 shrink-0" /> Xác thực KYC
                            </span>
                            @if (Auth::user()->kyc_status !== 'VERIFIED')
                                <span class="w-2 h-2 rounded-full bg-amber-500 shrink-0"></span>
                            @endif
                        </a>

                        <a href="{{ route('profile.index') }}"
                            class="flex items-center gap-2.5 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">
                            <x-heroicon-o-user-circle class="w-4 h-4 text-slate-400 shrink-0" /> Hồ sơ
                        </a>

                        <hr class="border-slate-100 my-1.5">

                        <form method="POST" action="{{ route('auth.logout') }}">
                            @csrf
                            <button type="submit"
                                class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm font-bold text-slate-400 hover:bg-slate-50 hover:text-primary transition-colors">
                                <x-heroicon-o-arrow-right-start-on-rectangle class="w-4 h-4 shrink-0" /> Đăng xuất
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('auth.login') }}"
                    class="text-sm font-semibold text-slate-600 hover:text-primary transition-colors whitespace-nowrap">
                    Đăng nhập
                </a>
            @endauth

            <a href="{{ route('missing.create') }}"
                class="bg-primary text-white text-sm font-bold px-5 py-2.5 rounded-xl whitespace-nowrap
                       hover:bg-primary-dark shadow-lg shadow-primary/10
                       hover:shadow-primary/25 hover:-translate-y-0.5 transition-all duration-300">
                + Báo tin tìm con
            </a>
        </div>

        {{-- ── Mobile/Tablet: Bell + Hamburger cùng hàng bên phải (hiện tới trước lg) ── --}}
        <div class="ml-auto flex items-center gap-2 lg:hidden">

            <a href="{{ route('missing.create') }}"
                class="flex h-10 w-10 items-center justify-center rounded-full
               bg-primary text-white font-black text-xl
               shadow-lg shadow-primary/20
               hover:bg-primary-dark
               active:scale-90 transition-all duration-200">
                +
            </a>

            @auth
                <x-notification-bell />
            @endauth

            <button type="button" id="mobile-hamburger"
                class="relative flex h-10 w-10 items-center justify-center rounded-full
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

    {{-- ── Mobile/Tablet Drawer (hiện tới trước xl) ── --}}
    <div id="mobile-menu" data-open="false"
        class="lg:hidden hidden border-t border-slate-100 bg-white px-4 py-5 space-y-4 shadow-xl">
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
                    <a href="/admin"
                        class="col-span-2 flex items-center justify-center gap-2 text-center font-bold
                                       bg-brand text-white py-3 rounded-xl hover:bg-brand-dark transition-all">
                        <x-heroicon-o-shield-check class="w-5 h-5" />
                        Bảng điều khiển Quản trị
                    </a>
                @endif

                <a href="{{ route('profile.kyc') }}"
                    class="text-center font-bold text-slate-700 hover:bg-slate-50 py-3 rounded-xl border border-slate-200 transition-all">
                    Xác thực KYC
                </a>

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