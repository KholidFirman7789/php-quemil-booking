<?php
/**
 * Model: JenisMakeup
 */
defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/database.php';

class JenisMakeup
{
    private PDO $db;

    public function __construct()
    {
        $this->db = db();
    }

    public function getActive(): array
    {
        return $this->db->query(
            'SELECT * FROM jenis_makeup WHERE is_active = 1 ORDER BY harga ASC'
        )->fetchAll();
    }

    public function getAll(): array
    {
        return $this->db->query('SELECT * FROM jenis_makeup ORDER BY harga ASC')->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM jenis_makeup WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $nama, string $deskripsi, float $harga): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO jenis_makeup (nama, deskripsi, harga) VALUES (?, ?, ?)'
        );
        $stmt->execute([$nama, $deskripsi, $harga]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $nama, string $deskripsi, float $harga, int $isActive): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE jenis_makeup SET nama=?, deskripsi=?, harga=?, is_active=? WHERE id=?'
        );
        return $stmt->execute([$nama, $deskripsi, $harga, $isActive, $id]);
    }

    public function toggleActive(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE jenis_makeup SET is_active = NOT is_active WHERE id = ?'
        );
        return $stmt->execute([$id]);
    }
}
