@php
    $statePath = $getStatePath();
@endphp

<div
    x-data="{
        map: null,
        marker: null,

        initMap() {
            const mapElement = this.$refs.map;

            if (!mapElement || mapElement.dataset.initialized === 'true') {
                return;
            }

            mapElement.dataset.initialized = 'true';

            const latState = Number(
                this.$wire.get('{{ str_replace('location_picker', 'lat', $statePath) }}')
            );

            const lngState = Number(
                this.$wire.get('{{ str_replace('location_picker', 'lng', $statePath) }}')
            );

            const defaultLat = Number.isFinite(latState) && latState !== 0
                ? latState
                : 10.045162;

            const defaultLng = Number.isFinite(lngState) && lngState !== 0
                ? lngState
                : 105.746857;

            this.map = L.map(mapElement).setView(
                [defaultLat, defaultLng],
                13
            );

            L.tileLayer(
                'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors',
                }
            ).addTo(this.map);

            this.marker = L.marker(
                [defaultLat, defaultLng],
                {
                    draggable: true,
                }
            ).addTo(this.map);

            const updateCoordinates = (lat, lng) => {
                const roundedLat = Number(lat).toFixed(7);
                const roundedLng = Number(lng).toFixed(7);

                this.$wire.set(
                    '{{ str_replace('location_picker', 'lat', $statePath) }}',
                    roundedLat
                );

                this.$wire.set(
                    '{{ str_replace('location_picker', 'lng', $statePath) }}',
                    roundedLng
                );
            };

            this.map.on('click', (event) => {
                const { lat, lng } = event.latlng;

                this.marker.setLatLng([lat, lng]);
                updateCoordinates(lat, lng);
            });

            this.marker.on('dragend', () => {
                const position = this.marker.getLatLng();

                updateCoordinates(position.lat, position.lng);
            });

            setTimeout(() => {
                this.map.invalidateSize();
            }, 300);
        },
    }"
    x-init="
        if (typeof L === 'undefined') {
            const leafletCss = document.createElement('link');
            leafletCss.rel = 'stylesheet';
            leafletCss.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            document.head.appendChild(leafletCss);

            const leafletScript = document.createElement('script');
            leafletScript.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            leafletScript.onload = () => initMap();
            document.head.appendChild(leafletScript);
        } else {
            initMap();
        }
    "
    wire:ignore
>
    <div
        x-ref="map"
        style="height: 420px; width: 100%; border-radius: 12px; z-index: 1;"
    ></div>

    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
        Click vào bản đồ hoặc kéo marker để chọn vị trí điểm bàn giao.
    </p>
</div>