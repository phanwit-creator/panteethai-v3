// assets/js/route.js — Route Planner
// PanteeThai.com v3

const PanteeRoute = {
    map: null,
    routeLayer: null,
    waypoints: [],

    init(map) {
        this.map = map;
        this.routeLayer = L.layerGroup().addTo(map);
        return this;
    },

    // เพิ่ม waypoint
    addWaypoint(lat, lng, label = '') {
        this.waypoints.push({ lat, lng, label });

        // แสดง marker
        const marker = L.marker([lat, lng], {
            icon: L.divIcon({
                className: '',
                html: `<div style="
                    background:#16A085;color:#fff;
                    border-radius:50%;width:28px;height:28px;
                    display:flex;align-items:center;justify-content:center;
                    font-weight:bold;font-size:13px;border:2px solid #fff;
                    box-shadow:0 2px 4px rgba(0,0,0,0.3)">
                    ${this.waypoints.length}
                </div>`,
                iconSize: [28, 28],
                iconAnchor: [14, 14]
            })
        }).bindPopup(label || `จุดที่ ${this.waypoints.length}`);

        this.routeLayer.addLayer(marker);

        // คำนวณเส้นทางถ้ามี 2 จุดขึ้นไป
        if (this.waypoints.length >= 2) {
            this.calculateRoute();
        }
    },

    // คำนวณเส้นทางผ่าน OSRM
    calculateRoute() {
        const points = this.waypoints
            .map(p => `${p.lng},${p.lat}`)
            .join(';');

        fetch(`/api/route.php?points=${points}&profile=car`)
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                this.drawRoute(data.data);
                this.showRouteInfo(data.data);
            })
            .catch(err => console.error('Route error:', err));
    },

    // วาดเส้นทางบนแผนที่
    drawRoute(routeData) {
        // ลบเส้นเดิม
        this.routeLayer.eachLayer(layer => {
            if (layer instanceof L.Polyline) {
                this.routeLayer.removeLayer(layer);
            }
        });

        if (!routeData.geometry) return;

        // Decode polyline
        const coords = this.decodePolyline(routeData.geometry);

        const line = L.polyline(coords, {
            color: '#16A085',
            weight: 5,
            opacity: 0.8,
            lineJoin: 'round'
        });

        this.routeLayer.addLayer(line);
        this.map.fitBounds(line.getBounds(), { padding: [40, 40] });
    },

    // แสดงข้อมูลเส้นทาง
    showRouteInfo(routeData) {
        const dist = (routeData.distance / 1000).toFixed(1);
        const time = Math.round(routeData.duration / 60);
        const hours = Math.floor(time / 60);
        const mins = time % 60;
        const timeStr = hours > 0 ? `${hours} ชม. ${mins} นาที` : `${mins} นาที`;

        // แสดง info bar
        let infoBar = document.getElementById('route-info');
        if (!infoBar) {
            infoBar = document.createElement('div');
            infoBar.id = 'route-info';
            infoBar.className = 'fixed top-20 left-1/2 -translate-x-1/2 z-[1000] bg-white rounded-xl shadow-lg px-6 py-3 flex gap-6 items-center';
            document.body.appendChild(infoBar);
        }

        infoBar.innerHTML = `
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600">${dist}</div>
                <div class="text-xs text-gray-500">กิโลเมตร</div>
            </div>
            <div class="w-px bg-gray-200 h-10"></div>
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">${timeStr}</div>
                <div class="text-xs text-gray-500">เวลาเดินทาง</div>
            </div>
            <button onclick="PanteeRoute.clear()"
                class="ml-4 text-xs text-red-400 hover:text-red-600">✕ ล้าง</button>
        `;
    },

    // ล้างเส้นทาง
    clear() {
        this.waypoints = [];
        this.routeLayer.clearLayers();
        const infoBar = document.getElementById('route-info');
        if (infoBar) infoBar.remove();
    },

    // Decode OSRM polyline
    decodePolyline(encoded) {
        const coords = [];
        let index = 0, lat = 0, lng = 0;

        while (index < encoded.length) {
            let b, shift = 0, result = 0;
            do {
                b = encoded.charCodeAt(index++) - 63;
                result |= (b & 0x1f) << shift;
                shift += 5;
            } while (b >= 0x20);
            lat += (result & 1) ? ~(result >> 1) : result >> 1;

            shift = 0; result = 0;
            do {
                b = encoded.charCodeAt(index++) - 63;
                result |= (b & 0x1f) << shift;
                shift += 5;
            } while (b >= 0x20);
            lng += (result & 1) ? ~(result >> 1) : result >> 1;

            coords.push([lat / 1e5, lng / 1e5]);
        }
        return coords;
    }
};

document.addEventListener('DOMContentLoaded', () => {
    // Init หลังจาก map พร้อม
    setTimeout(() => {
        if (PanteeMap.map) {
            PanteeRoute.init(PanteeMap.map);
        }
    }, 500);
});