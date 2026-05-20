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
    <main class="max-w-7xl mx-auto px-4 py-10 space-y-8">

        <!-- KPI Stripe-like CARDS GRID -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">

            <!-- KPI 1 -->
            <div class="bg-white border border-slate-200/80 rounded-3xl p-5 shadow-premium space-y-2">
                <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Tổng ca tiếp nhận</div>
                <div class="text-3xl font-black text-slate-900 tracking-tight">1.602</div>
                <div class="text-[10px] font-bold text-slate-400">
                    <span class="text-success-dark font-extrabold">↑ +12%</span> so với tháng trước
                </div>
            </div>

            <!-- KPI 2 -->
            <div class="bg-white border border-slate-200/80 rounded-3xl p-5 shadow-premium space-y-2">
                <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Ca đã tìm thấy</div>
                <div class="text-3xl font-black text-success tracking-tight">1.247</div>
                <div class="text-[10px] font-bold text-slate-400">
                    Đã bàn giao về gia đình an toàn
                </div>
            </div>

            <!-- KPI 3 -->
            <div class="bg-white border border-slate-200/80 rounded-3xl p-5 shadow-premium space-y-2">
                <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Ca đang tìm gấp</div>
                <div class="text-3xl font-black text-primary tracking-tight">355</div>
                <div class="text-[10px] font-bold text-slate-400 font-bold">
                    🔴 72 giờ qua: 5 trẻ mới lạc
                </div>
            </div>

            <!-- KPI 4 -->
            <div class="bg-white border border-slate-200/80 rounded-3xl p-5 shadow-premium space-y-2">
                <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Thời gian đoàn tụ</div>
                <div class="text-3xl font-black text-brand tracking-tight">48 giờ</div>
                <div class="text-[10px] font-bold text-slate-400">
                    Tính từ lúc phát báo động
                </div>
            </div>

        </div>

        <!-- CHARTS AREA -->
        <div class="grid lg:grid-cols-12 gap-6">

            <!-- Left: Line chart trends (8 cols) -->
            <div class="lg:col-span-8 bg-white border border-slate-200/80 rounded-3xl p-6 shadow-premium space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-extrabold text-slate-800 text-sm uppercase tracking-wider">Xu hướng tìm kiếm thành công
                        theo tháng</h3>
                    <span class="bg-brand/10 text-brand text-[9px] font-black uppercase px-2.5 py-1 rounded-full">Dữ liệu
                        2026</span>
                </div>

                <!-- Chart canvas -->
                <div class="h-80 relative w-full">
                    <canvas id="lineChart" class="w-full h-full"></canvas>
                </div>
            </div>

            <!-- Right: Donut classifications (4 cols) -->
            <div class="lg:col-span-4 bg-white border border-slate-200/80 rounded-3xl p-6 shadow-premium space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-extrabold text-slate-800 text-sm uppercase tracking-wider">Tỷ lệ theo khu vực chính</h3>
                </div>

                <!-- Chart canvas -->
                <div class="h-64 relative flex items-center justify-center">
                    <canvas id="donutChart"></canvas>
                </div>

                <div class="grid grid-cols-3 gap-2 text-center text-[10px] pt-2">
                    <div>
                        <span class="font-black text-primary">● TP.HCM</span>
                        <div class="font-extrabold text-slate-650 mt-0.5">45%</div>
                    </div>
                    <div>
                        <span class="font-black text-brand">● Hà Nội</span>
                        <div class="font-extrabold text-slate-650 mt-0.5">30%</div>
                    </div>
                    <div>
                        <span class="font-black text-slate-400">● Tỉnh khác</span>
                        <div class="font-extrabold text-slate-650 mt-0.5">25%</div>
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
            document.getElementById('mobile-menu').classList.toggle('hidden');
        }

        // Set chart.js default font family to match theme
        Chart.defaults.font.family = "'Be Vietnam Pro', sans-serif";
        Chart.defaults.font.weight = '600';
        Chart.defaults.color = '#94A3B8';

        // Line Chart Implementation
        const ctxLine = document.getElementById('lineChart').getContext('2d');
        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6'],
                datasets: [{
                    label: 'Ca đã đoàn tụ an toàn',
                    data: [120, 150, 180, 220, 260, 312],
                    borderColor: '#12B76A', // Emerald success color
                    backgroundColor: 'rgba(18, 183, 106, 0.05)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 3,
                    pointBackgroundColor: '#12B76A',
                    pointRadius: 4
                }, {
                    label: 'Ca tiếp nhận mới',
                    data: [140, 180, 210, 245, 290, 345],
                    borderColor: '#FF3B30', // Primary Coral red
                    backgroundColor: 'transparent',
                    tension: 0.35,
                    borderWidth: 2,
                    borderDash: [5, 5],
                    pointBackgroundColor: '#FF3B30',
                    pointRadius: 3
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

        // Donut Chart Implementation
        const ctxDonut = document.getElementById('donutChart').getContext('2d');
        new Chart(ctxDonut, {
            type: 'doughnut',
            data: {
                labels: ['TP.HCM', 'Hà Nội', 'Tỉnh thành khác'],
                datasets: [{
                    data: [45, 30, 25],
                    backgroundColor: ['#FF3B30', '#0052FF', '#94A3B8'], // Coral Red, Royal Blue, Slate Grey
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
    </script>
@endpush
