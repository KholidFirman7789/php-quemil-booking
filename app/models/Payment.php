<?php
/**
 * Model: Payment
 */
defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/database.php';

class Payment
{
    private PDO $db;

    public function __construct()
    {
        $this->db = db();
    }

    public function create(int $bookingId, string $orderId, float $amount): int
    {
        $expiredAt = date('Y-m-d H:i:s', strtotime('+' . PAYMENT_EXPIRED_HOURS . ' hours'));
        $stmt = $this->db->prepare(
            "INSERT INTO payments (booking_id, order_id, amount, status, expired_at)
             VALUES (?, ?, ?, 'pending', ?)"
        );
        $stmt->execute([$bookingId, $orderId, $amount, $expiredAt]);
        return (int) $this->db->lastInsertId();
    }

    public function findByBookingId(int $bookingId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM payments WHERE booking_id = ? LIMIT 1');
        $stmt->execute([$bookingId]);
        return $stmt->fetch() ?: null;
    }

    public function findByOrderId(string $orderId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM payments WHERE order_id = ? LIMIT 1');
        $stmt->execute([$orderId]);
        return $stmt->fetch() ?: null;
    }

    public function updateStatus(
        string  $orderId,
        string  $status,
        ?string $metode      = null,
        ?array  $rawResponse = null
    ): bool {
        $paidAt = ($status === 'success') ? date('Y-m-d H:i:s') : null;
        $stmt   = $this->db->prepare(
            'UPDATE payments SET status = ?, metode = ?, midtrans_response = ?, paid_at = ? WHERE order_id = ?'
        );
        return $stmt->execute([
            $status,
            $metode,
            $rawResponse ? json_encode($rawResponse) : null,
            $paidAt,
            $orderId,
        ]);
    }

    public function saveMidtransToken(int $paymentId, string $token): bool
    {
        $stmt = $this->db->prepare('UPDATE payments SET midtrans_token = ? WHERE id = ?');
        return $stmt->execute([$token, $paymentId]);
    }
}
