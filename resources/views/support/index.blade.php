@extends('layouts.app')
@section('title', 'Lịch sử yêu cầu hỗ trợ')

@section('content')

    {{-- Page header --}}
    <section class="bg-white border-b border-slate-100">
        <div class="max-w-4xl mx-auto px-4 py-8 sm:py-10">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div class="max-w-2xl space-y-3">
                    <div class="inline-flex items-center gap-2 rounded-full bg-primary/10 px-3 py-1.5
                                text-xs font-extrabold text-primary">
                        <x-heroicon-o-lifebuoy class="w-4 h-4" />
                        Trung tâm hỗ trợ
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                        Lịch sử yêu cầu hỗ trợ
                    </h1>

                    <p class="text-sm sm:text-base text-slate-500 font-semibold leading-7">
                        Theo dõi trạng thái và phản hồi của đội ngũ TìmTrẻ.vn cho các yêu cầu bạn đã gửi.
                    </p>
                </div>

                <a href="{{ route('support.create') }}"
                    class="inline-flex items-center gap-1.5 bg-primary text-white text-sm font-extrabold px-5 py-3 rounded-2xl
                           hover:bg-primary-dark transition-all shadow-md shadow-primary/10 shrink-0">
                    <x-heroicon-o-plus class="w-4 h-4" />
                    Gửi yêu cầu mới
                </a>
            </div>
        </div>
    </section>

    <main class="max-w-4xl mx-auto px-4 py-8 sm:py-10" x-data="{ filter: 'ALL' }">

        @if (session('ticket_submitted'))
            <div class="flex items-start gap-3 rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 mb-6"
                x-data x-init="setTimeout(() => $el.remove(), 5000)">
                <x-heroicon-o-check-circle class="w-5 h-5 text-emerald-500 mt-0.5 shrink-0" />
                <p class="text-sm font-bold text-emerald-800">{{ session('ticket_submitted') }}</p>
            </div>
        @endif

        @if ($tickets->isNotEmpty())
            {{-- Filter tabs --}}
            <div class="flex items-center gap-2 overflow-x-auto pb-1 mb-6 -mx-1 px-1">
                @foreach ([
                    'ALL' => 'Tất cả',
                    'OPEN' => 'Đang mở',
                    'IN_PROGRESS' => 'Đang xử lý',
                    'RESOLVED' => 'Đã giải quyết',
                    'CLOSED' => 'Đã đóng',
                ] as $value => $label)
                    <button type="button" @click="filter = '{{ $value }}'"
                        :class="filter === '{{ $value }}'
                            ? 'bg-slate-900 text-white border-slate-900'
                            : 'bg-white text-slate-500 border-slate-200 hover:border-slate-300'"
                        class="shrink-0 text-xs font-extrabold px-4 py-2 rounded-full border transition-colors">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        @endif

        @forelse ($tickets as $ticket)

            @php
                $chipClass = match ($ticket->status) {
                    'OPEN' => 'bg-sky-100 text-sky-600 border-sky-200',
                    'IN_PROGRESS' => 'bg-amber-100 text-amber-600 border-amber-200',
                    'RESOLVED' => 'bg-emerald-100 text-emerald-600 border-emerald-200',
                    'CLOSED' => 'bg-slate-100 text-slate-500 border-slate-200',
                    default => 'bg-slate-100 text-slate-500 border-slate-200',
                };
                $chipLabel = match ($ticket->status) {
                    'OPEN' => 'Đang mở',
                    'IN_PROGRESS' => 'Đang xử lý',
                    'RESOLVED' => 'Đã giải quyết',
                    'CLOSED' => 'Đã đóng',
                    default => $ticket->status,
                };
                $categoryIcon = match ($ticket->category) {
                    'BUG' => 'heroicon-o-bug-ant',
                    'COMPLAINT' => 'heroicon-o-exclamation-circle',
                    'FEEDBACK' => 'heroicon-o-light-bulb',
                    'FRAUD_SUSPECT' => 'heroicon-o-shield-exclamation',
                    default => 'heroicon-o-question-mark-circle',
                };
                $categoryLabel = match ($ticket->category) {
                    'BUG' => 'Báo lỗi kỹ thuật',
                    'COMPLAINT' => 'Khiếu nại',
                    'FEEDBACK' => 'Góp ý',
                    'FRAUD_SUSPECT' => 'Nghi ngờ lừa đảo / giả mạo',
                    default => $ticket->category,
                };
            @endphp

            <div x-show="filter === 'ALL' || filter === '{{ $ticket->status }}'" x-data="{ open: false }"
                class="bg-white border border-slate-200/80 rounded-3xl shadow-premium mb-4 overflow-hidden">

                <button type="button" @click="open = !open"
                    class="w-full flex items-start gap-4 text-left p-5 hover:bg-slate-50/60 transition-colors">

                    <div class="w-11 h-11 rounded-2xl bg-slate-50 border border-slate-200/70 text-slate-400
                                flex items-center justify-center shrink-0">
                        <x-dynamic-component :component="$categoryIcon" class="w-5 h-5" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <span
                                class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full border {{ $chipClass }}">
                                {{ $chipLabel }}
                            </span>
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wide">
                                {{ $categoryLabel }}
                            </span>
                        </div>
                        <h3 class="font-extrabold text-slate-900 text-sm truncate">{{ $ticket->subject }}</h3>
                        <p class="text-[10px] text-slate-300 font-semibold mt-0.5">
                            Gửi lúc {{ $ticket->created_at->format('H:i d/m/Y') }}
                            @if ($ticket->replies->isNotEmpty())
                                · {{ $ticket->replies->count() }} phản hồi
                            @endif
                        </p>
                    </div>

                    <x-heroicon-o-chevron-down class="w-5 h-5 text-slate-400 shrink-0 mt-1.5 transition-transform"
                        x-bind:class="open ? 'rotate-180' : ''" />
                </button>

                <div x-show="open" x-cloak x-transition class="px-5 pb-5 space-y-3">

                    <div class="border-t border-slate-100 pt-4">
                        <p class="text-sm text-slate-600 whitespace-pre-line">{{ $ticket->description }}</p>
                    </div>

                    @forelse ($ticket->replies as $reply)
                        <div
                            class="bg-slate-50 border border-slate-200/70 rounded-2xl p-3
                                {{ $reply->user_id !== $ticket->user_id ? 'border-l-4 border-l-primary' : '' }}">
                            <p class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wide mb-1">
                                {{ $reply->user_id === $ticket->user_id ? $ticket->user->full_name : 'Đội ngũ hỗ trợ' }}
                                · {{ $reply->created_at->format('H:i d/m/Y') }}
                            </p>
                            <p class="text-sm text-slate-700 whitespace-pre-line">{{ $reply->message }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 font-semibold flex items-center gap-1.5">
                            <x-heroicon-o-clock class="w-4 h-4" />
                            Chưa có phản hồi từ đội ngũ hỗ trợ.
                        </p>
                    @endforelse

                </div>

            </div>

        @empty

            <div class="text-center py-16 space-y-4">
                <div class="w-16 h-16 rounded-2xl bg-slate-50 flex items-center justify-center mx-auto">
                    <x-heroicon-o-lifebuoy class="w-8 h-8 text-slate-300" />
                </div>
                <h3 class="font-extrabold text-slate-700 text-base">Bạn chưa gửi yêu cầu hỗ trợ nào.</h3>
                <p class="text-sm text-slate-400 font-semibold">Gặp vấn đề khi dùng ứng dụng? Gửi ngay cho đội ngũ
                    của chúng tôi.</p>
                <a href="{{ route('support.create') }}"
                    class="inline-block bg-primary text-white font-bold text-sm px-6 py-3
                          rounded-xl hover:bg-primary-dark transition-colors shadow-md shadow-primary/10">
                    Gửi yêu cầu đầu tiên
                </a>
            </div>

        @endforelse

    </main>

@endsection