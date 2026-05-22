<?php

use Livewire\Component;
use Livewire\Attributes\Url;
use App\Models\Province;

new class extends Component
{
    #[Url(as: 'q')]        public string $q        = '';
    #[Url(as: 'province')] public string $province = '';
    #[Url(as: 'gender')]   public string $gender   = '';
    #[Url(as: 'age')]      public string $age      = '';
    #[Url(as: 'status')]   public string $status   = '';

    public $provinces;

    public function mount(): void
    {
        $this->provinces = Province::where('is_active', 1)->orderBy('name')->get();
    }

    public function updated(): void
    {
        $this->dispatch('filters-updated', filters: $this->getFilters());
    }

    public function removeFilter(string $key): void
    {
        $this->$key = '';
        $this->dispatch('filters-updated', filters: $this->getFilters());
    }

    public function clearAll(): void
    {
        $this->q = $this->province = $this->gender = $this->age = $this->status = '';
        $this->dispatch('filters-updated', filters: $this->getFilters());
    }

    public function getActivePillsProperty(): array
    {
        $pills = [];
        if ($this->q)
            $pills[] = ['key' => 'q', 'label' => "🔍 \"{$this->q}\""];
        if ($this->province) {
            $name = $this->provinces->firstWhere('id', $this->province)?->name;
            if ($name) $pills[] = ['key' => 'province', 'label' => "📍 {$name}"];
        }
        if ($this->gender)
            $pills[] = ['key' => 'gender', 'label' => $this->gender === 'MALE' ? '👦 Nam' : '👧 Nữ'];
        if ($this->age)
            $pills[] = ['key' => 'age', 'label' => "🎂 {$this->age} tuổi"];
        if ($this->status)
            $pills[] = ['key' => 'status', 'label' => $this->status === 'APPROVED' ? '🔴 Đang tìm kiếm' : '✓ Đã tìm thấy'];
        return $pills;
    }

    public function getFilters(): array
    {
        return [
            'q'        => $this->q,
            'province' => $this->province,
            'gender'   => $this->gender,
            'age'      => $this->age,
            'status'   => $this->status,
        ];
    }
};
?>

<div class="bg-white border-b border-slate-150 py-4 px-4 sticky top-16 z-40 shadow-sm">
    <div class="max-w-7xl mx-auto space-y-4">
        <div class="flex flex-col lg:flex-row gap-3">

            {{-- Search Input --}}
            <div class="relative flex-1">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text"
                       wire:model.live.debounce.400ms="q"
                       placeholder="Tìm theo tên con, đặc điểm đặc biệt, nơi mất tích..."
                       class="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent bg-slate-50 focus:bg-white transition-all font-medium"/>
            </div>

            {{-- Filters grid --}}
            <div class="flex gap-2 flex-wrap items-center">
                <select wire:model.live="province"
                    class="border border-slate-200 rounded-2xl px-4 py-3 text-xs bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary font-bold text-slate-600 transition-colors">
                    <option value="">Tất cả tỉnh thành</option>
                    @foreach($provinces as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="gender"
                    class="border border-slate-200 rounded-2xl px-4 py-3 text-xs bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary font-bold text-slate-600 transition-colors">
                    <option value="">Giới tính</option>
                    <option value="MALE">Nam</option>
                    <option value="FEMALE">Nữ</option>
                </select>

                <select wire:model.live="age"
                    class="border border-slate-200 rounded-2xl px-4 py-3 text-xs bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary font-bold text-slate-600 transition-colors">
                    <option value="">Độ tuổi</option>
                    <option value="0-5">Dưới 5 tuổi</option>
                    <option value="5-10">5–10 tuổi</option>
                    <option value="10-15">10–15 tuổi</option>
                    <option value="15+">Trên 15 tuổi</option>
                </select>

                <select wire:model.live="status"
                    class="border border-slate-200 rounded-2xl px-4 py-3 text-xs bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary font-bold text-slate-600 transition-colors">
                    <option value="">Trạng thái</option>
                    <option value="APPROVED">Đang tìm kiếm</option>
                    <option value="FOUND">Đã tìm thấy ✓</option>
                </select>
            </div>
        </div>

        {{-- Active Filter Pills --}}
        @if(count($this->activePills) > 0)
            <div class="flex items-center gap-2 flex-wrap text-xs pt-1">
                <span class="text-slate-400 font-bold">Lọc theo:</span>

                @foreach($this->activePills as $pill)
                    <span class="inline-flex items-center gap-1.5 bg-primary-light text-primary font-bold px-3 py-1.5 rounded-full border border-primary/20 shadow-sm">
                        {{ $pill['label'] }}
                        <button wire:click="removeFilter('{{ $pill['key'] }}')"
                                class="ml-1 text-slate-400 hover:text-primary transition-colors text-sm font-black">×</button>
                    </span>
                @endforeach

                <button wire:click="clearAll"
                        class="inline-flex items-center gap-1 text-slate-400 hover:text-primary font-bold transition-colors px-2 py-1.5">
                    Xóa tất cả ×
                </button>
            </div>
        @endif

    </div>
</div>