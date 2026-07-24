@props(['match'])
@php
    $found = $match->foundChild;
    $auth = Auth::check();
    $photo = $found->photos->first();
    $imgUrl = $photo
        ? ($auth ? asset('storage/' . $photo->path_thumb) : asset('storage/' . $photo->path_blurred))
        : null;
@endphp
<div class="flex gap-3 items-center bg-white border border-slate-200 rounded-xl p-3">
    {{-- Thumbnail --}}
    <div
        class="relative w-14 h-14 rounded-lg overflow-hidden flex-shrink-0 bg-slate-100 flex items-center justify-center"
        @if($imgUrl)
            x-data="{ imageLoaded: false }"
            x-init="imageLoaded = $refs.image.complete"
        @endif
    >
        @if($imgUrl)
            <x-ui.skeleton
                x-show="!imageLoaded"
                width="100%"
                height="100%"
                rounded="rounded-none"
                class="absolute inset-0"
            />
            <img
                x-ref="image"
                x-show="imageLoaded"
                x-cloak
                src="{{ $imgUrl }}"
                alt="Ảnh hồ sơ {{ $found->full_name ?? 'trẻ được tìm thấy' }}"
                class="w-full h-full object-cover {{ $auth ? '' : 'blur-sm' }}"
                @load="imageLoaded = true"
                @error="imageLoaded = true"
            >
        @else
            <x-heroicon-s-user class="w-6 h-6 text-slate-300" />
        @endif
    </div>
    {{-- Info --}}
    <div class="flex-1 min-w-0 space-y-1">
        <x-missing.ai-match-badge :score="$match->score" />
        <p class="text-xs font-bold text-slate-700 truncate">
            {{ $found->province?->name ?? 'Không rõ tỉnh' }}
        </p>
        <p class="text-[10px] text-slate-400 font-medium">
            Tìm thấy: {{ $found->found_date ? \Carbon\Carbon::parse($found->found_date)->format('d/m/Y') : '—' }}
        </p>
    </div>
    {{-- Link --}}
    <a href="{{ route('found-children.show', $found->id) }}"
        class="text-[10px] font-black text-primary hover:underline whitespace-nowrap">
        Xem chi tiết →
    </a>
</div>
