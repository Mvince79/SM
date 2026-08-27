@props([
    'lat' => null,
    'lng' => null,
    'title' => 'Điểm tiếp nhận gần nhất',
])

@php
    // U-02 — Khối gợi ý 3 điểm tiếp nhận gần nhất (top-3 + "Xem tất cả").
    // Gọi API V-04 (GET /diem-ban-giao/nearest?lat=&lng=) bằng fetch khi trang load,
    // ẩn khối nếu không có kết quả. Mỗi thẻ: tên, khoảng cách (km), nút tel: hotline.
    $hasCoords = is_numeric($lat) && is_numeric($lng)
        && (float) $lat >= -90 && (float) $lat <= 90
        && (float) $lng >= -180 && (float) $lng <= 180;
@endphp

@if ($hasCoords)
    <div x-data="nearestHandoverPoints(@js((float) $lat), @js((float) $lng))"
         x-init="init()"
         class="bg-white border border-slate-200/80 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-premium space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                    <x-heroicon-o-building-office-2 class="w-5 h-5" />
                </div>
                <div>
                    <h4 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider">{{ $title }}</h4>
                    <p class="text-xs text-slate-500">Gợi ý theo vị trí trẻ được tìm thấy</p>
                </div>
            </div>
            <a href="{{ route('handover-points.index', ['lat' => $lat, 'lng' => $lng]) }}"
               class="section-link text-xs font-bold text-primary hover:underline whitespace-nowrap">
                Xem tất cả →
            </a>
        </div>

        <div x-show="loading" class="space-y-2">
            <div class="h-16 bg-slate-100 rounded-2xl animate-pulse"></div>
            <div class="h-16 bg-slate-100 rounded-2xl animate-pulse"></div>
        </div>

        <div x-show="!loading && points.length === 0" x-cloak
             class="text-xs text-slate-400 text-center py-3">
            Không có điểm tiếp nhận nào gần vị trí này.
        </div>

        <div x-show="!loading && points.length > 0" x-cloak class="grid grid-cols-1 gap-3">
            <template x-for="(p, i) in points" :key="p.id">
                <div class="found-card p-3.5 bg-success-light/50 hover:bg-success-light rounded-2xl border border-success/20 transition-all">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-slate-800 truncate" x-text="p.name"></p>
                            <p class="text-[11px] text-slate-500 mt-0.5" x-text="(p.distance_km !== null ? 'Cách ' + p.distance_km + ' km' : '')"></p>
                        </div>
                        <span x-show="i === 0"
                              class="text-[10px] font-black px-2 py-0.5 rounded-md bg-success text-white shrink-0">Gần nhất</span>
                    </div>
                    <template x-if="p.hotline">
                        <a :href="'tel:' + p.hotline"
                           class="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-success px-3 py-1.5 text-white text-xs font-bold hover:opacity-90 transition-opacity">
                            <x-heroicon-o-phone class="w-4 h-4" /> Gọi <span x-text="p.hotline"></span>
                        </a>
                    </template>
                </div>
            </template>
        </div>
    </div>
@endif

@once
    <script>
        function nearestHandoverPoints(lat, lng) {
            return {
                loading: true,
                points: [],
                init() {
                    const url = `${window.location.origin}/diem-ban-giao/nearest?lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}`;
                    fetch(url, { headers: { 'Accept': 'application/json' } })
                        .then(r => r.json())
                        .then(data => {
                            this.points = (data.data || []).slice(0, 3);
                        })
                        .catch(() => { this.points = []; })
                        .finally(() => { this.loading = false; });
                }
            };
        }
    </script>
@endonce
