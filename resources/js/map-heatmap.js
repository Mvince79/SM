/**
 * Khởi tạo lớp Heatmap cho bản đồ Leaflet hiện có.
 *
 * @param {object} map Leaflet map instance
 * @param {object} options
 * @returns {{
 *   enable: (provinceId?: number|string|null) => Promise<void>,
 *   disable: () => void,
 *   toggle: (enabled: boolean, provinceId?: number|string|null) => Promise<void>,
 *   reload: (provinceId?: number|string|null) => Promise<void>,
 *   isEnabled: () => boolean
 * }}
 */
export function createCaseHeatmap(map, options = {}) {
    if (!map) {
        throw new Error('Không tìm thấy Leaflet map instance.');
    }

    const endpoint = options.endpoint ?? '/map/heatmap-data';

    let heatLayer = null;
    let enabled = false;
    let requestController = null;

    const buildUrl = (provinceId = null) => {
        const url = new URL(endpoint, window.location.origin);

        if (provinceId !== null && provinceId !== '') {
            url.searchParams.set('province_id', String(provinceId));
        }

        return url.toString();
    };

    const fetchPoints = async (provinceId = null) => {
        if (requestController) {
            requestController.abort();
        }

        requestController = new AbortController();

        const response = await fetch(buildUrl(provinceId), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            signal: requestController.signal,
        });

        if (!response.ok) {
            throw new Error(`Không thể tải dữ liệu Heatmap (${response.status}).`);
        }

        const payload = await response.json();

        return Array.isArray(payload.data) ? payload.data : [];
    };

    const render = (points) => {
        if (heatLayer && map.hasLayer(heatLayer)) {
            map.removeLayer(heatLayer);
        }

        heatLayer = window.L.heatLayer(points, {
            radius: 28,
            blur: 22,
            maxZoom: 17,
            minOpacity: 0.35,
            gradient: {
                0.2: '#22c55e',
                0.45: '#eab308',
                0.7: '#f97316',
                1: '#dc2626',
            },
        });

        if (enabled) {
            heatLayer.addTo(map);
        }
    };

    const reload = async (provinceId = null) => {
        try {
            const points = await fetchPoints(provinceId);
            render(points);
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            console.error('Heatmap load error:', error);
        }
    };

    const enable = async (provinceId = null) => {
        enabled = true;

        if (!heatLayer) {
            await reload(provinceId);
            return;
        }

        if (!map.hasLayer(heatLayer)) {
            heatLayer.addTo(map);
        }
    };

    const disable = () => {
        enabled = false;

        if (heatLayer && map.hasLayer(heatLayer)) {
            map.removeLayer(heatLayer);
        }
    };

    const toggle = async (shouldEnable, provinceId = null) => {
        if (shouldEnable) {
            await enable(provinceId);
        } else {
            disable();
        }
    };

    return {
        enable,
        disable,
        toggle,
        reload,
        isEnabled: () => enabled,
    };
}
