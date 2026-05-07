<?php
require_once __DIR__ . '/config.php';

function admin_session_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_secure'   => isset($_SERVER['HTTPS']),
            'cookie_samesite' => 'Strict',
        ]);
    }
}

function is_admin_logged_in(): bool {
    admin_session_start();
    return !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function require_admin(): void {
    admin_session_start();
    if (!is_admin_logged_in()) {
        header('Location: /admin/login.php');
        exit;
    }
}

function admin_login(string $user, string $pass): bool {
    $validUser = defined('ADMIN_USER')      ? ADMIN_USER      : '';
    $validHash = defined('ADMIN_PASS_HASH') ? ADMIN_PASS_HASH : '';

    if ($validUser && $validHash && $user === $validUser && password_verify($pass, $validHash)) {
        admin_session_start();
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user']      = $user;
        return true;
    }
    return false;
}

function admin_logout(): void {
    admin_session_start();
    $_SESSION = [];
    session_destroy();
    header('Location: /admin/login.php');
    exit;
}

function csrf_token(): string {
    admin_session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): bool {
    admin_session_start();
    $token = $_POST['_csrf'] ?? '';
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
