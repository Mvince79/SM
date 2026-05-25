<?php

use Livewire\Component;

new class extends Component
{
    public string $keyword = '';

    public function search(): void
    {
        $keyword = trim($this->keyword);

        if ($keyword === '') {
            return;
        }

        $this->redirect(route('missing.index', ['q' => $keyword]));
    }
};
?>

<section class="quick-search-section">
    <div class="max-w-3xl mx-auto text-center space-y-6">
        <h2 class="text-2xl font-black text-slate-900 tracking-tight">
            Tìm kiếm nhanh hồ sơ con lạc
        </h2>
        <p class="text-slate-500 text-sm max-w-lg mx-auto">
            Nhập tên của con, đặc điểm nhận dạng nổi bật hoặc khu vực bé bị lạc mất để lọc hồ sơ ngay lập tức.
        </p>
        <div class="search-box">
            <div class="relative flex-1 flex items-center px-3">
                <svg class="w-5 h-5 text-slate-400 absolute left-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text"
                       wire:model.live="keyword"
                       wire:keydown.enter="search"
                       placeholder="Tên con, đặc điểm, địa điểm bị lạc..."
                       class="w-full pl-9 pr-3 py-3 text-slate-800 placeholder-slate-400 bg-transparent text-sm focus:outline-none"/>
            </div>
            <button type="button"
                    wire:click="search"
                    class="bg-primary text-white font-extrabold px-8 py-3.5 rounded-xl hover:bg-primary-dark shadow-md shadow-primary/20 transition-premium text-sm">
                Tìm kiếm
            </button>
        </div>
    </div>
</section>