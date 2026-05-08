<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$endpoint = trim($_GET['endpoint'] ?? '');
$province = trim($_GET['province'] ?? '');
$category = trim($_GET['category'] ?? '');
$limit    = min((int)($_GET['limit'] ?? 50), 200);

$allowed = ['places', 'events', 'routes'];
if (!in_array($endpoint, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid endpoint', 'code' => 400]);
    exit;
}

if (!$province || !preg_match('/^[a-z0-9-]+$/', $province)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'province parameter required', 'code' => 400]);
    exit;
}

// Reconstruct the exact cache key that tat-sync.php would have stored.
// Must mirror the params each TatApi method passes to get():
//   places() → get('/places', ['province'=>slug, 'limit'=>100])
//   events() → get('/events', ['province'=>slug])
//   routes() → get('/routes', ['province'=>slug])
$keyParams = match($endpoint) {
    'places' => ['province' => $province, 'limit' => 100],
    'events' => ['province' => $province],
    'routes' => ['province' => $province],
};
$cacheKey = md5("/{$endpoint}" . json_encode($keyParams));

try {
    $row = db_row(
        "SELECT response_json, expires_at FROM tat_cache WHERE cache_key = ?",
        [$cacheKey]
    );

    if (!$row) {
        echo json_encode([
            'success'   => true,
            'data'      => [],
            'count'     => 0,
            'cached'    => false,
            'endpoint'  => $endpoint,
            'province'  => $province,
            'timestamp' => date('c'),
        ]);
        exit;
    }

    $response = json_decode($row['response_json'], true) ?? [];
    $list     = $response['data'] ?? (is_array($response) ? array_values($response) : []);
    if (!is_array($list)) $list = [];

    // Server-side category filter for places
    if ($endpoint === 'places' && $category !== '') {
        $list = array_values(array_filter($list, function ($item) use ($category) {
            $itemCat = $item['category']['description'] ?? ($item['type'] ?? '');
            return strcasecmp($itemCat, $category) === 0;
        }));
    }

    if ($limit < count($list)) {
        $list = array_slice($list, 0, $limit);
    }

    $fresh = ($row['expires_at'] > date('Y-m-d H:i:s'));

    echo json_encode([
        'success'   => true,
        'data'      => $list,
        'count'     => count($list),
        'cached'    => true,
        'fresh'     => $fresh,
        'endpoint'  => $endpoint,
        'province'  => $province,
        'timestamp' => date('c'),
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log('api/tat-proxy.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error', 'code' => 500]);
}
