@props([
    'width' => '100%',
    'height' => '1rem',
    'rounded' => 'rounded-xl',
])

<div
    {{ $attributes->merge([
        'class' => 'skeleton-shimmer ' . $rounded,
        'aria-hidden' => 'true',
    ]) }}
    style="width: {{ $width }}; height: {{ $height }};"
></div>
