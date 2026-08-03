<?php

use Livewire\Component;

new class extends Component
{
    /**
     * 24h | 7d | 30d | all
     */
    public string $timeRange = 'all';

    /**
     * all | missing | found
     */
    public string $status = 'all';

    public bool $hasNewClue = false;

    public bool $otherFiltersOpen = false;

    public function updatedTimeRange(): void
    {
        $this->emitFilters();
    }

    public function updatedStatus(): void
    {
        $this->emitFilters();
    }

    public function updatedHasNewClue(): void
    {
        $this->emitFilters();
    }

    public function setTimeRange(string $value): void
    {
        $this->timeRange = $value;
        $this->emitFilters();
    }

    public function toggleOtherFilters(): void
    {
        $this->otherFiltersOpen = ! $this->otherFiltersOpen;
    }

    public function resetFilters(): void
    {
        $this->timeRange = 'all';
        $this->status = 'all';
        $this->hasNewClue = false;
        $this->emitFilters();
    }

    /**
     * Bắn browser event ra ngoài để JS thuần (Leaflet) lắng nghe và
     * gọi lại /api/v1/map-data với query params tương ứng.
     */
    protected function emitFilters(): void
    {
        $this->dispatch('map-filters-changed', filters: [
            'time_range' => $this->timeRange,
            'type' => $this->status,
            'has_new_clue' => $this->hasNewClue ? 1 : 0,
        ]);
    }
};
?>

<div class="space-y-2" wire:ignore.self>

    {{-- Pill lớn: Thời gian mất tích — dễ bấm nhất trên mobile.
                 < 640px: grid 4 cột đều nhau (grid-cols-4) để 4 nút luôn cùng
                 kích thước dù nhãn dài ngắn khác nhau ("Tất cả" ngắn hơn hẳn
                 "24h qua" nếu để tự co theo nội dung sẽ lệch, nhìn lộn xộn).
                 Từ 640px (sm) trở lên quay lại hàng ngang cuộn ngang như cũ,
                 vì lúc đó đã đủ chỗ để không cần ép bằng nhau. --}}
    <div class="grid grid-cols-2 min-[360px]:grid-cols-4 gap-1.5 sm:flex sm:gap-2 sm:overflow-x-auto no-scrollbar pb-1">
        @foreach ([
            'all' => 'Tất cả',
            '24h' => '24h qua',
            '7d' => '7 ngày',
            '30d' => '30 ngày',
        ] as $value => $label)
            <button
                type="button"
                wire:click="setTimeRange('{{ $value }}')"
                class="min-h-10 sm:shrink-0 px-2 sm:px-4 py-2 rounded-full text-xs font-black whitespace-nowrap transition-colors text-center
                    {{ $timeRange === $value
                        ? 'bg-primary text-white shadow'
                        : 'bg-white/70 text-slate-600 border border-slate-200/70 hover:bg-white' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Nút mở dropdown Bộ lọc khác — tách hàng riêng bên dưới trên mobile
                 (thay vì chen vào cùng hàng grid-cols-4, sẽ làm 5 phần tử lệch cột) --}}
    <button
        type="button"
        wire:click="toggleOtherFilters"
        class="min-h-10 w-full sm:w-auto flex items-center justify-center sm:justify-start gap-1 px-3 py-2 rounded-full text-xs font-black whitespace-nowrap border border-dashed border-slate-300 text-slate-500 hover:bg-white/60 transition-colors"
    >
        <x-heroicon-o-adjustments-horizontal class="w-3.5 h-3.5" />
        Bộ lọc khác
        @if ($status !== 'all' || $hasNewClue)
            <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
        @endif
    </button>

    {{-- Dropdown: Trạng thái + Có manh mối GPS mới nhất --}}
    @if ($otherFiltersOpen)
        <div class="glass-premium p-3 rounded-2xl border border-slate-200/50 space-y-3 text-xs">

            <div class="space-y-1.5">
                <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Trạng thái</div>

                @foreach ([
                    'all' => ['label' => 'Tất cả', 'icon' => 'heroicon-o-squares-2x2'],
                    'missing' => ['label' => 'Đang tìm', 'icon' => 'heroicon-o-magnifying-glass'],
                    'found' => ['label' => 'Đã tìm thấy', 'icon' => 'heroicon-o-check-circle'],
                ] as $value => $opt)
                    <label class="flex items-center gap-2 cursor-pointer p-1.5 hover:bg-white/40 rounded-lg transition-colors">
                        <input
                            type="radio"
                            wire:model.live="status"
                            value="{{ $value }}"
                            class="accent-primary"
                        />
                        <x-dynamic-component :component="$opt['icon']" class="w-3.5 h-3.5 text-slate-500" />
                        <span class="font-bold text-slate-700">{{ $opt['label'] }}</span>
                    </label>
                @endforeach
            </div>

            <div class="border-t border-slate-200/40 pt-2">
                <label class="flex items-center gap-2 cursor-pointer p-1.5 hover:bg-white/40 rounded-lg transition-colors">
                    <input
                        type="checkbox"
                        wire:model.live="hasNewClue"
                        class="accent-primary"
                    />
                    <x-heroicon-o-map-pin class="w-3.5 h-3.5 text-slate-500" />
                    <span class="font-bold text-slate-700">Có manh mối GPS mới nhất</span>
                </label>
            </div>

            <button
                type="button"
                wire:click="resetFilters"
                class="w-full text-center text-[11px] font-black text-slate-400 hover:text-slate-600 pt-1"
            >
                Xóa bộ lọc
            </button>
        </div>
    @endif

</div>
