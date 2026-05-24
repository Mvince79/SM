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
        <div class="max-w-7xl mx-auto space-y-4">
            <form action="{{ route('missing.index') }}" method="GET">
                <div class="flex flex-col lg:flex-row gap-3">
                    <!-- Search Input -->
                    <div class="relative flex-1">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                            placeholder="Tìm theo tên con, đặc điểm đặc biệt, nơi mất tích..." class="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-2xl text-sm
                                       focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent
                                       bg-slate-50 focus:bg-white transition-all font-medium" />
                    </div>

                    <!-- Filters -->
                    <div class="flex gap-2 flex-wrap items-center">
                        <select name="province_id"
                            class="border border-slate-200 rounded-2xl px-4 py-3 text-xs bg-slate-50
                                       focus:outline-none focus:ring-2 focus:ring-primary font-bold text-slate-600 transition-colors">
                            <option value="">Tất cả tỉnh thành</option>
                            @foreach($provinces as $province)
                                <option value="{{ $province->id }}" {{ ($filters['province_id'] ?? '') == $province->id ? 'selected' : '' }}>
                                    {{ $province->name }}
                                </option>
                            @endforeach
                        </select>

                        <select name="gender"
                            class="border border-slate-200 rounded-2xl px-4 py-3 text-xs bg-slate-50
                                       focus:outline-none focus:ring-2 focus:ring-primary font-bold text-slate-600 transition-colors">
                            <option value="">Giới tính</option>
                            <option value="MALE" {{ ($filters['gender'] ?? '') === 'MALE' ? 'selected' : '' }}>Nam</option>
                            <option value="FEMALE" {{ ($filters['gender'] ?? '') === 'FEMALE' ? 'selected' : '' }}>Nữ</option>
                        </select>

                        <select name="age_range"
                            class="border border-slate-200 rounded-2xl px-4 py-3 text-xs bg-slate-50
                                       focus:outline-none focus:ring-2 focus:ring-primary font-bold text-slate-600 transition-colors">
                            <option value="">Độ tuổi</option>
                            <option value="0-5" {{ ($filters['age_range'] ?? '') === '0-5' ? 'selected' : '' }}>Dưới 5 tuổi
                            </option>
                            <option value="5-10" {{ ($filters['age_range'] ?? '') === '5-10' ? 'selected' : '' }}>5–10 tuổi
                            </option>
                            <option value="10-15" {{ ($filters['age_range'] ?? '') === '10-15' ? 'selected' : '' }}>10–15 tuổi
                            </option>
                            <option value="15-99" {{ ($filters['age_range'] ?? '') === '15-99' ? 'selected' : '' }}>Trên 15
                                tuổi</option>
                        </select>

                        <select name="sort"
                            class="border border-slate-200 rounded-2xl px-4 py-3 text-xs bg-slate-50
                                       focus:outline-none focus:ring-2 focus:ring-primary font-bold text-slate-600 transition-colors">
                            <option value="newest" {{ ($filters['sort'] ?? 'newest') === 'newest' ? 'selected' : '' }}>Mới
                                nhất</option>
                            <option value="oldest" {{ ($filters['sort'] ?? '') === 'oldest' ? 'selected' : '' }}>Cũ nhất
                            </option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap text-xs pt-3">
                    <button type="submit" class="inline-block bg-primary hover:bg-primary-dark text-white font-extrabold
                                   px-6 py-2.5 rounded-2xl shadow-lg transition-all text-xs uppercase tracking-wider">
                        🔍 Tìm Kiếm
                    </button>
                    @if(!empty(array_filter($filters ?? [])))
                        <a href="{{ route('missing.index') }}"
                            class="text-xs font-bold text-slate-500 hover:text-primary underline">
                            Xóa bộ lọc
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- SORT & SUMMARY BAR -->
    <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between z-10 relative">
        <p class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">
            {{ $cases->total() }} kết quả phù hợp
        </p>
    </div>

    <!-- CASES GRID -->
    <main class="max-w-7xl mx-auto px-4 pb-16">

        @if($cases->isEmpty())
            <!-- Empty State -->
            <div class="text-center py-24 space-y-4">
                <div class="text-6xl">🔍</div>
                <h3 class="text-lg font-extrabold text-slate-700">Không tìm thấy hồ sơ phù hợp</h3>
                <p class="text-sm text-slate-400 font-medium">Thử thay đổi từ khóa hoặc bộ lọc khác nhé</p>
                <a href="{{ route('missing.index') }}"
                    class="inline-block mt-2 bg-primary text-white font-extrabold px-6 py-2.5 rounded-2xl text-xs hover:bg-primary-dark transition-all">
                    Xóa bộ lọc, xem tất cả
                </a>
            </div>
        @else
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 lg:gap-8" id="cases-grid">
                @foreach($cases as $case)
                    @if($case->status === 'MATCHED' || !empty($case->found_at))
                        {{-- ── Card: Bé đã về nhà ── --}}
                        <div
                            class="bg-success-light/40 border border-success/30 rounded-3xl overflow-hidden shadow-premium hover:shadow-premium-hover hover:-translate-y-1 transition-premium flex flex-col justify-between">
                            <div class="relative overflow-hidden aspect-[4/3] bg-slate-900">
                                @if($case->primaryPhoto)
                                    <img src="{{ asset('storage/' . $case->primaryPhoto->path_thumb) }}"
                                        class="w-full h-full object-cover grayscale opacity-90" alt="{{ $case->full_name }}" />
                                @else
                                    <img src="https://api.dicebear.com/7.x/thumbs/svg?seed={{ $case->id }}&backgroundColor=d1fae5"
                                        class="w-full h-full object-cover grayscale opacity-90" alt="" />
                                @endif
                                <span
                                    class="absolute top-3 left-3 bg-success text-white text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-xl flex items-center gap-1">
                                    ✓ Bé đã về nhà
                                </span>
                                @if($case->found_at)
                                    <span
                                        class="absolute top-3 right-3 bg-black/60 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded-lg">
                                        {{ \Carbon\Carbon::parse($case->found_at)->format('d/m/Y') }}
                                    </span>
                                @endif
                            </div>
                            <div class="p-5 space-y-4 flex-1 flex flex-col justify-between">
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <h3 class="font-extrabold text-slate-700 text-base">{{ $case->full_name }}</h3>
                                        <span class="text-xs font-extrabold text-slate-400">{{ $case->age_estimate }} tuổi</span>
                                    </div>
                                    <div class="text-xs font-semibold text-slate-500 flex items-center gap-1">
                                        <span>📍</span> {{ $case->province?->name }}
                                    </div>
                                </div>
                                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                                    <span class="inline-flex items-center gap-1.5 text-success text-[10px] font-black">✅ Thành
                                        công</span>
                                    <span class="text-[10px] text-slate-400 font-bold">Cảm ơn cộng đồng!</span>
                                </div>
                            </div>
                        </div>

                    @else
                        {{-- ── Card: Đang tìm ── --}}
                        <div
                            class="bg-white border border-slate-200/80 rounded-3xl overflow-hidden shadow-premium hover:shadow-premium-hover hover:-translate-y-1 transition-premium flex flex-col justify-between">
                            <a href="{{ route('missing.detail', ['id' => $case->id]) }}"
                                class="block group relative overflow-hidden aspect-[4/3] bg-slate-900">
                                @if($case->primaryPhoto)
                                    <img src="{{ asset('storage/' . $case->primaryPhoto->path_thumb) }}"
                                        class="w-full h-full object-cover transform group-hover:scale-105 transition-premium"
                                        alt="{{ $case->full_name }}" />
                                @else
                                    <img src="https://api.dicebear.com/7.x/thumbs/svg?seed={{ $case->id }}&backgroundColor=ffdfbf"
                                        class="w-full h-full object-cover transform group-hover:scale-105 transition-premium" alt="" />
                                @endif
                                <span
                                    class="absolute top-3 left-3 bg-primary text-white text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-xl shadow-glow-sos flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 bg-white rounded-full animate-ping"></span> Đang tìm
                                </span>
                                <span
                                    class="absolute top-3 right-3 bg-black/60 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded-lg">
                                    {{ $case->missing_date ? \Carbon\Carbon::parse($case->missing_date)->diffForHumans() : '' }}
                                </span>
                            </a>
                            <div class="p-5 space-y-4 flex-1 flex flex-col justify-between">
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <h3 class="font-extrabold text-slate-900 text-base hover:text-primary transition-colors">
                                            <a href="{{ route('missing.detail', ['id' => $case->id]) }}">
                                                {{ $case->full_name }}
                                            </a>
                                        </h3>
                                        <span class="text-xs font-extrabold text-slate-400">{{ $case->age_estimate }} tuổi</span>
                                    </div>
                                    <div class="text-xs font-semibold text-slate-500 flex items-center gap-1">
                                        <span>📍</span>
                                        {{ implode(', ', array_filter([$case->district, $case->province?->name])) }}
                                    </div>
                                    @if($case->circumstances)
                                        <p class="text-xs text-slate-400 leading-relaxed line-clamp-2">
                                            {{ $case->circumstances }}
                                        </p>
                                    @endif
                                </div>
                                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                                    <span
                                        class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-600 text-[10px] font-extrabold px-2.5 py-1.5 rounded-full">
                                        📞 Bảo mật liên hệ
                                    </span>
                                    <a href="{{ route('missing.detail', ['id' => $case->id]) }}"
                                        class="text-xs font-extrabold text-primary hover:underline flex items-center gap-1">
                                        Chi tiết <span class="text-[10px]">→</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- ── PAGINATION ── --}}
            @if($cases->lastPage() > 1)
                <div class="flex items-center justify-center gap-2 mt-12 flex-wrap">
                    {{-- Prev --}}
                    @if($cases->onFirstPage())
                        <span
                            class="w-10 h-10 flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-300 text-sm font-bold cursor-not-allowed">←</span>
                    @else
                        <a href="{{ $cases->previousPageUrl() }}"
                            class="w-10 h-10 flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 transition-colors text-sm font-bold">←</a>
                    @endif

                    {{-- Page numbers --}}
                    @php
                        $current = $cases->currentPage();
                        $last = $cases->lastPage();
                        $window = 2; // số trang hiện mỗi bên current
                    @endphp

                    @for($p = 1; $p <= $last; $p++)
                        @if($p === 1 || $p === $last || abs($p - $current) <= $window)
                            @if($p === $current)
                                <span
                                    class="w-10 h-10 flex items-center justify-center rounded-xl bg-primary text-white text-sm font-extrabold shadow-md shadow-primary/20">{{ $p }}</span>
                            @else
                                <a href="{{ $cases->url($p) }}"
                                    class="w-10 h-10 flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-colors text-sm font-extrabold">{{ $p }}</a>
                            @endif
                        @elseif($p === $current - $window - 1 || $p === $current + $window + 1)
                            <span class="text-slate-400 text-xs font-extrabold px-1">...</span>
                        @endif
                    @endfor

                    {{-- Next --}}
                    @if($cases->hasMorePages())
                        <a href="{{ $cases->nextPageUrl() }}"
                            class="w-10 h-10 flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 transition-colors text-sm font-bold">→</a>
                    @else
                        <span
                            class="w-10 h-10 flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-300 text-sm font-bold cursor-not-allowed">→</span>
                    @endif
                </div>
            @endif
        @endif

    </main>
@endsection