<?php
/**
 * Midtrans Webhook Notification Handler
 * Fase 4 - Pembayaran Midtrans
 *
 * Endpoint ini dipanggil oleh server Midtrans secara otomatis
 * setelah pembayaran selesai (sukses/gagal/expired).
 * Daftarkan URL ini di dashboard Midtrans:
 * Settings > Configuration > Payment Notification URL
 * URL: {APP_URL}/payment/notification.php
 */

defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/models/Booking.php';
require_once BASE_PATH . '/app/models/Payment.php';
require_once BASE_PATH . '/app/models/Notification.php';

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Ambil payload JSON dari Midtrans
$payload = json_decode(file_get_contents('php://input'), true);

if (!$payload || empty($payload['order_id'])) {
    http_response_code(400);
    exit('Bad Request');
}

// ============================================================
// Verifikasi Signature Key Midtrans
// Format: SHA512(order_id + status_code + gross_amount + server_key)
// ============================================================
$signatureInput = $payload['order_id'] .
                  ($payload['status_code']   ?? '') .
                  ($payload['gross_amount']  ?? '') .
                  MIDTRANS_SERVER_KEY;

$expectedSignature = hash('sha512', $signatureInput);
$receivedSignature = $payload['signature_key'] ?? '';

if (!hash_equals($expectedSignature, $receivedSignature)) {
    http_response_code(403);
    exit('Invalid signature');
}

// ============================================================
// Tentukan status payment berdasarkan notifikasi Midtrans
// ============================================================
$transactionStatus = $payload['transaction_status'] ?? '';
$fraudStatus       = $payload['fraud_status']       ?? '';
$orderId           = $payload['order_id'];
$metode            = $payload['payment_type']       ?? null;

if ($transactionStatus === 'capture') {
    $paymentStatus = ($fraudStatus === 'accept') ? 'success' : 'failed';
} elseif ($transactionStatus === 'settlement') {
    $paymentStatus = 'success';
} elseif (in_array($transactionStatus, ['cancel', 'deny'])) {
    $paymentStatus = 'failed';
} elseif ($transactionStatus === 'expire') {
    $paymentStatus = 'expired';
} else {
    // pending atau status lain: tidak perlu aksi
    http_response_code(200);
    exit('OK');
}

// ============================================================
// Update database
// ============================================================
$paymentModel = new Payment();
$bookingModel = new Booking();
$notifModel   = new Notification();

$payment = $paymentModel->findByOrderId($orderId);

if (!$payment) {
    http_response_code(404);
    exit('Payment not found');
}

// Hindari proses ulang jika sudah success
if ($payment['status'] === 'success') {
    http_response_code(200);
    exit('Already processed');
}

// Update status payment
$paymentModel->updateStatus($orderId, $paymentStatus, $metode, $payload);

$bookingId = (int) $payment['booking_id'];
$booking   = $bookingModel->findById($bookingId);

if (!$booking) {
    http_response_code(200);
    exit('OK');
}

// ============================================================
// Aksi berdasarkan status
// ============================================================
if ($paymentStatus === 'success') {
    // Kunci slot (FCFS + slot locking dalam transaksi DB)
    $locked = $bookingModel->lockSlot($bookingId);

    if ($locked) {
        // Slot berhasil dikunci: kirim notifikasi konfirmasi ke user
        $notifModel->create(
            (int) $booking['user_id'],
            'Pembayaran DP Berhasil',
            'Pembayaran DP untuk booking ' . $booking['kode_booking'] .
            ' berhasil. Booking Anda sedang menunggu konfirmasi admin.',
            $bookingId
        );
    } else {
        // Slot sudah diambil booking lain (FCFS kalah)
        // Batalkan booking ini dan refund akan diproses manual
        $bookingModel->updateStatus($bookingId, 'cancelled',
            'Slot sudah diambil oleh pelanggan lain yang memesan lebih awal (FCFS). ' .
            'Silakan pilih slot lain. Refund DP akan diproses dalam 1-3 hari kerja.'
        );
        $notifModel->create(
            (int) $booking['user_id'],
            'Booking Dibatalkan - Slot Terpakai',
            'Maaf, slot waktu booking ' . $booking['kode_booking'] .
            ' sudah diambil pelanggan lain. ' .
            'Refund DP akan diproses dalam 1-3 hari kerja.',
            $bookingId
        );
    }

} elseif (in_array($paymentStatus, ['failed', 'expired'])) {
    // Batalkan booking, bebaskan slot
    $bookingModel->updateStatus($bookingId, 'cancelled',
        'Pembayaran ' . $paymentStatus . '.'
    );
    $notifModel->create(
        (int) $booking['user_id'],
        'Pembayaran ' . ($paymentStatus === 'expired' ? 'Kedaluwarsa' : 'Gagal'),
        'Pembayaran DP untuk booking ' . $booking['kode_booking'] .
        ($paymentStatus === 'expired'
            ? ' telah kedaluwarsa. Booking dibatalkan otomatis.'
            : ' gagal. Booking dibatalkan.'),
        $bookingId
    );
}

http_response_code(200);
echo 'OK';
