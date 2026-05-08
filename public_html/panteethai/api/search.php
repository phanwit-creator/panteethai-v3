<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$q = trim($_GET['q'] ?? '');

if (mb_strlen($q) < 2) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Query too short (min 2 chars)', 'code' => 400]);
    exit;
}

try {
    $results = [];

    // ---- 1. Province search via LIKE (provinces has no FULLTEXT index) ----
    $likeQ     = '%' . $q . '%';
    $provinces = db_query(
        "SELECT slug, name_th, name_en, lat, lng, zoom_level
         FROM provinces
         WHERE name_th LIKE ? OR name_en LIKE ?
         LIMIT 3",
        [$likeQ, $likeQ]
    );

    foreach ($provinces as $p) {
        $results[] = [
            'type'    => 'province',
            'slug'    => $p['slug'],
            'name_th' => $p['name_th'],
            'name_en' => $p['name_en'],
            'lat'     => (float)$p['lat'],
            'lng'     => (float)$p['lng'],
            'zoom'    => (int)($p['zoom_level'] ?? 11),
        ];
    }

    // ---- 2. Places FULLTEXT search (BOOLEAN MODE with prefix wildcard) ----
    $placeLimit = max(1, 10 - count($provinces));
    $ftQuery    = $q . '*';

    $pdo  = get_pdo();
    $stmt = $pdo->prepare(
        "SELECT p.id, p.province_slug, p.name_th, p.name_en,
                p.category,
                ST_X(p.location) AS lng,
                ST_Y(p.location) AS lat,
                pr.name_th AS province_name
         FROM places p
         LEFT JOIN provinces pr ON p.province_slug = pr.slug
         WHERE MATCH(p.name_th, p.name_en) AGAINST(? IN BOOLEAN MODE)
           AND p.status = 'active'
         ORDER BY MATCH(p.name_th, p.name_en) AGAINST(? IN BOOLEAN MODE) DESC
         LIMIT ?"
    );
    $stmt->bindValue(1, $ftQuery);
    $stmt->bindValue(2, $ftQuery);
    $stmt->bindValue(3, $placeLimit, PDO::PARAM_INT);
    $stmt->execute();
    $places = $stmt->fetchAll();

    foreach ($places as $p) {
        $results[] = [
            'type'          => 'place',
            'id'            => (int)$p['id'],
            'slug'          => $p['province_slug'],
            'name_th'       => $p['name_th'],
            'name_en'       => $p['name_en'] ?? '',
            'category'      => $p['category'],
            'lat'           => (float)$p['lat'],
            'lng'           => (float)$p['lng'],
            'province_name' => $p['province_name'] ?? '',
        ];
    }

    echo json_encode([
        'success'   => true,
        'data'      => $results,
        'count'     => count($results),
        'cached'    => false,
        'query'     => $q,
        'timestamp' => date('c'),
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log('api/search.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error', 'code' => 500]);
}
