<?php
/**
 * Payment Callback - Update status setelah onSuccess dari Midtrans Snap
 * Digunakan untuk development (localhost) saat webhook tidak bisa diakses
 * Di production, status diupdate via webhook notification.php
 *
 * SECURITY: Status pembayaran diverifikasi langsung ke Midtrans API
 * menggunakan order_id dari database — bukan dari parameter URL.
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
$status    = sanitize($_GET['status']     ?? '');

if (!$bookingId || !$status) {
    redirect(baseUrl('user/dashboard.php'));
}

$bookingModel = new Booking();
$paymentModel = new Payment();
$notifModel   = new Notification();

$booking = $bookingModel->findById($bookingId);
if (!$booking || (int)$booking['user_id'] !== (int)(currentUser()['id'])) {
    redirect(baseUrl('user/dashboard.php'));
}

$payment = $paymentModel->findByBookingId($bookingId);

if ($status === 'success' && $payment && $payment['status'] !== 'success') {

    // ============================================================
    // Verifikasi status pembayaran langsung ke Midtrans API
    // Gunakan order_id dari DATABASE, bukan dari URL parameter
    // Ini mencegah user memalsukan status dengan manipulasi URL
    // ============================================================
    $midtransApiBase = MIDTRANS_IS_PRODUCTION
        ? 'https://api.midtrans.com/v2'
        : 'https://api.sandbox.midtrans.com/v2';

    $verifyUrl = $midtransApiBase . '/' . urlencode($payment['order_id']) . '/status';
    $authHeader = 'Authorization: Basic ' . base64_encode(MIDTRANS_SERVER_KEY . ':');

    $ch = curl_init($verifyUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [$authHeader, 'Accept: application/json'],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response   = curl_exec($ch);
    $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError  = curl_error($ch);
    curl_close($ch);

    // Jika tidak bisa verifikasi ke Midtrans (misal: localhost tanpa internet),
    // fallback ke status dari URL hanya di environment development
    if ($response === false || $httpCode !== 200) {
        if (APP_ENV === 'development') {
            // Fallback development: percaya parameter URL tapi catat di log
            error_log("[callback.php] Midtrans verify gagal (curl: {$curlError}, http: {$httpCode}). Fallback development untuk booking #{$bookingId}.");
            $verifiedStatus = 'settlement';
            $metode         = sanitize($_GET['payment_type'] ?? 'midtrans_snap');
        } else {
            // Production: tolak jika tidak bisa verifikasi
            setFlash('error', 'Gagal memverifikasi pembayaran. Silakan hubungi admin.');
            redirect(baseUrl('booking/detail.php?id=' . $bookingId));
        }
    } else {
        $mtData         = json_decode($response, true);
        $verifiedStatus = $mtData['transaction_status'] ?? '';
        $fraudStatus    = $mtData['fraud_status']       ?? '';
        $metode         = $mtData['payment_type']       ?? 'midtrans_snap';

        // capture + fraud check
        if ($verifiedStatus === 'capture') {
            $verifiedStatus = ($fraudStatus === 'accept') ? 'settlement' : 'deny';
        }
    }

    // Hanya proses jika Midtrans mengkonfirmasi pembayaran sukses
    if (!in_array($verifiedStatus, ['settlement', 'capture'])) {
        if (in_array($verifiedStatus, ['pending', 'authorize'])) {
            setFlash('info', 'Pembayaran sedang diproses. Status booking akan diperbarui otomatis.');
        } else {
            setFlash('error', 'Pembayaran tidak berhasil dikonfirmasi. Silakan coba lagi.');
        }
        redirect(baseUrl('booking/detail.php?id=' . $bookingId));
    }

    // Update payment status (sudah terverifikasi dari Midtrans)
    $paymentModel->updateStatus(
        $payment['order_id'],
        'success',
        $metode,
        ['transaction_status' => $verifiedStatus, 'source' => 'frontend_callback_verified']
    );

    // Kunci slot (FCFS)
    $locked = $bookingModel->lockSlot($bookingId);

    if ($locked) {
        $notifModel->create(
            (int) $booking['user_id'],
            'Pembayaran DP Berhasil - Booking Terkonfirmasi',
            'Pembayaran DP untuk booking ' . $booking['kode_booking'] .
            ' berhasil. Booking Anda telah terkonfirmasi. Sampai jumpa di hari H!',
            $bookingId
        );
        setFlash('success', 'Pembayaran DP berhasil! Booking Anda telah terkonfirmasi.');
    } else {
        // Slot kalah FCFS
        $bookingModel->updateStatus($bookingId, 'cancelled',
            'Slot sudah diambil pelanggan lain (FCFS). Refund DP akan diproses 1-3 hari kerja atau silahkan hubungi admin.'
        );
        $notifModel->create(
            (int) $booking['user_id'],
            'Booking Dibatalkan - Slot Terpakai',
            'Maaf, slot booking ' . $booking['kode_booking'] .
            ' sudah diambil pelanggan lain. Refund DP diproses 1-3 hari kerja atau silahkan hubungi admin.',
            $bookingId
        );
        setFlash('warning', 'Pembayaran diterima, namun slot sudah diambil pelanggan lain. Refund akan diproses.');
    }

} elseif ($status === 'pending') {
    setFlash('info', 'Pembayaran sedang diproses. Status booking akan diperbarui otomatis.');

} elseif ($status === 'error') {
    setFlash('error', 'Pembayaran gagal. Silakan coba lagi.');
}

redirect(baseUrl('booking/detail.php?id=' . $bookingId));
