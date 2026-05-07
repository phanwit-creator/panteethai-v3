<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/tat.php';

// Restrict to CLI or authenticated HTTP request
if (PHP_SAPI !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
    $secret   = $_GET['secret'] ?? $_SERVER['HTTP_X_CRON_SECRET'] ?? '';
    $expected = defined('CRON_SECRET') ? CRON_SECRET : '';
    if (!$expected || !hash_equals($expected, $secret)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Forbidden', 'code' => 403]);
        exit;
    }
}

set_time_limit(300);

$log      = [];
$summary  = ['provinces' => 0, 'places_upserted' => 0, 'events_upserted' => 0, 'errors' => 0];
$startAt  = microtime(true);

function sync_log(string $msg): void {
    global $log;
    $line = '[' . date('H:i:s') . '] ' . $msg;
    $log[] = $line;
    error_log('tat-sync: ' . $msg);
    if (PHP_SAPI === 'cli') {
        echo $line . PHP_EOL;
    }
}

function map_tat_category(string $catStr): string {
    $map = [
        'TEMPLE' => 'temple', 'WAT' => 'temple',
        'BEACH'  => 'beach',
        'NATURE' => 'nature', 'PARK' => 'nature',
        'MARKET' => 'market', 'SHOPPING' => 'market',
        'HOTEL'  => 'hotel', 'ACCOMMODATION' => 'hotel',
        'RESTAURANT' => 'restaurant', 'FOOD' => 'restaurant',
        'MUSEUM' => 'museum', 'HERITAGE' => 'museum',
        'WATERFALL' => 'waterfall',
        'ISLAND' => 'island',
    ];
    return $map[strtoupper($catStr)] ?? 'other';
}

try {
    $provinces = db_query("SELECT slug, name_en FROM provinces ORDER BY slug");
    $tat       = new TatApi();

    sync_log('Starting TAT sync for ' . count($provinces) . ' provinces');

    foreach ($provinces as $prov) {
        $slug = $prov['slug'];
        sync_log("Syncing province: {$slug}");
        $summary['provinces']++;

        // ---- Sync Places ----
        try {
            $response = $tat->places($slug, '', 100);
            $items    = $response['data'] ?? (is_array($response) ? $response : []);

            foreach ($items as $item) {
                $tatId   = $item['id'] ?? ($item['tatId'] ?? null);
                $nameTh  = $item['nameThai'] ?? ($item['name'] ?? '');
                $nameEn  = $item['nameEng']  ?? ($item['nameEnglish'] ?? '');
                $lat     = (float)($item['latitude']  ?? ($item['location']['lat'] ?? 0));
                $lng     = (float)($item['longitude'] ?? ($item['location']['long'] ?? 0));
                $catStr  = $item['category']['description'] ?? ($item['type'] ?? 'other');
                $address = $item['address']   ?? '';
                $phone   = $item['tel']       ?? ($item['phone'] ?? '');
                $website = $item['website']   ?? '';
                $imgUrl  = $item['thumbnailUrl'] ?? ($item['thumbnail'] ?? '');

                if (!$lat || !$lng || !$nameTh) continue;

                $category = map_tat_category($catStr);

                $pdo  = get_pdo();
                $stmt = $pdo->prepare(
                    "INSERT INTO places
                        (province_slug, name_th, name_en, category, location,
                         address, phone, website, image_url, tat_id, source, status)
                     VALUES
                        (:pslug, :name_th, :name_en, :cat,
                         ST_GeomFromText(CONCAT('POINT(', :lng, ' ', :lat, ')'), 4326),
                         :address, :phone, :website, :img, :tat_id, 'tat', 'active')
                     ON DUPLICATE KEY UPDATE
                        name_th   = VALUES(name_th),
                        name_en   = VALUES(name_en),
                        category  = VALUES(category),
                        location  = VALUES(location),
                        address   = VALUES(address),
                        phone     = VALUES(phone),
                        website   = VALUES(website),
                        image_url = VALUES(image_url)"
                );
                $stmt->execute([
                    ':pslug'   => $slug,
                    ':name_th' => $nameTh,
                    ':name_en' => $nameEn,
                    ':cat'     => $category,
                    ':lng'     => $lng,
                    ':lat'     => $lat,
                    ':address' => $address,
                    ':phone'   => $phone,
                    ':website' => $website,
                    ':img'     => $imgUrl,
                    ':tat_id'  => $tatId,
                ]);
                $summary['places_upserted']++;
            }
        } catch (Exception $e) {
            sync_log("ERROR places {$slug}: " . $e->getMessage());
            $summary['errors']++;
        }

        // ---- Sync Events ----
        try {
            $response = $tat->events($slug, date('Y-m-d'));
            $items    = $response['data'] ?? (is_array($response) ? $response : []);

            foreach ($items as $item) {
                $tatId    = $item['id'] ?? null;
                $nameTh   = $item['nameThai'] ?? ($item['name'] ?? '');
                $nameEn   = $item['nameEng']  ?? '';
                $lat      = (float)($item['latitude']  ?? 0);
                $lng      = (float)($item['longitude'] ?? 0);
                $start    = $item['startDate'] ?? null;
                $end      = $item['endDate']   ?? null;
                $desc     = $item['detail']    ?? ($item['description'] ?? '');
                $imgUrl   = $item['thumbnailUrl'] ?? '';

                if (!$nameTh) continue;

                $locationSql = ($lat && $lng)
                    ? "ST_GeomFromText(CONCAT('POINT(', ?, ' ', ?, ')'), 4326)"
                    : "ST_GeomFromText('POINT(0 0)', 4326)";

                $pdo  = get_pdo();
                $stmt = $pdo->prepare(
                    "INSERT INTO events
                        (province_slug, name_th, name_en, event_date_start, event_date_end,
                         location, description, image_url, tat_id, status)
                     VALUES
                        (?, ?, ?, ?, ?, {$locationSql}, ?, ?, ?, 'active')
                     ON DUPLICATE KEY UPDATE
                        name_th         = VALUES(name_th),
                        name_en         = VALUES(name_en),
                        event_date_start= VALUES(event_date_start),
                        event_date_end  = VALUES(event_date_end),
                        description     = VALUES(description),
                        image_url       = VALUES(image_url)"
                );

                $params = [$slug, $nameTh, $nameEn, $start, $end];
                if ($lat && $lng) $params = array_merge($params, [$lng, $lat]);
                $params = array_merge($params, [$desc, $imgUrl, $tatId]);

                $stmt->execute($params);
                $summary['events_upserted']++;
            }
        } catch (Exception $e) {
            sync_log("ERROR events {$slug}: " . $e->getMessage());
            $summary['errors']++;
        }

        // Avoid hammering TAT API
        usleep(200000); // 200ms pause between provinces
    }

    // Clean expired TAT cache
    $cleaned = TatApi::cleanExpiredCache();
    sync_log("Cleaned {$cleaned} expired cache entries");

    $elapsed = round(microtime(true) - $startAt, 1);
    sync_log("Sync complete in {$elapsed}s — " . json_encode($summary));

    if (PHP_SAPI !== 'cli') {
        echo json_encode([
            'success'   => true,
            'summary'   => $summary,
            'elapsed_s' => $elapsed,
            'log'       => $log,
            'timestamp' => date('c'),
        ]);
    }

} catch (Exception $e) {
    error_log('tat-sync.php fatal: ' . $e->getMessage());
    if (PHP_SAPI !== 'cli') {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage(), 'code' => 500]);
    } else {
        echo 'FATAL: ' . $e->getMessage() . PHP_EOL;
        exit(1);
    }
}
