<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/seo.php';

// slug from .htaccess rewrite is the place id (numeric)
$slug = $_GET['slug'] ?? '';

if (!preg_match('/^\d+$/', $slug)) {
    http_response_code(404);
    die('ไม่พบสถานที่นี้');
}

$placeId = (int)$slug;

$place = db_row(
    "SELECT p.*,
            ST_X(p.location) AS lng,
            ST_Y(p.location) AS lat,
            pr.name_th AS province_name_th,
            pr.name_en AS province_name_en,
            pr.slug    AS province_slug_field
     FROM places p
     LEFT JOIN provinces pr ON p.province_slug = pr.slug
     WHERE p.id = ? AND p.status = 'active'",
    [$placeId]
);

if (!$place) {
    http_response_code(404);
    die('ไม่พบสถานที่นี้');
}

$nearby = find_nearby(
    (float)$place['lat'],
    (float)$place['lng'],
    5000
);
// Exclude the current place from nearby list
$nearby = array_filter($nearby, fn($p) => (int)$p['id'] !== $placeId);

$categoryIcon = match($place['category']) {
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
};

$hours = [];
if ($place['hours']) {
    $hours = json_decode($place['hours'], true) ?? [];
}

$seoTitle = htmlspecialchars($place['name_th']) . ' — ที่เที่ยว' . htmlspecialchars($place['province_name_th'] ?? '') . ' | PanteeThai';
$seoDesc  = $place['description']
    ? mb_substr(strip_tags($place['description']), 0, 155)
    : "ข้อมูล {$place['name_th']} สถานที่ท่องเที่ยวใน{$place['province_name_th']}";
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?= seo_meta([
        'title'    => $seoTitle,
        'desc'     => $seoDesc,
        'url'      => APP_URL . '/place/' . $placeId,
        'keywords' => "เที่ยว{$place['name_th']},{$place['province_name_th']},{$place['name_en']}",
    ]) ?>

    <?= jsonld_place([
        'name_th'     => $place['name_th'],
        'description' => $place['description'] ?? '',
        'id'          => $place['id'],
        'lat'         => $place['lat'],
        'lng'         => $place['lng'],
        'address'     => $place['address'] ?? '',
        'phone'       => $place['phone'] ?? '',
        'website'     => $place['website'] ?? '',
    ]) ?>

    <?= jsonld_breadcrumb([
        ['name' => 'หน้าแรก',                          'url' => '/'],
        ['name' => $place['province_name_th'] ?? '',    'url' => '/province/' . $place['province_slug']],
        ['name' => $place['name_th'],                   'url' => '/place/' . $placeId],
    ]) ?>

    <?= adsense_script() ?>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="/assets/css/app.css">

    <style>
        #place-map { height: 300px; width: 100%; }
    </style>
</head>
<body class="bg-gray-50">

    <nav class="bg-white shadow-sm h-16 flex items-center px-4 gap-2 text-sm">
        <a href="/" class="text-xl font-bold text-green-600">PanteeThai</a>
        <span class="text-gray-300">/</span>
        <a href="/province/<?= htmlspecialchars($place['province_slug']) ?>"
           class="text-gray-500 hover:text-green-600">
            <?= htmlspecialchars($place['province_name_th'] ?? '') ?>
        </a>
        <span class="text-gray-300">/</span>
        <span class="text-gray-700 truncate max-w-xs"><?= htmlspecialchars($place['name_th']) ?></span>
    </nav>

    <!-- Hero -->
    <div class="bg-white border-b">
        <div class="max-w-3xl mx-auto px-4 py-6">
            <div class="flex items-start gap-4">
                <span class="text-5xl"><?= $categoryIcon ?></span>
                <div class="flex-1 min-w-0">
                    <h1 class="text-2xl font-bold text-gray-800 leading-tight">
                        <?= htmlspecialchars($place['name_th']) ?>
                    </h1>
                    <?php if ($place['name_en']): ?>
                    <p class="text-gray-400 text-sm mt-0.5"><?= htmlspecialchars($place['name_en']) ?></p>
                    <?php endif; ?>
                    <div class="flex flex-wrap gap-2 mt-3">
                        <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs">
                            <?= htmlspecialchars($place['category']) ?>
                        </span>
                        <?php if ($place['sha_certified']): ?>
                        <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs">SHA Certified</span>
                        <?php endif; ?>
                        <?php if ($place['price_thb'] > 0): ?>
                        <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded-full text-xs">
                            ฿<?= number_format($place['price_thb']) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ad -->
    <div class="max-w-3xl mx-auto px-4 pt-4">
        <?= adsense_unit('XXXXXXXXXX', 'horizontal') ?>
    </div>

    <!-- Map -->
    <div id="place-map" class="border-b mt-4"></div>

    <!-- Details -->
    <div class="max-w-3xl mx-auto px-4 py-6 space-y-6">

        <!-- Description -->
        <?php if ($place['description']): ?>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h2 class="font-semibold text-gray-700 mb-3">เกี่ยวกับสถานที่</h2>
            <p class="text-gray-600 leading-relaxed text-sm">
                <?= nl2br(htmlspecialchars($place['description'])) ?>
            </p>
        </div>
        <?php endif; ?>

        <!-- Info Grid -->
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h2 class="font-semibold text-gray-700 mb-4">ข้อมูลติดต่อ</h2>
            <dl class="space-y-3 text-sm">
                <?php if ($place['address']): ?>
                <div class="flex gap-3">
                    <dt class="text-gray-400 w-20 flex-shrink-0">ที่อยู่</dt>
                    <dd class="text-gray-700"><?= htmlspecialchars($place['address']) ?></dd>
                </div>
                <?php endif; ?>
                <?php if ($place['phone']): ?>
                <div class="flex gap-3">
                    <dt class="text-gray-400 w-20 flex-shrink-0">โทรศัพท์</dt>
                    <dd>
                        <a href="tel:<?= htmlspecialchars($place['phone']) ?>"
                           class="text-green-600 hover:underline">
                            <?= htmlspecialchars($place['phone']) ?>
                        </a>
                    </dd>
                </div>
                <?php endif; ?>
                <?php if ($place['website']): ?>
                <div class="flex gap-3">
                    <dt class="text-gray-400 w-20 flex-shrink-0">เว็บไซต์</dt>
                    <dd>
                        <a href="<?= htmlspecialchars($place['website']) ?>"
                           target="_blank" rel="noopener noreferrer"
                           class="text-blue-600 hover:underline break-all">
                            <?= htmlspecialchars($place['website']) ?>
                        </a>
                    </dd>
                </div>
                <?php endif; ?>
            </dl>
        </div>

        <!-- Opening Hours -->
        <?php if (!empty($hours)): ?>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h2 class="font-semibold text-gray-700 mb-4">เวลาเปิด-ปิด</h2>
            <dl class="space-y-2 text-sm">
                <?php foreach ($hours as $day => $time): ?>
                <div class="flex gap-3">
                    <dt class="text-gray-400 w-24 flex-shrink-0"><?= htmlspecialchars((string)$day) ?></dt>
                    <dd class="text-gray-700"><?= htmlspecialchars((string)$time) ?></dd>
                </div>
                <?php endforeach; ?>
            </dl>
        </div>
        <?php endif; ?>

        <!-- Nearby Places -->
        <?php if (!empty($nearby)): ?>
        <div>
            <h2 class="font-semibold text-gray-700 mb-3">สถานที่ใกล้เคียง (รัศมี 5km)</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <?php foreach (array_slice($nearby, 0, 6) as $np): ?>
                <a href="/place/<?= (int)$np['id'] ?>"
                   class="bg-white rounded-xl shadow-sm p-4 hover:shadow-md transition flex items-center gap-3">
                    <span class="text-xl">
                        <?= match($np['category']) {
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
                    <div class="min-w-0">
                        <p class="font-medium text-gray-800 text-sm truncate">
                            <?= htmlspecialchars($np['name_th']) ?>
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            <?= number_format($np['dist_m']) ?> ม.
                        </p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Ad -->
        <?= adsense_unit('XXXXXXXXXX') ?>

    </div>

    <footer class="border-t bg-white mt-8 py-6 text-center text-xs text-gray-400">
        © <?= date('Y') ?> PanteeThai.com · ข้อมูลแผนที่จาก
        <a href="https://www.openstreetmap.org/copyright" class="hover:underline">
            OpenStreetMap contributors
        </a>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    const map = L.map('place-map').setView(
        [<?= (float)$place['lat'] ?>, <?= (float)$place['lng'] ?>], 15
    );

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>'
    }).addTo(map);

    L.marker([<?= (float)$place['lat'] ?>, <?= (float)$place['lng'] ?>])
        .addTo(map)
        .bindPopup('<b><?= htmlspecialchars(addslashes($place['name_th'])) ?></b>')
        .openPopup();
    </script>

</body>
</html>
