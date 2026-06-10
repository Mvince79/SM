@props(['score'])
@php
    $pct = round($score * 100, 2);
    $color = $pct >= 85 ? 'bg-green-500' : 'bg-amber-400';
@endphp
<span class="{{ $color }} text-white text-[10px] font-black px-2 py-0.5 rounded-full">
    {{ $pct }}% khớp
</span>