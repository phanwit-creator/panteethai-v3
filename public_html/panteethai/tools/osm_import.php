<?php
/**
 * tools/osm_import.php
 *
 * Fetches POI data from OSM Overpass API and generates INSERT SQL
 * for the PanteeThai places table.
 *
 * Usage:  php tools/osm_import.php
 * Output: tools/osm_data.sql
 *
 * Source: OpenStreetMap contributors (ODbL) https://www.openstreetmap.org/copyright
 */

define('OVERPASS_API', 'https://overpass-api.de/api/interpreter');
define('OUTPUT_FILE',  __DIR__ . '/osm_data.sql');
define('SLEEP_SECS',   2);
define('LIMIT',        50);

// ── Provinces ─────────────────────────────────────────────────────────────────
// slug => [display name, [south, west, north, east]]
$provinces = [
    'bangkok'           => ['Bangkok',           [13.49, 100.33, 13.96, 100.94]],
    'chiang-mai'        => ['Chiang Mai',        [18.15,  98.32, 19.52,  99.53]],
    'phuket'            => ['Phuket',            [ 7.74,  98.25,  8.21,  98.48]],
    'krabi'             => ['Krabi',             [ 7.50,  98.60,  8.45,  99.30]],
    'koh-samui'         => ['Koh Samui',         [ 9.40,  99.75,  9.65, 100.10]],
    'chiang-rai'        => ['Chiang Rai',        [19.47,  99.30, 20.43, 100.65]],
    'ayutthaya'         => ['Ayutthaya',         [14.15, 100.30, 14.60, 100.80]],
    'nakhon-ratchasima' => ['Nakhon Ratchasima', [14.20, 101.40, 15.50, 102.50]],
    'khon-kaen'         => ['Khon Kaen',         [15.80, 101.90, 16.70, 103.00]],
    'surat-thani'       => ['Surat Thani',       [ 8.50,  98.80,  9.80, 100.10]],
];

// ── Categories ────────────────────────────────────────────────────────────────
// slug => [label, [[tag_key => tag_val, ...]]]
// Multiple filter sets are OR'd (separate union arms in Overpass QL)
$categories = [
    'temple'     => ['Temple',     [['amenity' => 'place_of_worship', 'religion' => 'buddhist']]],
    'market'     => ['Market',     [['amenity' => 'marketplace']]],
    'museum'     => ['Museum',     [['tourism' => 'museum']]],
    'waterfall'  => ['Waterfall',  [['waterway' => 'waterfall']]],
    'beach'      => ['Beach',      [['natural'  => 'beach']]],
    'hotel'      => ['Hotel',      [['tourism'  => 'hotel']]],
    'restaurant' => ['Restaurant', [['amenity'  => 'restaurant', 'cuisine' => 'thai']]],
    'nature'     => ['Nature',     [['tourism'  => 'viewpoint'], ['natural' => 'peak']]],
];

// ── Helpers ───────────────────────────────────────────────────────────────────

function build_query(array $filterSets, array $bbox, int $limit): string
{
    [$s, $w, $n, $e] = $bbox;
    $bboxStr = "{$s},{$w},{$n},{$e}";
    $arms = [];
    foreach ($filterSets as $tags) {
        $tagStr = '';
        foreach ($tags as $k => $v) {
            $tagStr .= "[\"$k\"=\"$v\"]";
        }
        foreach (['node', 'way', 'relation'] as $type) {
            $arms[] = "  {$type}{$tagStr}({$bboxStr});";
        }
    }
    return "[out:json][timeout:30];\n(\n" . implode("\n", $arms) . "\n);\nout center {$limit};";
}

function fetch_overpass(string $query): ?array
{
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n"
                       . "User-Agent: PanteeThai-OSM-Import/1.0 (panteethai.com)",
            'content' => 'data=' . urlencode($query),
            'timeout' => 60,
        ],
    ]);
    $body = @file_get_contents(OVERPASS_API, false, $ctx);
    if ($body === false) return null;
    $data = json_decode($body, true);
    return $data['elements'] ?? null;
}

function get_coords(array $el): ?array
{
    if ($el['type'] === 'node' && isset($el['lat'], $el['lon'])) {
        return [(float)$el['lat'], (float)$el['lon']];
    }
    // ways and relations use center when requested via "out center"
    if (isset($el['center']['lat'], $el['center']['lon'])) {
        return [(float)$el['center']['lat'], (float)$el['center']['lon']];
    }
    return null;
}

function sql_esc(string $s): string
{
    return str_replace(
        ["\\",   "'",   "\n",  "\r",  "\0"],
        ["\\\\", "\\'", "\\n", "\\r", ''],
        $s
    );
}

function make_insert(string $slug, string $nameTh, string $nameEn, string $cat, float $lat, float $lng): string
{
    $nameTh = sql_esc(mb_substr($nameTh, 0, 200));
    $nameEn = sql_esc(mb_substr($nameEn, 0, 200));
    $lngStr = number_format($lng, 7, '.', '');
    $latStr = number_format($lat, 7, '.', '');
    return "INSERT IGNORE INTO places"
         . " (province_slug, name_th, name_en, category, location, source, status)"
         . " VALUES"
         . " ('{$slug}', '{$nameTh}', '{$nameEn}', '{$cat}',"
         . " ST_GeomFromText('POINT({$lngStr} {$latStr})', 4326), 'osm', 'active');";
}

function progress(string $msg): void
{
    fwrite(STDERR, $msg . "\n");
}

// ── Main ──────────────────────────────────────────────────────────────────────

progress('PanteeThai OSM Import');
progress('Output: ' . OUTPUT_FILE);
progress(str_repeat('─', 50));

$lines = [];
$lines[] = '-- osm_data.sql — OpenStreetMap POI import for PanteeThai.com';
$lines[] = '-- Generated:  ' . date('Y-m-d H:i:s T');
$lines[] = '-- Source:     OpenStreetMap contributors (ODbL)';
$lines[] = '--             https://www.openstreetmap.org/copyright';
$lines[] = '-- Script:     tools/osm_import.php';
$lines[] = '-- Categories: temple, market, museum, waterfall, beach, hotel, restaurant, nature';
$lines[] = '-- Provinces:  10 pilot provinces';
$lines[] = '';
$lines[] = 'SET NAMES utf8mb4;';
$lines[] = '';

$totalRows = 0;
$errors    = 0;

foreach ($provinces as $slug => [$name, $bbox]) {
    progress("\n[{$name}]");
    $lines[] = '';
    $lines[] = '-- ' . str_repeat('─', 60);
    $lines[] = "-- {$name} ({$slug})";
    $lines[] = '-- ' . str_repeat('─', 60);

    $seen = []; // deduplicate by name_th within province

    foreach ($categories as $cat => [$label, $filterSets]) {
        progress("  [{$label}] fetching...");

        $query    = build_query($filterSets, $bbox, LIMIT);
        $elements = fetch_overpass($query);

        if ($elements === null) {
            progress("  [{$label}] ERROR — API call failed, skipping");
            $errors++;
            sleep(SLEEP_SECS);
            continue;
        }

        $count = 0;
        foreach ($elements as $el) {
            $coords = get_coords($el);
            if (!$coords) continue;

            $tags   = $el['tags'] ?? [];
            $nameTh = trim($tags['name:th'] ?? '');
            $nameEn = trim($tags['name:en'] ?? $tags['name'] ?? '');

            // Fallback: use name field for name_th if no Thai name available
            if ($nameTh === '') {
                $nameTh = trim($tags['name'] ?? '');
            }

            // Skip elements with no usable name at all
            if ($nameTh === '' && $nameEn === '') continue;

            // Cross-fill empty side
            if ($nameTh === '') $nameTh = $nameEn;
            if ($nameEn === '') $nameEn = $nameTh;

            // Deduplicate within this province by normalised Thai name
            $dedupeKey = mb_strtolower(trim($nameTh));
            if (isset($seen[$dedupeKey])) continue;
            $seen[$dedupeKey] = true;

            [$lat, $lng] = $coords;
            $lines[] = make_insert($slug, $nameTh, $nameEn, $cat, $lat, $lng);
            $count++;
            $totalRows++;
        }

        progress("  [{$label}] {$count} rows");
        sleep(SLEEP_SECS);
    }
}

$lines[] = '';
$lines[] = '-- ' . str_repeat('─', 60);
$lines[] = "-- Total rows inserted: {$totalRows}";
$lines[] = "-- API errors:          {$errors}";
$lines[] = '-- ' . str_repeat('─', 60);
$lines[] = "-- To import:  mysql -u USER -p DB_NAME < tools/osm_data.sql";

file_put_contents(OUTPUT_FILE, implode("\n", $lines) . "\n");

progress(str_repeat('─', 50));
progress("Done — {$totalRows} rows written to " . OUTPUT_FILE);
if ($errors > 0) {
    progress("WARNING: {$errors} API errors occurred (check output for gaps)");
}
