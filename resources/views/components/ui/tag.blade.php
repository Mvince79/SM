@props(['type' => 'searching'])

@php
    $class = match ($type) {
        'urgent' => 'tag tag--urgent',
        'found' => 'tag tag--found',
        default => 'tag tag--searching',
    };
    $label = match ($type) {
        'urgent' => 'Khẩn cấp',
        'found' => 'Đã tìm thấy',
        default => 'Đang tìm',
    };
@endphp

<span {{ $attributes->merge(['class' => $class]) }}>
    {{ $slot->isEmpty() ? $label : $slot }}
</span>