(function (window) {
    'use strict';

    const sovereigntyData = {
        type: 'FeatureCollection',
        features: [
            {
                type: 'Feature',
                properties: {
                    name: 'Quần đảo Hoàng Sa',
                    fullName: 'Quần đảo Hoàng Sa — TP Đà Nẵng, Việt Nam',
                    type: 'area',
                },
                geometry: {
                    type: 'Polygon',
                    coordinates: [
                        [
                            [110.8, 15.3],
                            [113.3, 15.3],
                            [113.3, 17.8],
                            [110.8, 17.8],
                            [110.8, 15.3],
                        ],
                    ],
                },
            },
            {
                type: 'Feature',
                properties: {
                    name: 'Quần đảo Hoàng Sa',
                    fullName: 'Quần đảo Hoàng Sa — TP Đà Nẵng, Việt Nam',
                    type: 'label',
                },
                geometry: {
                    type: 'Point',
                    coordinates: [112.0, 16.5],
                },
            },
            {
                type: 'Feature',
                properties: {
                    name: 'Quần đảo Trường Sa',
                    fullName: 'Quần đảo Trường Sa — Tỉnh Khánh Hòa, Việt Nam',
                    type: 'area',
                },
                geometry: {
                    type: 'Polygon',
                    coordinates: [
                        [
                            [111.5, 6.5],
                            [116.8, 6.5],
                            [116.8, 12.3],
                            [111.5, 12.3],
                            [111.5, 6.5],
                        ],
                    ],
                },
            },
            {
                type: 'Feature',
                properties: {
                    name: 'Quần đảo Trường Sa',
                    fullName: 'Quần đảo Trường Sa — Tỉnh Khánh Hòa, Việt Nam',
                    type: 'label',
                },
                geometry: {
                    type: 'Point',
                    coordinates: [114.0, 9.5],
                },
            },
        ],
    };

    function addStyles() {
        if (document.getElementById('vn-sovereignty-layer-style')) {
            return;
        }

        const style = document.createElement('style');

        style.id = 'vn-sovereignty-layer-style';
        style.textContent = `
            .vn-sovereignty-label-icon {
                background: transparent;
                border: none;
            }

            .vn-sovereignty-label {
                width: 190px;
                transform: translate(-50%, -50%);
                padding: 4px 6px;
                border: 1px solid rgba(185, 28, 28, 0.85);
                border-radius: 5px;
                background: rgba(255, 255, 255, 0.92);
                color: #991b1b;
                font-size: 11px;
                font-weight: 700;
                line-height: 1.35;
                text-align: center;
                white-space: normal;
                box-shadow: 0 1px 5px rgba(0, 0, 0, 0.25);
                pointer-events: none;
            }

            @media (max-width: 640px) {
                .vn-sovereignty-label {
                    width: 145px;
                    font-size: 9px;
                }
            }
        `;

        document.head.appendChild(style);
    }

    function initVnSovereigntyLayer(map) {
        if (typeof window.L === 'undefined') {
            console.error('[VN Sovereignty] Leaflet chưa được tải.');

            return null;
        }

        if (!map || typeof map.addLayer !== 'function') {
            console.error('[VN Sovereignty] Đối tượng map không hợp lệ.');

            return null;
        }

        addStyles();

        if (!map.getPane('vnSovereigntyPane')) {
            map.createPane('vnSovereigntyPane');

            const pane = map.getPane('vnSovereigntyPane');

            pane.style.zIndex = '450';
            pane.style.pointerEvents = 'none';
        }

        const layer = L.geoJSON(sovereigntyData, {
            pane: 'vnSovereigntyPane',

            style(feature) {
                if (feature.properties.type !== 'area') {
                    return {};
                }

                return {
                    color: '#dc2626',
                    weight: 1.5,
                    opacity: 0.9,
                    fillColor: '#ef4444',
                    fillOpacity: 0.08,
                    dashArray: '6 5',
                    interactive: false,
                };
            },

            pointToLayer(feature, latlng) {
                return L.marker(latlng, {
                    pane: 'vnSovereigntyPane',
                    interactive: false,
                    icon: L.divIcon({
                        className: 'vn-sovereignty-label-icon',
                        html: `
                            <div class="vn-sovereignty-label">
                                ${feature.properties.fullName}
                            </div>
                        `,
                        iconSize: [0, 0],
                        iconAnchor: [0, 0],
                    }),
                });
            },
        });

        layer.addTo(map);

        return layer;
    }

    window.initVnSovereigntyLayer = initVnSovereigntyLayer;
})(window);