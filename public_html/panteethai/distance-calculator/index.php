<?php
require_once '../includes/config.php';
require_once '../includes/seo.php';

$seo = [
    'title'    => 'คำนวณระยะทาง — PanteeThai',
    'desc'     => 'คำนวณระยะทางและเวลาเดินทางระหว่างสถานที่ท่องเที่ยวทั่วไทย รองรับรถยนต์ จักรยาน และการเดิน',
    'url'      => APP_URL . '/distance-calculator',
    'keywords' => 'คำนวณระยะทาง,distance calculator,ระยะทางไทย,เดินทางไทย',
];
$json_ld = [
    '<script type="application/ld+json">'
    . json_encode([
        '@context' => 'https://schema.org',
        '@type'    => 'WebApplication',
        'name'     => 'คำนวณระยะทางไทย — PanteeThai',
        'url'      => APP_URL . '/distance-calculator',
        'description' => 'คำนวณระยะทางและเวลาเดินทางระหว่างสถานที่ท่องเที่ยวทั่วไทย รองรับรถยนต์ จักรยาน และการเดิน',
        'applicationCategory' => 'TravelApplication',
        'operatingSystem'     => 'Web',
        'inLanguage'          => 'th',
    ], JSON_UNESCAPED_UNICODE)
    . '</script>',
];
$extra_head = '<style>
    .calc-layout { height: calc(100vh - 64px); }
    #calc-map    { height: 320px; }
    @media (min-width: 1024px) { #calc-map { height: 100%; } }
</style>';

$footer_inline  = 'const MAPTILER_KEY=' . json_encode(defined('MAPTILER_KEY') ? MAPTILER_KEY : '') . ';';
$footer_inline .= <<<'ENDJS'
(function () {

    // ---- Map init ----
    const map = L.map('calc-map', {
        center: [13.0, 101.5],
        zoom: 6,
        zoomControl: false,
    });

    let usingFallback = false;
    const primaryTile = L.tileLayer('https://tiles.openfreemap.org/styles/liberty/{z}/{x}/{y}.png', {
        maxZoom: 20,
        attribution: '© <a href="https://openfreemap.org">OpenFreeMap</a> · © <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>',
    });
    const fallbackTile = MAPTILER_KEY
        ? L.tileLayer(`https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key=${MAPTILER_KEY}`, {
              maxZoom: 20,
              attribution: '© <a href="https://www.maptiler.com">MapTiler</a> · © <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>',
          })
        : L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
              maxZoom: 19, subdomains: ['a', 'b', 'c'],
              attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>',
          });

    primaryTile.on('tileerror', () => {
        if (!usingFallback) { usingFallback = true; map.removeLayer(primaryTile); fallbackTile.addTo(map); }
    });
    primaryTile.addTo(map);
    L.control.zoom({ position: 'topright' }).addTo(map);
    L.control.scale({ metric: true, imperial: false }).addTo(map);

    // ---- State ----
    let fromPoint  = null;   // {lat, lng, name}
    let toPoint    = null;
    let routeLayer = null;
    let fromMarker = null;
    let toMarker   = null;

    // ---- Autocomplete factory ----
    function createAutocomplete(input, dropdown, onSelect) {
        let timer = null;

        input.addEventListener('input', () => {
            clearTimeout(timer);
            const q = input.value.trim();
            if (q.length < 2) { dropdown.innerHTML = ''; dropdown.classList.add('hidden'); return; }
            timer = setTimeout(() => {
                fetch(`/api/search.php?q=${encodeURIComponent(q)}`)
                    .then(r => r.json())
                    .then(data => {
                        dropdown.innerHTML = '';
                        if (!data.success || !data.data.length) {
                            dropdown.innerHTML = '<div class="px-4 py-3 text-sm text-gray-400 text-center">ไม่พบผลลัพธ์</div>';
                            dropdown.classList.remove('hidden');
                            return;
                        }
                        data.data.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'px-4 py-3 hover:bg-green-50 cursor-pointer border-b border-gray-100 last:border-0';
                            const icon = item.type === 'province' ? '🗺️' : '📍';
                            const sub  = [item.name_en, item.province_name].filter(Boolean).join(' · ');
                            div.innerHTML = `<div class="flex items-center gap-2">
                                <span class="text-base flex-shrink-0">${icon}</span>
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-gray-800 truncate">${item.name_th}</div>
                                    ${sub ? `<div class="text-xs text-gray-500 truncate">${sub}</div>` : ''}
                                </div>
                            </div>`;
                            div.addEventListener('click', () => {
                                input.value = item.name_th;
                                dropdown.innerHTML = '';
                                dropdown.classList.add('hidden');
                                onSelect({ lat: item.lat, lng: item.lng, name: item.name_th });
                            });
                            dropdown.appendChild(div);
                        });
                        dropdown.classList.remove('hidden');
                    })
                    .catch(() => { dropdown.innerHTML = ''; dropdown.classList.add('hidden'); });
            }, 300);
        });

        document.addEventListener('click', e => {
            if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // Keyboard navigation
        input.addEventListener('keydown', e => {
            const items = dropdown.querySelectorAll('.cursor-pointer');
            const active = dropdown.querySelector('.bg-green-100');
            let idx = Array.from(items).indexOf(active);
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (active) active.classList.remove('bg-green-100');
                items[(idx + 1) % items.length]?.classList.add('bg-green-100');
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (active) active.classList.remove('bg-green-100');
                items[(idx - 1 + items.length) % items.length]?.classList.add('bg-green-100');
            } else if (e.key === 'Escape') {
                dropdown.classList.add('hidden');
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (active) active.click();
            }
        });
    }

    // ---- Wire up autocompletes ----
    createAutocomplete(
        document.getElementById('from-input'),
        document.getElementById('from-dropdown'),
        point => {
            fromPoint = point;
            if (fromMarker) map.removeLayer(fromMarker);
            fromMarker = L.marker([point.lat, point.lng])
                .addTo(map)
                .bindPopup(`<b>จาก:</b> ${point.name}`);
            map.setView([point.lat, point.lng], 11);
        }
    );

    createAutocomplete(
        document.getElementById('to-input'),
        document.getElementById('to-dropdown'),
        point => {
            toPoint = point;
            if (toMarker) map.removeLayer(toMarker);
            toMarker = L.marker([point.lat, point.lng])
                .addTo(map)
                .bindPopup(`<b>ถึง:</b> ${point.name}`);
        }
    );

    // ---- Swap button ----
    document.getElementById('swap-btn').addEventListener('click', () => {
        const fromInput = document.getElementById('from-input');
        const toInput   = document.getElementById('to-input');
        [fromPoint, toPoint]           = [toPoint,    fromPoint];
        [fromInput.value, toInput.value] = [toInput.value, fromInput.value];
    });

    // ---- Route calculation ----
    const calcBtn    = document.getElementById('calc-btn');
    const resultPanel = document.getElementById('result-panel');
    const errorMsg   = document.getElementById('error-msg');

    function showError(msg) {
        errorMsg.textContent = msg;
        errorMsg.style.display = '';
    }
    function clearError() { errorMsg.style.display = 'none'; }

    calcBtn.addEventListener('click', () => {
        clearError();
        if (!fromPoint || !toPoint) { showError('กรุณาเลือกจุดเริ่มต้นและปลายทางจากรายการ'); return; }
        if (fromPoint.lat === toPoint.lat && fromPoint.lng === toPoint.lng) { showError('จุดเริ่มต้นและปลายทางต้องไม่ใช่ที่เดียวกัน'); return; }

        const profile = document.getElementById('profile-select').value;
        calcBtn.disabled    = true;
        calcBtn.textContent = 'กำลังคำนวณ...';

        const url = `/api/route.php?from_lat=${fromPoint.lat}&from_lng=${fromPoint.lng}`
                  + `&to_lat=${toPoint.lat}&to_lng=${toPoint.lng}&profile=${profile}`;

        fetch(url)
            .then(r => r.json())
            .then(data => {
                calcBtn.disabled    = false;
                calcBtn.textContent = 'คำนวณเส้นทาง';

                if (!data.success) { showError(data.error || 'ไม่พบเส้นทาง'); return; }

                // Draw route
                if (routeLayer) map.removeLayer(routeLayer);
                routeLayer = L.geoJSON(
                    { type: 'Feature', geometry: data.data.geometry, properties: {} },
                    { style: { color: '#16a34a', weight: 5, opacity: 0.85, lineJoin: 'round' } }
                ).addTo(map);
                map.fitBounds(routeLayer.getBounds(), { padding: [30, 30] });

                // Show results
                const d = data.data;
                document.getElementById('result-distance').textContent = d.distance_km + ' กม.';
                document.getElementById('result-time').textContent     = formatDuration(d.duration_min);
                resultPanel.style.display = '';
            })
            .catch(() => {
                calcBtn.disabled    = false;
                calcBtn.textContent = 'คำนวณเส้นทาง';
                showError('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
            });
    });

    function formatDuration(min) {
        if (min < 60) return min + ' นาที';
        const h = Math.floor(min / 60);
        const m = min % 60;
        return h + ' ชั่วโมง' + (m > 0 ? ' ' + m + ' นาที' : '');
    }

})();
ENDJS;

require_once '../includes/head.php';
?>
<body class="bg-gray-50">

    <!-- Navbar -->
    <nav class="bg-white shadow-sm h-16 flex items-center px-4 gap-3 relative z-[900]">
        <a href="/" class="text-xl font-bold text-green-600 flex-shrink-0">PanteeThai</a>
        <span class="text-gray-300 flex-shrink-0">/</span>
        <span class="text-gray-700 text-sm">คำนวณระยะทาง</span>
    </nav>

    <!-- Main layout: sidebar + map -->
    <div class="calc-layout flex flex-col lg:flex-row overflow-hidden">

        <!-- Left sidebar -->
        <div class="w-full lg:w-80 bg-white border-b lg:border-b-0 lg:border-r flex-shrink-0 overflow-y-auto">
            <div class="p-5">

                <h1 class="text-lg font-semibold text-gray-800 mb-5">คำนวณระยะทาง</h1>

                <!-- From -->
                <div class="mb-2">
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">
                        จุดเริ่มต้น
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs leading-none">🟢</span>
                        <input id="from-input" type="text" autocomplete="off"
                               placeholder="ค้นหาจังหวัด / สถานที่..."
                               class="w-full pl-8 pr-3 py-2.5 border border-gray-300 rounded-lg text-sm
                                      focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100">
                        <div id="from-dropdown"
                             class="hidden absolute top-full left-0 right-0 z-[2000] mt-1 bg-white
                                    border border-gray-200 rounded-xl shadow-lg max-h-56 overflow-y-auto"></div>
                    </div>
                </div>

                <!-- Swap -->
                <div class="flex justify-center my-2">
                    <button id="swap-btn"
                            class="w-8 h-8 rounded-full border border-gray-200 bg-white hover:bg-gray-50
                                   transition text-gray-500 text-base flex items-center justify-center shadow-sm"
                            title="สลับจุด">⇅</button>
                </div>

                <!-- To -->
                <div class="mb-5">
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">
                        ปลายทาง
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs leading-none">🔴</span>
                        <input id="to-input" type="text" autocomplete="off"
                               placeholder="ค้นหาจังหวัด / สถานที่..."
                               class="w-full pl-8 pr-3 py-2.5 border border-gray-300 rounded-lg text-sm
                                      focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100">
                        <div id="to-dropdown"
                             class="hidden absolute top-full left-0 right-0 z-[2000] mt-1 bg-white
                                    border border-gray-200 rounded-xl shadow-lg max-h-56 overflow-y-auto"></div>
                    </div>
                </div>

                <!-- Profile -->
                <div class="mb-5">
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">
                        ประเภทการเดินทาง
                    </label>
                    <select id="profile-select"
                            class="w-full py-2.5 px-3 border border-gray-300 rounded-lg text-sm
                                   focus:outline-none focus:border-green-500 bg-white cursor-pointer">
                        <option value="car">🚗 รถยนต์</option>
                        <option value="bike">🚲 จักรยาน</option>
                        <option value="foot">🚶 เดิน</option>
                    </select>
                </div>

                <!-- Error -->
                <p id="error-msg" class="text-red-500 text-sm mb-3" style="display:none"></p>

                <!-- Button -->
                <button id="calc-btn"
                        class="w-full py-2.5 bg-green-500 hover:bg-green-600 active:bg-green-700
                               text-white font-medium rounded-lg text-sm transition
                               disabled:opacity-60 disabled:cursor-not-allowed">
                    คำนวณเส้นทาง
                </button>

                <!-- Result -->
                <div id="result-panel" class="mt-5 p-4 bg-green-50 rounded-xl border border-green-100" style="display:none">
                    <p class="text-xs font-semibold text-green-800 mb-3 uppercase tracking-wide">ผลลัพธ์</p>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div class="bg-white rounded-lg p-3 text-center shadow-sm">
                            <p class="text-xs text-gray-500 mb-1">ระยะทาง</p>
                            <p id="result-distance" class="text-xl font-bold text-green-600"></p>
                        </div>
                        <div class="bg-white rounded-lg p-3 text-center shadow-sm">
                            <p class="text-xs text-gray-500 mb-1">เวลาโดยประมาณ</p>
                            <p id="result-time" class="text-xl font-bold text-green-600"></p>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 text-center">
                        ข้อมูลเส้นทางจาก OSRM · ใช้เพื่ออ้างอิงเท่านั้น
                    </p>
                </div>

                <!-- Ad: below result panel -->
                <?php if (($ad = adsense_unit('4567890123')) !== ''): ?>
                <div class="mt-4"><?= $ad ?></div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Map -->
        <div id="calc-map" class="flex-1 min-h-0"></div>

    </div>

<?php require_once '../includes/footer.php';
