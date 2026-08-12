@extends('layouts.app')
@section('title', 'Thống kê số liệu')

@section('content')
    <!-- PAGE HEADER -->
    <div class="bg-white border-b border-slate-100 py-8 px-4">
        <div class="max-w-7xl mx-auto space-y-2">
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Thống kê số liệu công khai</h1>
            <p class="text-slate-500 text-sm font-semibold">Minh bạch thông tin hoạt động hỗ trợ cộng đồng tìm kiếm trẻ lạc
                tại Việt Nam</p>
        </div>
    </div>

    <!-- MAIN ANALYTICS DASHBOARD -->
    <main class="max-w-7xl min-w-0 mx-auto px-4 py-8 sm:py-10 space-y-6 sm:space-y-8 overflow-x-hidden">

        <!-- KPI Stripe-like CARDS GRID -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 sm:gap-6">

            <!-- KPI 1 -->
            <div class="bg-white border border-slate-200/80 rounded-3xl p-5 shadow-premium space-y-2">
                <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Tổng ca tiếp nhận</div>
                <div id="totalStat" class="text-3xl font-black text-slate-900 tracking-tight">0</div>
                <div class="text-[10px] font-bold text-slate-400">
                    Dữ liệu thống kê từ hệ thống
                </div>
            </div>

            <!-- KPI 2 -->
            <div class="bg-white border border-slate-200/80 rounded-3xl p-5 shadow-premium space-y-2">
                <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Ca đã tìm thấy</div>
                <div id="foundStat" class="text-3xl font-black text-success tracking-tight">0</div>
                <div class="text-[10px] font-bold text-slate-400">
                    Đã bàn giao về gia đình an toàn
                </div>
            </div>

            <!-- KPI 3 -->
            <div class="bg-white border border-slate-200/80 rounded-3xl p-5 shadow-premium space-y-2">
                <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Ca đang tìm</div>
                <div id="pendingStat" class="text-3xl font-black text-primary tracking-tight">0</div>
                <div class="text-[10px] font-bold text-slate-400 font-bold">
                    Các hồ sơ đang cần cộng đồng hỗ trợ
                </div>
            </div>

            <!-- KPI 4 -->
            <div class="bg-white border border-slate-200/80 rounded-3xl p-5 shadow-premium space-y-2">
                <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Tỷ lệ tìm thấy</div>
                <div class="text-3xl font-black text-brand tracking-tight">
                    <span id="foundRateStat">0</span>%
                </div>
                <div class="text-[10px] font-bold text-slate-400">
                    Tính trên tổng hồ sơ tiếp nhận
                </div>
            </div>

            <!-- KPI 5 -->
            <div class="bg-white border border-slate-200/80 rounded-3xl p-5 shadow-premium space-y-2">
                <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">
                    Thời gian tìm thấy TB
                </div>

                <div class="text-3xl font-black text-slate-900 tracking-tight">
                    <span id="averageFoundDaysStat">0</span>
                    <span class="text-base">ngày</span>
                </div>

                <div class="text-[10px] font-bold text-slate-400">
                    Trung bình từ ngày mất tích đến khi tìm thấy
                </div>
            </div>
        </div>

        <!-- CHARTS AREA -->
        <div class="grid lg:grid-cols-12 gap-6">

            <!-- Left: Line chart trends (8 cols) -->
            <div
                class="min-w-0 lg:col-span-8 bg-white border border-slate-200/80 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-premium space-y-4">
                <div
                    class="flex flex-col items-start gap-2 min-[360px]:flex-row min-[360px]:items-center min-[360px]:justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-extrabold text-slate-800 text-sm uppercase tracking-wider">Xu hướng hồ sơ tiếp nhận
                        theo tháng</h3>
                    <span class="bg-brand/10 text-brand text-[9px] font-black uppercase px-2.5 py-1 rounded-full">Dữ liệu
                        2026</span>
                </div>

                <!-- Chart canvas -->
                <div class="h-64 sm:h-80 relative w-full min-w-0 overflow-hidden">
                    <canvas id="lineChart" class="max-w-full w-full h-full"></canvas>
                </div>
            </div>

            <!-- Right: Donut classifications (4 cols) -->
            <div
                class="min-w-0 lg:col-span-4 bg-white border border-slate-200/80 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-premium space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-extrabold text-slate-800 text-sm uppercase tracking-wider">Tỷ lệ theo khu vực chính</h3>
                </div>

                <!-- Chart canvas -->
                <div class="h-56 sm:h-64 min-w-0 overflow-hidden relative flex items-center justify-center">
                    <canvas id="donutChart" class="max-w-full"></canvas>
                </div>

                <div id="provinceLegend"
                    class="grid grid-cols-2 min-[360px]:grid-cols-3 gap-2 text-center text-[10px] pt-2">
                    <div>
                        <span class="font-black text-primary">● Đang tải</span>
                        <div class="font-extrabold text-slate-650 mt-0.5">0%</div>
                    </div>
                    <div>
                        <span class="font-black text-brand">● Đang tải</span>
                        <div class="font-extrabold text-slate-650 mt-0.5">0%</div>
                    </div>
                    <div>
                        <span class="font-black text-slate-400">● Đang tải</span>
                        <div class="font-extrabold text-slate-650 mt-0.5">0%</div>
                    </div>
                </div>
            </div>

        </div>

    </main>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function toggleMenu() {
            document.getElementById('mobile-menu')?.classList.toggle('hidden');
        }

        Chart.defaults.font.family = "'Be Vietnam Pro', sans-serif";
        Chart.defaults.font.weight = '600';
        Chart.defaults.color = '#94A3B8';

        fetch('/api/v1/stats')
            .then(response => response.json())
            .then(data => {
                document.getElementById('totalStat').innerText = data.total ?? 0;
                document.getElementById('foundStat').innerText = data.found ?? 0;
                document.getElementById('pendingStat').innerText = data.pending ?? 0;
                document.getElementById('foundRateStat').innerText = data.found_rate_percent ?? 0;
                document.getElementById('averageFoundDaysStat').innerText =
                    data.average_found_days ?? 0;
                const monthly = data.trend_monthly ?? [];
                const monthlyLabels = monthly.map(item => item.month);
                const monthlyData = monthly.map(item => item.count);

                const provinces = data.missing_by_province ?? [];
                const provinceLabels = provinces.map(item => item.name);
                const provinceData = provinces.map(item => item.count);

                const totalProvince = provinceData.reduce((sum, count) => sum + Number(count), 0) || 1;

                // 1 dataset duy nhất (đã bỏ dataset trùng lặp "Dữ liệu thống kê")
                // Màu theo đúng token thương hiệu --color-primary (carmine)
                const ctxLine = document.getElementById('lineChart').getContext('2d');
                new Chart(ctxLine, {
                    type: 'bar',
                    data: {
                        labels: monthlyLabels,
                        datasets: [{
                            label: 'Hồ sơ tiếp nhận',
                            data: monthlyData,
                            borderColor: '#b9293f',
                            backgroundColor: '#b9293f',
                            borderRadius: 6,
                            borderWidth: 0,
                            maxBarThickness: 36
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    boxWidth: 12,
                                    padding: 20,
                                    color: '#475569'
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                grid: {
                                    color: '#F1F5F9'
                                }
                            }
                        }
                    }
                });

                // Bảng màu 5 sắc thái trầm, cùng hệ với thương hiệu
                // (carmine → navy → forest → đồng/gold → xám trung tính)
                const ctxDonut = document.getElementById('donutChart').getContext('2d');
                new Chart(ctxDonut, {
                    type: 'doughnut',
                    data: {
                        labels: provinceLabels,
                        datasets: [{
                            data: provinceData,
                            backgroundColor: ['#b9293f', '#1c3a5e', '#2f6d52', '#8a6d3b', '#94a3b8'],
                            borderWidth: 3,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        cutout: '70%'
                    }
                });

                // Màu khớp đúng thứ tự backgroundColor của donut chart phía trên
                const legendColors = ['text-primary', 'text-brand', 'text-[#2f6d52]', 'text-[#8a6d3b]', 'text-slate-400'];
                const topFour = provinces.slice(0, 4);
                const restCount = provinces.slice(4).reduce((sum, item) => sum + Number(item.count), 0);

                const legendItems = topFour.map((item, index) => ({
                    label: item.name,
                    percent: Math.round((Number(item.count) / totalProvince) * 100),
                    color: legendColors[index] ?? 'text-slate-400'
                }));

                // Gộp toàn bộ khu vực còn lại (nếu có) thành 1 mục "Khác" để không mất dữ liệu khỏi chú thích
                if (restCount > 0) {
                    legendItems.push({
                        label: 'Khác',
                        percent: Math.round((restCount / totalProvince) * 100),
                        color: 'text-slate-400'
                    });
                }

                document.getElementById('provinceLegend').innerHTML = legendItems.map(item => `
                                        <div>
                                            <span class="font-black ${item.color}">● ${item.label}</span>
                                            <div class="font-extrabold text-slate-650 mt-0.5">${item.percent}%</div>
                                        </div>
                                    `).join('');
            })
            .catch(() => {
                document.getElementById('totalStat').innerText = 0;
                document.getElementById('foundStat').innerText = 0;
                document.getElementById('pendingStat').innerText = 0;
                document.getElementById('foundRateStat').innerText = 0;
                document.getElementById('averageFoundDaysStat').innerText = 0;
            });
    </script>
@endpush