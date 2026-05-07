<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Already logged in
if (is_admin_logged_in()) {
    header('Location: /admin/');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    if (admin_login($user, $pass)) {
        header('Location: /admin/');
        exit;
    }
    $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — PanteeThai</title>
    <meta name="robots" content="noindex,nofollow">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center px-4">

    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-8">
        <div class="text-center mb-8">
            <div class="text-4xl mb-2">🗺️</div>
            <h1 class="text-xl font-bold text-gray-800">PanteeThai Admin</h1>
            <p class="text-sm text-gray-400 mt-1">เข้าสู่ระบบผู้ดูแล</p>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 mb-5">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อผู้ใช้</label>
                    <input type="text"
                           name="username"
                           required
                           autocomplete="username"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่าน</label>
                    <input type="password"
                           name="password"
                           required
                           autocomplete="current-password"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>
            </div>

            <button type="submit"
                    class="w-full mt-6 bg-green-500 hover:bg-green-600 text-white font-medium py-2.5 rounded-lg text-sm transition">
                เข้าสู่ระบบ
            </button>
        </form>

        <p class="text-center text-xs text-gray-300 mt-6">
            <a href="/" class="hover:text-gray-500">กลับหน้าหลัก</a>
        </p>
    </div>

</body>
</html>
