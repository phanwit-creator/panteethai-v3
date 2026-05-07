<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

require_admin();

$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);
$msg    = '';
$error  = '';

$provinces = db_query("SELECT slug, name_th FROM provinces ORDER BY name_th");

// ---- Handle POST (create / update / delete) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'CSRF token invalid. กรุณา refresh และลองใหม่';
    } else {
        $postAction = $_POST['_action'] ?? '';

        if ($postAction === 'delete') {
            $delId = (int)($_POST['id'] ?? 0);
            if ($delId > 0) {
                db_execute("DELETE FROM places WHERE id = ?", [$delId]);
                $msg = 'ลบสถานที่เรียบร้อยแล้ว';
                $action = 'list';
            }

        } elseif (in_array($postAction, ['create', 'update'], true)) {
            $nameTh   = trim($_POST['name_th']        ?? '');
            $nameEn   = trim($_POST['name_en']        ?? '');
            $provSlug = trim($_POST['province_slug']  ?? '');
            $category = trim($_POST['category']       ?? 'other');
            $lat      = (float)($_POST['lat']         ?? 0);
            $lng      = (float)($_POST['lng']         ?? 0);
            $address  = trim($_POST['address']        ?? '');
            $phone    = trim($_POST['phone']          ?? '');
            $website  = trim($_POST['website']        ?? '');
            $desc     = trim($_POST['description']    ?? '');
            $price    = (int)($_POST['price_thb']     ?? 0);
            $sha      = isset($_POST['sha_certified']) ? 1 : 0;
            $status   = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

            $validCats = ['temple','beach','nature','market','hotel','restaurant','museum','waterfall','island','other'];

            if (!$nameTh) {
                $error = 'กรุณาระบุชื่อสถานที่';
            } elseif (!$provSlug) {
                $error = 'กรุณาเลือกจังหวัด';
            } elseif (!$lat || !$lng) {
                $error = 'กรุณาเลือกตำแหน่งบนแผนที่';
            } elseif (!in_array($category, $validCats, true)) {
                $error = 'ประเภทสถานที่ไม่ถูกต้อง';
            } else {
                try {
                    if ($postAction === 'create') {
                        $pdo  = get_pdo();
                        $stmt = $pdo->prepare(
                            "INSERT INTO places
                                (province_slug, name_th, name_en, category,
                                 location, address, phone, website,
                                 description, price_thb, sha_certified, source, status)
                             VALUES
                                (?, ?, ?, ?,
                                 ST_GeomFromText(CONCAT('POINT(', ?, ' ', ?, ')'), 4326),
                                 ?, ?, ?, ?, ?, ?, 'manual', ?)"
                        );
                        $stmt->execute([
                            $provSlug, $nameTh, $nameEn, $category,
                            $lng, $lat,
                            $address, $phone, $website, $desc, $price, $sha, $status
                        ]);
                        $msg    = 'เพิ่มสถานที่เรียบร้อยแล้ว';
                        $action = 'list';
                    } else {
                        $pdo  = get_pdo();
                        $stmt = $pdo->prepare(
                            "UPDATE places SET
                                province_slug = ?, name_th = ?, name_en = ?, category = ?,
                                location = ST_GeomFromText(CONCAT('POINT(', ?, ' ', ?, ')'), 4326),
                                address = ?, phone = ?, website = ?,
                                description = ?, price_thb = ?, sha_certified = ?, status = ?
                             WHERE id = ?"
                        );
                        $stmt->execute([
                            $provSlug, $nameTh, $nameEn, $category,
                            $lng, $lat,
                            $address, $phone, $website, $desc, $price, $sha, $status,
                            $editId
                        ]);
                        $msg    = 'อัปเดตสถานที่เรียบร้อยแล้ว';
                        $action = 'list';
                    }
                } catch (Exception $e) {
                    error_log('admin/places.php save: ' . $e->getMessage());
                    $error = 'เกิดข้อผิดพลาด กรุณาลองใหม่';
                }
            }
        }
    }
}

// ---- List data ----
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;
$search  = trim($_GET['q'] ?? '');
$filterProv = $_GET['province'] ?? '';

$where  = [];
$params = [];

if ($search) {
    $where[] = '(name_th LIKE :q OR name_en LIKE :q2)';
    $params[':q'] = $params[':q2'] = "%{$search}%";
}
if ($filterProv) {
    $where[]            = 'province_slug = :prov';
    $params[':prov']    = $filterProv;
}

$whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total      = 0;
$places     = [];
$editPlace  = null;

try {
    $total = (int)db_row("SELECT COUNT(*) AS c FROM places {$whereStr}", $params)['c'];
    $places = db_query(
        "SELECT id, name_th, name_en, category, province_slug,
                ST_X(location) AS lng, ST_Y(location) AS lat,
                status, source
         FROM places {$whereStr}
         ORDER BY id DESC
         LIMIT :limit OFFSET :offset",
        array_merge($params, [':limit' => $perPage, ':offset' => $offset])
    );

    if ($action === 'edit' && $editId) {
        $editPlace = db_row(
            "SELECT *, ST_X(location) AS lng, ST_Y(location) AS lat
             FROM places WHERE id = ?",
            [$editId]
        );
        if (!$editPlace) { $action = 'list'; }
    }
} catch (Exception $e) {
    error_log('admin/places.php list: ' . $e->getMessage());
}

$totalPages = (int)ceil($total / $perPage);
$showForm   = in_array($action, ['add', 'edit'], true);
$formTitle  = $action === 'add' ? 'เพิ่มสถานที่ใหม่' : 'แก้ไขสถานที่';
$categories = ['temple','beach','nature','market','hotel','restaurant','museum','waterfall','island','other'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สถานที่ (POI) — PanteeThai Admin</title>
    <meta name="robots" content="noindex,nofollow">
    <script src="https://cdn.tailwindcss.com"></script>
    <?php if ($showForm): ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <?php endif; ?>
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
            <a href="/admin/places.php" class="active">📍 สถานที่ (POI)</a>
            <a href="/admin/articles.php">📝 บทความ</a>
            <a href="/admin/tat-status.php">🔄 TAT Sync</a>
        </nav>
        <div class="px-5 mt-auto">
            <a href="/admin/?action=logout" class="text-xs text-red-400 hover:text-red-300">ออกจากระบบ</a>
        </div>
    </aside>

    <main class="flex-1 p-8 overflow-y-auto">

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800">
                <?= $showForm ? $formTitle : 'จัดการสถานที่ (POI)' ?>
            </h1>
            <?php if (!$showForm): ?>
            <a href="/admin/places.php?action=add"
               class="px-4 py-2 bg-green-500 text-white rounded-lg text-sm hover:bg-green-600">
                + เพิ่มสถานที่
            </a>
            <?php else: ?>
            <a href="/admin/places.php"
               class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm hover:bg-gray-300">
                ← กลับรายการ
            </a>
            <?php endif; ?>
        </div>

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

        <?php if ($showForm): ?>
        <!-- ---- Add / Edit Form ---- -->
        <div class="admin-card">
            <form method="POST" action="/admin/places.php<?= $action === 'edit' ? "?action=edit&id={$editId}" : "?action=add" ?>">
                <input type="hidden" name="_csrf"   value="<?= csrf_token() ?>">
                <input type="hidden" name="_action" value="<?= $action === 'edit' ? 'update' : 'create' ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อภาษาไทย *</label>
                            <input type="text" name="name_th" required
                                   value="<?= htmlspecialchars($editPlace['name_th'] ?? '') ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อภาษาอังกฤษ</label>
                            <input type="text" name="name_en"
                                   value="<?= htmlspecialchars($editPlace['name_en'] ?? '') ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">จังหวัด *</label>
                            <select name="province_slug" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">-- เลือกจังหวัด --</option>
                                <?php foreach ($provinces as $prov): ?>
                                <option value="<?= htmlspecialchars($prov['slug']) ?>"
                                    <?= ($editPlace['province_slug'] ?? '') === $prov['slug'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($prov['name_th']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ประเภท *</label>
                            <select name="category"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat ?>"
                                    <?= ($editPlace['category'] ?? 'other') === $cat ? 'selected' : '' ?>>
                                    <?= $cat ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Latitude *</label>
                                <input type="number" name="lat" id="field-lat" step="any" required
                                       value="<?= $editPlace['lat'] ?? '' ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Longitude *</label>
                                <input type="number" name="lng" id="field-lng" step="any" required
                                       value="<?= $editPlace['lng'] ?? '' ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>
                        </div>
                        <p class="text-xs text-gray-400">คลิกบนแผนที่เพื่อเลือกตำแหน่ง</p>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ที่อยู่</label>
                            <input type="text" name="address"
                                   value="<?= htmlspecialchars($editPlace['address'] ?? '') ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">โทรศัพท์</label>
                            <input type="text" name="phone"
                                   value="<?= htmlspecialchars($editPlace['phone'] ?? '') ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">เว็บไซต์</label>
                            <input type="url" name="website"
                                   value="<?= htmlspecialchars($editPlace['website'] ?? '') ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ราคาเข้าชม (บาท)</label>
                            <input type="number" name="price_thb" min="0"
                                   value="<?= (int)($editPlace['price_thb'] ?? 0) ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">สถานะ</label>
                            <select name="status"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="active"   <?= ($editPlace['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>เปิดใช้งาน</option>
                                <option value="inactive" <?= ($editPlace['status'] ?? '')        === 'inactive' ? 'selected' : '' ?>>ปิดใช้งาน</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="sha_certified" id="sha" value="1"
                                   <?= !empty($editPlace['sha_certified']) ? 'checked' : '' ?>>
                            <label for="sha" class="text-sm text-gray-700">SHA Certified (มาตรฐาน TAT)</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">คำอธิบาย</label>
                    <textarea name="description" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500"><?= htmlspecialchars($editPlace['description'] ?? '') ?></textarea>
                </div>

                <!-- Map Picker -->
                <div class="mt-4">
                    <p class="text-sm font-medium text-gray-700 mb-2">แผนที่ (คลิกเพื่อเลือกตำแหน่ง)</p>
                    <div id="picker-map" style="height:300px;border-radius:12px;overflow:hidden;"></div>
                </div>

                <div class="mt-6 flex gap-3">
                    <button type="submit"
                            class="px-6 py-2.5 bg-green-500 text-white rounded-lg text-sm font-medium hover:bg-green-600">
                        <?= $action === 'edit' ? 'บันทึกการแก้ไข' : 'เพิ่มสถานที่' ?>
                    </button>
                    <a href="/admin/places.php"
                       class="px-6 py-2.5 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300">
                        ยกเลิก
                    </a>
                </div>
            </form>
        </div>

        <?php else: ?>
        <!-- ---- List View ---- -->

        <!-- Filter bar -->
        <form method="GET" action="/admin/places.php" class="flex gap-3 mb-5">
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                   placeholder="ค้นหาชื่อสถานที่..."
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            <select name="province"
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                <option value="">ทุกจังหวัด</option>
                <?php foreach ($provinces as $prov): ?>
                <option value="<?= htmlspecialchars($prov['slug']) ?>"
                    <?= $filterProv === $prov['slug'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($prov['name_th']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button type="submit"
                    class="px-4 py-2 bg-green-500 text-white rounded-lg text-sm hover:bg-green-600">
                ค้นหา
            </button>
        </form>

        <div class="admin-card">
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm text-gray-500"><?= number_format($total) ?> รายการ</p>
            </div>

            <?php if (empty($places)): ?>
            <p class="text-center text-gray-400 py-8">ไม่พบข้อมูล</p>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ชื่อ</th>
                            <th>จังหวัด</th>
                            <th>ประเภท</th>
                            <th>แหล่ง</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($places as $pl): ?>
                        <tr>
                            <td class="text-gray-400 text-xs"><?= $pl['id'] ?></td>
                            <td>
                                <div class="font-medium text-gray-800"><?= htmlspecialchars($pl['name_th']) ?></div>
                                <?php if ($pl['name_en']): ?>
                                <div class="text-xs text-gray-400"><?= htmlspecialchars($pl['name_en']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-sm"><?= htmlspecialchars($pl['province_slug']) ?></td>
                            <td><span class="badge badge-blue"><?= $pl['category'] ?></span></td>
                            <td><span class="badge badge-gray"><?= $pl['source'] ?></span></td>
                            <td>
                                <span class="badge <?= $pl['status'] === 'active' ? 'badge-green' : 'badge-red' ?>">
                                    <?= $pl['status'] === 'active' ? 'เปิด' : 'ปิด' ?>
                                </span>
                            </td>
                            <td>
                                <div class="flex gap-2">
                                    <a href="/admin/places.php?action=edit&id=<?= $pl['id'] ?>"
                                       class="text-xs text-blue-600 hover:underline">แก้ไข</a>
                                    <form method="POST" action="/admin/places.php"
                                          onsubmit="return confirm('ลบ <?= htmlspecialchars(addslashes($pl['name_th'])) ?> ?')">
                                        <input type="hidden" name="_csrf"   value="<?= csrf_token() ?>">
                                        <input type="hidden" name="_action" value="delete">
                                        <input type="hidden" name="id"     value="<?= $pl['id'] ?>">
                                        <button type="submit" class="text-xs text-red-500 hover:underline">ลบ</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="flex justify-center gap-2 mt-5">
                <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                <a href="?page=<?= $p ?>&q=<?= urlencode($search) ?>&province=<?= urlencode($filterProv) ?>"
                   class="px-3 py-1.5 rounded text-sm <?= $p === $page ? 'bg-green-500 text-white' : 'bg-white border hover:bg-gray-50' ?>">
                    <?= $p ?>
                </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </main>
</div>

<?php if ($showForm): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const initLat = <?= (float)($editPlace['lat'] ?? 13.7563) ?>;
const initLng = <?= (float)($editPlace['lng'] ?? 100.5018) ?>;

const pickerMap = L.map('picker-map').setView([initLat, initLng], initLat !== 13.7563 ? 14 : 6);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>'
}).addTo(pickerMap);

let pickerMarker = null;

function setPickerMarker(lat, lng) {
    if (pickerMarker) pickerMap.removeLayer(pickerMarker);
    pickerMarker = L.marker([lat, lng]).addTo(pickerMap);
    document.getElementById('field-lat').value = lat.toFixed(7);
    document.getElementById('field-lng').value = lng.toFixed(7);
}

<?php if (!empty($editPlace['lat']) && !empty($editPlace['lng'])): ?>
setPickerMarker(initLat, initLng);
<?php endif; ?>

pickerMap.on('click', e => {
    setPickerMarker(e.latlng.lat, e.latlng.lng);
});
</script>
<?php endif; ?>

</body>
</html>
