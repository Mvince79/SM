<?php

use Livewire\Component;
use Livewire\Attributes\Url;
use App\Models\Province;

new class extends Component {
    #[Url(as: 'q')] public string $q = '';
    #[Url(as: 'province')] public string $province = '';
    #[Url(as: 'gender')] public string $gender = '';
    #[Url(as: 'age')] public string $age = '';
    #[Url(as: 'status')] public string $status = '';

    public $provinces;

    public function mount(): void
    {
        $this->provinces = Province::where('is_active', 1)->orderBy('name')->get();
        $this->dispatch('filters-updated', filters: $this->getFilters());
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
            $pills[] = ['key' => 'q', 'type' => 'search', 'label' => "\"{$this->q}\""];
        if ($this->province) {
            $name = $this->provinces->firstWhere('id', $this->province)?->name;
            if ($name)
                $pills[] = ['key' => 'province', 'type' => 'location', 'label' => $name];
        }
        if ($this->gender)
            $pills[] = ['key' => 'gender', 'type' => 'gender', 'label' => $this->gender === 'MALE' ? 'Nam' : 'Nữ'];
        if ($this->age)
            $pills[] = ['key' => 'age', 'type' => 'age', 'label' => "Tuổi: {$this->age}"];
        if ($this->status)
            $pills[] = ['key' => 'status', 'type' => 'status', 'label' => $this->status === 'APPROVED' ? 'Đang tìm kiếm' : 'Đã tìm thấy'];

        return $pills;
    }

    public function getFilters(): array
    {
        return [
            'q' => $this->q,
            'province' => $this->province,
            'gender' => $this->gender,
            'age' => $this->age,
            'status' => $this->status,
        ];
    }
};
?>

<div class="bg-white border-b border-slate-150 py-4 px-4 sticky top-16 z-40 shadow-sm">
    <div class="max-w-7xl mx-auto space-y-4">
        <div class="flex flex-col lg:flex-row gap-3">

            {{-- Search Input --}}
            <div class="relative flex-1">
                <x-heroicon-o-magnifying-glass
                    class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                <input type="text" wire:model.live.debounce.400ms="q"
                    placeholder="Tìm theo tên con, đặc điểm đặc biệt, nơi mất tích..."
                    class="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent bg-slate-50 focus:bg-white transition-all font-medium" />
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
                    <option value="FOUND">Đã tìm thấy</option>
                </select>
            </div>
        </div>

        {{-- Active Filter Pills --}}
        @if(count($this->activePills) > 0)
            <div class="flex items-center gap-2 flex-wrap text-xs pt-1">
                <span class="text-slate-400 font-bold">Lọc theo:</span>

               @foreach($this->activePills as $pill)
                    <span class="inline-flex items-center gap-1.5 bg-primary-light text-primary font-bold px-3 py-1.5 rounded-full border border-primary/20 shadow-sm whitespace-nowrap">
                        @if($pill['type'] === 'search')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.602 10.602Z" /></svg>
                        @elseif($pill['type'] === 'location')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
                        @elseif($pill['type'] === 'gender')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                        @elseif($pill['type'] === 'age')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v1.5m0 3v3.75m-9.303-3.376C1.83 12.302 2.2 10.25 3.5 8.96l4.5-4.5A4.5 4.5 0 0 1 11.182 3h1.636a4.5 4.5 0 0 1 3.182 1.318l4.5 4.5c1.3 1.29 1.67 3.342.303 4.164a10.454 10.454 0 0 1-17.303 0Z" /></svg>
                        @elseif($pill['type'] === 'status')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        @endif
                        
                        {{ $pill['label'] }}
                        
                        <button wire:click=\"removeFilter('{{ $pill['key'] }}')\"
                                class=\"ml-1 text-slate-400 hover:text-primary transition-colors text-sm font-black\">&times;</button>
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