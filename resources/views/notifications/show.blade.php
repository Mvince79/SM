@extends('layouts.app')
@section('content')
<main class="max-w-xl mx-auto px-4 py-10">
    <a href="{{ route('notifications.center') }}"
        class="inline-flex items-center gap-1.5 text-xs font-black text-slate-400 hover:text-primary transition-colors mb-4">
        <x-heroicon-o-arrow-left class="w-3.5 h-3.5" /> Quay lại trung tâm thông báo
    </a>

    <div class="bg-white border border-slate-200/85 rounded-3xl p-6 sm:p-8 shadow-premium">
        <div class="flex items-center gap-4">
            @php
                $icon = match ($notification->event?->event_type) {
                    'FACE_MATCH_CONFIRMED' => 'heroicon-o-face-smile',
                    'HANDOVER_READY' => 'heroicon-o-check-circle',
                    'CASE_APPROVED', 'CASE_APPROVED_IN_PROVINCE' => 'heroicon-o-document-check',
                    'CLUE_VERIFIED' => 'heroicon-o-light-bulb',
                    default => 'heroicon-o-bell',
                };
            @endphp
            <div class="w-11 h-11 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0">
                <x-dynamic-component :component="$icon" class="w-5 h-5" />
            </div>
            <div class="min-w-0">
                <h1 class="text-lg font-black text-slate-900 leading-snug">
                    {{ $notification->rendered_title }}
                </h1>
                <p class="text-xs text-slate-400 font-semibold mt-0.5">
                    {{ $notification->created_at->diffForHumans() }}
                    @if ($notification->read_at)
                        · <span class="text-slate-300">Đã đọc</span>
                    @endif
                </p>
            </div>
        </div>

        <p class="text-sm text-slate-600 leading-relaxed mt-5 pt-5 border-t border-slate-100">
            {{ $notification->rendered_body }}
        </p>

        @if ($notification->action_url)
            <a href="{{ $notification->action_url }}"
                class="mt-6 inline-flex items-center gap-2 bg-primary text-white text-sm font-black px-5 py-3 rounded-xl hover:bg-primary-dark transition-colors">
                @if (auth()->user()->isAdmin())
                    <x-heroicon-o-shield-check class="w-4 h-4" /> Xem chi tiết (Quản trị)
                @else
                    Xem chi tiết
                @endif
            </a>
        @endif
    </div>
</main>
@endsection