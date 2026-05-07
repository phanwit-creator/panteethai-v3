<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$points  = trim($_GET['points'] ?? '');
$profile = $_GET['profile'] ?? 'car';

if (!in_array($profile, ['car', 'bike', 'foot'], true)) {
    $profile = 'car';
}

// Expect at least "lng1,lat1;lng2,lat2"
$pointArr = array_filter(explode(';', $points));
if (count($pointArr) < 2) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'points parameter requires at least 2 waypoints', 'code' => 400]);
    exit;
}

// Parse first and last points for caching columns
$parsePoint = function (string $p): array {
    $parts = explode(',', $p);
    return [(float)($parts[0] ?? 0), (float)($parts[1] ?? 0)]; // [lng, lat]
};

[$from_lng, $from_lat] = $parsePoint(reset($pointArr));
[$to_lng, $to_lat]     = $parsePoint(end($pointArr));

if (!$from_lat || !$from_lng || !$to_lat || !$to_lng) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid coordinates', 'code' => 400]);
    exit;
}

$cacheKey = md5($points . '|' . $profile);

try {
    $cached = db_row(
        "SELECT route_json FROM route_cache
         WHERE cache_key = ?
           AND TIMESTAMPDIFF(HOUR, cached_at, NOW()) < 168",
        [$cacheKey]
    );

    if ($cached) {
        echo json_encode([
            'success'   => true,
            'data'      => json_decode($cached['route_json'], true),
            'cached'    => true,
            'timestamp' => date('c'),
        ]);
        exit;
    }

    $osrmProfile = match($profile) {
        'bike' => 'cycling',
        'foot' => 'foot',
        default => 'driving',
    };

    $waypointsStr = implode(';', $pointArr);
    $osrmUrl = "https://router.project-osrm.org/route/v1/{$osrmProfile}/{$waypointsStr}"
             . "?overview=full&geometries=polyline&steps=false";

    $ctx  = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 10]]);
    $body = @file_get_contents($osrmUrl, false, $ctx);

    if ($body === false) {
        http_response_code(502);
        echo json_encode(['success' => false, 'error' => 'Routing service unavailable', 'code' => 502]);
        exit;
    }

    $osrm = json_decode($body, true);
    if (($osrm['code'] ?? '') !== 'Ok' || empty($osrm['routes'][0])) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'No route found', 'code' => 404]);
        exit;
    }

    $route = [
        'distance'    => (int)$osrm['routes'][0]['distance'],
        'duration'    => (int)$osrm['routes'][0]['duration'],
        'distance_km' => round($osrm['routes'][0]['distance'] / 1000, 1),
        'duration_min'=> (int)round($osrm['routes'][0]['duration'] / 60),
        'geometry'    => $osrm['routes'][0]['geometry'],
    ];

    db_execute(
        "INSERT INTO route_cache
            (cache_key, from_lat, from_lng, to_lat, to_lng, profile, route_json, cached_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE route_json = VALUES(route_json), cached_at = NOW()",
        [$cacheKey, $from_lat, $from_lng, $to_lat, $to_lng, $profile, json_encode($route)]
    );

    echo json_encode([
        'success'   => true,
        'data'      => $route,
        'cached'    => false,
        'timestamp' => date('c'),
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log('api/route.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error', 'code' => 500]);
}
