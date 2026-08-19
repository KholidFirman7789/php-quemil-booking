<?php
/**
 * Model: Testimoni
 * CRUD testimoni pelanggan
 */
defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/database.php';

class Testimoni
{
    private PDO $db;

    public function __construct()
    {
        $this->db = db();
    }

    /**
     * Ambil semua testimoni (untuk admin)
     */
    public function getAll(): array
    {
        return $this->db->query(
            'SELECT * FROM testimoni ORDER BY urutan ASC, id ASC'
        )->fetchAll();
    }

    /**
     * Ambil testimoni aktif (untuk landing page)
     */
    public function getActive(int $limit = 6): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM testimoni WHERE is_active = 1 ORDER BY urutan ASC, id ASC LIMIT ?'
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Cari berdasarkan ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM testimoni WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Tambah testimoni baru
     */
    public function create(string $nama, string $event, string $teks, int $rating, int $urutan): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO testimoni (nama, event, teks, rating, urutan) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$nama, $event, $teks, $rating, $urutan]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update testimoni
     */
    public function update(int $id, string $nama, string $event, string $teks, int $rating, int $urutan, int $isActive): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE testimoni SET nama=?, event=?, teks=?, rating=?, urutan=?, is_active=? WHERE id=?'
        );
        return $stmt->execute([$nama, $event, $teks, $rating, $urutan, $isActive, $id]);
    }

    /**
     * Toggle aktif/nonaktif
     */
    public function toggleActive(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE testimoni SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?'
        );
        return $stmt->execute([$id]);
    }

    /**
     * Hapus testimoni
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM testimoni WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
