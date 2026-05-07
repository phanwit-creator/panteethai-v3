<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/tat.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$endpoint = $_GET['endpoint'] ?? 'places';
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

try {
    $tat    = new TatApi();
    $params = ['province' => $province, 'limit' => $limit];
    if ($category) $params['category'] = $category;

    $response = $tat->get("/{$endpoint}", $params);

    $list = $response['data'] ?? (is_array($response) ? $response : []);

    echo json_encode([
        'success'   => true,
        'data'      => $list,
        'count'     => count($list),
        'cached'    => true,
        'endpoint'  => $endpoint,
        'province'  => $province,
        'timestamp' => date('c'),
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log('api/tat-proxy.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error', 'code' => 500]);
}
