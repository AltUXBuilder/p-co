<?php
/**
 * Prescribe & Co. — Application Configuration
 * Copy this file to config.php and update values before deployment.
 */

// ── Environment ─────────────────────────────────────────────────────
define('APP_ENV',     'production');  // 'development' | 'production'
define('APP_DEBUG',   false);         // true only in development
define('APP_VERSION', '1.0.0');

// ── Application ─────────────────────────────────────────────────────
define('APP_NAME',    'Prescribe & Co.');
define('APP_NAME_SHORT', 'P&Co.');
define('APP_URL',     'https://pandco.infinityfree.me');   // ← UPDATE — no trailing slash
define('APP_ROOT',    dirname(__DIR__));
define('APP_PATH',    __DIR__ . '/..');

// ── Database ────────────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_PORT',    3306);
define('DB_NAME',    'if0_41729760_pandco');    // ← UPDATE
define('DB_USER',    'if0_41729760');      // ← UPDATE
define('DB_PASS',    'PandCo2025');  // ← UPDATE
define('DB_CHARSET', 'utf8mb4');

// ── Security ────────────────────────────────────────────────────────
define('SECRET_KEY',      'kP9zR2mL5vX8nQ1wT4bY7cJ0sH3fG6dX9aV2eM5uK8iL1oP4rS7tB0nG3yJ6qZ9mW'); // ← UPDATE
define('SESSION_NAME',    'pco_sess');
define('SESSION_LIFETIME', 3600);  // 1 hour
define('CSRF_TOKEN_NAME', '_pco_csrf');

// ── Pharmacy Identity ────────────────────────────────────────────────
define('PHARMACY_NAME',    'Prescribe & Co.');
define('PHARMACY_SHORT',   'P&Co.');
define('PHARMACY_ADDRESS', '1 Harley Street, London, W1G 9QD');
define('PHARMACY_PHONE',   '0800 000 0000');
define('PHARMACY_EMAIL',   'hello@prescribeandco.co.uk');
define('GPHC_NUMBER',      '1234567');
define('LABEL_FOOTER',     'Keep out of reach of children. Store below 25°C.');

// ── Stripe (UI placeholder — no live processing without real keys) ──
define('STRIPE_PUBLIC_KEY', '');  // pk_test_...
define('STRIPE_SECRET_KEY', '');  // sk_test_...

// ── Uploads ──────────────────────────────────────────────────────────
define('UPLOAD_PATH',     APP_ROOT . '/uploads');
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5 MB

// ── Timezone ─────────────────────────────────────────────────────────
date_default_timezone_set('Europe/London');

// ── Error reporting ──────────────────────────────────────────────────
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', APP_ROOT . '/logs/php_errors.log');
}
