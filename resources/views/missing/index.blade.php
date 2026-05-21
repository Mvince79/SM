@extends('layouts.app')
@section('title', 'Hồ sơ đang tìm kiếm')

@section('content')
    <!-- PAGE HEADER -->
    <div class="bg-white border-b border-slate-100 py-8 px-4">
        <div class="max-w-7xl mx-auto space-y-2">
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Hồ sơ tìm trẻ lạc</h1>
            <p class="text-slate-500 text-sm font-semibold">
                Hiện có <strong class="text-slate-800">{{ $cases->total() }}</strong> bé đang được cộng đồng tích cực tìm
                kiếm
            </p>
        </div>
    </div>

    <!-- SEARCH + FILTER BAR -->
    <div class="bg-white border-b border-slate-150 py-4 px-4 sticky top-16 z-40 shadow-sm">
        <form method="GET" action="{{ route('missing.index') }}">
            <div class="max-w-7xl mx-auto space-y-4">
                <div class="flex flex-col lg:flex-row gap-3">
                    <!-- Search Input -->
                    <div class="relative flex-1">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text"
                               name="q"
                               value="{{ $q }}"
                               placeholder="Tìm theo tên con, đặc điểm đặc biệt, nơi mất tích..."
                               class="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent bg-slate-50 focus:bg-white transition-all font-medium" />
                    </div>

                    <!-- Filters -->
                    <div class="flex gap-2 flex-wrap items-center">
                        <select name="province"
                            class="border border-slate-200 rounded-2xl px-4 py-3 text-xs bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary font-bold text-slate-600 transition-colors">
                            <option value="">Tất cả tỉnh thành</option>
                            @foreach($provinces as $province)
                                <option value="{{ $province->id }}" {{ request('province') == $province->id ? 'selected' : '' }}>
                                    {{ $province->name }}
                                </option>
                            @endforeach
                        </select>
                        <select name="gender"
                            class="border border-slate-200 rounded-2xl px-4 py-3 text-xs bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary font-bold text-slate-600 transition-colors">
                            <option value="">Giới tính</option>
                            <option value="MALE"   {{ request('gender') === 'MALE'   ? 'selected' : '' }}>Nam</option>
                            <option value="FEMALE" {{ request('gender') === 'FEMALE' ? 'selected' : '' }}>Nữ</option>
                        </select>
                        <select name="age"
                            class="border border-slate-200 rounded-2xl px-4 py-3 text-xs bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary font-bold text-slate-600 transition-colors">
                            <option value="">Độ tuổi</option>
                            <option value="0-5"   {{ request('age') === '0-5'   ? 'selected' : '' }}>Dưới 5 tuổi</option>
                            <option value="5-10"  {{ request('age') === '5-10'  ? 'selected' : '' }}>5–10 tuổi</option>
                            <option value="10-15" {{ request('age') === '10-15' ? 'selected' : '' }}>10–15 tuổi</option>
                            <option value="15+"   {{ request('age') === '15+'   ? 'selected' : '' }}>Trên 15 tuổi</option>
                        </select>
                        <button type="submit"
                            class="bg-primary text-white font-extrabold px-5 py-3 rounded-2xl text-xs hover:bg-primary-dark transition-colors shadow-sm">
                            Lọc
                        </button>
                        @if($q || request('province') || request('gender') || request('age'))
                            <a href="{{ route('missing.index') }}"
                               class="text-xs font-bold text-slate-500 hover:text-primary px-3 py-3 transition-colors">
                                Xóa lọc ×
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- SORT & SUMMARY BAR -->
    <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
        <p class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">
            {{ $cases->total() }} kết quả phù hợp
            @if($q)
                — tìm kiếm: "<span class="text-primary">{{ $q }}</span>"
            @endif
        </p>
    </div>

    <!-- CASES GRID -->
    <main class="max-w-7xl mx-auto px-4 pb-16">

        @if($cases->isEmpty())
            <div class="text-center py-24 text-slate-400">
                <div class="text-6xl mb-4">🔍</div>
                <p class="font-extrabold text-slate-700 text-lg">Không tìm thấy hồ sơ nào</p>
                <p class="text-sm mt-2">Thử thay đổi từ khoá hoặc bỏ bớt bộ lọc</p>
                <a href="{{ route('missing.index') }}" class="inline-block mt-6 text-primary font-bold text-sm hover:underline">
                    Xem tất cả hồ sơ →
                </a>
            </div>
        @else
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 lg:gap-8">
                @foreach($cases as $case)
                    @php
                        $isFound = $case->status === 'FOUND';
                    @endphp
                    <div class="{{ $isFound
                        ? 'bg-success-light/40 border border-success/30'
                        : 'bg-white border border-slate-200/80' }}
                        rounded-3xl overflow-hidden shadow-premium hover:shadow-premium-hover hover:-translate-y-1 transition-premium flex flex-col justify-between">

                        <a href="{{ route('missing.detail', $case->id) }}"
                           class="block group relative overflow-hidden aspect-[4/3] bg-slate-900">
                            <img src="{{ $case->thumb_url }}"
                                 class="w-full h-full object-cover transform group-hover:scale-105 transition-premium {{ $isFound ? 'grayscale opacity-90' : '' }}"
                                 alt="Ảnh {{ $case->full_name }}"
                                 onerror="this.src='https://api.dicebear.com/7.x/thumbs/svg?seed={{ urlencode($case->full_name) }}&backgroundColor=ffdfbf'"/>

                            @if($isFound)
                                <span class="absolute top-3 left-3 bg-success text-white text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-xl flex items-center gap-1">
                                    ✓ Bé đã về nhà
                                </span>
                            @else
                                <span class="absolute top-3 left-3 bg-primary text-white text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-xl shadow-glow-sos flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 bg-white rounded-full animate-ping"></span> Đang tìm
                                </span>
                            @endif

                            <span class="absolute top-3 right-3 bg-black/60 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded-lg">
                                {{ $case->approved_at?->diffForHumans() ?? $case->created_at->diffForHumans() }}
                            </span>
                        </a>

                        <div class="p-5 space-y-4 flex-1 flex flex-col justify-between">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-extrabold text-slate-900 text-base hover:text-primary transition-colors">
                                        <a href="{{ route('missing.detail', $case->id) }}">{{ $case->full_name }}</a>
                                    </h3>
                                    @if($case->age)
                                        <span class="text-xs font-extrabold text-slate-400">{{ $case->age }} tuổi</span>
                                    @endif
                                </div>
                                <div class="text-xs font-semibold text-slate-500 flex items-center gap-1">
                                    <span>📍</span>
                                    {{ collect([$case->district, $case->province?->name])->filter()->implode(', ') }}
                                </div>
                                @if($case->clothes_desc || $case->circumstances)
                                    <p class="text-xs text-slate-400 leading-relaxed line-clamp-2">
                                        {{ $case->clothes_desc ?? $case->circumstances }}
                                    </p>
                                @endif
                            </div>

                            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                                <span class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-600 text-[10px] font-extrabold px-2.5 py-1.5 rounded-full">
                                    {{ $case->case_code }}
                                </span>
                                @if(!$isFound)
                                    <a href="{{ route('missing.detail', $case->id) }}"
                                       class="text-xs font-extrabold text-primary hover:underline flex items-center gap-1">
                                        Chi tiết <span class="text-[10px]">→</span>
                                    </a>
                                @else
                                    <span class="text-[10px] text-slate-400 font-bold">Cảm ơn cộng đồng!</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- PAGINATION -->
            @if($cases->hasPages())
                <div class="flex items-center justify-center gap-2 mt-12">
                    {{-- Previous --}}
                    @if($cases->onFirstPage())
                        <span class="w-10 h-10 flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-300 text-sm font-bold cursor-not-allowed">←</span>
                    @else
                        <a href="{{ $cases->previousPageUrl() }}" class="w-10 h-10 flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 transition-colors text-sm font-bold">←</a>
                    @endif

                    {{-- Page numbers --}}
                    @foreach($cases->getUrlRange(1, $cases->lastPage()) as $page => $url)
                        @if($page == $cases->currentPage())
                            <span class="w-10 h-10 flex items-center justify-center rounded-xl bg-primary text-white text-sm font-extrabold shadow-md shadow-primary/20">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="w-10 h-10 flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-colors text-sm font-extrabold">{{ $page }}</a>
                        @endif
                    @endforeach

                    {{-- Next --}}
                    @if($cases->hasMorePages())
                        <a href="{{ $cases->nextPageUrl() }}" class="w-10 h-10 flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 transition-colors text-sm font-bold">→</a>
                    @else
                        <span class="w-10 h-10 flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-300 text-sm font-bold cursor-not-allowed">→</span>
                    @endif
                </div>
            @endif
        @endif
    </main>
@endsection