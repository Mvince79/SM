<?php

use App\Models\Province;
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

    /**
     * ID tỉnh được chọn (null = tất cả). Gửi lên backend MapDataController (province_id).
     */
    public ?string $provinceId = null;

    public bool $hasNewClue = false;

    public bool $otherFiltersOpen = false;

    /**
     * Danh sách tỉnh để chọn lọc — lấy từ DB, sắp xếp theo tên.
     */
    public function getProvincesProperty()
    {
        return Province::query()->orderBy('name')->get(['id', 'name']);
    }

    public function updatedTimeRange(): void
    {
        $this->emitFilters();
    }

    public function updatedStatus(): void
    {
        $this->emitFilters();
    }

    public function updatedProvinceId(): void
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

    public function setStatus(string $value): void
    {
        $this->status = $value;
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
        $this->provinceId = null;
        $this->hasNewClue = false;
        $this->emitFilters();
    }

    /**
     * Bắn browser event để JS Leaflet lắng nghe và gọi /api/v1/map-data.
     */
    protected function emitFilters(): void
    {
        $this->dispatch('map-filters-changed', filters: [
            'time_range' => $this->timeRange,
            'type' => $this->status,
            'province_id' => $this->provinceId ? (int) $this->provinceId : null,
            'has_new_clue' => $this->hasNewClue ? 1 : 0,
        ]);
    }
};
?>

<div class="space-y-3" wire:ignore.self>

    {{-- Thời gian mất tích — pill dễ bấm, responsive --}}
    <div>
        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Thời gian mất tích</div>
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
    </div>

    {{-- Tỉnh thành — select thực sự lọc được (gửi province_id) --}}
    <div>
        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Tỉnh thành</label>
        <div class="relative mt-1">
            <x-heroicon-o-map-pin class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" />
            <select
                wire:model.live="provinceId"
                class="w-full pl-8 pr-3 py-2 border border-slate-200/70 rounded-xl text-xs focus:outline-none focus:ring-1 focus:ring-primary focus:border-transparent bg-white/70 appearance-none"
            >
                <option value="">Tất cả tỉnh thành</option>
                @foreach ($this->provinces as $province)
                    <option value="{{ $province->id }}">{{ $province->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Trạng thái — segmented control --}}
    <div>
        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Trạng thái</div>
        <div class="grid grid-cols-3 gap-1.5">
            @foreach ([
                'all' => ['label' => 'Tất cả', 'icon' => 'heroicon-o-squares-2x2'],
                'missing' => ['label' => 'Đang tìm', 'icon' => 'heroicon-o-magnifying-glass'],
                'found' => ['label' => 'Tìm thấy', 'icon' => 'heroicon-o-check-circle'],
            ] as $value => $opt)
                <button
                    type="button"
                    wire:click="setStatus('{{ $value }}')"
                    class="flex items-center justify-center gap-1 min-h-10 px-2 py-2 rounded-xl text-xs font-black transition-colors border
                        {{ $status === $value
                            ? 'bg-primary text-white border-primary shadow-sm'
                            : 'bg-white/70 text-slate-600 border-slate-200/70 hover:bg-white' }}"
                >
                    <x-dynamic-component :component="$opt['icon']" class="w-3.5 h-3.5" />
                    {{ $opt['label'] }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Có manh mối GPS mới nhất --}}
    <label class="flex items-center gap-2 cursor-pointer p-1.5 hover:bg-white/40 rounded-lg transition-colors">
        <input
            type="checkbox"
            wire:model.live="hasNewClue"
            class="accent-primary w-4 h-4"
        />
        <x-heroicon-o-map-pin class="w-3.5 h-3.5 text-slate-500" />
        <span class="font-bold text-slate-700 text-xs">Có manh mối GPS mới nhất</span>
    </label>

    {{-- Tóm tắt bộ lọc đang bật + nút xóa --}}
    @if ($status !== 'all' || $provinceId || $hasNewClue || $timeRange !== 'all')
        <div class="flex items-center justify-between pt-1 border-t border-slate-200/40">
            <span class="flex items-center gap-1.5 text-[11px] font-bold text-primary">
                <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                Đang lọc
            </span>
            <button
                type="button"
                wire:click="resetFilters"
                class="text-[11px] font-black text-slate-400 hover:text-slate-600"
            >
                Xóa bộ lọc
            </button>
        </div>
    @endif

</div>
