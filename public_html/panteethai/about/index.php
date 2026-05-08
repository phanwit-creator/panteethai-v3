<?php
require_once '../includes/config.php';
require_once '../includes/seo.php';

$seo = [
    'title'    => 'เกี่ยวกับเรา — PanteeThai แผนที่ท่องเที่ยวไทย',
    'desc'     => 'PanteeThai.com คือแพลตฟอร์มแผนที่ท่องเที่ยวไทยครบทุกจังหวัด ค้นหาสถานที่ท่องเที่ยว โรงแรม และร้านอาหารทั่วประเทศไทย',
    'url'      => APP_URL . '/about',
    'keywords' => 'เกี่ยวกับ PanteeThai,แผนที่ท่องเที่ยวไทย,Thailand travel map',
];
$json_ld = [
    '<script type="application/ld+json">'
    . json_encode([
        '@context'    => 'https://schema.org',
        '@type'       => 'AboutPage',
        'name'        => 'เกี่ยวกับ PanteeThai',
        'url'         => APP_URL . '/about',
        'description' => 'PanteeThai.com คือแพลตฟอร์มแผนที่ท่องเที่ยวไทย',
        'publisher'   => [
            '@type' => 'Organization',
            'name'  => 'PanteeThai',
            'url'   => APP_URL,
        ],
    ], JSON_UNESCAPED_UNICODE)
    . '</script>',
];
$extra_head = '';

require_once '../includes/head.php';
?>
<body class="bg-gray-50">

    <!-- Navbar -->
    <nav class="bg-white shadow-sm h-16 flex items-center px-4 gap-3 relative z-[900]">
        <a href="/" class="text-xl font-bold text-green-600 flex-shrink-0">PanteeThai</a>
        <span class="text-gray-300 flex-shrink-0">/</span>
        <span class="text-gray-700 text-sm">เกี่ยวกับเรา</span>
    </nav>

    <main class="max-w-3xl mx-auto px-4 py-10">

        <!-- Hero -->
        <div class="text-center mb-12">
            <h1 class="text-3xl font-bold text-gray-800 mb-3">
                🗺️ PanteeThai.com
            </h1>
            <p class="text-lg text-gray-500">แผนที่ท่องเที่ยวไทยครบทุกจังหวัด</p>
        </div>

        <!-- Mission -->
        <section class="bg-white rounded-2xl shadow-sm p-6 mb-6 border border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800 mb-3">พันธกิจของเรา</h2>
            <p class="text-gray-600 leading-relaxed mb-3">
                PanteeThai.com ก่อตั้งขึ้นในปี 2569 โดยมีเป้าหมายเดียว คือ
                ช่วยให้คนไทยและนักท่องเที่ยวทั่วโลกค้นหาสถานที่ท่องเที่ยว โรงแรม
                ร้านอาหาร และสถานที่น่าสนใจทั่วประเทศไทยได้ง่าย รวดเร็ว และถูกต้อง
            </p>
            <p class="text-gray-600 leading-relaxed">
                เราเชื่อว่าข้อมูลการท่องเที่ยวที่ดีควรเข้าถึงได้ฟรี
                ไม่ว่าจะเดินทางด้วยตัวเองหรือวางแผนล่วงหน้า
                PanteeThai พร้อมเป็นเพื่อนร่วมทางสำหรับทุกการเดินทาง
            </p>
        </section>

        <!-- What we offer -->
        <section class="bg-white rounded-2xl shadow-sm p-6 mb-6 border border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">สิ่งที่เรานำเสนอ</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="flex gap-3 items-start">
                    <span class="text-2xl flex-shrink-0">🗺️</span>
                    <div>
                        <h3 class="font-medium text-gray-800">แผนที่ครบทุกจังหวัด</h3>
                        <p class="text-sm text-gray-500 mt-0.5">ข้อมูลสถานที่ท่องเที่ยวทั้ง 77 จังหวัดทั่วไทย</p>
                    </div>
                </div>
                <div class="flex gap-3 items-start">
                    <span class="text-2xl flex-shrink-0">🔍</span>
                    <div>
                        <h3 class="font-medium text-gray-800">ค้นหาอัจฉริยะ</h3>
                        <p class="text-sm text-gray-500 mt-0.5">ค้นหาสถานที่ด้วยชื่อภาษาไทยหรืออังกฤษ</p>
                    </div>
                </div>
                <div class="flex gap-3 items-start">
                    <span class="text-2xl flex-shrink-0">📍</span>
                    <div>
                        <h3 class="font-medium text-gray-800">POI หลากหลายประเภท</h3>
                        <p class="text-sm text-gray-500 mt-0.5">วัด ชายหาด ธรรมชาติ ตลาด โรงแรม ร้านอาหาร และอื่น ๆ</p>
                    </div>
                </div>
                <div class="flex gap-3 items-start">
                    <span class="text-2xl flex-shrink-0">📏</span>
                    <div>
                        <h3 class="font-medium text-gray-800">คำนวณระยะทาง</h3>
                        <p class="text-sm text-gray-500 mt-0.5">วางแผนเส้นทางระหว่างสถานที่ด้วยรถยนต์ จักรยาน หรือเดินเท้า</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Data sources -->
        <section class="bg-white rounded-2xl shadow-sm p-6 mb-6 border border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800 mb-3">แหล่งข้อมูล</h2>
            <p class="text-gray-600 leading-relaxed mb-3">
                ข้อมูลบนเว็บไซต์รวบรวมจากหลายแหล่งที่เชื่อถือได้:
            </p>
            <ul class="space-y-2 text-gray-600">
                <li class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-green-400 flex-shrink-0"></span>
                    <span>
                        <strong>การท่องเที่ยวแห่งประเทศไทย (TAT)</strong> — ข้อมูลสถานที่และกิจกรรมท่องเที่ยวอย่างเป็นทางการ
                    </span>
                </li>
                <li class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-blue-400 flex-shrink-0"></span>
                    <span>
                        <strong>OpenStreetMap</strong> — ข้อมูลแผนที่จากชุมชนนักทำแผนที่อาสาสมัครทั่วโลก
                    </span>
                </li>
                <li class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-orange-400 flex-shrink-0"></span>
                    <span>
                        <strong>ทีมงาน PanteeThai</strong> — ข้อมูลที่รวบรวมและตรวจสอบโดยทีมงานโดยตรง
                    </span>
                </li>
            </ul>
        </section>

        <!-- Tech -->
        <section class="bg-white rounded-2xl shadow-sm p-6 mb-6 border border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800 mb-3">เทคโนโลยีที่ใช้</h2>
            <div class="flex flex-wrap gap-2">
                <?php foreach (['Leaflet.js', 'OpenFreeMap', 'OpenStreetMap', 'OSRM Routing', 'PHP 8.2', 'MariaDB'] as $tech): ?>
                <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-sm"><?= $tech ?></span>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Contact CTA -->
        <section class="bg-green-50 rounded-2xl p-6 border border-green-100 text-center">
            <h2 class="text-lg font-semibold text-gray-800 mb-2">มีข้อเสนอแนะ?</h2>
            <p class="text-gray-600 mb-4 text-sm">
                เราพัฒนาเว็บไซต์อยู่เสมอ หากพบข้อผิดพลาดหรือต้องการแนะนำสถานที่
                ยินดีรับฟังทุกความคิดเห็น
            </p>
            <a href="/contact"
               class="inline-block px-6 py-2.5 bg-green-500 hover:bg-green-600 text-white
                      font-medium rounded-full text-sm transition">
                ติดต่อเรา
            </a>
        </section>

    </main>

<?php require_once '../includes/footer.php';
