<?php
/**
 * Dashboard User
 * Fase 5
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

$user         = currentUser();
$bookingModel = new Booking();
$notifModel   = new Notification();

// Auto-cancel expired
$bookingModel->cancelExpiredBookings();

// Handle mark notif as read
if (isset($_GET['read_notif'])) {
    $notifModel->markRead((int)$_GET['read_notif'], $user['id']);
    redirect(baseUrl('user/dashboard.php'));
}

// Handle mark all read
if (isset($_GET['read_all'])) {
    $notifModel->markAllRead($user['id']);
    redirect(baseUrl('user/dashboard.php'));
}

// Ambil data
$bookings      = $bookingModel->getByUser($user['id']);
$counts        = $bookingModel->countByStatus($user['id']);
$notifications = $notifModel->getByUser($user['id'], 5);
$unreadCount   = $notifModel->countUnread($user['id']);

// Hitung statistik
$totalBooking    = array_sum($counts);
$menunggu        = ($counts['pending'] ?? 0)
                 + ($counts['pending_approval'] ?? 0)
                 + ($counts['pending_negotiation'] ?? 0)
                 + ($counts['waiting_payment'] ?? 0)
                 + ($counts['waiting_confirmation'] ?? 0);
$terkonfirmasi   = ($counts['confirmed'] ?? 0) + ($counts['completed'] ?? 0);

$pageTitle = 'Dashboard';
require_once BASE_PATH . '/views/partials/header.php';
require_once BASE_PATH . '/views/partials/navbar.php';
?>

<div class="container py-5">

  <?php renderFlash(); ?>

  <!-- Greeting -->
  <div class="card border-0 shadow-sm rounded-rose mb-4">
    <div class="card-body p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div>
        <h4 class="fw-bold mb-1" style="font-family:var(--font-serif)">
          Halo, <?= e($user['name']) ?>!
        </h4>
        <p class="text-muted mb-0 small">Selamat datang di dashboard Quemil Makeup.</p>
      </div>
      <a href="<?= baseUrl('booking/index.php') ?>" class="btn btn-rose">
        <i class="bi bi-calendar-plus me-1"></i>Booking Sekarang
      </a>
    </div>
  </div>

  <!-- Statistik -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
      <div class="stat-card">
        <div class="d-flex align-items-center gap-3">
          <div class="stat-card-icon" style="background:#f8e6ea">
            <i class="bi bi-calendar-check" style="color:var(--rose)"></i>
          </div>
          <div>
            <div class="fw-bold fs-4"><?= $totalBooking ?></div>
            <div class="text-muted small">Total Booking</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4">
      <div class="stat-card">
        <div class="d-flex align-items-center gap-3">
          <div class="stat-card-icon" style="background:#fff3cd">
            <i class="bi bi-hourglass-split" style="color:#ffc107"></i>
          </div>
          <div>
            <div class="fw-bold fs-4"><?= $menunggu ?></div>
            <div class="text-muted small">Menunggu</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4">
      <div class="stat-card">
        <div class="d-flex align-items-center gap-3">
          <div class="stat-card-icon" style="background:#d1e7dd">
            <i class="bi bi-check-circle" style="color:#198754"></i>
          </div>
          <div>
            <div class="fw-bold fs-4"><?= $terkonfirmasi ?></div>
            <div class="text-muted small">Terkonfirmasi</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Notifikasi -->
  <?php if (!empty($notifications)): ?>
  <div class="card border-0 shadow-sm rounded-rose mb-4">
    <div class="card-body p-4">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-semibold mb-0">
          <i class="bi bi-bell me-1 text-rose"></i>Notifikasi
          <?php if ($unreadCount > 0): ?>
          <span class="badge bg-rose ms-1"><?= $unreadCount ?></span>
          <?php endif; ?>
        </h6>
        <?php if ($unreadCount > 0): ?>
        <a href="?read_all=1" class="text-muted small text-decoration-none">
          <i class="bi bi-check-all me-1"></i>Tandai semua dibaca
        </a>
        <?php endif; ?>
      </div>
      <div class="list-group list-group-flush">
        <?php foreach ($notifications as $notif): ?>
        <a href="<?= baseUrl('user/dashboard.php?read_notif=' . $notif['id']) ?>"
           class="list-group-item list-group-item-action border-0 px-0 py-2 <?= !$notif['is_read'] ? 'fw-medium' : 'text-muted' ?>">
          <div class="d-flex align-items-start gap-2">
            <div class="mt-1">
              <?php if (!$notif['is_read']): ?>
              <span class="badge rounded-pill bg-rose" style="width:8px;height:8px;padding:0">&nbsp;</span>
              <?php else: ?>
              <span style="display:inline-block;width:8px;height:8px"></span>
              <?php endif; ?>
            </div>
            <div class="flex-grow-1">
              <div class="small fw-semibold"><?= e($notif['judul']) ?></div>
              <div class="small <?= $notif['is_read'] ? 'text-muted' : '' ?>"><?= e($notif['pesan']) ?></div>
              <div class="text-muted" style="font-size:.7rem">
                <?= date('d M Y H:i', strtotime($notif['created_at'])) ?>
              </div>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Riwayat Booking -->
  <div class="card border-0 shadow-sm rounded-rose">
    <div class="card-body p-4">
      <h6 class="fw-semibold mb-4"><i class="bi bi-clock-history me-1 text-rose"></i>Riwayat Booking</h6>

      <?php if (empty($bookings)): ?>
      <!-- Empty State -->
      <div class="text-center py-5">
        <i class="bi bi-calendar-x" style="font-size:3.5rem;color:var(--rose);opacity:.4"></i>
        <p class="text-muted mt-3 mb-1">Belum ada booking.</p>
        <p class="text-muted small mb-4">Yuk, booking layanan makeup pertamamu!</p>
      <a href="<?= baseUrl('booking/pilih-layanan.php') ?>" class="btn btn-rose">
          <i class="bi bi-calendar-plus me-1"></i>Booking Sekarang
        </a>
      </div>
      <?php else: ?>

      <!-- Tabel Desktop -->
      <div class="table-responsive d-none d-md-block">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Kode</th>
              <th>Jenis Makeup</th>
              <th>Tanggal</th>
              <th>Jam</th>
              <th>Tipe</th>
              <th>Total</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bookings as $b): ?>
            <tr>
              <td><code class="small"><?= e($b['kode_booking']) ?></code></td>
              <td class="small"><?= e($b['jenis_nama']) ?></td>
              <td class="small"><?= date('d/m/Y', strtotime($b['tanggal'])) ?></td>
              <td class="small"><?= e($b['jam_label'] ?? '-') ?></td>
              <td>
                <?php if ($b['tipe_layanan'] === 'home_service'): ?>
                  <span class="badge bg-info text-dark"><i class="bi bi-house-heart me-1"></i>Home</span>
                <?php else: ?>
                  <span class="badge bg-secondary"><i class="bi bi-building me-1"></i>Studio</span>
                <?php endif; ?>
              </td>
              <td class="small fw-medium"><?= formatRupiah((float)$b['total_biaya']) ?></td>
              <td>
                <span class="badge badge-<?= e($b['status']) ?>">
                  <?= e(labelStatus($b['status'])) ?>
                </span>
              </td>
              <td>
                <div class="d-flex gap-1">
                  <a href="<?= baseUrl('booking/detail.php?id=' . $b['id']) ?>"
                     class="btn btn-sm btn-outline-secondary" title="Detail">
                    <i class="bi bi-eye"></i>
                  </a>
                  <?php if ($b['status'] === 'waiting_payment'): ?>
                  <a href="<?= baseUrl('payment/process.php?booking_id=' . $b['id']) ?>"
                     class="btn btn-sm btn-rose" title="Bayar DP">
                    <i class="bi bi-credit-card"></i>
                  </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Card Mobile -->
      <div class="d-md-none">
        <?php foreach ($bookings as $b): ?>
        <div class="card border shadow-sm rounded-rose mb-3">
          <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <code class="small"><?= e($b['kode_booking']) ?></code>
              <span class="badge badge-<?= e($b['status']) ?>">
                <?= e(labelStatus($b['status'])) ?>
              </span>
            </div>
            <div class="small mb-1"><strong><?= e($b['jenis_nama']) ?></strong></div>
            <div class="small text-muted mb-1">
              <i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y', strtotime($b['tanggal'])) ?>
              &nbsp;<i class="bi bi-clock me-1"></i><?= e($b['jam_label'] ?? '-') ?>
            </div>
            <div class="small text-muted mb-2">
              <?= $b['tipe_layanan'] === 'home_service' ? '<i class="bi bi-house-heart me-1"></i>Home Service' : '<i class="bi bi-building me-1"></i>Studio' ?>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="fw-semibold text-rose small"><?= formatRupiah((float)$b['total_biaya']) ?></span>
              <div class="d-flex gap-1">
                <a href="<?= baseUrl('booking/detail.php?id=' . $b['id']) ?>" class="btn btn-sm btn-outline-secondary">
                  <i class="bi bi-eye"></i> Detail
                </a>
                <?php if ($b['status'] === 'pending_approval' && $b['tipe_layanan'] === 'home_service'): ?>
                <span class="btn btn-sm btn-outline-warning disabled">
                  <i class="bi bi-hourglass-split"></i> Menunggu Persetujuan
                </span>
                <?php elseif ($b['status'] === 'waiting_payment'): ?>
                <a href="<?= baseUrl('payment/process.php?booking_id=' . $b['id']) ?>" class="btn btn-sm btn-rose">
                  <i class="bi bi-credit-card"></i> Bayar
                </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <?php endif; ?>
    </div>
  </div>

</div>

<?php require_once BASE_PATH . '/views/partials/footer.php'; ?>
