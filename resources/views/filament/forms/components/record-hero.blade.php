@php
    // Hero đầu trang edit: ảnh lớn phía trên + tên + trạng thái.
    // $record được Filament truyền tự động vào View component (getExtraViewData).
    $photo = null;
    $name = 'Hồ sơ mới';
    $status = null;

    if ($record ?? null) {
        $photo = $record->detail_primary_photo_url
            ?? $record->primary_photo_url
            ?? null;
        $name = $record->full_name ?: '—';
        $status = $record->status ?? null;
    }

    $statusMap = [
        'PENDING'   => ['label' => 'Chờ duyệt',        'cls' => 'bg-amber-100 text-amber-700'],
        'APPROVED'  => ['label' => 'Đã duyệt',         'cls' => 'bg-emerald-100 text-emerald-700'],
        'MATCHED'   => ['label' => 'Đã khớp',          'cls' => 'bg-emerald-100 text-emerald-700'],
        'FOUND'     => ['label' => 'Đã tìm thấy',       'cls' => 'bg-sky-100 text-sky-700'],
        'REJECTED'  => ['label' => 'Bị từ chối',        'cls' => 'bg-rose-100 text-rose-700'],
        'ACTIVE'    => ['label' => 'Đang hoạt động',    'cls' => 'bg-sky-100 text-sky-700'],
        'RESOLVED'  => ['label' => 'Đã xử lý',          'cls' => 'bg-slate-200 text-slate-600'],
        'CLOSED'    => ['label' => 'Đã đóng',           'cls' => 'bg-slate-200 text-slate-600'],
    ];
    $badge = $statusMap[$status] ?? ['label' => '—', 'cls' => 'bg-slate-200 text-slate-600'];
@endphp

<div class="sm-record-hero relative w-full select-none overflow-hidden rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700"
     style="height:240px;">

    @if ($photo)
        <img src="{{ $photo }}" alt="{{ e($name) }}"
             class="absolute inset-0 h-full w-full object-cover" loading="lazy" />
        <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/15 to-transparent"></div>
    @else
        <div class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-slate-200 to-slate-300 dark:from-slate-700 dark:to-slate-800">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                 stroke="currentColor" class="h-16 w-16 text-slate-400">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0v.375c0 .621-.504 1.125-1.125 1.125H5.625a1.125 1.125 0 0 1-1.125-1.125v-.375Z" />
            </svg>
        </div>
    @endif

    <div class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-3 p-4">
        <div class="min-w-0">
            <h2 class="truncate text-lg font-bold leading-tight text-white drop-shadow-sm">{{ e($name) }}</h2>
            @if ($status)
                <span class="mt-1.5 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold shadow-sm {{ $badge['cls'] }}">
                    {{ $badge['label'] }}
                </span>
            @endif
        </div>
    </div>
</div>
