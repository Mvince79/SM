@extends('layouts.app')
@section('title', 'Hồ sơ đang tìm kiếm')

@section('content')

    {{-- Page header --}}
    <div class="bg-white border-b border-slate-100 py-8 px-4">
        <div class="max-w-7xl mx-auto space-y-2">
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                Hồ sơ tìm trẻ lạc
            </h1>
            <p class="text-slate-500 text-sm font-semibold">
                Cộng đồng đang chung tay tìm kiếm các bé
            </p>
        </div>
    </div>

    {{-- SearchFilter: search bar + filter bar + active pills --}}
    @livewire('missing.search-filter')

    {{-- CasesGrid: sort bar + grid + pagination --}}
    @livewire('missing.cases-grid')

    {{-- FAB mobile: nút Đăng tin nổi --}}
    <a href="{{ route('cases.create') }}"
       class="fixed bottom-6 right-6 z-50 flex items-center gap-2
              bg-primary text-white font-extrabold text-sm
              px-5 py-3.5 rounded-2xl shadow-lg shadow-primary/30
              hover:bg-primary-dark transition-all
              sm:hidden">
        ＋ Đăng tin
    </a>

@endsection