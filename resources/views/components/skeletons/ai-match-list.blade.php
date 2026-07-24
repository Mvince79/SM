@props(['rows' => 2])

<div
    {{ $attributes->merge([
        'class' => 'space-y-3 rounded-2xl border border-brand/20 bg-brand/5 p-4',
        'role' => 'status',
        'aria-label' => 'Đang tải kết quả AI match',
    ]) }}
>
    <div class="flex items-center gap-3">
        <x-ui.skeleton width="40px" height="40px" rounded="rounded-xl" class="shrink-0" />
        <div class="min-w-0 flex-1 space-y-2">
            <x-ui.skeleton height="14px" width="48%" />
            <x-ui.skeleton height="10px" width="34%" />
        </div>
    </div>

    @foreach (range(1, max(1, (int) $rows)) as $row)
        <div class="flex items-center gap-3 rounded-xl border border-slate-100 bg-white p-3">
            <x-ui.skeleton width="56px" height="56px" rounded="rounded-lg" class="shrink-0" />
            <div class="min-w-0 flex-1 space-y-2">
                <x-ui.skeleton height="11px" width="{{ $row % 2 === 0 ? '38%' : '46%' }}" />
                <x-ui.skeleton height="10px" width="{{ $row % 2 === 0 ? '68%' : '58%' }}" />
                <x-ui.skeleton height="9px" width="42%" />
            </div>
            <x-ui.skeleton width="58px" height="10px" class="shrink-0" />
        </div>
    @endforeach

    <span class="sr-only">Hệ thống đang phân tích các hồ sơ tương đồng.</span>
</div>
