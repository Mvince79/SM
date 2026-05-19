@extends('layouts.app')

@section('title', 'Hồ sơ cá nhân')

@section('content')
<main class="min-h-screen pt-24 pb-16 flex items-center justify-center bg-slate-50 px-4">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl shadow-slate-100 p-8 text-center border border-slate-100 space-y-6">
        
        {{-- Animated Icon --}}
        <div class="mx-auto w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center animate-bounce">
            <svg class="w-10 h-10 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A1.5 1.5 0 0019.5 21l.75-.75a1.5 1.5 0 000-2.25l-5.83-5.83M11.42 15.17l2.83-2.83m-2.83 2.83l-5.83-5.83M11.42 15.17l-5.83 5.83A1.5 1.5 0 013 21l-.75-.75a1.5 1.5 0 010-2.25l5.83-5.83M14.25 12.34l5.83-5.83A1.5 1.5 0 0021 4.5l-.75-.75a1.5 1.5 0 00-2.25 0l-5.83 5.83m0 0l-2.83 2.83m0-2.83l-5.83-5.83A1.5 1.5 0 003 3.75l-.75.75a1.5 1.5 0 000 2.25l5.83 5.83" />
            </svg>
        </div>

        <div class="space-y-2">
            <h1 class="text-2xl font-black text-slate-800">Hồ sơ cá nhân</h1>
            <p class="text-slate-500 font-medium">Tính năng này đang được phát triển</p>
        </div>

        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-5 space-y-3">
            <span class="inline-block px-3 py-1 bg-primary/10 text-primary text-xs font-bold rounded-full">
                Sprint 1 — PB-10
            </span>
            <p class="text-sm text-slate-600 leading-relaxed">
                Trang cá nhân cho phép bạn cập nhật thông tin liên hệ, xem lại lịch sử các tin đăng tìm kiếm trẻ em thất lạc và cấu hình bán kính nhận thông báo thông minh.
            </p>
        </div>

        <div class="pt-4">
            <a href="{{ route('home') }}" class="inline-block w-full bg-primary text-white font-bold py-3.5 px-6 rounded-2xl
                      hover:bg-primary-dark shadow-lg shadow-primary/10 hover:shadow-primary/20 hover:-translate-y-0.5 transition-all duration-300">
                Quay lại trang chủ
            </a>
        </div>
    </div>
</main>
@endsection
