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
        <div class="max-w-5xl mx-auto px-4 text-xs text-gray-400 space-y-3">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                <p>© <?= date('Y') ?> <a href="/" class="hover:text-green-600 font-medium">PanteeThai.com</a> — แผนที่ท่องเที่ยวไทย</p>
                <p>
                    ข้อมูลแผนที่จาก
                    <a href="https://www.openstreetmap.org/copyright"
                       target="_blank" rel="noopener"
                       class="hover:underline">© OpenStreetMap contributors</a>
                </p>
            </div>
            <div class="flex flex-wrap justify-center sm:justify-start gap-x-4 gap-y-1">
                <a href="/blog"            class="hover:text-green-600 hover:underline transition">บทความ</a>
                <a href="/distance-calculator" class="hover:text-green-600 hover:underline transition">คำนวณระยะทาง</a>
                <a href="/about"           class="hover:text-green-600 hover:underline transition">เกี่ยวกับเรา</a>
                <a href="/contact"         class="hover:text-green-600 hover:underline transition">ติดต่อเรา</a>
                <a href="/privacy-policy"  class="hover:text-green-600 hover:underline transition">นโยบายความเป็นส่วนตัว</a>
                <a href="/sitemap.xml"     class="hover:text-green-600 hover:underline transition">Sitemap</a>
            </div>
        </div>
    </footer>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

    <?php foreach ($footer_scripts as $src): ?>
    <script src="<?= htmlspecialchars($src) ?>"></script>
    <?php endforeach; ?>

    <?php if ($footer_inline): ?>
    <script><?= $footer_inline ?></script>
    <?php endif; ?>

    <!-- ── Global Navbar JS ───────────────────────────────────────────────── -->
    <script>
    (function () {
        var nav      = document.getElementById('site-nav');
        var hbBtn    = document.getElementById('nav-hamburger');
        var menu     = document.getElementById('nav-mobile-menu');
        var iconOpen = document.getElementById('nav-icon-open');
        var iconClose= document.getElementById('nav-icon-close');
        var srchBtn  = document.getElementById('nav-search-btn');
        var mInput   = document.getElementById('nav-search-mobile');
        var dInput   = document.getElementById('nav-search-input');
        var isOpen   = false;

        // ── 1. Scroll shadow ──────────────────────────────────────────────
        if (nav) {
            window.addEventListener('scroll', function () {
                nav.classList.toggle('shadow-md', window.scrollY > 10);
            }, { passive: true });
        }

        // ── 2. Active page highlight ──────────────────────────────────────
        var path = window.location.pathname;
        document.querySelectorAll('.nav-link, .mobile-nav-link').forEach(function (a) {
            var href   = a.getAttribute('href');
            var active = href === '/'
                ? path === '/'
                : (path === href || path.startsWith(href + '/'));
            if (active) {
                a.classList.add('text-green-700', 'bg-green-50', 'font-semibold');
                a.classList.remove('text-gray-600', 'text-gray-700');
            }
        });

        // ── 3. Hamburger open / close ─────────────────────────────────────
        function openMenu() {
            isOpen = true;
            menu.style.maxHeight = menu.scrollHeight + 'px';
            iconOpen.classList.add('hidden');
            iconClose.classList.remove('hidden');
            if (hbBtn) hbBtn.setAttribute('aria-expanded', 'true');
        }
        function closeMenu() {
            isOpen = false;
            menu.style.maxHeight = '0';
            iconOpen.classList.remove('hidden');
            iconClose.classList.add('hidden');
            if (hbBtn) hbBtn.setAttribute('aria-expanded', 'false');
        }

        if (hbBtn) {
            hbBtn.addEventListener('click', function () {
                isOpen ? closeMenu() : openMenu();
            });
        }

        // ── 4. Mobile search icon → open menu + focus search input ────────
        if (srchBtn) {
            srchBtn.addEventListener('click', function () {
                if (!isOpen) openMenu();
                setTimeout(function () { if (mInput) mInput.focus(); }, 320);
            });
        }

        // ── 5. Mobile search: Enter → /search page ────────────────────────
        if (mInput) {
            mInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    var q = mInput.value.trim();
                    if (q) window.location.href = '/search?q=' + encodeURIComponent(q);
                }
            });
        }

        // ── 6. Desktop search: autocomplete via inline fetch ──────────────
        // Uses id="nav-search-input" — separate from index.php's #search-input
        if (dInput) {
            var ddWrap = dInput.parentElement;
            ddWrap.style.position = 'relative';
            var dd = document.createElement('div');
            dd.className = 'absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 '
                         + 'rounded-xl shadow-lg z-[2000] max-h-64 overflow-y-auto hidden';
            ddWrap.appendChild(dd);

            var debounce;
            dInput.addEventListener('input', function () {
                clearTimeout(debounce);
                var q = dInput.value.trim();
                if (q.length < 2) { dd.classList.add('hidden'); return; }
                debounce = setTimeout(function () {
                    fetch('/api/search.php?q=' + encodeURIComponent(q))
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            dd.innerHTML = '';
                            if (!data.success || !data.data.length) {
                                dd.innerHTML = '<div class="px-4 py-3 text-sm text-gray-400 text-center">ไม่พบผลลัพธ์</div>';
                                dd.classList.remove('hidden');
                                return;
                            }
                            data.data.forEach(function (item) {
                                var div = document.createElement('div');
                                div.className = 'px-4 py-3 hover:bg-green-50 cursor-pointer border-b border-gray-100 last:border-0';
                                var icon = item.type === 'province' ? '🗺️' : '📍';
                                var sub  = (item.name_en || '') + (item.province_name ? ' · ' + item.province_name : '');
                                div.innerHTML = '<div class="flex items-center gap-2">'
                                    + '<span class="text-lg">' + icon + '</span>'
                                    + '<div><div class="font-medium text-gray-800 text-sm">'
                                    + item.name_th + '</div>'
                                    + (sub ? '<div class="text-xs text-gray-500">' + sub + '</div>' : '')
                                    + '</div></div>';
                                div.addEventListener('click', function () {
                                    dInput.value = item.name_th;
                                    dd.classList.add('hidden');
                                    // Fly map if on homepage
                                    if (typeof PanteeMap !== 'undefined' && item.lat && item.lng) {
                                        var zoom = item.type === 'province' ? (item.zoom || 11) : 14;
                                        PanteeMap.flyTo(item.lat, item.lng, zoom);
                                        if (item.type === 'province') PanteeMap.loadPOI(item.slug);
                                    } else if (item.type === 'province' && item.slug) {
                                        window.location.href = '/province/' + item.slug;
                                    } else if (item.id) {
                                        window.location.href = '/place/' + item.id;
                                    }
                                });
                                dd.appendChild(div);
                            });
                            dd.classList.remove('hidden');
                        })
                        .catch(function () { dd.classList.add('hidden'); });
                }, 300);
            });

            // Keyboard navigation
            dInput.addEventListener('keydown', function (e) {
                var items = dd.querySelectorAll('.cursor-pointer');
                var active = dd.querySelector('.bg-green-100');
                var idx = Array.from(items).indexOf(active);
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (active) active.classList.remove('bg-green-100');
                    items[(idx + 1) % items.length]?.classList.add('bg-green-100');
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (active) active.classList.remove('bg-green-100');
                    items[(idx - 1 + items.length) % items.length]?.classList.add('bg-green-100');
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    var a = dd.querySelector('.bg-green-100');
                    if (a) { a.click(); }
                    else {
                        var q = dInput.value.trim();
                        if (q) window.location.href = '/search?q=' + encodeURIComponent(q);
                    }
                } else if (e.key === 'Escape') {
                    dd.classList.add('hidden');
                }
            });

            document.addEventListener('click', function (e) {
                if (!ddWrap.contains(e.target)) dd.classList.add('hidden');
            });
        }

        // ── 7. Close hamburger on outside click ───────────────────────────
        document.addEventListener('click', function (e) {
            if (isOpen && nav && !nav.contains(e.target)) closeMenu();
        });

        // ── 8. Close hamburger on menu item click ─────────────────────────
        document.querySelectorAll('.mobile-nav-link').forEach(function (a) {
            a.addEventListener('click', closeMenu);
        });

        // ── 9. Close hamburger when resizing to desktop ───────────────────
        window.addEventListener('resize', function () {
            if (isOpen && window.innerWidth >= 768) closeMenu();
        }, { passive: true });

    })();
    </script>

</body>
</html>
