<div
    {{ $attributes->merge([
        'class' => 'relative h-full w-full overflow-hidden bg-slate-100',
        'role' => 'status',
        'aria-label' => 'Đang tải bản đồ',
    ]) }}
>
    <div class="absolute inset-0 grid grid-cols-4 grid-rows-4 gap-1 p-1">
        @foreach (range(1, 16) as $tile)
            <x-ui.skeleton
                width="100%"
                height="100%"
                rounded="rounded-md"
                class="{{ $tile % 3 === 0 ? 'opacity-70' : 'opacity-100' }}"
            />
        @endforeach
    </div>

    <div class="absolute inset-0 flex items-center justify-center bg-white/25 px-4">
        <div class="w-full max-w-xs rounded-2xl border border-white/90 bg-white/90 p-4 shadow-xl backdrop-blur">
            <div class="flex items-center gap-3">
                <x-ui.skeleton width="42px" height="42px" rounded="rounded-xl" class="shrink-0" />
                <div class="min-w-0 flex-1 space-y-2">
                    <x-ui.skeleton height="14px" width="62%" />
                    <x-ui.skeleton height="10px" width="84%" />
                </div>
            </div>
        </div>
    </div>

    <span class="sr-only">Đang tải dữ liệu và các lớp bản đồ.</span>
</div>
