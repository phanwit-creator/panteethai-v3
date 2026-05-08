<?php
require_once 'includes/config.php';
require_once 'includes/seo.php';

http_response_code(404);

$seo = [
    'title'    => 'ไม่พบหน้าที่ค้นหา (404) — PanteeThai',
    'desc'     => 'ไม่พบหน้าที่คุณค้นหา กรุณาตรวจสอบ URL หรือกลับไปยังหน้าแรก',
    'url'      => APP_URL . '/404',
    'keywords' => '',
];
$json_ld    = [];
$extra_head = '<meta name="robots" content="noindex, nofollow">';

require_once 'includes/head.php';
?>
<body class="bg-gray-50 min-h-screen flex flex-col">

    <!-- Navbar -->
    <nav class="bg-white shadow-sm h-16 flex items-center px-4 gap-3 relative z-[900]">
        <a href="/" class="text-xl font-bold text-green-600 flex-shrink-0">PanteeThai</a>
    </nav>

    <!-- 404 content -->
    <main class="flex-1 flex items-center justify-center px-4 py-16">
        <div class="text-center max-w-md w-full">

            <!-- Illustration -->
            <div class="text-8xl mb-4 select-none">🗺️</div>
            <p class="text-6xl font-bold text-gray-200 mb-2 leading-none">404</p>
            <h1 class="text-xl font-semibold text-gray-700 mb-2">ไม่พบหน้าที่คุณค้นหา</h1>
            <p class="text-sm text-gray-400 mb-8">
                อาจเป็นเพราะ URL ผิดพลาด หรือหน้านี้ถูกย้ายไปแล้ว
            </p>

            <!-- Search bar -->
            <form action="/search" method="get" class="mb-8">
                <div class="flex gap-2 max-w-sm mx-auto">
                    <input type="text" name="q"
                           placeholder="ค้นหาจังหวัด หรือสถานที่..."
                           class="flex-1 px-4 py-2.5 border border-gray-300 rounded-full text-sm
                                  focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100">
                    <button type="submit"
                            class="px-5 py-2.5 bg-green-500 hover:bg-green-600 text-white
                                   font-medium rounded-full text-sm transition flex-shrink-0">
                        ค้นหา
                    </button>
                </div>
            </form>

            <!-- Quick links -->
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="/"
                   class="px-5 py-2.5 bg-green-500 hover:bg-green-600 text-white
                          font-medium rounded-full text-sm transition text-center">
                    🏠 หน้าแรก
                </a>
                <a href="/province/bangkok"
                   class="px-5 py-2.5 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200
                          font-medium rounded-full text-sm transition text-center shadow-sm">
                    🗺️ แผนที่ไทย
                </a>
                <a href="/distance-calculator"
                   class="px-5 py-2.5 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200
                          font-medium rounded-full text-sm transition text-center shadow-sm">
                    📏 คำนวณระยะทาง
                </a>
            </div>

        </div>
    </main>

<?php require_once 'includes/footer.php';
