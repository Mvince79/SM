@props(['match'])
@php
    $found = $match->foundChild;
    $auth  = Auth::check();
    $photo = $found->photos->first();
    $imgUrl = $photo
        ? ($auth ? asset('storage/'.$photo->path_thumb) : asset('storage/'.$photo->path_blurred))
        : null;
@endphp
<div class="flex gap-3 items-center bg-white border border-slate-200 rounded-xl p-3">
    {{-- Thumbnail --}}
    <div class="w-14 h-14 rounded-lg overflow-hidden flex-shrink-0 bg-slate-100">
        @if($imgUrl)
            <img src="{{ $imgUrl }}" class="w-full h-full object-cover {{ $auth ? '' : 'blur-sm' }}">
        @else
            <div class="w-full h-full flex items-center justify-center text-2xl">👤</div>
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