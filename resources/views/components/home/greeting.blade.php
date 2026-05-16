@props(['user'])

@php
    $hour = now()->setTimezone('Asia/Ho_Chi_Minh')->hour;
    $greet = match (true) {
        $hour < 12 => 'Chào buổi sáng ☀️',
        $hour < 18 => 'Chào buổi chiều 🌤️',
        default => 'Chào buổi tối 🌙',
    };
    $openCases = 0; // Sprint 1: truyền từ controller
    $foundCases = 0;
@endphp

<div class="greeting">
    <div class="greeting-eyebrow">{{ $greet }}</div>
    <div class="greeting-name">{{ $user->full_name ?? 'Bạn' }} 👋</div>
    <div class="greeting-stats">
        <div class="stat-box">
            <div class="stat-num">{{ $openCases }}</div>
            <div class="stat-label">Ca đang tìm kiếm</div>
        </div>
        <div class="stat-box">
            <div class="stat-num">{{ $foundCases }}</div>
            <div class="stat-label">Đã tìm thấy</div>
        </div>
    </div>
</div>