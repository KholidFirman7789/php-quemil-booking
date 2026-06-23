<?php
/**
 * Model: JamTersedia
 */
defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/database.php';

class JamTersedia
{
    private PDO $db;

    public function __construct()
    {
        $this->db = db();
    }

    public function getActive(): array
    {
        return $this->db->query(
            'SELECT * FROM jam_tersedia WHERE is_active = 1 ORDER BY jam_mulai ASC'
        )->fetchAll();
    }

    public function getAll(): array
    {
        return $this->db->query(
            'SELECT * FROM jam_tersedia ORDER BY jam_mulai ASC'
        )->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM jam_tersedia WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $jamMulai, string $jamSelesai, string $label): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO jam_tersedia (jam_mulai, jam_selesai, label) VALUES (?, ?, ?)'
        );
        $stmt->execute([$jamMulai, $jamSelesai, $label]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $jamMulai, string $jamSelesai, string $label, int $isActive): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE jam_tersedia SET jam_mulai=?, jam_selesai=?, label=?, is_active=? WHERE id=?'
        );
        return $stmt->execute([$jamMulai, $jamSelesai, $label, $isActive, $id]);
    }

    public function toggleActive(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE jam_tersedia SET is_active = NOT is_active WHERE id = ?'
        );
        return $stmt->execute([$id]);
    }
}
