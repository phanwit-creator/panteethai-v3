<?php
// includes/tat.php — TAT Data API v2 Client + Cache
// PanteeThai.com v3

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

class TatApi {

    private string $base = 'https://tatdataapi.io/api/v2';
    private string $key;

    public function __construct() {
        $this->key = TAT_API_KEY;
    }

    // Generic GET with MySQL cache
    public function get(string $endpoint, array $params = [], int $ttl = 86400): array {
        $cacheKey = md5($endpoint . json_encode($params));

        // 1. เช็ค cache ก่อน
        $cached = db_row(
            "SELECT response_json FROM tat_cache
             WHERE cache_key = ? AND expires_at > NOW()",
            [$cacheKey]
        );

        if ($cached) {
            return json_decode($cached['response_json'], true) ?? [];
        }

        // 2. Fetch จาก TAT API
        $url = $this->base . $endpoint;
        if ($params) $url .= '?' . http_build_query($params);

        $ctx = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header'  => implode("\r\n", [
                    'Authorization: Bearer ' . $this->key,
                    'Accept: application/json',
                    'Content-Type: application/json',
                ]),
                'timeout' => 10,
            ]
        ]);

        $body = @file_get_contents($url, false, $ctx);

        if ($body === false) {
            error_log("TAT API failed: $url");
            // Return stale cache ถ้ามี
            $stale = db_row(
                "SELECT response_json FROM tat_cache WHERE cache_key = ?",
                [$cacheKey]
            );
            return $stale ? json_decode($stale['response_json'], true) ?? [] : [];
        }

        // 3. บันทึก cache
        try {
            $pdo  = get_pdo();
            $stmt = $pdo->prepare(
                "INSERT INTO tat_cache
                    (cache_key, endpoint, response_json, expires_at)
                 VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))
                 ON DUPLICATE KEY UPDATE
                    response_json = VALUES(response_json),
                    expires_at    = VALUES(expires_at),
                    fetched_at    = NOW()"
            );
            $stmt->execute([$cacheKey, $endpoint, $body, $ttl]);
        } catch (Exception $e) {
            error_log('TAT cache save error: ' . $e->getMessage());
        }

        return json_decode($body, true) ?? [];
    }

    // ดึงสถานที่ท่องเที่ยวตามจังหวัด
    public function places(string $province, string $category = '', int $limit = 50): array {
        $params = [
            'province' => $province,
            'limit'    => $limit,
        ];
        if ($category) $params['category'] = $category;

        return $this->get('/places', $params, 86400); // cache 24hr
    }

    // ดึง events ตามจังหวัด
    public function events(string $province, string $startDate = ''): array {
        $params = ['province' => $province];
        if ($startDate) $params['startDate'] = $startDate;

        return $this->get('/events', $params, 3600); // cache 1hr
    }

    // ดึง routes
    public function routes(string $province): array {
        return $this->get('/routes', ['province' => $province], 604800); // cache 7 days
    }

    // ล้าง cache ที่หมดอายุ
    public static function cleanExpiredCache(): int {
        return db_execute(
            "DELETE FROM tat_cache WHERE expires_at < NOW()"
        );
    }

    // Force refresh cache key
    public function refresh(string $endpoint, array $params = []): array {
        $cacheKey = md5($endpoint . json_encode($params));
        db_execute(
            "DELETE FROM tat_cache WHERE cache_key = ?",
            [$cacheKey]
        );
        return $this->get($endpoint, $params);
    }
}