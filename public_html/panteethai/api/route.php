<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$from_lat = (float)($_GET['from_lat'] ?? 0);
$from_lng = (float)($_GET['from_lng'] ?? 0);
$to_lat   = (float)($_GET['to_lat']   ?? 0);
$to_lng   = (float)($_GET['to_lng']   ?? 0);
$profile  = $_GET['profile'] ?? 'car';

if (!in_array($profile, ['car', 'bike', 'foot'], true)) {
    $profile = 'car';
}

if (!$from_lat || !$from_lng || !$to_lat || !$to_lng) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'from_lat, from_lng, to_lat, to_lng are required', 'code' => 400]);
    exit;
}

// Round to 5 decimal places (~1 m precision) for better cache hit rate
$from_lat = round($from_lat, 5);
$from_lng = round($from_lng, 5);
$to_lat   = round($to_lat,   5);
$to_lng   = round($to_lng,   5);

$cacheKey = md5("{$from_lat},{$from_lng}|{$to_lat},{$to_lng}|{$profile}");

try {
    $cached = db_row(
        "SELECT route_json FROM route_cache
         WHERE cache_key = ? AND TIMESTAMPDIFF(HOUR, cached_at, NOW()) < 24",
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

    // OSRM demo only supports driving; always fetch driving route
    $osrmUrl = "https://router.project-osrm.org/route/v1/driving"
             . "/{$from_lng},{$from_lat};{$to_lng},{$to_lat}"
             . "?overview=full&geometries=geojson&steps=false";

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

    $r          = $osrm['routes'][0];
    $distance_m = $r['distance'];
    $duration_s = $r['duration']; // driving default

    // Override duration for non-car profiles
    if ($profile === 'bike') {
        // cycling avg 15 km/h
        $duration_s = ($distance_m / 1000) / 15 * 3600;
    } elseif ($profile === 'foot') {
        // walking avg 5 km/h
        $duration_s = ($distance_m / 1000) / 5 * 3600;
    }
    $duration_min = (int) round($duration_s / 60);

    $route = [
        'distance_m'   => (int)$distance_m,
        'distance_km'  => round($distance_m / 1000, 1),
        'duration_s'   => (int)$duration_s,
        'duration_min' => $duration_min,
        'geometry'     => $r['geometry'],   // GeoJSON LineString
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
