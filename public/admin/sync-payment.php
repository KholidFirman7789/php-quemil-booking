<?php
/**
 * Admin: Sinkronisasi status pembayaran dari Midtrans API
 * Untuk development/sandbox dimana webhook tidak bisa diakses
 */

defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/models/Booking.php';
require_once BASE_PATH . '/app/models/Payment.php';
require_once BASE_PATH . '/app/models/Notification.php';

startSession();
requireAdmin();

$bookingId    = (int) ($_GET['booking_id'] ?? 0);
$bookingModel = new Booking();
$paymentModel = new Payment();
$notifModel   = new Notification();

if (!$bookingId) {
    setFlash('error', 'Booking tidak ditemukan.');
    redirect(baseUrl('admin/bookings.php'));
}

$booking = $bookingModel->findById($bookingId);
$payment = $paymentModel->findByBookingId($bookingId);

if (!$booking || !$payment) {
    setFlash('error', 'Data booking atau payment tidak ditemukan.');
    redirect(baseUrl('admin/bookings.php'));
}

if ($payment['status'] === 'success') {
    setFlash('info', 'Pembayaran sudah tercatat berhasil sebelumnya.');
    redirect(baseUrl('admin/bookings.php'));
}

// Query status ke Midtrans API
$orderId = $payment['order_id'];
$auth    = base64_encode(MIDTRANS_SERVER_KEY . ':');
$apiUrl  = MIDTRANS_IS_PRODUCTION
    ? 'https://api.midtrans.com/v2/' . $orderId . '/status'
    : 'https://api.sandbox.midtrans.com/v2/' . $orderId . '/status';

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Accept: application/json',
        'Authorization: Basic ' . $auth,
    ],
    CURLOPT_TIMEOUT        => 15,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!$response || $httpCode !== 200) {
    setFlash('error', 'Gagal menghubungi Midtrans (HTTP ' . $httpCode . '). Pastikan order_id valid.');
    redirect(baseUrl('admin/bookings.php'));
}

$result            = json_decode($response, true);
$transactionStatus = $result['transaction_status'] ?? '';
$fraudStatus       = $result['fraud_status']       ?? '';
$metode            = $result['payment_type']        ?? null;

if ($transactionStatus === 'capture') {
    $newStatus = ($fraudStatus === 'accept') ? 'success' : 'failed';
} elseif ($transactionStatus === 'settlement') {
    $newStatus = 'success';
} elseif (in_array($transactionStatus, ['cancel', 'deny'])) {
    $newStatus = 'failed';
} elseif ($transactionStatus === 'expire') {
    $newStatus = 'expired';
} else {
    setFlash('info', 'Status Midtrans: ' . $transactionStatus . '. Pembayaran belum selesai.');
    redirect(baseUrl('admin/bookings.php'));
}

// Update payment
$paymentModel->updateStatus($orderId, $newStatus, $metode, $result);

if ($newStatus === 'success') {
    $locked = $bookingModel->lockSlot($bookingId);
    if ($locked) {
        $notifModel->create(
            (int) $booking['user_id'],
            'Pembayaran DP Berhasil',
            'Pembayaran DP booking ' . $booking['kode_booking'] . ' telah dikonfirmasi. Menunggu konfirmasi admin.',
            $bookingId
        );
        setFlash('success', 'Sinkronisasi berhasil! Booking ' . $booking['kode_booking'] . ' sudah terbayar, siap dikonfirmasi.');
    } else {
        $bookingModel->updateStatus($bookingId, 'cancelled',
            'Slot sudah diambil pelanggan lain (FCFS). Refund diproses 1-3 hari kerja.'
        );
        setFlash('warning', 'Pembayaran sukses tapi slot sudah diambil pelanggan lain (FCFS).');
    }
} elseif (in_array($newStatus, ['failed', 'expired'])) {
    $bookingModel->updateStatus($bookingId, 'cancelled', 'Pembayaran ' . $newStatus . '.');
    setFlash('warning', 'Pembayaran ' . $newStatus . '. Booking dibatalkan.');
}

redirect(baseUrl('admin/bookings.php'));
