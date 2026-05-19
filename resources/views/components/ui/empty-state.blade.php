{{--
┌──────────────────────────────────────────────────────────┐
│ <x-ui.empty-state> │
    │ Props: │
    │ $icon — emoji or SVG (default: '🔍') │
    │ $title — heading text │
    │ $message — body text │
    │ $action — array ['label'=>'', 'href'=>''] optional │
    └──────────────────────────────────────────────────────────┘
    --}}

    @props([
        'icon' => '🔍',
        'title' => 'Không tìm thấy kết quả',
        'message' => 'Thử thay đổi từ khoá tìm kiếm hoặc điều chỉnh bộ lọc.',
        'action' => null,
    ])

    <div class="flex flex-col items-center justify-center py-20 px-4 text-center space-y-5">
        <div class="w-20 h-20 rounded-3xl bg-slate-100 flex items-center justify-center text-4xl shadow-sm">
            {{ $icon }}
        </div>

        <div class="space-y-2 max-w-sm">
        <h3 class="text-lg font-extrabold text-slate-900 tracking-tight">{{ $title }}</h3>
        <p class="text-sm text-slate-400 font-medium leading-relaxed">{{ $message }}</p>
    </div>

    @if($action)
            <a href="{{ $action['href'] }}"
           class="inline-flex items-center gap-2 bg-primary text-white font-extrabold text-xs px-6 py-3 rounded-xl
                      hover:bg-primary-dark transition-all shadow-md shadow-primary/10 uppercase tracking-widest">
                {{ $action['label'] }}
        </a>
    @endif

    {{ $slot }}
</div>
