(function (window) {
    "use strict";

    const sovereigntyData = {
        type: "FeatureCollection",
        features: [
            // Hoàng Sa (Paracel) — quần đảo trải khoảng 15.6°N–17.2°N,
            // 111.0°E–113.0°E. Vùng mặt nạ trước đây (111.65–112.45E,
            // 16.88–17.18N) quá hẹp, không phủ hết các đảo/rạn ở rìa nên
            // nhãn Trung Quốc trên tile gốc vẫn lộ ra ở biên. Nới rộng ra
            // đủ để che toàn bộ cụm đảo ở mọi mức zoom.
            {
                type: "Feature",
                properties: {
                    name: "Quần đảo Hoàng Sa",
                    type: "mask",
                },
                geometry: {
                    type: "Polygon",
                    coordinates: [
                        [
                            [111.0, 15.6],
                            [113.0, 15.6],
                            [113.0, 17.2],
                            [111.0, 17.2],
                            [111.0, 15.6],
                        ],
                    ],
                },
            },
            {
                type: "Feature",
                properties: {
                    name: "Quần đảo Hoàng Sa",
                    fullName: "Quần đảo Hoàng Sa — TP Đà Nẵng, Việt Nam",
                    type: "label",
                },
                geometry: {
                    type: "Point",
                    coordinates: [112.0, 16.4],
                },
            },
            // Trường Sa (Spratly) — quần đảo trải khoảng 6.0°N–12.5°N,
            // 111.0°E–117.5°E. Trước đây feature này có type: 'area' với
            // toạ độ SAI (trùng vĩ độ ~17°N của Hoàng Sa) và style
            // fill:false/stroke:false → không che được gì, nên chữ Trung
            // Quốc trên tile gốc ở khu vực Trường Sa vẫn hiện ra. Thay
            // bằng 1 mask thật, đúng toạ độ, giống cơ chế của Hoàng Sa.
            {
                type: "Feature",
                properties: {
                    name: "Quần đảo Trường Sa",
                    type: "mask",
                },
                geometry: {
                    type: "Polygon",
                    coordinates: [
                        [
                            [111.0, 6.0],
                            [117.5, 6.0],
                            [117.5, 12.5],
                            [111.0, 12.5],
                            [111.0, 6.0],
                        ],
                    ],
                },
            },
            {
                type: "Feature",
                properties: {
                    name: "Quần đảo Trường Sa",
                    fullName: "Quần đảo Trường Sa — Tỉnh Khánh Hòa, Việt Nam",
                    type: "label",
                },
                geometry: {
                    type: "Point",
                    coordinates: [114.0, 9.5],
                },
            },
        ],
    };

    function addStyles() {
        if (document.getElementById("vn-sovereignty-layer-style")) {
            return;
        }

        const style = document.createElement("style");

        style.id = "vn-sovereignty-layer-style";
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
        if (typeof window.L === "undefined") {
            console.error("[VN Sovereignty] Leaflet chưa được tải.");

            return null;
        }

        if (!map || typeof map.addLayer !== "function") {
            console.error("[VN Sovereignty] Đối tượng map không hợp lệ.");

            return null;
        }

        addStyles();

        if (!map.getPane("vnSovereigntyPane")) {
            map.createPane("vnSovereigntyPane");

            const pane = map.getPane("vnSovereigntyPane");

            pane.style.zIndex = "450";
            pane.style.pointerEvents = "none";
        }

        const layer = L.geoJSON(sovereigntyData, {
            pane: "vnSovereigntyPane",

            style(feature) {
                if (feature.properties.type === "mask") {
                    return {
                        stroke: false,
                        fillColor: "#aad3df",
                        fillOpacity: 1,
                        interactive: false,
                    };
                }

                if (feature.properties.type === "area") {
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
                    pane: "vnSovereigntyPane",
                    interactive: false,
                    icon: L.divIcon({
                        className: "vn-sovereignty-label-icon",
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
