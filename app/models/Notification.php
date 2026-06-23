<?php
/**
 * Model: Notification
 */
defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/database.php';

class Notification
{
    private PDO $db;

    public function __construct()
    {
        $this->db = db();
    }

    public function create(int $userId, string $judul, string $pesan, ?int $bookingId = null): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO notifications (user_id, booking_id, judul, pesan) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $bookingId, $judul, $pesan]);
        return (int) $this->db->lastInsertId();
    }

    public function getByUser(int $userId, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?'
        );
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    public function getUnread(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function countUnread(int $userId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0'
        );
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public function markRead(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?'
        );
        return $stmt->execute([$id, $userId]);
    }

    public function markAllRead(int $userId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE notifications SET is_read = 1 WHERE user_id = ?'
        );
        return $stmt->execute([$userId]);
    }
}
