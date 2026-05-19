@extends('layouts.app')

@section('title', 'Hồ sơ cá nhân')

@section('content')
<main class="min-h-screen pt-24 pb-16 flex items-center justify-center bg-slate-50 px-4">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl shadow-slate-100 p-8 text-center border border-slate-100 space-y-6">
        
        {{-- Animated Icon --}}
        <div class="mx-auto w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center animate-bounce">
            <svg class="w-10 h-10 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
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
