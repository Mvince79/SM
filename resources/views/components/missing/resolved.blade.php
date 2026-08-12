@extends('layouts.app')

@section('title', 'Đoàn tụ thành công: ' . $case->full_name)

@push('head')
    <meta property="og:title" content="Đoàn tụ thành công: {{ $case->full_name }}">
    <meta property="og:description"
        content="Bé {{ $case->full_name }} đã được tìm thấy{{ $daysCount ? ' sau ' . $daysCount . ' ngày' : '' }}.">
    @if($ogImage)
        <meta property="og:image" content="{{ str_starts_with($ogImage, 'http') ? $ogImage : asset('storage/' . $ogImage) }}">
    @endif
    <meta property="og:type" content="article">
@endpush

@section('content')

    {{-- BREADCRUMB --}}
    <div class="max-w-5xl mx-auto px-4 py-3">
        <nav
            class="text-[10px] sm:text-xs font-bold text-slate-400 flex gap-1.5 items-center uppercase tracking-wider flex-wrap">
            <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Trang chủ</a>
            <span>/</span>
            <a href="{{ route('missing.index') }}" class="hover:text-primary transition-colors">Hồ sơ mất tích</a>
            <span>/</span>
            <span class="text-slate-700 truncate">{{ $case->full_name }}</span>
            <span>/</span>
            <span class="text-green-600">Đoàn tụ</span>
        </nav>
    </div>

    <main class="max-w-5xl mx-auto px-4 pb-16 space-y-6">

        {{-- HERO BANNER --}}
        <div
            class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-2xl sm:rounded-3xl px-6 py-8 text-center text-white shadow-lg">
            <div class="text-4xl mb-2">🎉</div>
            <h1 class="text-2xl sm:text-3xl font-extrabold mb-1">Đoàn tụ thành công!</h1>
            <p class="text-green-100 text-sm font-medium">
                Bé <span class="font-extrabold text-white">{{ $case->full_name }}</span>
                đã được tìm thấy và trở về an toàn.
            </p>
        </div>

        {{-- 2 CỘT ẢNH --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">

            {{-- Ảnh lúc mất --}}
            <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                <div class="bg-red-50 border-b border-red-100 px-4 py-3 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>
                    <span class="text-red-600 font-extrabold text-xs uppercase tracking-wider">Lúc mất tích</span>
                </div>
                <div class="p-4">
                    @php $missingPhoto = $case->primaryPhoto ?? $case->photos->first(); @endphp
                    @if($missingPhoto)
                        <img src="{{ asset('storage/' . ($missingPhoto->path_thumb ?? $missingPhoto->path_original)) }}"
                            alt="Ảnh lúc mất: {{ $case->full_name }}" class="w-full aspect-square object-cover rounded-xl">
                    @else
                        <div
                            class="w-full aspect-square bg-slate-100 rounded-xl flex items-center justify-center text-slate-400 text-sm">
                            Chưa có ảnh
                        </div>
                    @endif
                </div>
            </div>

            {{-- Ảnh lúc tìm thấy --}}
            <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                <div class="bg-green-50 border-b border-green-100 px-4 py-3 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                    <span class="text-green-600 font-extrabold text-xs uppercase tracking-wider">Lúc tìm thấy</span>
                </div>
                <div class="p-4">
                    @php $foundPhoto = $foundChild?->primaryPhoto ?? $foundChild?->photos->first(); @endphp
                    @if($foundPhoto)
                        <img src="{{ asset('storage/' . ($foundPhoto->path_thumb ?? $foundPhoto->path_original)) }}"
                            alt="Ảnh lúc tìm thấy: {{ $case->full_name }}" class="w-full aspect-square object-cover rounded-xl">
                    @else
                        <div
                            class="w-full aspect-square bg-slate-100 rounded-xl flex items-center justify-center text-slate-400 text-sm">
                            Chưa có ảnh
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- TIMELINE --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 shadow-sm">
            <h2 class="text-xs font-extrabold uppercase tracking-widest text-slate-400 mb-4">Hành trình tìm kiếm</h2>
            <x-missing.resolved-timeline :case="$case" :daysCount="$daysCount" />
        </div>

        {{-- AI SCORE — chỉ hiện khi có match CONFIRMED --}}
        @if($matchResult)
            <div class="bg-brand/5 border border-brand/20 rounded-2xl p-5 sm:p-6 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-brand flex items-center justify-center flex-shrink-0">
                    <x-heroicon-o-light-bulb class="w-6 h-6 text-white" />
                </div>
                <div>
                    <p class="text-brand font-extrabold text-base">
                        Được phát hiện nhờ AI khớp
                        <span class="text-brand text-xl">{{ round($matchResult->score * 100) }}%</span>
                    </p>
                    <p class="text-brand/70 text-xs mt-0.5">Hệ thống nhận diện khuôn mặt AI tự động khớp hồ sơ</p>
                </div>
            </div>
        @endif

        {{-- SHARE BUTTONS --}}
        @php
            $shareUrl = route('missing.found', $case->id);
            $shareTitle = urlencode('Bé ' . $case->full_name . ' đã được tìm thấy! Cùng chia sẻ tin vui này.');
        @endphp

        <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 shadow-sm">
            <h2 class="text-xs font-extrabold uppercase tracking-widest text-slate-400 mb-4">Chia sẻ tin vui</h2>
            <div class="flex flex-wrap gap-3">

                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}" target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-2 bg-[#1877F2] hover:bg-[#1665d8] text-white text-sm font-bold px-4 py-2.5 rounded-xl transition-colors">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z" />
                    </svg>
                    Facebook
                </a>

                <a href="https://zalo.me/share/url?url={{ urlencode($shareUrl) }}&title={{ $shareTitle }}" target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-2 bg-[#0068FF] hover:bg-[#0057d9] text-white text-sm font-bold px-4 py-2.5 rounded-xl transition-colors">
                    Zalo
                </a>

                <a href="https://twitter.com/intent/tweet?url={{ urlencode($shareUrl) }}&text={{ $shareTitle }}"
                    target="_blank" rel="noopener"
                    class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-700 text-white text-sm font-bold px-4 py-2.5 rounded-xl transition-colors">
                    Twitter/X
                </a>

                <button
                    onclick="navigator.clipboard.writeText('{{ $shareUrl }}').then(() => { this.textContent = '✓ Đã copy'; setTimeout(() => this.textContent = 'Copy link', 2000) })"
                    class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold px-4 py-2.5 rounded-xl transition-colors">
                    Copy link
                </button>

            </div>
        </div>

        {{-- CTA --}}
        <div class="text-center py-4">
            <a href="{{ route('missing.index') }}"
                class="inline-flex items-center gap-2 bg-primary hover:bg-primary/90 text-white font-extrabold px-8 py-3.5 rounded-2xl transition-colors text-sm shadow-md">
                <x-heroicon-o-magnifying-glass class="w-4 h-4 inline" /> Giúp tìm thêm trẻ khác
            </a>
        </div>

    </main>

@endsection