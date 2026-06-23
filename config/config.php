<?php
/**
 * Konfigurasi Utama Aplikasi
 * Quemil Booking System
 */

defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));

// Muat konfigurasi lokal yang tidak ikut Git.
$localConfigPath = BASE_PATH . '/config/config.local.php';
if (is_file($localConfigPath)) {
    require_once $localConfigPath;
}

if (!function_exists('defineIfMissing')) {
    function defineIfMissing(string $name, mixed $value): void
    {
        if (!defined($name)) {
            define($name, $value);
        }
    }
}

if (!function_exists('envValue')) {
    function envValue(string $name, mixed $default = null): mixed
    {
        $value = getenv($name);
        return ($value === false || $value === '') ? $default : $value;
    }
}

if (!function_exists('envBool')) {
    function envBool(string $name, bool $default = false): bool
    {
        $value = envValue($name, null);
        return $value === null
            ? $default
            : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}

// ============================================================
// Konfigurasi Aplikasi
// ============================================================
defineIfMissing('APP_NAME', envValue('APP_NAME', 'Quemil Makeup'));
defineIfMissing('APP_URL',  envValue('APP_URL', 'http://localhost/quemil-booking/public'));
defineIfMissing('APP_ENV',  envValue('APP_ENV', 'development')); // 'development' | 'production'

// ============================================================
// Konfigurasi Database
// ============================================================
defineIfMissing('DB_HOST',    envValue('DB_HOST', 'localhost'));
defineIfMissing('DB_PORT',    envValue('DB_PORT', '3306'));
defineIfMissing('DB_NAME',    envValue('DB_NAME', 'quemil_booking'));
defineIfMissing('DB_USER',    envValue('DB_USER', 'root'));
defineIfMissing('DB_PASS',    envValue('DB_PASS', ''));
defineIfMissing('DB_CHARSET', envValue('DB_CHARSET', 'utf8mb4'));

// ============================================================
// Konfigurasi Midtrans
// ============================================================
defineIfMissing('MIDTRANS_SERVER_KEY',    envValue('MIDTRANS_SERVER_KEY', ''));
defineIfMissing('MIDTRANS_CLIENT_KEY',    envValue('MIDTRANS_CLIENT_KEY', ''));
defineIfMissing('MIDTRANS_IS_PRODUCTION', envBool('MIDTRANS_IS_PRODUCTION', false)); // false = Sandbox
defineIfMissing('MIDTRANS_SNAP_URL',
    MIDTRANS_IS_PRODUCTION
        ? 'https://app.midtrans.com/snap/snap.js'
        : 'https://app.sandbox.midtrans.com/snap/snap.js'
);
defineIfMissing('MIDTRANS_API_URL',
    MIDTRANS_IS_PRODUCTION
        ? 'https://app.midtrans.com/snap/v1/transactions'
        : 'https://app.sandbox.midtrans.com/snap/v1/transactions'
);

// ============================================================
// Konfigurasi Session
// ============================================================
defineIfMissing('SESSION_NAME',    envValue('SESSION_NAME', 'quemil_session'));
defineIfMissing('CSRF_TOKEN_NAME', envValue('CSRF_TOKEN_NAME', '_csrf_token'));

// ============================================================
// Konfigurasi Upload
// ============================================================
defineIfMissing('UPLOAD_PATH',   BASE_PATH . '/public/uploads');
defineIfMissing('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB
defineIfMissing('ALLOWED_IMAGES', ['image/jpeg', 'image/png', 'image/webp']);

// ============================================================
// Konfigurasi Booking
// ============================================================
defineIfMissing('DP_PERCENT',            30); // DP 30% dari total
defineIfMissing('PAYMENT_EXPIRED_HOURS', 24); // Batas waktu bayar 24 jam

// ============================================================
// Timezone
// ============================================================
date_default_timezone_set('Asia/Jakarta');