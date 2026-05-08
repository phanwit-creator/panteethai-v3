<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/seo.php';

// ดึงจังหวัดนำร่อง (graceful: แสดงหน้าได้แม้ DB ยังไม่พร้อม)
$provinces = [];
try {
    $provinces = db_query(
        "SELECT slug, name_th, name_en, lat, lng
         FROM provinces
         ORDER BY name_en
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

$extra_head = '<style>#map { height: calc(100vh - 64px); width: 100%; }</style>';

require_once 'includes/head.php';
?>
<body class="bg-gray-50 overflow-hidden">

    <!-- Navbar -->
    <nav class="bg-white shadow-sm h-16 flex items-center px-4 gap-4 relative z-[1500]">

        <a href="/" class="text-xl font-bold text-green-600 flex-shrink-0">
            PanteeThai
        </a>

        <!-- Search box -->
        <div class="flex-1 max-w-lg relative">
            <input type="text"
                   id="search-input"
                   autocomplete="off"
                   placeholder="ค้นหาสถานที่, จังหวัด..."
                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-full text-sm
                          focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-200">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-base pointer-events-none">
                🔍
            </span>
            <div id="search-dropdown"></div>
        </div>

        <nav class="hidden sm:flex items-center gap-4 text-sm text-gray-600 flex-shrink-0">
            <a href="/blog" class="hover:text-green-600">บทความ</a>
            <a href="/distance-calculator" class="hover:text-green-600">คำนวณระยะทาง</a>
        </nav>

    </nav>

    <!-- Map container -->
    <div id="map"></div>

    <!-- Category filter bar — floats over map just below navbar -->
    <div class="fixed top-16 left-0 right-0 z-[1400] px-3 pt-2 pointer-events-none">
        <div class="flex gap-1.5 overflow-x-auto pb-2 pointer-events-auto justify-start sm:justify-center">
            <?php
            $filters = [
                ''           => 'ทั้งหมด',
                'temple'     => '🛕 วัด',
                'beach'      => '🏖️ ชายหาด',
                'nature'     => '🌿 ธรรมชาติ',
                'market'     => '🛒 ตลาด',
                'hotel'      => '🏨 โรงแรม',
                'restaurant' => '🍜 ร้านอาหาร',
            ];
            foreach ($filters as $cat => $label):
            ?>
            <button data-category="<?= htmlspecialchars($cat) ?>"
                    onclick="PanteeMap.filterByCategory('<?= htmlspecialchars($cat) ?>')"
                    class="category-btn flex-shrink-0 px-3 py-1.5 rounded-full text-sm font-medium
                           shadow-sm transition whitespace-nowrap
                           <?= $cat === '' ? 'bg-green-500 text-white shadow-md' : 'bg-white text-gray-600' ?>">
                <?= $label ?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Province quick-pick bar (pinned above map) -->
    <?php if (!empty($provinces)): ?>
    <div class="fixed bottom-4 left-0 right-0 z-[1000] px-4 pointer-events-none">
        <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg p-3 max-w-2xl mx-auto pointer-events-auto">
            <p class="text-xs text-gray-400 mb-2 px-1">จังหวัดยอดนิยม</p>
            <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-hide">
                <?php foreach ($provinces as $p): ?>
                <a href="/province/<?= htmlspecialchars($p['slug']) ?>"
                   class="flex-shrink-0 px-3 py-1.5 bg-green-50 text-green-700 rounded-full
                          text-sm hover:bg-green-100 transition whitespace-nowrap">
                    <?= htmlspecialchars($p['name_th']) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

<?php
// Expose Maptiler key to JS for tile fallback
$footer_inline = 'const MAPTILER_KEY = ' . json_encode(defined('MAPTILER_KEY') ? MAPTILER_KEY : '') . ';';

$footer_scripts = [
    '/assets/js/map.js',
    '/assets/js/search.js',
];

require_once 'includes/footer.php';
?>
