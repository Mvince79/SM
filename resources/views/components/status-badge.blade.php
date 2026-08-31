@props([
    'status' => null,
    'class' => '',
])

@php
    // U-03 — Badge trạng thái dùng đúng 3 token thiết kế (.badge-brand / .badge-found / .badge-urgent)
    // thông qua component <x-ui.tag>. Không tự định nghĩa class màu mới.
    [$variant, $label] = match ($status) {
        'PENDING'            => ['default', 'Chờ duyệt'],
        'APPROVED'           => ['brand', 'Đã duyệt'],
        'FOUND'              => ['found', 'Nghi vấn khớp mạnh'],
        'MATCHED'            => ['found', 'Đã khớp'],
        'HANDED_OVER'        => ['brand', 'Nghi vấn khớp mạnh — đang xác minh'],
        'REUNITED'           => ['found', 'Đã đoàn tụ'],
        'UNREACHABLE_FAMILY' => ['urgent', 'Không liên lạc được'],
        'REJECTED'           => ['urgent', 'Từ chối'],
        'CLOSED'             => ['default', 'Đã đóng'],
        'ARCHIVED'           => ['default', 'Lưu trữ'],
        default              => ['default', (string) $status],
    };
@endphp

<x-ui.tag :variant="$variant" class="{{ $class }}">
    {{ $label }}
</x-ui.tag>
