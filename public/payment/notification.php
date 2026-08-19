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
 *
 * ============================================================
 * ALUR KERJA FILE INI:
 * ============================================================
 * 1. Midtrans mengirim POST request ke endpoint ini
 * 2. Validasi: request harus POST dan payload harus valid
 * 3. Verifikasi signature SHA512 untuk memastikan request asli dari Midtrans
 * 4. Tentukan status pembayaran dari data yang dikirim Midtrans
 * 5. Cari record payment di database berdasarkan order_id
 * 6. Jika pembayaran sukses → jalankan FCFS slot locking
 *    - Berhasil kunci slot → booking dikonfirmasi, notifikasi dikirim
 *    - Gagal kunci slot (slot diambil orang lain) → booking dibatalkan, refund manual
 * 7. Jika pembayaran gagal/expired → booking dibatalkan
 * 8. Kembalikan HTTP 200 ke Midtrans sebagai tanda notifikasi diterima
 * ============================================================
 */

defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/models/Booking.php';
require_once BASE_PATH . '/app/models/Payment.php';
require_once BASE_PATH . '/app/models/Notification.php';

// ============================================================
// STEP 1: Validasi method — hanya menerima POST
// Midtrans selalu mengirim notifikasi via POST, bukan GET
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// ============================================================
// STEP 2: Ambil dan parse payload JSON dari body request
// Midtrans mengirim data dalam format JSON, bukan form data
// ============================================================
$payload = json_decode(file_get_contents('php://input'), true);

// Tolak jika payload kosong atau tidak ada order_id
if (!$payload || empty($payload['order_id'])) {
    http_response_code(400);
    exit('Bad Request');
}

// ============================================================
// STEP 3: Verifikasi Signature Key Midtrans
// Tujuan: memastikan request benar-benar dari server Midtrans,
// bukan dari pihak lain yang coba memanipulasi status pembayaran.
//
// Cara kerja:
// - Midtrans membuat hash dari: order_id + status_code + gross_amount + server_key
// - Kita buat hash yang sama dari data lokal kita
// - Bandingkan keduanya dengan hash_equals() (timing-safe, aman dari timing attack)
// - Jika tidak cocok → tolak request dengan HTTP 403
//
// Format: SHA512(order_id + status_code + gross_amount + server_key)
// ============================================================
$signatureInput = $payload['order_id'] .
                  ($payload['status_code']   ?? '') .
                  ($payload['gross_amount']  ?? '') .
                  MIDTRANS_SERVER_KEY;

$expectedSignature = hash('sha512', $signatureInput);
$receivedSignature = $payload['signature_key'] ?? '';

// hash_equals() dipakai agar aman dari timing attack
if (!hash_equals($expectedSignature, $receivedSignature)) {
    http_response_code(403);
    exit('Invalid signature');
}

// ============================================================
// STEP 4: Tentukan status pembayaran internal
// dari transaction_status yang dikirim Midtrans
//
// Mapping status Midtrans → status internal:
// - capture + fraud accept → success  (kartu kredit berhasil, lolos fraud check)
// - capture + fraud lainnya → failed  (kartu kredit berhasil tapi dicurigai fraud)
// - settlement             → success  (transfer bank / e-wallet sudah settle)
// - cancel / deny          → failed   (dibatalkan user atau ditolak bank)
// - expire                 → expired  (batas waktu pembayaran habis)
// - pending / lainnya      → abaikan, tidak perlu aksi (tunggu update berikutnya)
// ============================================================
$transactionStatus = $payload['transaction_status'] ?? '';
$fraudStatus       = $payload['fraud_status']       ?? '';
$orderId           = $payload['order_id'];
$metode            = $payload['payment_type']       ?? null; // contoh: bank_transfer, gopay, qris

if ($transactionStatus === 'capture') {
    // Khusus kartu kredit: perlu cek fraud status
    $paymentStatus = ($fraudStatus === 'accept') ? 'success' : 'failed';
} elseif ($transactionStatus === 'settlement') {
    // Transfer bank / e-wallet sudah masuk rekening Midtrans
    $paymentStatus = 'success';
} elseif (in_array($transactionStatus, ['cancel', 'deny'])) {
    // User batalkan atau bank tolak transaksi
    $paymentStatus = 'failed';
} elseif ($transactionStatus === 'expire') {
    // User tidak bayar dalam batas waktu yang ditentukan
    $paymentStatus = 'expired';
} else {
    // Status pending atau status lain yang tidak butuh aksi sekarang
    // Midtrans akan kirim notifikasi lagi saat status berubah
    http_response_code(200);
    exit('OK');
}

// ============================================================
// STEP 5: Inisialisasi model dan ambil data dari database
// ============================================================
$paymentModel = new Payment();
$bookingModel = new Booking();
$notifModel   = new Notification();

// Cari record payment berdasarkan order_id dari Midtrans
$payment = $paymentModel->findByOrderId($orderId);

if (!$payment) {
    // order_id tidak ditemukan di database — kemungkinan data tidak sinkron
    http_response_code(404);
    exit('Payment not found');
}

// ============================================================
// STEP 6: Cegah double processing
// Midtrans kadang mengirim notifikasi yang sama lebih dari sekali.
// Jika payment sudah berstatus success, abaikan notifikasi berikutnya
// agar tidak terjadi slot locking ganda atau notifikasi duplikat.
// ============================================================
if ($payment['status'] === 'success') {
    http_response_code(200);
    exit('Already processed');
}

// Update status payment di tabel payments
// Menyimpan juga metode pembayaran dan raw payload dari Midtrans
$paymentModel->updateStatus($orderId, $paymentStatus, $metode, $payload);

// Ambil data booking yang terkait dengan payment ini
$bookingId = (int) $payment['booking_id'];
$booking   = $bookingModel->findById($bookingId);

if (!$booking) {
    // Booking tidak ditemukan, tidak ada yang perlu diupdate
    http_response_code(200);
    exit('OK');
}

// ============================================================
// STEP 7: Aksi berdasarkan status pembayaran
// ============================================================
if ($paymentStatus === 'success') {

    // ----------------------------------------------------------
    // FCFS SLOT LOCKING
    // Ini adalah inti dari mekanisme First Come First Served.
    //
    // lockSlot() bekerja dalam transaksi database dengan SELECT FOR UPDATE,
    // artinya baris booking di-lock sementara, tidak bisa diakses
    // booking lain secara bersamaan (race condition aman).
    //
    // Di dalam lockSlot():
    // 1. Cek apakah ada booking lain dengan tanggal + jam yang sama
    //    yang sudah memiliki slot_locked = 1
    // 2. Jika tidak ada → UPDATE slot_locked = 1, status = 'waiting_confirmation'
    //    → booking ini MENANG, slot resmi terkunci
    // 3. Jika ada → return false
    //    → booking ini KALAH karena ada yang bayar lebih dulu (FCFS)
    // ----------------------------------------------------------
    $locked = $bookingModel->lockSlot($bookingId);

    if ($locked) {
        // Slot berhasil dikunci untuk booking ini
        // Kirim notifikasi ke user bahwa pembayaran DP diterima
        // Status booking sekarang: waiting_confirmation (menunggu konfirmasi admin)
        $notifModel->create(
            (int) $booking['user_id'],
            'Pembayaran DP Berhasil',
            'Pembayaran DP untuk booking ' . $booking['kode_booking'] .
            ' berhasil. Booking Anda sedang menunggu konfirmasi admin.',
            $bookingId
        );
    } else {
        // Slot sudah dikunci oleh booking lain yang bayar lebih dulu (FCFS kalah)
        // Booking ini dibatalkan otomatis, refund DP diproses manual oleh admin
        $bookingModel->updateStatus($bookingId, 'cancelled',
            'Slot sudah diambil oleh pelanggan lain yang memesan lebih awal (FCFS). ' .
            'Silakan pilih slot lain. Refund DP akan diproses dalam 1-3 hari kerja.'
        );
        // Kirim notifikasi ke user bahwa booking dibatalkan karena kalah FCFS
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

    // Pembayaran gagal atau kedaluwarsa
    // Booking dibatalkan dan slot dibebaskan (slot_locked tetap 0)
    // sehingga slot bisa dipesan oleh user lain
    $bookingModel->updateStatus($bookingId, 'cancelled',
        'Pembayaran ' . $paymentStatus . '.'
    );
    // Kirim notifikasi ke user sesuai penyebab pembatalan
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

// ============================================================
// STEP 8: Kembalikan HTTP 200 ke Midtrans
// Midtrans membutuhkan response 200 sebagai tanda notifikasi
// sudah diterima dan diproses. Jika tidak, Midtrans akan
// mencoba mengirim ulang notifikasi yang sama beberapa kali.
// ============================================================
http_response_code(200);
echo 'OK';
