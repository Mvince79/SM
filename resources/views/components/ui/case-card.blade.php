@props(['case'])

@php
    // Nhãn trạng thái công khai — chỉ REUNITED mới khẳng định "đã tìm thấy".
    // FOUND / HANDED_OVER là giai đoạn trung gian (nghi vấn khớp, đang xác minh),
    // KHÔNG được hiển thị khẳng định "đã tìm thấy" (theo chuẩn thống nhất).
    [$badgeVariant, $badgeText] = match ($case->status) {
        'FOUND'     => ['brand', 'Nghi vấn khớp mạnh'],
        'HANDED_OVER' => ['brand', 'Nghi vấn khớp mạnh — đang xác minh'],
        'REUNITED'  => ['found', 'Đã tìm thấy'],
        default     => ['primary', 'Đang tìm khẩn'],
    };
@endphp

<div class="{{ $case->status === 'REUNITED'
    ? 'bg-success-light/40 border border-success/30'
    : 'bg-white border border-slate-200/80' }}
    rounded-3xl overflow-hidden shadow-premium
    hover:shadow-premium-hover hover:-translate-y-1
    transition-premium flex flex-col justify-between">

    {{-- Ảnh --}}
    <a href="{{ route('missing.detail', $case->id) }}"
       class="block group relative overflow-hidden aspect-[4/3] bg-slate-900">
        <picture>
            @if($case->thumb_webp_url)
                <source srcset="{{ $case->thumb_webp_url }}" type="image/webp">
            @endif
            <img src="{{ $case->thumb_url }}"
                 class="w-full h-full object-cover transform group-hover:scale-105 transition-premium
                        {{ $case->status === 'REUNITED' ? 'grayscale opacity-90' : '' }}"
                 alt="Ảnh {{ $case->full_name }}"
                 loading="lazy"
                 onerror="this.src='https://api.dicebear.com/7.x/thumbs/svg?seed={{ urlencode($case->full_name) }}&backgroundColor=ffdfbf'"/>
        </picture>

        {{-- Badge trạng thái --}}
        @if($case->status === 'REUNITED')
            <span class="absolute top-3 left-3 bg-success text-white text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-xl flex items-center gap-1">
                ✓ {{ $badgeText }}
            </span>
        @elseif($case->status === 'FOUND' || $case->status === 'HANDED_OVER')
            <span class="absolute top-3 left-3 bg-brand text-white text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-xl flex items-center gap-1">
                {{ $badgeText }}
            </span>
        @else
            <span class="absolute top-3 left-3 bg-primary text-white text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-xl shadow-glow-sos flex items-center gap-1">
                <span class="w-1.5 h-1.5 bg-white rounded-full animate-ping"></span>
                {{ $badgeText }}
            </span>
        @endif

        <span class="absolute top-3 right-3 bg-black/60 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded-lg">
            {{ $case->approved_at?->diffForHumans() ?? $case->created_at->diffForHumans() }}
        </span>
    </a>

    {{-- Body --}}
    <div class="p-5 space-y-4 flex-1 flex flex-col justify-between">
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <h3 class="font-extrabold text-slate-900 text-base hover:text-primary transition-colors">
                    <a href="{{ route('missing.detail', $case->id) }}">{{ $case->full_name }}</a>
                </h3>
                @if($case->age)
                    <span class="text-xs font-extrabold text-slate-400">{{ $case->age }} tuổi</span>
                @endif
            </div>

            <div class="text-xs font-semibold text-slate-500 flex items-center gap-1">
                <span><x-heroicon-o-map-pin class="w-4 h-4 inline shrink-0" /></span>
                {{ collect([$case->ward, $case->province?->name])->filter()->implode(', ') }}
            </div>

            @if($case->clothes_desc || $case->circumstances)
                <p class="text-xs text-slate-400 leading-relaxed line-clamp-2">
                    {{ $case->clothes_desc ?? $case->circumstances }}
                </p>
            @endif
        </div>

        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
            <span class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-600 text-[10px] font-extrabold px-2.5 py-1.5 rounded-full">
                {{ $case->case_code }}
            </span>
            @if($case->status !== 'REUNITED')
                <a href="{{ route('missing.detail', $case->id) }}"
                   class="text-xs font-extrabold text-primary hover:underline flex items-center gap-1">
                    Chi tiết <span class="text-[10px]">→</span>
                </a>
            @else
                <span class="text-[10px] text-slate-400 font-bold">Đang phối hợp xác minh cùng cơ quan chức năng</span>
            @endif
        </div>
    </div>
</div>