<?php
// includes/footer.php — Shared site footer + script loader
// Usage: optionally set $footer_scripts (array of src URLs) or $footer_inline (JS string) before require_once

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/seo.php';

// $footer_scripts string[] — additional <script src="..."> to load
// $footer_inline  string   — inline <script> block content
$footer_scripts = $footer_scripts ?? [];
$footer_inline  = $footer_inline  ?? '';
?>

    <footer class="border-t bg-white py-6 mt-8">
        <div class="max-w-5xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-400">
            <p>© <?= date('Y') ?> <a href="/" class="hover:text-green-600 font-medium">PanteeThai.com</a> — แผนที่ท่องเที่ยวไทย</p>
            <p>
                ข้อมูลแผนที่จาก
                <a href="https://www.openstreetmap.org/copyright"
                   target="_blank" rel="noopener"
                   class="hover:underline">© OpenStreetMap contributors</a>
                ·
                <a href="/sitemap.xml" class="hover:underline">Sitemap</a>
                ·
                <a href="/blog" class="hover:underline">บทความ</a>
            </p>
        </div>
    </footer>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV/XN2GqaE=" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

    <?php foreach ($footer_scripts as $src): ?>
    <script src="<?= htmlspecialchars($src) ?>"></script>
    <?php endforeach; ?>

    <?php if ($footer_inline): ?>
    <script><?= $footer_inline ?></script>
    <?php endif; ?>

</body>
</html>
