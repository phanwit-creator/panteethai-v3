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

// ---- Handle POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'CSRF token invalid. กรุณา refresh และลองใหม่';
    } else {
        $postAction = $_POST['_action'] ?? '';

        if ($postAction === 'delete') {
            $delId = (int)($_POST['id'] ?? 0);
            if ($delId > 0) {
                db_execute("DELETE FROM articles WHERE id = ?", [$delId]);
                $msg    = 'ลบบทความเรียบร้อยแล้ว';
                $action = 'list';
            }

        } elseif (in_array($postAction, ['create', 'update'], true)) {
            $titleTh   = trim($_POST['title_th']       ?? '');
            $titleEn   = trim($_POST['title_en']       ?? '');
            $slug      = trim($_POST['slug']           ?? '');
            $provSlug  = trim($_POST['province_slug']  ?? '');
            $contentTh = trim($_POST['content_th']     ?? '');
            $contentEn = trim($_POST['content_en']     ?? '');
            $featImg   = trim($_POST['featured_image'] ?? '');
            $keywords  = trim($_POST['seo_keywords']   ?? '');
            $status    = in_array($_POST['status'] ?? '', ['published', 'draft']) ? $_POST['status'] : 'draft';

            // Auto-generate slug if empty (create only)
            if (!$slug && $titleEn) {
                $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $titleEn));
                $slug = trim($slug, '-');
            }
            $slug = preg_replace('/[^a-z0-9-]/', '', strtolower($slug));

            if (!$titleTh) {
                $error = 'กรุณาระบุหัวข้อบทความภาษาไทย';
            } elseif (!$slug) {
                $error = 'กรุณาระบุ slug (URL) ของบทความ';
            } else {
                try {
                    if ($postAction === 'create') {
                        db_execute(
                            "INSERT INTO articles
                                (province_slug, slug, title_th, title_en,
                                 content_th, content_en, featured_image,
                                 seo_keywords, published_at, status)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)",
                            [$provSlug ?: null, $slug, $titleTh, $titleEn,
                             $contentTh, $contentEn, $featImg, $keywords, $status]
                        );
                        $msg    = 'สร้างบทความเรียบร้อยแล้ว';
                        $action = 'list';
                    } else {
                        db_execute(
                            "UPDATE articles SET
                                province_slug = ?, slug = ?, title_th = ?, title_en = ?,
                                content_th = ?, content_en = ?, featured_image = ?,
                                seo_keywords = ?, status = ?
                             WHERE id = ?",
                            [$provSlug ?: null, $slug, $titleTh, $titleEn,
                             $contentTh, $contentEn, $featImg, $keywords, $status,
                             $editId]
                        );
                        $msg    = 'อัปเดตบทความเรียบร้อยแล้ว';
                        $action = 'list';
                    }
                } catch (Exception $e) {
                    error_log('admin/articles.php save: ' . $e->getMessage());
                    // Duplicate slug error
                    if ($e->getCode() == 23000) {
                        $error = "Slug \"{$slug}\" ถูกใช้งานแล้ว กรุณาเปลี่ยน";
                    } else {
                        $error = 'เกิดข้อผิดพลาด กรุณาลองใหม่';
                    }
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
$filterStatus = $_GET['status'] ?? '';

$where  = [];
$params = [];

if ($search) {
    $where[] = '(a.title_th LIKE :q OR a.title_en LIKE :q2)';
    $params[':q'] = $params[':q2'] = "%{$search}%";
}
if (in_array($filterStatus, ['published', 'draft'], true)) {
    $where[]           = 'a.status = :status';
    $params[':status'] = $filterStatus;
}

$whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total      = 0;
$articles   = [];
$editArt    = null;

try {
    $total = (int)db_row("SELECT COUNT(*) AS c FROM articles a {$whereStr}", $params)['c'];
    $articles = db_query(
        "SELECT a.id, a.slug, a.title_th, a.province_slug,
                a.published_at, a.status,
                pr.name_th AS province_name
         FROM articles a
         LEFT JOIN provinces pr ON a.province_slug = pr.slug
         {$whereStr}
         ORDER BY a.id DESC
         LIMIT :limit OFFSET :offset",
        array_merge($params, [':limit' => $perPage, ':offset' => $offset])
    );

    if ($action === 'edit' && $editId) {
        $editArt = db_row("SELECT * FROM articles WHERE id = ?", [$editId]);
        if (!$editArt) { $action = 'list'; }
    }
} catch (Exception $e) {
    error_log('admin/articles.php list: ' . $e->getMessage());
}

$totalPages = (int)ceil($total / $perPage);
$showForm   = in_array($action, ['add', 'edit'], true);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บทความ — PanteeThai Admin</title>
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
            <a href="/admin/articles.php" class="active">📝 บทความ</a>
            <a href="/admin/tat-status.php">🔄 TAT Sync</a>
        </nav>
        <div class="px-5 mt-auto">
            <a href="/admin/?action=logout" class="text-xs text-red-400 hover:text-red-300">ออกจากระบบ</a>
        </div>
    </aside>

    <main class="flex-1 p-8 overflow-y-auto">

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800">
                <?= $showForm ? ($action === 'add' ? 'เขียนบทความใหม่' : 'แก้ไขบทความ') : 'จัดการบทความ' ?>
            </h1>
            <?php if (!$showForm): ?>
            <a href="/admin/articles.php?action=add"
               class="px-4 py-2 bg-green-500 text-white rounded-lg text-sm hover:bg-green-600">
                + เขียนบทความ
            </a>
            <?php else: ?>
            <a href="/admin/articles.php"
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
        <!-- ---- Article Form ---- -->
        <div class="admin-card">
            <form method="POST"
                  action="/admin/articles.php<?= $action === 'edit' ? "?action=edit&id={$editId}" : "?action=add" ?>">
                <input type="hidden" name="_csrf"   value="<?= csrf_token() ?>">
                <input type="hidden" name="_action" value="<?= $action === 'edit' ? 'update' : 'create' ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">หัวข้อภาษาไทย *</label>
                        <input type="text" name="title_th" required
                               value="<?= htmlspecialchars($editArt['title_th'] ?? '') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">หัวข้อภาษาอังกฤษ</label>
                        <input type="text" name="title_en"
                               value="<?= htmlspecialchars($editArt['title_en'] ?? '') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Slug (URL) *
                            <span class="text-gray-400 font-normal">เช่น travel-guide-chiangmai</span>
                        </label>
                        <input type="text" name="slug"
                               pattern="[a-z0-9-]+"
                               value="<?= htmlspecialchars($editArt['slug'] ?? '') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">จังหวัด</label>
                        <select name="province_slug"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="">-- ไม่ระบุ --</option>
                            <?php foreach ($provinces as $prov): ?>
                            <option value="<?= htmlspecialchars($prov['slug']) ?>"
                                <?= ($editArt['province_slug'] ?? '') === $prov['slug'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($prov['name_th']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">รูป Featured (URL)</label>
                        <input type="url" name="featured_image"
                               value="<?= htmlspecialchars($editArt['featured_image'] ?? '') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">SEO Keywords</label>
                        <input type="text" name="seo_keywords"
                               value="<?= htmlspecialchars($editArt['seo_keywords'] ?? '') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">เนื้อหาภาษาไทย</label>
                    <textarea name="content_th" rows="12"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-green-500"><?= htmlspecialchars($editArt['content_th'] ?? '') ?></textarea>
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">เนื้อหาภาษาอังกฤษ (optional)</label>
                    <textarea name="content_en" rows="6"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-green-500"><?= htmlspecialchars($editArt['content_en'] ?? '') ?></textarea>
                </div>

                <div class="flex items-center gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">สถานะ</label>
                        <select name="status"
                                class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="draft"     <?= ($editArt['status'] ?? 'draft') === 'draft'     ? 'selected' : '' ?>>ร่าง</option>
                            <option value="published" <?= ($editArt['status'] ?? '')       === 'published' ? 'selected' : '' ?>>เผยแพร่</option>
                        </select>
                    </div>
                    <div class="flex gap-3 mt-5">
                        <button type="submit"
                                class="px-6 py-2.5 bg-green-500 text-white rounded-lg text-sm font-medium hover:bg-green-600">
                            <?= $action === 'edit' ? 'บันทึกการแก้ไข' : 'สร้างบทความ' ?>
                        </button>
                        <a href="/admin/articles.php"
                           class="px-6 py-2.5 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300">
                            ยกเลิก
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <?php else: ?>
        <!-- ---- List View ---- -->

        <!-- Filter bar -->
        <form method="GET" action="/admin/articles.php" class="flex gap-3 mb-5">
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                   placeholder="ค้นหาหัวข้อ..."
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            <select name="status"
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                <option value="">ทุกสถานะ</option>
                <option value="published" <?= $filterStatus === 'published' ? 'selected' : '' ?>>เผยแพร่</option>
                <option value="draft"     <?= $filterStatus === 'draft'     ? 'selected' : '' ?>>ร่าง</option>
            </select>
            <button type="submit"
                    class="px-4 py-2 bg-green-500 text-white rounded-lg text-sm hover:bg-green-600">
                ค้นหา
            </button>
        </form>

        <div class="admin-card">
            <p class="text-sm text-gray-500 mb-4"><?= number_format($total) ?> บทความ</p>

            <?php if (empty($articles)): ?>
            <p class="text-center text-gray-400 py-8">ยังไม่มีบทความ</p>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>หัวข้อ</th>
                            <th>Slug</th>
                            <th>จังหวัด</th>
                            <th>วันที่</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($articles as $ar): ?>
                        <tr>
                            <td class="text-gray-400 text-xs"><?= $ar['id'] ?></td>
                            <td class="max-w-xs">
                                <div class="font-medium text-gray-800 truncate">
                                    <?= htmlspecialchars($ar['title_th']) ?>
                                </div>
                            </td>
                            <td class="font-mono text-xs text-gray-500">
                                <?= htmlspecialchars($ar['slug']) ?>
                            </td>
                            <td class="text-sm"><?= htmlspecialchars($ar['province_name'] ?? '—') ?></td>
                            <td class="text-xs text-gray-400">
                                <?= $ar['published_at'] ? date('j M Y', strtotime($ar['published_at'])) : '—' ?>
                            </td>
                            <td>
                                <span class="badge <?= $ar['status'] === 'published' ? 'badge-green' : 'badge-yellow' ?>">
                                    <?= $ar['status'] === 'published' ? 'เผยแพร่' : 'ร่าง' ?>
                                </span>
                            </td>
                            <td>
                                <div class="flex gap-2">
                                    <a href="/blog/<?= htmlspecialchars($ar['slug']) ?>"
                                       target="_blank"
                                       class="text-xs text-gray-400 hover:text-gray-600">ดู</a>
                                    <a href="/admin/articles.php?action=edit&id=<?= $ar['id'] ?>"
                                       class="text-xs text-blue-600 hover:underline">แก้ไข</a>
                                    <form method="POST" action="/admin/articles.php"
                                          onsubmit="return confirm('ลบบทความนี้?')">
                                        <input type="hidden" name="_csrf"   value="<?= csrf_token() ?>">
                                        <input type="hidden" name="_action" value="delete">
                                        <input type="hidden" name="id"     value="<?= $ar['id'] ?>">
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
                <a href="?page=<?= $p ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($filterStatus) ?>"
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

</body>
</html>
