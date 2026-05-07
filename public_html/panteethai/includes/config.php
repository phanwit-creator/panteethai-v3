<?php
// includes/config.php — Load .env + Constants
// PanteeThai.com v3

// Load .env file
// $cfg = parse_ini_file(__DIR__ . '/../../../.env');
//$cfg = parse_ini_file(dirname(dirname(dirname(__DIR__))) . '/.env');
//$cfg = parse_ini_file(dirname(dirname(__DIR__)) . '/.env');
//$cfg = parse_ini_file('/home/panteeth/domains/dev.panteethai.com/.env');
//$cfg = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/../.env');
$cfg = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/../.env');
if (!$cfg) {
    error_log('Cannot load .env file');
    die('Configuration error');
}

// Database
define('DB_HOST', $cfg['DB_HOST']);
define('DB_NAME', $cfg['DB_NAME']);
define('DB_USER', $cfg['DB_USER']);
define('DB_PASS', $cfg['DB_PASS']);

// APIs
define('TAT_API_KEY',    $cfg['TAT_API_KEY']);
define('MAPTILER_KEY',   $cfg['MAPTILER_KEY']);
define('ADSENSE_PUB_ID', $cfg['ADSENSE_PUB_ID']);

// App
define('APP_ENV',   $cfg['APP_ENV']);
define('APP_DEBUG', $cfg['APP_DEBUG']);
define('APP_URL',   $cfg['APP_URL']);

// Admin auth (set ADMIN_USER + ADMIN_PASS_HASH in .env via: php -r "echo password_hash('yourpass', PASSWORD_DEFAULT);")
define('ADMIN_USER',      $cfg['ADMIN_USER']      ?? '');
define('ADMIN_PASS_HASH', $cfg['ADMIN_PASS_HASH'] ?? '');

// CRON secret for tat-sync.php HTTP access
define('CRON_SECRET', $cfg['CRON_SECRET'] ?? '');

// Error handling
if (APP_ENV === 'production') {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../../../logs/error.log');
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Timezone
date_default_timezone_set('Asia/Bangkok');