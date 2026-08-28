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
                @foreach ($sosKids as $index => $kid)
                    <a href="{{ route('missing.by_slug', ['slug' => $kid->slug]) }}"
                        class="flex flex-col items-center flex-shrink-0 group">
                        <div
                            class="w-12 h-12 rounded-full p-0.5 border-2 {{ $index < 3 ? 'border-primary pulse-dot' : 'border-slate-300' }} flex items-center justify-center transform group-hover:scale-105 transition-all">
                            @php
                                $storyPhoto = $kid->primaryPhoto
                                    ?? ($kid->photos->isNotEmpty() ? $kid->photos->first() : null);
                            @endphp
                            @if ($storyPhoto)
                                <img src="{{ asset('storage/' . ($storyPhoto->path_thumb ?: $storyPhoto->path_display ?: $storyPhoto->path_blurred)) }}"
                                    class="w-full h-full rounded-full object-cover bg-slate-100" alt="" loading="lazy" />
                            @else
                                <img src="https://api.dicebear.com/7.x/initials/svg?seed={{ $kid->full_name }}&backgroundColor=1c3a5e&textColor=ffffff&fontWeight=700"
                                    class="w-full h-full rounded-full object-cover bg-slate-100" alt="" />
                            @endif
                        </div>
                        <span
                            class="text-[10px] font-bold {{ $index < 3 ? 'text-slate-800' : 'text-slate-500' }} mt-1 text-center truncate w-14">
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
                <div
                    class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md px-3.5 py-1.5 rounded-full text-xs font-bold border border-white/10 shadow-lg">
                    <span class="w-2.5 h-2.5 bg-success rounded-full animate-pulse"></span>
                    <span class="text-slate-200">355 hồ sơ đang cần tìm · Cập nhật thời gian thực</span>
                </div>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black leading-[1.1] tracking-tight">
                    Mỗi một đứa trẻ<br />
                    <span class="bg-gradient-to-r from-primary to-primary/70 bg-clip-text text-transparent">đều xứng
                        đáng</span><br />
                    được về nhà.
                </h1>
                <p class="text-slate-300 text-base sm:text-lg max-w-xl leading-relaxed font-medium">
                    TìmTrẻ.vn kết nối sức mạnh cộng đồng Việt Nam, ứng dụng AI đối chiếu khuôn mặt hiện đại và định vị GPS
                    tức thời để hỗ trợ gia đình tìm lại con em nhanh nhất có thể.
                </p>


                <div class="flex flex-col sm:flex-row gap-4 pt-3">
                    <a href="{{ route('missing.create') }}"
                        class="inline-flex items-center justify-center gap-2 bg-primary text-white font-extrabold px-8 py-4 rounded-2xl hover:bg-primary-dark transition-premium text-base shadow-lg shadow-primary/30 hover:-translate-y-0.5">
                        <x-heroicon-o-megaphone class="w-5 h-5" /> Báo tin khẩn cấp tìm con
                    </a>
                    <a href="{{ route('missing.index') }}"
                        class="inline-flex items-center justify-center gap-2 bg-white/10 backdrop-blur-md text-white font-bold px-8 py-4 rounded-2xl hover:bg-white/20 transition-premium text-base border border-white/20">
                        <x-heroicon-o-magnifying-glass class="w-5 h-5" /> Tìm kiếm thông tin bé lạc
                    </a>
                </div>
            </div>

            {{-- ── Trust Panel — thay cho widget "AI đang chạy" kiểu demo sản phẩm ── --}}
            <div class="hero-widget">
                <div class="bg-white/5 backdrop-blur-lg border border-white/10 rounded-3xl p-6 shadow-2xl">
                    <div class="flex items-center gap-2 mb-5 border-b border-white/5 pb-4">
                        <x-heroicon-o-shield-check class="w-5 h-5 text-slate-200" />
                        <span class="text-sm font-bold text-slate-200">Vì sao gia đình tin tưởng TìmTrẻ.vn</span>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div
                                class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <x-heroicon-o-building-library class="w-4 h-4 text-slate-200" />
                            </div>
                            <div>
                                <div class="text-sm font-bold text-white">Phối hợp cùng cơ quan chức năng</div>
                                <div class="text-xs text-slate-400 mt-0.5">Mọi manh mối xác thực đều được chuyển tới Công an
                                    và Tổng đài 111.</div>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div
                                class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <x-heroicon-o-lock-closed class="w-4 h-4 text-slate-200" />
                            </div>
                            <div>
                                <div class="text-sm font-bold text-white">Bảo mật &amp; tuân thủ pháp luật trẻ em</div>
                                <div class="text-xs text-slate-400 mt-0.5">Dữ liệu và hình ảnh trẻ được mã hoá, chỉ dùng
                                    đúng mục đích tìm kiếm.</div>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div
                                class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <x-heroicon-o-check-badge class="w-4 h-4 text-slate-200" />
                            </div>
                            <div>
                                <div class="text-sm font-bold text-white">Kiểm duyệt trước khi công khai</div>
                                <div class="text-xs text-slate-400 mt-0.5">Đội ngũ xác minh từng hồ sơ trước khi hiển thị
                                    cho cộng đồng.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 pt-4 border-t border-white/5 grid grid-cols-2 gap-3 text-xs">
                        <div class="bg-white/5 rounded-xl p-3 border border-white/5">
                            <div class="text-slate-400">Đã đoàn tụ</div>
                            <div class="text-base font-extrabold text-success mt-0.5">2 gia đình</div>
                        </div>
                        <div class="bg-white/5 rounded-xl p-3 border border-white/5">
                            <div class="text-slate-400">Thời gian phản hồi TB</div>
                            <div class="text-base font-extrabold text-success mt-0.5">48 giờ</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Stats Bar ── --}}
    @livewire('home.stats-bar')

    {{-- ── Quick Search ── --}}
    @livewire('home.quick-search')

    {{-- ── Urgent Cases ── --}}
    @livewire('home.urgent-cases-grid')

    {{-- ── How It Works ── --}}
    <section class="bg-white border-y border-slate-100 py-16 px-4">
        <div class="max-w-7xl mx-auto">
            <div class="text-center max-w-xl mx-auto mb-14 space-y-3">
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">TìmTrẻ.vn hoạt động thế nào?</h2>
                <p class="text-slate-500 text-sm font-medium">3 bước đơn giản giúp gia đình lan tỏa thông tin, đoàn tụ
                    nhanh nhất</p>
            </div>
            <div class="grid md:grid-cols-3 gap-10">
                @foreach ([
                        [
                            'icon' => 'heroicon-o-document-text',
                            'bg' => 'bg-primary/10 text-primary',
                            'title' => '1. Báo tin mất tích',
                            'desc' => 'Điền biểu mẫu 3 bước tinh gọn, tải ảnh rõ mặt của con lên hệ thống. Đội ngũ kiểm duyệt sẽ phê duyệt và đưa thông tin lên cộng đồng chỉ sau tối đa 30 phút.'
                        ],
                        [
                            'icon' => 'heroicon-o-globe-alt',
                            'bg' => 'bg-brand/10 text-brand',
                            'title' => '2. Lan toả & Báo manh mối',
                            'desc' => 'Hệ thống sinh mã QR riêng. Người dân chia sẻ lên Facebook, Zalo bằng 1 cú chạm. Bất kỳ ai nhìn thấy trẻ có thể gửi manh mối kèm tọa độ GPS trực tiếp.'
                        ],
                        [
                            'icon' => 'heroicon-o-home',
                            'bg' => 'bg-success/10 text-success',
                            'title' => '3. Đoàn tụ cùng gia đình',
                            'desc' => 'Admin phối hợp với cơ quan công an kiểm chứng manh mối, kết nối trực tiếp với phụ huynh để đưa con trở về an toàn. Trạng thái con được cập nhật.'
                        ]
                    ] as $step)
                    <div class="how-step">
                        <div class="how-step-icon {{ $step['bg'] }} shadow-lg flex items-center justify-center">
                            <x-dynamic-component :component="$step['icon']" class="w-6 h-6" />
                        </div>
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
            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight"><x-heroicon-o-check-circle
                    class="w-7 h-7 inline text-green-500" /> Bé đã về nhà an toàn</h2>
            <p class="section-subtitle">Những câu chuyện hạnh phúc từ cộng đồng TìmTrẻ.vn</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach ([['name' => 'Lê Văn Minh', 'age' => 10, 'city' => 'Đà Nẵng', 'days' => '3 ngày'], ['name' => 'Hà Thị Ngân', 'age' => 7, 'city' => 'TP.HCM', 'days' => '24h'], ['name' => 'Tuấn Anh', 'age' => 11, 'city' => 'Hà Nội', 'days' => '6h'], ['name' => 'Mỹ Duyên', 'age' => 8, 'city' => 'Cần Thơ', 'days' => '2 ngày']] as $f)
                <div class="found-card">
                    <div class="w-14 h-14 rounded-full flex-shrink-0 border-2 border-white shadow overflow-hidden">
                        <img src="https://api.dicebear.com/7.x/initials/svg?seed={{ $f['name'] }}&backgroundColor=2f6d52&textColor=ffffff&fontWeight=700"
                            class="w-full h-full object-cover" alt="" />
                    </div>
                    <div class="min-w-0">
                        <div class="font-extrabold text-slate-900 text-sm truncate">{{ $f['name'] }}</div>
                        <div class="text-[10px] text-slate-500 font-semibold mt-0.5">{{ $f['age'] }} tuổi ·
                            {{ $f['city'] }}
                        </div>
                        <div class="text-[10px] text-success-dark font-black mt-1">Về nhà sau {{ $f['days'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ── CTA Banner ── --}}
    <section class="cta-banner">
        <div class="max-w-3xl mx-auto space-y-6 relative z-10">
            <h2 class="text-3xl sm:text-4xl font-black tracking-tight leading-tight">Bạn có thể là người cứu rỗi cả một gia
                đình hôm nay</h2>
            <p class="text-white/85 text-sm leading-relaxed max-w-xl mx-auto font-medium">
                Một lượt chia sẻ, một manh mối định vị hay một đăng ký làm tình nguyện viên — mỗi hành động nhỏ từ bạn đều
                mang tính sinh tử với một gia đình mất con.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center pt-4">
                <a href="{{ route('auth.register') }}"
                    class="bg-white text-primary font-black px-8 py-4 rounded-2xl hover:bg-slate-50 shadow-xl transition-premium text-base">
                    <x-heroicon-o-hand-raised class="w-5 h-5 inline-flex items-center gap-2 shrink-0 " /> Tham gia làm tình
                    nguyện viên
                </a>
                <a href="{{ route('missing.index') }}"
                    class="border-2 border-white/80 text-white font-bold px-8 py-4 rounded-2xl hover:bg-white/10 transition-premium text-base">
                    Duyệt hồ sơ trẻ thất lạc
                </a>
            </div>
        </div>
    </section>

@endsection