<?php
/**
 * Model: SiteSetting
 * Key-value store untuk pengaturan situs (hero image, dsb)
 */
defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/database.php';

class SiteSetting
{
    private PDO $db;

    public function __construct()
    {
        $this->db = db();
    }

    /**
     * Ambil nilai setting berdasarkan key
     */
    public function get(string $key): ?string
    {
        $stmt = $this->db->prepare(
            'SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1'
        );
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['setting_value'] : null;
    }

    /**
     * Simpan / update setting
     */
    public function set(string $key, string $value): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO site_settings (setting_key, setting_value)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $stmt->execute([$key, $value]);
    }

    /**
     * Ambil path hero image.
     * Return path relatif dari public/ — siap dipakai di baseUrl().
     * Fallback ke placeholder jika belum di-set.
     */
    public function getHeroImage(): ?string
    {
        return $this->get('hero_image');
    }
}
