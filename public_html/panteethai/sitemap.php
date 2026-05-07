<?php
// sitemap.php — Dynamic XML Sitemap
// PanteeThai.com v3

require_once 'includes/config.php';
require_once 'includes/db.php';

header('Content-Type: application/xml; charset=utf-8');

$base = APP_URL;
$now  = date('Y-m-d');

// ดึงข้อมูล
$provinces = db_query("SELECT slug, updated_at FROM provinces ORDER BY slug");
$articles  = db_query("SELECT slug, updated_at FROM articles WHERE status = 'published' ORDER BY published_at DESC");

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
        <lastmod><?= date('Y-m-d', strtotime($p['updated_at'])) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= $base ?>/province/<?= htmlspecialchars($p['slug']) ?>/places</loc>
        <lastmod><?= date('Y-m-d', strtotime($p['updated_at'])) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php endforeach; ?>

    <!-- บทความ -->
    <?php foreach ($articles as $a): ?>
    <url>
        <loc><?= $base ?>/blog/<?= htmlspecialchars($a['slug']) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($a['updated_at'])) ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <?php endforeach; ?>

</urlset>