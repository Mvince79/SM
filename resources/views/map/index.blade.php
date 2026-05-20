@extends('layouts.app')
@section('title', 'Bản đồ tìm kiếm trẻ em thất lạc')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@section('content')
    <!-- FULL VIEWPORT MAP CONTAINER -->
    <div class="relative z-10 h-screen">

        <!-- The Leaflet map -->
        <div id="full-map" class="w-full h-full bg-slate-100"></div>

        <!-- FLOATING CONTROL PANEL (Premium glassmorphism Card) -->
        <div class="absolute top-4 left-4 z-40 max-w-sm w-[90%] sm:w-80 space-y-3 pointer-events-auto">

            <!-- Filter Card -->
            <div class="glass-premium p-4 sm:p-5 rounded-3xl shadow-premium border border-slate-200/50 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200/40 pb-2">
                    <h3 class="font-black text-slate-900 text-sm tracking-tight flex items-center gap-1.5">
                        🌍 Bản Đồ Tìm Kiếm
                    </h3>
                    <span
                        class="bg-primary/10 text-primary text-[9px] font-black uppercase px-2 py-0.5 rounded-full">REALTIME</span>
                </div>

                <!-- Quick search input in card -->
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" placeholder="Tìm theo tỉnh thành, quận huyện..."
                        class="w-full pl-8 pr-3 py-2 border border-slate-200/70 rounded-xl text-xs focus:outline-none focus:ring-1 focus:ring-primary focus:border-transparent bg-white/70" />
                </div>

                <!-- Categories toggle checkboxes -->
                <div class="space-y-2 text-xs">
                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Loại điểm hiển thị</div>

                    <label
                        class="flex items-center gap-2.5 cursor-pointer p-1.5 hover:bg-white/40 rounded-lg transition-colors">
                        <input type="checkbox" checked class="accent-primary" />
                        <span
                            class="w-2.5 h-2.5 bg-primary rounded-full border border-white shadow-sm flex-shrink-0"></span>
                        <span class="font-bold text-slate-700">Trẻ đang cần tìm (355)</span>
                    </label>

                    <label
                        class="flex items-center gap-2.5 cursor-pointer p-1.5 hover:bg-white/40 rounded-lg transition-colors">
                        <input type="checkbox" checked class="accent-[#F59E0B]" />
                        <span
                            class="w-2.5 h-2.5 bg-[#F59E0B] rounded-full border border-white shadow-sm flex-shrink-0"></span>
                        <span class="font-bold text-slate-700">Địa bàn có manh mối (18)</span>
                    </label>

                    <label
                        class="flex items-center gap-2.5 cursor-pointer p-1.5 hover:bg-white/40 rounded-lg transition-colors">
                        <input type="checkbox" checked class="accent-success" />
                        <span
                            class="w-2.5 h-2.5 bg-success rounded-full border border-white shadow-sm flex-shrink-0"></span>
                        <span class="font-bold text-slate-700">Trẻ đã đoàn tụ an toàn</span>
                    </label>
                </div>

                <!-- Quick locating button -->
                <button onclick="locateMe()"
                    class="w-full bg-slate-900 text-white font-extrabold text-xs py-2.5 rounded-xl hover:bg-slate-800 transition-colors shadow flex items-center justify-center gap-1">
                    🛰️ Vị trí của tôi
                </button>

            </div>

        </div>
    </div>
@endsection


@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function toggleMenu() {
                document.getElementById('mobile-menu').classList.toggle('hidden');
            }

            // Initialize main map tracking the country center
            const map = L.map('full-map', {
                zoomControl: false
            }).setView([16.0544, 108.2022], 6); // Da Nang center center

            // Custom Zoom Control placed to the bottom-right for thumb ease
            L.control.zoom({
                position: 'bottomright'
            }).addTo(map);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map);

            // Mock markers for children
            const kidsList = [{
                    name: "Nguyễn Văn An",
                    age: 8,
                    location: "Chợ Bình Tây, Q.6, TP.HCM",
                    lat: 10.7553,
                    lng: 106.6608,
                    img: "https://api.dicebear.com/7.x/thumbs/svg?seed=An1&backgroundColor=ffdfbf"
                },
                {
                    name: "Trần Thị Linh",
                    age: 6,
                    location: "Hồ Tây, Hà Nội",
                    lat: 21.0504,
                    lng: 105.8122,
                    img: "https://api.dicebear.com/7.x/thumbs/svg?seed=Linh2&backgroundColor=b6e3f4"
                },
                {
                    name: "Phạm Minh Đức",
                    age: 12,
                    location: "Mỹ Khê, Đà Nẵng",
                    lat: 16.0689,
                    lng: 108.2452,
                    img: "https://api.dicebear.com/7.x/thumbs/svg?seed=Duc&backgroundColor=ffd5dc"
                },
                {
                    name: "Lê Thị Hoa",
                    age: 9,
                    location: "Bến Ninh Kiều, Cần Thơ",
                    lat: 10.0336,
                    lng: 105.7876,
                    img: "https://api.dicebear.com/7.x/thumbs/svg?seed=Hoa&backgroundColor=c0aede"
                },
                {
                    name: "Ngô Tuấn Kiệt",
                    age: 7,
                    location: "KCN VSIP 2, Bình Dương",
                    lat: 11.0558,
                    lng: 106.6800,
                    img: "https://api.dicebear.com/7.x/thumbs/svg?seed=Tuan&backgroundColor=d1fae5"
                }
            ];

            // Render lost children markers with red glowing pulse ring
            kidsList.forEach(kid => {
                const lostIcon = L.divIcon({
                    className: 'pulse-dot',
                    html: '<div style="width:14px;height:14px;background:#FF3B30;border:2px solid white;border-radius:50%;box-shadow:0 0 10px rgba(255,59,48,0.4);"></div>',
                    iconSize: [14, 14],
                    iconAnchor: [7, 7]
                });

                const popupHtml = `
      <div class="flex gap-3 items-center min-w-[220px]">
        <div class="w-12 h-12 rounded-xl overflow-hidden bg-slate-900 border border-slate-200 flex-shrink-0">
          <img src="${kid.img}" class="w-full h-full object-cover" />
        </div>
        <div class="min-w-0">
          <div class="font-extrabold text-xs text-slate-900">${kid.name}</div>
          <div class="text-[10px] text-slate-400 font-bold mt-0.5">${kid.age} tuổi · 📍 Bị lạc ở:</div>
          <div class="text-[10px] text-slate-600 font-semibold truncate mt-0.5">${kid.location}</div>
          <a href="{{ route('missing.detail') }}" class="inline-flex items-center gap-0.5 text-[10px] font-black text-primary hover:underline mt-1.5 uppercase">
            Hồ sơ chi tiết →
          </a>
        </div>
      </div>
    `;

                L.marker([kid.lat, kid.lng], {
                    icon: lostIcon
                }).addTo(map).bindPopup(popupHtml);
            });

            // Locate the client device location
            function locateMe() {
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
                            "<div class='font-bold text-xs p-1 text-slate-900'>🛰️ Vị trí hiện tại của bạn</div>"
                            )
                        .openPopup();
                });
                map.on('locationerror', () => alert(
                    'Không thể xác minh vị trí của bạn. Vui lòng cấp quyền truy cập GPS cho trình duyệt.'));
            }
        })
    </script>
@endpush