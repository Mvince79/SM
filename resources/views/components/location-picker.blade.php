{{--
    ╔══════════════════════════════════════════════════════════════════════════╗
    ║  V-01 — LOCATION PICKER HỢP NHẤT (1 nguồn sự thật duy nhất cho toạ độ)   ║
    ╚══════════════════════════════════════════════════════════════════════════╝

    Component dùng chung cho:
      • form báo tin mất tích   (missing.create-missing-form)
      • form báo tin tìm được   (found-children.create-found-form / edit-found-form)
      • Filament HandoverPointResource + MissingChildResource
        (qua App\Filament\Forms\Components\LocationPicker →
         resources/views/filament/forms/components/location-picker.blade.php)

    NGUYÊN TẮC V-01:
      • KHÔNG có input text địa chỉ tự do. Toạ độ CHỈ đến từ bản đồ và được ghi
        vào 2 field ẩn lat/lng (`$latWire` / `$lngWire`).
      • Tắt/từ chối GPS vẫn thao tác được: chạm (click/tap) vào bản đồ hoặc KÉO
        marker đều ghim được điểm. Nút GPS chỉ là đường tắt tiện lợi.
      • Bỏ hoàn toàn định vị theo IP.

    V-02 (tuỳ chọn, bật bằng :geo-lookup="true"): sau mỗi lần ghim sẽ gọi
    window.vnGeoLookupAndSync() → POST /api/geo/lookup (100% nội bộ,
    point-in-polygon trên GeoJSON tĩnh — KHÔNG gọi API bên ngoài) rồi đẩy
    {status, province, ward} về Livewire::applyGeoResult() để render field chỉ đọc.
    Yêu cầu: component cha phải có method applyGeoResult() và nạp
    public/js/map/geo-lookup.js.
--}}
@props([
    // Tên property Livewire nhận toạ độ (Filament dùng 'data.lat' / 'data.lng').
    'latWire' => 'lat',
    'lngWire' => 'lng',
    // Giá trị khởi tạo render từ server (ưu tiên thấp hơn giá trị đang có trên $wire).
    'initLat' => null,
    'initLng' => null,
    'mapId' => 'location-picker',
    'label' => 'Ghim vị trí trên bản đồ',
    'hint' => null,
    'height' => 'h-56',
    // Tâm bản đồ khi chưa ghim điểm nào — mặc định: trung tâm Việt Nam.
    'defaultLat' => 16.0544,
    'defaultLng' => 108.2022,
    'defaultZoom' => 6,
    'geoLookup' => false,
    'autoLocate' => false,
    'sovereignty' => true,
    'hiddenFields' => true,
    'draggable' => true,
    // 'app' = bundle resources/css/app.css (.btn-oauth/.form-label…);
    // 'filament' = panel admin dùng CSS precompiled của Filament (không có class app.css).
    'theme' => 'app',
])

@php
    $isFilament = $theme === 'filament';

    $btnClass = $isFilament
        ? 'mb-3 inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 disabled:opacity-50'
        : 'btn-oauth mb-3 w-full sm:w-auto px-5 font-extrabold text-primary disabled:opacity-50 disabled:cursor-not-allowed';

    $wrapperClass = $isFilament ? 'relative w-full' : 'relative w-full ' . $height . ' rounded-2xl overflow-hidden border border-slate-200';
    $wrapperStyle = $isFilament ? 'height:420px;border-radius:12px;overflow:hidden;' : '';

    $mapClass = $isFilament ? 'w-full h-full' : 'map-container w-full h-full z-0';
    $mapStyle = $isFilament ? 'height:100%;width:100%;z-index:1;' : '';

    $noteClass = $isFilament
        ? 'mt-2 text-sm text-gray-500 dark:text-gray-400'
        : 'mt-2 text-xs font-semibold text-amber-600';
@endphp

<div class="space-y-2">
    @if ($label)
        <label class="form-label flex items-center gap-1.5">
            <x-heroicon-o-map-pin class="w-4 h-4 text-slate-500" />
            {{ $label }}
        </label>
    @endif

    @if ($hint)
        <p class="text-xs text-slate-400 font-medium">{{ $hint }}</p>
    @endif

    <div wire:ignore x-data="{
        map: null,
        marker: null,
        locating: false,
        gpsError: '',
        latWire: @js($latWire),
        lngWire: @js($lngWire),
        initLat: @js($initLat !== null && $initLat !== '' ? (float) $initLat : null),
        initLng: @js($initLng !== null && $initLng !== '' ? (float) $initLng : null),
        defaultLat: @js((float) $defaultLat),
        defaultLng: @js((float) $defaultLng),
        defaultZoom: @js((int) $defaultZoom),
        geoLookup: @js((bool) $geoLookup),
        autoLocate: @js((bool) $autoLocate),
        sovereignty: @js((bool) $sovereignty),
        draggable: @js((bool) $draggable),

        init() {
            this.ensureLeaflet()
                .then(() => this.buildMap())
                .catch(() => {
                    this.gpsError = 'Không tải được thư viện bản đồ. Vui lòng kiểm tra kết nối rồi tải lại trang.';
                });
        },

        /** Leaflet có thể đã được trang nạp sẵn; nếu chưa (vd. trong Filament) thì tự nạp. */
        ensureLeaflet() {
            if (typeof window.L !== 'undefined') {
                return Promise.resolve();
            }

            if (!window.__leafletLoaderPromise) {
                window.__leafletLoaderPromise = new Promise((resolve, reject) => {
                    if (!document.querySelector('link[data-leaflet-css]')) {
                        const css = document.createElement('link');
                        css.rel = 'stylesheet';
                        css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                        css.setAttribute('data-leaflet-css', '');
                        document.head.appendChild(css);
                    }

                    const script = document.createElement('script');
                    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                    script.onload = resolve;
                    script.onerror = reject;
                    document.head.appendChild(script);
                });
            }

            return window.__leafletLoaderPromise;
        },

        /** Điểm đã ghim: ưu tiên state Livewire hiện tại, sau đó tới giá trị server render. */
        savedPoint() {
            const read = (name) => {
                try { return parseFloat(this.$wire.get(name)); } catch (e) { return NaN; }
            };

            let lat = read(this.latWire);
            let lng = read(this.lngWire);

            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                lat = this.initLat;
                lng = this.initLng;
            }

            const valid = Number.isFinite(lat) && Number.isFinite(lng) && (lat !== 0 || lng !== 0);

            return valid ? [lat, lng] : null;
        },

        buildMap() {
            const el = this.$refs.map;

            if (!el || el.dataset.lpReady === '1') {
                return;
            }

            el.dataset.lpReady = '1';

            // Livewire có thể re-mount view → dọn instance Leaflet cũ trên cùng element.
            const key = '_locationPicker_' + (el.id || 'default');
            if (window[key]) {
                try { window[key].remove(); } catch (e) {}
                window[key] = null;
            }

            const saved = this.savedPoint();

            this.map = L.map(el).setView(
                saved ? saved : [this.defaultLat, this.defaultLng],
                saved ? 15 : this.defaultZoom
            );
            window[key] = this.map;

            if (this.sovereignty && typeof window.initVnSovereigntyLayer === 'function') {
                window.initVnSovereigntyLayer(this.map);
            }

            // Bản đồ luôn dùng tile sáng, không đổi theo theme.
            // keepBuffer + updateWhenZooming:false → kéo/zoom tới lui không bị nhá hàng xám.
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap',
                keepBuffer: 2,
                updateWhenZooming: false
            }).addTo(this.map);

            if (saved) {
                this.placeMarker(saved[0], saved[1], 'Vị trí đã chọn', false);
            }

            // CHẠM/CLICK vào bản đồ — hoạt động cả khi GPS bị tắt hoặc bị từ chối.
            this.map.on('click', (e) => {
                this.placeMarker(e.latlng.lat, e.latlng.lng, 'Vị trí đã ghim');
            });

            if (!saved && this.autoLocate) {
                this.locateMe(true);
            }

            setTimeout(() => this.map.invalidateSize(), 150);
        },

        placeMarker(lat, lng, popup, sync = true) {
            if (this.marker) {
                this.marker.setLatLng([lat, lng]);
            } else {
                this.marker = L.marker([lat, lng], { draggable: this.draggable }).addTo(this.map);

                // KÉO marker — cách ghim thứ hai khi không có GPS.
                this.marker.on('dragend', () => {
                    const pos = this.marker.getLatLng();
                    this.marker.bindPopup('Vị trí đã ghim').openPopup();
                    this.pushCoordinates(pos.lat, pos.lng);
                });
            }

            this.marker.bindPopup(popup).openPopup();

            if (sync) {
                this.pushCoordinates(lat, lng);
            }
        },

        /** Ghi toạ độ vào 2 field ẩn + (tuỳ chọn) reverse-geocode nội bộ V-02. */
        pushCoordinates(lat, lng) {
            this.gpsError = '';
            this.$wire.set(this.latWire, Number(lat).toFixed(6));
            this.$wire.set(this.lngWire, Number(lng).toFixed(6));

            if (this.geoLookup && typeof window.vnGeoLookupAndSync === 'function') {
                window.vnGeoLookupAndSync(this.$wire, lat, lng);
            }
        },

        locateMe(silent = false) {
            const fallbackHint = ' Bạn vẫn có thể chạm trực tiếp vào bản đồ hoặc kéo ghim để chọn vị trí.';

            this.gpsError = '';

            if (!navigator.geolocation) {
                if (!silent) {
                    this.gpsError = 'Trình duyệt không hỗ trợ định vị.' + fallbackHint;
                }
                return;
            }

            this.locating = true;

            const done = () => { this.locating = false; };

            const onSuccess = (pos) => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                this.map.setView([lat, lng], 16);
                this.placeMarker(lat, lng, 'Vị trí hiện tại của bạn');
                done();
            };

            const onError = (err) => {
                done();
                if (!silent) {
                    this.gpsError = 'Không lấy được vị trí GPS'
                        + (err && err.message ? ' (' + err.message + ')' : '')
                        + '.' + fallbackHint;
                }
            };

            const opts = { enableHighAccuracy: true, timeout: 10000 };

            try { window.__lastUserGestureAt = Date.now(); } catch (e) {}
            if (typeof window.allowGeolocationForNextCall === 'function') {
                window.allowGeolocationForNextCall();
            }

            if (typeof window.__nativeGeolocationGet === 'function') {
                window.__nativeGeolocationGet(onSuccess, onError, opts);
            } else {
                navigator.geolocation.getCurrentPosition(onSuccess, onError, opts);
            }
        },
    }" x-init="$nextTick(() => init())">

        {{-- U-01 — nút GPS dùng style .btn-oauth --}}
        <button type="button" @click="locateMe()" :disabled="locating" class="{{ $btnClass }}">
            <template x-if="!locating">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                    stroke="currentColor" class="w-4 h-4 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                </svg>
            </template>
            <template x-if="locating">
                <span class="inline-block w-3.5 h-3.5 border-2 border-primary/40 border-t-primary rounded-full animate-spin"></span>
            </template>
            <span x-text="locating ? 'Đang định vị…' : 'Lấy vị trí hiện tại'"></span>
        </button>

        <div class="{{ $wrapperClass }}" @if ($wrapperStyle) style="{{ $wrapperStyle }}" @endif>
            <div id="{{ $mapId }}" x-ref="map" class="{{ $mapClass }}"
                @if ($mapStyle) style="{{ $mapStyle }}" @endif></div>
            @if ($sovereignty)
                {{-- Hook overlay chủ quyền (C-05) — layer thật do initVnSovereigntyLayer() vẽ bằng pane riêng. --}}
                <div id="{{ $mapId }}-sovereignty-overlay" class="map-sovereignty-overlay"></div>
            @endif
        </div>

        {{-- Tắt/từ chối GPS KHÔNG phải lỗi chặn: chỉ nhắc cách ghim tay. --}}
        <p x-show="gpsError" x-cloak x-text="gpsError" class="{{ $noteClass }}"></p>
    </div>

    @if ($hiddenFields)
        {{-- V-01 — toạ độ CHỈ nằm ở 2 field ẩn này, không có input địa chỉ tự do nào. --}}
        <input type="hidden" wire:model="{{ $latWire }}" />
        <input type="hidden" wire:model="{{ $lngWire }}" />
    @endif
</div>
