<?php
// api/search.php — FULLTEXT Search
// PanteeThai.com v3

require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$q     = trim($_GET['q'] ?? '');
$limit = min((int)($_GET['limit'] ?? 10), 30);

// Validate
if (mb_strlen($q) < 2) {
    echo json_encode([
        'success' => false,
        'error'   => 'Query too short',
        'code'    => 400
    ]);
    exit;
}

try {
    $results = [];

    // ---- 1. ค้นหาจังหวัด ----
    $provinces = db_query(
        "SELECT slug, name_th, name_en,
                lat, lng, zoom_level,
                'province' AS type
         FROM provinces
         WHERE name_th LIKE :q
            OR name_en LIKE :q2
         LIMIT 3",
        [':q' => "%{$q}%", ':q2' => "%{$q}%"]
    );

    foreach ($provinces as $p) {
        $results[] = [
            'type'     => 'province',
            'slug'     => $p['slug'],
            'name_th'  => $p['name_th'],
            'name_en'  => $p['name_en'],
            'lat'      => (float)$p['lat'],
            'lng'      => (float)$p['lng'],
            'zoom'     => (int)$p['zoom_level'],
        ];
    }

    // ---- 2. ค้นหา POI ด้วย FULLTEXT ----
    $places = db_query(
        "SELECT p.id, p.province_slug, p.name_th, p.name_en,
                p.category, p.address,
                ST_X(p.location) AS lng,
                ST_Y(p.location) AS lat,
                pr.name_th AS province_name,
                MATCH(p.name_th, p.name_en) AGAINST(:q IN BOOLEAN MODE) AS score
         FROM places p
         LEFT JOIN provinces pr ON p.province_slug = pr.slug
         WHERE MATCH(p.name_th, p.name_en) AGAINST(:q2 IN BOOLEAN MODE)
           AND p.status = 'active'
         ORDER BY score DESC
         LIMIT :limit",
        [
            ':q'     => $q . '*',
            ':q2'    => $q . '*',
            ':limit' => $limit
        ]
    );

    foreach ($places as $p) {
        $results[] = [
            'type'          => 'place',
            'id'            => (int)$p['id'],
            'slug'          => $p['province_slug'],
            'name_th'       => $p['name_th'],
            'name_en'       => $p['name_en'],
            'category'      => $p['category'],
            'lat'           => (float)$p['lat'],
            'lng'           => (float)$p['lng'],
            'province_name' => $p['province_name'],
        ];
    }

    echo json_encode([
        'success'   => true,
        'data'      => $results,
        'count'     => count($results),
        'query'     => $q,
        'timestamp' => date('c')
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log('api/search.php error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error'   => 'Server error',
        'code'    => 500
    ]);
}