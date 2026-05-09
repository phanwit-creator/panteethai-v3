// assets/js/map.js — PanteeThai Leaflet Map
// PanteeThai.com v3

const PanteeMap = {
    map: null,
    markers: null,
    searchMarker: null,     // single highlighted marker from search results
    _currentProvince: null, // province slug currently loaded, or null
    _currentCategory: '',   // active category filter, or ''
    _usingFallback: false,

    init() {
        this.map = L.map('map', {
            center: [13.0, 101.5],
            zoom: 6,
            zoomControl: false,
        });

        // Primary tile: OpenFreeMap
        const primaryTile = L.tileLayer('https://tiles.openfreemap.org/styles/liberty/{z}/{x}/{y}.png', {
            maxZoom: 20,
            attribution: '© <a href="https://openfreemap.org" target="_blank">OpenFreeMap</a> · © <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>',
        });

        // Fallback tile: Maptiler (if key available) or OSM
        const maptilerKey = (typeof MAPTILER_KEY !== 'undefined' && MAPTILER_KEY) ? MAPTILER_KEY : null;
        const fallbackTile = maptilerKey
            ? L.tileLayer(`https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key=${maptilerKey}`, {
                maxZoom: 20,
                attribution: '© <a href="https://www.maptiler.com">MapTiler</a> · © <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>',
              })
            : L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                subdomains: ['a', 'b', 'c'],
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>',
              });

        primaryTile.on('tileerror', () => {
            if (!this._usingFallback) {
                this._usingFallback = true;
                this.map.removeLayer(primaryTile);
                fallbackTile.addTo(this.map);
            }
        });

        primaryTile.addTo(this.map);

        L.control.zoom({ position: 'topright' }).addTo(this.map);
        L.control.scale({ metric: true, imperial: false }).addTo(this.map);

        this.markers = L.markerClusterGroup({ chunkedLoading: true });
        this.map.addLayer(this.markers);

        this.loadProvinces();
        this.addLocateButton();

        return this;
    },

    // Category → fill color
    categoryColor: {
        temple:     '#C0392B',
        beach:      '#2980B9',
        nature:     '#27AE60',
        market:     '#E67E22',
        hotel:      '#8E44AD',
        restaurant: '#F39C12',
        museum:     '#16A085',
        waterfall:  '#2471A3',
        island:     '#1ABC9C',
        shopping:   '#E91E8C',
        airport:    '#0288D1',
        hospital:   '#D32F2F',
        transport:  '#F57C00',
        other:      '#7F8C8D',
    },

    // Province overview markers (zoom 6 default)
    loadProvinces() {
        this._currentProvince = null;
        this._currentCategory = '';
        fetch('/api/places.php?type=provinces')
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                this.markers.clearLayers();
                data.data.forEach(p => {
                    L.marker([p.lat, p.lng])
                        .bindPopup(
                            `<b>${p.name_th}</b><br>` +
                            `<small>${p.name_en}</small><br>` +
                            `<a href="/province/${p.slug}">ดูสถานที่ →</a>`
                        )
                        .addTo(this.markers);
                });
            })
            .catch(err => console.error('Load provinces error:', err));
    },

    // Fetch POI from api/places.php and render as circleMarkers
    loadPOI(provinceSlug, category = '') {
        this._currentProvince = provinceSlug || null;
        this._currentCategory = category;

        let url = '/api/places.php?limit=200';
        if (provinceSlug) url += `&province=${encodeURIComponent(provinceSlug)}`;
        if (category)     url += `&category=${encodeURIComponent(category)}`;

        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                this._renderPOI(data.data.features);
            })
            .catch(err => console.error('Load POI error:', err));
    },

    // Render GeoJSON features as circleMarkers into the cluster layer
    _renderPOI(features) {
        this.markers.clearLayers();
        if (!features || !features.length) return;

        features.forEach(f => {
            const [lng, lat] = f.geometry.coordinates;
            const p          = f.properties;
            const color      = this.categoryColor[p.category] || '#7F8C8D';

            const popup =
                `<b>${p.name_th}</b><br>` +
                (p.name_en ? `<small>${p.name_en}</small><br>` : '') +
                `<a href="/place/${p.id}">รายละเอียด →</a>`;

            L.circleMarker([lat, lng], {
                radius:      8,
                fillColor:   color,
                color:       '#fff',
                weight:      2,
                opacity:     1,
                fillOpacity: 0.9,
            })
            .bindPopup(popup)
            .addTo(this.markers);
        });
    },

    // Fetch nearby POI from api/nearby.php and render with distance in popup
    loadNearby(lat, lng, radius = 5000) {
        fetch(`/api/nearby.php?lat=${lat}&lng=${lng}&radius=${radius}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                this.markers.clearLayers();
                data.data.forEach(poi => {
                    const color = this.categoryColor[poi.category] || '#7F8C8D';

                    const popup =
                        `<b>${poi.name_th}</b><br>` +
                        (poi.name_en ? `<small>${poi.name_en}</small><br>` : '') +
                        `<span style="color:#6b7280">${poi.dist_km} km</span><br>` +
                        `<a href="/place/${poi.id}">รายละเอียด →</a>`;

                    L.circleMarker([poi.lat, poi.lng], {
                        radius:      8,
                        fillColor:   color,
                        color:       '#fff',
                        weight:      2,
                        opacity:     1,
                        fillOpacity: 0.9,
                    })
                    .bindPopup(popup)
                    .addTo(this.markers);
                });
            })
            .catch(err => console.error('Nearby error:', err));
    },

    // Category filter: re-fetches current view with new category
    filterByCategory(category) {
        this._currentCategory = category;

        // Update active button styling
        document.querySelectorAll('.category-btn').forEach(btn => {
            const active = btn.dataset.category === category;
            btn.classList.toggle('bg-green-500', active);
            btn.classList.toggle('text-white',   active);
            btn.classList.toggle('shadow-md',    active);
            btn.classList.toggle('bg-white',     !active);
            btn.classList.toggle('text-gray-600',!active);
        });

        if (this._currentProvince) {
            // Refilter within the current province
            this.loadPOI(this._currentProvince, category);
        } else if (category) {
            // No province selected — load all places for this category
            this.loadPOI(null, category);
        } else {
            // No filter — back to province overview
            this.loadProvinces();
        }
    },

    flyTo(lat, lng, zoom = 11) {
        this.map.flyTo([lat, lng], zoom, { duration: 1.5 });
    },

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
        this.map.once('locationfound', e => {
            L.marker(e.latlng)
                .addTo(this.map)
                .bindPopup('คุณอยู่ที่นี่')
                .openPopup();
            this.loadNearby(e.latlng.lat, e.latlng.lng, 5000);
        });
        this.map.once('locationerror', () => {
            alert('ไม่สามารถระบุตำแหน่งได้');
        });
    },
};

document.addEventListener('DOMContentLoaded', () => {
    PanteeMap.init();
});
