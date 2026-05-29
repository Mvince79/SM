@extends('layouts.app')
@section('title', '404 — Không tìm thấy trang')

@section('content')
<main class="min-h-full flex items-center justify-center px-4 py-20">
    <div class="text-center space-y-6 max-w-md w-full">

        {{-- Icon --}}
        <div class="w-24 h-24 rounded-3xl bg-slate-100 border border-slate-200
                    flex items-center justify-center text-5xl mx-auto shadow-sm">
            🔍
        </div>

        {{-- Code + Message --}}
        <div class="space-y-2">
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                Không tìm thấy trang
            </h1>
            <p class="text-sm text-slate-400 font-medium leading-relaxed">
                Trang bạn đang tìm không tồn tại, đã bị di chuyển<br>
                hoặc đường dẫn bị sai.
            </p>
        </div>

        {{-- CTA --}}
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ url('/') }}"
               class="inline-flex items-center justify-center gap-2
                      bg-primary text-white font-extrabold text-xs
                      px-6 py-3 rounded-xl hover:bg-primary-dark
                      transition-all shadow-md shadow-primary/10 uppercase tracking-widest">
                🏠 Về trang chủ
            </a>
            <a href="{{ url('/missing') }}"
               class="inline-flex items-center justify-center gap-2
                      border border-slate-200 text-slate-700 font-extrabold text-xs
                      px-6 py-3 rounded-xl hover:bg-slate-50 transition-colors">
                Xem danh sách tin tìm kiếm
            </a>
        </div>

    </div>
</main>
@endsection