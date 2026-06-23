<?php
/**
 * Model: Portofolio
 * Nama tabel: portofolio (ejaan Bahasa Indonesia)
 */
defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/database.php';

class Portofolio
{
    private PDO $db;

    public function __construct()
    {
        $this->db = db();
    }

    public function getActive(int $limit = 12): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM portofolio WHERE is_active = 1 ORDER BY urutan ASC, created_at DESC LIMIT ?'
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function getAll(): array
    {
        return $this->db->query(
            'SELECT * FROM portofolio ORDER BY urutan ASC, created_at DESC'
        )->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM portofolio WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $judul, string $deskripsi, string $foto, string $kategori, int $urutan): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO portofolio (judul, deskripsi, foto, kategori, urutan) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$judul, $deskripsi, $foto, $kategori, $urutan]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $judul, string $deskripsi, ?string $foto, string $kategori, int $urutan, int $isActive): bool
    {
        if ($foto) {
            $stmt = $this->db->prepare(
                'UPDATE portofolio SET judul=?, deskripsi=?, foto=?, kategori=?, urutan=?, is_active=? WHERE id=?'
            );
            return $stmt->execute([$judul, $deskripsi, $foto, $kategori, $urutan, $isActive, $id]);
        }
        $stmt = $this->db->prepare(
            'UPDATE portofolio SET judul=?, deskripsi=?, kategori=?, urutan=?, is_active=? WHERE id=?'
        );
        return $stmt->execute([$judul, $deskripsi, $kategori, $urutan, $isActive, $id]);
    }

    /**
     * Hapus portofolio, kembalikan path foto untuk dihapus dari disk
     */
    public function delete(int $id): ?string
    {
        $item = $this->findById($id);
        if (!$item) return null;
        $stmt = $this->db->prepare('DELETE FROM portofolio WHERE id = ?');
        $stmt->execute([$id]);
        return $item['foto'];
    }
}
