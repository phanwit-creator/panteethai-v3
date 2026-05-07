<?php
// index.php — PanteeThai.com v3 Homepage
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/seo.php';

// ดึง 10 จังหวัดนำร่อง
$provinces = db_query("SELECT slug, name_th, name_en, lat, lng, image_url 
                        FROM provinces 
                        ORDER BY name_en 
                        LIMIT 10");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PanteeThai — แผนที่และท่องเที่ยวไทย</title>
    <meta name="description" content="แผนที่ท่องเที่ยวไทยครบทุกจังหวัด ค้นหาสถานที่ท่องเที่ยว โรงแรม ร้านอาหาร พร้อมข้อมูลจาก TAT">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

    <!-- Marker Cluster CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">

    <style>
        #map { height: calc(100vh - 64px); width: 100%; }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Navbar -->
    <nav class="bg-white shadow-sm h-16 flex items-center px-4 justify-between">
        <a href="/" class="text-xl font-bold text-green-600">🗺️ PanteeThai</a>

        <!-- Search Box -->
        <div class="flex-1 max-w-md mx-4">
            <input type="text"
                   id="search-input"
                   placeholder="ค้นหาสถานที่, จังหวัด..."
                   class="w-full px-4 py-2 border border-gray-300 rounded-full text-sm focus:outline-none focus:border-green-500">
        </div>

        <a href="/blog" class="text-sm text-gray-600 hover:text-green-600">บทความ</a>
    </nav>

    <!-- Map -->
    <div id="map"></div>

    <!-- Province Quick Links -->
    <div class="fixed bottom-4 left-0 right-0 z-[1000] px-4">
        <div class="bg-white rounded-2xl shadow-lg p-3 max-w-2xl mx-auto">
            <p class="text-xs text-gray-500 mb-2">จังหวัดยอดนิยม</p>
            <div class="flex gap-2 overflow-x-auto pb-1">
                <?php foreach ($provinces as $p): ?>
                <a href="/province/<?= htmlspecialchars($p['slug']) ?>"
                   class="flex-shrink-0 px-3 py-1 bg-green-50 text-green-700 rounded-full text-sm hover:bg-green-100">
                    <?= htmlspecialchars($p['name_th']) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

    <!-- Map Init -->
    <script src="assets/js/map.js"></script>
    <script src="assets/js/search.js"></script>

</body>
</html>