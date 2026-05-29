{{--
┌──────────────────────────────────────────────────────────────┐
│  <x-ui.empty-state>                                          │
│  Props:                                                      │
│    $icon    — emoji (default: '🔍')                          │
│    $title   — tiêu đề                                        │
│    $message / $desc — mô tả phụ (alias nhau)                 │
│    $action  — array ['label'=>'', 'href'=>'']  (cũ)          │
│    $cta     — label nút CTA  (mới, ưu tiên hơn action)       │
│    $ctaUrl  — href nút CTA   (mới)                           │
└──────────────────────────────────────────────────────────────┘
--}}

@props([
    'icon'    => '🔍',
    'title'   => 'Không tìm thấy kết quả',
    'message' => null,
    'desc'    => null,
    'action'  => null,
    'cta'     => null,
    'ctaUrl'  => null,
])

@php
    // desc và message là alias — ưu tiên desc nếu truyền cả hai
    $bodyText = $desc ?? $message ?? 'Thử thay đổi từ khoá tìm kiếm hoặc điều chỉnh bộ lọc.';

    // cta/ctaUrl ưu tiên hơn action array (giữ tương thích ngược)
    $ctaLabel = $cta ?? ($action['label'] ?? null);
    $ctaHref  = $ctaUrl ?? ($action['href'] ?? null);
@endphp

<div class="flex flex-col items-center justify-center py-16 px-4 text-center space-y-5">
    <div class="w-20 h-20 rounded-3xl bg-slate-100 flex items-center justify-center text-4xl shadow-sm">
        {{ $icon }}
    </div>

    <div class="space-y-2 max-w-sm">
        <h3 class="text-base font-extrabold text-slate-900 tracking-tight">{{ $title }}</h3>
        <p class="text-sm text-slate-400 font-medium leading-relaxed">{{ $bodyText }}</p>
    </div>

    @if($ctaLabel && $ctaHref)
        <a href="{{ $ctaHref }}"
           class="inline-flex items-center gap-2 bg-primary text-white font-extrabold text-xs
                  px-6 py-3 rounded-xl hover:bg-primary-dark transition-all
                  shadow-md shadow-primary/10 uppercase tracking-widest">
            {{ $ctaLabel }}
        </a>
    @endif

    {{ $slot }}
</div>