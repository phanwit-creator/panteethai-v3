<?php
// api/places.php — GET POI GeoJSON / Province list
// PanteeThai.com v3

require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// รับ params
$type     = $_GET['type']     ?? 'places';
$province = $_GET['province'] ?? '';
$category = $_GET['category'] ?? '';
$bbox     = $_GET['bbox']     ?? '';
$limit    = min((int)($_GET['limit'] ?? 50), 200);

try {
    // ---- ดึงรายการจังหวัด ----
    if ($type === 'provinces') {
        $rows = db_query(
            "SELECT slug, name_th, name_en, lat, lng, zoom_level, image_url
             FROM provinces
             ORDER BY name_en"
        );
        echo json_encode([
            'success' => true,
            'data'    => $rows,
            'count'   => count($rows),
            'cached'  => false,
            'timestamp' => date('c')
        ]);
        exit;
    }

    // ---- ดึง POI ----
    $sql    = "SELECT id, province_slug, name_th, name_en, category,
                      ST_X(location) AS lng,
                      ST_Y(location) AS lat,
                      address, phone, website, price_thb,
                      sha_certified, tat_id
               FROM places
               WHERE status = 'active'";
    $params = [];

    // Filter: จังหวัด
    if ($province) {
        $sql .= " AND province_slug = :province";
        $params[':province'] = $province;
    }

    // Filter: category
    if ($category) {
        $sql .= " AND category = :category";
        $params[':category'] = $category;
    }

    // Filter: bounding box (bbox=minLng,minLat,maxLng,maxLat)
    if ($bbox) {
        $b = explode(',', $bbox);
        if (count($b) === 4) {
            $sql .= " AND ST_Within(location,
                        ST_GeomFromText('POLYGON((:x1 :y1,:x2 :y1,:x2 :y2,:x1 :y2,:x1 :y1))', 4326))";
            $params[':x1'] = (float)$b[0];
            $params[':y1'] = (float)$b[1];
            $params[':x2'] = (float)$b[2];
            $params[':y2'] = (float)$b[3];
        }
    }

    $sql .= " ORDER BY name_th LIMIT :limit";
    $params[':limit'] = $limit;

    $pdo  = get_pdo();
    $stmt = $pdo->prepare($sql);

    // Bind limit แยกเพราะต้อง bindValue เป็น INT
    foreach ($params as $key => $val) {
        if ($key === ':limit') {
            $stmt->bindValue($key, $val, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $val);
        }
    }
    $stmt->execute();
    $rows = $stmt->fetchAll();

    echo json_encode([
        'success'   => true,
        'data'      => $rows,
        'count'     => count($rows),
        'cached'    => false,
        'timestamp' => date('c')
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log('api/places.php error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error'   => 'Server error',
        'code'    => 500
    ]);
}