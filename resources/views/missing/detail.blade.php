@extends('layouts.app')
@section('title', $case->full_name . ' — Chi tiết hồ sơ')

@section('content')
    <!-- BREADCRUMB -->
    <div class="max-w-7xl mx-auto px-4 py-4">
        <nav class="text-xs font-bold text-slate-400 flex gap-2 items-center uppercase tracking-wider">
            <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Trang chủ</a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('missing.index') }}" class="hover:text-primary transition-colors">Hồ sơ mất tích</a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-800">{{ $case->full_name }}</span>
        </nav>
    </div>

    <!-- MAIN CONTENT GRID -->
    <main class="max-w-7xl mx-auto px-4 pb-20">
        <div class="grid lg:grid-cols-12 gap-8 items-start">

            <!-- LEFT: 8 cols -->
            <div class="lg:col-span-8 space-y-8">

                <!-- Primary Profile Card -->
                <div class="bg-white border border-slate-200/80 rounded-3xl overflow-hidden shadow-premium">
                    <!-- Status Banner -->
                    <div
                        class="bg-primary/5 border-b border-primary/10 px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 bg-primary rounded-full animate-ping"></span>
                            <span class="text-primary font-extrabold text-sm tracking-tight">
                                @if ($case->status === 'MATCHED')
                                    ✅ BÉ ĐÃ ĐƯỢC TÌM THẤY
                                @else
                                    🚨 ĐANG CẦN TÌM KHẨN CẤP TRÊN TOÀN QUỐC
                                @endif
                            </span>
                        </div>
                        @if ($case->missing_date)
                            <span
                                class="bg-primary text-white text-[10px] font-black uppercase tracking-wider px-3 py-1.5 rounded-full shadow-glow-sos self-start sm:self-auto">
                                Mất tích {{ (int) -now()->diffInDays($case->missing_date) }} ngày
                            </span>
                        @endif
                    </div>

                    <!-- Profile Grid -->
                    <div class="p-6 sm:p-8 space-y-6">
                        <div class="flex flex-col sm:flex-row gap-8 items-start">

                            <!-- Avatar photo container + Gallery -->
                            <div class="w-full sm:w-44 flex-shrink-0" x-data="{
                                open: false,
                                current: 0,
                                photos: {{ Js::from($case->detail_photo_urls) }},
                                prev() { this.current = (this.current - 1 + this.photos.length) % this.photos.length },
                                next() { this.current = (this.current + 1) % this.photos.length }
                            }"
                                @keydown.left.window="if(open) prev()" @keydown.right.window="if(open) next()"
                                @keydown.escape.window="open = false">

                                {{-- Ảnh chính --}}
                                <div class="relative overflow-hidden rounded-2xl border-4 border-slate-100 shadow-md bg-slate-900 group aspect-square sm:aspect-auto cursor-pointer"
                                    @click="open = true; current = 0">
                                    <img src="{{ $case->detail_primary_photo_url ?? $case->thumb_url }}"
                                        class="w-full h-full sm:h-44 object-cover transform group-hover:scale-105 transition-premium"
                                        alt="{{ $case->full_name }}" />
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
                                                @click="open = true; current = {{ $i }}"
                                                alt="Ảnh {{ $i + 1 }}" />
                                        @endforeach
                                    </div>
                                @endif

                                @if ($case->primaryPhoto)
                                    <p class="text-center text-[10px] text-slate-400 font-bold mt-2.5">
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

                                        {{-- Prev --}}
                                        <button x-show="photos.length > 1" @click="prev"
                                            class="absolute left-4 text-white bg-white/20 hover:bg-white/40 rounded-full w-10 h-10 flex items-center justify-center text-xl z-10">
                                            ‹
                                        </button>

                                        {{-- Image --}}
                                        <img :src="photos[current]"
                                            class="max-h-[85vh] max-w-[90vw] object-contain rounded-lg shadow-2xl"
                                            :alt="'Ảnh ' + (current + 1)" />

                                        {{-- Next --}}
                                        <button x-show="photos.length > 1" @click="next"
                                            class="absolute right-4 text-white bg-white/20 hover:bg-white/40 rounded-full w-10 h-10 flex items-center justify-center text-xl z-10">
                                            ›
                                        </button>

                                        {{-- Thumbnail strip --}}
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

                            <!-- Key Facts -->
                            <div class="flex-1 space-y-4 w-full">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h1
                                            class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight leading-none">
                                            {{ $case->full_name }}</h1>
                                        <p class="text-xs text-slate-400 font-bold mt-2">Mã vụ việc: <span
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
                                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Info matrix -->
                                <div class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm border-t border-slate-100 pt-4">
                                    <div>
                                        <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Tuổi</span>
                                        <div class="font-extrabold text-slate-800 mt-0.5">
                                            {{ $case->age }} tuổi
                                            @if ($case->dob)
                                                (Sinh năm {{ $case->dob->year }})
                                            @endif
                                        </div>
                                    </div>
                                    <div>
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
                                    <div>
                                        <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Khu vực lạc
                                            mất</span>
                                        <div class="font-extrabold text-slate-800 mt-0.5">
                                            {{ collect([$case->address_detail, $case->ward, $case->district, $case->province?->name])->filter()->implode(', ') ?:'—' }}
                                        </div>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Thời gian bị
                                            lạc</span>
                                        <div class="font-extrabold text-slate-800 mt-0.5">
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
                            </div>
                        </div>

                        <!-- Detailed Description Box -->
                        @if ($case->circumstances || $case->special_marks)
                            <div class="border-t border-slate-100 pt-6 space-y-3">
                                <h4 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider">Mô tả đặc điểm cụ
                                    thể
                                </h4>
                                <p
                                    class="text-xs text-slate-500 leading-relaxed bg-slate-50 p-4 rounded-2xl border border-slate-150 font-medium">
                                    {{ $case->circumstances ?? $case->special_marks }}
                                </p>
                            </div>
                        @endif

                    </div>
                </div>

                <!-- AI FACE SCANNER WIDGET -->
                <div
                    class="bg-gradient-to-br from-brand/5 via-brand/10 to-brand-light border border-brand/20 rounded-3xl p-6 sm:p-8 space-y-6 shadow-sm relative overflow-hidden">
                    <div
                        class="absolute right-[-5%] bottom-[-5%] w-48 h-48 bg-brand/10 rounded-full blur-3xl pointer-events-none">
                    </div>

                    <div class="flex items-start gap-4">
                        <div
                            class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-2xl shadow border border-brand/20 flex-shrink-0 animate-pulse">
                            🤖</div>
                        <div class="space-y-1">
                            <h3 class="font-black text-brand text-lg tracking-tight">AI Nhận Diện & Đối Chiếu Gương Mặt
                            </h3>
                            <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">Hệ thống phân tích tự động
                                · Cập nhật: {{ now()->format('H:i') }} hôm nay</p>
                        </div>
                    </div>
                    <p
                        class="text-[10px] text-slate-500 font-bold leading-relaxed flex items-start gap-1.5 bg-white/50 p-3.5 rounded-xl border border-brand/10">
                        <span>⚠️</span>
                        <span>LƯU Ý PHÁP LÝ: Kết quả phân tích AI chỉ mang tính định hướng. Quyết định cuối cùng cần có sự
                            xác thực của gia đình và cơ quan chức năng.</span>
                    </p>
                </div>

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

                    <!-- Interactive Clue Box Form (Hidden by default) -->
                    <div id="clue-box-form" class="hidden bg-slate-50 rounded-2xl p-5 border border-slate-200 space-y-4">
                        <h4 class="font-extrabold text-slate-800 text-sm">Gửi manh mối của bạn về bé
                            {{ $case->full_name }}</h4>
                        <textarea rows="3" placeholder="Ghi cụ thể những gì bạn thấy, thời gian, đặc điểm trang phục bé lúc đó..."
                            class="w-full px-4 py-3 border border-slate-250 rounded-2xl text-xs focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent bg-white resize-none"></textarea>

                        <div class="flex flex-wrap gap-2">
                            <button
                                class="bg-slate-100 text-slate-700 text-xs font-bold px-3 py-2 rounded-xl border border-slate-200 hover:bg-slate-200 transition-colors inline-flex items-center gap-1.5">
                                📷 Đính kèm ảnh chụp trẻ
                            </button>
                            <button
                                class="bg-slate-100 text-slate-700 text-xs font-bold px-3 py-2 rounded-xl border border-slate-200 hover:bg-slate-200 transition-colors inline-flex items-center gap-1.5">
                                📍 Gửi toạ độ vị trí GPS hiện tại
                            </button>
                        </div>

                        <div class="flex gap-2 justify-end pt-2">
                            <button onclick="document.getElementById('clue-box-form').classList.add('hidden')"
                                class="text-xs font-bold text-slate-500 px-4 py-2 hover:bg-slate-200 rounded-xl">Hủy
                                bỏ</button>
                            <button
                                class="bg-primary text-white text-xs font-extrabold px-5 py-2 rounded-xl hover:bg-primary-dark transition-all shadow-md shadow-primary/10">Gửi
                                thông tin</button>
                        </div>
                    </div>

                    <!-- Comment Thread List -->
                    <div class="space-y-6">

                        @forelse($case->clues as $clue)
                            <div class="flex gap-3.5 items-start">
                                <div
                                    class="w-10 h-10 rounded-full {{ $clue->is_verified ? 'bg-brand/10 border border-brand/20 text-brand' : 'bg-slate-100 border border-slate-200 text-slate-500' }} flex-shrink-0 flex items-center justify-center font-bold text-sm">
                                    {{ $clue->initials }}
                                </div>
                                <div class="flex-1 space-y-2">
                                    <div
                                        class="bg-slate-100 p-4 rounded-2xl inline-block max-w-full {{ $clue->is_verified ? 'border-l-4 border-success' : '' }}">
                                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                                            <span
                                                class="text-xs font-extrabold text-slate-900">{{ $clue->display_name }}</span>
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
                                    </div>

                                    <div class="flex items-center gap-3 text-[10px] font-bold text-slate-400 pl-2">
                                        @if ($clue->lat && $clue->lng)
                                            <span class="bg-primary/10 text-primary px-2.5 py-1 rounded-full">📍
                                                {{ number_format($clue->lat, 4) }},
                                                {{ number_format($clue->lng, 4) }}</span>
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

                {{-- ── Comment Section ── --}}
                <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-premium">
                    @livewire('missing.comment-section', ['caseId' => $case->id])
                </div>
            </div>

            <!-- RIGHT SIDEBAR: 4 cols -->
            <div class="lg:col-span-4 space-y-6">

                <!-- GPS ACTION CARD -->
                <div
                    class="bg-gradient-to-r from-primary to-primary-dark text-white rounded-3xl p-6 shadow-lg shadow-primary/20 space-y-4 relative overflow-hidden">
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
                <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-premium space-y-4">
                    <h4 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider">Thông tin liên lạc</h4>
                    @guest
                        <p class="text-xs text-slate-400 leading-relaxed font-semibold">Số điện thoại liên hệ đang được ẩn.
                            Đăng nhập để mở tính năng chat nội bộ và kết nối với gia đình.</p>

                        <a href="{{ route('auth.login') }}"
                            class="block w-full bg-slate-900 text-white font-extrabold text-xs py-3.5 rounded-2xl text-center hover:bg-slate-800 shadow transition-colors uppercase tracking-widest">
                            Đăng nhập để liên hệ
                        </a>
                    @else
                        <div class="space-y-2">
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
                        class="flex items-center gap-2 justify-center text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                        <span>🔒</span> Thông tin liên hệ được bảo vệ
                    </div>
                </div>

                <form class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-premium space-y-4"
                    onsubmit="return false;">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h4 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider">QR hồ sơ</h4>
                            <p class="text-[10px] text-slate-400 font-semibold mt-1">Mở nhanh hồ sơ bằng quét mã</p>
                        </div>
                        <span
                            class="text-[10px] font-black uppercase tracking-widest text-primary bg-primary/10 px-2.5 py-1 rounded-full">QR</span>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Đường dẫn QR</label>
                        <input id="qr-link" type="text" readonly
                            value="{{ route('missing.qr', ['id' => $case->id]) }}"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs font-medium text-slate-600 focus:outline-none" />
                    </div>

                    <div class="rounded-2xl overflow-hidden border border-slate-200 bg-slate-50 p-4 flex justify-center">
                        <img src="{{ route('missing.qr', ['id' => $case->id]) }}" alt="QR hồ sơ {{ $case->full_name }}"
                            class="max-w-full h-auto" />
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <a href="{{ route('missing.qr', ['id' => $case->id]) }}" target="_blank"
                            class="text-center rounded-2xl border border-slate-200 px-3 py-3 text-xs font-extrabold text-slate-700 hover:bg-slate-50 transition-colors">
                            Mở QR
                        </a>
                        <button type="button"
                            onclick="navigator.clipboard.writeText(document.getElementById('qr-link').value); alert('Đã sao chép link QR!')"
                            class="rounded-2xl bg-primary text-white px-3 py-3 text-xs font-extrabold hover:bg-primary-dark transition-colors">
                            Sao chép
                        </button>
                    </div>

                    <p class="text-[10px] text-slate-400 font-semibold text-center uppercase tracking-wide">Quét mã để mở
                        hồ sơ nhanh</p>
                </form>

                <!-- Interactive Mini map tracking location -->
                @if ($case->lat && $case->lng)
                    <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-premium space-y-4">
                        <h4 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider">Vị trí mất tích</h4>
                        <div id="mini-map"
                            class="h-44 bg-slate-150 rounded-2xl overflow-hidden border border-slate-200 z-10"
                            data-lat="{{ $case->lat }}" data-lng="{{ $case->lng }}"></div>
                        <p class="text-[10px] text-slate-400 font-semibold text-center uppercase tracking-wide">
                            📍
                            {{ collect([$case->address_detail, $case->ward, $case->district, $case->province?->name])->filter()->implode(', ') }}
                        </p>
                    </div>
                @endif

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
