@extends('layouts.app')
@section('title', $case->full_name . ' — Chi tiết hồ sơ')

@push('head')
    <meta name="robots" content="noindex">
    <meta name="description"
        content="{{ $case->meta_description ?: 'Thông tin tìm kiếm trẻ mất tích ' . $case->full_name }}">
    <x-json-ld type="person" :data="[
            'name' => $case->full_name,
            'image' => 'https://api.dicebear.com/7.x/thumbs/svg?seed=' . urlencode($case->full_name),
            'description' => $case->circumstances,
            'age' => $case->age_estimate ?? \Carbon\Carbon::parse($case->dob)->age,
            'missing_location' => $case->district . ', ' . $case->address_detail,
            'missing_date' => \Carbon\Carbon::parse($case->missing_date)->format('d/m/Y'),
            'case_code' => $case->case_code,
        ]" />
@endpush

@section('og_meta')
    @php
        $ogTitle = $case->meta_title ?: $case->full_name . ' - Hồ sơ mất tích';
        $ogDescription = $case->meta_description ?: 'Thông tin tìm kiếm trẻ mất tích ' . $case->full_name;
        $ogImage = $case->og_image_url ?: $case->detail_primary_photo_url ?: $case->thumb_url;
    @endphp
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="TìmTrẻ.vn">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $ogImage }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $ogDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
@endsection

@section('content')

    <!-- BREADCRUMB -->
    <div class="max-w-7xl mx-auto px-4 py-2 sm:py-4">
        <nav
            class="text-[10px] sm:text-xs font-bold text-slate-400 flex gap-1.5 sm:gap-2 items-center uppercase tracking-wider overflow-hidden">
            <a href="{{ route('home') }}" class="hover:text-primary transition-colors whitespace-nowrap">Trang chủ</a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('missing.index') }}" class="hover:text-primary transition-colors whitespace-nowrap">Hồ sơ mất
                tích</a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-800 truncate">{{ $case->full_name }}</span>
        </nav>
    </div>

    <!-- MAIN CONTENT GRID -->
    <main class="max-w-7xl mx-auto px-3 sm:px-4 pb-12 sm:pb-20">
        <div class="grid lg:grid-cols-12 gap-3 sm:gap-6 lg:gap-8 items-start">

            <!-- LEFT: 8 cols -->
            <div class="lg:col-span-8 space-y-3 sm:space-y-6 lg:space-y-8 order-first min-w-0">

                <!-- Primary Profile Card -->
                <div class="bg-white border border-slate-200/80 rounded-2xl sm:rounded-3xl overflow-hidden shadow-premium">
                    <!-- Status Banner -->
                    <div
                        class="bg-primary/5 border-b border-primary/10 px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 bg-primary rounded-full animate-ping"></span>
                            <span class="text-primary font-extrabold text-sm tracking-tight">
                                @if ($case->status === 'MATCHED')
                                    <x-heroicon-o-check-circle class="w-4 h-4 inline" /> BÉ ĐÃ ĐƯỢC TÌM THẤY
                                @else
                                    <x-heroicon-o-megaphone class="w-4 h-4 inline" /> ĐANG CẦN TÌM KHẨN CẤP TRÊN TOÀN QUỐC
                                @endif
                            </span>
                        </div>
                        @if ($case->missing_date)
                            <span
                                class="{{ $bannerConfig['badge'] }} text-white text-[10px] font-black uppercase tracking-wider px-2.5 py-1 sm:px-3 sm:py-1.5 rounded-full flex-shrink-0">
                                @if ($bannerConfig['badgeText'])
                                    {{ $bannerConfig['badgeText'] }}
                                @else
                                    Mất tích {{ (int) -now()->diffInDays($case->missing_date) }} ngày
                                @endif
                            </span>
                        @endif
                    </div>

                    <!-- Profile Grid -->
                    <div class="p-3 sm:p-6 lg:p-8 space-y-3 sm:space-y-6">
                        <div class="flex gap-3 sm:gap-8 items-start">

                            <!-- Avatar photo container + Gallery -->
                            <div class="w-full sm:w-44 flex-shrink-0" x-data="{
                                                                    open: false,
                                                                    current: 0,
                                                                    photos: {{ Js::from($case->detail_photo_urls) }},
                                                                    prev() { this.current = (this.current - 1 + this.photos.length) % this.photos.length },
                                                                    next() { this.current = (this.current + 1) % this.photos.length }
                                                                }" @keydown.left.window="if(open) prev()"
                                @keydown.right.window="if(open) next()" @keydown.escape.window="open = false">

                                <div class="relative overflow-hidden rounded-xl sm:rounded-2xl border-2 border-slate-100 shadow bg-slate-900 group aspect-square cursor-pointer"
                                    @click="open = true; current = 0">
                                    <img src="{{ $case->detail_primary_photo_url ?? $case->thumb_url }}"
                                        class="w-full h-full object-cover transform group-hover:scale-105 transition-premium"
                                        alt="{{ $case->full_name }}" />
                                    @auth
                                        @if (isset($aiMatches) && $aiMatches->isNotEmpty() && $aiMatches->first()->score >= 0.7)
                                            <div class="absolute top-2 left-2 z-10">
                                                <x-missing.ai-match-badge :score="$aiMatches->first()->score" />
                                            </div>
                                        @endif
                                    @endauth

                                    @if (count($case->detail_photo_urls) > 1)
                                        <div
                                            class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                            <span class="text-white text-xs font-bold bg-black/60 px-3 py-1.5 rounded-xl">
                                                📷 {{ count($case->detail_photo_urls) }} ảnh
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Thumbnail strip --}}
                                @if (count($case->detail_photo_urls) > 1)
                                    <div class="flex gap-1.5 mt-2 overflow-x-auto pb-1">
                                        @foreach ($case->detail_photo_urls as $i => $photoUrl)
                                            <img src="{{ $photoUrl }}"
                                                class="w-12 h-12 object-cover rounded-lg border-2 border-slate-200 cursor-pointer hover:border-red-400 flex-shrink-0"
                                                @click="open = true; current = {{ $i }}" alt="Ảnh {{ $i + 1 }}" />
                                        @endforeach
                                    </div>
                                @endif

                                @if ($case->primaryPhoto)
                                    <p class="text-center text-[10px] text-slate-400 font-bold mt-2.5">
                                        @if ($case->primaryPhoto->updated_at || $case->primaryPhoto->created_at)
                                            Ảnh chụp rõ nhất:
                                            {{ $case->primaryPhoto->updated_at?->format('n/Y') ?? $case->primaryPhoto->created_at?->format('n/Y') }}
                                        @else
                                            Ảnh chụp rõ nhất (chưa rõ thời gian chụp)
                                        @endif
                                    </p>
                                @endif

                                {{-- Lightbox Modal --}}
                                <template x-teleport="body">
                                    <div x-show="open" x-transition:enter="transition ease-out duration-200"
                                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                        x-transition:leave="transition ease-in duration-150"
                                        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/90"
                                        @click.self="open = false" style="display:none">

                                        {{-- Close --}}
                                        <button @click="open = false"
                                            class="absolute top-4 right-4 text-white bg-white/20 hover:bg-white/40 rounded-full w-10 h-10 flex items-center justify-center text-xl font-bold z-10">
                                            ✕
                                        </button>

                                        {{-- Counter --}}
                                        <div
                                            class="absolute top-4 left-1/2 -translate-x-1/2 text-white text-sm font-bold bg-black/50 px-3 py-1 rounded-full">
                                            <span x-text="current + 1"></span> / <span x-text="photos.length"></span>
                                        </div>
                                        <button x-show="photos.length > 1" @click="prev"
                                            class="absolute left-4 text-white bg-white/20 hover:bg-white/40 rounded-full w-10 h-10 flex items-center justify-center text-xl z-10">‹</button>
                                        <img :src="photos[current]"
                                            class="max-h-[85vh] max-w-[90vw] object-contain rounded-lg shadow-2xl"
                                            :alt="'Ảnh ' + (current + 1)" />
                                        <button x-show="photos.length > 1" @click="next"
                                            class="absolute right-4 text-white bg-white/20 hover:bg-white/40 rounded-full w-10 h-10 flex items-center justify-center text-xl z-10">›</button>
                                        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
                                            <template x-for="(photo, i) in photos" :key="i">
                                                <img :src="photo"
                                                    :class="current === i ? 'border-white scale-110' :
                                                                                            'border-transparent opacity-60'"
                                                    class="w-12 h-12 object-cover rounded-lg border-2 cursor-pointer transition-all"
                                                    @click="current = i" />
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="flex-1 min-w-0 hidden sm:block">
                                <div class="flex items-start justify-between gap-1.5">
                                    <div class="min-w-0 flex-1">
                                        <h1
                                            class="text-base font-black text-slate-900 tracking-tight leading-tight break-words">
                                            {{ $case->full_name }}
                                        </h1>
                                        <p class="text-[10px] text-slate-400 font-bold mt-1">Mã: <span
                                                class="font-mono text-slate-700">#{{ $case->case_code }}</span></p>
                                        <div
                                            class="mt-3 inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-600 px-3 py-1.5 rounded-full">
                                            <span>👁</span>
                                            <span>{{ number_format($case->view_count) }} lượt xem</span>
                                        </div>
                                    </div>
                                    <button title="Sao chép liên kết"
                                        onclick="navigator.clipboard.writeText(window.location.href); alert('Đã sao chép link hồ sơ!')"
                                        class="p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors shadow-sm flex-shrink-0">
                                        <x-heroicon-o-share class="w-4 h-4 text-slate-500" />
                                    </button>
                                </div>

                                <!-- Info matrix — desktop only (sm+) -->
                                <div class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm border-t border-slate-100 pt-4">
                                    <div class="min-w-0">
                                        <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Tuổi</span>
                                        <div class="font-extrabold text-slate-800 text-sm mt-0.5 break-words">
                                            {{ $case->age }} tuổi
                                            @if ($case->dob)
                                                (Sinh năm {{ $case->dob->year }})
                                            @endif
                                        </div>
                                    </div>
                                    <div class="min-w-0">
                                        <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Giới
                                            tính</span>
                                        <div class="font-extrabold text-slate-800 mt-0.5">
                                            @if ($case->gender === 'MALE')
                                                Nam
                                            @elseif($case->gender === 'FEMALE')
                                                Nữ
                                            @else
                                                Không rõ
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-span-full min-w-0">
                                        <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Khu vực lạc
                                            mất</span>
                                        <div class="font-extrabold text-slate-800 mt-0.5">
                                            {{ collect([$case->address_detail, $case->ward, $case->district, $case->province?->name])->filter()->implode(', ') ?: '—' }}
                                        </div>
                                    </div>
                                    <div class="col-span-full min-w-0">
                                        <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Thời gian
                                            bị lạc</span>
                                        <div class="font-extrabold text-slate-800 text-sm mt-0.5">
                                            {{ $case->missing_date?->format('d/m/Y') }}
                                            @if ($case->formatted_time)
                                                · Lúc {{ $case->formatted_time }}
                                            @endif
                                        </div>
                                    </div>
                                    @if ($case->height_cm || $case->weight_kg)
                                        <div>
                                            <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Thể
                                                trạng</span>
                                            <div class="font-extrabold text-slate-800 mt-0.5">
                                                @if ($case->height_cm)
                                                    Cao {{ $case->height_cm }}cm
                                                @endif
                                                @if ($case->height_cm && $case->weight_kg)
                                                    ·
                                                @endif
                                                @if ($case->weight_kg)
                                                    Nặng {{ $case->weight_kg }}kg
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                    @if ($case->clothes_desc || $case->clothes_color)
                                        <div>
                                            <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Trang
                                                phục
                                                khi lạc</span>
                                            <div class="font-extrabold text-primary mt-0.5">
                                                {{ $case->clothes_desc ?? ($case->clothes_color ?? '—') }}
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>{{-- end hidden sm:block --}}

                        </div>{{-- end flex row (avatar + desktop-info) --}}

                        {{-- Info matrix — MOBILE only, full width bên dưới row avatar+tên --}}
                        <div class="sm:hidden grid grid-cols-2 gap-x-4 gap-y-2.5 text-sm border-t border-slate-100 pt-3">
                            <div class="min-w-0">
                                <span class="text-slate-400 font-bold text-[9px] uppercase tracking-wider">Tuổi</span>
                                <div class="font-extrabold text-slate-800 text-xs mt-0.5 break-words">
                                    {{ $case->age }} tuổi
                                    @if ($case->dob)
                                        (Sinh năm {{ $case->dob->year }})
                                    @endif
                                </div>
                            </div>
                            <div class="min-w-0">
                                <span class="text-slate-400 font-bold text-[9px] uppercase tracking-wider">Giới tính</span>
                                <div class="font-extrabold text-slate-800 text-xs mt-0.5">
                                    @if ($case->gender === 'MALE')
                                        Nam
                                    @elseif($case->gender === 'FEMALE')
                                        Nữ
                                    @else
                                        Không rõ
                                    @endif
                                </div>
                            </div>
                            <div class="col-span-full min-w-0">
                                <span class="text-slate-400 font-bold text-[9px] uppercase tracking-wider">Khu vực lạc
                                    mất</span>
                                <div class="font-extrabold text-slate-800 text-xs mt-0.5 break-words">
                                    {{ collect([$case->address_detail, $case->ward, $case->district, $case->province?->name])->filter()->implode(', ') ?: '—' }}
                                </div>
                            </div>
                            <div class="col-span-full min-w-0">
                                <span class="text-slate-400 font-bold text-[9px] uppercase tracking-wider">Thời gian bị
                                    lạc</span>
                                <div class="font-extrabold text-slate-800 text-xs mt-0.5">
                                    {{ $case->missing_date?->format('d/m/Y') }}
                                    @if ($case->formatted_time)
                                        · Lúc {{ $case->formatted_time }}
                                    @endif
                                </div>
                            </div>
                            @if ($case->height_cm || $case->weight_kg)
                                <div class="min-w-0">
                                    <span class="text-slate-400 font-bold text-[9px] uppercase tracking-wider">Thể
                                        trạng</span>
                                    <div class="font-extrabold text-slate-800 text-xs mt-0.5">
                                        @if ($case->height_cm)
                                            Cao {{ $case->height_cm }}cm
                                        @endif
                                        @if ($case->height_cm && $case->weight_kg)
                                            ·
                                        @endif
                                        @if ($case->weight_kg)
                                            Nặng {{ $case->weight_kg }}kg
                                        @endif
                                    </div>
                                </div>
                            @endif
                            @if ($case->clothes_desc || $case->clothes_color)
                                <div class="col-span-full min-w-0">
                                    <span class="text-slate-400 font-bold text-[9px] uppercase tracking-wider">Trang phục
                                        khi lạc</span>
                                    <div class="font-extrabold text-primary text-xs mt-0.5 break-words">
                                        {{ $case->clothes_desc ?? ($case->clothes_color ?? '—') }}
                                    </div>
                                </div>
                            @endif
                        </div>{{-- end sm:hidden info grid --}}

                        <!-- Detailed Description Box -->
                        @if ($case->circumstances || $case->special_marks)
                            <div class="border-t border-slate-100 pt-6 space-y-3">
                                <h4 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider">Mô tả đặc điểm
                                    cụ
                                    thể
                                </h4>
                                <p
                                    class="text-xs text-slate-500 leading-relaxed bg-slate-50 p-3 sm:p-4 rounded-xl sm:rounded-2xl border border-slate-150 font-medium break-words">
                                    {{ $case->circumstances ?? $case->special_marks }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                <div
                    class="lg:hidden bg-gradient-to-r from-primary to-primary-dark text-white rounded-2xl p-4 shadow-lg shadow-primary/20 space-y-3 relative overflow-hidden">
                    <div
                        class="absolute top-[-20%] right-[-10%] w-28 h-28 bg-white/10 rounded-full blur-2xl pointer-events-none">
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="relative flex h-2.5 w-2.5">
                            <span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-white"></span>
                        </span>
                        <span class="font-extrabold text-sm tracking-widest uppercase">Bạn đang thấy bé
                            {{ Str::words($case->full_name, 1, '') }}?</span>
                    </div>
                    <p class="text-xs text-slate-100 leading-relaxed font-medium">
                        Nếu bạn đang đứng cạnh bé hoặc nhìn thấy bé ngay lúc này, hãy ấn nút dưới đây để chia sẻ toạ độ GPS
                        chính xác.
                    </p>
                    <button onclick="shareGPS()"
                        class="w-full bg-white text-primary font-black py-3.5 rounded-xl hover:bg-slate-50 transition-premium text-sm shadow-md">
                        <x-heroicon-o-map-pin class="w-4 h-4 inline" /> Chia sẻ vị trí GPS tức thời Chia sẻ vị trí GPS tức
                        thời
                    </button>
                </div>

                <!-- AI MATCH SECTION -->
                @auth
                    @if (isset($aiMatches) && $aiMatches->isNotEmpty())
                        <div
                            class="bg-gradient-to-br from-brand/5 via-brand/10 to-brand-light border border-brand/20 rounded-2xl sm:rounded-3xl p-4 sm:p-6 space-y-4 shadow-sm relative overflow-hidden">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-xl shadow border border-brand/20 flex-shrink-0">
                                    <x-heroicon-o-cpu-chip class="w-6 h-6 text-brand" />
                                </div>
                                <div>
                                    <h3 class="font-black text-brand text-base tracking-tight">Có thể là</h3>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">AI gợi ý ·
                                        {{ $aiMatches->count() }} hồ sơ tương đồng
                                    </p>
                                </div>
                            </div>
                            <p
                                class="text-[10px] text-slate-500 font-bold leading-relaxed flex items-start gap-1.5 bg-white/50 p-3 rounded-xl border border-brand/10">
                                <span><x-heroicon-o-exclamation-triangle class="w-4 h-4 inline" /></span>
                                <span>Kết quả AI chỉ mang tính định hướng, cần xác thực của gia đình và cơ quan chức
                                    năng.</span>
                            </p>
                            <div class="space-y-2">
                                @foreach ($aiMatches as $match)
                                    <x-missing.ai-match-card :match="$match" />
                                @endforeach
                            </div>
                        </div>
                    @elseif($case->primaryPhoto?->ai_status === 'processing')
                        <x-skeletons.ai-match-list :rows="2" data-ai-match-list-skeleton />
                    @elseif($case->primaryPhoto?->ai_status === 'failed')
                        <div class="border border-slate-200 rounded-2xl p-5 bg-slate-50 text-center space-y-2">
                            <div class="flex justify-center"><x-heroicon-o-exclamation-triangle class="w-10 h-10 text-slate-300" />
                            </div>
                            <p class="text-xs font-bold text-slate-500">Không thể xử lý AI cho ảnh này, vui lòng thử lại sau</p>
                        </div>
                    @else
                        {{-- Empty state: done nhưng không có match --}}
                        <div class="border border-brand/20 rounded-2xl p-5 bg-brand/5 text-center space-y-2">
                            <div class="flex justify-center"><x-heroicon-o-cpu-chip class="w-10 h-10 text-brand" /></div>
                            <p class="text-xs font-bold text-slate-500">Hệ thống đã quét nhưng chưa tìm thấy hồ sơ tương đồng nào
                                cho trường hợp này</p>
                        </div>
                    @endif
                @else
                    {{-- Guest CTA --}}
                    <div class="border border-brand/20 rounded-2xl p-5 bg-brand/5 text-center space-y-3">
                        <p class="text-2xl text-center"><x-heroicon-o-lock-closed class="w-6 h-6 text-slate-400" /></p>
                        <p class="text-sm font-bold text-slate-700">Đăng nhập để xem gợi ý AI</p>
                        <a href="{{ route('auth.login') }}"
                            class="inline-block bg-brand text-white text-xs font-black px-5 py-2.5 rounded-xl hover:bg-brand-dark transition-colors">
                            Đăng nhập ngay
                        </a>
                    </div>
                @endauth

                @livewire('missing.handover-suggestion-card', ['caseId' => $case->id])

                {{-- START: KHỐI ĐIỂM BÀN GIAO / TIẾP NHẬN --}}
                @if(isset($handoverPoints) && $handoverPoints->isNotEmpty())
                    <div
                        class="bg-white border border-slate-200/80 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-premium space-y-4">
                        {{-- Tiêu đề khối --}}
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                                    <x-heroicon-o-building-office-2 class="w-5 h-5" />
                                </div>
                                <div>
                                    <h4 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider">
                                        Điểm Tiếp Nhận & Bàn Giao
                                    </h4>
                                    <p class="text-xs text-slate-500">Các cơ quan/tổ chức hỗ trợ tiếp nhận người trong khu vực
                                    </p>
                                </div>
                            </div>
                            <span class="text-xs font-bold bg-slate-100 text-slate-600 px-2.5 py-1 rounded-full">
                                {{ $handoverPoints->count() }} địa điểm
                            </span>
                        </div>

                        {{-- Danh sách các điểm bàn giao --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($handoverPoints as $point)
                                <div
                                    class="p-3.5 bg-slate-50/80 hover:bg-slate-50 rounded-2xl border border-slate-200/60 transition-all duration-200 space-y-2">
                                    <div class="flex items-start justify-between gap-2">
                                        <h5 class="text-xs font-bold text-slate-800 line-clamp-1">
                                            {{ $point->name }}
                                        </h5>
                                        @if($point->type)
                                            <span
                                                class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-blue-50 text-blue-600 border border-blue-100 shrink-0">
                                                {{ $point->type }}
                                            </span>
                                        @endif
                                    </div>

                                    <p class="text-xs text-slate-600 font-medium flex items-start gap-1">
                                        <x-heroicon-o-map-pin class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" />
                                        <span>{{ $point->address }}</span>
                                    </p>

                                    @if($point->responsible_org_name)
                                        <p class="text-[11px] text-slate-500 font-medium flex items-center gap-1">
                                            <x-heroicon-o-user-group class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                            <span>{{ $point->responsible_org_name }}</span>
                                        </p>
                                    @endif

                                    @if($point->hotline_phone)
                                        <div class="pt-1 border-t border-slate-200/50 flex justify-end">
                                            <a href="tel:{{ $point->hotline_phone }}"
                                                class="inline-flex items-center gap-1 text-xs font-black text-primary hover:underline">
                                                <x-heroicon-o-phone class="w-3.5 h-3.5" />
                                                <span>Hotline: {{ $point->hotline_phone }}</span>
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                {{-- END: KHỐI ĐIỂM BÀN GIAO --}}
                
                <!-- CLUES SECTION -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-6 sm:p-8 space-y-6 shadow-premium">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <h3 class="font-black text-slate-900 text-lg tracking-tight">Manh mối từ cộng đồng <span
                                class="text-primary">({{ $case->clues->count() }})</span></h3>
                        <button onclick="document.getElementById('clue-box-form').classList.toggle('hidden')"
                            class="text-xs font-black text-primary hover:text-primary-dark transition-colors uppercase tracking-wider">
                            + Gửi manh mối mới
                        </button>
                    </div>

                    <!-- Interactive Clue Box Form (Livewire) -->
                    <div id="clue-box-form" class="hidden bg-slate-50 rounded-2xl p-5 border border-slate-200 space-y-4">
                        @livewire('gps-report-button', ['missingId' => $case->id])

                        @livewire('missing-child.clue-form', ['missingId' => $case->id])
                    </div>

                    <!-- Comment Thread List -->
                    <div class="space-y-6">

                        @forelse($case->clues as $clue)
                            <div class="flex gap-3.5 items-start">
                                @if ($clue->user && !$clue->is_anonymous)
                                    <div class="w-10 h-10 rounded-full overflow-hidden flex-shrink-0">
                                        <img src="{{ $clue->user->avatar_url }}" alt="{{ $clue->display_name }}"
                                            class="w-full h-full object-cover cursor-pointer js-lightbox"
                                            data-fullsrc="{{ $clue->user->avatar_url }}" />
                                    </div>
                                @else
                                    <div
                                        class="w-10 h-10 rounded-full {{ $clue->is_verified ? 'bg-brand/10 border border-brand/20 text-brand' : 'bg-slate-100 border border-slate-200 text-slate-500' }} flex-shrink-0 flex items-center justify-center font-bold text-sm">
                                        {{ $clue->initials }}
                                    </div>
                                @endif
                                <div class="flex-1 space-y-2">
                                    <div
                                        class="bg-slate-100 p-4 rounded-2xl inline-block max-w-full {{ $clue->is_verified ? 'border-l-4 border-success' : '' }}">
                                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                                            <span class="text-xs font-extrabold text-slate-900">{{ $clue->display_name }}</span>
                                            @if ($clue->is_verified)
                                                <span
                                                    class="bg-success-light text-success border border-success/35 text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full">Đã
                                                    xác minh ✓</span>
                                            @else
                                                <span
                                                    class="bg-amber-50 text-amber-700 border border-amber-200 text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full">Đang
                                                    xác minh</span>
                                            @endif
                                            <span
                                                class="text-[10px] text-slate-400 font-bold">{{ $clue->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-xs text-slate-600 leading-relaxed font-medium">
                                            {{ $clue->description }}
                                        </p>
                                        @if ($clue->image_path)
                                            <div class="mt-3">
                                                <img src="{{ asset('storage/' . $clue->image_path) }}" alt="Clue image"
                                                    class="max-h-48 rounded shadow-sm object-contain border cursor-pointer js-lightbox"
                                                    data-fullsrc="{{ asset('storage/' . $clue->image_path) }}" />
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-3 text-[10px] font-bold text-slate-400 pl-2">
                                        @if ($clue->lat && $clue->lng)
                                            @php $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . $clue->lat . ',' . $clue->lng; @endphp
                                            <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer"
                                                class="bg-primary/10 text-primary px-2.5 py-1 rounded-full inline-flex items-center gap-2">
                                                <span><x-heroicon-o-map-pin class="w-4 h-4 inline" /></span>
                                                <span>{{ number_format($clue->lat, 4) }},
                                                    {{ number_format($clue->lng, 4) }}</span>
                                            </a>
                                        @endif
                                        <button class="hover:text-primary transition-colors">Phản hồi</button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 font-semibold text-center py-4">Chưa có manh mối nào. Hãy là
                                người đầu tiên chia sẻ thông tin!</p>
                        @endforelse

                    </div>
                </div>

                {{-- Comment Section --}}
                <div class="bg-white border border-slate-200/80 rounded-2xl sm:rounded-3xl p-3 sm:p-6 shadow-premium">
                    @livewire('missing.comment-section', ['caseId' => $case->id])
                </div>
            </div>

            <!-- RIGHT SIDEBAR: 4 cols -->
            <div class="lg:col-span-4 space-y-3 sm:space-y-6 order-last min-w-0">

                <!-- GPS ACTION CARD — ẩn trên mobile (đã hiển thị ở trên), chỉ hiện từ lg -->
                <div
                    class="hidden lg:block bg-gradient-to-r from-primary to-primary-dark text-white rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-lg shadow-primary/20 space-y-3 sm:space-y-4 relative overflow-hidden">
                    <div
                        class="absolute top-[-20%] right-[-10%] w-32 h-32 bg-white/10 rounded-full blur-2xl pointer-events-none">
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="relative flex h-3 w-3">
                            <span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-white"></span>
                        </span>
                        <span class="font-extrabold text-sm tracking-widest uppercase">Bạn đang thấy bé
                            {{ Str::words($case->full_name, 1, '') }}?</span>
                    </div>
                    <p class="text-xs text-slate-100 leading-relaxed font-medium">
                        Nếu bạn đang đứng cạnh bé hoặc nhìn thấy bé ngay lúc này, hãy ấn nút dưới đây để chia sẻ toạ độ GPS
                        chính xác.
                    </p>
                    <button id="btn-share-gps" onclick="shareGPS()"
                        class="w-full bg-white text-primary font-black py-4 rounded-2xl hover:bg-slate-50 transition-premium text-sm shadow-md">
                        <x-heroicon-o-map-pin class="w-4 h-4 inline" /> Chia sẻ vị trí GPS tức thời
                    </button>
                </div>

                <!-- Contact block -->
                <div
                    class="bg-white border border-slate-200/80 rounded-2xl sm:rounded-3xl p-3 sm:p-6 shadow-premium space-y-3 sm:space-y-4">
                    <h4 class="font-extrabold text-slate-900 text-xs sm:text-sm uppercase tracking-wider">Thông tin liên
                        lạc</h4>
                    @guest
                        <p class="text-xs text-slate-400 leading-relaxed font-semibold">Số điện thoại liên hệ đang được ẩn.
                            Đăng nhập để xem thông tin liên hệ đã được người đăng cung cấp.</p>
                        <a href="{{ route('auth.login') }}"
                            class="block w-full bg-slate-900 text-white font-extrabold text-xs py-3.5 rounded-2xl text-center hover:bg-slate-800 shadow transition-colors uppercase tracking-widest">
                            Đăng nhập để liên hệ
                        </a>
                    @else
                        <div class="space-y-1.5 sm:space-y-2">
                            <p class="text-sm font-bold text-slate-900">{{ $case->contact_name }}</p>
                            <p class="text-xs text-slate-500">{{ $case->contact_relation }}</p>
                            @if (Auth::check())
                                @if ($case->contact_phone)
                                    <p class="text-sm font-extrabold text-slate-800">SĐT: <a href="tel:{{ $case->contact_phone }}"
                                            class="text-primary">{{ $case->contact_phone }}</a></p>
                                @else
                                    <p class="text-xs text-slate-400 leading-relaxed font-semibold">Số điện thoại chưa được
                                        cung cấp.</p>
                                @endif
                            @else
                                <p class="text-xs text-slate-400 leading-relaxed font-semibold">Liên hệ nội bộ sẽ được mở cho
                                    tài khoản đã đăng nhập.</p>
                            @endif
                        </div>
                    @endguest

                    <div
                        class="flex items-center gap-2 justify-center text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                        <span><x-heroicon-o-lock-closed class="w-6 h-6 text-slate-400" /></span> Thông tin liên hệ được bảo
                        vệ
                    </div>
                </div>

                <form class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-premium space-y-4"
                    onsubmit="return false;">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h4 class="font-extrabold text-slate-900 text-xs sm:text-sm uppercase tracking-wider">QR hồ sơ
                            </h4>
                            <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-0.5 sm:mt-1">Mở nhanh hồ sơ
                                bằng quét mã</p>
                        </div>
                        <span
                            class="text-[10px] font-black uppercase tracking-widest text-primary bg-primary/10 px-2.5 py-1 rounded-full">QR</span>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Đường dẫn QR</label>
                        <input id="qr-link" type="text" readonly value="{{ route('missing.qr', ['id' => $case->id]) }}"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs font-medium text-slate-600 focus:outline-none" />
                    </div>

                    <div class="rounded-2xl overflow-hidden border border-slate-200 bg-slate-50 p-4 flex justify-center">
                        <img src="{{ route('missing.qr', ['id' => $case->id]) }}" alt="QR hồ sơ {{ $case->full_name }}"
                            class="max-w-full h-auto" />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="{{ route('missing.qr', ['id' => $case->id]) }}" target="_blank"
                            class="text-center rounded-xl sm:rounded-2xl border border-slate-200 px-3 py-2.5 sm:py-3 text-xs font-extrabold text-slate-700 hover:bg-slate-50 transition-colors">
                            Mở QR
                        </a>
                        <button type="button"
                            onclick="navigator.clipboard.writeText(document.getElementById('qr-link').value); alert('Đã sao chép link QR!')"
                            class="rounded-xl sm:rounded-2xl bg-primary text-white px-3 py-2.5 sm:py-3 text-xs font-extrabold hover:bg-primary-dark transition-colors">
                            Sao chép
                        </button>
                    </div>

                    <p class="text-[10px] text-slate-400 font-semibold text-center uppercase tracking-wide">Quét mã để mở
                        hồ sơ nhanh</p>
                </form>

                <!-- E-01: Xuất PDF hồ sơ -->
                <a href="{{ route('missing.pdf', ['id' => $case->id]) }}"
                    class="flex items-center justify-center gap-2 bg-white border border-slate-200/80 rounded-3xl p-4 sm:p-5 shadow-premium text-sm font-extrabold text-slate-800 hover:bg-slate-50 hover:border-primary/30 transition-colors">
                    <x-heroicon-o-document-arrow-down class="w-5 h-5 text-primary" />
                    In / Xuất PDF hồ sơ
                </a>

                <!-- Interactive Mini map tracking location -->
                @php
                    $hasMissingCoordinates = is_numeric($case->lat)
                        && is_numeric($case->lng)
                        && (float) $case->lat >= -90
                        && (float) $case->lat <= 90
                        && (float) $case->lng >= -180
                        && (float) $case->lng <= 180;
                    $missingAddress = collect([
                        $case->address_detail,
                        $case->ward,
                        $case->district,
                        $case->province?->name,
                    ])->filter()->implode(', ');
                @endphp
                <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-premium space-y-4">
                    <h4 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider">Vị trí mất tích</h4>
                    @if ($hasMissingCoordinates)
                        <div class="relative h-44 rounded-2xl overflow-hidden border border-slate-200">
                            <div id="mini-map" class="map-container w-full h-full bg-slate-150" data-lat="{{ $case->lat }}"
                                data-lng="{{ $case->lng }}"></div>
                            {{-- C-01 hook: mini-map không cần widget/filter (theo DoD C-05), chỉ cần overlay chủ quyền hoạt
                            động đúng --}}
                            <div id="mini-map-sovereignty-overlay" class="map-sovereignty-overlay"></div>
                        </div>
                    @else
                        <div
                            class="flex min-h-28 items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 text-center">
                            <p class="text-xs font-semibold text-slate-500">Hồ sơ chưa có tọa độ bản đồ chính xác.</p>
                        </div>
                    @endif
                    <p class="text-[10px] text-slate-500 font-semibold text-center uppercase tracking-wide">
                        <x-heroicon-o-map-pin class="w-3.5 h-3.5 inline shrink-0" />
                        {{ $missingAddress ?: 'Chưa cập nhật địa chỉ mất tích' }}
                    </p>
                </div>

                <!-- Social sharing -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-premium space-y-4">
                    <h4 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider">Lan toả để giúp tìm con</h4>
                    @if ($case->qr_code_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($case->qr_code_path))
                        <div class="flex items-center gap-3">
                            <img src="{{ asset('storage/' . $case->qr_code_path) }}" alt="QR code"
                                class="w-20 h-20 rounded-lg border" />
                            <div class="flex-1">
                                <div class="text-xs text-slate-500 font-bold">QR Code hồ sơ</div>
                                <a href="{{ asset('storage/' . $case->qr_code_path) }}"
                                    download="{{ basename($case->qr_code_path) }}"
                                    class="inline-block mt-2 bg-slate-900 text-white text-xs font-extrabold py-2 px-3 rounded-xl">Tải
                                    QR Code</a>
                            </div>
                        </div>
                    @endif
                    <div class="grid grid-cols-3 gap-2">
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->url()) }}"
                            target="_blank" title="Chia sẻ lên Facebook"
                            class="flex items-center justify-center gap-1.5 bg-[#1877F2] text-white text-xs font-extrabold py-3 px-2 rounded-xl hover:bg-[#165ECC] transition-colors shadow-sm">
                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path
                                    d="M9 8H6v4h3v12h5V12h3.642l.358-4h-4V6.333c0-1.163.235-1.667 1.412-1.667H18V0h-3.933C10.169 0 9 1.635 9 4.667V8z" />
                            </svg>
                            <span>Facebook</span>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url={{ urlencode(request()->url()) }}&text={{ urlencode('Tìm bé ' . $case->full_name) }}"
                            target="_blank" title="Chia sẻ lên Twitter/X"
                            class="flex items-center justify-center gap-1.5 bg-black text-white text-xs font-extrabold py-3 px-2 rounded-xl hover:bg-slate-800 transition-colors shadow-sm">
                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path
                                    d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                            </svg>
                            <span>Twitter/X</span>
                        </a>
                        <a href="https://zalo.me/share/url?url={{ urlencode(request()->url()) }}&title={{ urlencode('Tìm bé ' . $case->full_name) }}"
                            target="_blank" title="Chia sẻ lên Zalo"
                            class="flex items-center justify-center gap-1.5 bg-[#0068FF] text-white text-xs font-extrabold py-3 px-2 rounded-xl hover:bg-[#0051CC] transition-colors shadow-sm">
                            <x-heroicon-o-chat-bubble-left-right class="w-4 h-4 shrink-0" />
                            <span>Zalo</span>
                        </a>
                    </div>
                    <button onclick="navigator.clipboard.writeText(window.location.href); alert('Đã sao chép link hồ sơ!')"
                        class="w-full border border-slate-200 text-slate-700 text-xs font-bold py-3 rounded-xl hover:bg-slate-50 transition-colors shadow-sm">
                        Sao chép đường dẫn hồ sơ
                    </button>
                </div>

            </div>
        </div>
    </main>

    <script>
        function shareGPS() {
            // mark a user gesture now (inline onclick may not update the capture listener early enough)
            try {
                window.__lastUserGestureAt = Date.now();
            } catch (e) { }

            if (navigator.geolocation) {
                var btn = document.getElementById('btn-share-gps');
                var oldHtml = null;
                if (btn) {
                    try {
                        oldHtml = btn.innerHTML;
                        btn.disabled = true;
                        btn.innerHTML = 'Đang lấy…';
                    } catch (e) { }
                }

                // Prefer calling the browser's native API exposed by the layout (avoids guard races)
                if (window.__nativeGeolocationGet) {
                    try {
                        window.__nativeGeolocationGet(function (pos) {
                            var lat = pos.coords.latitude;
                            var lng = pos.coords.longitude;
                            var box = document.getElementById('clue-box-form');
                            if (box) box.classList.remove('hidden');
                            try {
                                if (window.Livewire && typeof Livewire.emit === 'function') {
                                    Livewire.emit('setLocation', {
                                        lat: lat,
                                        lng: lng
                                    });
                                } else if (window.livewire && typeof livewire.emit === 'function') {
                                    livewire.emit('setLocation', {
                                        lat: lat,
                                        lng: lng
                                    });
                                }
                            } catch (e) { }
                            try {
                                var latInput = document.getElementById('clue-lat-input');
                                var lngInput = document.getElementById('clue-lng-input');
                                if (latInput) {
                                    latInput.value = lat;
                                    latInput.dispatchEvent(new Event('input', {
                                        bubbles: true
                                    }));
                                }
                                if (lngInput) {
                                    lngInput.value = lng;
                                    lngInput.dispatchEvent(new Event('input', {
                                        bubbles: true
                                    }));
                                }
                            } catch (e) { }
                            if (box) box.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                            // Show brief confirmation so user knows coords were captured
                            try {
                                alert('Đã lấy tọa độ: ' + lat.toFixed(6) + ', ' + lng.toFixed(6) +
                                    '\nBạn có thể chỉnh mô tả và gửi manh mối.');
                            } catch (e) { }

                            // Removed automatic immediate POST sharing — only populate form and emit Livewire.
                            if (btn) {
                                try {
                                    btn.disabled = false;
                                    if (oldHtml !== null) btn.innerHTML = oldHtml;
                                } catch (e) { }
                            }
                        }, function (err) {
                            alert('Không thể lấy vị trí GPS: ' + (err.message || 'Lỗi không xác định'));
                            if (btn) {
                                try {
                                    btn.disabled = false;
                                    if (oldHtml !== null) btn.innerHTML = oldHtml;
                                } catch (e) { }
                            }
                        }, {
                            enableHighAccuracy: true,
                            timeout: 10000
                        });
                        return;
                    } catch (e) {
                        // fall back to normal flow below
                    }
                }

                // fallback: normal permission-based flow
                navigator.geolocation.getCurrentPosition(function (pos) {
                    var lat = pos.coords.latitude;
                    var lng = pos.coords.longitude;

                    var box = document.getElementById('clue-box-form');
                    if (box) box.classList.remove('hidden');

                    try {
                        if (window.Livewire && typeof Livewire.emit === 'function') {
                            Livewire.emit('setLocation', {
                                lat: lat,
                                lng: lng
                            });
                        } else if (window.livewire && typeof livewire.emit === 'function') {
                            livewire.emit('setLocation', {
                                lat: lat,
                                lng: lng
                            });
                        }
                    } catch (e) { }

                    try {
                        var latInput = document.getElementById('clue-lat-input');
                        var lngInput = document.getElementById('clue-lng-input');
                        if (latInput) {
                            latInput.value = lat;
                            latInput.dispatchEvent(new Event('input', {
                                bubbles: true
                            }));
                        }
                        if (lngInput) {
                            lngInput.value = lng;
                            lngInput.dispatchEvent(new Event('input', {
                                bubbles: true
                            }));
                        }
                    } catch (e) { }

                    if (box) box.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    alert('Đã lấy tọa độ: ' + lat.toFixed(6) + ', ' + lng.toFixed(6) +
                        '\nBạn có thể chỉnh mô tả và gửi manh mối.');
                }, function (err) {
                    alert('Không thể lấy vị trí GPS: ' + (err.message || 'Lỗi không xác định'));
                    if (btn) {
                        try {
                            btn.disabled = false;
                            if (oldHtml !== null) btn.innerHTML = oldHtml;
                        } catch (e) { }
                    }
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000
                });

                setTimeout(function () {
                    if (btn) {
                        try {
                            btn.disabled = false;
                            if (oldHtml !== null) btn.innerHTML = oldHtml;
                        } catch (e) { }
                    }
                }, 12000);
            } else {
                alert('Trình duyệt không hỗ trợ GPS.');
            }
        }

        function doGetPosition() {
            navigator.geolocation.getCurrentPosition(function (pos) {
                var lat = pos.coords.latitude;
                var lng = pos.coords.longitude;

                // Open the clue form
                var box = document.getElementById('clue-box-form');
                if (box) box.classList.remove('hidden');

                // Emit to Livewire listener `setLocation`
                try {
                    if (window.Livewire && typeof Livewire.emit === 'function') {
                        Livewire.emit('setLocation', {
                            lat: lat,
                            lng: lng
                        });
                    } else if (window.livewire && typeof livewire.emit === 'function') {
                        livewire.emit('setLocation', {
                            lat: lat,
                            lng: lng
                        });
                    }
                } catch (e) {
                    // ignore
                }

                // Also populate any hidden inputs in the clue form for non-Livewire consumers
                try {
                    var latInput = document.getElementById('clue-lat-input');
                    var lngInput = document.getElementById('clue-lng-input');
                    if (latInput) {
                        latInput.value = lat;
                        latInput.dispatchEvent(new Event('input', {
                            bubbles: true
                        }));
                    }
                    if (lngInput) {
                        lngInput.value = lng;
                        lngInput.dispatchEvent(new Event('input', {
                            bubbles: true
                        }));
                    }
                } catch (e) {
                    // ignore
                }

                // Scroll to form for visibility
                if (box) box.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

                // Brief confirmation
                alert('Đã lấy tọa độ: ' + lat.toFixed(6) + ', ' + lng.toFixed(6) +
                    '\nBạn có thể chỉnh mô tả và gửi manh mối.');
            }, function (err) {
                alert('Không thể lấy vị trí GPS: ' + (err.message || 'Lỗi không xác định'));
                if (btn) {
                    try {
                        btn.disabled = false;
                        if (oldHtml !== null) btn.innerHTML = oldHtml;
                    } catch (e) { }
                }
            }, {
                enableHighAccuracy: true,
                timeout: 10000
            });
        }

        // Use Permissions API to check state
        if (navigator.permissions && navigator.permissions.query) {
            navigator.permissions.query({
                name: 'geolocation'
            }).then(function (result) {
                if (result.state === 'denied') {
                    if (btn) {
                        try {
                            btn.disabled = false;
                            if (oldHtml !== null) btn.innerHTML = oldHtml;
                        } catch (e) { }
                    }
                    alert(
                        'Quyền vị trí đã bị chặn cho trang này. Vui lòng cho phép vị trí trong cài đặt trình duyệt và thử lại.'
                    );
                    return;
                }
                // allow next geolocation call (user gesture)
                if (window.allowGeolocationForNextCall) window.allowGeolocationForNextCall();
                doGetPosition();
            }).catch(function () {
                doGetPosition();
            });
        } else {
            doGetPosition();
        }
    </script>

    {{-- Lightbox modal for avatar and clue images --}}
    <div id="lightbox-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/90 p-4">
        <button id="lightbox-close" aria-label="Đóng"
            class="absolute top-4 right-4 text-white bg-white/10 hover:bg-white/20 rounded-full w-10 h-10 flex items-center justify-center text-xl font-bold z-30">✕</button>
        <div id="lightbox-backdrop" class="absolute inset-0" style="cursor:default"></div>

        <div class="relative z-20 flex items-center justify-center max-h-[85vh] max-w-[90vw]">
            <div class="absolute -bottom-16 left-1/2 -translate-x-1/2 flex items-center gap-2 z-40">
                <button id="lb-zoom-out"
                    class="bg-white/10 text-white rounded-full w-10 h-10 flex items-center justify-center">−</button>
                <button id="lb-zoom-reset"
                    class="bg-white/10 text-white rounded-full w-10 h-10 flex items-center justify-center">1×</button>
                <button id="lb-zoom-in"
                    class="bg-white/10 text-white rounded-full w-10 h-10 flex items-center justify-center">+</button>
            </div>

            <img id="lightbox-img" src="" alt=""
                class="max-h-[85vh] max-w-[90vw] object-contain rounded-lg shadow-2xl z-10 touch-none cursor-grab"
                style="transform-origin:center center; transition: transform 120ms ease;" />
        </div>
    </div>

    <script>
        (function () {
            function openLightbox(src, alt) {
                var modal = document.getElementById('lightbox-modal');
                var img = document.getElementById('lightbox-img');
                img.src = src || '';
                img.alt = alt || '';
                modal.classList.remove('hidden');
            }

            function closeLightbox() {
                var modal = document.getElementById('lightbox-modal');
                var img = document.getElementById('lightbox-img');
                img.src = '';
                modal.classList.add('hidden');
            }

            document.addEventListener('click', function (e) {
                var el = e.target.closest('.js-lightbox');
                if (el) {
                    e.preventDefault();
                    var src = el.dataset.fullsrc || el.src;
                    var alt = el.alt || '';
                    openLightbox(src, alt);
                }
            });

            document.getElementById('lightbox-close').addEventListener('click', closeLightbox);
            document.getElementById('lightbox-backdrop').addEventListener('click', function (e) {
                // close if clicking outside the image area (backdrop)
                closeLightbox();
            });

            // Zoom/drag state
            var lbImg = document.getElementById('lightbox-img');
            var scale = 1;
            var minScale = 1;
            var maxScale = 4;
            var startX = 0,
                startY = 0,
                originX = 0,
                originY = 0,
                isPanning = false;

            function applyTransform() {
                lbImg.style.transform = 'translate(' + originX + 'px,' + originY + 'px) scale(' + scale + ')';
            }

            document.getElementById('lb-zoom-in').addEventListener('click', function () {
                scale = Math.min(maxScale, +(scale + 0.25).toFixed(2));
                applyTransform();
            });
            document.getElementById('lb-zoom-out').addEventListener('click', function () {
                scale = Math.max(minScale, +(scale - 0.25).toFixed(2));
                if (scale === 1) {
                    originX = 0;
                    originY = 0;
                }
                applyTransform();
            });
            document.getElementById('lb-zoom-reset').addEventListener('click', function () {
                scale = 1;
                originX = 0;
                originY = 0;
                applyTransform();
            });

            // wheel zoom
            lbImg.addEventListener('wheel', function (e) {
                if (!e.deltaY) return;
                e.preventDefault();
                var delta = e.deltaY > 0 ? -0.15 : 0.15;
                scale = Math.min(maxScale, Math.max(minScale, +(scale + delta).toFixed(2)));
                applyTransform();
            }, {
                passive: false
            });

            // double click toggle
            lbImg.addEventListener('dblclick', function (e) {
                if (scale === 1) {
                    scale = 2;
                } else {
                    scale = 1;
                    originX = 0;
                    originY = 0;
                }
                applyTransform();
            });

            // pointer drag for panning when zoomed
            lbImg.addEventListener('pointerdown', function (e) {
                if (scale <= 1) return;
                isPanning = true;
                lbImg.setPointerCapture(e.pointerId);
                startX = e.clientX;
                startY = e.clientY;
            });
            lbImg.addEventListener('pointermove', function (e) {
                if (!isPanning) return;
                var dx = e.clientX - startX;
                var dy = e.clientY - startY;
                startX = e.clientX;
                startY = e.clientY;
                originX += dx;
                originY += dy;
                applyTransform();
            });
            lbImg.addEventListener('pointerup', function (e) {
                if (isPanning) {
                    isPanning = false;
                    try {
                        lbImg.releasePointerCapture(e.pointerId);
                    } catch (e) { }
                }
            });
            lbImg.addEventListener('pointercancel', function () {
                isPanning = false;
            });

            document.addEventListener('keydown', function (e) {
                if (!document.getElementById('lightbox-modal')) return;
                if (e.key === 'Escape') closeLightbox();
            });
        })();
    </script>
    <script>
        // Debug helper: ensure GPS button clicks are detected and surface errors
        document.addEventListener('DOMContentLoaded', function () {
            var btn = document.getElementById('btn-share-gps');
            if (!btn) return;
            btn.addEventListener('click', function () {
                try {
                    console.log('[DEBUG] GPS button clicked');
                } catch (e) {
                    // ignore
                }
            });
        });
    </script>
@endsection

@push('scripts')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="{{ asset('js/map/vn-sovereignty-layer.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const mapEl = document.getElementById('mini-map');
            if (!mapEl) return;
            const lat = parseFloat(mapEl.dataset.lat);
            const lng = parseFloat(mapEl.dataset.lng);
            if (isNaN(lat) || isNaN(lng)) return;
            if (!window.L || typeof window.initVnSovereigntyLayer !== 'function') {
                console.error('Không thể tải thư viện bản đồ vị trí mất tích.');
                return;
            }

            const map = L.map('mini-map', {
                center: [lat, lng],
                zoom: 15,
                zoomControl: false,
                scrollWheelZoom: false,
            });

            initVnSovereigntyLayer(map);
            // Theo yêu cầu: bản đồ luôn dùng tile sáng, không đổi theo theme.
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
            const icon = L.divIcon({
                className: '',
                html: '<div style="width:20px;height:20px;background:#FF3B30;border:3px solid white;border-radius:50%;box-shadow:0 2px 8px rgba(255,59,48,0.5)"></div>',
                iconSize: [20, 20],
                iconAnchor: [10, 10],
            });
            L.marker([lat, lng], { icon: icon }).addTo(map)
                .bindPopup(`
                                        <div class='font-bold text-xs p-1 text-slate-900 flex items-center gap-1.5'>
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 text-primary">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                            </svg>
                                            Vị trí mất tích ước tính
                                        </div>
                                    `)
                .openPopup();
        });
    </script>
@endpush