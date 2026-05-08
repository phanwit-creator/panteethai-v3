<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/seo.php';

$slug = $_GET['slug'] ?? '';

if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
    http_response_code(404);
    die('ไม่พบบทความนี้');
}

$article = db_row(
    "SELECT a.*,
            pr.name_th AS province_name_th,
            pr.name_en AS province_name_en
     FROM articles a
     LEFT JOIN provinces pr ON a.province_slug = pr.slug
     WHERE a.slug = ? AND a.status = 'published'",
    [$slug]
);

if (!$article) {
    http_response_code(404);
    die('ไม่พบบทความนี้');
}

// Related articles from the same province
$related = db_query(
    "SELECT id, slug, title_th, featured_image, published_at
     FROM articles
     WHERE province_slug = ? AND slug != ? AND status = 'published'
     ORDER BY published_at DESC
     LIMIT 3",
    [$article['province_slug'], $slug]
);

// JSON-LD Article schema
$articleSchema = [
    '@context'         => 'https://schema.org',
    '@type'            => 'Article',
    'headline'         => $article['title_th'],
    'description'      => $article['seo_keywords'] ?? '',
    'image'            => $article['featured_image'] ?? APP_URL . '/assets/img/og-default.jpg',
    'datePublished'    => $article['published_at'],
    'dateModified'     => $article['published_at'],
    'url'              => APP_URL . '/blog/' . $slug,
    'publisher'        => [
        '@type' => 'Organization',
        'name'  => 'PanteeThai',
        'url'   => APP_URL,
    ],
];

$seoTitle = htmlspecialchars($article['title_th']) . ' | PanteeThai';
$seoDesc  = $article['seo_keywords']
    ? mb_substr($article['seo_keywords'], 0, 155)
    : mb_substr(strip_tags($article['content_th'] ?? ''), 0, 155);

$seo = [
    'title'    => $seoTitle,
    'desc'     => $seoDesc,
    'url'      => APP_URL . '/blog/' . $slug,
    'image'    => $article['featured_image'] ?? '',
    'keywords' => $article['seo_keywords'] ?? '',
];

$json_ld = [
    '<script type="application/ld+json">'
    . json_encode($articleSchema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    . '</script>',
    jsonld_breadcrumb([
        ['name' => 'หน้าแรก',            'url' => '/'],
        ['name' => 'บทความ',             'url' => '/blog'],
        ['name' => $article['title_th'], 'url' => '/blog/' . $slug],
    ]),
];

$extra_head = '';

require_once '../includes/head.php';
?>
<body class="bg-gray-50">

    <nav class="bg-white shadow-sm h-16 flex items-center px-4 justify-between">
        <a href="/" class="text-xl font-bold text-green-600">PanteeThai</a>
        <div class="flex gap-4 text-sm text-gray-600">
            <a href="/blog" class="hover:text-green-600">บทความ</a>
            <a href="/" class="hover:text-green-600">แผนที่</a>
        </div>
    </nav>

    <div class="max-w-2xl mx-auto px-4 py-8">

        <!-- Breadcrumb -->
        <nav class="text-xs text-gray-400 mb-5 flex gap-1 items-center">
            <a href="/" class="hover:underline">หน้าแรก</a>
            <span>/</span>
            <a href="/blog" class="hover:underline">บทความ</a>
            <?php if ($article['province_slug']): ?>
            <span>/</span>
            <a href="/blog?province=<?= htmlspecialchars($article['province_slug']) ?>"
               class="hover:underline">
                <?= htmlspecialchars($article['province_name_th'] ?? '') ?>
            </a>
            <?php endif; ?>
        </nav>

        <!-- Article Header -->
        <header class="mb-6">
            <?php if ($article['province_name_th']): ?>
            <a href="/blog?province=<?= htmlspecialchars($article['province_slug']) ?>"
               class="text-xs text-green-600 font-medium hover:underline">
                <?= htmlspecialchars($article['province_name_th']) ?>
            </a>
            <?php endif; ?>

            <h1 class="text-3xl font-bold text-gray-900 mt-1 leading-tight">
                <?= htmlspecialchars($article['title_th']) ?>
            </h1>

            <?php if ($article['title_en']): ?>
            <p class="text-gray-400 text-sm mt-1"><?= htmlspecialchars($article['title_en']) ?></p>
            <?php endif; ?>

            <p class="text-xs text-gray-400 mt-3">
                <?= date('j F Y', strtotime($article['published_at'])) ?>
            </p>
        </header>

        <!-- Featured Image -->
        <?php if ($article['featured_image']): ?>
        <img src="<?= htmlspecialchars($article['featured_image']) ?>"
             alt="<?= htmlspecialchars($article['title_th']) ?>"
             class="w-full rounded-xl mb-6 object-cover max-h-80">
        <?php endif; ?>

        <!-- Ad: Top of article -->
        <div class="mb-6">
            <?= adsense_unit('XXXXXXXXXX', 'horizontal') ?>
        </div>

        <!-- Article Body -->
        <article class="prose prose-sm max-w-none text-gray-700 leading-relaxed">
            <?= nl2br(htmlspecialchars($article['content_th'] ?? '')) ?>
        </article>

        <!-- Ad: Mid-article -->
        <div class="my-8">
            <?= adsense_unit('XXXXXXXXXX') ?>
        </div>

        <!-- English version toggle -->
        <?php if ($article['content_en']): ?>
        <details class="bg-blue-50 rounded-xl p-4 my-6">
            <summary class="cursor-pointer text-sm font-medium text-blue-700">
                Read in English
            </summary>
            <div class="mt-4 text-sm text-gray-700 leading-relaxed">
                <?= nl2br(htmlspecialchars($article['content_en'])) ?>
            </div>
        </details>
        <?php endif; ?>

        <!-- Province link -->
        <?php if ($article['province_slug']): ?>
        <div class="bg-green-50 rounded-xl p-5 mt-8 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">สำรวจสถานที่ท่องเที่ยวใน</p>
                <p class="font-semibold text-gray-800"><?= htmlspecialchars($article['province_name_th'] ?? '') ?></p>
            </div>
            <a href="/province/<?= htmlspecialchars($article['province_slug']) ?>"
               class="px-4 py-2 bg-green-500 text-white rounded-lg text-sm hover:bg-green-600">
                ดูแผนที่ →
            </a>
        </div>
        <?php endif; ?>

        <!-- Related Articles -->
        <?php if (!empty($related)): ?>
        <section class="mt-10">
            <h2 class="font-bold text-gray-800 mb-4">บทความที่เกี่ยวข้อง</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <?php foreach ($related as $rel): ?>
                <a href="/blog/<?= htmlspecialchars($rel['slug']) ?>"
                   class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition">
                    <?php if ($rel['featured_image']): ?>
                    <img src="<?= htmlspecialchars($rel['featured_image']) ?>"
                         alt="<?= htmlspecialchars($rel['title_th']) ?>"
                         class="w-full h-32 object-cover" loading="lazy">
                    <?php else: ?>
                    <div class="w-full h-32 bg-gradient-to-br from-green-100 to-emerald-200 flex items-center justify-center text-3xl">🌏</div>
                    <?php endif; ?>
                    <div class="p-3">
                        <p class="text-sm font-medium text-gray-800 leading-snug line-clamp-2">
                            <?= htmlspecialchars($rel['title_th']) ?>
                        </p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

    </div>

<?php require_once '../includes/footer.php';
