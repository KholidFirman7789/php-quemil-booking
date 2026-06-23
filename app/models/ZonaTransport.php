<?php
/**
 * Model: ZonaTransport
 */
defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/database.php';

class ZonaTransport
{
    private PDO $db;

    public function __construct()
    {
        $this->db = db();
    }

    public function getActive(): array
    {
        return $this->db->query(
            'SELECT * FROM zona_transport WHERE is_active = 1 ORDER BY biaya ASC'
        )->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM zona_transport WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Tentukan status booking berdasarkan provinsi:
     * - 'jatim'     => proses otomatis, hitung zona
     * - 'jawa'      => pending_negotiation
     * - 'luar_jawa' => tolak
     */
    public function resolveStatus(string $provinsi): string
    {
        return validasiProvinsi($provinsi);
    }
}
