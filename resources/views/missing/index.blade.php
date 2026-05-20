@extends('layouts.app')
@section('title', 'Hồ sơ đang tìm kiếm')

@section('content')
    <!-- PAGE HEADER -->
    <div class="bg-white border-b border-slate-100 py-8 px-4">
        <div class="max-w-7xl mx-auto space-y-2">
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Hồ sơ tìm trẻ lạc</h1>
            <p class="text-slate-500 text-sm font-semibold">Hiện có <strong class="text-slate-800">355</strong> bé đang được
                cộng đồng tích cực tìm kiếm</p>
        </div>
    </div>

    <!-- SEARCH + COLLAPSIBLE FILTER BAR -->
    <div class="bg-white border-b border-slate-150 py-4 px-4 sticky top-16 z-40 shadow-sm">
        <div class="max-w-7xl mx-auto space-y-4">
            <div class="flex flex-col lg:flex-row gap-3">
                <!-- Search Input -->
                <div class="relative flex-1">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" placeholder="Tìm theo tên con, đặc điểm đặc biệt, nơi mất tích..."
                        class="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent bg-slate-50 focus:bg-white transition-all font-medium" />
                </div>

                <!-- Filters grid -->
                <div class="flex gap-2 flex-wrap items-center">
                    <select
                        class="border border-slate-200 rounded-2xl px-4 py-3 text-xs bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary font-bold text-slate-600 transition-colors">
                        <option value="">Tất cả tỉnh thành</option>
                        <option>TP. Hồ Chí Minh</option>
                        <option>Hà Nội</option>
                        <option>Đà Nẵng</option>
                        <option>Cần Thơ</option>
                        <option>Bình Dương</option>
                        <option>Đồng Nai</option>
                    </select>
                    <select
                        class="border border-slate-200 rounded-2xl px-4 py-3 text-xs bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary font-bold text-slate-600 transition-colors">
                        <option value="">Giới tính</option>
                        <option>Nam</option>
                        <option>Nữ</option>
                    </select>
                    <select
                        class="border border-slate-200 rounded-2xl px-4 py-3 text-xs bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary font-bold text-slate-600 transition-colors">
                        <option value="">Độ tuổi</option>
                        <option>Dưới 5 tuổi</option>
                        <option>5–10 tuổi</option>
                        <option>10–15 tuổi</option>
                        <option>Trên 15 tuổi</option>
                    </select>
                    <select
                        class="border border-slate-200 rounded-2xl px-4 py-3 text-xs bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary font-bold text-slate-600 transition-colors">
                        <option value="">Trạng thái</option>
                        <option>Đang tìm kiếm</option>
                        <option>Đã tìm thấy ✓</option>
                    </select>
                </div>
            </div>

            <!-- Active Filter Tags (Pills Style) -->
            <div class="flex items-center gap-2 flex-wrap text-xs pt-1">
                <span class="text-slate-400 font-bold">Lọc theo:</span>
                <span
                    class="inline-flex items-center gap-1.5 bg-primary-light text-primary font-bold px-3 py-1.5 rounded-full border border-primary/20 shadow-sm">
                    🔴 Đang tìm kiếm
                    <button onclick="this.parentElement.remove()"
                        class="ml-1 text-slate-400 hover:text-primary transition-colors text-sm font-black">×</button>
                </span>
                <span
                    class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-600 font-bold px-3 py-1.5 rounded-full border border-slate-200 shadow-sm">
                    TP. Hồ Chí Minh
                    <button onclick="this.parentElement.remove()"
                        class="ml-1 text-slate-400 hover:text-slate-700 transition-colors text-sm font-black">×</button>
                </span>
            </div>
        </div>
    </div>

    <!-- SORT & SUMMARY BAR -->
    <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between z-10 relative">
        <p class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">355 kết quả phù hợp</p>
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-500">Sắp xếp:</span>
            <select
                class="border border-slate-200 rounded-xl px-3 py-1.5 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-primary font-bold text-slate-600 shadow-sm">
                <option>Mới nhất khẩn cấp</option>
                <option>Cũ nhất</option>
                <option>Ưu tiên tìm khẩn cấp</option>
            </select>
        </div>
    </div>

    <!-- CASES GRID -->
    <main class="max-w-7xl mx-auto px-4 pb-16">
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 lg:gap-8" id="cases-grid">

            <!-- Card 1 (Khẩn cấp, có AI) -->
            <div
                class="bg-white border border-slate-200/80 rounded-3xl overflow-hidden shadow-premium hover:shadow-premium-hover hover:-translate-y-1 transition-premium flex flex-col justify-between">
                <a href="{{ route('missing.detail') }}"
                    class="block group relative overflow-hidden aspect-[4/3] bg-slate-900">
                    <img src="https://api.dicebear.com/7.x/thumbs/svg?seed=An1&backgroundColor=ffdfbf"
                        class="w-full h-full object-cover transform group-hover:scale-105 transition-premium"
                        alt="Ảnh chân dung" />
                    <span
                        class="absolute top-3 left-3 bg-primary text-white text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-xl shadow-glow-sos flex items-center gap-1">
                        <span class="w-1.5 h-1.5 bg-white rounded-full animate-ping"></span> Đang tìm
                    </span>
                    <span
                        class="absolute top-3 right-3 bg-black/60 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded-lg">2
                        giờ trước</span>
                </a>
                <div class="p-5 space-y-4 flex-1 flex flex-col justify-between">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <h3 class="font-extrabold text-slate-900 text-base hover:text-primary transition-colors"><a
                                    href="{{ route('missing.detail') }}">Nguyễn Văn An</a></h3>
                            <span class="text-xs font-extrabold text-slate-400">8 tuổi</span>
                        </div>
                        <div class="text-xs font-semibold text-slate-500 flex items-center gap-1">
                            <span>📍</span> Chợ Bình Tây, Q.6, TP.HCM
                        </div>
                        <p class="text-xs text-slate-400 leading-relaxed line-clamp-2">Bị lạc lúc đi chợ cùng mẹ. Mặc áo
                            thun xanh lá sọc trắng, quần thun ngắn màu đen.</p>
                    </div>
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                        <span
                            class="inline-flex items-center gap-1.5 bg-brand/10 text-brand text-[10px] font-extrabold px-2.5 py-1.5 rounded-full">
                            🤖 AI: 87% khớp
                        </span>
                        <a href="{{ route('missing.detail') }}"
                            class="text-xs font-extrabold text-primary hover:underline flex items-center gap-1">
                            Chi tiết <span class="text-[10px]">→</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card 2 -->
            <div
                class="bg-white border border-slate-200/80 rounded-3xl overflow-hidden shadow-premium hover:shadow-premium-hover hover:-translate-y-1 transition-premium flex flex-col justify-between">
                <a href="{{ route('missing.detail') }}"
                    class="block group relative overflow-hidden aspect-[4/3] bg-slate-900">
                    <img src="https://api.dicebear.com/7.x/thumbs/svg?seed=Linh2&backgroundColor=b6e3f4"
                        class="w-full h-full object-cover transform group-hover:scale-105 transition-premium"
                        alt="Ảnh chân dung" />
                    <span
                        class="absolute top-3 left-3 bg-primary text-white text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-xl shadow-glow-sos flex items-center gap-1">
                        <span class="w-1.5 h-1.5 bg-white rounded-full animate-ping"></span> Đang tìm
                    </span>
                    <span
                        class="absolute top-3 right-3 bg-black/60 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded-lg">3
                        ngày trước</span>
                </a>
                <div class="p-5 space-y-4 flex-1 flex flex-col justify-between">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <h3 class="font-extrabold text-slate-900 text-base hover:text-primary transition-colors"><a
                                    href="{{ route('missing.detail') }}">Trần Thị Linh</a></h3>
                            <span class="text-xs font-extrabold text-slate-400">6 tuổi</span>
                        </div>
                        <div class="text-xs font-semibold text-slate-500 flex items-center gap-1">
                            <span>📍</span> Hồ Tây, Hà Nội
                        </div>
                        <p class="text-xs text-slate-400 leading-relaxed line-clamp-2">Tóc ngắn ngang vai, mặc váy hoa hồng
                            phấn phấn nhạt, đi giày búp bê đen.</p>
                    </div>
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                        <span
                            class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-600 text-[10px] font-extrabold px-2.5 py-1.5 rounded-full">
                            📞 Bảo mật liên hệ
                        </span>
                        <a href="{{ route('missing.detail') }}"
                            class="text-xs font-extrabold text-primary hover:underline flex items-center gap-1">
                            Chi tiết <span class="text-[10px]">→</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card 3 -->
            <div
                class="bg-white border border-slate-200/80 rounded-3xl overflow-hidden shadow-premium hover:shadow-premium-hover hover:-translate-y-1 transition-premium flex flex-col justify-between">
                <a href="{{ route('missing.detail') }}"
                    class="block group relative overflow-hidden aspect-[4/3] bg-slate-900">
                    <img src="https://api.dicebear.com/7.x/thumbs/svg?seed=Duc&backgroundColor=ffd5dc"
                        class="w-full h-full object-cover transform group-hover:scale-105 transition-premium"
                        alt="Ảnh chân dung" />
                    <span
                        class="absolute top-3 left-3 bg-primary text-white text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-xl shadow-glow-sos flex items-center gap-1">
                        <span class="w-1.5 h-1.5 bg-white rounded-full animate-ping"></span> Đang tìm
                    </span>
                    <span
                        class="absolute top-3 right-3 bg-black/60 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded-lg">5
                        ngày trước</span>
                </a>
                <div class="p-5 space-y-4 flex-1 flex flex-col justify-between">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <h3 class="font-extrabold text-slate-900 text-base hover:text-primary transition-colors"><a
                                    href="{{ route('missing.detail') }}">Phạm Minh Đức</a></h3>
                            <span class="text-xs font-extrabold text-slate-400">12 tuổi</span>
                        </div>
                        <div class="text-xs font-semibold text-slate-500 flex items-center gap-1">
                            <span>📍</span> Mỹ Khê, Đà Nẵng
                        </div>
                        <p class="text-xs text-slate-400 leading-relaxed line-clamp-2">Lạc khu vực bãi tắm số 2. Cao khoảng
                            1m45, tóc hơi xoăn nhẹ, mặc quần bơi cam.</p>
                    </div>
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                        <span
                            class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-600 text-[10px] font-extrabold px-2.5 py-1.5 rounded-full">
                            📞 Bảo mật liên hệ
                        </span>
                        <a href="{{ route('missing.detail') }}"
                            class="text-xs font-extrabold text-primary hover:underline flex items-center gap-1">
                            Chi tiết <span class="text-[10px]">→</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card 4 -->
            <div
                class="bg-white border border-slate-200/80 rounded-3xl overflow-hidden shadow-premium hover:shadow-premium-hover hover:-translate-y-1 transition-premium flex flex-col justify-between">
                <a href="{{ route('missing.detail') }}"
                    class="block group relative overflow-hidden aspect-[4/3] bg-slate-900">
                    <img src="https://api.dicebear.com/7.x/thumbs/svg?seed=Hoa&backgroundColor=c0aede"
                        class="w-full h-full object-cover transform group-hover:scale-105 transition-premium"
                        alt="Ảnh chân dung" />
                    <span
                        class="absolute top-3 left-3 bg-primary text-white text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-xl shadow-glow-sos flex items-center gap-1">
                        <span class="w-1.5 h-1.5 bg-white rounded-full animate-ping"></span> Đang tìm
                    </span>
                    <span
                        class="absolute top-3 right-3 bg-black/60 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded-lg">6
                        ngày trước</span>
                </a>
                <div class="p-5 space-y-4 flex-1 flex flex-col justify-between">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <h3 class="font-extrabold text-slate-900 text-base hover:text-primary transition-colors"><a
                                    href="{{ route('missing.detail') }}">Lê Thị Hoa</a></h3>
                            <span class="text-xs font-extrabold text-slate-400">9 tuổi</span>
                        </div>
                        <div class="text-xs font-semibold text-slate-500 flex items-center gap-1">
                            <span>📍</span> Bến Ninh Kiều, Cần Thơ
                        </div>
                        <p class="text-xs text-slate-400 leading-relaxed line-clamp-2">Mất liên lạc tại phố đi bộ. Mặc áo
                            thun vàng tươi có hình mèo, đi dép hồng.</p>
                    </div>
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                        <span
                            class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-600 text-[10px] font-extrabold px-2.5 py-1.5 rounded-full">
                            📞 Bảo mật liên hệ
                        </span>
                        <a href="pages/{{ route('missing.detail') }}"
                            class="text-xs font-extrabold text-primary hover:underline flex items-center gap-1">
                            Chi tiết <span class="text-[10px]">→</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card 5 -->
            <div
                class="bg-white border border-slate-200/80 rounded-3xl overflow-hidden shadow-premium hover:shadow-premium-hover hover:-translate-y-1 transition-premium flex flex-col justify-between">
                <a href="{{ route('missing.detail') }}"
                    class="block group relative overflow-hidden aspect-[4/3] bg-slate-900">
                    <img src="https://api.dicebear.com/7.x/thumbs/svg?seed=Tuan&backgroundColor=d1fae5"
                        class="w-full h-full object-cover transform group-hover:scale-105 transition-premium"
                        alt="Ảnh chân dung" />
                    <span
                        class="absolute top-3 left-3 bg-primary text-white text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-xl shadow-glow-sos flex items-center gap-1">
                        <span class="w-1.5 h-1.5 bg-white rounded-full animate-ping"></span> Đang tìm
                    </span>
                    <span
                        class="absolute top-3 right-3 bg-black/60 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded-lg">7
                        ngày trước</span>
                </a>
                <div class="p-5 space-y-4 flex-1 flex flex-col justify-between">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <h3 class="font-extrabold text-slate-900 text-base hover:text-primary transition-colors"><a
                                    href="{{ route('missing.detail') }}">Ngô Tuấn Kiệt</a></h3>
                            <span class="text-xs font-extrabold text-slate-400">7 tuổi</span>
                        </div>
                        <div class="text-xs font-semibold text-slate-500 flex items-center gap-1">
                            <span>📍</span> KCN VSIP 2, Bình Dương
                        </div>
                        <p class="text-xs text-slate-400 leading-relaxed line-clamp-2">Lạc ngoài cổng phụ KCN. Mặc áo thun
                            màu đỏ, quần đùi bò xanh đậm.</p>
                    </div>
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                        <span
                            class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-600 text-[10px] font-extrabold px-2.5 py-1.5 rounded-full">
                            📞 Bảo mật liên hệ
                        </span>
                        <a href="{{ route('missing.detail') }}"
                            class="text-xs font-extrabold text-primary hover:underline flex items-center gap-1">
                            Chi tiết <span class="text-[10px]">→</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card 6 (BÉ ĐÃ TÌM THẤY - Emerald Style) -->
            <div
                class="bg-success-light/40 border border-success/30 rounded-3xl overflow-hidden shadow-premium hover:shadow-premium-hover hover:-translate-y-1 transition-premium flex flex-col justify-between">
                <div class="relative overflow-hidden aspect-[4/3] bg-slate-900">
                    <img src="https://api.dicebear.com/7.x/thumbs/svg?seed=MinhLe&backgroundColor=c0aede"
                        class="w-full h-full object-cover grayscale opacity-90" alt="Ảnh chân dung" />
                    <span
                        class="absolute top-3 left-3 bg-success text-white text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-xl flex items-center gap-1">
                        ✓ Bé đã về nhà
                    </span>
                    <span
                        class="absolute top-3 right-3 bg-black/60 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded-lg">13/05/2026</span>
                </div>
                <div class="p-5 space-y-4 flex-1 flex flex-col justify-between">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <h3 class="font-extrabold text-slate-700 text-base">Lê Văn Minh</h3>
                            <span class="text-xs font-extrabold text-slate-400">10 tuổi</span>
                        </div>
                        <div class="text-xs font-semibold text-slate-500 flex items-center gap-1">
                            <span>📍</span> Đà Nẵng
                        </div>
                        <p class="text-xs text-slate-400 leading-relaxed line-clamp-2">Đã tìm thấy an toàn sau 3 ngày mất
                            tích nhờ sự chung tay từ Zalo cộng đồng Đà Nẵng.</p>
                    </div>
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                        <span class="inline-flex items-center gap-1.5 text-success text-[10px] font-black">
                            ✅ Thành công
                        </span>
                        <span class="text-[10px] text-slate-400 font-bold">Cảm ơn cộng đồng!</span>
                    </div>
                </div>
            </div>

            <!-- Card 7 -->
            <div
                class="bg-white border border-slate-200/80 rounded-3xl overflow-hidden shadow-premium hover:shadow-premium-hover hover:-translate-y-1 transition-premium flex flex-col justify-between">
                <a href="{{ route('missing.detail') }}"
                    class="block group relative overflow-hidden aspect-[4/3] bg-slate-900">
                    <img src="https://api.dicebear.com/7.x/thumbs/svg?seed=Phuong&backgroundColor=fef3c7"
                        class="w-full h-full object-cover transform group-hover:scale-105 transition-premium"
                        alt="Ảnh chân dung" />
                    <span
                        class="absolute top-3 left-3 bg-primary text-white text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-xl shadow-glow-sos flex items-center gap-1">
                        <span class="w-1.5 h-1.5 bg-white rounded-full animate-ping"></span> Đang tìm
                    </span>
                    <span
                        class="absolute top-3 right-3 bg-black/60 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded-lg">8
                        ngày trước</span>
                </a>
                <div class="p-5 space-y-4 flex-1 flex flex-col justify-between">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <h3 class="font-extrabold text-slate-900 text-base hover:text-primary transition-colors"><a
                                    href="{{ route('missing.detail') }}">Vũ Thị Phương</a></h3>
                            <span class="text-xs font-extrabold text-slate-400">11 tuổi</span>
                        </div>
                        <div class="text-xs font-semibold text-slate-500 flex items-center gap-1">
                            <span>📍</span> Chợ Sắt, Hải Phòng
                        </div>
                        <p class="text-xs text-slate-400 leading-relaxed line-clamp-2">Lạc ngoài cổng chính. Tóc dài cột
                            cao đuôi gà, mặc áo thun cam nhạt.</p>
                    </div>
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                        <span
                            class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-600 text-[10px] font-extrabold px-2.5 py-1.5 rounded-full">
                            📞 Bảo mật liên hệ
                        </span>
                        <a href="{{ route('missing.detail') }}"
                            class="text-xs font-extrabold text-primary hover:underline flex items-center gap-1">
                            Chi tiết <span class="text-[10px]">→</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card 8 -->
            <div
                class="bg-white border border-slate-200/80 rounded-3xl overflow-hidden shadow-premium hover:shadow-premium-hover hover:-translate-y-1 transition-premium flex flex-col justify-between">
                <a href="{{ route('missing.detail') }}"
                    class="block group relative overflow-hidden aspect-[4/3] bg-slate-900">
                    <img src="https://api.dicebear.com/7.x/thumbs/svg?seed=Bao&backgroundColor=ffe4e6"
                        class="w-full h-full object-cover transform group-hover:scale-105 transition-premium"
                        alt="Ảnh chân dung" />
                    <span
                        class="absolute top-3 left-3 bg-primary text-white text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-xl shadow-glow-sos flex items-center gap-1">
                        <span class="w-1.5 h-1.5 bg-white rounded-full animate-ping"></span> Đang tìm
                    </span>
                    <span
                        class="absolute top-3 right-3 bg-black/60 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded-lg">10
                        ngày trước</span>
                </a>
                <div class="p-5 space-y-4 flex-1 flex flex-col justify-between">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <h3 class="font-extrabold text-slate-900 text-base hover:text-primary transition-colors"><a
                                    href="{{ route('missing.detail') }}">Trương Quốc Bảo</a></h3>
                            <span class="text-xs font-extrabold text-slate-400">5 tuổi</span>
                        </div>
                        <div class="text-xs font-semibold text-slate-500 flex items-center gap-1">
                            <span>📍</span> Biên Hòa, Đồng Nai
                        </div>
                        <p class="text-xs text-slate-400 leading-relaxed line-clamp-2">Bị lạc gần trường mầm non Hoa Mai,
                            Biên Hoà. Mặc áo khủng long xanh.</p>
                    </div>
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                        <span
                            class="inline-flex items-center gap-1.5 bg-brand/10 text-brand text-[10px] font-extrabold px-2.5 py-1.5 rounded-full">
                            🤖 AI: 73% khớp
                        </span>
                        <a href="{{ route('missing.detail') }}"
                            class="text-xs font-extrabold text-primary hover:underline flex items-center gap-1">
                            Chi tiết <span class="text-[10px]">→</span>
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <!-- PAGINATION (Silicon Valley Style Pills) -->
        <div class="flex items-center justify-center gap-2 mt-12">
            <button
                class="w-10 h-10 flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 transition-colors text-sm font-bold">←</button>
            <button
                class="w-10 h-10 flex items-center justify-center rounded-xl bg-primary text-white text-sm font-extrabold shadow-md shadow-primary/20">1</button>
            <button
                class="w-10 h-10 flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-colors text-sm font-extrabold">2</button>
            <button
                class="w-10 h-10 flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-colors text-sm font-extrabold">3</button>
            <span class="text-slate-400 text-xs font-extrabold px-2">...</span>
            <button
                class="w-10 h-10 flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-colors text-sm font-extrabold">45</button>
            <button
                class="w-10 h-10 flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 transition-colors text-sm font-bold">→</button>
        </div>
    </main>
@endsection
