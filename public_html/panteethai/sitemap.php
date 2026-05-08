<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

header('Content-Type: application/xml; charset=utf-8');

$base = APP_URL;
$now  = date('Y-m-d');

try {
    $provinces = db_query("SELECT slug FROM provinces ORDER BY slug");
    $articles  = db_query(
        "SELECT slug, published_at FROM articles WHERE status = 'published' ORDER BY published_at DESC LIMIT 500"
    );
    $places    = db_query(
        "SELECT id FROM places WHERE status = 'active' ORDER BY id LIMIT 2000"
    );
} catch (Exception $e) {
    error_log('sitemap.php: ' . $e->getMessage());
    $provinces = [];
    $articles  = [];
    $places    = [];
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">

    <!-- หน้าแรก -->
    <url>
        <loc><?= $base ?>/</loc>
        <lastmod><?= $now ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

    <!-- หน้า Distance Calculator -->
    <url>
        <loc><?= $base ?>/distance-calculator</loc>
        <lastmod><?= $now ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>

    <!-- หน้า Blog -->
    <url>
        <loc><?= $base ?>/blog</loc>
        <lastmod><?= $now ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>

    <!-- หน้าจังหวัด -->
    <?php foreach ($provinces as $p): ?>
    <url>
        <loc><?= $base ?>/province/<?= htmlspecialchars($p['slug']) ?></loc>
        <lastmod><?= $now ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= $base ?>/province/<?= htmlspecialchars($p['slug']) ?>/places</loc>
        <lastmod><?= $now ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php endforeach; ?>

    <!-- สถานที่ท่องเที่ยว -->
    <?php foreach ($places as $pl): ?>
    <url>
        <loc><?= $base ?>/place/<?= (int)$pl['id'] ?></loc>
        <lastmod><?= $now ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    <?php endforeach; ?>

    <!-- บทความ -->
    <?php foreach ($articles as $a): ?>
    <url>
        <loc><?= $base ?>/blog/<?= htmlspecialchars($a['slug']) ?></loc>
        <lastmod><?= $a['published_at'] ? date('Y-m-d', strtotime($a['published_at'])) : $now ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <?php endforeach; ?>

</urlset>
