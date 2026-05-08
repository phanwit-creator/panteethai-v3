<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$lat      = (float)($_GET['lat']      ?? 0);
$lng      = (float)($_GET['lng']      ?? 0);
$radius   = min((int)($_GET['radius'] ?? 5000), 50000); // max 50 km
$category = trim($_GET['category']    ?? '');
$limit    = min((int)($_GET['limit']  ?? 20), 50);

$validCategories = ['temple','beach','nature','market','hotel','restaurant','museum','waterfall','island','other'];

if (!$lat || !$lng) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'lat and lng are required', 'code' => 400]);
    exit;
}

if ($category && !in_array($category, $validCategories, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid category', 'code' => 400]);
    exit;
}

try {
    $sql = "SELECT id, name_th, name_en, category,
                   ST_X(location) AS lng,
                   ST_Y(location) AS lat,
                   ST_Distance_Sphere(location, POINT(:lng, :lat)) AS dist_m
            FROM places
            WHERE ST_Distance_Sphere(location, POINT(:lng2, :lat2)) < :radius
              AND status = 'active'";

    $params = [
        ':lng'    => $lng,
        ':lat'    => $lat,
        ':lng2'   => $lng,
        ':lat2'   => $lat,
        ':radius' => $radius,
    ];

    if ($category) {
        $sql .= " AND category = :category";
        $params[':category'] = $category;
    }

    $sql .= " ORDER BY dist_m LIMIT :limit";

    $pdo  = get_pdo();
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $result = [];
    foreach ($rows as $row) {
        $result[] = [
            'id'       => (int)$row['id'],
            'name_th'  => $row['name_th'],
            'name_en'  => $row['name_en'] ?? '',
            'category' => $row['category'],
            'lat'      => (float)$row['lat'],
            'lng'      => (float)$row['lng'],
            'dist_m'   => (int)$row['dist_m'],
            'dist_km'  => round($row['dist_m'] / 1000, 1),
        ];
    }

    echo json_encode([
        'success'   => true,
        'data'      => $result,
        'count'     => count($result),
        'center'    => ['lat' => $lat, 'lng' => $lng],
        'radius_m'  => $radius,
        'timestamp' => date('c'),
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log('api/nearby.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error', 'code' => 500]);
}
