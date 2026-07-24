@props([
    'rows' => 4,
    'compact' => false,
])

<div
    {{ $attributes->merge([
        'class' => $compact ? 'divide-y divide-slate-100' : 'space-y-2',
        'role' => 'status',
        'aria-label' => 'Đang tải thông báo',
    ]) }}
>
    @foreach (range(1, max(1, (int) $rows)) as $row)
        <div class="flex gap-3 {{ $compact ? 'px-4 py-3.5' : 'rounded-2xl border border-slate-100 bg-white p-4' }}">
            <x-ui.skeleton
                width="{{ $compact ? '40px' : '36px' }}"
                height="{{ $compact ? '40px' : '36px' }}"
                rounded="rounded-full"
                class="shrink-0"
            />
            <div class="min-w-0 flex-1 space-y-2">
                <x-ui.skeleton height="12px" width="{{ $row % 2 === 0 ? '58%' : '72%' }}" />
                <x-ui.skeleton height="10px" width="94%" />
                <x-ui.skeleton height="10px" width="{{ $row % 2 === 0 ? '68%' : '82%' }}" />
                <x-ui.skeleton height="8px" width="24%" />
            </div>
        </div>
    @endforeach

    <span class="sr-only">Đang tải danh sách thông báo.</span>
</div>
