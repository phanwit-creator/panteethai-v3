<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/seo.php';

$slug      = trim($_GET['slug'] ?? '');
$not_found = !preg_match('/^[a-z0-9-]+$/', $slug);

$province = null;
if (!$not_found) {
    $province = db_row(
        "SELECT slug, name_th, name_en, region, lat, lng, zoom_level, description, image_url
         FROM provinces
         WHERE slug = ?",
        [$slug]
    );
    $not_found = ($province === null);
}

// ---- 404 ----
if ($not_found) {
    http_response_code(404);
    $seo        = ['title' => 'ไม่พบจังหวัด | PanteeThai', 'url' => APP_URL . '/province/'];
    $json_ld    = [];
    $extra_head = '';
    require_once '../includes/head.php';
    ?>
    <body class="bg-gray-50 min-h-screen flex items-center justify-center">
        <div class="text-center px-4">
            <p class="text-7xl font-bold text-gray-200 mb-4">404</p>
            <h1 class="text-xl font-semibold text-gray-600 mb-2">ไม่พบจังหวัดที่ต้องการ</h1>
            <p class="text-sm text-gray-400 mb-6">ตรวจสอบ URL หรือค้นหาจังหวัดจากหน้าแรก</p>
            <a href="/" class="inline-block px-6 py-2 bg-green-500 text-white rounded-full text-sm
                               font-medium hover:bg-green-600 transition">
                กลับหน้าแรก
            </a>
        </div>
    <?php
    require_once '../includes/footer.php';
    exit;
}

// ---- Data ----
$places = db_query(
    "SELECT id, name_th, name_en, category, address, price_thb
     FROM places
     WHERE province_slug = ? AND status = 'active'
     ORDER BY name_th",
    [$slug]
);

// ---- SEO ----
$seo_title = 'แผนที่' . $province['name_th'] . ' - ที่เที่ยว โรงแรม ร้านอาหาร | PanteeThai';
$desc_raw  = 'ค้นหาสถานที่ท่องเที่ยวใน' . $province['name_th']
           . ' (' . $province['name_en'] . ') ครบทุกประเภท'
           . ' วัด ชายหาด ธรรมชาติ ตลาด โรงแรม ร้านอาหาร มี ' . count($places) . ' สถานที่';
$seo_desc  = mb_strlen($desc_raw) > 160 ? mb_substr($desc_raw, 0, 158) . '…' : $desc_raw;

$seo = [
    'title'    => $seo_title,
    'desc'     => $seo_desc,
    'url'      => APP_URL . '/province/' . $slug,
    'image'    => $province['image_url'] ?: (APP_URL . '/assets/img/og-default.jpg'),
    'keywords' => 'เที่ยว' . $province['name_th'] . ','
                . $province['name_en'] . ' travel,ที่เที่ยว' . $province['name_th']
                . ',แผนที่' . $province['name_th'],
];

$json_ld = [
    jsonld_tourist_destination($province),
    jsonld_breadcrumb([
        ['name' => 'หน้าแรก',            'url' => '/'],
        ['name' => $province['name_th'], 'url' => '/province/' . $slug],
    ]),
];

$extra_head = '<style>#prov-map{height:55vh;min-height:280px;width:100%;}</style>';

// ---- Map JS (executes after Leaflet loads in footer.php) ----
$footer_inline  = 'const PROVINCE_LAT='  . json_encode((float)$province['lat'])                         . ';'
                . 'const PROVINCE_LNG='  . json_encode((float)$province['lng'])                         . ';'
                . 'const PROVINCE_ZOOM=' . (int)($province['zoom_level'] ?? 11)                         . ';'
                . 'const PROVINCE_SLUG=' . json_encode($slug)                                           . ';'
                . 'const MAPTILER_KEY='  . json_encode(defined('MAPTILER_KEY') ? MAPTILER_KEY : '')     . ';';

$footer_inline .= <<<'ENDJS'
(function(){
    const categoryColor={
        temple:'#C0392B',beach:'#2980B9',nature:'#27AE60',
        market:'#E67E22',hotel:'#8E44AD',restaurant:'#F39C12',
        museum:'#16A085',waterfall:'#2471A3',island:'#1ABC9C',other:'#7F8C8D'
    };
    const map=L.map('prov-map',{
        center:[PROVINCE_LAT,PROVINCE_LNG],
        zoom:PROVINCE_ZOOM,
        zoomControl:false
    });
    let usingFallback=false;
    const primaryTile=L.tileLayer('https://tiles.openfreemap.org/styles/liberty/{z}/{x}/{y}.png',{
        maxZoom:20,
        attribution:'© <a href="https://openfreemap.org">OpenFreeMap</a> · © <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>'
    });
    const fallbackTile=MAPTILER_KEY
        ?L.tileLayer(`https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key=${MAPTILER_KEY}`,{
            maxZoom:20,
            attribution:'© <a href="https://www.maptiler.com">MapTiler</a> · © <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>'
          })
        :L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
            maxZoom:19,subdomains:['a','b','c'],
            attribution:'© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>'
          });
    primaryTile.on('tileerror',()=>{
        if(!usingFallback){usingFallback=true;map.removeLayer(primaryTile);fallbackTile.addTo(map);}
    });
    primaryTile.addTo(map);
    L.control.zoom({position:'topright'}).addTo(map);
    L.control.scale({metric:true,imperial:false}).addTo(map);
    const cluster=L.markerClusterGroup({chunkedLoading:true});
    map.addLayer(cluster);
    function renderPOI(features){
        cluster.clearLayers();
        (features||[]).forEach(f=>{
            const[lng,lat]=f.geometry.coordinates;
            const p=f.properties;
            L.circleMarker([lat,lng],{
                radius:8,fillColor:categoryColor[p.category]||'#7F8C8D',
                color:'#fff',weight:2,opacity:1,fillOpacity:0.9
            })
            .bindPopup(`<b>${p.name_th}</b><br>`+(p.name_en?`<small>${p.name_en}</small><br>`:'')+`<a href="/place/${p.id}">รายละเอียด →</a>`)
            .addTo(cluster);
        });
    }
    function loadPOI(category){
        let url=`/api/places.php?province=${encodeURIComponent(PROVINCE_SLUG)}&limit=200`;
        if(category)url+=`&category=${encodeURIComponent(category)}`;
        fetch(url).then(r=>r.json()).then(data=>{
            if(data.success)renderPOI(data.data.features);
        }).catch(err=>console.error('Province POI error:',err));
    }
    loadPOI('');
    document.querySelectorAll('.prov-cat-btn').forEach(btn=>{
        btn.addEventListener('click',()=>{
            const cat=btn.dataset.category;
            document.querySelectorAll('.prov-cat-btn').forEach(b=>{
                const active=b===btn;
                b.classList.toggle('bg-green-500',active);
                b.classList.toggle('text-white',active);
                b.classList.toggle('shadow-md',active);
                b.classList.toggle('bg-white',!active);
                b.classList.toggle('text-gray-600',!active);
            });
            loadPOI(cat);
            document.querySelectorAll('.poi-card').forEach(card=>{
                card.style.display=(!cat||card.dataset.category===cat)?'':'none';
            });
        });
    });
})();
ENDJS;

require_once '../includes/head.php';
?>
<body class="bg-gray-50">

    <!-- Navbar -->
    <nav class="bg-white shadow-sm h-16 flex items-center px-4 gap-3 relative z-[900]">
        <a href="/" class="text-xl font-bold text-green-600 flex-shrink-0">PanteeThai</a>
        <span class="text-gray-300 flex-shrink-0">/</span>
        <span class="text-gray-700 text-sm truncate"><?= htmlspecialchars($province['name_th']) ?></span>
    </nav>

    <!-- Province header -->
    <div class="bg-white border-b">
        <div class="max-w-5xl mx-auto px-4 py-5">
            <nav aria-label="breadcrumb" class="text-xs text-gray-400 mb-2 flex items-center gap-1">
                <a href="/" class="hover:text-green-600">หน้าแรก</a>
                <span aria-hidden="true">›</span>
                <span class="text-gray-600"><?= htmlspecialchars($province['name_th']) ?></span>
            </nav>
            <h1 class="text-2xl font-bold text-gray-800">
                แผนที่<?= htmlspecialchars($province['name_th']) ?>
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                <?= htmlspecialchars($province['name_en']) ?>
                · <?= count($places) ?> สถานที่
                <?php if (!empty($province['region'])): ?>
                · <span class="text-green-600"><?= htmlspecialchars($province['region']) ?></span>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Category filter strip (sticky below navbar) -->
    <div class="bg-white border-b sticky top-0 z-[890] overflow-x-auto">
        <div class="flex gap-1.5 px-3 py-2 min-w-max">
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
            ];
            foreach ($filters as $cat => $label):
            ?>
            <button data-category="<?= htmlspecialchars($cat) ?>"
                    class="prov-cat-btn flex-shrink-0 px-3 py-1.5 rounded-full text-sm font-medium
                           shadow-sm transition whitespace-nowrap
                           <?= $cat === '' ? 'bg-green-500 text-white shadow-md' : 'bg-white text-gray-600' ?>">
                <?= $label ?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Map -->
    <div id="prov-map" class="border-b"></div>

    <!-- POI list -->
    <div class="max-w-5xl mx-auto px-4 py-6">
        <?php if (empty($places)): ?>
        <p class="text-gray-400 text-center py-16">ยังไม่มีสถานที่ในจังหวัดนี้</p>
        <?php else: ?>
        <h2 class="text-lg font-semibold text-gray-700 mb-4">
            สถานที่ท่องเที่ยวใน<?= htmlspecialchars($province['name_th']) ?>
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($places as $place): ?>
            <a href="/place/<?= (int)$place['id'] ?>"
               data-category="<?= htmlspecialchars($place['category']) ?>"
               class="poi-card bg-white rounded-xl shadow-sm p-4 hover:shadow-md transition
                      flex items-start gap-3 group">
                <span class="text-2xl flex-shrink-0 leading-none mt-0.5">
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
                        default      => '📍',
                    } ?>
                </span>
                <div class="min-w-0">
                    <h3 class="font-medium text-gray-800 truncate group-hover:text-green-700 transition">
                        <?= htmlspecialchars($place['name_th']) ?>
                    </h3>
                    <?php if (!empty($place['name_en'])): ?>
                    <p class="text-xs text-gray-400 truncate mt-0.5">
                        <?= htmlspecialchars($place['name_en']) ?>
                    </p>
                    <?php endif; ?>
                    <?php if ((int)$place['price_thb'] > 0): ?>
                    <p class="text-xs text-green-600 mt-1">฿<?= number_format((int)$place['price_thb']) ?></p>
                    <?php else: ?>
                    <p class="text-xs text-gray-400 mt-1">ฟรี</p>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

<?php require_once '../includes/footer.php';
