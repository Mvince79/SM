@php
    // Badge đơn giản, TĨNH, KHÔNG JS/Alpine — dành riêng cho panel admin (Filament).
    // Panel admin không load notifications.js của trang client nên không dùng được
    // dropdown chuông đầy đủ; ở đây chỉ hiện số thông báo CHƯA ĐỌC và link tới
    // trang thông báo chung (route('notifications.center')).
    $count = auth()->check()
        ? (int) auth()->user()->notificationRecipients()->whereNull('read_at')->count()
        : 0;
@endphp

@if ($count > 0)
    <a href="{{ route('notifications.center') }}"
       data-admin-notification-badge
       class="inline-flex items-center gap-1.5 rounded-full bg-danger px-2.5 py-1 text-xs font-bold text-white transition hover:opacity-90"
       title="{{ $count }} thông báo chưa đọc">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>
        <span data-admin-notification-count>{{ $count }}</span>
    </a>
@endif
