(function (window) {
    'use strict';

    const sovereigntyData = {
        type: 'FeatureCollection',
        features: [
            {
                type: 'Feature',
                properties: {
                    type: 'mask',
                },
                geometry: {
                    type: 'Polygon',
                    coordinates: [
                        [
                            [111.65, 16.88],
                            [112.45, 16.88],
                            [112.45, 17.18],
                            [111.65, 17.18],
                            [111.65, 16.88],
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
                            [111.55, 16.95],
                            [112.70, 16.95],
                            [112.70, 17.45],
                            [111.55, 17.45],
                            [111.55, 16.95],
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
                border: 1px solid rgba(100, 116, 139, 0.45);
                border-radius: 6px;
                background: rgba(255, 255, 255, 0.9);
                color: #334155;
                font-size: 11px;
                font-weight: 600;
                line-height: 1.35;
                text-align: center;
                white-space: normal;
                box-shadow: 0 1px 4px rgba(15, 23, 42, 0.16);
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
                if (feature.properties.type === 'mask') {
                    return {
                        stroke: false,
                        fillColor: '#aad3df',
                        fillOpacity: 1,
                        interactive: false,
                    };
                }

                if (feature.properties.type === 'area') {
                    return {
                        stroke: false,
                        fill: false,
                        interactive: false,
                    };
                }

                return {};
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