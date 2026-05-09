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
$total_count = (int)(db_row(
    "SELECT COUNT(*) AS c FROM places WHERE province_slug = ? AND status = 'active'",
    [$slug]
)['c'] ?? 0);

$places = db_query(
    "SELECT id, name_th, name_en, category, address, price_thb
     FROM places
     WHERE province_slug = ? AND status = 'active'
     ORDER BY name_th
     LIMIT 20",
    [$slug]
);

// ---- SEO ----
$seo_title = 'แผนที่' . $province['name_th'] . ' - ที่เที่ยว โรงแรม ร้านอาหาร | PanteeThai';
$desc_raw  = 'ค้นหาสถานที่ท่องเที่ยวใน' . $province['name_th']
           . ' (' . $province['name_en'] . ') ครบทุกประเภท'
           . ' วัด ชายหาด ธรรมชาติ ตลาด โรงแรม ร้านอาหาร มี ' . $total_count . ' สถานที่';
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

$extra_head = '<style>
    #prov-map{height:40vh;min-height:200px;width:100%;}
    @media(min-width:768px){#prov-map{height:100%;min-height:0;}}
</style>';

// ---- Map JS (executes after Leaflet loads in footer.php) ----
$footer_inline  = 'const PROVINCE_LAT='  . json_encode((float)$province['lat'])                         . ';'
                . 'const PROVINCE_LNG='  . json_encode((float)$province['lng'])                         . ';'
                . 'const PROVINCE_ZOOM=' . (int)($province['zoom_level'] ?? 11)                         . ';'
                . 'const PROVINCE_SLUG=' . json_encode($slug)                                           . ';'
                . 'const MAPTILER_KEY='  . json_encode(defined('MAPTILER_KEY') ? MAPTILER_KEY : '')     . ';'
                . 'const INIT_COUNT='    . count($places)                                               . ';';

$footer_inline .= <<<'ENDJS'
(function(){
    const categoryColor={
        temple:'#C0392B',beach:'#2980B9',nature:'#27AE60',
        market:'#E67E22',hotel:'#8E44AD',restaurant:'#F39C12',
        museum:'#16A085',waterfall:'#2471A3',island:'#1ABC9C',
        shopping:'#E91E8C',airport:'#0288D1',hospital:'#D32F2F',transport:'#F57C00',
        other:'#7F8C8D'
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
    // ── Infinite scroll list ──────────────────────────────────────
    const grid   =document.getElementById('poi-grid');
    const spinner=document.getElementById('poi-spinner');
    const doneMsg=document.getElementById('poi-done');
    const catEmoji={
        temple:'🛕',beach:'🏖️',nature:'🌿',market:'🛒',
        hotel:'🏨',restaurant:'🍜',museum:'🏛️',waterfall:'💧',
        island:'🏝️',shopping:'🛍️',airport:'✈️',hospital:'🏥',
        transport:'🚌'
    };
    function escHtml(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
    function makeCard(f){
        const p=f.properties;
        const emoji=catEmoji[p.category]||'📍';
        const priceHtml=parseInt(p.price_thb)>0
            ?`<p class="text-xs text-green-600 mt-1">฿${parseInt(p.price_thb).toLocaleString()}</p>`
            :`<p class="text-xs text-gray-400 mt-1">ฟรี</p>`;
        const nameEnHtml=p.name_en
            ?`<p class="text-xs text-gray-400 truncate mt-0.5">${escHtml(p.name_en)}</p>`:'';
        const a=document.createElement('a');
        a.href=`/place/${p.id}`;
        a.dataset.category=p.category;
        a.className='poi-card bg-white rounded-xl shadow-sm p-4 hover:shadow-md transition flex items-start gap-3 group';
        a.innerHTML=`<span class="text-2xl flex-shrink-0 leading-none mt-0.5">${emoji}</span>`
            +`<div class="min-w-0"><h3 class="font-medium text-gray-800 truncate group-hover:text-green-700 transition">${escHtml(p.name_th)}</h3>`
            +nameEnHtml+priceHtml+`</div>`;
        return a;
    }
    let listOffset=INIT_COUNT,listCategory='',listLoading=false,listDone=INIT_COUNT<20,loadedCount=INIT_COUNT;
    let observer;
    if(listDone){
        if(spinner)spinner.style.display='none';
        if(INIT_COUNT>0&&doneMsg){doneMsg.textContent=`แสดงทั้งหมดแล้ว ${INIT_COUNT} สถานที่`;doneMsg.style.display='';}
    }
    function fetchList(){
        if(listLoading||listDone||!spinner||!grid)return;
        listLoading=true;
        let url=`/api/places.php?province=${encodeURIComponent(PROVINCE_SLUG)}&limit=20&offset=${listOffset}`;
        if(listCategory)url+=`&category=${encodeURIComponent(listCategory)}`;
        fetch(url).then(r=>r.json()).then(data=>{
            listLoading=false;
            if(!data.success)return;
            const features=data.data.features||[];
            features.forEach(f=>{grid.appendChild(makeCard(f));loadedCount++;});
            listOffset+=features.length;
            if(!data.has_more){
                listDone=true;
                spinner.style.display='none';
                if(doneMsg){doneMsg.textContent=`แสดงทั้งหมดแล้ว ${loadedCount} สถานที่`;doneMsg.style.display='';}
                if(observer)observer.disconnect();
            }
        }).catch(()=>{listLoading=false;});
    }
    observer=new IntersectionObserver(entries=>{
        if(entries[0].isIntersecting)fetchList();
    },{threshold:0.1});
    if(!listDone&&spinner)observer.observe(spinner);
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
            listCategory=cat;listOffset=0;loadedCount=0;listDone=false;listLoading=false;
            if(grid)grid.innerHTML='';
            if(doneMsg)doneMsg.style.display='none';
            if(spinner)spinner.style.display='';
            if(observer){observer.disconnect();observer.observe(spinner);}
            loadPOI(cat);
            fetchList();
        });
    });
})();
ENDJS;

// TAT Events fetch (appended to same inline script block)
$footer_inline .= <<<'EVTJS'
(function(){
    function esc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
    var section=document.getElementById('tat-events');
    var list=document.getElementById('tat-events-list');
    fetch('/api/tat-proxy.php?endpoint=events&province='+encodeURIComponent(PROVINCE_SLUG))
        .then(function(r){return r.json();})
        .then(function(data){
            if(!data.success||!data.data||!data.data.length){return;}
            data.data.forEach(function(ev){
                var name  =esc(ev.nameThai||ev.name||'');
                var nameEn=esc(ev.nameEng||ev.nameEnglish||'');
                var start =esc(ev.startDate||'');
                var end   =esc(ev.endDate||'');
                var desc  =esc(ev.detail||ev.description||'');
                var img   =ev.thumbnailUrl||'';
                var imgHtml=(img&&img.startsWith('http'))
                    ?'<img src="'+esc(img)+'" alt="" class="w-16 h-16 object-cover rounded-lg flex-shrink-0">'
                    :'';
                var dateHtml=start
                    ?'<p class="text-xs text-green-600 mt-1">'+start+(end&&end!==start?' — '+end:'')+'</p>'
                    :'';
                var el=document.createElement('div');
                el.className='bg-white rounded-xl shadow-sm p-4 flex gap-4 items-start';
                el.innerHTML=imgHtml
                    +'<div class="min-w-0">'
                    +'<h3 class="font-medium text-gray-800">'+name+'</h3>'
                    +(nameEn?'<p class="text-xs text-gray-400 mt-0.5">'+nameEn+'</p>':'')
                    +dateHtml
                    +(desc?'<p class="text-sm text-gray-500 mt-1 line-clamp-2">'+desc+'</p>':'')
                    +'</div>';
                if(list)list.appendChild(el);
            });
            if(section)section.style.display='';
        })
        .catch(function(){/* stay hidden on error */});
})();
EVTJS;

require_once '../includes/head.php';
?>
<body class="bg-gray-50 md:overflow-hidden">

    <!-- Split container: flex row on desktop, stack on mobile -->
    <div class="md:flex md:h-[calc(100vh-56px)]">

        <!-- LEFT panel: province header + category filter + map (60%) -->
        <div class="md:w-[60%] md:flex-shrink-0 md:flex md:flex-col md:overflow-hidden">

            <!-- Province header -->
            <div class="bg-white border-b flex-shrink-0">
                <div class="px-4 py-4">
                    <nav aria-label="breadcrumb" class="text-xs text-gray-400 mb-1.5 flex items-center gap-1">
                        <a href="/" class="hover:text-green-600">หน้าแรก</a>
                        <span aria-hidden="true">›</span>
                        <span class="text-gray-600"><?= htmlspecialchars($province['name_th']) ?></span>
                    </nav>
                    <h1 class="text-xl font-bold text-gray-800">
                        แผนที่<?= htmlspecialchars($province['name_th']) ?>
                    </h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        <?= htmlspecialchars($province['name_en']) ?>
                        · <?= $total_count ?> สถานที่
                        <?php if (!empty($province['region'])): ?>
                        · <span class="text-green-600"><?= htmlspecialchars($province['region']) ?></span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <!-- Category filter strip -->
            <?php
            $category_counts = [];
            $rows = db_query(
                "SELECT category, COUNT(*) as cnt FROM places
                 WHERE province_slug = :slug AND status = 'active'
                 GROUP BY category",
                [':slug' => $province['slug']]
            );
            foreach ($rows as $r) {
                $category_counts[$r['category']] = (int)$r['cnt'];
            }
            $total_count_all = array_sum($category_counts);
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
            ?>
            <div class="bg-white border-b flex-shrink-0 overflow-x-auto">
                <div class="flex flex-nowrap md:flex-wrap gap-1.5 px-3 py-2.5 min-w-max md:min-w-0">
                    <?php foreach ($filters as $cat => $label):
                        $count = ($cat === '') ? $total_count_all : ($category_counts[$cat] ?? 0);
                        $active = $cat === '';
                    ?>
                    <button data-category="<?= htmlspecialchars($cat) ?>"
                            class="prov-cat-btn flex-shrink-0 px-3 py-1.5 rounded-full text-sm font-medium
                                   border transition whitespace-nowrap
                                   <?= $active
                                       ? 'bg-green-500 text-white border-green-500'
                                       : 'bg-white text-green-600 border-green-500 hover:bg-green-50' ?>">
                        <?= $label ?> (<?= $count ?>)
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Map — 40vh on mobile, fills remaining flex height on desktop -->
            <div id="prov-map" class="border-b md:flex-1 md:min-h-0"></div>

        </div><!-- /LEFT panel -->

        <!-- RIGHT panel: POI list (40%) — scrollable on desktop -->
        <div class="md:w-[40%] md:overflow-y-auto md:border-l bg-gray-50">

            <!-- Ad: top of right panel -->
            <?php if (($ad = adsense_unit('2345678901')) !== ''): ?>
            <div class="px-4 py-2"><?= $ad ?></div>
            <?php endif; ?>

            <!-- POI list -->
            <div class="px-4 py-6">
                <?php if ($total_count === 0): ?>
                <p class="text-gray-400 text-center py-16">ยังไม่มีสถานที่ในจังหวัดนี้</p>
                <?php else: ?>
                <h2 class="text-lg font-semibold text-gray-700 mb-4">
                    สถานที่ท่องเที่ยวใน<?= htmlspecialchars($province['name_th']) ?>
                </h2>
                <div id="poi-grid" data-offset="20" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-1 xl:grid-cols-2 gap-4">
                    <?php
                    $cat_color = [
                        'temple'=>'#C0392B','beach'=>'#2980B9','nature'=>'#27AE60',
                        'market'=>'#E67E22','hotel'=>'#8E44AD','restaurant'=>'#F39C12',
                        'museum'=>'#16A085','waterfall'=>'#2471A3','island'=>'#1ABC9C',
                        'shopping'=>'#E91E63','airport'=>'#607D8B','hospital'=>'#F44336',
                        'transport'=>'#795548','other'=>'#9E9E9E',
                    ];
                    $cat_emoji = [
                        'temple'=>'🛕','beach'=>'🏖️','nature'=>'🌿','market'=>'🛒',
                        'hotel'=>'🏨','restaurant'=>'🍜','museum'=>'🏛️','waterfall'=>'💧',
                        'island'=>'🏝️','shopping'=>'🛍️','airport'=>'✈️','hospital'=>'🏥',
                        'transport'=>'🚌','other'=>'📍',
                    ];
                    $cat_label = [
                        'temple'=>'วัด','beach'=>'ชายหาด','nature'=>'ธรรมชาติ',
                        'market'=>'ตลาด','hotel'=>'โรงแรม','restaurant'=>'ร้านอาหาร',
                        'museum'=>'พิพิธภัณฑ์','waterfall'=>'น้ำตก','island'=>'เกาะ',
                        'shopping'=>'ห้าง','airport'=>'สนามบิน','hospital'=>'โรงพยาบาล',
                        'transport'=>'ขนส่ง','other'=>'สถานที่',
                    ];
                    foreach ($places as $place):
                        $cat   = $place['category'];
                        $color = $cat_color[$cat] ?? '#9E9E9E';
                        $emoji = $cat_emoji[$cat] ?? '📍';
                        $label = $cat_label[$cat] ?? 'สถานที่';
                    ?>
                    <a href="/place/<?= (int)$place['id'] ?>"
                       data-category="<?= htmlspecialchars($cat) ?>"
                       class="poi-card block bg-white rounded-xl shadow-sm border-l-4
                              hover:shadow-md hover:scale-[1.01] transition-all duration-200 overflow-hidden"
                       style="border-left-color:<?= $color ?>">
                        <div class="p-4">
                            <div class="flex justify-end mb-2">
                                <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full"
                                      style="background-color:<?= $color ?>22;color:<?= $color ?>">
                                    <?= $emoji ?> <?= htmlspecialchars($label) ?>
                                </span>
                            </div>
                            <h3 class="font-semibold text-gray-900 text-sm leading-snug line-clamp-2">
                                <?= htmlspecialchars($place['name_th']) ?>
                            </h3>
                            <?php if (!empty($place['name_en'])): ?>
                            <p class="text-xs text-gray-400 truncate mt-0.5">
                                <?= htmlspecialchars($place['name_en']) ?>
                            </p>
                            <?php endif; ?>
                            <div class="flex items-center mt-2">
                                <?php if ((int)$place['price_thb'] > 0): ?>
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-orange-50 text-orange-600">
                                    ฿<?= number_format((int)$place['price_thb']) ?>
                                </span>
                                <?php else: ?>
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-green-50 text-green-700">
                                    ฟรี
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <!-- Loading spinner (IntersectionObserver target) -->
                <div id="poi-spinner" class="flex justify-center py-6">
                    <div class="w-6 h-6 border-2 border-green-500 border-t-transparent rounded-full animate-spin"></div>
                </div>
                <!-- Done message -->
                <p id="poi-done" class="text-center text-sm text-gray-400 py-4" style="display:none"></p>
                <?php endif; ?>
            </div>

            <!-- Ad: below POI list -->
            <?php if (($ad = adsense_unit('3456789012')) !== ''): ?>
            <div class="px-4 py-2"><?= $ad ?></div>
            <?php endif; ?>

            <!-- TAT Events — hidden until JS confirms there are events to show -->
            <div id="tat-events" class="px-4 pb-8" style="display:none">
                <h2 class="text-lg font-semibold text-gray-700 mb-4">กิจกรรมและเทศกาล</h2>
                <div id="tat-events-list" class="space-y-3"></div>
            </div>

        </div><!-- /RIGHT panel -->

    </div><!-- /Split container -->

<?php require_once '../includes/footer.php';
