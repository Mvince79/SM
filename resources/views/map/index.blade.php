@extends('layouts.app')
@section('title', 'Bản đồ tìm kiếm trẻ em thất lạc')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@section('content')
    <!-- FULL VIEWPORT MAP CONTAINER -->
    <div class="relative z-10 h-screen">

        <!-- The Leaflet map -->
        <div id="full-map" class="w-full h-full bg-slate-100 z-10"></div>
        <div id="map-loading" class="absolute inset-0 z-50 flex items-center justify-center bg-white/70">

            <div class="bg-white rounded-2xl shadow-xl px-5 py-4 flex items-center gap-3 border border-slate-200">

                <div class="w-6 h-6 rounded-full border-4 border-slate-200 border-t-red-500 animate-spin">
                </div>

                <div>
                    <div class="text-sm font-black text-slate-900">
                        Đang tải bản đồ
                    </div>

                    <div class="text-xs text-slate-500">
                        Vui lòng chờ...
                    </div>
                </div>

            </div>
        </div>
        <!-- FLOATING CONTROL PANEL (Premium glassmorphism Card) -->
        <div class="absolute z-1 top-4 left-4 z-40 max-w-sm w-[90%] sm:w-80 space-y-3 pointer-events-auto">
            <!-- Filter Card -->
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

            </div>

        </div>

        <!-- Widget C-02: Gần đây có bao nhiêu trẻ mất tích -->
        <div class="absolute top-4 right-4 z-40 pointer-events-none">
            <x-map.nearby-widget />
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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

            const tileLayer = L.tileLayer(
                'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19
                }
            );

            tileLayer.on('loading', function () {
                loadingEl?.classList.remove('hidden');
            });

            tileLayer.on('load', function () {
                loadingEl?.classList.add('hidden');
            });

            tileLayer.addTo(map);

            // Gom toàn bộ marker vào 1 layerGroup để dễ clear/vẽ lại khi đổi filter (C-04)
            const markersLayer = L.layerGroup().addTo(map);

            function buildIcon(status) {
                const color = status === 'FOUND' ? '#22C55E' : '#FF3B30';
                return L.divIcon({
                    className: 'pulse-dot',
                    html: `<div style="width:14px;height:14px;background:${color};border:2px solid white;border-radius:50%;box-shadow:0 0 10px ${color}66;"></div>`,
                    iconSize: [14, 14],
                    iconAnchor: [7, 7]
                });
            }

            function loadMarkers(queryString = '') {
                loadingEl?.classList.remove('hidden');

                fetch('/api/v1/map-data' + queryString)
                    .then(response => response.json())
                    .then(result => {
                        markersLayer.clearLayers();

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
                                icon: buildIcon(kid.status)
                            })
                                .addTo(markersLayer)
                                .bindPopup(
                                    popupHtml,
                                    {
                                        maxWidth: window.innerWidth - 32
                                    }
                                );

                        });

                    })
                    .catch(error => {
                        console.error(error);
                    })
                    .finally(() => {
                        loadingEl?.classList.add('hidden');
                    });
            }

            // Tải marker lần đầu (không filter)
            loadMarkers();

            // C-04: Livewire MapFilters bắn event này mỗi khi người dùng đổi filter
            Livewire.on('map-filters-changed', ({ filters }) => {
                const params = new URLSearchParams(filters).toString();
                loadMarkers('?' + params);
            });

            // Locate the client device location
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