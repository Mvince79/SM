@props(['case', 'daysCount'])

<div class="flex items-center gap-3 flex-wrap">

    {{-- Mốc 1: Ngày mất --}}
    <div class="flex flex-col items-center bg-red-50 border border-red-200 rounded-xl px-4 py-3 min-w-[110px]">
        <span class="text-[10px] uppercase tracking-widest text-red-400 font-bold mb-1">Ngày mất tích</span>
        <span class="text-red-600 font-extrabold text-base">
            {{ $case->missing_date ? \Carbon\Carbon::parse($case->missing_date)->format('d/m/Y') : '—' }}
        </span>
    </div>

    {{-- Khoảng giữa + số ngày --}}
    <div class="flex flex-col items-center gap-1">
        <div class="h-[2px] w-8 bg-slate-300 rounded"></div>
        @if($daysCount !== null)
            <span class="text-[10px] text-slate-500 font-bold whitespace-nowrap">{{ $daysCount }} ngày</span>
        @endif
        <div class="h-[2px] w-8 bg-slate-300 rounded"></div>
    </div>

    {{-- Mốc 2: Ngày tìm thấy --}}
    <div class="flex flex-col items-center bg-green-50 border border-green-200 rounded-xl px-4 py-3 min-w-[110px]">
        <span class="text-[10px] uppercase tracking-widest text-green-500 font-bold mb-1">Ngày tìm thấy</span>
        <span class="text-green-600 font-extrabold text-base">
            {{ $case->found_at ? \Carbon\Carbon::parse($case->found_at)->format('d/m/Y') : '—' }}
        </span>
    </div>

</div>