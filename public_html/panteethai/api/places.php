<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$type     = $_GET['type']     ?? 'places';
$province = trim($_GET['province'] ?? '');
$category = trim($_GET['category'] ?? '');
$bbox     = trim($_GET['bbox']     ?? '');
$limit    = min((int)($_GET['limit'] ?? 200), 500);

$validCategories = ['temple','beach','nature','market','hotel','restaurant','museum','waterfall','island','other'];

// Validate inputs
if ($province && !preg_match('/^[a-z0-9-]+$/', $province)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid province slug', 'code' => 400]);
    exit;
}

if ($category && !in_array($category, $validCategories, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid category', 'code' => 400]);
    exit;
}

try {
    // ---- Province list endpoint ----
    if ($type === 'provinces') {
        $rows = db_query(
            "SELECT slug, name_th, name_en, lat, lng, zoom_level, image_url
             FROM provinces
             ORDER BY name_en"
        );
        echo json_encode([
            'success'   => true,
            'data'      => $rows,
            'count'     => count($rows),
            'cached'    => false,
            'timestamp' => date('c'),
        ]);
        exit;
    }

    // ---- POI endpoint — returns GeoJSON FeatureCollection ----
    $sql    = "SELECT id, province_slug, name_th, name_en, category,
                      ST_X(location) AS lng,
                      ST_Y(location) AS lat,
                      address, phone, website, price_thb, sha_certified
               FROM places
               WHERE status = 'active'";
    $params = [];

    if ($province) {
        $sql .= " AND province_slug = :province";
        $params[':province'] = $province;
    }

    if ($category) {
        $sql .= " AND category = :category";
        $params[':category'] = $category;
    }

    // bbox=minLng,minLat,maxLng,maxLat
    if ($bbox) {
        $b = array_map('floatval', explode(',', $bbox));
        if (count($b) === 4) {
            $sql .= " AND ST_X(location) BETWEEN :minLng AND :maxLng
                      AND ST_Y(location) BETWEEN :minLat AND :maxLat";
            $params[':minLng'] = $b[0];
            $params[':minLat'] = $b[1];
            $params[':maxLng'] = $b[2];
            $params[':maxLat'] = $b[3];
        }
    }

    $sql .= " ORDER BY name_th LIMIT :limit";

    $pdo  = get_pdo();
    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    // Build GeoJSON FeatureCollection
    $features = [];
    foreach ($rows as $row) {
        $features[] = [
            'type'     => 'Feature',
            'geometry' => [
                'type'        => 'Point',
                'coordinates' => [(float)$row['lng'], (float)$row['lat']],
            ],
            'properties' => [
                'id'            => (int)$row['id'],
                'province_slug' => $row['province_slug'],
                'name_th'       => $row['name_th'],
                'name_en'       => $row['name_en'] ?? '',
                'category'      => $row['category'],
                'address'       => $row['address'] ?? '',
                'phone'         => $row['phone']   ?? '',
                'website'       => $row['website'] ?? '',
                'price_thb'     => (int)$row['price_thb'],
                'sha_certified' => (bool)$row['sha_certified'],
            ],
        ];
    }

    $geojson = [
        'type'     => 'FeatureCollection',
        'features' => $features,
    ];

    echo json_encode([
        'success'   => true,
        'data'      => $geojson,
        'count'     => count($features),
        'cached'    => false,
        'timestamp' => date('c'),
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log('api/places.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error', 'code' => 500]);
}
