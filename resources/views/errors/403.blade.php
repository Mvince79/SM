@extends('layouts.app')
@section('title', '403 — Không có quyền truy cập')

@section('content')
<main class="min-h-full flex items-center justify-center px-4 py-20">
    <div class="text-center space-y-6 max-w-md w-full">

        <div class="w-24 h-24 rounded-3xl bg-slate-100 border border-slate-200
                    flex items-center justify-center text-5xl mx-auto shadow-sm">
            🔒
        </div>

        <div class="space-y-2">
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                Bạn không có quyền truy cập
            </h1>
            <p class="text-sm text-slate-400 font-medium leading-relaxed">
                Trang này yêu cầu quyền hạn cao hơn hoặc bạn chưa đăng nhập.<br>
                Vui lòng đăng nhập để tiếp tục.
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            @guest
                <a href="{{ route('auth.login') }}"
                   class="inline-flex items-center justify-center gap-2
                          bg-primary text-white font-extrabold text-xs
                          px-6 py-3 rounded-xl hover:bg-primary-dark
                          transition-all shadow-md shadow-primary/10 uppercase tracking-widest">
                    🔑 Đăng nhập
                </a>
            @endguest
            <a href="{{ url('/') }}"
               class="inline-flex items-center justify-center gap-2
                      border border-slate-200 text-slate-700 font-extrabold text-xs
                      px-6 py-3 rounded-xl hover:bg-slate-50 transition-colors">
                🏠 Về trang chủ
            </a>
        </div>

    </div>
</main>
@endsection