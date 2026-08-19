<?php
/**
 * Model: JenisMakeup
 */
defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/database.php';

class JenisMakeup
{
    private PDO $db;

    // Daftar kategori yang tersedia
    public const KATEGORI_LIST = [
        'Reguler',
        'Dayang',
        'Karnaval',
        'Pengantin',
        'Sewa Baju',
        'Sewa Sandal',
    ];

    public const GENDER_LIST = ['wanita', 'pria', 'couple', 'anak'];

    public function __construct()
    {
        $this->db = db();
    }

    /**
     * Ambil semua jenis aktif, diurutkan berdasarkan harga
     */
    public function getActive(): array
    {
        return $this->db->query(
            "SELECT * FROM jenis_makeup WHERE is_active = 1
             ORDER BY FIELD(kategori,'Reguler','Dayang','Karnaval','Pengantin','Sewa Baju','Sewa Sandal'),
                      FIELD(gender,'wanita','pria','couple','anak'),
                      harga ASC"
        )->fetchAll();
    }

    /**
     * Ambil semua jenis aktif, dikelompokkan per kategori dan gender
     * Return: ['Reguler' => ['wanita' => [...], 'pria' => [...]], ...]
     */
    public function getActiveGrouped(): array
    {
        $rows = $this->getActive();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['kategori']][$row['gender']][] = $row;
        }
        return $grouped;
    }

    /**
     * Ambil semua jenis (admin), diurutkan per kategori
     */
    public function getAll(): array
    {
        return $this->db->query(
            "SELECT * FROM jenis_makeup
             ORDER BY FIELD(kategori,'Reguler','Dayang','Karnaval','Pengantin','Sewa Baju','Sewa Sandal'),
                      FIELD(gender,'wanita','pria','couple','anak'),
                      harga ASC"
        )->fetchAll();
    }

    /**
     * Ambil semua jenis (admin), dikelompokkan per kategori
     */
    public function getAllGrouped(): array
    {
        $rows = $this->getAll();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['kategori']][] = $row;
        }
        return $grouped;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM jenis_makeup WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $nama, string $kategori, string $gender, string $deskripsi, float $harga, ?string $foto = null): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO jenis_makeup (nama, kategori, gender, deskripsi, foto, harga) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$nama, $kategori, $gender, $deskripsi, $foto, $harga]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $nama, string $kategori, string $gender, string $deskripsi, float $harga, int $isActive, ?string $foto = null): bool
    {
        if ($foto !== null) {
            $stmt = $this->db->prepare(
                'UPDATE jenis_makeup SET nama=?, kategori=?, gender=?, deskripsi=?, harga=?, is_active=?, foto=? WHERE id=?'
            );
            return $stmt->execute([$nama, $kategori, $gender, $deskripsi, $harga, $isActive, $foto, $id]);
        }
        $stmt = $this->db->prepare(
            'UPDATE jenis_makeup SET nama=?, kategori=?, gender=?, deskripsi=?, harga=?, is_active=? WHERE id=?'
        );
        return $stmt->execute([$nama, $kategori, $gender, $deskripsi, $harga, $isActive, $id]);
    }

    public function updateFoto(int $id, ?string $foto): bool
    {
        $stmt = $this->db->prepare('UPDATE jenis_makeup SET foto=? WHERE id=?');
        return $stmt->execute([$foto, $id]);
    }

    public function toggleActive(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE jenis_makeup SET is_active = NOT is_active WHERE id = ?'
        );
        return $stmt->execute([$id]);
    }
}
