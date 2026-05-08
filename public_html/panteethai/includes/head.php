<?php
// includes/head.php — Shared HTML <head> template
// Usage: set $seo, $json_ld[], $extra_head before require_once

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/seo.php';

// $seo       array   — keys: title, desc, url, image, keywords
// $json_ld   string[] — output of jsonld_*() functions
// $extra_head string  — raw HTML injected before </head> (e.g. <style>)
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

    <?= $extra_head ?>
</head>
