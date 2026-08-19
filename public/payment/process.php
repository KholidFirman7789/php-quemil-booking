<?php
/**
 * Payment Process - Generate Midtrans Snap Token
 * Fase 4 - Pembayaran Midtrans
 */

defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/models/Booking.php';
require_once BASE_PATH . '/app/models/Payment.php';
require_once BASE_PATH . '/app/models/User.php';
require_once BASE_PATH . '/app/models/Notification.php';

startSession();
requireLogin();

$user         = currentUser();
$bookingModel = new Booking();
$paymentModel = new Payment();

$bookingId = (int) ($_GET['booking_id'] ?? 0);
$booking   = $bookingModel->findById($bookingId);

// Validasi akses
if (!$booking) {
    setFlash('error', 'Booking tidak ditemukan.');
    redirect(baseUrl('user/dashboard.php'));
}
if ((int)$booking['user_id'] !== (int)$user['id']) {
    setFlash('error', 'Akses tidak diizinkan.');
    redirect(baseUrl('user/dashboard.php'));
}
if ($booking['status'] !== 'waiting_payment') {
    setFlash('info', 'Booking ini tidak memerlukan pembayaran saat ini.');
    redirect(baseUrl('booking/detail.php?id=' . $bookingId));
}

// Cek apakah sudah ada payment record
$existingPayment = $paymentModel->findByBookingId($bookingId);

if ($existingPayment && $existingPayment['status'] === 'success') {
    setFlash('info', 'Pembayaran sudah berhasil.');
    redirect(baseUrl('booking/detail.php?id=' . $bookingId));
}

// Generate order_id unik
$orderId = 'QB-' . $bookingId . '-' . time();

// Jika sudah ada payment pending, gunakan token yang ada
if ($existingPayment && $existingPayment['status'] === 'pending' && $existingPayment['midtrans_token']) {
    $snapToken = $existingPayment['midtrans_token'];
    $orderId   = $existingPayment['order_id'];
} else {
    // Buat Snap Token baru via Midtrans API
    $payload = [
        'transaction_details' => [
            'order_id'     => $orderId,
            'gross_amount' => (int) $booking['dp_amount'],
        ],
        'customer_details' => [
            'first_name' => $booking['user_name'],
            'email'      => $booking['user_email'],
            'phone'      => $booking['user_phone'],
        ],
        'item_details' => [
            [
                'id'       => 'DP-' . $bookingId,
                'price'    => (int) $booking['dp_amount'],
                'quantity' => 1,
                'name'     => substr('DP ' . $booking['kode_booking'], 0, 50),
            ]
        ],
        'expiry' => [
            'unit'     => 'hours',
            'duration' => PAYMENT_EXPIRED_HOURS,
        ],
    ];
  // define('MIDTRANS_API_URL',
  //     MIDTRANS_IS_PRODUCTION
  //         ? 'https://app.midtrans.com/snap/v1/transactions'
  //         : 'https://app.sandbox.midtrans.com/snap/v1/transactions'
  // );

    $auth = base64_encode(MIDTRANS_SERVER_KEY . ':');
    $ch   = curl_init(MIDTRANS_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . $auth,
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);

    $response     = curl_exec($ch);
    $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError    = curl_error($ch);
    curl_close($ch);

    if ($curlError || $httpCode !== 201) {
        if (APP_ENV === 'development') {
            setFlash('error', 'Gagal menghubungi Midtrans: HTTP ' . $httpCode . ' - ' . $curlError . ' | ' . $response);
        } else {
            setFlash('error', 'Gagal memproses pembayaran. Silakan coba beberapa saat lagi.');
        }
        redirect(baseUrl('booking/detail.php?id=' . $bookingId));
    }

    $result    = json_decode($response, true);
    $snapToken = $result['token'] ?? null;

    if (!$snapToken) {
        setFlash('error', 'Gagal mendapatkan token pembayaran.');
        redirect(baseUrl('booking/detail.php?id=' . $bookingId));
    }

    // Simpan payment record
    if ($existingPayment) {
        // Update token jika record sudah ada
        $paymentModel->saveMidtransToken($existingPayment['id'], $snapToken);
    } else {
        $paymentId = $paymentModel->create($bookingId, $orderId, (float)$booking['dp_amount']);
        $paymentModel->saveMidtransToken($paymentId, $snapToken);
    }
}

$pageTitle = 'Pembayaran DP';
require_once BASE_PATH . '/views/partials/header.php';
require_once BASE_PATH . '/views/partials/navbar.php';
?>

<div class="container py-5" style="max-width:640px">

  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= baseUrl('/') ?>" class="text-rose">Beranda</a></li>
      <li class="breadcrumb-item"><a href="<?= baseUrl('user/dashboard.php') ?>" class="text-rose">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="<?= baseUrl('booking/detail.php?id=' . $bookingId) ?>" class="text-rose">Detail Booking</a></li>
      <li class="breadcrumb-item active">Pembayaran DP</li>
    </ol>
  </nav>

  <div class="text-center mb-4">
    <h4 class="section-title">Pembayaran Down Payment</h4>
    <div class="section-divider"></div>
  </div>

  <?php renderFlash(); ?>

  <!-- Ringkasan Booking -->
  <div class="card border-0 shadow-sm rounded-rose mb-4">
    <div class="card-body p-4">
      <h6 class="fw-semibold mb-3 text-rose"><i class="bi bi-receipt me-1"></i>Ringkasan Pembayaran</h6>
      <table class="table table-sm table-borderless mb-0">
        <tr>
          <td class="text-muted" style="width:45%">Kode Booking</td>
          <td class="fw-bold"><?= e($booking['kode_booking']) ?></td>
        </tr>
        <tr>
          <td class="text-muted">Jenis Makeup</td>
          <td><?= e($booking['jenis_nama']) ?></td>
        </tr>
        <tr>
          <td class="text-muted">Tanggal</td>
          <td><?= formatTanggal($booking['tanggal']) ?></td>
        </tr>
        <tr>
          <td class="text-muted">Slot Waktu</td>
          <td><?= e($booking['jam_label']) ?> WIB</td>
        </tr>
        <tr>
          <td class="text-muted">Total Biaya</td>
          <td><?= formatRupiah((float)$booking['total_biaya']) ?></td>
        </tr>
        <?php if ((float)$booking['biaya_transport'] > 0): ?>
        <tr>
          <td class="text-muted small">— Harga Jasa</td>
          <td class="small"><?= formatRupiah((float)$booking['harga_jasa']) ?></td>
        </tr>
        <tr>
          <td class="text-muted small">— Biaya Transport</td>
          <td class="small"><?= formatRupiah((float)$booking['biaya_transport']) ?></td>
        </tr>
        <?php endif; ?>
        <tr class="border-top">
          <td class="fw-semibold">
            DP yang dibayar
            <?php if ((float)$booking['biaya_transport'] > 0): ?>
            <div class="text-muted fw-normal small">makeup 30% + transport 100%</div>
            <?php else: ?>
            <div class="text-muted fw-normal small">30% dari total</div>
            <?php endif; ?>
          </td>
          <td class="fw-bold text-rose fs-5"><?= formatRupiah((float)$booking['dp_amount']) ?></td>
        </tr>
      </table>
    </div>
  </div>

  <!-- Tombol Bayar -->
  <div class="card border-0 shadow-sm rounded-rose mb-4">
    <div class="card-body p-4 text-center">
      <p class="text-muted small mb-3">
        <i class="bi bi-shield-check text-success me-1"></i>
        Pembayaran diproses secara aman melalui <strong>Midtrans</strong>.
        Tersedia transfer bank, GoPay, QRIS, dan metode lainnya.
      </p>
      <button id="btnBayar" class="btn btn-rose btn-lg px-5">
        <i class="bi bi-credit-card me-2"></i>Bayar DP <?= formatRupiah((float)$booking['dp_amount']) ?>
      </button>
      <div id="loadingSpinner" class="d-none mt-3">
        <div class="spinner-border text-rose" role="status"></div>
        <p class="text-muted small mt-2">Memproses pembayaran...</p>
      </div>
    </div>
  </div>

  <div class="text-center">
    <a href="<?= baseUrl('booking/detail.php?id=' . $bookingId) ?>" class="text-muted small">
      <i class="bi bi-arrow-left me-1"></i>Kembali ke detail booking
    </a>
  </div>

</div>

<!-- Midtrans Snap.js -->
<script src="<?= MIDTRANS_SNAP_URL ?>" data-client-key="<?= MIDTRANS_CLIENT_KEY ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('btnBayar').addEventListener('click', function () {
  var btn     = this;
  var spinner = document.getElementById('loadingSpinner');

  btn.disabled     = true;
  spinner.classList.remove('d-none');

  window.snap.pay('<?= $snapToken ?>', {
    onSuccess: function (result) {
      // Callback langsung update status (untuk localhost / sandbox)
      window.location.href = '<?= baseUrl('payment/callback.php?booking_id=' . $bookingId) ?>'
        + '&status=success'
        + '&order_id=' + encodeURIComponent(result.order_id || '')
        + '&payment_type=' + encodeURIComponent(result.payment_type || '');
    },
    onPending: function (result) {
      window.location.href = '<?= baseUrl('payment/callback.php?booking_id=' . $bookingId) ?>'
        + '&status=pending'
        + '&order_id=' + encodeURIComponent(result.order_id || '');
    },
    onError: function (result) {
      btn.disabled = false;
      spinner.classList.add('d-none');
      window.location.href = '<?= baseUrl('payment/callback.php?booking_id=' . $bookingId) ?>'
        + '&status=error';
    },
    onClose: function () {
      btn.disabled = false;
      spinner.classList.add('d-none');
    }
  });
});
</script>
</body>
</html>
