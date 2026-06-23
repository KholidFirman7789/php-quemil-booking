<?php
/**
 * Contoh konfigurasi lokal.
 *
 * Copy file ini menjadi config.local.php, lalu isi value sesuai environment.
 * File config.local.php sudah masuk .gitignore dan tidak perlu dipush.
 */

defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));

define('APP_URL', 'http://localhost/quemil-booking/public');
define('APP_ENV', 'development');

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'quemil_booking');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('MIDTRANS_SERVER_KEY', '');
define('MIDTRANS_CLIENT_KEY', '');
define('MIDTRANS_IS_PRODUCTION', false);