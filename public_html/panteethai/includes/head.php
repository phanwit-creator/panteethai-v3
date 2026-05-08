<?php
// includes/head.php — Shared HTML <head> + global sticky navbar
// Usage: set $seo, $json_ld[], $extra_head before require_once

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/seo.php';

// $seo       array   — keys: title, desc, url, image, keywords
// $json_ld   string[] — output of jsonld_*() functions
// $extra_head string  — raw HTML injected before </head>
$seo        = $seo        ?? [];
$json_ld    = $json_ld    ?? [];
$extra_head = $extra_head ?? '';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?= seo_meta($seo) ?>

    <?php foreach ($json_ld as $schema): ?>
    <?= $schema ?>
    <?php endforeach; ?>

    <?= adsense_script() ?>

    <!-- Tailwind CSS (Play CDN — no build step) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Leaflet 1.9 -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">

    <!-- Site CSS -->
    <link rel="stylesheet" href="/assets/css/app.css">

    <!-- Global navbar body offset: 52px mobile / 56px desktop -->
    <style>body{padding-top:52px}@media(min-width:768px){body{padding-top:56px}}</style>

    <?= $extra_head ?>

    <!-- Google Analytics 4 -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-86FDSSS7XG"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-86FDSSS7XG');
    </script>

    <!-- Google AdSense -->
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= defined('ADSENSE_PUB_ID') ? htmlspecialchars(ADSENSE_PUB_ID) : '' ?>"
         crossorigin="anonymous"></script>
</head>

<!-- ── Global Sticky Navbar ──────────────────────────────────────────────── -->
<!-- Rendered before each page's <body> tag; browser merges body attributes  -->
<nav id="site-nav"
     class="fixed top-0 left-0 right-0 z-[1000] bg-white transition-shadow duration-200">

  <!-- ── Main bar ── -->
  <div class="max-w-screen-xl mx-auto px-4 flex items-center gap-2 h-[52px] md:h-14">

    <!-- Logo -->
    <a href="/"
       class="flex items-center gap-1 font-bold text-green-600 text-base sm:text-lg
              flex-shrink-0 mr-1 hover:opacity-80 transition-opacity duration-150">
      🗺️ <span>PanteeThai</span>
    </a>

    <!-- Desktop search (md+) — uses id="nav-search-input" to avoid conflict
         with index.php's own #search-input map search bar -->
    <div class="hidden md:flex flex-1 max-w-md mx-2">
      <div class="relative w-full">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input id="nav-search-input" type="text" autocomplete="off"
               placeholder="ค้นหาสถานที่, จังหวัด..."
               class="w-full pl-9 pr-4 py-2 text-sm bg-gray-100 rounded-full
                      border border-transparent focus:outline-none focus:ring-2
                      focus:ring-green-400 focus:bg-white transition-all duration-200">
      </div>
    </div>

    <!-- Flex spacer (mobile only) -->
    <div class="flex-1 md:hidden"></div>

    <!-- Desktop nav links (md+) -->
    <div class="hidden md:flex items-center gap-0.5 flex-shrink-0">
      <a href="/blog"
         class="nav-link px-3 py-2 rounded-lg text-sm font-medium text-gray-600
                hover:text-green-700 hover:bg-green-50 transition-colors duration-150">
        บทความ
      </a>
      <a href="/distance-calculator"
         class="nav-link px-3 py-2 rounded-lg text-sm font-medium text-gray-600
                hover:text-green-700 hover:bg-green-50 transition-colors duration-150">
        คำนวณระยะทาง
      </a>
      <span class="ml-1 text-xl select-none" aria-hidden="true">🇹🇭</span>
    </div>

    <!-- Mobile: search icon -->
    <button id="nav-search-btn"
            class="md:hidden p-2 rounded-lg text-gray-500 hover:text-green-600
                   hover:bg-gray-100 transition-colors duration-150"
            aria-label="ค้นหา">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
      </svg>
    </button>

    <!-- Mobile: hamburger toggle -->
    <button id="nav-hamburger"
            class="md:hidden p-2 rounded-lg text-gray-500 hover:text-green-600
                   hover:bg-gray-100 transition-colors duration-150"
            aria-label="เมนู" aria-expanded="false">
      <svg id="nav-icon-open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
      <svg id="nav-icon-close" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>

  </div><!-- /main bar -->

  <!-- ── Mobile dropdown menu ── -->
  <div id="nav-mobile-menu"
       class="md:hidden bg-white border-t border-gray-100 overflow-hidden
              transition-all duration-300 ease-in-out"
       style="max-height:0">
    <div class="px-4 pt-3 pb-4 space-y-1">

      <!-- Mobile search bar (full width, submits to /search page) -->
      <div class="relative mb-3">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input id="nav-search-mobile" type="text" autocomplete="off"
               placeholder="ค้นหาสถานที่, จังหวัด..."
               class="w-full pl-9 pr-4 py-2.5 text-sm bg-gray-100 rounded-xl
                      border border-transparent focus:outline-none focus:ring-2
                      focus:ring-green-400 focus:bg-white transition-all duration-200">
      </div>

      <a href="/blog"
         class="mobile-nav-link flex items-center px-3 py-2.5 rounded-lg text-sm
                text-gray-700 hover:text-green-700 hover:bg-green-50 transition-colors duration-150">
        บทความ
      </a>
      <a href="/distance-calculator"
         class="mobile-nav-link flex items-center px-3 py-2.5 rounded-lg text-sm
                text-gray-700 hover:text-green-700 hover:bg-green-50 transition-colors duration-150">
        คำนวณระยะทาง
      </a>
      <a href="/"
         class="mobile-nav-link flex items-center px-3 py-2.5 rounded-lg text-sm
                text-gray-700 hover:text-green-700 hover:bg-green-50 transition-colors duration-150">
        หน้าแรก
      </a>

    </div>
  </div><!-- /mobile dropdown -->

</nav>
