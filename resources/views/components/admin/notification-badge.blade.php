@php
    // Badge thông báo topbar admin — TĨNH, KHÔNG JS/Alpine.
    // QUAN TRỌNG: panel admin dùng Filament app.css đã compile sẵn, KHÔNG chứa các
    // utility Tailwind của client (h-9, w-9, text-gray-500, rounded-full, bg-danger...).
    // Nên style trực tiếp bằng <style> tự chứa trong component, không phụ thuộc vào
    // build CSS của Filament (tránh badge bị "vô hình" do thiếu class).
    // Màu danger dùng đúng hex định nghĩa ở AdminPanelProvider: Color::hex('#dc2626').
    $count = auth()->check()
        ? (int) auth()->user()->notificationRecipients()->whereNull('read_at')->count()
        : 0;
@endphp

<style>
.admin-notif-bell{display:inline-flex;align-items:center;justify-content:center;position:relative;width:36px;height:36px;border-radius:9999px;color:#6b7280;text-decoration:none;transition:background-color .15s ease,color .15s ease;}
.admin-notif-bell:hover{background-color:rgba(107,114,128,.12);color:#374151;}
.admin-notif-bell__icon{width:20px;height:20px;}
.admin-notif-bell__count{position:absolute;top:-3px;right:-3px;display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;padding:0 4px;border-radius:9999px;background-color:#dc2626;color:#fff;font-size:11px;font-weight:700;line-height:1;box-shadow:0 0 0 2px #fff;}
</style>

<a href="{{ route('notifications.center') }}"
   class="admin-notif-bell"
   data-admin-notification-badge
   title="{{ $count > 0 ? $count . ' thông báo chưa đọc' : 'Không có thông báo mới' }}"
   aria-label="{{ $count > 0 ? $count . ' thông báo chưa đọc' : 'Không có thông báo mới' }}">
    <svg class="admin-notif-bell__icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
    </svg>

    @if ($count > 0)
        <span class="admin-notif-bell__count" data-admin-notification-count>{{ $count }}</span>
    @endif
</a>
