/**
 * V-02 — Reverse-geocode nội bộ cho các form báo tin (mất tích / tìm thấy).
 *
 * lat/lng  →  { status, province, ward }  qua POST /api/geo/lookup
 * (route công khai, throttle 60 req/phút). Toàn bộ xử lý là NỘI BỘ —
 * point-in-polygon trên GeoJSON tĩnh trong storage/app/geo — KHÔNG gọi
 * bất kỳ API bên ngoài nào (không Google/Nominatim/Mapbox…).
 *
 * HÀNH CHÍNH VIỆT NAM TỪ 01/07/2025 (Nghị quyết 202/2025/QH15):
 * chỉ còn 2 cấp  Tỉnh → Xã/Phường.  Cấp Huyện/Quận đã bị BÃI BỎ,
 * nên phản hồi chỉ gồm `province` + `ward`, không có `district`.
 */
(function () {
    'use strict';

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');

        return meta ? meta.getAttribute('content') : '';
    }

    /**
     * Gọi endpoint nội bộ. Luôn resolve (không bao giờ reject) để UI
     * của form không bị treo khi mất mạng — trả {status:'ERROR'} thay thế.
     */
    window.vnGeoLookup = function (lat, lng) {
        return fetch('/api/geo/lookup', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken()
            },
            body: JSON.stringify({ lat: lat, lng: lng })
        })
            .then(function (res) {
                return res.ok ? res.json() : { status: 'ERROR' };
            })
            .catch(function () {
                return { status: 'ERROR' };
            });
    };

    /**
     * Tra cứu rồi đẩy kết quả về Livewire để render field CHỈ ĐỌC
     * (Tỉnh/Thành + Xã/Phường) và bật/tắt nút gửi.
     *
     * Giá trị đẩy về CHỈ dùng để HIỂN THỊ. Server tự giải lại toạ độ bằng
     * GeoBoundaryService trong syncGeoResolution() trước khi validate/lưu,
     * nên client không thể giả mạo dữ liệu hành chính hay vượt qua chặn
     * "ngoài lãnh thổ Việt Nam".
     */
    window.vnGeoLookupAndSync = function (wire, lat, lng) {
        if (!wire) {
            return Promise.resolve(null);
        }

        // Bật trạng thái chờ ngay lập tức để người dùng thấy "Đang xác định…"
        // thay vì 2 ô Tỉnh/Xã trống trong lúc chờ point-in-polygon.
        try {
            wire.call('applyGeoResult', 'PENDING', null, null);
        } catch (e) {
            /* component cha không có applyGeoResult() → bỏ qua, vẫn tra cứu bình thường */
        }

        return window.vnGeoLookup(lat, lng).then(function (res) {
            var status = res && res.status ? res.status : 'ERROR';
            var ok = status === 'OK';

            wire.call(
                'applyGeoResult',
                status,
                ok && res.province ? res.province : null,
                ok && res.ward ? res.ward : null
            );

            return res;
        });
    };
})();
