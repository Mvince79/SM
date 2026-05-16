@props([
    'message' => 'Không có dữ liệu.',
    'hint'    => '',
])

<div class="empty-state">
    <svg width="44" height="44" viewBox="0 0 24 24" fill="none"
         stroke="#d1d5db" stroke-width="1.5" stroke-linecap="round"
         class="mx-auto">
        <circle cx="11" cy="11" r="7"/>
        <path d="m21 21-4.35-4.35"/>
    </svg>
    <p>{{ $message }}</p>
    @if($hint)
        <p class="empty-state__hint">{!! $hint !!}</p>
    @endif
</div>
