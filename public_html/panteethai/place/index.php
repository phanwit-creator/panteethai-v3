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

// ---- Template data ----
$cat_color = [
    'temple'=>'#C0392B','beach'=>'#2980B9','nature'=>'#27AE60',
    'market'=>'#E67E22','hotel'=>'#8E44AD','restaurant'=>'#F39C12',
    'museum'=>'#16A085','waterfall'=>'#2471A3','island'=>'#1ABC9C',
    'shopping'=>'#E91E63','airport'=>'#607D8B','hospital'=>'#F44336',
    'transport'=>'#795548','other'=>'#9E9E9E',
];
$cat_label = [
    'temple'=>'วัด','beach'=>'ชายหาด','nature'=>'ธรรมชาติ',
    'market'=>'ตลาด','hotel'=>'โรงแรม','restaurant'=>'ร้านอาหาร',
    'museum'=>'พิพิธภัณฑ์','waterfall'=>'น้ำตก','island'=>'เกาะ',
    'shopping'=>'ห้าง','airport'=>'สนามบิน','hospital'=>'โรงพยาบาล',
    'transport'=>'ขนส่ง','other'=>'สถานที่',
];
$cat   = $place['category'];
$color = $cat_color[$cat] ?? '#9E9E9E';
$label = $cat_label[$cat] ?? 'สถานที่';

// ---- head.php variables ----
$seo = [
    'title'    => $seoTitle,
    'desc'     => $seoDesc,
    'url'      => APP_URL . '/place/' . $placeId,
    'keywords' => "เที่ยว{$place['name_th']},{$place['province_name_th']},{$place['name_en']}",
];
$json_ld = [
    jsonld_place([
        'name_th'     => $place['name_th'],
        'description' => $place['description'] ?? '',
        'id'          => $place['id'],
        'lat'         => $place['lat'],
        'lng'         => $place['lng'],
        'address'     => $place['address'] ?? '',
        'phone'       => $place['phone'] ?? '',
        'website'     => $place['website'] ?? '',
    ]),
    jsonld_breadcrumb([
        ['name' => 'หน้าแรก',                          'url' => '/'],
        ['name' => $place['province_name_th'] ?? '',    'url' => '/province/' . $place['province_slug']],
        ['name' => $place['name_th'],                   'url' => '/place/' . $placeId],
    ]),
];
$extra_head = '<style>#place-map{height:350px;width:100%;}</style>';

$footer_inline  = 'const PLACE_LAT='  . json_encode((float)$place['lat'])  . ';'
                . 'const PLACE_LNG='  . json_encode((float)$place['lng'])  . ';'
                . 'const PLACE_NAME=' . json_encode($place['name_th'])     . ';';

$footer_inline .= <<<'ENDJS'
(function(){
    const map = L.map('place-map', {zoomControl: false}).setView([PLACE_LAT, PLACE_LNG], 15);
    L.control.zoom({position: 'topright'}).addTo(map);
    let usingFallback = false;
    const primary = L.tileLayer('https://tiles.openfreemap.org/styles/liberty/{z}/{x}/{y}.png', {
        maxZoom: 20,
        attribution: '© <a href="https://openfreemap.org">OpenFreeMap</a> · © <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>'
    });
    primary.on('tileerror', () => {
        if (!usingFallback) {
            usingFallback = true;
            map.removeLayer(primary);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19, subdomains: ['a', 'b', 'c'],
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>'
            }).addTo(map);
        }
    });
    primary.addTo(map);
    L.marker([PLACE_LAT, PLACE_LNG])
        .addTo(map)
        .bindPopup('<b>' + PLACE_NAME + '</b>')
        .openPopup();
})();
ENDJS;

require_once '../includes/head.php';
?>
<body class="bg-gray-50">

    <!-- Category color bar (4px top strip) -->
    <div class="h-1 w-full" style="background-color:<?= $color ?>"></div>

    <div class="max-w-5xl mx-auto px-4 py-5">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="text-xs text-gray-400 mb-4 flex items-center gap-1.5 flex-wrap">
            <a href="/" class="hover:text-green-600">หน้าแรก</a>
            <span>›</span>
            <?php if ($place['province_slug']): ?>
            <a href="/province/<?= htmlspecialchars($place['province_slug']) ?>"
               class="hover:text-green-600">
                <?= htmlspecialchars($place['province_name_th'] ?? '') ?>
            </a>
            <span>›</span>
            <?php endif; ?>
            <span class="text-gray-600 truncate max-w-[200px]"><?= htmlspecialchars($place['name_th']) ?></span>
        </nav>

        <!-- Place header card -->
        <div class="bg-white rounded-xl shadow-sm p-5 mb-5 border-l-4"
             style="border-left-color:<?= $color ?>">
            <div class="flex items-start gap-4">
                <span class="flex-shrink-0 leading-none mt-0.5" style="font-size:3rem"><?= $categoryIcon ?></span>
                <div class="flex-1 min-w-0">
                    <h1 class="text-2xl font-bold text-gray-900 leading-tight">
                        <?= htmlspecialchars($place['name_th']) ?>
                    </h1>
                    <?php if ($place['name_en']): ?>
                    <p class="text-gray-400 text-lg mt-0.5"><?= htmlspecialchars($place['name_en']) ?></p>
                    <?php endif; ?>
                    <div class="flex flex-wrap gap-2 mt-3">
                        <!-- Category badge -->
                        <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full"
                              style="background-color:<?= $color ?>22;color:<?= $color ?>">
                            <?= $categoryIcon ?> <?= htmlspecialchars($label) ?>
                        </span>
                        <!-- Price badge: only when price_thb > 0 -->
                        <?php if ((int)$place['price_thb'] > 0): ?>
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-orange-50 text-orange-600">
                            ฿<?= number_format((int)$place['price_thb']) ?>
                        </span>
                        <?php endif; ?>
                        <!-- SHA certified badge -->
                        <?php if ($place['sha_certified']): ?>
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-green-50 text-green-700">
                            ✓ SHA
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Two-column: LEFT 55% map+actions | RIGHT 45% info+nearby -->
        <div class="md:flex md:gap-6 md:items-start">

            <!-- LEFT: Map + action buttons -->
            <div class="md:w-[55%] md:flex-shrink-0 mb-5 md:mb-0">

                <!-- Map -->
                <div class="rounded-xl overflow-hidden shadow-sm mb-3">
                    <div id="place-map"></div>
                </div>

                <!-- Action buttons -->
                <div class="flex gap-3">
                    <a href="https://maps.google.com/?q=<?= (float)$place['lat'] ?>,<?= (float)$place['lng'] ?>"
                       target="_blank" rel="noopener noreferrer"
                       class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl
                              text-sm font-medium text-gray-700 bg-white border border-gray-200
                              hover:bg-gray-50 hover:border-gray-300 transition shadow-sm">
                        📍 ดูบน Google Maps
                    </a>
                    <a href="/distance-calculator?to_lat=<?= (float)$place['lat'] ?>&amp;to_lng=<?= (float)$place['lng'] ?>"
                       class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl
                              text-sm font-medium text-white bg-green-500 hover:bg-green-600
                              transition shadow-sm">
                        🗺️ คำนวณเส้นทางมาที่นี่
                    </a>
                </div>

            </div><!-- /LEFT -->

            <!-- RIGHT: Info card + description + ad + nearby -->
            <div class="md:flex-1 min-w-0 space-y-5">

                <!-- Info card (address / phone / website / hours) -->
                <?php $hasInfo = $place['address'] || $place['phone'] || $place['website'] || !empty($hours); ?>
                <?php if ($hasInfo): ?>
                <div class="bg-white rounded-xl shadow-sm p-5">
                    <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-4">ข้อมูลติดต่อ</h2>
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
                        <?php if (!empty($hours)): ?>
                        <div class="flex gap-3">
                            <dt class="text-gray-400 w-20 flex-shrink-0">เวลาทำการ</dt>
                            <dd class="text-gray-700 space-y-0.5">
                                <?php foreach ($hours as $day => $time): ?>
                                <div><?= htmlspecialchars((string)$day) ?>: <?= htmlspecialchars((string)$time) ?></div>
                                <?php endforeach; ?>
                            </dd>
                        </div>
                        <?php endif; ?>
                    </dl>
                </div>
                <?php endif; ?>

                <!-- Description -->
                <?php if ($place['description']): ?>
                <div class="bg-white rounded-xl shadow-sm p-5">
                    <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">เกี่ยวกับสถานที่</h2>
                    <p class="text-gray-600 leading-relaxed text-sm">
                        <?= nl2br(htmlspecialchars($place['description'])) ?>
                    </p>
                </div>
                <?php endif; ?>

                <!-- Ad -->
                <?php if (($ad = adsense_unit('XXXXXXXXXX')) !== ''): ?>
                <div><?= $ad ?></div>
                <?php endif; ?>

                <!-- Nearby places -->
                <?php if (!empty($nearby)): ?>
                <div>
                    <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">
                        สถานที่ใกล้เคียง (รัศมี 5 กม.)
                    </h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <?php foreach (array_slice($nearby, 0, 6) as $np):
                            $nc         = $np['category'];
                            $ncolor     = $cat_color[$nc] ?? '#9E9E9E';
                            $dist_label = (int)$np['dist_m'] >= 1000
                                ? number_format((float)$np['dist_m'] / 1000, 1) . ' กม.'
                                : number_format((int)$np['dist_m']) . ' ม.';
                        ?>
                        <a href="/place/<?= (int)$np['id'] ?>"
                           class="bg-white rounded-xl shadow-sm border-l-4 overflow-hidden block
                                  hover:shadow-md hover:scale-[1.01] transition-all duration-200"
                           style="border-left-color:<?= $ncolor ?>">
                            <div class="p-3">
                                <p class="font-medium text-gray-800 text-sm leading-snug line-clamp-2">
                                    <?= htmlspecialchars($np['name_th']) ?>
                                </p>
                                <div class="flex items-center justify-between mt-2 gap-1">
                                    <span class="text-xs px-1.5 py-0.5 rounded-full truncate"
                                          style="background-color:<?= $ncolor ?>22;color:<?= $ncolor ?>">
                                        <?= htmlspecialchars($cat_label[$nc] ?? $nc) ?>
                                    </span>
                                    <span class="text-xs text-gray-400 flex-shrink-0"><?= $dist_label ?></span>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div><!-- /RIGHT -->

        </div><!-- /two-column -->

    </div><!-- /max-w-5xl -->

<?php require_once '../includes/footer.php';
