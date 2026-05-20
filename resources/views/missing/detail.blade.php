@extends('layouts.app')
@section('title', 'Nguyễn Văn An — Chi tiết hồ sơ')

@section('content')
    <!-- BREADCRUMB -->
    <div class="max-w-7xl mx-auto px-4 py-4">
        <nav class="text-xs font-bold text-slate-400 flex gap-2 items-center uppercase tracking-wider">
            <a href="../index.html" class="hover:text-primary transition-colors">Trang chủ</a>
            <span class="text-slate-300">/</span>
            <a href="danh-sach.html" class="hover:text-primary transition-colors">Hồ sơ mất tích</a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-800">Nguyễn Văn An</span>
        </nav>
    </div>

    <!-- MAIN CONTENT GRID -->
    <main class="max-w-7xl mx-auto px-4 pb-20">
        <div class="grid lg:grid-cols-12 gap-8 items-start">

            <!-- LEFT: Profile details & AI Match & Clues (8 cols) -->
            <div class="lg:col-span-8 space-y-8">

                <!-- Primary Profile Card -->
                <div class="bg-white border border-slate-200/80 rounded-3xl overflow-hidden shadow-premium">
                    <!-- Status Indicator Banner -->
                    <div
                        class="bg-primary/5 border-b border-primary/10 px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 bg-primary rounded-full animate-ping"></span>
                            <span class="text-primary font-extrabold text-sm tracking-tight">🚨 ĐANG CẦN TÌM KHẨN CẤP TRÊN
                                TOÀN QUỐC</span>
                        </div>
                        <span
                            class="bg-primary text-white text-[10px] font-black uppercase tracking-wider px-3 py-1.5 rounded-full shadow-glow-sos self-start sm:self-auto">
                            Mất tích 3 ngày
                        </span>
                    </div>

                    <!-- Profile Grid -->
                    <div class="p-6 sm:p-8 space-y-6">
                        <div class="flex flex-col sm:flex-row gap-8 items-start">

                            <!-- Avatar photo container -->
                            <div class="w-full sm:w-44 flex-shrink-0">
                                <div
                                    class="relative overflow-hidden rounded-2xl border-4 border-slate-100 shadow-md bg-slate-900 group aspect-square sm:aspect-auto">
                                    <img src="https://api.dicebear.com/7.x/thumbs/svg?seed=An1&backgroundColor=ffdfbf"
                                        class="w-full h-full sm:h-44 object-cover transform group-hover:scale-105 transition-premium"
                                        alt="Nguyễn Văn An" />
                                    <div
                                        class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center cursor-pointer">
                                        <span class="text-white text-xs font-bold bg-black/60 px-3 py-1.5 rounded-xl">Xem
                                            ảnh (3)</span>
                                    </div>
                                </div>
                                <p class="text-center text-[10px] text-slate-400 font-bold mt-2.5">Ảnh chụp rõ nhất tháng
                                    4/2026</p>
                            </div>

                            <!-- Key Facts -->
                            <div class="flex-1 space-y-4 w-full">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h1
                                            class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight leading-none">
                                            Nguyễn Văn An</h1>
                                        <p class="text-xs text-slate-400 font-bold mt-2">Mã vụ việc: <span
                                                class="font-mono text-slate-700">#HS-2026-0523</span></p>
                                    </div>
                                    <button title="Sao chép liên kết"
                                        onclick="navigator.clipboard.writeText(window.location.href); alert('Đã sao chép link hồ sơ!')"
                                        class="p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors shadow-sm">
                                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Info metadata matrix -->
                                <div class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm border-t border-slate-100 pt-4">
                                    <div>
                                        <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Tuổi</span>
                                        <div class="font-extrabold text-slate-800 mt-0.5">8 tuổi (Sinh năm 2018)</div>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Giới
                                            tính</span>
                                        <div class="font-extrabold text-slate-800 mt-0.5">Nam</div>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Khu vực lạc
                                            mất</span>
                                        <div class="font-extrabold text-slate-800 mt-0.5">Chợ Bình Tây, Q.6, TP.HCM</div>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Thời gian bị
                                            lạc</span>
                                        <div class="font-extrabold text-slate-800 mt-0.5">15/05/2026 · Lúc 14:30</div>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Thể
                                            trạng</span>
                                        <div class="font-extrabold text-slate-800 mt-0.5">Cao 1m20 · Nặng 22kg</div>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Trang phục
                                            khi lạc</span>
                                        <div class="font-extrabold text-primary mt-0.5">Áo thun xanh lá sọc trắng</div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Detailed Description Box -->
                        <div class="border-t border-slate-100 pt-6 space-y-3">
                            <h4 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider">Mô tả đặc điểm cụ thể
                            </h4>
                            <p
                                class="text-xs text-slate-500 leading-relaxed bg-slate-50 p-4 rounded-2xl border border-slate-150 font-medium">
                                Bé Nguyễn Văn An bị lạc trong lúc đi mua sắm cùng mẹ tại khu vực Chợ Bình Tây, Quận 6, TP.
                                Hồ Chí Minh vào chiều thứ Sáu 15/05/2026. Lúc bị lạc bé mặc áo thun cộc tay màu xanh lá sọc
                                trắng ngang, quần short thun đen, đi dép tổ ong nhựa màu xanh lục nhạt. Bé nói giọng Nam Bộ,
                                tính cách nhút nhát, sợ người lạ, khi khóc hay dụi mắt trái. Gia đình đang vô cùng hoảng sợ
                                và mong mỏi cộng đồng hỗ trợ cứu giúp bé.
                            </p>
                        </div>

                    </div>
                </div>

                <!-- AI FACE SCANNER WIDGET (Silicon Valley high-tech trust card) -->
                <div
                    class="bg-gradient-to-br from-brand/5 via-brand/10 to-brand-light border border-brand/20 rounded-3xl p-6 sm:p-8 space-y-6 shadow-sm relative overflow-hidden">
                    <!-- Floating abstract layout -->
                    <div
                        class="absolute right-[-5%] bottom-[-5%] w-48 h-48 bg-brand/10 rounded-full blur-3xl pointer-events-none">
                    </div>

                    <div class="flex items-start gap-4">
                        <div
                            class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-2xl shadow border border-brand/20 flex-shrink-0 animate-pulse">
                            🤖
                        </div>
                        <div class="space-y-1">
                            <h3 class="font-black text-brand text-lg tracking-tight">AI Nhận Diện & Đối Chiếu Gương Mặt</h3>
                            <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">Hệ thống phân tích tự động
                                · Cập nhật: 16:45 hôm nay</p>
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <!-- Suggestion Item 1 (High Match) -->
                        <div
                            class="bg-white rounded-2xl p-4 flex gap-3.5 items-center border border-brand/10 shadow-sm hover:border-brand transition-colors relative group">
                            <div class="w-14 h-14 rounded-xl overflow-hidden bg-slate-900 relative">
                                <img src="https://api.dicebear.com/7.x/thumbs/svg?seed=match1&backgroundColor=fef3c7"
                                    class="w-full h-full object-cover" alt="" />
                                <div class="ai-scanner-overlay"></div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="font-extrabold text-slate-900 text-xs truncate">Trẻ được tìm thấy #TT-0041</div>
                                <div class="text-[10px] text-slate-400 font-semibold mt-0.5">Quận 8, TP.HCM · 16/05</div>
                                <div
                                    class="inline-flex items-center gap-1 bg-success-light text-success text-[10px] font-black px-2 py-0.5 rounded-full mt-2 border border-success/20">
                                    87% Khớp mặt
                                </div>
                            </div>
                            <a href="#" class="absolute inset-0 z-20"></a>
                        </div>

                        <!-- Suggestion Item 2 (Medium Match) -->
                        <div
                            class="bg-white rounded-2xl p-4 flex gap-3.5 items-center border border-brand/10 shadow-sm hover:border-brand transition-colors relative group">
                            <div class="w-14 h-14 rounded-xl overflow-hidden bg-slate-900 relative">
                                <img src="https://api.dicebear.com/7.x/thumbs/svg?seed=match2&backgroundColor=d1fae5"
                                    class="w-full h-full object-cover" alt="" />
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="font-extrabold text-slate-900 text-xs truncate">Trẻ được tìm thấy #TT-0038</div>
                                <div class="text-[10px] text-slate-400 font-semibold mt-0.5">Quận 6, TP.HCM · 15/05</div>
                                <div
                                    class="inline-flex items-center gap-1 bg-amber-50 text-amber-700 text-[10px] font-black px-2 py-0.5 rounded-full mt-2 border border-amber-200">
                                    62% Khớp mặt
                                </div>
                            </div>
                            <a href="#" class="absolute inset-0 z-20"></a>
                        </div>
                    </div>

                    <p
                        class="text-[10px] text-slate-500 font-bold leading-relaxed flex items-start gap-1.5 bg-white/50 p-3.5 rounded-xl border border-brand/10">
                        <span>⚠️</span>
                        <span>LƯU Ý PHÁP LÝ: Kết quả phân tích đối chiếu khuôn mặt bằng trí tuệ nhân tạo (AI DeepFace) chỉ
                            mang tính chất định hướng và hỗ trợ lọc dữ liệu. Quyết định cuối cùng cần có sự xác thực trực
                            tiếp của gia đình và cơ quan chức năng có thẩm quyền.</span>
                    </p>
                </div>

                <!-- CLUES SECTION (Facebook-style Nested Discussion comments) -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-6 sm:p-8 space-y-6 shadow-premium">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <h3 class="font-black text-slate-900 text-lg tracking-tight">Manh mối từ cộng đồng <span
                                class="text-primary">(3)</span></h3>
                        <button onclick="document.getElementById('clue-box-form').classList.toggle('hidden')"
                            class="text-xs font-black text-primary hover:text-primary-dark transition-colors uppercase tracking-wider">
                            + Gửi manh mối mới
                        </button>
                    </div>

                    <!-- Interactive Clue Box Form (Hidden by default) -->
                    <div id="clue-box-form" class="hidden bg-slate-50 rounded-2xl p-5 border border-slate-200 space-y-4">
                        <h4 class="font-extrabold text-slate-800 text-sm">Gửi manh mối của bạn về bé An</h4>
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

                        <!-- Comment Item 1 -->
                        <div class="flex gap-3.5 items-start">
                            <div
                                class="w-10 h-10 rounded-full bg-slate-100 border border-slate-200 flex-shrink-0 flex items-center justify-center font-bold text-slate-500 text-sm">
                                NK</div>
                            <div class="flex-1 space-y-2">
                                <div class="bg-slate-100 p-4 rounded-2xl inline-block max-w-full">
                                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                                        <span class="text-xs font-extrabold text-slate-900">Nguyễn Kim Lan</span>
                                        <span
                                            class="bg-amber-50 text-amber-700 border border-amber-200 text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full">Đang
                                            xác minh</span>
                                        <span class="text-[10px] text-slate-400 font-bold">1 giờ trước</span>
                                    </div>
                                    <p class="text-xs text-slate-600 leading-relaxed font-medium">
                                        Tôi thấy một bé trai trông rất giống trong ảnh đang đi đứng một mình gần cổng sau
                                        Chợ Bình Tây khoảng 16:00 chiều nay. Bé đứng khóc, mặc áo thun xanh lá sọc trắng
                                        nhưng dép thì không mang.
                                    </p>
                                </div>

                                <div class="flex items-center gap-3 text-[10px] font-bold text-slate-400 pl-2">
                                    <span class="bg-primary/10 text-primary px-2.5 py-1 rounded-full">📍 Quận 6,
                                        TP.HCM</span>
                                    <button class="hover:text-primary transition-colors">Phản hồi</button>
                                </div>
                            </div>
                        </div>

                        <!-- Comment Item 2 (Verified Clue) -->
                        <div class="flex gap-3.5 items-start">
                            <div
                                class="w-10 h-10 rounded-full bg-brand/10 border border-brand/20 flex-shrink-0 flex items-center justify-center font-bold text-brand text-sm">
                                TH</div>
                            <div class="flex-1 space-y-2">
                                <div
                                    class="bg-slate-100 p-4 rounded-2xl inline-block max-w-full border-l-4 border-success">
                                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                                        <span class="text-xs font-extrabold text-slate-900">Trần Hùng</span>
                                        <span
                                            class="bg-success-light text-success border border-success/35 text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full">Đã
                                            xác minh ✓</span>
                                        <span class="text-[10px] text-slate-400 font-bold">3 giờ trước</span>
                                    </div>
                                    <p class="text-xs text-slate-600 leading-relaxed font-medium">
                                        Camera an ninh của cửa hàng tiện lợi Circle K ngay ngã tư tháp nước ghi nhận hình
                                        ảnh bé đi cùng một người phụ nữ trùm kín mặt chạy xe máy đỏ biển số 59-X3... lúc
                                        15:15 ngày 15/05. Gia đình nên báo ngay công an kiểm tra camera công cộng này gấp
                                        nhé!
                                    </p>
                                </div>

                                <div class="flex items-center gap-3 text-[10px] font-bold text-slate-400 pl-2">
                                    <button class="hover:text-primary transition-colors">Phản hồi</button>
                                </div>
                            </div>
                        </div>

                        <!-- Comment Item 3 -->
                        <div class="flex gap-3.5 items-start">
                            <div
                                class="w-10 h-10 rounded-full bg-slate-100 border border-slate-200 flex-shrink-0 flex items-center justify-center font-bold text-slate-500 text-sm">
                                AN</div>
                            <div class="flex-1 space-y-2">
                                <div class="bg-slate-100 p-4 rounded-2xl inline-block max-w-full">
                                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                                        <span class="text-xs font-extrabold text-slate-900">Ẩn danh</span>
                                        <span
                                            class="bg-amber-50 text-amber-700 border border-amber-200 text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full">Đang
                                            xác minh</span>
                                        <span class="text-[10px] text-slate-400 font-bold">5 giờ trước</span>
                                    </div>
                                    <p class="text-xs text-slate-600 leading-relaxed font-medium">
                                        Một người dân sinh sống gần đường Phan Văn Khỏe báo có nhìn thấy bé đi cùng người lạ
                                        trên vỉa hè lúc tầm chiều tối ngày 15/05. Trùng khớp hoàn toàn với áo sọc xanh của
                                        bé.
                                    </p>
                                </div>

                                <div class="flex items-center gap-3 text-[10px] font-bold text-slate-400 pl-2">
                                    <span class="bg-primary/10 text-primary px-2.5 py-1 rounded-full">📍 Vỉa hè Phan Văn
                                        Khỏe, Q.6</span>
                                    <button class="hover:text-primary transition-colors">Phản hồi</button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            <!-- RIGHT: Emergency Action Sidebar (4 cols) -->
            <div class="lg:col-span-4 space-y-6">

                <!-- GPS ACTION CARD (Facebook/TikTok Style high-engagement trigger) -->
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
                        <span class="font-extrabold text-sm tracking-widest uppercase">Bạn đang thấy bé An?</span>
                    </div>

                    <p class="text-xs text-slate-100 leading-relaxed font-medium">
                        Nếu bạn đang đứng cạnh bé hoặc nhìn thấy bé ngay lúc này, hãy ấn nút dưới đây lập tức để chia sẻ toạ
                        độ GPS chính xác. Hệ thống sẽ phát báo động khẩn cấp tới gia đình và cơ quan chức năng trong 3 giây.
                    </p>

                    <button onclick="shareGPS()"
                        class="w-full bg-white text-primary font-black py-4 rounded-2xl hover:bg-slate-50 transition-premium text-sm shadow-md active:scale-98">
                        📍 Chia sẻ vị trí GPS tức thời
                    </button>
                </div>

                <!-- Security Contact block -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-premium space-y-4">
                    <h4 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider">Thông tin liên lạc</h4>
                    <p class="text-xs text-slate-400 leading-relaxed font-semibold">Để chống đăng tin giả và bảo vệ thông
                        tin cá nhân của phụ huynh, bạn cần đăng nhập tài khoản tình nguyện viên đã xác minh danh tính để kết
                        nối.</p>

                    <a href="dang-nhap.html"
                        class="block w-full bg-slate-900 text-white font-extrabold text-xs py-3.5 rounded-2xl text-center hover:bg-slate-800 shadow transition-colors uppercase tracking-widest">
                        Đăng nhập để liên hệ
                    </a>

                    <div
                        class="flex items-center gap-2 justify-center text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                        <span>🔒</span> Mã hóa dữ liệu bảo mật 100%
                    </div>
                </div>

                <!-- Interactive Mini map tracking location -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-premium space-y-4">
                    <h4 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider">Vị trí mất tích</h4>
                    <div id="mini-map"
                        class="h-44 bg-slate-150 rounded-2xl overflow-hidden border border-slate-200 z-10"></div>
                    <p class="text-[10px] text-slate-400 font-semibold text-center uppercase tracking-wide">📍 Chợ Bình
                        Tây, Phường 2, Quận 6, TP.HCM</p>
                </div>

                <!-- Social sharing controls -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-premium space-y-4">
                    <h4 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider">Lan toả để giúp tìm con</h4>

                    <div class="grid grid-cols-2 gap-2">
                        <button
                            class="bg-[#1877F2] text-white text-xs font-extrabold py-3 px-4 rounded-xl hover:bg-[#165ECC] transition-colors shadow-sm">
                            Facebook
                        </button>
                        <button
                            class="bg-[#0068FF] text-white text-xs font-extrabold py-3 px-4 rounded-xl hover:bg-[#0051CC] transition-colors shadow-sm">
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
@endsection
