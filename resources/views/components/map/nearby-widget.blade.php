@props(['radiusOptions' => [5, 10, 20, 50]])

<div x-data="nearbyWidget()" x-init="init()"
    class="glass-premium rounded-3xl shadow-premium border border-slate-200/50 p-4 sm:p-5 space-y-3 w-full sm:w-72 pointer-events-auto">

    <div class="flex items-center justify-between border-b border-slate-200/40 pb-2">
        <h3 class="font-black text-slate-900 text-sm flex items-center gap-1.5">
            <x-heroicon-o-map class="w-4 h-4 text-primary" /> Trẻ mất tích gần đây
        </h3>
    </div>

    <select x-model.number="radius" @change="fetchNearby()"
        class="w-full px-3 py-2 border border-slate-200/70 rounded-xl text-xs font-bold bg-white/70 focus:outline-none focus:ring-1 focus:ring-primary">
        @foreach($radiusOptions as $r)
            <option value="{{ $r }}">Trong bán kính {{ $r }} km</option>
        @endforeach
    </select>

    <template x-if="locating">
        <div class="flex items-center gap-2 text-xs text-slate-400 font-bold py-2">
            <x-heroicon-o-arrow-path class="w-4 h-4 animate-spin" /> Đang xác định vị trí...
        </div>
    </template>

    <template x-if="!locating && locationError && !usingDefaultLocation">
        <p class="text-xs text-danger font-bold py-1" x-text="locationError"></p>
    </template>

    <template x-if="!locating && (!locationError || usingDefaultLocation)">
        <div class="pt-1 space-y-1.5">
            <template x-if="usingDefaultLocation">
                <p class="text-[10px] text-slate-400 font-semibold leading-snug" x-text="locationError"></p>
            </template>
            <div class="flex items-end justify-between">
                <div>
                    <div class="text-3xl font-black text-slate-900" x-text="count"></div>
                    <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wide">
                        trẻ trong <span x-text="radius"></span>km
                    </div>
                </div>
                <button type="button" @click="showList = true"
                    class="text-[11px] font-black text-primary hover:underline flex items-center gap-1">
                    Xem danh sách <x-heroicon-o-chevron-right class="w-3 h-3" />
                </button>
            </div>
        </div>
    </template>

    {{-- Panel danh sách bên hông --}}
    <div x-show="showList" x-cloak @click.self="showList = false" class="fixed inset-0 z-[55] bg-black/30"></div>

    <div x-show="showList" x-cloak x-transition:enter="transition ease-out duration-250"
        x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed inset-y-0 right-0 z-[60] w-full sm:w-96 bg-white shadow-2xl overflow-y-auto">
        <div class="p-5 space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h4 class="font-black text-slate-900 text-sm">
                    Danh sách trong <span x-text="radius"></span>km (<span x-text="count"></span>)
                </h4>
                <button type="button" @click="showList = false" class="text-slate-400 hover:text-slate-700">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>

            <template x-for="kid in children" :key="kid.id">
                <a :href="kid.url"
                    class="flex gap-3 items-center p-3 rounded-2xl border border-slate-100 hover:border-primary/40 hover:bg-primary/5 transition-colors">
                    <img :src="kid.thumb_url" :alt="kid.name"
                        class="w-12 h-12 rounded-xl object-cover border border-slate-200 flex-shrink-0" />
                    <div class="min-w-0">
                        <div class="font-extrabold text-slate-900 text-xs truncate" x-text="kid.name"></div>
                        <div class="text-[10px] text-slate-400 font-bold"
                            x-text="(kid.age ? kid.age + ' tuổi · ' : '') + (kid.province_name ?? '')"></div>
                    </div>
                </a>
            </template>

            <template x-if="!locating && children.length === 0">
                <p class="text-xs text-slate-400 font-semibold text-center py-10">Không có bé nào trong bán kính này.
                </p>
            </template>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('nearbyWidget', () => ({
            lat: null,
            lng: null,
            radius: 10,
            count: 0,
            children: [],
            locating: true,
            locationError: null,
            usingDefaultLocation: false,
            showList: false,

            init() {
                let settled = false;
                let fallbackTimer = null;

                window.addEventListener('map-user-location-found', (event) => {
                    if (fallbackTimer) {
                        clearTimeout(fallbackTimer);
                    }

                    settled = true;

                    this.lat = event.detail.lat;
                    this.lng = event.detail.lng;
                    this.locating = false;
                    this.usingDefaultLocation = false;
                    this.locationError = null;

                    console.log('[nearbyWidget] Dùng vị trí từ bản đồ:', {
                        lat: this.lat,
                        lng: this.lng,
                    });

                    this.fetchNearby();
                });

                const DEFAULT_LAT = 16.0544;
                const DEFAULT_LNG = 108.2022;

                const useDefaultLocation = (message) => {
                    if (settled) {
                        return;
                    }

                    settled = true;
                    this.lat = DEFAULT_LAT;
                    this.lng = DEFAULT_LNG;
                    this.locating = false;
                    this.usingDefaultLocation = true;
                    this.locationError = message ?? null;
                    this.fetchNearby();
                };

                if (!navigator.geolocation) {
                    useDefaultLocation(
                        'Trình duyệt không hỗ trợ định vị, đang hiển thị theo vị trí mặc định.'
                    );
                    return;
                }

                fallbackTimer = setTimeout(() => {
                    useDefaultLocation(
                        'Không lấy được vị trí của bạn, đang hiển thị theo vị trí mặc định.'
                    );
                }, 12000);

                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        if (settled) {
                            return;
                        }

                        clearTimeout(fallbackTimer);
                        settled = true;

                        this.lat = pos.coords.latitude;
                        this.lng = pos.coords.longitude;
                        this.locating = false;
                        this.usingDefaultLocation = false;
                        this.locationError = null;

                        this.fetchNearby();
                    },
                    () => {
                        useDefaultLocation(
                            'Không thể xác định vị trí của bạn, đang hiển thị theo vị trí mặc định.'
                        );
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                    }
                );
            },

            async fetchNearby() {
                if (this.lat === null || this.lng === null) return;

                try {
                    const res = await fetch(
                        `/api/v1/map-data?radius=${this.radius}&lat=${this.lat}&lng=${this.lng}`
                    );

                    const json = await res.json();

                    this.count = json.count ?? 0;
                    this.children = json.data ?? [];

                    window.dispatchEvent(new CustomEvent('nearby-radius-changed', {
                        detail: {
                            radius: this.radius,
                            lat: this.lat,
                            lng: this.lng,
                        },
                    }));
                } catch (e) {
                    console.error('Lỗi tải dữ liệu nearby-widget:', e);
                }
            },
        }));
    });
</script>