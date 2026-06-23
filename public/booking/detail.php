<?php
/**
 * Halaman Detail Booking
 * Fase 3 - Booking + FCFS
 */

defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/models/Booking.php';
require_once BASE_PATH . '/app/models/Payment.php';

startSession();
requireLogin();

$user          = currentUser();
$bookingModel  = new Booking();
$bookingModel->cancelExpiredBookings();

$id      = (int) ($_GET['id'] ?? 0);
$booking = $bookingModel->findById($id);

// Pastikan booking ada dan milik user ini (atau admin)
if (!$booking) {
    setFlash('error', 'Booking tidak ditemukan.');
    redirect(baseUrl('user/dashboard.php'));
}
if (!isAdmin() && (int)$booking['user_id'] !== (int)$user['id']) {
    setFlash('error', 'Anda tidak memiliki akses ke booking ini.');
    redirect(baseUrl('user/dashboard.php'));
}

$paymentModel = new Payment();
$payment      = $paymentModel->findByBookingId($id);

// Status label & warna badge
$statusLabel = labelStatus($booking['status']);

$pageTitle = 'Detail Booking - ' . e($booking['kode_booking']);
require_once BASE_PATH . '/views/partials/header.php';
require_once BASE_PATH . '/views/partials/navbar.php';
?>

<div class="container py-5" style="max-width:760px">

  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= baseUrl('/') ?>" class="text-rose">Beranda</a></li>
      <li class="breadcrumb-item">
        <a href="<?= baseUrl(isAdmin() ? 'admin/bookings.php' : 'user/dashboard.php') ?>" class="text-rose">
          <?= isAdmin() ? 'Manajemen Booking' : 'Dashboard' ?>
        </a>
      </li>
      <li class="breadcrumb-item active">Detail Booking</li>
    </ol>
  </nav>

  <?php renderFlash(); ?>

  <!-- Header Card -->
  <div class="card border-0 shadow-sm rounded-rose mb-4">
    <div class="card-body p-4">
      <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
        <div>
          <h4 class="fw-bold mb-1" style="font-family:var(--font-serif)">
            <?= e($booking['kode_booking']) ?>
          </h4>
          <p class="text-muted small mb-0">
            Dibuat: <?= formatTanggal($booking['created_at']) ?>
            &middot; <?= date('H:i', strtotime($booking['created_at'])) ?> WIB
          </p>
        </div>
        <span class="badge badge-<?= e($booking['status']) ?> px-3 py-2 fs-6">
          <?= e($statusLabel) ?>
        </span>
      </div>
    </div>
  </div>

  <div class="row g-4">

    <!-- Kolom Kiri: Info Booking -->
    <div class="col-md-7">

      <!-- Detail Layanan -->
      <div class="card border-0 shadow-sm rounded-rose mb-4">
        <div class="card-body p-4">
          <h6 class="fw-semibold mb-3 text-rose"><i class="bi bi-stars me-1"></i>Detail Layanan</h6>
          <table class="table table-sm table-borderless mb-0">
            <tbody>
              <tr>
                <td class="text-muted" style="width:40%">Jenis Makeup</td>
                <td class="fw-medium"><?= e($booking['jenis_nama']) ?></td>
              </tr>
              <tr>
                <td class="text-muted">Tanggal</td>
                <td class="fw-medium"><?= formatTanggal($booking['tanggal']) ?></td>
              </tr>
              <tr>
                <td class="text-muted">Slot Waktu</td>
                <td class="fw-medium"><?= e($booking['jam_label']) ?> WIB</td>
              </tr>
              <tr>
                <td class="text-muted">Tipe Layanan</td>
                <td class="fw-medium">
                  <?php if ($booking['tipe_layanan'] === 'home_service'): ?>
                    <span class="badge bg-info text-dark"><i class="bi bi-house-heart me-1"></i>Home Service</span>
                  <?php else: ?>
                    <span class="badge bg-secondary"><i class="bi bi-building me-1"></i>Studio</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php if ($booking['tipe_layanan'] === 'home_service' && $booking['alamat_lengkap']): ?>
              <tr>
                <td class="text-muted">Alamat</td>
                <td class="fw-medium">
                  <?= e($booking['alamat_lengkap']) ?><br>
                  <?= e($booking['kota']) ?>, <?= e($booking['provinsi']) ?>
                </td>
              </tr>
              <?php endif; ?>
              <?php if ($booking['catatan_user']): ?>
              <tr>
                <td class="text-muted">Catatan</td>
                <td><?= e($booking['catatan_user']) ?></td>
              </tr>
              <?php endif; ?>
              <?php if ($booking['catatan_admin']): ?>
              <tr>
                <td class="text-muted">Catatan Admin</td>
                <td class="text-warning fw-medium"><?= e($booking['catatan_admin']) ?></td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Info User (hanya admin) -->
      <?php if (isAdmin()): ?>
      <div class="card border-0 shadow-sm rounded-rose mb-4">
        <div class="card-body p-4">
          <h6 class="fw-semibold mb-3 text-rose"><i class="bi bi-person me-1"></i>Data Pelanggan</h6>
          <table class="table table-sm table-borderless mb-0">
            <tbody>
              <tr>
                <td class="text-muted" style="width:40%">Nama</td>
                <td class="fw-medium"><?= e($booking['user_name']) ?></td>
              </tr>
              <tr>
                <td class="text-muted">Email</td>
                <td><?= e($booking['user_email']) ?></td>
              </tr>
              <tr>
                <td class="text-muted">No. WA</td>
                <td>
                  <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $booking['user_phone']) ?>" target="_blank"
                     class="text-success text-decoration-none">
                    <i class="bi bi-whatsapp me-1"></i><?= e($booking['user_phone']) ?>
                  </a>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

    </div>

    <!-- Kolom Kanan: Biaya & Pembayaran -->
    <div class="col-md-5">

      <!-- Ringkasan Biaya -->
      <div class="card border-0 shadow-sm rounded-rose mb-4">
        <div class="card-body p-4">
          <h6 class="fw-semibold mb-3 text-rose"><i class="bi bi-receipt me-1"></i>Ringkasan Biaya</h6>
          <div class="d-flex justify-content-between mb-2 small">
            <span class="text-muted">Harga Jasa</span>
            <span class="fw-medium"><?= formatRupiah((float)$booking['harga_jasa']) ?></span>
          </div>
          <div class="d-flex justify-content-between mb-2 small">
            <span class="text-muted">Biaya Transport</span>
            <span class="fw-medium">
              <?= $booking['biaya_transport'] > 0
                ? formatRupiah((float)$booking['biaya_transport'])
                : '<span class="text-success">Gratis</span>' ?>
            </span>
          </div>
          <hr class="my-2">
          <div class="d-flex justify-content-between mb-2">
            <span class="fw-semibold">Total Biaya</span>
            <span class="fw-bold text-rose"><?= formatRupiah((float)$booking['total_biaya']) ?></span>
          </div>
          <div class="d-flex justify-content-between mb-2 small">
            <span class="text-muted">DP (30%)</span>
            <span class="fw-bold text-rose"><?= formatRupiah((float)$booking['dp_amount']) ?></span>
          </div>
          <div class="d-flex justify-content-between small">
            <span class="text-muted">Pelunasan (70%, hari H)</span>
            <span class="text-muted"><?= formatRupiah((float)$booking['pelunasan_amount']) ?></span>
          </div>
        </div>
      </div>

      <!-- Status Pembayaran -->
      <div class="card border-0 shadow-sm rounded-rose mb-4">
        <div class="card-body p-4">
          <h6 class="fw-semibold mb-3 text-rose"><i class="bi bi-credit-card me-1"></i>Status Pembayaran DP</h6>
          <?php if ($payment): ?>
            <div class="d-flex justify-content-between mb-2 small">
              <span class="text-muted">Order ID</span>
              <span class="fw-medium"><?= e($payment['order_id']) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2 small">
              <span class="text-muted">Jumlah</span>
              <span class="fw-bold text-rose"><?= formatRupiah((float)$payment['amount']) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2 small">
              <span class="text-muted">Status</span>
              <span class="badge
                <?php
                  $pc = ['pending'=>'bg-warning text-dark','success'=>'bg-success',
                         'failed'=>'bg-danger','expired'=>'bg-secondary','cancelled'=>'bg-secondary'];
                  echo $pc[$payment['status']] ?? 'bg-secondary';
                ?>">  <?= ucfirst($payment['status']) ?>
              </span>
            </div>
            <?php if ($payment['paid_at']): ?>
            <div class="d-flex justify-content-between small">
              <span class="text-muted">Dibayar pada</span>
              <span><?= date('d/m/Y H:i', strtotime($payment['paid_at'])) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($payment['expired_at'] && $payment['status'] === 'pending'): ?>
            <div class="alert alert-warning mt-3 mb-0 small p-2">
              <i class="bi bi-clock me-1"></i>
              Batas bayar: <strong><?= date('d/m/Y H:i', strtotime($payment['expired_at'])) ?> WIB</strong>
            </div>
            <?php endif; ?>
          <?php else: ?>
            <p class="text-muted small mb-0">Belum ada data pembayaran.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Tombol Aksi -->
      <div class="d-grid gap-2">
        <?php if (!isAdmin() && $booking['status'] === 'waiting_payment'): ?>
          <a href="<?= baseUrl('payment/process.php?booking_id=' . $booking['id']) ?>"
             class="btn btn-rose">
            <i class="bi bi-credit-card me-1"></i>Bayar DP Sekarang
          </a>
        <?php endif; ?>

        <?php if (isAdmin() && $booking['status'] === 'waiting_confirmation'): ?>
          <a href="<?= baseUrl('admin/bookings.php?action=confirm&id=' . $booking['id']) ?>"
             class="btn btn-success">
            <i class="bi bi-check-circle me-1"></i>Konfirmasi Booking
          </a>
          <a href="<?= baseUrl('admin/bookings.php?action=reject&id=' . $booking['id']) ?>"
             class="btn btn-outline-danger">
            <i class="bi bi-x-circle me-1"></i>Tolak Booking
          </a>
        <?php endif; ?>

        <!-- Tombol WhatsApp Admin -->
        <?php if (!isAdmin()): ?>
        <a href="https://wa.me/6281234567890?text=Halo+Quemil+Makeup,+saya+ingin+bertanya+tentang+booking+<?= urlencode($booking['kode_booking']) ?>"
           target="_blank" class="btn btn-outline-success">
          <i class="bi bi-whatsapp me-1"></i>Chat Admin
        </a>
        <?php endif; ?>

        <a href="<?= baseUrl(isAdmin() ? 'admin/bookings.php' : 'user/dashboard.php') ?>"
           class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
      </div>

    </div>
  </div>
</div>

<?php require_once BASE_PATH . '/views/partials/footer.php'; ?>
