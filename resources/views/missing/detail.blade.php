@extends('layouts.app')
@section('title', $case->full_name . ' — Chi tiết hồ sơ')

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
                    @php
                        $bannerConfig = match ($case->status) {
                            'FOUND' => [
                                'bg' => 'bg-success/5 border-success/10',
                                'dot' => 'bg-success',
                                'text' => 'text-success',
                                'label' => '✅ BÉ ĐÃ ĐƯỢC TÌM THẤY — CẢM ƠN CỘNG ĐỒNG!',
                                'badge' => 'bg-success',
                                'badgeText' => 'Đã đoàn tụ',
                            ],
                            'PENDING' => [
                                'bg' => 'bg-amber-50 border-amber-100',
                                'dot' => 'bg-amber-400',
                                'text' => 'text-amber-700',
                                'label' => '⏳ HỒ SƠ ĐANG CHỜ KIỂM DUYỆT',
                                'badge' => 'bg-amber-400',
                                'badgeText' => 'Chờ duyệt',
                            ],
                            'REJECTED' => [
                                'bg' => 'bg-slate-50 border-slate-100',
                                'dot' => 'bg-slate-400',
                                'text' => 'text-slate-600',
                                'label' => '❌ HỒ SƠ KHÔNG ĐƯỢC PHÊ DUYỆT',
                                'badge' => 'bg-slate-400',
                                'badgeText' => 'Từ chối',
                            ],
                            default => [
                                'bg' => 'bg-primary/5 border-primary/10',
                                'dot' => 'bg-primary',
                                'text' => 'text-primary',
                                'label' => '🚨 ĐANG CẦN TÌM KHẨN CẤP TRÊN TOÀN QUỐC',
                                'badge' => 'bg-primary',
                                'badgeText' => null,
                            ],
                        };
                    @endphp

                    <div
                        class="{{ $bannerConfig['bg'] }} border-b px-4 sm:px-6 py-2.5 sm:py-4 flex items-center justify-between gap-2 sm:gap-3">
                        <div class="flex items-center gap-2 min-w-0 flex-1 overflow-hidden">
                            <span
                                class="w-2.5 h-2.5 flex-shrink-0 {{ $bannerConfig['dot'] }} rounded-full {{ $case->status === 'APPROVED' ? 'animate-ping' : '' }}"></span>
                            <span
                                class="{{ $bannerConfig['text'] }} font-extrabold text-xs sm:text-sm tracking-tight truncate">
                                {{ $bannerConfig['label'] }}
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
                            <div class="w-20 sm:w-40 flex-shrink-0" x-data="{
                                open: false,
                                current: 0,
                                photos: {{ Js::from($case->detail_photo_urls) }},
                                prev() { this.current = (this.current - 1 + this.photos.length) % this.photos.length },
                                next() { this.current = (this.current + 1) % this.photos.length }
                            }"
                                @keydown.left.window="if(open) prev()" @keydown.right.window="if(open) next()"
                                @keydown.escape.window="open = false">

                                <div class="relative overflow-hidden rounded-xl sm:rounded-2xl border-2 border-slate-100 shadow bg-slate-900 group aspect-square cursor-pointer"
                                    @click="open = true; current = 0">
                                    <img src="{{ $case->detail_primary_photo_url ?? $case->thumb_url }}"
                                        class="w-full h-full object-cover transform group-hover:scale-105 transition-premium"
                                        alt="{{ $case->full_name }}" />
                                    @if (count($case->detail_photo_urls) > 1)
                                        <div
                                            class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                            <span class="text-white text-[10px] font-bold bg-black/60 px-2 py-1 rounded-lg">
                                                📷 {{ count($case->detail_photo_urls) }} ảnh
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                @if (count($case->detail_photo_urls) > 1)
                                    <div class="hidden sm:flex gap-1.5 mt-2 overflow-x-auto pb-1">
                                        @foreach ($case->detail_photo_urls as $i => $photoUrl)
                                            <img src="{{ $photoUrl }}"
                                                class="w-12 h-12 object-cover rounded-lg border-2 border-slate-200 cursor-pointer hover:border-red-400 flex-shrink-0"
                                                @click="open = true; current = {{ $i }}"
                                                alt="Ảnh {{ $i + 1 }}" />
                                        @endforeach
                                    </div>
                                @endif

                                @if ($case->primaryPhoto)
                                    <p
                                        class="text-center text-[9px] sm:text-[10px] text-slate-400 font-bold mt-1.5 sm:mt-2.5">
                                        Ảnh chụp rõ nhất
                                        {{ $case->primaryPhoto->updated_at?->format('n/Y') ?? $case->primaryPhoto->created_at->format('n/Y') }}
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
                                        <button @click="open = false"
                                            class="absolute top-4 right-4 text-white bg-white/20 hover:bg-white/40 rounded-full w-10 h-10 flex items-center justify-center text-xl font-bold z-10">✕</button>
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

                            <div class="flex-1 min-w-0 sm:hidden">
                                <div class="flex items-start justify-between gap-1.5">
                                    <div class="min-w-0 flex-1">
                                        <h1
                                            class="text-base font-black text-slate-900 tracking-tight leading-tight break-words">
                                            {{ $case->full_name }}</h1>
                                        <p class="text-[10px] text-slate-400 font-bold mt-1">Mã: <span
                                                class="font-mono text-slate-700">#{{ $case->case_code }}</span></p>
                                        <div
                                            class="mt-1.5 inline-flex items-center gap-1 text-[9px] font-black uppercase tracking-wider bg-slate-100 text-slate-600 px-2.5 py-1 rounded-full">
                                            <span>👁</span>
                                            <span>{{ number_format($case->view_count) }} lượt xem</span>
                                        </div>
                                    </div>
                                    <button title="Sao chép liên kết"
                                        onclick="navigator.clipboard.writeText(window.location.href); alert('Đã sao chép link hồ sơ!')"
                                        class="p-2 rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors shadow-sm flex-shrink-0">
                                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="hidden sm:block flex-1 min-w-0 space-y-4 w-full">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <h1
                                            class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight leading-tight break-words">
                                            {{ $case->full_name }}</h1>
                                        <p class="text-[10px] text-slate-400 font-bold mt-2">Mã vụ việc: <span
                                                class="font-mono text-slate-700">#{{ $case->case_code }}</span></p>
                                        <div
                                            class="mt-3 inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-600 px-3 py-1.5 rounded-full">
                                            <span>👁</span>
                                            <span>{{ number_format($case->view_count) }} lượt xem</span>
                                        </div>
                                    </div>
                                    <button title="Sao chép liên kết"
                                        onclick="navigator.clipboard.writeText(window.location.href); alert('Đã sao chép link hồ sơ!')"
                                        class="p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors shadow-sm flex-shrink-0">
                                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                        </svg>
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
                                        <div class="font-extrabold text-slate-800 text-sm mt-0.5">
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
                                        <div class="font-extrabold text-slate-800 text-sm mt-0.5 break-words">
                                            {{ collect([$case->address_detail, $case->ward, $case->district, $case->province?->name])->filter()->implode(', ') ?:'—' }}
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
                                        <div class="min-w-0">
                                            <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Thể
                                                trạng</span>
                                            <div class="font-extrabold text-slate-800 text-sm mt-0.5">
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
                                            <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Trang
                                                phục khi lạc</span>
                                            <div class="font-extrabold text-primary text-sm mt-0.5 break-words">
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
                                    {{ collect([$case->address_detail, $case->ward, $case->district, $case->province?->name])->filter()->implode(', ') ?:'—' }}
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
                            <div class="border-t border-slate-100 pt-3 sm:pt-6 space-y-2 sm:space-y-3">
                                <h4 class="font-extrabold text-slate-900 text-xs sm:text-sm uppercase tracking-wider">Mô tả
                                    đặc điểm cụ thể</h4>
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
                        📍 Chia sẻ vị trí GPS tức thời
                    </button>
                </div>

                <!-- AI FACE SCANNER WIDGET -->
                <div
                    class="bg-gradient-to-br from-brand/5 via-brand/10 to-brand-light border border-brand/20 rounded-2xl sm:rounded-3xl p-3 sm:p-6 lg:p-8 space-y-3 sm:space-y-6 shadow-sm relative overflow-hidden">
                    <div
                        class="absolute right-[-5%] bottom-[-5%] w-48 h-48 bg-brand/10 rounded-full blur-3xl pointer-events-none">
                    </div>
                    <div class="flex items-start gap-3 sm:gap-4">
                        <div
                            class="w-10 h-10 sm:w-12 sm:h-12 bg-white rounded-xl sm:rounded-2xl flex items-center justify-center text-xl sm:text-2xl shadow border border-brand/20 flex-shrink-0 animate-pulse">
                            🤖</div>
                        <div class="space-y-0.5 sm:space-y-1 min-w-0">
                            <h3 class="font-black text-brand text-sm sm:text-lg tracking-tight">AI Nhận Diện & Đối Chiếu
                                Gương Mặt</h3>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Hệ thống phân tích tự
                                động · Cập nhật: {{ now()->format('H:i') }} hôm nay</p>
                        </div>
                    </div>
                    <p
                        class="text-[10px] text-slate-500 font-bold leading-relaxed flex items-start gap-1.5 bg-white/50 p-3 sm:p-3.5 rounded-xl border border-brand/10">
                        <span class="flex-shrink-0">⚠️</span>
                        <span>LƯU Ý PHÁP LÝ: Kết quả phân tích AI chỉ mang tính định hướng. Quyết định cuối cùng cần có sự
                            xác thực của gia đình và cơ quan chức năng.</span>
                    </p>
                </div>

                <!-- CLUES SECTION -->
                <div
                    class="bg-white border border-slate-200/80 rounded-2xl sm:rounded-3xl p-3 sm:p-6 lg:p-8 space-y-4 sm:space-y-6 shadow-premium">
                    @livewire('missing.clue-form', ['missingId' => $case->id])
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
                    <button onclick="shareGPS()"
                        class="w-full bg-white text-primary font-black py-4 rounded-2xl hover:bg-slate-50 transition-premium text-sm shadow-md">
                        📍 Chia sẻ vị trí GPS tức thời
                    </button>
                </div>

                <!-- Contact block -->
                <div
                    class="bg-white border border-slate-200/80 rounded-2xl sm:rounded-3xl p-3 sm:p-6 shadow-premium space-y-3 sm:space-y-4">
                    <h4 class="font-extrabold text-slate-900 text-xs sm:text-sm uppercase tracking-wider">Thông tin liên
                        lạc</h4>
                    @guest
                        <p class="text-xs text-slate-400 leading-relaxed font-semibold">Số điện thoại liên hệ đang được ẩn.
                            Đăng nhập để mở tính năng chat nội bộ và kết nối với gia đình.</p>
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
                                    <p class="text-sm font-extrabold text-slate-800">SĐT: <a
                                            href="tel:{{ $case->contact_phone }}"
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
                        class="flex items-center gap-2 justify-center text-[9px] sm:text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                        <span>🔒</span> Thông tin liên hệ được bảo vệ
                    </div>
                </div>

                <!-- QR Card -->
                <form
                    class="bg-white border border-slate-200/80 rounded-2xl sm:rounded-3xl p-3 sm:p-6 shadow-premium space-y-3 sm:space-y-4"
                    onsubmit="return false;">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h4 class="font-extrabold text-slate-900 text-xs sm:text-sm uppercase tracking-wider">QR hồ sơ
                            </h4>
                            <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-0.5 sm:mt-1">Mở nhanh hồ sơ
                                bằng quét mã</p>
                        </div>
                        <span
                            class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-primary bg-primary/10 px-2 sm:px-2.5 py-1 rounded-full">QR</span>
                    </div>
                    <div class="space-y-1.5 sm:space-y-2">
                        <label class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-slate-400">Đường
                            dẫn QR</label>
                        <input id="qr-link" type="text" readonly
                            value="{{ route('missing.qr', ['id' => $case->id]) }}"
                            class="w-full rounded-xl sm:rounded-2xl border border-slate-200 bg-slate-50 px-3 sm:px-4 py-2.5 sm:py-3 text-xs font-medium text-slate-600 focus:outline-none" />
                    </div>
                    <div
                        class="rounded-xl sm:rounded-2xl overflow-hidden border border-slate-200 bg-slate-50 p-3 sm:p-4 flex justify-center">
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
                    <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold text-center uppercase tracking-wide">
                        Quét mã để mở hồ sơ nhanh</p>
                </form>

                <!-- Interactive Mini map -->
                @if ($case->lat && $case->lng)
                    <div
                        class="bg-white border border-slate-200/80 rounded-2xl sm:rounded-3xl p-3 sm:p-6 shadow-premium space-y-2.5 sm:space-y-4">
                        <h4 class="font-extrabold text-slate-900 text-xs sm:text-sm uppercase tracking-wider">Vị trí mất
                            tích</h4>
                        <div id="mini-map"
                            class="h-36 sm:h-44 bg-slate-150 rounded-xl sm:rounded-2xl overflow-hidden border border-slate-200 z-10"
                            data-lat="{{ $case->lat }}" data-lng="{{ $case->lng }}"></div>
                        <p
                            class="text-[9px] sm:text-[10px] text-slate-400 font-semibold text-center uppercase tracking-wide break-words">
                            📍
                            {{ collect([$case->address_detail, $case->ward, $case->district, $case->province?->name])->filter()->implode(', ') }}
                        </p>
                    </div>
                @endif

                <!-- Social sharing -->
                <div
                    class="bg-white border border-slate-200/80 rounded-2xl sm:rounded-3xl p-3 sm:p-6 shadow-premium space-y-3 sm:space-y-4">
                    <h4 class="font-extrabold text-slate-900 text-xs sm:text-sm uppercase tracking-wider">Lan toả để giúp
                        tìm con</h4>
                    @if ($case->qr_code_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($case->qr_code_path))
                        <div class="flex items-center gap-3">
                            <img src="{{ asset('storage/' . $case->qr_code_path) }}" alt="QR code"
                                class="w-16 sm:w-20 h-16 sm:h-20 rounded-lg border" />
                            <div class="flex-1 min-w-0">
                                <div class="text-xs text-slate-500 font-bold">QR Code hồ sơ</div>
                                <a href="{{ asset('storage/' . $case->qr_code_path) }}"
                                    download="{{ basename($case->qr_code_path) }}"
                                    class="inline-block mt-2 bg-slate-900 text-white text-xs font-extrabold py-2 px-3 rounded-xl">Tải
                                    QR Code</a>
                            </div>
                        </div>
                    @endif
                    <div class="grid grid-cols-2 gap-2">
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->url()) }}"
                            target="_blank"
                            class="bg-[#1877F2] text-white text-xs font-extrabold py-3 px-4 rounded-xl hover:bg-[#165ECC] transition-colors shadow-sm text-center">
                            Facebook
                        </a>
                        <a href="https://zalo.me/share/url?url={{ urlencode(request()->url()) }}&title={{ urlencode('Tìm bé ' . $case->full_name) }}"
                            target="_blank"
                            class="bg-[#0068FF] text-white text-xs font-extrabold py-3 px-4 rounded-xl hover:bg-[#0051CC] transition-colors shadow-sm text-center">
                            Zalo
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
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(pos) {
                    alert('Tọa độ của bạn: ' + pos.coords.latitude + ', ' + pos.coords.longitude +
                        '\nTính năng gửi GPS sẽ có ở Sprint 2.');
                });
            } else {
                alert('Trình duyệt không hỗ trợ GPS.');
            }
        }
    </script>
@endsection

@push('scripts')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mapEl = document.getElementById('mini-map');
            if (!mapEl) return;
            const lat = parseFloat(mapEl.dataset.lat);
            const lng = parseFloat(mapEl.dataset.lng);
            if (isNaN(lat) || isNaN(lng)) return;
            const map = L.map('mini-map', {
                center: [lat, lng],
                zoom: 15,
                zoomControl: false,
                scrollWheelZoom: false,
            });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);
            const icon = L.divIcon({
                className: '',
                html: '<div style="width:20px;height:20px;background:#FF3B30;border:3px solid white;border-radius:50%;box-shadow:0 2px 8px rgba(255,59,48,0.5)"></div>',
                iconSize: [20, 20],
                iconAnchor: [10, 10],
            });
            L.marker([lat, lng], {
                icon
            }).addTo(map).bindPopup('📍 Vị trí mất tích').openPopup();
        });
    </script>
@endpush
