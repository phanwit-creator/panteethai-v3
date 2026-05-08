<?php
// includes/seo.php — Meta Tags, JSON-LD Schema, OG Tags
// PanteeThai.com v3

// Generate meta tags สำหรับทุกหน้า
function seo_meta(array $data): string {
    $title    = htmlspecialchars($data['title']    ?? 'PanteeThai — แผนที่ท่องเที่ยวไทย');
    $desc     = htmlspecialchars($data['desc']     ?? 'แผนที่ท่องเที่ยวไทยครบทุกจังหวัด ค้นหาสถานที่ท่องเที่ยว โรงแรม ร้านอาหาร');
    $url      = htmlspecialchars($data['url']      ?? APP_URL);
    $image    = htmlspecialchars($data['image']    ?? APP_URL . '/assets/img/og-default.jpg');
    $keywords = htmlspecialchars($data['keywords'] ?? 'ท่องเที่ยวไทย,แผนที่ไทย,ที่เที่ยว');

    return <<<HTML
    <title>{$title}</title>
    <meta name="description"        content="{$desc}">
    <meta name="keywords"           content="{$keywords}">
    <link rel="canonical"           href="{$url}">

    <!-- Open Graph -->
    <meta property="og:title"       content="{$title}">
    <meta property="og:description" content="{$desc}">
    <meta property="og:url"         content="{$url}">
    <meta property="og:image"       content="{$image}">
    <meta property="og:type"        content="website">
    <meta property="og:site_name"   content="PanteeThai">
    <meta property="og:locale"      content="th_TH">

    <!-- Twitter Card -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="{$title}">
    <meta name="twitter:description" content="{$desc}">
    <meta name="twitter:image"       content="{$image}">
    HTML;
}

// JSON-LD: TouristDestination (หน้าจังหวัด)
function jsonld_tourist_destination(array $province): string {
    $data = [
        '@context'    => 'https://schema.org',
        '@type'       => 'TouristDestination',
        'name'        => $province['name_th'] . ' (' . $province['name_en'] . ')',
        'description' => $province['description'] ?? '',
        'url'         => APP_URL . '/province/' . $province['slug'],
        'geo'         => [
            '@type'     => 'GeoCoordinates',
            'latitude'  => (float)$province['lat'],
            'longitude' => (float)$province['lng'],
        ],
        'touristType' => ['ท่องเที่ยว', 'วัฒนธรรม', 'ธรรมชาติ'],
        'image'       => $province['image_url'] ?? '',
    ];
    return '<script type="application/ld+json">'
         . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
         . '</script>';
}

// JSON-LD: TouristAttraction (หน้า POI)
function jsonld_place(array $place): string {
    $data = [
        '@context'    => 'https://schema.org',
        '@type'       => 'TouristAttraction',
        'name'        => $place['name_th'],
        'description' => $place['description'] ?? '',
        'url'         => APP_URL . '/place/' . ($place['slug'] ?? $place['id']),
        'geo'         => [
            '@type'     => 'GeoCoordinates',
            'latitude'  => (float)$place['lat'],
            'longitude' => (float)$place['lng'],
        ],
        'address' => [
            '@type'           => 'PostalAddress',
            'addressCountry'  => 'TH',
            'streetAddress'   => $place['address'] ?? '',
        ],
        'telephone' => $place['phone'] ?? '',
        'url'       => $place['website'] ?? '',
    ];
    return '<script type="application/ld+json">'
         . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
         . '</script>';
}

// JSON-LD: BreadcrumbList
function jsonld_breadcrumb(array $items): string {
    $list = [];
    foreach ($items as $i => $item) {
        $list[] = [
            '@type'    => 'ListItem',
            'position' => $i + 1,
            'name'     => $item['name'],
            'item'     => APP_URL . $item['url'],
        ];
    }
    $data = [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $list,
    ];
    return '<script type="application/ld+json">'
         . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
         . '</script>';
}

// AdSense script tag
function adsense_script(): string {
    $pubId = ADSENSE_PUB_ID;
    if (!$pubId || $pubId === 'ca-pub-XXXXXXXXXX') return '';
    return <<<HTML
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={$pubId}"
            crossorigin="anonymous"></script>
    HTML;
}

// Ad unit
function adsense_unit(string $slot, string $format = 'auto'): string {
    $pubId = ADSENSE_PUB_ID;
    if (!$pubId || $pubId === 'ca-pub-XXXXXXXXXX') return '';
    return <<<HTML
    <ins class="adsbygoogle"
         style="display:block"
         data-ad-client="{$pubId}"
         data-ad-slot="{$slot}"
         data-ad-format="{$format}"
         data-full-width-responsive="true"></ins>
    <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
    HTML;
}