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
                    <div class="bg-primary/5 border-b border-primary/10 px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 bg-primary rounded-full animate-ping"></span>
                            <span class="text-primary font-extrabold text-sm tracking-tight">
                                @if($case->status === 'MATCHED')
                                    ✅ BÉ ĐÃ ĐƯỢC TÌM THẤY
                                @else
                                    🚨 ĐANG CẦN TÌM KHẨN CẤP TRÊN TOÀN QUỐC
                                @endif
                            </span>
                        </div>
                        <span class="bg-primary text-white text-[10px] font-black uppercase tracking-wider px-3 py-1.5 rounded-full shadow-glow-sos self-start sm:self-auto">
                            {{ $case->missing_date ? 'Mất tích ' . \Carbon\Carbon::parse($case->missing_date)->diffForHumans() : 'Đang xử lý' }}
                        </span>
                    </div>

                    <!-- Profile Grid -->
                    <div class="p-6 sm:p-8 space-y-6">
                        <div class="flex flex-col sm:flex-row gap-8 items-start">

                            <!-- Avatar -->
                            <div class="w-full sm:w-44 flex-shrink-0">
                                <div class="relative overflow-hidden rounded-2xl border-4 border-slate-100 shadow-md bg-slate-900 group aspect-square sm:aspect-auto">
                                    @if($case->primaryPhoto)
                                        <img src="{{ asset('storage/' . $case->primaryPhoto->path_thumb) }}"
                                            class="w-full h-full sm:h-44 object-cover transform group-hover:scale-105 transition-premium"
                                            alt="{{ $case->full_name }}" />
                                    @else
                                        <img src="https://api.dicebear.com/7.x/thumbs/svg?seed={{ $case->id }}&backgroundColor=ffdfbf"
                                            class="w-full h-full sm:h-44 object-cover"
                                            alt="{{ $case->full_name }}" />
                                    @endif
                                    @if($case->photos && $case->photos->count() > 1)
                                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center cursor-pointer">
                                            <span class="text-white text-xs font-bold bg-black/60 px-3 py-1.5 rounded-xl">
                                                Xem ảnh ({{ $case->photos->count() }})
                                            </span>
                                        </div>
                                    @endif
                                </div>
                                <p class="text-center text-[10px] text-slate-400 font-bold mt-2.5">
                                    Mã hồ sơ: <span class="font-mono">{{ $case->case_code }}</span>
                                </p>
                            </div>

                            <!-- Key Facts -->
                            <div class="flex-1 space-y-4 w-full">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight leading-none">
                                            {{ $case->full_name }}
                                        </h1>
                                        <p class="text-xs text-slate-400 font-bold mt-2">
                                            Mã vụ việc: <span class="font-mono text-slate-700">{{ $case->case_code }}</span>
                                        </p>
                                    </div>
                                    <button title="Sao chép liên kết"
                                        onclick="navigator.clipboard.writeText(window.location.href); alert('Đã sao chép link hồ sơ!')"
                                        class="p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors shadow-sm flex-shrink-0">
                                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                            {{ $case->age_estimate }} tuổi
                                        </div>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Giới tính</span>
                                        <div class="font-extrabold text-slate-800 mt-0.5">
                                            {{ $case->gender === 'MALE' ? 'Nam' : ($case->gender === 'FEMALE' ? 'Nữ' : 'Không rõ') }}
                                        </div>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Khu vực lạc mất</span>
                                        <div class="font-extrabold text-slate-800 mt-0.5">
                                            {{ implode(', ', array_filter([$case->address_detail, $case->ward, $case->district, $case->province?->name])) }}
                                        </div>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Thời gian bị lạc</span>
                                        <div class="font-extrabold text-slate-800 mt-0.5">
                                            {{ $case->missing_date ? \Carbon\Carbon::parse($case->missing_date)->format('d/m/Y') : '—' }}
                                            @if($case->missing_time)
                                                · Lúc {{ \Carbon\Carbon::parse($case->missing_time)->format('H:i') }}
                                            @endif
                                        </div>
                                    </div>
                                    @if($case->height_cm || $case->weight_kg)
                                        <div>
                                            <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Thể trạng</span>
                                            <div class="font-extrabold text-slate-800 mt-0.5">
                                                {{ $case->height_cm ? 'Cao ' . $case->height_cm . 'cm' : '' }}
                                                {{ $case->weight_kg ? '· Nặng ' . $case->weight_kg . 'kg' : '' }}
                                            </div>
                                        </div>
                                    @endif
                                    @if($case->clothes_desc)
                                        <div>
                                            <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Trang phục khi lạc</span>
                                            <div class="font-extrabold text-primary mt-0.5">{{ $case->clothes_desc }}</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        @if($case->circumstances || $case->special_marks)
                            <div class="border-t border-slate-100 pt-6 space-y-3">
                                <h4 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider">Mô tả đặc điểm cụ thể</h4>
                                <p class="text-xs text-slate-500 leading-relaxed bg-slate-50 p-4 rounded-2xl border border-slate-150 font-medium">
                                    {{ $case->circumstances }}
                                    @if($case->special_marks)
                                        <br><br><strong>Dấu hiệu đặc biệt:</strong> {{ $case->special_marks }}
                                    @endif
                                </p>
                            </div>
                        @endif

                        <!-- Photo gallery -->
                        @if($case->photos && $case->photos->count() > 1)
                            <div class="border-t border-slate-100 pt-6 space-y-3">
                                <h4 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider">Thư viện ảnh</h4>
                                <div class="grid grid-cols-4 gap-2">
                                    @foreach($case->photos as $photo)
                                        <div class="aspect-square rounded-xl overflow-hidden bg-slate-100">
                                            <img src="{{ asset('storage/' . $photo->path_thumb) }}"
                                                class="w-full h-full object-cover hover:scale-105 transition-transform cursor-pointer"
                                                alt="" />
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- AI WIDGET (placeholder — Sprint 3) -->
                <div class="bg-gradient-to-br from-brand/5 via-brand/10 to-brand-light border border-brand/20 rounded-3xl p-6 sm:p-8 space-y-4 shadow-sm relative overflow-hidden">
                    <div class="absolute right-[-5%] bottom-[-5%] w-48 h-48 bg-brand/10 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-2xl shadow border border-brand/20 flex-shrink-0 animate-pulse">🤖</div>
                        <div class="space-y-1">
                            <h3 class="font-black text-brand text-lg tracking-tight">AI Nhận Diện & Đối Chiếu Gương Mặt</h3>
                            <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">Hệ thống phân tích tự động · Tính năng sẽ có ở Sprint 3</p>
                        </div>
                    </div>
                    <p class="text-[10px] text-slate-500 font-bold leading-relaxed flex items-start gap-1.5 bg-white/50 p-3.5 rounded-xl border border-brand/10">
                        <span>⚠️</span>
                        <span>LƯU Ý PHÁP LÝ: Kết quả phân tích AI chỉ mang tính định hướng. Quyết định cuối cùng cần có sự xác thực của gia đình và cơ quan chức năng.</span>
                    </p>
                </div>

                <!-- CLUES SECTION -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-6 sm:p-8 space-y-6 shadow-premium">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <h3 class="font-black text-slate-900 text-lg tracking-tight">
                            Manh mối từ cộng đồng
                        </h3>
                        @auth
                            <button onclick="document.getElementById('clue-box-form').classList.toggle('hidden')"
                                class="text-xs font-black text-primary hover:text-primary-dark transition-colors uppercase tracking-wider">
                                + Gửi manh mối mới
                            </button>
                        @else
                            <a href="{{ route('login') }}" class="text-xs font-black text-primary hover:text-primary-dark transition-colors uppercase tracking-wider">
                                Đăng nhập để gửi manh mối
                            </a>
                        @endauth
                    </div>

                    @auth
                        <!-- Clue Form -->
                        <div id="clue-box-form" class="hidden bg-slate-50 rounded-2xl p-5 border border-slate-200 space-y-4">
                            <h4 class="font-extrabold text-slate-800 text-sm">Gửi manh mối của bạn về {{ $case->full_name }}</h4>
                            <textarea rows="3"
                                placeholder="Ghi cụ thể những gì bạn thấy, thời gian, đặc điểm trang phục bé lúc đó..."
                                class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-xs focus:outline-none focus:ring-2 focus:ring-primary bg-white resize-none"></textarea>
                            <div class="flex gap-2 justify-end pt-2">
                                <button onclick="document.getElementById('clue-box-form').classList.add('hidden')"
                                    class="text-xs font-bold text-slate-500 px-4 py-2 hover:bg-slate-200 rounded-xl">Hủy bỏ</button>
                                <button class="bg-primary text-white text-xs font-extrabold px-5 py-2 rounded-xl hover:bg-primary-dark transition-all shadow-md">
                                    Gửi thông tin
                                </button>
                            </div>
                        </div>
                    @endauth

                    <div class="text-center py-8 text-slate-400 text-sm font-semibold">
                        Tính năng manh mối sẽ có ở Sprint 2
                    </div>
                </div>

            </div>

            <!-- RIGHT SIDEBAR: 4 cols -->
            <div class="lg:col-span-4 space-y-6">

                <!-- GPS ACTION CARD -->
                <div class="bg-gradient-to-r from-primary to-primary-dark text-white rounded-3xl p-6 shadow-lg shadow-primary/20 space-y-4 relative overflow-hidden">
                    <div class="absolute top-[-20%] right-[-10%] w-32 h-32 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
                    <div class="flex items-center gap-2">
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-white"></span>
                        </span>
                        <span class="font-extrabold text-sm tracking-widest uppercase">Bạn đang thấy {{ $case->full_name }}?</span>
                    </div>
                    <p class="text-xs text-slate-100 leading-relaxed font-medium">
                        Nếu bạn đang đứng cạnh bé hoặc nhìn thấy bé ngay lúc này, hãy ấn nút dưới đây để chia sẻ toạ độ GPS chính xác.
                    </p>
                    <button onclick="shareGPS()"
                        class="w-full bg-white text-primary font-black py-4 rounded-2xl hover:bg-slate-50 transition-premium text-sm shadow-md">
                        📍 Chia sẻ vị trí GPS tức thời
                    </button>
                </div>

                <!-- Contact block -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-premium space-y-4">
                    <h4 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider">Thông tin liên lạc</h4>
                    @auth
                        @if($case->contact_name)
                            <div class="space-y-2 text-xs">
                                <div class="flex justify-between">
                                    <span class="text-slate-400 font-bold">Người liên hệ</span>
                                    <span class="font-extrabold text-slate-800">{{ $case->contact_name }}</span>
                                </div>
                                @if($case->contact_relation)
                                    <div class="flex justify-between">
                                        <span class="text-slate-400 font-bold">Quan hệ</span>
                                        <span class="font-extrabold text-slate-800">{{ $case->contact_relation }}</span>
                                    </div>
                                @endif
                            </div>
                        @endif
                    @else
                        <p class="text-xs text-slate-400 leading-relaxed font-semibold">
                            Để bảo vệ thông tin, bạn cần đăng nhập để xem thông tin liên hệ.
                        </p>
                        <a href="{{ route('login') }}"
                            class="block w-full bg-slate-900 text-white font-extrabold text-xs py-3.5 rounded-2xl text-center hover:bg-slate-800 shadow transition-colors uppercase tracking-widest">
                            Đăng nhập để liên hệ
                        </a>
                        <div class="flex items-center gap-2 justify-center text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                            <span>🔒</span> Mã hóa dữ liệu bảo mật 100%
                        </div>
                    @endauth
                </div>

                <!-- Mini map -->
                @if($case->lat && $case->lng)
                    <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-premium space-y-4">
                        <h4 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider">Vị trí mất tích</h4>
                        <div id="mini-map" class="h-44 bg-slate-100 rounded-2xl overflow-hidden border border-slate-200 z-10"
                            data-lat="{{ $case->lat }}" data-lng="{{ $case->lng }}"></div>
                        <p class="text-[10px] text-slate-400 font-semibold text-center uppercase tracking-wide">
                            📍 {{ implode(', ', array_filter([$case->district, $case->province?->name])) }}
                        </p>
                    </div>
                @endif

                <!-- Social sharing -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-premium space-y-4">
                    <h4 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider">Lan toả để giúp tìm con</h4>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->url()) }}"
                            target="_blank"
                            class="bg-[#1877F2] text-white text-xs font-extrabold py-3 px-4 rounded-xl hover:bg-[#165ECC] transition-colors shadow-sm text-center">
                            Facebook
                        </a>
                        <button class="bg-[#0068FF] text-white text-xs font-extrabold py-3 px-4 rounded-xl hover:bg-[#0051CC] transition-colors shadow-sm">
                            Zalo
                        </button>
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
                    alert('Tọa độ của bạn: ' + pos.coords.latitude + ', ' + pos.coords.longitude + '\nTính năng gửi GPS sẽ có ở Sprint 2.');
                });
            } else {
                alert('Trình duyệt không hỗ trợ GPS.');
            }
        }
    </script>
@endsection