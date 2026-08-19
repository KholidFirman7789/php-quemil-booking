<?php
/**
 * Cek Status Pembayaran dari Midtrans API
 * Digunakan untuk sinkronisasi manual jika callback/webhook tidak terpanggil
 */

defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/models/Booking.php';
require_once BASE_PATH . '/app/models/Payment.php';
require_once BASE_PATH . '/app/models/Notification.php';

startSession();
requireLogin();

$bookingId = (int) ($_GET['booking_id'] ?? 0);
if (!$bookingId) redirect(baseUrl('user/dashboard.php'));

$bookingModel = new Booking();
$paymentModel = new Payment();
$notifModel   = new Notification();

$booking = $bookingModel->findById($bookingId);
if (!$booking || (int)$booking['user_id'] !== (int)(currentUser()['id'])) {
    redirect(baseUrl('user/dashboard.php'));
}

$payment = $paymentModel->findByBookingId($bookingId);
if (!$payment) {
    setFlash('error', 'Data pembayaran tidak ditemukan.');
    redirect(baseUrl('booking/detail.php?id=' . $bookingId));
}

// Jika sudah success, tidak perlu cek lagi
if ($payment['status'] === 'success') {
    setFlash('info', 'Pembayaran sudah tercatat berhasil.');
    redirect(baseUrl('booking/detail.php?id=' . $bookingId));
}

// Query status ke Midtrans API
$orderId  = $payment['order_id'];
$auth     = base64_encode(MIDTRANS_SERVER_KEY . ':');
$apiUrl   = MIDTRANS_IS_PRODUCTION
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
    setFlash('error', 'Gagal menghubungi Midtrans untuk cek status. Coba lagi.');
    redirect(baseUrl('booking/detail.php?id=' . $bookingId));
}

$result            = json_decode($response, true);
$transactionStatus = $result['transaction_status'] ?? '';
$fraudStatus       = $result['fraud_status']       ?? '';
$metode            = $result['payment_type']        ?? null;

// Tentukan status
if ($transactionStatus === 'capture') {
    $newStatus = ($fraudStatus === 'accept') ? 'success' : 'failed';
} elseif ($transactionStatus === 'settlement') {
    $newStatus = 'success';
} elseif (in_array($transactionStatus, ['cancel', 'deny'])) {
    $newStatus = 'failed';
} elseif ($transactionStatus === 'expire') {
    $newStatus = 'expired';
} else {
    // pending atau lainnya
    setFlash('info', 'Pembayaran masih dalam proses (status: ' . $transactionStatus . '). Silakan tunggu atau coba lagi.');
    redirect(baseUrl('booking/detail.php?id=' . $bookingId));
}

// Update payment
$paymentModel->updateStatus($orderId, $newStatus, $metode, $result);

if ($newStatus === 'success') {
    $locked = $bookingModel->lockSlot($bookingId);
    if ($locked) {
        $notifModel->create(
            (int) $booking['user_id'],
            'Pembayaran DP Berhasil',
            'Pembayaran DP untuk booking ' . $booking['kode_booking'] .
            ' berhasil dikonfirmasi. Menunggu konfirmasi admin.',
            $bookingId
        );
        setFlash('success', 'Pembayaran berhasil dikonfirmasi! Booking menunggu konfirmasi admin.');
    } else {
        $bookingModel->updateStatus($bookingId, 'cancelled',
            'Slot sudah diambil pelanggan lain (FCFS). Refund DP akan diproses 1-3 hari kerja.'
        );
        $notifModel->create(
            (int) $booking['user_id'],
            'Booking Dibatalkan',
            'Maaf, slot booking ' . $booking['kode_booking'] . ' sudah diambil. Refund diproses 1-3 hari kerja.',
            $bookingId
        );
        setFlash('warning', 'Pembayaran diterima, namun slot sudah diambil pelanggan lain.');
    }
} elseif (in_array($newStatus, ['failed', 'expired'])) {
    $bookingModel->updateStatus($bookingId, 'cancelled', 'Pembayaran ' . $newStatus . '.');
    $notifModel->create(
        (int) $booking['user_id'],
        'Pembayaran ' . ($newStatus === 'expired' ? 'Kedaluwarsa' : 'Gagal'),
        'Pembayaran booking ' . $booking['kode_booking'] . ' ' . $newStatus . '. Booking dibatalkan.',
        $bookingId
    );
    setFlash('error', 'Pembayaran ' . $newStatus . '. Booking dibatalkan.');
}

redirect(baseUrl('booking/detail.php?id=' . $bookingId));
