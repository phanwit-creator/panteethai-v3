<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/tat.php';

require_admin();

$msg   = '';
$error = '';

// ---- Handle POST actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'CSRF token invalid';
    } else {
        $postAction = $_POST['_action'] ?? '';

        if ($postAction === 'clean') {
            $cleaned = TatApi::cleanExpiredCache();
            $msg     = "ลบ cache ที่หมดอายุแล้ว {$cleaned} รายการ";

        } elseif ($postAction === 'refresh_key') {
            $key = $_POST['cache_key'] ?? '';
            if ($key) {
                db_execute("DELETE FROM tat_cache WHERE cache_key = ?", [$key]);
                $msg = 'ลบ cache key นี้แล้ว จะถูก fetch ใหม่เมื่อมีการเรียกใช้';
            }

        } elseif ($postAction === 'sync_now') {
            // Trigger sync in the background by calling tat-sync.php internally
            $secret = defined('CRON_SECRET') ? CRON_SECRET : '';
            if (!$secret) {
                $error = 'กรุณาตั้ง CRON_SECRET ใน .env ก่อน';
            } else {
                $url = APP_URL . '/api/tat-sync.php?secret=' . urlencode($secret);
                $ctx = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 60]]);
                $res = @file_get_contents($url, false, $ctx);
                if ($res !== false) {
                    $data = json_decode($res, true);
                    if (!empty($data['success'])) {
                        $s   = $data['summary'] ?? [];
                        $msg = "Sync สำเร็จ — "
                             . "provinces: {$s['provinces']}, "
                             . "places: {$s['places_upserted']}, "
                             . "events: {$s['events_upserted']}, "
                             . "errors: {$s['errors']}";
                    } else {
                        $error = $data['error'] ?? 'Sync ล้มเหลว';
                    }
                } else {
                    $error = 'ไม่สามารถเรียก tat-sync.php ได้';
                }
            }
        }
    }
}

// ---- Load cache data ----
$cacheEntries = [];
$cacheStats   = ['total' => 0, 'active' => 0, 'expired' => 0];
$routeStats   = ['total' => 0];

try {
    $cacheEntries = db_query(
        "SELECT cache_key, endpoint, fetched_at, expires_at,
                CHAR_LENGTH(response_json) AS size_bytes,
                expires_at < NOW() AS is_expired
         FROM tat_cache
         ORDER BY fetched_at DESC
         LIMIT 100"
    );

    $cacheStats['total']   = (int)db_row("SELECT COUNT(*) AS c FROM tat_cache")['c'];
    $cacheStats['expired'] = (int)db_row("SELECT COUNT(*) AS c FROM tat_cache WHERE expires_at < NOW()")['c'];
    $cacheStats['active']  = $cacheStats['total'] - $cacheStats['expired'];
    $routeStats['total']   = (int)db_row("SELECT COUNT(*) AS c FROM route_cache")['c'];
} catch (Exception $e) {
    error_log('admin/tat-status.php: ' . $e->getMessage());
    $error = 'ไม่สามารถโหลดข้อมูลได้: ' . $e->getMessage();
}

$hasCronSecret = defined('CRON_SECRET') && CRON_SECRET;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TAT Sync Status — PanteeThai Admin</title>
    <meta name="robots" content="noindex,nofollow">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="bg-gray-100 min-h-screen">

<div class="flex min-h-screen">

    <!-- Sidebar -->
    <aside class="admin-sidebar flex flex-col py-6">
        <div class="px-5 mb-8">
            <p class="text-white font-bold text-lg">PanteeThai</p>
            <p class="text-gray-400 text-xs mt-0.5">Admin Panel</p>
        </div>
        <nav class="flex-1">
            <a href="/admin/">📊 Dashboard</a>
            <a href="/admin/places.php">📍 สถานที่ (POI)</a>
            <a href="/admin/articles.php">📝 บทความ</a>
            <a href="/admin/tat-status.php" class="active">🔄 TAT Sync</a>
        </nav>
        <div class="px-5 mt-auto">
            <a href="/admin/?action=logout" class="text-xs text-red-400 hover:text-red-300">ออกจากระบบ</a>
        </div>
    </aside>

    <main class="flex-1 p-8 overflow-y-auto">

        <h1 class="text-2xl font-bold text-gray-800 mb-6">TAT Sync Status</h1>

        <?php if ($msg): ?>
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 mb-5 text-sm">
            <?= htmlspecialchars($msg) ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-5 text-sm">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- Stats Row -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="admin-card admin-stat">
                <div class="value text-gray-700"><?= number_format($cacheStats['total']) ?></div>
                <div class="label">TAT Cache ทั้งหมด</div>
            </div>
            <div class="admin-card admin-stat">
                <div class="value text-green-600"><?= number_format($cacheStats['active']) ?></div>
                <div class="label">Cache ยังใช้งานได้</div>
            </div>
            <div class="admin-card admin-stat">
                <div class="value text-red-600"><?= number_format($cacheStats['expired']) ?></div>
                <div class="label">Cache หมดอายุ</div>
            </div>
            <div class="admin-card admin-stat">
                <div class="value text-blue-600"><?= number_format($routeStats['total']) ?></div>
                <div class="label">Route Cache</div>
            </div>
        </div>

        <!-- Actions -->
        <div class="admin-card mb-6">
            <h2 class="font-semibold text-gray-700 mb-4">Actions</h2>
            <div class="flex flex-wrap gap-3">

                <!-- Sync Now -->
                <form method="POST">
                    <input type="hidden" name="_csrf"   value="<?= csrf_token() ?>">
                    <input type="hidden" name="_action" value="sync_now">
                    <?php if (!$hasCronSecret): ?>
                    <button type="submit" disabled
                            title="ตั้ง CRON_SECRET ใน .env ก่อน"
                            class="px-4 py-2 bg-gray-300 text-gray-500 rounded-lg text-sm cursor-not-allowed">
                        🔄 Sync TAT ทันที (ต้องตั้ง CRON_SECRET)
                    </button>
                    <?php else: ?>
                    <button type="submit"
                            onclick="return confirm('เริ่ม TAT sync สำหรับทุกจังหวัด? อาจใช้เวลาหลายนาที')"
                            class="px-4 py-2 bg-orange-500 text-white rounded-lg text-sm hover:bg-orange-600">
                        🔄 Sync TAT ทันที
                    </button>
                    <?php endif; ?>
                </form>

                <!-- Clean expired -->
                <form method="POST">
                    <input type="hidden" name="_csrf"   value="<?= csrf_token() ?>">
                    <input type="hidden" name="_action" value="clean">
                    <button type="submit"
                            onclick="return confirm('ลบ cache ที่หมดอายุทั้งหมด?')"
                            class="px-4 py-2 bg-red-500 text-white rounded-lg text-sm hover:bg-red-600">
                        🗑️ ลบ Cache หมดอายุ (<?= $cacheStats['expired'] ?>)
                    </button>
                </form>

            </div>

            <?php if (!$hasCronSecret): ?>
            <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-xs text-yellow-800">
                เพิ่ม <code class="font-mono bg-yellow-100 px-1 rounded">CRON_SECRET=your_secret_here</code>
                ใน .env และ config.php เพื่อเปิดใช้งาน sync ผ่านหน้า admin
            </div>
            <?php endif; ?>

            <div class="mt-4 p-3 bg-gray-50 rounded-lg text-xs text-gray-600">
                <strong>CRON command (รันทุกวัน 02:00):</strong><br>
                <code class="font-mono">0 2 * * * php <?= htmlspecialchars(dirname(__DIR__)) ?>/api/tat-sync.php</code>
            </div>
        </div>

        <!-- Cache Table -->
        <div class="admin-card">
            <h2 class="font-semibold text-gray-700 mb-4">TAT Cache Entries (แสดง 100 รายการล่าสุด)</h2>

            <?php if (empty($cacheEntries)): ?>
            <p class="text-center text-gray-400 py-8">ยังไม่มี cache</p>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Endpoint</th>
                            <th>Cache Key</th>
                            <th>ขนาด</th>
                            <th>Fetch เมื่อ</th>
                            <th>หมดอายุ</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cacheEntries as $entry): ?>
                        <tr>
                            <td class="font-mono text-xs"><?= htmlspecialchars($entry['endpoint'] ?? '—') ?></td>
                            <td class="font-mono text-xs text-gray-400">
                                <?= substr(htmlspecialchars($entry['cache_key']), 0, 12) ?>...
                            </td>
                            <td class="text-xs text-gray-500">
                                <?= number_format((int)$entry['size_bytes'] / 1024, 1) ?> KB
                            </td>
                            <td class="text-xs text-gray-500">
                                <?= $entry['fetched_at'] ? date('j M H:i', strtotime($entry['fetched_at'])) : '—' ?>
                            </td>
                            <td class="text-xs <?= $entry['is_expired'] ? 'text-red-500' : 'text-gray-500' ?>">
                                <?= $entry['expires_at'] ? date('j M H:i', strtotime($entry['expires_at'])) : '—' ?>
                            </td>
                            <td>
                                <span class="badge <?= $entry['is_expired'] ? 'badge-red' : 'badge-green' ?>">
                                    <?= $entry['is_expired'] ? 'หมดอายุ' : 'ใช้ได้' ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="_csrf"     value="<?= csrf_token() ?>">
                                    <input type="hidden" name="_action"   value="refresh_key">
                                    <input type="hidden" name="cache_key" value="<?= htmlspecialchars($entry['cache_key']) ?>">
                                    <button type="submit"
                                            class="text-xs text-orange-500 hover:underline">
                                        ลบ
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

    </main>
</div>

</body>
</html>
