<?php
/**
 * Manajemen Booking - Admin
 * Fase 6
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

$bookingModel = new Booking();
$bookingModel->cancelExpiredBookings();
$user = currentUser();

// ============================================================
// Handle Aksi (konfirmasi, tolak, selesai, negosiasi)
// ============================================================
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action    = sanitize($_GET['action']);
    $bookingId = (int) $_GET['id'];
    $booking   = $bookingModel->findById($bookingId);

    if ($booking) {
        $notifModel = new Notification();

        if ($action === 'confirm' && $booking['status'] === 'waiting_confirmation') {
            $bookingModel->updateStatus($bookingId, 'confirmed');
            $notifModel->create(
                (int)$booking['user_id'],
                'Booking Dikonfirmasi',
                'Booking ' . $booking['kode_booking'] . ' telah dikonfirmasi oleh admin. Sampai jumpa di hari H!',
                $bookingId
            );
            setFlash('success', 'Booking ' . e($booking['kode_booking']) . ' berhasil dikonfirmasi.');

        } elseif ($action === 'reject') {
            $catatan = sanitize($_GET['catatan'] ?? 'Booking ditolak oleh admin.');
            $bookingModel->updateStatus($bookingId, 'cancelled', $catatan);
            // Bebaskan slot jika terkunci
            if ($booking['slot_locked']) {
                db()->prepare('UPDATE bookings SET slot_locked = 0 WHERE id = ?')->execute([$bookingId]);
            }
            $notifModel->create(
                (int)$booking['user_id'],
                'Booking Ditolak',
                'Booking ' . $booking['kode_booking'] . ' ditolak. Alasan: ' . $catatan,
                $bookingId
            );
            setFlash('warning', 'Booking ' . e($booking['kode_booking']) . ' ditolak.');

        } elseif ($action === 'complete' && $booking['status'] === 'confirmed') {
            $bookingModel->updateStatus($bookingId, 'completed');
            $notifModel->create(
                (int)$booking['user_id'],
                'Layanan Selesai',
                'Terima kasih! Layanan booking ' . $booking['kode_booking'] . ' telah selesai. Semoga puas!',
                $bookingId
            );
            setFlash('success', 'Booking ' . e($booking['kode_booking']) . ' ditandai selesai.');

        } elseif ($action === 'approve' && $booking['status'] === 'pending_approval' && $booking['tipe_layanan'] === 'home_service') {
            // Admin setujui booking home service — jika luar Jatim, input biaya transport sekaligus
            $biayaTransport = (float) ($_GET['biaya_transport'] ?? 0);
            $hargaJasa      = (float) $booking['harga_jasa'];

            if ($biayaTransport > 0) {
                // Ada biaya transport baru (home service luar Jatim) — hitung ulang total
                $biaya = hitungBiaya($hargaJasa, $biayaTransport);
                db()->prepare(
                    'UPDATE bookings SET status = ?, biaya_transport = ?, total_biaya = ?, dp_amount = ?, pelunasan_amount = ? WHERE id = ?'
                )->execute([
                    'waiting_payment',
                    $biaya['biaya_transport'],
                    $biaya['total_biaya'],
                    $biaya['dp_amount'],
                    $biaya['pelunasan_amount'],
                    $bookingId
                ]);
                $notifMsg = 'Booking ' . $booking['kode_booking'] . ' telah disetujui. Biaya transport: ' . formatRupiah($biayaTransport) . '. Silakan lakukan pembayaran DP.';
                setFlash('success', 'Booking ' . e($booking['kode_booking']) . ' disetujui. Biaya transport: ' . formatRupiah($biayaTransport));
            } else {
                // Booking studio atau home service Jatim — biaya sudah fix dari awal
                $bookingModel->updateStatus($bookingId, 'waiting_payment');
                $notifMsg = 'Booking ' . $booking['kode_booking'] . ' telah disetujui oleh admin. Silakan lakukan pembayaran DP.';
                setFlash('success', 'Booking ' . e($booking['kode_booking']) . ' berhasil disetujui.');
            }
            $notifModel->create(
                (int)$booking['user_id'],
                'Booking Disetujui',
                $notifMsg,
                $bookingId
            );
        }
    }
    redirect(baseUrl('admin/bookings.php'));
}

// Filter status
$filterStatus = sanitize($_GET['status'] ?? '');
$bookings     = $bookingModel->getAll($filterStatus ?: null, 100, 0);
$counts       = $bookingModel->countByStatus();

$statusOptions = [
    ''                      => 'Semua',
    'pending'               => 'Menunggu',
    'pending_approval'      => 'Persetujuan',
    'pending_negotiation'   => 'Negosiasi',
    'waiting_payment'       => 'Belum Bayar',
    'waiting_confirmation'  => 'Menunggu Konfirmasi',
    'confirmed'             => 'Terkonfirmasi',
    'completed'             => 'Selesai',
    'cancelled'             => 'Dibatalkan',
];

$pageTitle = 'Manajemen Booking';
require_once BASE_PATH . '/views/partials/header.php';
?>

<div class="dashboard-wrapper">
  <?php $activePage = 'bookings'; require_once BASE_PATH . '/views/partials/sidebar_admin.php'; ?>

  <div class="dashboard-content">
    <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center gap-3">
        <button class="btn-sidebar-open" title="Buka menu" aria-label="Buka menu"><i class="bi bi-list"></i></button>
        <div>
          <h4 class="fw-bold mb-0" style="font-family:var(--font-serif)">Manajemen Booking</h4>
          <p class="text-muted small mb-0">Diurutkan berdasarkan waktu pesan (FCFS)</p>
        </div>
      </div>
      <button class="btn-theme-toggle" id="themeToggleDesktop" title="Toggle dark mode" aria-label="Toggle dark mode"><i class="bi bi-moon-stars" id="themeIconDesktop"></i></button>
    </div>

    <?php renderFlash(); ?>

    <!-- Filter Status -->
    <div class="d-flex flex-wrap gap-2 mb-4">
      <?php foreach ($statusOptions as $val => $label): ?>
      <a href="<?= baseUrl('admin/bookings.php') . ($val ? '?status=' . $val : '') ?>"
         class="btn btn-sm <?= $filterStatus === $val ? 'btn-rose' : 'btn-outline-secondary' ?>">
        <?= $label ?>
        <?php if ($val && isset($counts[$val]) && $counts[$val] > 0): ?>
        <span class="badge bg-white text-dark ms-1"><?= $counts[$val] ?></span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Tabel Booking -->
    <div class="card border-0 shadow-sm rounded-rose">
      <div class="card-body p-4">
        <?php if (empty($bookings)): ?>
        <p class="text-center text-muted py-4">Tidak ada booking ditemukan.</p>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Pelanggan</th>
                <th>Jenis</th>
                <th>Tanggal</th>
                <th>Jam</th>
                <th>Tipe</th>
                <th>DP</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($bookings as $i => $b): ?>
              <tr>
                <td class="text-muted small"><?= $i + 1 ?></td>
                <td><code class="small"><?= e($b['kode_booking']) ?></code></td>
                <td>
                  <div class="small fw-medium"><?= e($b['user_name']) ?></div>
                  <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $b['user_phone']) ?>" target="_blank"
                     class="small text-success text-decoration-none">
                    <i class="bi bi-whatsapp"></i> <?= e($b['user_phone']) ?>
                  </a>
                </td>
                <td class="small"><?= e($b['jenis_nama']) ?></td>
                <td class="small"><?= date('d/m/Y', strtotime($b['tanggal'])) ?></td>
                <td class="small"><?= e($b['jam_label'] ?? '-') ?></td>
                <td>
                  <?php if ($b['tipe_layanan'] === 'home_service'): ?>
                    <span class="badge bg-info text-dark"><i class="bi bi-house-heart"></i> Home</span>
                  <?php else: ?>
                    <span class="badge bg-secondary"><i class="bi bi-building"></i> Studio</span>
                  <?php endif; ?>
                </td>
                <td class="small fw-medium text-rose"><?= formatRupiah((float)$b['dp_amount']) ?></td>
                <td>
                  <span class="badge badge-<?= e($b['status']) ?>">
                    <?= e(labelStatus($b['status'])) ?>
                  </span>
                </td>
                <td>
                  <div class="d-flex gap-1 flex-wrap">
                    <!-- Detail -->
                    <a href="<?= baseUrl('booking/detail.php?id=' . $b['id']) ?>"
                       class="btn btn-sm btn-outline-secondary" title="Detail">
                      <i class="bi bi-eye"></i>
                    </a>

                    <!-- Approve booking (hanya untuk home service) -->
                    <?php if ($b['status'] === 'pending_approval' && $b['tipe_layanan'] === 'home_service'): ?>
                    <a href="#" class="btn btn-sm btn-success" title="Setujui Booking"
                       onclick="approveBooking(<?= $b['id'] ?>, '<?= e($b['kode_booking']) ?>', '<?= $b['tipe_layanan'] ?>', '<?= e($b['provinsi'] ?? '') ?>')">
                      <i class="bi bi-check-circle"></i>
                    </a>
                    <?php endif; ?>

                    <!-- Sinkronisasi pembayaran (untuk sandbox/localhost) -->
                    <?php if ($b['status'] === 'waiting_payment'): ?>
                    <?php
                      // Ambil payment untuk cek apakah ada order_id
                      $pay = (new Payment())->findByBookingId($b['id']);
                    ?>
                    <?php if ($pay && $pay['order_id'] && $pay['status'] !== 'success'): ?>
                    <a href="<?= baseUrl('admin/sync-payment.php?booking_id=' . $b['id']) ?>"
                       class="btn btn-sm btn-outline-info" title="Sinkronisasi status pembayaran dari Midtrans">
                      <i class="bi bi-arrow-clockwise"></i>
                    </a>
                    <?php endif; ?>
                    <?php endif; ?>

                    <!-- Konfirmasi -->
                    <?php if ($b['status'] === 'waiting_confirmation'): ?>
                    <a href="<?= baseUrl('admin/bookings.php?action=confirm&id=' . $b['id']) ?>"
                       class="btn btn-sm btn-success"
                       onclick="return confirm('Konfirmasi booking <?= e($b['kode_booking']) ?>?')">
                      <i class="bi bi-check-lg"></i>
                    </a>
                    <a href="#" class="btn btn-sm btn-outline-danger"
                       onclick="rejectBooking(<?= $b['id'] ?>, '<?= e($b['kode_booking']) ?>')">
                      <i class="bi bi-x-lg"></i>
                    </a>
                    <?php endif; ?>

                    <!-- Selesai -->
                    <?php if ($b['status'] === 'confirmed'): ?>
                    <a href="<?= baseUrl('admin/bookings.php?action=complete&id=' . $b['id']) ?>"
                       class="btn btn-sm btn-primary"
                       onclick="return confirm('Tandai booking ini selesai?')">
                      <i class="bi bi-flag-fill"></i>
                    </a>
                    <?php endif; ?>

                    <!-- Negosiasi (legacy - tidak digunakan lagi) -->
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Modal Tolak Booking -->
<div class="modal fade" id="modalReject" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Tolak Booking</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small">Kode: <strong id="rejectKode"></strong></p>
        <label for="rejectCatatan" class="form-label fw-medium">Alasan Penolakan</label>
        <textarea class="form-control" id="rejectCatatan" rows="3"
                  placeholder="Tulis alasan penolakan...">Booking ditolak oleh admin.</textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-danger" id="btnRejectConfirm">Tolak Booking</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Approve Booking -->
<div class="modal fade" id="modalApprove" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Setujui Booking</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small">Kode: <strong id="approveKode"></strong></p>
        <div id="approveTransportWrapper" style="display:none">
          <div class="alert alert-info small py-2">
            <i class="bi bi-info-circle me-1"></i>
            Booking ini adalah <strong>home service luar Jawa Timur</strong>. Masukkan biaya transport sebelum menyetujui.
          </div>
          <label for="approveBiaya" class="form-label fw-medium">Biaya Transport (Rp)</label>
          <input type="number" class="form-control" id="approveBiaya" min="0" step="1000"
                 placeholder="Contoh: 150000">
          <div class="form-text">Masukkan 0 jika gratis.</div>
        </div>
        <p class="text-muted small mt-2 mb-0" id="approveNormalText">
          Booking akan disetujui dan user bisa melakukan pembayaran DP.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-success" id="btnApproveConfirm">
          <i class="bi bi-check-circle me-1"></i>Setujui Booking
        </button>
      </div>
    </div>
  </div>
</div>

<?php require_once BASE_PATH . '/views/partials/footer.php'; ?>

<script>
var rejectId = 0, approveId = 0, approveNeedsTransport = false;
var baseUrl  = '<?= baseUrl('admin/bookings.php') ?>';

function rejectBooking(id, kode) {
  rejectId = id;
  document.getElementById('rejectKode').textContent = kode;
  new bootstrap.Modal(document.getElementById('modalReject')).show();
}

function approveBooking(id, kode, tipeLayanan, provinsi) {
  approveId = id;
  document.getElementById('approveKode').textContent = kode;

  // Cek apakah home service luar Jatim (biaya transport belum diketahui)
  var jatimList = ['jawa timur', 'jatim'];
  var isHomeService = tipeLayanan === 'home_service';
  var isJatim = jatimList.indexOf(provinsi.toLowerCase().trim()) !== -1;
  approveNeedsTransport = isHomeService && !isJatim && provinsi !== '';

  document.getElementById('approveTransportWrapper').style.display = approveNeedsTransport ? 'block' : 'none';
  document.getElementById('approveNormalText').style.display = approveNeedsTransport ? 'none' : 'block';
  if (approveNeedsTransport) {
    document.getElementById('approveBiaya').value = '';
  }

  new bootstrap.Modal(document.getElementById('modalApprove')).show();
}

document.getElementById('btnRejectConfirm').addEventListener('click', function () {
  var catatan = document.getElementById('rejectCatatan').value;
  window.location.href = baseUrl + '?action=reject&id=' + rejectId + '&catatan=' + encodeURIComponent(catatan);
});

document.getElementById('btnApproveConfirm').addEventListener('click', function () {
  var url = baseUrl + '?action=approve&id=' + approveId;
  if (approveNeedsTransport) {
    var biaya = document.getElementById('approveBiaya').value || 0;
    url += '&biaya_transport=' + encodeURIComponent(biaya);
  }
  window.location.href = url;
});
</script>
