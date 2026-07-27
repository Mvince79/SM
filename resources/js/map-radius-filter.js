/**
 * PB-32 — Bộ lọc bán kính bản đồ.
 *
 * Cho phép vẽ một vòng tròn bằng Leaflet Draw và chỉ hiển thị
 * những marker nằm trong bán kính đó.
 *
 * @param {object} map Leaflet map instance
 * @param {object} markersLayer LayerGroup đang hiển thị marker
 * @returns {{
 *   registerMarker: (marker: object) => void,
 *   clearMarkers: () => void,
 *   applyFilter: () => void,
 *   reset: () => void
 * }}
 */
export function createMapRadiusFilter(map, markersLayer) {
    if (!map || !markersLayer) {
        throw new Error('Thiếu map hoặc markersLayer cho bộ lọc bán kính.');
    }

    const L = window.L;

    if (!L?.Control?.Draw) {
        console.error(
            'Leaflet Draw chưa được tải. Hãy kiểm tra leaflet.draw.js.'
        );

        return {
            registerMarker: () => { },
            clearMarkers: () => { },
            applyFilter: () => { },
            reset: () => { },
        };
    }

    const drawnItems = new L.FeatureGroup();
    map.addLayer(drawnItems);

    let radiusCircle = null;
    let markers = [];

    /**
     * Tính khoảng cách giữa hai tọa độ theo công thức Haversine.
     *
     * @returns {number} Khoảng cách tính bằng mét.
     */
    const haversineDistance = (lat1, lng1, lat2, lng2) => {
        const earthRadius = 6371000;
        const toRadians = (degrees) => degrees * Math.PI / 180;

        const latitudeDelta = toRadians(lat2 - lat1);
        const longitudeDelta = toRadians(lng2 - lng1);

        const a =
            Math.sin(latitudeDelta / 2) ** 2 +
            Math.cos(toRadians(lat1)) *
            Math.cos(toRadians(lat2)) *
            Math.sin(longitudeDelta / 2) ** 2;

        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

        return earthRadius * c;
    };

    const isMarkerInsideCircle = (marker) => {
        if (!radiusCircle) {
            return true;
        }

        const center = radiusCircle.getLatLng();
        const markerPosition = marker.getLatLng();
        const radius = radiusCircle.getRadius();

        const distance = haversineDistance(
            center.lat,
            center.lng,
            markerPosition.lat,
            markerPosition.lng
        );

        return distance <= radius;
    };

    const applyFilter = () => {
        markersLayer.clearLayers();

        markers.forEach((marker) => {
            if (isMarkerInsideCircle(marker)) {
                markersLayer.addLayer(marker);
            }
        });
    };

    const registerMarker = (marker) => {
        if (!marker) {
            return;
        }

        markers.push(marker);

        if (isMarkerInsideCircle(marker)) {
            markersLayer.addLayer(marker);
        }
    };

    const clearMarkers = () => {
        markers = [];
        markersLayer.clearLayers();
    };

    const reset = () => {
    if (radiusCircle && map.hasLayer(radiusCircle)) {
        map.removeLayer(radiusCircle);
    }

    drawnItems.clearLayers();
    radiusCircle = null;

    applyFilter();
};
    const drawControl = new L.Control.Draw({
        position: 'bottomright',
        draw: {
            circle: {
                shapeOptions: {
                    color: '#2563eb',
                    weight: 2,
                    fillColor: '#3b82f6',
                    fillOpacity: 0.12,
                },

                showRadius: true,
                metric: true,
                repeatMode: false,
            },

            marker: false,
            circlemarker: false,
            polygon: false,
            polyline: false,
            rectangle: false,
        },

        edit: {
            featureGroup: drawnItems,
            edit: true,
            remove: true,
        },
    });

    map.addControl(drawControl);

    map.on(L.Draw.Event.CREATED, (event) => {
        drawnItems.clearLayers();

        radiusCircle = event.layer;
        drawnItems.addLayer(radiusCircle);

        applyFilter();
    });

    map.on(L.Draw.Event.EDITED, (event) => {
        event.layers.eachLayer((layer) => {
            radiusCircle = layer;
        });

        applyFilter();
    });

    map.on(L.Draw.Event.DELETED, (event) => {
    event.layers.eachLayer((layer) => {
        if (map.hasLayer(layer)) {
            map.removeLayer(layer);
        }
    });

    drawnItems.clearLayers();
    radiusCircle = null;

    applyFilter();
});

    return {
        registerMarker,
        clearMarkers,
        applyFilter,
        reset,
    };
}