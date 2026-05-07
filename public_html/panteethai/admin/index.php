<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

require_admin();

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    admin_logout();
}

// Stats
try {
    $stats = [
        'provinces' => (int)db_row("SELECT COUNT(*) AS c FROM provinces")['c'],
        'places'    => (int)db_row("SELECT COUNT(*) AS c FROM places")['c'],
        'articles'  => (int)db_row("SELECT COUNT(*) AS c FROM articles WHERE status = 'published'")['c'],
        'events'    => (int)db_row("SELECT COUNT(*) AS c FROM events WHERE status = 'active'")['c'],
        'cache'     => (int)db_row("SELECT COUNT(*) AS c FROM tat_cache")['c'],
        'expired'   => (int)db_row("SELECT COUNT(*) AS c FROM tat_cache WHERE expires_at < NOW()")['c'],
    ];

    $recentPlaces = db_query(
        "SELECT name_th, province_slug, source, created_at
         FROM places
         ORDER BY id DESC
         LIMIT 5"
    );

    $recentArticles = db_query(
        "SELECT title_th, province_slug, published_at, status
         FROM articles
         ORDER BY id DESC
         LIMIT 5"
    );
} catch (Exception $e) {
    error_log('admin/index.php: ' . $e->getMessage());
    $stats = [];
    $recentPlaces = $recentArticles = [];
}

$adminUser = $_SESSION['admin_user'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — PanteeThai Admin</title>
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
                <a href="/admin/" class="active">📊 Dashboard</a>
                <a href="/admin/places.php">📍 สถานที่ (POI)</a>
                <a href="/admin/articles.php">📝 บทความ</a>
                <a href="/admin/tat-status.php">🔄 TAT Sync</a>
            </nav>
            <div class="px-5 mt-auto">
                <p class="text-gray-500 text-xs mb-2">ล็อกอินในนาม <?= htmlspecialchars($adminUser) ?></p>
                <a href="/admin/?action=logout"
                   class="text-xs text-red-400 hover:text-red-300">ออกจากระบบ</a>
            </div>
        </aside>

        <!-- Content -->
        <main class="flex-1 p-8 overflow-y-auto">

            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
                <p class="text-sm text-gray-400 mt-1">
                    <?= date('j F Y, H:i') ?>
                </p>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
                <?php
                $statItems = [
                    ['value' => $stats['provinces'] ?? 0, 'label' => 'จังหวัด', 'color' => 'text-blue-600'],
                    ['value' => $stats['places']    ?? 0, 'label' => 'สถานที่', 'color' => 'text-green-600'],
                    ['value' => $stats['articles']  ?? 0, 'label' => 'บทความ',  'color' => 'text-purple-600'],
                    ['value' => $stats['events']    ?? 0, 'label' => 'กิจกรรม', 'color' => 'text-orange-600'],
                    ['value' => $stats['cache']     ?? 0, 'label' => 'TAT Cache','color' => 'text-teal-600'],
                    ['value' => $stats['expired']   ?? 0, 'label' => 'Cache หมดอายุ', 'color' => 'text-red-600'],
                ];
                foreach ($statItems as $s):
                ?>
                <div class="admin-card admin-stat">
                    <div class="value <?= $s['color'] ?>"><?= number_format($s['value']) ?></div>
                    <div class="label"><?= $s['label'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Recent Places -->
                <div class="admin-card">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-semibold text-gray-700">สถานที่ล่าสุด</h2>
                        <a href="/admin/places.php" class="text-xs text-green-600 hover:underline">ดูทั้งหมด →</a>
                    </div>
                    <?php if (empty($recentPlaces)): ?>
                    <p class="text-sm text-gray-400 py-4 text-center">ยังไม่มีข้อมูล</p>
                    <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ชื่อ</th>
                                <th>จังหวัด</th>
                                <th>แหล่ง</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPlaces as $pl): ?>
                            <tr>
                                <td class="max-w-xs truncate">
                                    <?= htmlspecialchars($pl['name_th']) ?>
                                </td>
                                <td><?= htmlspecialchars($pl['province_slug']) ?></td>
                                <td>
                                    <span class="badge <?= $pl['source'] === 'tat' ? 'badge-blue' : 'badge-gray' ?>">
                                        <?= htmlspecialchars($pl['source']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>

                <!-- Recent Articles -->
                <div class="admin-card">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-semibold text-gray-700">บทความล่าสุด</h2>
                        <a href="/admin/articles.php" class="text-xs text-green-600 hover:underline">ดูทั้งหมด →</a>
                    </div>
                    <?php if (empty($recentArticles)): ?>
                    <p class="text-sm text-gray-400 py-4 text-center">ยังไม่มีบทความ</p>
                    <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>หัวข้อ</th>
                                <th>จังหวัด</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentArticles as $ar): ?>
                            <tr>
                                <td class="max-w-xs truncate">
                                    <?= htmlspecialchars($ar['title_th']) ?>
                                </td>
                                <td><?= htmlspecialchars($ar['province_slug'] ?? '—') ?></td>
                                <td>
                                    <span class="badge <?= $ar['status'] === 'published' ? 'badge-green' : 'badge-yellow' ?>">
                                        <?= $ar['status'] === 'published' ? 'เผยแพร่' : 'ร่าง' ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Quick Actions -->
            <div class="mt-6 admin-card">
                <h2 class="font-semibold text-gray-700 mb-4">Quick Actions</h2>
                <div class="flex flex-wrap gap-3">
                    <a href="/admin/places.php?action=add"
                       class="px-4 py-2 bg-green-500 text-white rounded-lg text-sm hover:bg-green-600">
                        + เพิ่มสถานที่
                    </a>
                    <a href="/admin/articles.php?action=add"
                       class="px-4 py-2 bg-blue-500 text-white rounded-lg text-sm hover:bg-blue-600">
                        + เขียนบทความ
                    </a>
                    <a href="/admin/tat-status.php"
                       class="px-4 py-2 bg-orange-500 text-white rounded-lg text-sm hover:bg-orange-600">
                        🔄 TAT Sync Status
                    </a>
                    <a href="/"
                       target="_blank"
                       class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm hover:bg-gray-300">
                        ดูเว็บไซต์ →
                    </a>
                </div>
            </div>

        </main>
    </div>

</body>
</html>
