<?php
/**
 * Koneksi Database - PDO Singleton
 * Quemil Booking System
 */

defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
            );
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                if (APP_ENV === 'development') {
                    die('Koneksi database gagal: ' . $e->getMessage());
                }
                die('Terjadi kesalahan pada server. Silakan coba beberapa saat lagi.');
            }
        }
        return self::$instance;
    }

    private function __construct() {}
    private function __clone() {}
}

function db(): PDO
{
    return Database::getInstance();
}
