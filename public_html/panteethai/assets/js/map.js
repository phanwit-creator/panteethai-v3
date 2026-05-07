// assets/js/map.js — PanteeThai Leaflet Map
// PanteeThai.com v3

const PanteeMap = {
    map: null,
    markers: null,

    init() {
        // Thailand overview
        this.map = L.map('map', {
            center: [13.0, 101.5],
            zoom: 6,
            zoomControl: false
        });

        // Primary tile: OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            subdomains: ['a', 'b', 'c'],
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>'
        }).addTo(this.map);

        // Controls
        L.control.zoom({ position: 'topright' }).addTo(this.map);
        L.control.scale({ metric: true, imperial: false }).addTo(this.map);

        // Marker cluster
        this.markers = L.markerClusterGroup({ chunkedLoading: true });
        this.map.addLayer(this.markers);

        // Load province markers
        this.loadProvinces();

        // Locate user button
        this.addLocateButton();

        return this;
    },

    // Category colors
    categoryColor: {
        temple: '#C0392B',
        beach: '#2980B9',
        nature: '#27AE60',
        market: '#E67E22',
        hotel: '#8E44AD',
        restaurant: '#F39C12',
        museum: '#16A085',
        waterfall: '#2471A3',
        island: '#1ABC9C',
        other: '#7F8C8D'
    },

    // Load province center markers
    loadProvinces() {
        fetch('/api/places.php?type=provinces')
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                this.markers.clearLayers();
                data.data.forEach(p => {
                    const marker = L.marker([p.lat, p.lng])
                        .bindPopup(`
                            <b>${p.name_th}</b><br>
                            <small>${p.name_en}</small><br>
                            <a href="/province/${p.slug}" 
                               class="text-green-600">ดูสถานที่ →</a>
                        `);
                    this.markers.addLayer(marker);
                });
            })
            .catch(err => console.error('Load provinces error:', err));
    },

    // Load POI markers for a province
    loadPOI(provinceSlug) {
        fetch(`/api/places.php?province=${provinceSlug}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                this.markers.clearLayers();
                data.data.forEach(poi => {
                    const color = this.categoryColor[poi.category] || '#666';
                    const marker = L.circleMarker([poi.lat, poi.lng], {
                        radius: 8,
                        fillColor: color,
                        color: '#fff',
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.9
                    }).bindPopup(`
                        <b>${poi.name_th}</b><br>
                        <small>${poi.name_en || ''}</small><br>
                        <a href="/place/${poi.slug}">รายละเอียด →</a>
                    `);
                    this.markers.addLayer(marker);
                });
            })
            .catch(err => console.error('Load POI error:', err));
    },

    // Fly to province
    flyTo(lat, lng, zoom = 11) {
        this.map.flyTo([lat, lng], zoom, { duration: 1.5 });
    },

    // Locate user
    addLocateButton() {
        const btn = L.control({ position: 'topright' });
        btn.onAdd = () => {
            const div = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
            div.innerHTML = '<a href="#" title="ตำแหน่งของฉัน" style="font-size:18px;line-height:30px;text-align:center;display:block;width:30px;">📍</a>';
            div.onclick = (e) => {
                e.preventDefault();
                this.locateUser();
            };
            return div;
        };
        btn.addTo(this.map);
    },

    locateUser() {
        this.map.locate({ setView: true, maxZoom: 13 });
        this.map.on('locationfound', e => {
            L.marker(e.latlng)
                .addTo(this.map)
                .bindPopup('คุณอยู่ที่นี่')
                .openPopup();

            // Load nearby POI
            fetch(`/api/nearby.php?lat=${e.latlng.lat}&lng=${e.latlng.lng}&radius=5000`)
                .then(r => r.json())
                .then(d => d.success && this.loadPOI(null, d.data));
        });
        this.map.on('locationerror', () => {
            alert('ไม่สามารถระบุตำแหน่งได้');
        });
    }
};

// Init map เมื่อ DOM พร้อม
document.addEventListener('DOMContentLoaded', () => {
    PanteeMap.init();
});