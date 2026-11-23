<?php
/**
 * Alam Al-Sinaa - Main Configuration
 * Enterprise-grade configuration file
 */

// Prevent direct access
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}

// ============================================================
// DATABASE CONFIGURATION
// ============================================================
// Credentials are NOT stored in this file (it is tracked in git).
// Put them in config.local.php (git-ignored) next to this file — see
// config.local.example.php — or set them as environment variables.
$__localConfig = is_file(__DIR__ . '/config.local.php') ? (array) require __DIR__ . '/config.local.php' : [];
$__cfg = static function (string $key, string $default = '') use ($__localConfig): string {
    $env = getenv($key);
    if ($env !== false && $env !== '') return $env;
    return isset($__localConfig[$key]) ? (string) $__localConfig[$key] : $default;
};

define('DB_HOST', $__cfg('DB_HOST', 'localhost'));
define('DB_NAME', $__cfg('DB_NAME'));
define('DB_USER', $__cfg('DB_USER'));
define('DB_PASS', $__cfg('DB_PASS'));
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');

// ============================================================
// SITE CONFIGURATION
// ============================================================
define('SITE_URL', rtrim($__cfg('SITE_URL', 'https://industry-world.online'), '/'));
define('ADMIN_URL', SITE_URL . '/admin');
define('ASSETS_URL', SITE_URL . '/assets');
define('UPLOADS_URL', SITE_URL . '/assets/uploads');
define('UPLOADS_PATH', BASE_PATH . '/assets/uploads');

// ============================================================
// SECURITY CONFIGURATION
// ============================================================
define('SESSION_NAME', 'AlamAlSinaa_Session');
define('SESSION_LIFETIME', 7200); // 2 hours
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_TIME', 'csrf_token_time');
define('CSRF_TOKEN_LIFETIME', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes
define('RATE_LIMIT_REQUESTS', 60); // requests per minute
define('RATE_LIMIT_WINDOW', 60); // seconds
// Encryption key - Should be changed in production
define('ENCRYPTION_KEY', $__cfg('ENCRYPTION_KEY', hash('sha256', __DIR__ . DB_NAME . DB_USER)));

// ============================================================
// APPLICATION CONFIGURATION
// ============================================================
define('DEFAULT_LANGUAGE', 'ar');
define('SUPPORTED_LANGUAGES', ['ar', 'en']);
define('DEFAULT_THEME', 'light');
define('ITEMS_PER_PAGE', 12);
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_FILE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf']);

// ============================================================
// TIMEZONE
// ============================================================
date_default_timezone_set('Asia/Aden');

// ============================================================
// ERROR HANDLING
// ============================================================
$env = $__cfg('APP_ENV', 'production');
if ($env === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', BASE_PATH . '/logs/error.log');
}

// ============================================================
// LOAD HELPERS
// ============================================================
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/database.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/i18n.php';
require_once BASE_PATH . '/includes/page_images.php';
require_once BASE_PATH . '/includes/media_library.php';
