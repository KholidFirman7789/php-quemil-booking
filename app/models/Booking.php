<?php
/**
 * Model: Booking
 * FCFS ditentukan oleh created_at (DEFAULT CURRENT_TIMESTAMP)
 * Slot locking menggunakan transaksi DB + SELECT FOR UPDATE
 */
defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/database.php';

class Booking
{
    private PDO $db;

    public function __construct()
    {
        $this->db = db();
    }

    /**
     * Cek apakah slot (tanggal + jam_id preset) masih tersedia
     */
    public function isSlotAvailable(string $tanggal, int $jamId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM bookings
             WHERE tanggal = ? AND jam_id = ?
               AND slot_locked = 1
               AND status NOT IN ('cancelled')"
        );
        $stmt->execute([$tanggal, $jamId]);
        return (int) $stmt->fetchColumn() === 0;
    }

    /**
     * Cek apakah jam custom (jam_mulai - jam_selesai) overlap dengan booking lain
     * Overlap terjadi jika: mulai_baru < selesai_lama AND selesai_baru > mulai_lama
     */
    public function isTimeRangeAvailable(string $tanggal, string $jamMulai, string $jamSelesai, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM bookings
                WHERE tanggal = ?
                  AND slot_locked = 1
                  AND status NOT IN ('cancelled')
                  AND jam_mulai  < ?
                  AND jam_selesai > ?";
        $params = [$tanggal, $jamSelesai, $jamMulai];
        if ($excludeId) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() === 0;
    }

    /**
     * Ambil semua booking yang overlap pada tanggal tertentu (untuk alert UI)
     */
    public function getOverlappingBookings(string $tanggal): array
    {
        $stmt = $this->db->prepare(
            "SELECT jam_mulai, jam_selesai FROM bookings
             WHERE tanggal = ?
               AND slot_locked = 1
               AND status NOT IN ('cancelled')
               AND jam_mulai IS NOT NULL"
        );
        $stmt->execute([$tanggal]);
        return $stmt->fetchAll();
    }

    /**
     * Buat record booking baru
     * created_at diisi otomatis oleh MySQL (FCFS key)
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO bookings
                (kode_booking, user_id, jenis_makeup_id, jam_mulai, jam_selesai, tanggal,
                 tipe_layanan, alamat_lengkap, kota, provinsi, zona_id,
                 maps_url, jumlah_orang,
                 biaya_transport, harga_jasa, total_biaya, dp_amount,
                 pelunasan_amount, status, catatan_user)
             VALUES
                (:kode_booking, :user_id, :jenis_makeup_id, :jam_mulai, :jam_selesai, :tanggal,
                 :tipe_layanan, :alamat_lengkap, :kota, :provinsi, :zona_id,
                 :maps_url, :jumlah_orang,
                 :biaya_transport, :harga_jasa, :total_biaya, :dp_amount,
                 :pelunasan_amount, :status, :catatan_user)"
        );
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Kunci slot setelah DP berhasil dibayar (FCFS + atomicity)
     */
    public function lockSlot(int $bookingId): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT * FROM bookings WHERE id = ? FOR UPDATE');
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch();

            if (!$booking) {
                $this->db->rollBack();
                return false;
            }

            // Cek overlap waktu dengan booking lain yang sudah terkunci (FCFS)
            // Semua booking menggunakan jam_mulai/jam_selesai — jam_id tidak lagi digunakan
            if (!$booking['jam_mulai'] || !$booking['jam_selesai']) {
                $this->db->rollBack();
                throw new \InvalidArgumentException("Booking #{$bookingId} tidak memiliki jam_mulai/jam_selesai.");
            }

            $stmtCheck = $this->db->prepare(
                "SELECT COUNT(*) FROM bookings
                 WHERE tanggal = ? AND slot_locked = 1
                   AND status NOT IN ('cancelled') AND id != ?
                   AND jam_mulai < ? AND jam_selesai > ?"
            );
            $stmtCheck->execute([
                $booking['tanggal'], $bookingId,
                $booking['jam_selesai'], $booking['jam_mulai']
            ]);

            if ((int) $stmtCheck->fetchColumn() > 0) {
                // Slot sudah diambil booking lain yang lebih awal (FCFS)
                $this->db->rollBack();
                return false;
            }

            $stmtUpdate = $this->db->prepare(
                "UPDATE bookings SET slot_locked = 1, status = 'confirmed' WHERE id = ?"
            );
            $stmtUpdate->execute([$bookingId]);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Bebaskan slot (cancel / expired)
     */
    public function unlockSlot(int $bookingId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE bookings SET slot_locked = 0, status = 'cancelled' WHERE id = ?"
        );
        return $stmt->execute([$bookingId]);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT b.*,
                    u.name AS user_name, u.email AS user_email, u.phone AS user_phone,
                    j.nama AS jenis_nama, j.harga AS jenis_harga,
                    LEFT(b.jam_mulai, 5)   AS jam_mulai_fmt,
                    LEFT(b.jam_selesai, 5) AS jam_selesai_fmt,
                    CONCAT(LEFT(b.jam_mulai, 5), ' - ', LEFT(b.jam_selesai, 5)) AS jam_label,
                    z.nama_zona, z.biaya AS zona_biaya
             FROM bookings b
             JOIN users u              ON b.user_id = u.id
             JOIN jenis_makeup j       ON b.jenis_makeup_id = j.id
             LEFT JOIN zona_transport z ON b.zona_id = z.id
             WHERE b.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByKode(string $kode): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT b.*, u.name AS user_name, j.nama AS jenis_nama,
                    CONCAT(LEFT(b.jam_mulai, 5), ' - ', LEFT(b.jam_selesai, 5)) AS jam_label
             FROM bookings b
             JOIN users u        ON b.user_id = u.id
             JOIN jenis_makeup j ON b.jenis_makeup_id = j.id
             WHERE b.kode_booking = ?"
        );
        $stmt->execute([$kode]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Ambil semua booking milik user, urut FCFS
     */
    public function getByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT b.*, j.nama AS jenis_nama,
                    CONCAT(LEFT(b.jam_mulai, 5), ' - ', LEFT(b.jam_selesai, 5)) AS jam_label
             FROM bookings b
             JOIN jenis_makeup j ON b.jenis_makeup_id = j.id
             WHERE b.user_id = ?
             ORDER BY b.created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Ambil semua booking (admin), urut FCFS
     */
    public function getAll(?string $status = null, int $limit = 50, int $offset = 0): array
    {
        $where = $status ? 'WHERE b.status = :status' : '';
        $sql   = "SELECT b.*,
                         u.name AS user_name, u.phone AS user_phone,
                         j.nama AS jenis_nama,
                         CONCAT(LEFT(b.jam_mulai, 5), ' - ', LEFT(b.jam_selesai, 5)) AS jam_label
                  FROM bookings b
                  JOIN users u        ON b.user_id = u.id
                  JOIN jenis_makeup j ON b.jenis_makeup_id = j.id
                  {$where}
                  ORDER BY b.created_at DESC
                  LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        if ($status) $stmt->bindValue(':status', $status);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateStatus(int $id, string $status, ?string $catatanAdmin = null): bool
    {
        if ($catatanAdmin !== null) {
            $stmt = $this->db->prepare(
                'UPDATE bookings SET status = ?, catatan_admin = ? WHERE id = ?'
            );
            return $stmt->execute([$status, $catatanAdmin, $id]);
        }
        $stmt = $this->db->prepare('UPDATE bookings SET status = ? WHERE id = ?');
        return $stmt->execute([$status, $id]);
    }

    /**
     * Ambil jam_id yang sudah terkunci pada tanggal tertentu
     */
    public function getBookedSlots(string $tanggal): array
    {
        $stmt = $this->db->prepare(
            "SELECT jam_id FROM bookings
             WHERE tanggal = ? AND slot_locked = 1
               AND status NOT IN ('cancelled')"
        );
        $stmt->execute([$tanggal]);
        return array_column($stmt->fetchAll(), 'jam_id');
    }

    /**
     * Auto-cancel booking yang melewati batas waktu pembayaran
     */
    public function cancelExpiredBookings(): int
    {
        $stmt = $this->db->prepare(
            "UPDATE bookings b
             JOIN payments p ON p.booking_id = b.id
             SET b.status = 'cancelled', b.slot_locked = 0, p.status = 'expired'
             WHERE b.status = 'waiting_payment'
               AND p.expired_at IS NOT NULL
               AND p.expired_at < NOW()"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function countByStatus(?int $userId = null): array
    {
        $where = $userId ? 'WHERE user_id = ' . (int) $userId : '';
        $rows  = $this->db->query(
            "SELECT status, COUNT(*) AS total FROM bookings {$where} GROUP BY status"
        )->fetchAll();
        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }
        return $counts;
    }

    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM bookings')->fetchColumn();
    }
}
