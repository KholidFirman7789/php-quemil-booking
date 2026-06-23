<?php
/**
 * Payment Finish - Redirect setelah pembayaran Midtrans
 * Fase 4 - Pembayaran Midtrans
 */

defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/app/helpers/functions.php';

startSession();
requireLogin();

$bookingId = (int) ($_GET['booking_id'] ?? 0);
$status    = sanitize($_GET['status'] ?? 'pending');

if ($status === 'success') {
    setFlash('success', 'Pembayaran DP berhasil! Booking Anda sedang menunggu konfirmasi admin.');
} elseif ($status === 'pending') {
    setFlash('info', 'Pembayaran sedang diproses. Kami akan memperbarui status booking Anda.');
} else {
    setFlash('error', 'Pembayaran gagal atau dibatalkan.');
}

redirect(baseUrl('booking/detail.php?id=' . $bookingId));
