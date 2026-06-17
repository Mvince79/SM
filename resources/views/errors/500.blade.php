@extends('layouts.app')
@section('title', '500 — Lỗi hệ thống')

@section('content')
<main class="min-h-full flex items-center justify-center px-4 py-20">
    <div class="text-center space-y-6 max-w-md w-full">

        <div class="w-24 h-24 rounded-3xl bg-amber-50 border border-amber-200
                    flex items-center justify-center text-5xl mx-auto shadow-sm">
            <x-heroicon-o-exclamation-triangle class="w-16 h-16 mx-auto text-amber-400" />
        </div>

        <div class="space-y-2">
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                Lỗi hệ thống
            </h1>
            <p class="text-sm text-slate-400 font-medium leading-relaxed">
                Máy chủ đang gặp sự cố, vui lòng thử lại sau.<br>
                Nếu vấn đề tiếp tục xảy ra, hãy liên hệ đội hỗ trợ.
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ url('/') }}"
               class="inline-flex items-center justify-center gap-2
                      bg-primary text-white font-extrabold text-xs
                      px-6 py-3 rounded-xl hover:bg-primary-dark
                      transition-all shadow-md shadow-primary/10 uppercase tracking-widest">
                <x-heroicon-o-home class="w-4 h-4" /> Về trang chủ
            </a>
            <button onclick="window.location.reload()"
                    class="inline-flex items-center justify-center gap-2
                           border border-slate-200 text-slate-700 font-extrabold text-xs
                           px-6 py-3 rounded-xl hover:bg-slate-50 transition-colors cursor-pointer">
                🔄 Thử lại
            </button>
        </div>

    </div>
</main>
@endsection