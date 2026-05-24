@extends('layouts.app')
@section('title', 'Trang chủ')

@section('content')

  {{-- ── SOS Stories Bar ── --}}
<section class="sos-bar">
    <div class="sos-bar-inner">
        <div class="sos-badge">
            <span class="w-2 h-2 bg-primary rounded-full animate-ping"></span> SOS khẩn cấp
        </div>
        <div class="flex items-center gap-5 overflow-x-auto no-scrollbar py-1 pr-4 flex-1">
            @foreach($sosKids as $index => $kid)
                <a href="{{ route('missing.detail', $kid->id) }}" 
                   class="flex flex-col items-center flex-shrink-0 group">
                    <div class="w-12 h-12 rounded-full p-0.5 border-2 {{ $index < 3 ? 'border-primary pulse-dot' : 'border-slate-300' }} flex items-center justify-center transform group-hover:scale-105 transition-all">
                        @if($kid->primaryPhoto)
                            <img src="{{ asset('storage/' . $kid->primaryPhoto->path_thumb) }}"
                                 class="w-full h-full rounded-full object-cover bg-slate-100" alt=""/>
                        @else
                            <img src="https://api.dicebear.com/7.x/thumbs/svg?seed={{ $kid->id }}&backgroundColor=ffdfbf"
                                 class="w-full h-full rounded-full object-cover bg-slate-100" alt=""/>
                        @endif
                    </div>
                    <span class="text-[10px] font-bold {{ $index < 3 ? 'text-slate-800' : 'text-slate-500' }} mt-1 text-center truncate w-14">
                        {{ \Illuminate\Support\Str::limit($kid->full_name, 10) }} · {{ $kid->age_estimate }}t
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</section>

    {{-- ── Hero ── --}}
    <section class="hero">
        <div class="absolute inset-0 pointer-events-none opacity-20">
            <div class="absolute top-[-10%] left-[5%] w-96 h-96 bg-primary rounded-full blur-[120px]"></div>
            <div class="absolute bottom-[-10%] right-[5%] w-96 h-96 bg-brand rounded-full blur-[120px]"></div>
        </div>
        <div class="hero-inner">
            <div class="hero-content">
                <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md px-3.5 py-1.5 rounded-full text-xs font-bold border border-white/10 shadow-lg">
                    <span class="w-2.5 h-2.5 bg-success rounded-full animate-pulse"></span>
                    <span class="text-slate-200">355 hồ sơ đang cần tìm · Cập nhật thời gian thực</span>
                </div>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black leading-[1.1] tracking-tight">
                    Mỗi một đứa trẻ<br/>
                    <span class="bg-gradient-to-r from-primary to-orange-400 bg-clip-text text-transparent">đều xứng đáng</span><br/>
                    được về nhà.
                </h1>
                <p class="text-slate-300 text-base sm:text-lg max-w-xl leading-relaxed font-medium">
                    TìmTrẻ.vn kết nối sức mạnh cộng đồng Việt Nam, ứng dụng AI đối chiếu khuôn mặt hiện đại và định vị GPS tức thời để hỗ trợ gia đình tìm lại con em nhanh nhất có thể.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 pt-3">
                    <a href="{{ route('found-children.create') }}"
                       class="inline-flex items-center justify-center gap-2 bg-primary text-white font-extrabold px-8 py-4 rounded-2xl hover:bg-primary-dark transition-premium text-base shadow-lg shadow-primary/30 hover:-translate-y-0.5">
                        📢 Báo tin khẩn cấp tìm con
                    </a>
                    <a href="{{ route('missing.index') }}"
                       class="inline-flex items-center justify-center gap-2 bg-white/10 backdrop-blur-md text-white font-bold px-8 py-4 rounded-2xl hover:bg-white/20 transition-premium text-base border border-white/20">
                        🔍 Tìm kiếm thông tin bé lạc
                    </a>
                </div>
            </div>

            <div class="hero-widget">
                <div class="bg-white/5 backdrop-blur-lg border border-white/10 rounded-3xl p-6 shadow-2xl">
                    <div class="flex justify-between items-center mb-5 border-b border-white/5 pb-4">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">🤖</span>
                            <span class="text-sm font-bold text-slate-200">AI Nhận Diện Khuôn Mặt Đang Chạy</span>
                        </div>
                        <span class="bg-brand/20 text-blue-300 text-[10px] font-bold px-2 py-0.5 rounded-full border border-brand/20">ARC-FACE MODEL</span>
                    </div>
                    <div class="relative overflow-hidden rounded-2xl aspect-video bg-slate-950 flex items-center justify-center border border-white/5">
                        <img src="https://api.dicebear.com/7.x/thumbs/svg?seed=An1&backgroundColor=ffdfbf" class="w-32 h-32 rounded-xl object-cover" alt=""/>
                        <div class="ai-scanner-overlay"></div>
                        <div class="absolute bottom-2 left-2 bg-black/75 px-2.5 py-1 rounded-lg text-[10px] text-slate-300 font-semibold border border-white/5">Phân tích: HS-2026-0523</div>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-3 text-xs">
                        <div class="bg-white/5 rounded-xl p-3 border border-white/5">
                            <div class="text-slate-400">Tỷ lệ chính xác</div>
                            <div class="text-base font-extrabold text-success mt-0.5">97.8%</div>
                        </div>
                        <div class="bg-white/5 rounded-xl p-3 border border-white/5">
                            <div class="text-slate-400">Thời gian đối chiếu</div>
                            <div class="text-base font-extrabold text-blue-300 mt-0.5">&lt; 0.8 giây</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Stats Bar ── --}}
  <section class="stats-bar">
    <div class="stats-bar-inner">
        <div class="stat-item">
            <div class="stat-num text-primary">{{ number_format($stats['total_found']) }}</div>
            <div class="stat-label">Con đã về nhà</div>
        </div>
        <div class="stat-item">
            <div class="stat-num text-slate-900">{{ number_format($stats['total_approved']) }}</div>
            <div class="stat-label">Đang tích cực tìm</div>
        </div>
        <div class="stat-item">
            <div class="stat-num text-brand">48h</div>
            <div class="stat-label">Thời gian trung bình</div>
        </div>
        <div class="stat-item">
            <div class="stat-num text-success">89%</div>
            <div class="stat-label">Tỷ lệ đoàn tụ</div>
        </div>
    </div>
</section>

    {{-- ── Quick Search ── --}}
    <section class="quick-search-section">
        <div class="max-w-3xl mx-auto text-center space-y-6">
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Tìm kiếm nhanh hồ sơ con lạc</h2>
            <p class="text-slate-500 text-sm max-w-lg mx-auto">Nhập tên của con, đặc điểm nhận dạng nổi bật hoặc khu vực bé bị lạc mất để lọc hồ sơ ngay lập tức.</p>
            <form action="{{ route('missing.index') }}" method="GET" class="search-box">
    <div class="relative flex-1 flex items-center px-3">
        <svg class="w-5 h-5 text-slate-400 absolute left-4" fill="none"
             stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input type="text"
               name="q"
               placeholder="Tên con, đặc điểm, địa điểm bị lạc..."
               class="w-full pl-9 pr-3 py-3 text-slate-800 placeholder-slate-400
                      bg-transparent text-sm focus:outline-none"/>
    </div>
    <button type="submit"
            class="bg-primary text-white font-extrabold px-8 py-3.5 rounded-xl
                   hover:bg-primary-dark shadow-md shadow-primary/20 transition-premium text-sm">
        Tìm kiếm
    </button>
</form>
        </div>
    </section>

    {{-- ── Urgent Cases ── --}}
    <section class="py-16 px-4 max-w-7xl mx-auto">
        <div class="section-header">
            <div>
                <h2 class="section-title">🚨 Con đang cần tìm gấp</h2>
                <p class="section-subtitle">Các hồ sơ mất tích trong vòng 72 giờ qua được cộng đồng cảnh báo</p>
            </div>
            <a href="{{ route('missing.index') }}" class="section-link">
                Xem tất cả hồ sơ <span class="group-hover:translate-x-1 transition-transform">→</span>
            </a>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
    @foreach($urgentCases as $c)
        <div class="case-card">
            <div class="case-card-img">
                @if($c->primaryPhoto)
                    <img src="{{ asset('storage/' . $c->primaryPhoto->path_thumb) }}" alt="{{ $c->full_name }}"/>
                @else
                    <img src="https://api.dicebear.com/7.x/thumbs/svg?seed={{ $c->id }}&backgroundColor=ffdfbf" alt=""/>
                @endif
                <span class="absolute top-3 left-3 bg-primary text-white text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-xl shadow-glow-sos flex items-center gap-1">
                    <span class="w-1.5 h-1.5 bg-white rounded-full animate-ping"></span> Đang tìm gấp
                </span>
                <span class="absolute top-3 right-3 bg-black/60 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded-lg">
                    {{ $c->missing_date ? \Carbon\Carbon::parse($c->missing_date)->diffForHumans() : '' }}
                </span>
            </div>
            <div class="case-card-body">
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <h3 class="case-card-name">{{ $c->full_name }}</h3>
                        <span class="text-xs font-extrabold text-slate-400">{{ $c->age_estimate }} tuổi</span>
                    </div>
                    <div class="case-card-location">
                        <span>📍</span>
                        {{ implode(', ', array_filter([$c->district, $c->province?->name])) }}
                    </div>
                    @if($c->circumstances)
                        <p class="case-card-desc">{{ \Illuminate\Support\Str::limit($c->circumstances, 100) }}</p>
                    @endif
                </div>
                <div class="case-card-footer">
                    <span class="badge badge-default">📞 Bảo mật liên hệ</span>
                    <a href="{{ route('missing.detail', $c->id) }}" 
                       class="text-xs font-extrabold text-primary hover:underline flex items-center gap-1">
                        Xem hồ sơ <span class="text-[10px]">→</span>
                    </a>
                </div>
            </div>
        </div>
    @endforeach
</div>
    </section>

    {{-- ── How It Works ── --}}
    <section class="bg-white border-y border-slate-100 py-16 px-4">
        <div class="max-w-7xl mx-auto">
            <div class="text-center max-w-xl mx-auto mb-14 space-y-3">
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">TìmTrẻ.vn hoạt động thế nào?</h2>
                <p class="text-slate-500 text-sm font-medium">3 bước đơn giản giúp gia đình lan tỏa thông tin, đoàn tụ nhanh nhất</p>
            </div>
            <div class="grid md:grid-cols-3 gap-10">
                @foreach([
                    ['icon' => '📝', 'bg' => 'bg-primary/10 text-primary', 'title' => '1. Báo tin mất tích', 'desc' => 'Điền biểu mẫu 3 bước tinh gọn, tải ảnh rõ mặt của con lên hệ thống. Đội ngũ kiểm duyệt sẽ phê duyệt và đưa thông tin lên cộng đồng chỉ sau tối đa 30 phút.'],
                    ['icon' => '🌐', 'bg' => 'bg-brand/10 text-brand', 'title' => '2. Lan toả & Báo manh mối', 'desc' => 'Hệ thống sinh mã QR riêng. Người dân chia sẻ lên Facebook, Zalo bằng 1 cú chạm. Bất kỳ ai nhìn thấy trẻ có thể gửi manh mối kèm tọa độ GPS trực tiếp.'],
                    ['icon' => '🏠', 'bg' => 'bg-success/10 text-success', 'title' => '3. Đoàn tụ cùng gia đình', 'desc' => 'Admin phối hợp với cơ quan công an kiểm chứng manh mối, kết nối trực tiếp với phụ huynh để đưa con trở về an toàn. Trạng thái con được cập nhật.'],
                ] as $step)
                    <div class="how-step">
                        <div class="how-step-icon {{ $step['bg'] }} shadow-lg">{{ $step['icon'] }}</div>
                        <h3 class="font-extrabold text-slate-900 text-lg">{{ $step['title'] }}</h3>
                        <p class="text-xs text-slate-500 leading-relaxed max-w-xs mx-auto text-center">{{ $step['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── Found Stories ── --}}
    <section class="py-16 px-4 max-w-7xl mx-auto">
        <div class="space-y-2 mb-10 text-center sm:text-left">
            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">✅ Bé đã về nhà an toàn</h2>
            <p class="section-subtitle">Những câu chuyện hạnh phúc từ cộng đồng TìmTrẻ.vn</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach([
                ['seed' => 'MinhLe', 'name' => 'Lê Văn Minh', 'age' => 10, 'city' => 'Đà Nẵng', 'days' => '3 ngày'],
                ['seed' => 'NganHa', 'name' => 'Hà Thị Ngân', 'age' => 7, 'city' => 'TP.HCM', 'days' => '24h'],
                ['seed' => 'TuanAnh', 'name' => 'Tuấn Anh', 'age' => 11, 'city' => 'Hà Nội', 'days' => '6h'],
                ['seed' => 'MyDuyen', 'name' => 'Mỹ Duyên', 'age' => 8, 'city' => 'Cần Thơ', 'days' => '2 ngày'],
            ] as $f)
                <div class="found-card">
                    <div class="w-14 h-14 rounded-full flex-shrink-0 border-2 border-white shadow overflow-hidden">
                        <img src="https://api.dicebear.com/7.x/thumbs/svg?seed={{ $f['seed'] }}&backgroundColor=d1fae5"
                             class="w-full h-full object-cover" alt=""/>
                    </div>
                    <div class="min-w-0">
                        <div class="font-extrabold text-slate-900 text-sm truncate">{{ $f['name'] }}</div>
                        <div class="text-[10px] text-slate-500 font-semibold mt-0.5">{{ $f['age'] }} tuổi · {{ $f['city'] }}</div>
                        <div class="text-[10px] text-success-dark font-black mt-1">✓ Về nhà sau {{ $f['days'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ── CTA Banner ── --}}
    <section class="cta-banner">
        <div class="max-w-3xl mx-auto space-y-6 relative z-10">
            <h2 class="text-3xl sm:text-4xl font-black tracking-tight leading-tight">Bạn có thể là người cứu rỗi cả một gia đình hôm nay</h2>
            <p class="text-red-105 text-sm leading-relaxed max-w-xl mx-auto font-medium">
                Một lượt chia sẻ, một manh mối định vị hay một đăng ký làm tình nguyện viên — mỗi hành động nhỏ từ bạn đều mang tính sinh tử với một gia đình mất con.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center pt-4">
                <a href="{{ route('auth.register') }}"
                   class="bg-white text-primary font-black px-8 py-4 rounded-2xl hover:bg-slate-50 shadow-xl transition-premium text-base">
                    🤝 Tham gia làm tình nguyện viên
                </a>
                <a href="{{ route('missing.index') }}"
                   class="border-2 border-white/80 text-white font-bold px-8 py-4 rounded-2xl hover:bg-white/10 transition-premium text-base">
                    Duyệt hồ sơ trẻ thất lạc
                </a>
            </div>
        </div>
    </section>

@endsection