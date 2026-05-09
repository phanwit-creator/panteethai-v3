<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/seo.php';

// ดึงจังหวัดเรียงตาม POI count (graceful: แสดงหน้าได้แม้ DB ยังไม่พร้อม)
$provinces = [];
try {
    $provinces = db_query(
        "SELECT pr.slug, pr.name_th, pr.lat, pr.lng, pr.zoom_level,
                COUNT(p.id) AS cnt
         FROM provinces pr
         LEFT JOIN places p ON p.province_slug = pr.slug AND p.status = 'active'
         GROUP BY pr.slug, pr.name_th, pr.lat, pr.lng, pr.zoom_level
         ORDER BY cnt DESC
         LIMIT 20"
    );
} catch (Exception $e) {
    error_log('index.php: ' . $e->getMessage());
}

// --- head.php variables ---
$seo = [
    'title'    => 'PanteeThai — แผนที่ท่องเที่ยวไทย',
    'desc'     => 'แผนที่ท่องเที่ยวไทยครบทุกจังหวัด ค้นหาสถานที่ท่องเที่ยว โรงแรม ร้านอาหาร พร้อมข้อมูลจาก TAT',
    'url'      => APP_URL . '/',
    'keywords' => 'แผนที่ไทย,ท่องเที่ยวไทย,ที่เที่ยวไทย,Thailand travel map',
];

$json_ld = [
    '<script type="application/ld+json">'
    . json_encode([
        '@context' => 'https://schema.org',
        '@type'    => 'WebSite',
        'name'     => 'PanteeThai',
        'url'      => APP_URL,
        'description' => 'แผนที่ท่องเที่ยวไทยครบทุกจังหวัด',
        'inLanguage'  => 'th',
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => [
                '@type'       => 'EntryPoint',
                'urlTemplate' => APP_URL . '/search?q={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ], JSON_UNESCAPED_UNICODE)
    . '</script>',
];

$extra_head = '<style>
    #map { height: calc(100vh - 52px); width: 100%; }
    @media(min-width:768px) { #map { height: calc(100vh - 56px); } }
</style>';

require_once 'includes/head.php';
?>
<body class="bg-gray-50 overflow-hidden">

    <!-- Map (full-screen hero) -->
    <div id="map"></div>

    <!-- Bottom overlay: category filter strip + province panel -->
    <div class="fixed bottom-0 left-0 right-0 z-[1000] pointer-events-none">

        <!-- Category filter bar (sits above province panel) -->
        <div class="px-3 pb-2 pointer-events-auto">
            <div class="flex gap-1.5 overflow-x-auto scrollbar-hide pb-1">
                <?php
                $filters = [
                    ''           => 'ทั้งหมด',
                    'temple'     => '🛕 วัด',
                    'beach'      => '🏖️ ชายหาด',
                    'nature'     => '🌿 ธรรมชาติ',
                    'market'     => '🛒 ตลาด',
                    'hotel'      => '🏨 โรงแรม',
                    'restaurant' => '🍜 ร้านอาหาร',
                    'museum'     => '🏛️ พิพิธภัณฑ์',
                    'island'     => '🏝️ เกาะ',
                    'waterfall'  => '💧 น้ำตก',
                    'shopping'   => '🛍️ ห้าง',
                    'airport'    => '✈️ สนามบิน',
                    'hospital'   => '🏥 โรงพยาบาล',
                    'transport'  => '🚌 ขนส่ง',
                ];
                foreach ($filters as $cat => $label):
                ?>
                <button data-category="<?= htmlspecialchars($cat) ?>"
                        <?php if ($cat === ''): ?>
                        onclick="PanteeMap.filterByCategory('');PanteeMap.map.flyTo([13.0,101.5],6,{animate:true,duration:1});if(PanteeMap.searchMarker){PanteeMap.map.removeLayer(PanteeMap.searchMarker);PanteeMap.searchMarker=null;}"
                        <?php else: ?>
                        onclick="PanteeMap.filterByCategory('<?= htmlspecialchars($cat) ?>')"
                        <?php endif; ?>
                        class="category-btn flex-shrink-0 px-3 py-1.5 rounded-full text-sm font-medium
                               shadow-sm transition whitespace-nowrap
                               <?= $cat === '' ? 'bg-green-500 text-white' : 'bg-white text-gray-600' ?>">
                    <?= $label ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Province panel: white card, rounded top corners -->
        <?php if (!empty($provinces)): ?>
        <div class="bg-white rounded-t-2xl shadow-lg pointer-events-auto">
            <!-- Drag handle -->
            <div class="flex justify-center pt-2.5 pb-1.5">
                <div class="w-10 h-1 bg-gray-300 rounded-full"></div>
            </div>
            <!-- Header -->
            <div class="px-4 pb-2">
                <p class="text-sm font-semibold text-gray-800">จังหวัดและเมืองยอดนิยม</p>
                <p class="text-xs text-gray-400 mt-0.5">เลือกจังหวัดเพื่อดูสถานที่</p>
            </div>
            <!-- Province chips with POI count badges -->
            <div class="flex gap-2 overflow-x-auto scrollbar-hide px-4 pb-5">
                <?php foreach ($provinces as $p): ?>
                <button onclick="jumpToProvince(<?= (float)$p['lat'] ?>,<?= (float)$p['lng'] ?>,<?= (int)($p['zoom_level'] ?? 11) ?>)"
                        class="flex-shrink-0 flex items-center gap-1.5 px-3 py-1.5 bg-gray-50
                               hover:bg-green-50 rounded-full text-sm text-gray-700 hover:text-green-700
                               transition whitespace-nowrap border border-gray-200 hover:border-green-300">
                    <span class="font-medium"><?= htmlspecialchars($p['name_th']) ?></span>
                    <?php if ((int)$p['cnt'] > 0): ?>
                    <span class="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded-full font-medium">
                        <?= (int)$p['cnt'] ?>
                    </span>
                    <?php endif; ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /bottom overlay -->

<?php
// Expose Maptiler key to JS for tile fallback
$footer_inline  = 'const MAPTILER_KEY = ' . json_encode(defined('MAPTILER_KEY') ? MAPTILER_KEY : '') . ';';
$footer_inline .= 'function jumpToProvince(lat,lng,zoom){if(typeof PanteeMap!=="undefined")PanteeMap.flyTo(lat,lng,zoom);}';

$footer_scripts = [
    '/assets/js/map.js',
    '/assets/js/search.js',
];

require_once 'includes/footer.php';
?>
