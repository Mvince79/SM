{{--
┌──────────────────────────────────────────────────────────┐
│ <x-ui.tag> │
    │ Props: │
    │ $variant — 'urgent' | 'found' | 'brand' | 'default' │
    └──────────────────────────────────────────────────────────┘
    --}}

    @props([
        'variant' => 'default',
    ])

@php
    $classes = match ($variant) {
        'urgent' => 'badge badge-urgent',
        'found' => 'badge badge-found',
        'brand' => 'badge badge-brand',
        default => 'badge badge-default',
    };
@endphp
    
    <span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
