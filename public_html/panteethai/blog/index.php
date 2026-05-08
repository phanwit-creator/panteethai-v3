<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/seo.php';

$province = $_GET['province'] ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 12;
$offset   = ($page - 1) * $perPage;

// Sanitize province slug
if ($province && !preg_match('/^[a-z0-9-]+$/', $province)) {
    $province = '';
}

// Build query
$where  = ["a.status = 'published'"];
$params = [];

if ($province) {
    $where[]              = 'a.province_slug = :province';
    $params[':province']  = $province;
}

$whereStr = implode(' AND ', $where);

$total = (int)db_row(
    "SELECT COUNT(*) AS c FROM articles a WHERE {$whereStr}",
    $params
)['c'];

$totalPages = (int)ceil($total / $perPage);

$articles = db_query(
    "SELECT a.id, a.slug, a.title_th, a.title_en, a.featured_image,
            a.published_at, a.province_slug, a.seo_keywords,
            pr.name_th AS province_name
     FROM articles a
     LEFT JOIN provinces pr ON a.province_slug = pr.slug
     WHERE {$whereStr}
     ORDER BY a.published_at DESC
     LIMIT :limit OFFSET :offset",
    array_merge($params, [':limit' => $perPage, ':offset' => $offset])
);

$provinceList = db_query(
    "SELECT slug, name_th FROM provinces ORDER BY name_th"
);

$seoTitle = $province
    ? "บทความท่องเที่ยว " . ($articles[0]['province_name'] ?? $province) . " | PanteeThai"
    : "บทความท่องเที่ยวไทย — เคล็ดลับและไกด์ | PanteeThai";
$seoDesc = "บทความท่องเที่ยวไทย ข้อมูลสถานที่ท่องเที่ยว เคล็ดลับการเดินทาง ทั่วทุกจังหวัด";

$seo = [
    'title'    => $seoTitle,
    'desc'     => $seoDesc,
    'url'      => APP_URL . '/blog' . ($province ? '?province=' . htmlspecialchars($province) : ''),
    'keywords' => 'บทความท่องเที่ยว,เที่ยวไทย,ไกด์ท่องเที่ยว',
];
$json_ld    = [];
$extra_head = '';

require_once '../includes/head.php';
?>
<body class="bg-gray-50">

    <nav class="bg-white shadow-sm h-16 flex items-center px-4 justify-between">
        <a href="/" class="text-xl font-bold text-green-600">PanteeThai</a>
        <div class="flex gap-4 text-sm text-gray-600">
            <a href="/blog" class="text-green-600 font-medium">บทความ</a>
            <a href="/" class="hover:text-green-600">แผนที่</a>
        </div>
    </nav>

    <!-- Header -->
    <div class="bg-white border-b px-4 py-6">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-2xl font-bold text-gray-800">บทความท่องเที่ยวไทย</h1>
            <p class="text-gray-500 text-sm mt-1"><?= $total ?> บทความ</p>
        </div>
    </div>

    <!-- Ad -->
    <div class="max-w-4xl mx-auto px-4 pt-4">
        <?= adsense_unit('XXXXXXXXXX', 'horizontal') ?>
    </div>

    <div class="max-w-4xl mx-auto px-4 py-6 flex gap-8">

        <!-- Articles Grid -->
        <main class="flex-1 min-w-0">

            <?php if (empty($articles)): ?>
            <div class="text-center py-16 text-gray-400">
                <p class="text-4xl mb-3">📝</p>
                <p>ยังไม่มีบทความในขณะนี้</p>
            </div>
            <?php else: ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <?php foreach ($articles as $i => $art): ?>
                <article class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition">
                    <?php if ($art['featured_image']): ?>
                    <img src="<?= htmlspecialchars($art['featured_image']) ?>"
                         alt="<?= htmlspecialchars($art['title_th']) ?>"
                         class="w-full h-44 object-cover"
                         loading="lazy">
                    <?php else: ?>
                    <div class="w-full h-44 bg-gradient-to-br from-green-100 to-emerald-200 flex items-center justify-center text-4xl">
                        🌏
                    </div>
                    <?php endif; ?>

                    <div class="p-4">
                        <?php if ($art['province_name']): ?>
                        <a href="/blog?province=<?= htmlspecialchars($art['province_slug']) ?>"
                           class="text-xs text-green-600 font-medium hover:underline">
                            <?= htmlspecialchars($art['province_name']) ?>
                        </a>
                        <?php endif; ?>

                        <h2 class="font-semibold text-gray-800 mt-1 leading-snug">
                            <a href="/blog/<?= htmlspecialchars($art['slug']) ?>"
                               class="hover:text-green-600">
                                <?= htmlspecialchars($art['title_th']) ?>
                            </a>
                        </h2>

                        <p class="text-xs text-gray-400 mt-2">
                            <?= date('j M Y', strtotime($art['published_at'])) ?>
                        </p>
                    </div>
                </article>

                <?php // In-feed ad after 4th article
                if ($i === 3): ?>
                <div class="sm:col-span-2">
                    <?= adsense_unit('XXXXXXXXXX', 'fluid') ?>
                </div>
                <?php endif; ?>

                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav class="flex justify-center gap-2 mt-8">
                <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?><?= $province ? '&province=' . htmlspecialchars($province) : '' ?>"
                   class="px-4 py-2 bg-white rounded-lg shadow-sm text-sm hover:bg-green-50">
                    &laquo; ก่อนหน้า
                </a>
                <?php endif; ?>

                <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                <a href="?page=<?= $p ?><?= $province ? '&province=' . htmlspecialchars($province) : '' ?>"
                   class="px-4 py-2 rounded-lg shadow-sm text-sm <?= $p === $page ? 'bg-green-500 text-white' : 'bg-white hover:bg-green-50' ?>">
                    <?= $p ?>
                </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?><?= $province ? '&province=' . htmlspecialchars($province) : '' ?>"
                   class="px-4 py-2 bg-white rounded-lg shadow-sm text-sm hover:bg-green-50">
                    ถัดไป &raquo;
                </a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>

            <?php endif; ?>
        </main>

        <!-- Sidebar -->
        <aside class="hidden md:block w-56 flex-shrink-0">
            <div class="bg-white rounded-xl shadow-sm p-4 sticky top-4">
                <h3 class="font-semibold text-gray-700 mb-3 text-sm">จังหวัด</h3>
                <ul class="space-y-1 text-sm max-h-96 overflow-y-auto">
                    <li>
                        <a href="/blog"
                           class="block px-2 py-1 rounded hover:bg-green-50 <?= !$province ? 'text-green-600 font-medium' : 'text-gray-600' ?>">
                            ทั้งหมด
                        </a>
                    </li>
                    <?php foreach ($provinceList as $prov): ?>
                    <li>
                        <a href="/blog?province=<?= htmlspecialchars($prov['slug']) ?>"
                           class="block px-2 py-1 rounded hover:bg-green-50 <?= $province === $prov['slug'] ? 'text-green-600 font-medium' : 'text-gray-600' ?>">
                            <?= htmlspecialchars($prov['name_th']) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>

    </div>

<?php require_once '../includes/footer.php';
