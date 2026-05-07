<?php
// province/index.php — Province Page
// PanteeThai.com v3

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/seo.php';
require_once '../includes/tat.php';

// รับ slug จาก URL
$slug = $_GET['slug'] ?? '';
$tab  = $_GET['tab']  ?? 'map';

// Validate slug
if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
    http_response_code(404);
    die('Province not found');
}

// ดึงข้อมูลจังหวัด
$province = db_row(
    "SELECT * FROM provinces WHERE slug = ?",
    [$slug]
);

if (!$province) {
    http_response_code(404);
    die('Province not found');
}

// ดึง POI
$places = db_query(
    "SELECT id, name_th, name_en, category,
            ST_X(location) AS lng,
            ST_Y(location) AS lat,
            address, phone, price_thb
     FROM places
     WHERE province_slug = ? AND status = 'active'
     ORDER BY name_th",
    [$slug]
);

// ดึง TAT Events
$tat    = new TatApi();
$events = $tat->events($slug);

// SEO
$seoTitle = "แผนที่{$province['name_th']} — ที่เที่ยว โรงแรม ร้านอาหาร | PanteeThai";
$seoDesc  = "ค้นหาสถานที่ท่องเที่ยวใน{$province['name_th']} ({$province['name_en']}) "
          . "แผนที่ครบ POI ทุกประเภท ข้อมูลจาก TAT";
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?= seo_meta([
        'title'    => $seoTitle,
        'desc'     => $seoDesc,
        'url'      => APP_URL . '/province/' . $slug,
        'keywords' => "เที่ยว{$province['name_th']},{$province['name_en']} travel,ที่เที่ยว{$province['name_th']}",
    ]) ?>

    <?= jsonld_province($province) ?>
    <?= jsonld_breadcrumb([
        ['name' => 'หน้าแรก',      'url' => '/'],
        ['name' => $province['name_th'], 'url' => '/province/' . $slug],
    ]) ?>

    <?= adsense_script() ?>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">

    <style>
        #map { height: 400px; width: 100%; }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Navbar -->
    <nav class="bg-white shadow-sm h-16 flex items-center px-4 gap-4">
        <a href="/" class="text-xl font-bold text-green-600">🗺️ PanteeThai</a>
        <span class="text-gray-400">/</span>
        <span class="text-gray-700"><?= htmlspecialchars($province['name_th']) ?></span>
    </nav>

    <!-- Header -->
    <div class="bg-white border-b px-4 py-6">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800">
                <?= htmlspecialchars($province['name_th']) ?>
            </h1>
            <p class="text-gray-500 mt-1">
                <?= htmlspecialchars($province['name_en']) ?>
                · <?= count($places) ?> สถานที่
            </p>
        </div>
    </div>

    <!-- Ad: Leaderboard -->
    <div class="max-w-4xl mx-auto px-4 pt-4">
        <?= adsense_unit('XXXXXXXXXX', 'horizontal') ?>
    </div>

    <!-- Map -->
    <div id="map" class="border-b"></div>

    <!-- Tabs -->
    <div class="bg-white border-b sticky top-0 z-10">
        <div class="max-w-4xl mx-auto flex">
            <?php foreach (['map' => 'แผนที่', 'places' => 'สถานที่', 'events' => 'กิจกรรม'] as $t => $label): ?>
            <a href="/province/<?= $slug ?>/<?= $t ?>"
               class="px-6 py-3 text-sm font-medium border-b-2 <?= $tab === $t
                   ? 'border-green-500 text-green-600'
                   : 'border-transparent text-gray-500 hover:text-gray-700' ?>">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Content -->
    <div class="max-w-4xl mx-auto px-4 py-6">

        <?php if ($tab === 'places'): ?>
        <!-- POI List -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($places as $place): ?>
            <a href="/place/<?= $place['id'] ?>"
               class="bg-white rounded-xl shadow-sm p-4 hover:shadow-md transition">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">
                        <?= match($place['category']) {
                            'temple'     => '🛕',
                            'beach'      => '🏖️',
                            'nature'     => '🌿',
                            'market'     => '🛒',
                            'hotel'      => '🏨',
                            'restaurant' => '🍜',
                            'museum'     => '🏛️',
                            'waterfall'  => '💧',
                            'island'     => '🏝️',
                            default      => '📍'
                        } ?>
                    </span>
                    <div>
                        <h3 class="font-medium text-gray-800">
                            <?= htmlspecialchars($place['name_th']) ?>
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">
                            <?= htmlspecialchars($place['address'] ?? '') ?>
                        </p>
                        <?php if ($place['price_thb'] > 0): ?>
                        <p class="text-xs text-green-600 mt-1">
                            ฿<?= number_format($place['price_thb']) ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <?php elseif ($tab === 'events'): ?>
        <!-- Events from TAT -->
        <div class="space-y-4">
            <?php
            $eventList = $events['data'] ?? [];
            if (empty($eventList)):
            ?>
            <p class="text-gray-400 text-center py-8">ไม่มีกิจกรรมในขณะนี้</p>
            <?php else: ?>
                <?php foreach ($eventList as $event): ?>
                <div class="bg-white rounded-xl shadow-sm p-4">
                    <h3 class="font-medium text-gray-800">
                        <?= htmlspecialchars($event['name'] ?? '') ?>
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">
                        <?= htmlspecialchars($event['startDate'] ?? '') ?>
                        — <?= htmlspecialchars($event['endDate'] ?? '') ?>
                    </p>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

    <script>
    // Init map สำหรับหน้าจังหวัด
    const map = L.map('map').setView(
        [<?= $province['lat'] ?>, <?= $province['lng'] ?>],
        <?= $province['zoom_level'] ?? 11 ?>
    );

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>'
    }).addTo(map);

    // Load POI markers
    fetch('/api/places.php?province=<?= $slug ?>')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const markers = L.markerClusterGroup();
            data.data.forEach(poi => {
                L.marker([poi.lat, poi.lng])
                    .bindPopup(`<b>${poi.name_th}</b>`)
                    .addTo(markers);
            });
            map.addLayer(markers);
        });
    </script>

</body>
</html>