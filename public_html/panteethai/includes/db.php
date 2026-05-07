<?php
// includes/db.php — PDO Singleton + Query Helpers
// PanteeThai.com v3

require_once __DIR__ . '/config.php';

function get_pdo(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    return $pdo;
}

// Helper: query → array of rows
function db_query(string $sql, array $params = []): array {
    $stmt = get_pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Helper: query → single row
function db_row(string $sql, array $params = []): ?array {
    $stmt = get_pdo()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

// Helper: insert/update/delete → affected rows
function db_execute(string $sql, array $params = []): int {
    $stmt = get_pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

// Spatial: หา POI รัศมี X เมตร จากพิกัด
function find_nearby(float $lat, float $lng, int $radius = 5000, string $category = ''): array {
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

    $sql .= " ORDER BY dist_m LIMIT 20";
    return db_query($sql, $params);
}