<?php

use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use App\Repositories\MissingChildRepository;

new class extends Component
{
    use WithPagination;

    #[Url] public string $sort = 'latest';

    public array $filters = [
        'q' => '', 'province' => '', 'gender' => '', 'age' => '', 'status' => '',
    ];

    #[On('filters-updated')]
    public function updateFilters(array $filters): void
    {
        $this->filters = $filters;
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $repo  = new MissingChildRepository();
        $cases = $repo->searchPublic($this->filters, 15, $this->sort);

        return view('components.missing.cases-grid', compact('cases'));
    }
};
?>

<div>
    {{-- Sort + summary bar --}}
    <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
        <p class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">
            {{ $cases->total() }} kết quả
            @if($filters['q'])
                — "<span class="text-primary">{{ $filters['q'] }}</span>"
            @endif
        </p>

        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-500">Sắp xếp:</span>
            <select wire:model.live="sort"
                class="border border-slate-200 rounded-xl px-3 py-1.5 text-xs bg-white
                       focus:outline-none focus:ring-1 focus:ring-primary font-bold text-slate-600 shadow-sm">
                <option value="latest">Mới nhất</option>
                <option value="urgent">Khẩn cấp nhất</option>
                <option value="oldest">Cũ nhất</option>
            </select>
        </div>
    </div>

    {{-- Grid --}}
    <main class="max-w-7xl mx-auto px-4 pb-16">

        <div wire:loading.class.remove="hidden" class="hidden">
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 lg:gap-8">
                @for ($i = 0; $i < 6; $i++)
                    <div class="bg-white border border-slate-200/80 rounded-3xl overflow-hidden shadow-premium">
                        <x-ui.skeleton height="220px" rounded="rounded-none" />

                        <div class="p-5 space-y-4">
                            <div class="flex items-center justify-between gap-4">
                                <x-ui.skeleton height="20px" width="62%" />
                                <x-ui.skeleton height="16px" width="48px" />
                            </div>

                            <x-ui.skeleton height="14px" width="80%" />
                            <x-ui.skeleton height="14px" width="100%" />
                            <x-ui.skeleton height="14px" width="72%" />

                            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                                <x-ui.skeleton height="28px" width="112px" rounded="rounded-full" />
                                <x-ui.skeleton height="16px" width="64px" />
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>

        <div wire:loading.class="hidden">
            @if($cases->isEmpty())
                <div class="text-center py-24 text-slate-400">
                    <div class="text-6xl mb-4"><x-heroicon-o-magnifying-glass class="w-14 h-14 mx-auto mb-4 text-slate-300" /></div>
                    <p class="font-extrabold text-slate-700 text-lg">Không tìm thấy hồ sơ nào</p>
                    <p class="text-sm mt-2">Thử thay đổi từ khoá hoặc bỏ bớt bộ lọc</p>
                </div>
            @else
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 lg:gap-8">
                    @foreach($cases as $case)
                        <x-ui.case-card :case="$case"/>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($cases->hasPages())
                    <div class="flex items-center justify-center gap-2 mt-12">
                        @if($cases->onFirstPage())
                            <span class="w-10 h-10 flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-300 text-sm font-bold cursor-not-allowed">←</span>
                        @else
                            <button wire:click="previousPage"
                                    class="w-10 h-10 flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 transition-colors text-sm font-bold">←</button>
                        @endif

                        @foreach($cases->getUrlRange(1, $cases->lastPage()) as $page => $url)
                            @if($page == $cases->currentPage())
                                <span class="w-10 h-10 flex items-center justify-center rounded-xl bg-primary text-white text-sm font-extrabold shadow-md shadow-primary/20">{{ $page }}</span>
                            @else
                                <button wire:click="gotoPage({{ $page }})"
                                        class="w-10 h-10 flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-colors text-sm font-extrabold">{{ $page }}</button>
                            @endif
                        @endforeach

                        @if($cases->hasMorePages())
                            <button wire:click="nextPage"
                                    class="w-10 h-10 flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 transition-colors text-sm font-bold">→</button>
                        @else
                            <span class="w-10 h-10 flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-300 text-sm font-bold cursor-not-allowed">→</span>
                        @endif
                    </div>
                @endif
            @endif
        </div>
    </main>
</div>
