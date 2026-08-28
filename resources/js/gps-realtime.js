window.gpsRealtime = (wire) => ({
    watchId: null,
    clueId: null,
    map: null,
    marker: null,
    accuracyCircle: null,

    isTracking: false,
    isSending: false,
    lastSentAt: 0,

    status: 'Chưa chia sẻ vị trí.',
    errorMessage: '',

    init() {
        this.$nextTick(() => {
            this.initializeMap();
        });
    },

    initializeMap() {
        const mapElement = this.$refs.gpsMap;

        if (!mapElement || typeof window.L === 'undefined') {
            return;
        }

        if (mapElement._leaflet_id) {
            return;
        }

        this.map = window.L.map(mapElement, {
            zoomControl: true,
        }).setView([10.0452, 105.7469], 15);

        // keepBuffer + updateWhenZooming:false → kéo/zoom tới lui không bị giật/xám.
        window.L.tileLayer(
            'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19,
                keepBuffer: 2,
                updateWhenZooming: false
            },
        ).addTo(this.map);

        // Đảm bảo tính đúng kích thước khi cửa sổ thay đổi (tránh nửa bản đồ / giật).
        let resizeTimer = null;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => this.map.invalidateSize(), 150);
        });
    },

    startTracking() {
        this.errorMessage = '';

        if (!navigator.geolocation) {
            this.errorMessage = 'Trình duyệt không hỗ trợ định vị GPS.';
            return;
        }

        if (
            typeof window.allowGeolocationForNextCall === 'function' &&
            !window.allowGeolocationForNextCall()
        ) {
            this.errorMessage =
                'Vui lòng bấm lại nút để cho phép truy cập vị trí.';
            this.status = 'Chưa thể truy cập vị trí.';
            return;
        }

        this.status = 'Đang xin quyền truy cập vị trí...';

        navigator.geolocation.getCurrentPosition(
            async (position) => {
                this.isTracking = true;

                await this.handlePosition(position);

                if (typeof window.allowGeolocationForNextCall === 'function') {
                    window.allowGeolocationForNextCall();
                }

                this.watchId = navigator.geolocation.watchPosition(
                    (newPosition) => this.handlePosition(newPosition),
                    (error) => this.handleError(error),
                    {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 3000,
                    },
                );
            },
            (error) => this.handleError(error),
            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0,
            },
        );
    },

    async handlePosition(position) {
        const latitude = Number(position.coords.latitude);
        const longitude = Number(position.coords.longitude);
        const accuracy = Number(position.coords.accuracy || 0);

        this.updateMarker(latitude, longitude, accuracy);

        const now = Date.now();

        if (
            this.isSending ||
            (this.lastSentAt !== 0 && now - this.lastSentAt < 10000)
        ) {
            return;
        }

        this.isSending = true;

        try {
            let result;

            if (this.clueId === null) {
                result = await wire.startTracking(
                    latitude,
                    longitude,
                    accuracy,
                );

                this.clueId = Number(result.clue_id);
            } else {
                result = await wire.updatePosition(
                    this.clueId,
                    latitude,
                    longitude,
                    accuracy,
                );
            }

            this.lastSentAt = now;
            this.status = 'Đang chia sẻ GPS realtime.';
            this.errorMessage = '';
        } catch (error) {
            console.error('Không thể gửi vị trí GPS:', error);

            this.errorMessage =
                'Không thể gửi vị trí lên máy chủ. Vui lòng thử lại.';
        } finally {
            this.isSending = false;
        }
    },

    updateMarker(latitude, longitude, accuracy) {
        if (!this.map) {
            this.initializeMap();
        }

        if (!this.map || typeof window.L === 'undefined') {
            return;
        }

        const position = [latitude, longitude];

        if (!this.marker) {
            const icon = window.L.divIcon({
                className: 'gps-live-marker-wrapper',
                html: '<span class="gps-live-marker"></span>',
                iconSize: [28, 28],
                iconAnchor: [14, 14],
            });

            this.marker = window.L.marker(position, {
                icon,
                zIndexOffset: 1000,
            })
                .addTo(this.map)
                .bindPopup('Vị trí GPS đang được chia sẻ');
        } else {
            this.marker.setLatLng(position);
        }

        if (!this.accuracyCircle) {
            this.accuracyCircle = window.L.circle(position, {
                radius: accuracy,
                weight: 1,
                fillOpacity: 0.08,
            }).addTo(this.map);
        } else {
            this.accuracyCircle.setLatLng(position);
            this.accuracyCircle.setRadius(accuracy);
        }

        this.map.setView(position, Math.max(this.map.getZoom(), 16));
    },

    async stopTracking() {
        if (this.watchId !== null) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }

        this.isTracking = false;
        this.status = 'Đã dừng chia sẻ vị trí.';

        try {
            await wire.stopTracking();
        } catch (error) {
            console.error('Không thể cập nhật trạng thái GPS:', error);
        }
    },

    handleError(error) {
        this.isTracking = false;

        if (this.watchId !== null) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }

        const messages = {
            1: 'Bạn đã từ chối quyền truy cập vị trí.',
            2: 'Thiết bị không thể xác định vị trí hiện tại.',
            3: 'Quá thời gian chờ lấy vị trí GPS.',
        };

        this.errorMessage =
            messages[error.code] || 'Đã xảy ra lỗi khi lấy vị trí GPS.';

        this.status = 'Không thể chia sẻ vị trí.';
    },
});