<?php
// api/nearby.php — Spatial Radius Search
// PanteeThai.com v3

require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// รับ params
$lat      = (float)($_GET['lat']      ?? 0);
$lng      = (float)($_GET['lng']      ?? 0);
$radius   = min((int)($_GET['radius'] ?? 5000), 50000); // max 50km
$category = $_GET['category'] ?? '';
$limit    = min((int)($_GET['limit']  ?? 20), 50);

// Validate
if (!$lat || !$lng) {
    echo json_encode([
        'success' => false,
        'error'   => 'lat and lng are required',
        'code'    => 400
    ]);
    exit;
}

try {
    $rows = find_nearby($lat, $lng, $radius, $category);

    // เพิ่ม province name
    $result = [];
    foreach ($rows as $row) {
        $result[] = [
            'id'        => (int)$row['id'],
            'name_th'   => $row['name_th'],
            'name_en'   => $row['name_en'],
            'category'  => $row['category'],
            'lat'       => (float)$row['lat'],
            'lng'       => (float)$row['lng'],
            'dist_m'    => (int)$row['dist_m'],
            'dist_km'   => round($row['dist_m'] / 1000, 1),
        ];
    }

    echo json_encode([
        'success'   => true,
        'data'      => $result,
        'count'     => count($result),
        'center'    => ['lat' => $lat, 'lng' => $lng],
        'radius_m'  => $radius,
        'timestamp' => date('c')
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log('api/nearby.php error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error'   => 'Server error',
        'code'    => 500
    ]);
}