<?php

use Livewire\Component;
use App\Models\MissingChild;

new class extends Component {
    public $cases;

    public function mount(): void
    {
        $this->cases = MissingChild::where('status', 'APPROVED')
            ->with(['primaryPhoto', 'province'])
            ->latest('approved_at')
            ->limit(6)
            ->get();
    }
};
?>

<section class="py-16 px-4 max-w-7xl mx-auto">
    <div class="section-header">
        <div>
            <h2 class="section-title">🚨 Con đang cần tìm gấp</h2>
            <p class="section-subtitle">Các hồ sơ được duyệt gần nhất cần cộng đồng hỗ trợ</p>
        </div>
        <a href="{{ route('missing.index') }}" class="section-link">
            Xem tất cả hồ sơ <span>→</span>
        </a>
    </div>

    @if($cases->isEmpty())
        <div class="text-center py-16 text-slate-400">
            <div class="text-5xl mb-4">🔍</div>
            <p class="font-semibold">Chưa có hồ sơ nào được duyệt.</p>
        </div>
    @else
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
            @foreach($cases as $case)
                <div class="case-card">
                    <div class="case-card-img">
                        <img src="{{ $case->thumb_url }}" alt="Ảnh {{ $case->full_name }}" loading="lazy"
                            onerror="this.src='https://api.dicebear.com/7.x/thumbs/svg?seed={{ urlencode($case->full_name) }}&backgroundColor=ffdfbf'" />
                        <span
                            class="absolute top-3 left-3 bg-primary text-white text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-xl flex items-center gap-1">
                            <span class="w-1.5 h-1.5 bg-white rounded-full animate-ping"></span>
                            Đang tìm gấp
                        </span>
                        <span
                            class="absolute top-3 right-3 bg-black/60 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded-lg">
                            {{ $case->approved_at?->diffForHumans() ?? $case->created_at->diffForHumans() }}
                        </span>
                    </div>
                    <div class="case-card-body">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <h3 class="case-card-name">{{ $case->full_name }}</h3>
                                @if($case->age)
                                    <span class="text-xs font-extrabold text-slate-400">{{ $case->age }} tuổi</span>
                                @endif
                            </div>
                            <div class="case-card-location">
                                <span>📍</span>
                                {{ collect([$case->district, $case->province?->name])->filter()->implode(', ') }}
                            </div>
                            @if($case->clothes_desc || $case->special_marks)
                                <p class="case-card-desc">
                                    {{ Str::limit($case->clothes_desc ?? $case->special_marks, 120) }}
                                </p>
                            @endif
                        </div>
                        <div class="case-card-footer">
                            <span class="badge badge-default">{{ $case->case_code }}</span>
                            <a href="{{ route('missing.by_slug', ['slug' => $case->slug]) }}"
                                class="text-xs font-extrabold text-primary hover:underline flex items-center gap-1">
                                Xem hồ sơ <span>→</span>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>