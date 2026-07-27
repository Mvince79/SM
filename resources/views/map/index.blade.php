@extends('layouts.app')
@section('title', 'Bản đồ tìm kiếm trẻ em thất lạc')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@section('content')
    <!-- FULL VIEWPORT MAP CONTAINER -->
    <!-- Trừ chiều cao topnav (sticky top-0 h-16 = 4rem) khỏi 100vh, nếu không trang sẽ
                                                                                         cao hơn màn hình 64px và phần đáy bản đồ (chứa nút ẩn/hiện panel) sẽ bị đẩy
                                                                                         khuất khỏi viewport, phải cuộn xuống mới thấy — chính là lý do nút bị cắt mất nửa.
                                                                                         Dùng style inline để có fallback vh -> dvh (dvh chính xác hơn trên mobile do
                                                                                         thanh địa chỉ trình duyệt co giãn). -->
    <div class="relative" style="height: calc(100vh - 4rem); height: calc(100dvh - 4rem);" x-data="{ panelsOpen: true }">

        <!-- The Leaflet map -->
        <div id="full-map" class="map-container w-full h-full bg-slate-100"></div>

        <!-- C-01 hook: overlay chủ quyền cắm vào đây (class định nghĩa ở map-widgets.css/map-labels.css).
                                                                                             Hiện chưa có nội dung — chỉ đảm bảo z-index đúng vị trí (trên tile, dưới panel UI) sẵn cho khi C-01 hoàn thành. -->
        <div id="map-sovereignty-overlay" class="map-sovereignty-overlay"></div>

        <div id="map-loading" class="absolute inset-0 z-50" aria-busy="true">
            <x-skeletons.map-tiles data-map-loading-skeleton />
        </div>

        <!-- C-05: Nút ẩn/hiện 2 panel (filter + widget) — hiện trên MỌI thiết bị.
                                                                                             To hơn (w-16 h-16) + màu nổi bật + z-[60] (CAO HƠN lớp "Đang tải bản đồ" z-50)
                                                                                             để không bao giờ bị lớp loading (hoặc bất kỳ overlay nào khác) đè mất, đảm bảo luôn bấm được.
                                                                                             bottom-28: widget chat nổi + hotline 111 (x-floating-chat-widget, mọi trang) cũng
                                                                                             neo góc dưới-trái (bottom-5 left-5) → phải đặt CAO HƠN hẳn để 2 cụm nút không đè
                                                                                             lên nhau (trước để bottom-6/8 nên bị chồng lên đúng vị trí widget chat). -->
        <div class="absolute bottom-28 md:bottom-32 left-4 z-[60] flex flex-col items-start gap-1.5 pointer-events-auto">
            <span class="bg-slate-900 text-white text-xs font-bold px-2.5 py-1 rounded-full shadow"
                x-text="panelsOpen ? 'Ẩn bộ lọc' : 'Hiện bộ lọc'"></span>
            <button type="button" @click="panelsOpen = !panelsOpen" aria-label="Ẩn/hiện bộ lọc và widget bản đồ"
                class="w-16 h-16 rounded-full bg-primary text-white border-4 border-white shadow-xl flex items-center justify-center active:scale-95 hover:scale-105 hover:shadow-2xl transition-all"
                style="box-shadow: 0 0 0 2px rgba(0,0,0,0.06), 0 8px 24px rgba(0,0,0,0.35);">
                <x-heroicon-o-eye-slash x-show="panelsOpen" x-cloak class="w-7 h-7" />
                <x-heroicon-o-eye x-show="!panelsOpen" x-cloak class="w-7 h-7" />
            </button>
        </div>

        <!-- FLOATING CONTROL PANELS (C-04 filter + C-02 widget)
                                                                                             .map-floating-panels (map-widgets.css) tự xếp dọc 1 cột ở < 640px
                                                                                             (tránh chồng nhau như bug cũ), tách 2 cột trái/phải từ 640px trở lên.
                                                                                             Nút toggle (C-05) giờ hiện trên MỌI thiết bị, panelsOpen mặc định true
                                                                                             nên desktop/tablet vẫn thấy panel ngay khi vào trang, nhưng người dùng
                                                                                             có thể chủ động ẩn/hiện trên mọi kích thước màn hình. -->
        <div class="map-floating-panels" x-show="panelsOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2">

            <!-- Filter Card -->
            <div class="w-full sm:w-80 max-w-sm space-y-3">
                <div class="glass-premium p-4 sm:p-5 rounded-3xl shadow-premium border border-slate-200/50 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-200/40 pb-2">
                        <h3 class="font-black text-slate-900 text-sm tracking-tight flex items-center gap-1.5">
                            <x-heroicon-o-globe-alt class="w-4 h-4" /> Bản Đồ Tìm Kiếm
                        </h3>
                        <span
                            class="bg-primary/10 text-primary text-[9px] font-black uppercase px-2 py-0.5 rounded-full">REALTIME</span>
                    </div>

                    <!-- Quick search input in card -->
                    <div class="relative">
                        <x-heroicon-o-magnifying-glass
                            class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" />
                        <input type="text" placeholder="Tìm theo tỉnh thành, quận huyện..."
                            class="w-full pl-8 pr-3 py-2 border border-slate-200/70 rounded-xl text-xs focus:outline-none focus:ring-1 focus:ring-primary focus:border-transparent bg-white/70" />
                    </div>

                    <!-- C-04: Bộ lọc bản đồ nâng cao (thời gian / trạng thái / manh mối mới) -->
                    @livewire('map.map-filters')

                    <!-- Quick locating button -->
                    <button onclick="locateMe()"
                        class="w-full bg-slate-900 text-white font-extrabold text-xs py-2.5 rounded-xl hover:bg-slate-800 transition-colors shadow flex items-center justify-center gap-1">
                        <x-heroicon-o-cursor-arrow-rays class="w-4 h-4" /> Vị trí của tôi </button>
                    <button id="toggle-heatmap" type="button"
                        class="w-full bg-red-600 text-white font-extrabold text-xs py-2.5 rounded-xl mt-2">
                        Heatmap: Tắt
                    </button>

                </div>
            </div>

            <!-- Widget C-02: Gần đây có bao nhiêu trẻ mất tích -->
            <div class="flex sm:block justify-end">
                <x-map.nearby-widget />
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.heat/dist/leaflet-heat.js"></script>
    <script src="{{ asset('js/map/vn-sovereignty-layer.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function toggleMenu() {
                document.getElementById('mobile-menu').classList.toggle('hidden');
            }

            // Base URL prefix for missing detail routes
            const missingDetailBase = '/missing';

            // Initialize main map tracking the country center
            const map = L.map('full-map', {
                zoomControl: false
            }).setView([16.0544, 108.2022], 6); // Da Nang center center
            initVnSovereigntyLayer(map);
            // Custom Zoom Control placed to the bottom-right for thumb ease
            L.control.zoom({
                position: 'bottomright'
            }).addTo(map);

            const loadingEl = document.getElementById('map-loading');

            // An toàn: nếu vì lý do gì đó (mạng chậm/tile lỗi) mà event 'load' của
            // tileLayer không bao giờ bắn, lớp overlay "Đang tải bản đồ" (z-50) sẽ
            // che kín màn hình vĩnh viễn và chặn MỌI thao tác bấm bên dưới (kể cả
            // nút ẩn/hiện panel). Đặt timeout dự phòng để luôn tự ẩn sau 6s.
            const loadingReasons = new Set(['tiles', 'markers']);
            const syncLoadingOverlay = function () {
                const isLoading = loadingReasons.size > 0;
                loadingEl?.classList.toggle('hidden', !isLoading);
                loadingEl?.setAttribute('aria-busy', isLoading ? 'true' : 'false');
            };
            const startLoading = function (reason) {
                loadingReasons.add(reason);
                syncLoadingOverlay();
            };
            const finishLoading = function (reason) {
                loadingReasons.delete(reason);
                syncLoadingOverlay();
            };

            setTimeout(function () {
                loadingReasons.clear();
                syncLoadingOverlay();
            }, 6000);

            // PB-UI-10: theo yêu cầu, bản đồ LUÔN dùng tile sáng (OpenStreetMap)
            // bất kể trang đang ở light hay dark mode — không đổi sang tile tối
            // (CartoDB Dark Matter) nữa vì tile tối quá khó nhìn dù đã chỉnh filter.
            const tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            });

            tileLayer.on('loading', function () {
                startLoading('tiles');
            });

            tileLayer.on('load', function () {
                finishLoading('tiles');
            });

            tileLayer.addTo(map);

            // Gom toàn bộ marker vào 1 layerGroup để dễ clear/vẽ lại khi đổi filter (C-04)
            const markersLayer = L.layerGroup().addTo(map);
            let heatLayer = null;
            let heatmapEnabled = false;
            // C-05: popup marker phải luôn nổi trên panel filter/widget.
            // #full-map là 1 stacking context riêng (Leaflet set position:relative),
            // nên phải nâng z-index của chính #full-map (qua class .has-open-popup,
            // định nghĩa ở map-widgets.css) chứ không phải nâng popup.
            const fullMapEl = document.getElementById('full-map');
            map.on('popupopen', () => fullMapEl.classList.add('has-open-popup'));
            map.on('popupclose', () => fullMapEl.classList.remove('has-open-popup'));

            function buildIcon(type) {
                const color = type === 'found'
                    ? '#22C55E'
                    : '#FF3B30';

                return L.divIcon({
                    className: 'pulse-dot',
                    html: `<div style="width:14px;height:14px;background:${color};border:2px solid white;border-radius:50%;box-shadow:0 0 10px ${color}66;"></div>`,
                    iconSize: [14, 14],
                    iconAnchor: [7, 7]
                });
            }

            function loadMarkers(queryString = '') {
                startLoading('markers');

                fetch('/api/v1/map-data' + queryString)
                    .then(response => response.json())
                    .then(result => {

                        markersLayer.clearLayers();



                        result.data.forEach((kid) => {



                            const popupHtml = `
                                            <div class="flex gap-3 items-center min-w-[240px] max-w-[280px]">

                                                <div class="w-[60px] h-[60px] rounded-2xl overflow-hidden border border-slate-200 flex-shrink-0">
                                                    <img
                                                        src="${kid.thumb_url}"
                                                        class="w-full h-full object-cover"
                                                        alt="${kid.name}"
                                                    >
                                                </div>

                                                <div class="min-w-0 flex-1">

                                                    <div class="font-black text-sm text-slate-900">
                                                        ${kid.name}
                                                    </div>

                                                    <div class="text-[11px] text-slate-500 font-semibold mt-1">
                                                        ${kid.age ? kid.age + ' tuổi' : 'Chưa rõ tuổi'}
                                                    </div>

                                                    <div class="text-[11px] text-slate-500 font-semibold flex items-center gap-1 mt-0.5">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 text-slate-400 shrink-0">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                                        </svg>
                                                        ${kid.province_name ?? 'Chưa rõ'}
                                                    </div>

                                                    <div class="text-[11px] text-slate-500 font-semibold flex items-center gap-1 mt-0.5">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 text-slate-400 shrink-0">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                                        </svg>
                                                        ${kid.missing_date ?? 'Chưa rõ'}
                                                    </div>

                                                    <a
                                                        href="${kid.url}"
                                                        class="inline-flex mt-2 text-[11px] font-black text-primary hover:underline"
                                                    >
                                                        Xem chi tiết →
                                                    </a>

                                                </div>

                                            </div>
                                            `;

                            L.marker([kid.lat, kid.lng], {
                                icon: buildIcon(kid.type)
                            })
                                .addTo(markersLayer)
                                .bindPopup(popupHtml, {
                                    maxWidth: window.innerWidth - 32
                                });

                        });


                    })
                    .catch(error => {
                        console.error(error);
                    })
                    .finally(() => {
                        finishLoading('markers');
                    });
            }
            function loadHeatmap(queryString = '') {
                fetch('/map/heatmap-data' + queryString)
                    .then(response => response.json())
                    .then(result => {
                        const heatPoints = result.data ?? [];

                        if (heatLayer) {
                            map.removeLayer(heatLayer);
                        }

                        heatLayer = L.heatLayer(heatPoints, {
                            radius: 28,
                            blur: 22,
                            maxZoom: 18,
                            gradient: {
                                0.2: '#22c55e',
                                0.5: '#eab308',
                                0.8: '#f97316',
                                1.0: '#dc2626',
                            },
                        });

                        if (heatmapEnabled) {
                            heatLayer.addTo(map);
                        }
                    })
                    .catch(error => {
                        console.error('Lỗi tải heatmap:', error);
                    });
            }
            // Tải marker lần đầu (không filter)
            loadMarkers();
            loadHeatmap();
            // C-04: Livewire MapFilters bắn event này mỗi khi người dùng đổi filter
            Livewire.on('map-filters-changed', ({ filters }) => {
                const params = new URLSearchParams(filters).toString();
                const queryString = params ? '?' + params : '';

                loadMarkers(queryString);
                loadHeatmap(queryString);
            });

            // Locate the client device location
            document
                .getElementById('toggle-heatmap')
                ?.addEventListener('click', function () {

                    heatmapEnabled = !heatmapEnabled;

                    this.textContent = heatmapEnabled
                        ? 'Heatmap: Bật'
                        : 'Heatmap: Tắt';

                    if (!heatLayer) {
                        return;
                    }

                    if (heatmapEnabled) {
                        heatLayer.addTo(map);
                    } else {
                        map.removeLayer(heatLayer);
                    }
                });
            window.locateMe = function () {
                map.locate({
                    setView: true,
                    maxZoom: 14
                });

                map.on('locationfound', (e) => {
                    const myIcon = L.divIcon({
                        html: '<div style="width:14px;height:14px;background:#0052FF;border:2px solid white;border-radius:50%;box-shadow:0 0 0 4px rgba(0,82,255,0.25);"></div>',
                        iconSize: [14, 14],
                        iconAnchor: [7, 7]
                    });

                    L.marker(e.latlng, {
                        icon: myIcon
                    }).addTo(map).bindPopup(
                        `<div class='font-bold text-xs p-1 text-slate-900 flex items-center gap-1'>
                                                                                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-primary">
                                                                                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                                                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                                                                                                        </svg>
                                                                                                                        Vị trí hiện tại của bạn
                                                                                                                    </div>`
                    ).openPopup();
                });

                map.on('locationerror', () => alert(
                    'Không thể xác minh vị trí của bạn. Vui lòng cấp quyền truy cập GPS cho trình duyệt.'
                ));
            };
        });
    </script>
@endpush