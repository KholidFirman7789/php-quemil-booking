<?php
/**
 * Dashboard Admin
 * Fase 6
 */

defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/models/Booking.php';

startSession();
requireAdmin();

$bookingModel  = new Booking();
$bookingModel->cancelExpiredBookings();

$user   = currentUser();
$counts = $bookingModel->countByStatus();
$total  = $bookingModel->countAll();

$menungguBayar   = $counts['waiting_payment']      ?? 0;
$menungguKonfirm = $counts['waiting_confirmation'] ?? 0;
$negosiasi       = $counts['pending_negotiation']  ?? 0;
$terkonfirmasi   = $counts['confirmed']            ?? 0;
$selesai         = $counts['completed']            ?? 0;
$dibatalkan      = $counts['cancelled']            ?? 0;

// 5 booking terbaru (FCFS)
$recentBookings = $bookingModel->getAll(null, 5, 0);

$pageTitle = 'Dashboard Admin';
require_once BASE_PATH . '/views/partials/header.php';
?>

<div class="dashboard-wrapper">

  <?php $activePage = 'dashboard'; require_once BASE_PATH . '/views/partials/sidebar_admin.php'; ?>

  <div class="dashboard-content">

    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center gap-3">
        <button class="btn-sidebar-open" title="Buka menu" aria-label="Buka menu">
          <i class="bi bi-list"></i>
        </button>
        <div>
          <h4 class="fw-bold mb-0" style="font-family:var(--font-serif)">Dashboard Admin</h4>
          <p class="text-muted small mb-0">Sistem Informasi Booking Quemil Makeup</p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="text-muted small d-none d-md-block">Halo, <strong><?= e($user['name']) ?></strong></span>
      </div>
    </div>

    <?php renderFlash(); ?>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-xl-3">
        <div class="stat-card">
          <div class="d-flex align-items-center gap-3">
            <div class="stat-card-icon" style="background:#e8f4fd">
              <i class="bi bi-calendar-check" style="color:#0d6efd"></i>
            </div>
            <div>
              <div class="fw-bold fs-4"><?= $total ?></div>
              <div class="text-muted small">Total Booking</div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-6 col-xl-3">
        <div class="stat-card">
          <div class="d-flex align-items-center gap-3">
            <div class="stat-card-icon" style="background:#fff3cd">
              <i class="bi bi-credit-card" style="color:#ffc107"></i>
            </div>
            <div>
              <div class="fw-bold fs-4"><?= $menungguBayar ?></div>
              <div class="text-muted small">Menunggu Bayar</div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-6 col-xl-3">
        <div class="stat-card">
          <div class="d-flex align-items-center gap-3">
            <div class="stat-card-icon" style="background:#f3e8ff">
              <i class="bi bi-hourglass-split" style="color:#6f42c1"></i>
            </div>
            <div>
              <div class="fw-bold fs-4"><?= $menungguKonfirm ?></div>
              <div class="text-muted small">Menunggu Konfirmasi</div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-6 col-xl-3">
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

    <!-- Baris info tambahan -->
    <?php if ($negosiasi > 0): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
      <i class="bi bi-exclamation-triangle-fill"></i>
      <span>Ada <strong><?= $negosiasi ?> booking</strong> dengan status <strong>Negosiasi</strong> yang perlu dievaluasi.</span>
      <a href="<?= baseUrl('admin/bookings.php?status=pending_negotiation') ?>" class="btn btn-sm btn-warning ms-auto">Lihat</a>
    </div>
    <?php endif; ?>

    <!-- Booking Terbaru -->
    <div class="card border-0 shadow-sm rounded-rose">
      <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h6 class="fw-semibold mb-0"><i class="bi bi-clock-history me-1 text-rose"></i>Booking Terbaru</h6>
          <a href="<?= baseUrl('admin/bookings.php') ?>" class="btn btn-sm btn-outline-rose">Lihat Semua</a>
        </div>
        <?php if (empty($recentBookings)): ?>
        <p class="text-muted text-center py-4">Belum ada booking.</p>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Kode</th>
                <th>Pelanggan</th>
                <th>Jenis</th>
                <th>Tanggal</th>
                <th>Jam</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentBookings as $b): ?>
              <tr>
                <td><code class="small"><?= e($b['kode_booking']) ?></code></td>
                <td class="small"><?= e($b['user_name']) ?></td>
                <td class="small"><?= e($b['jenis_nama']) ?></td>
                <td class="small"><?= date('d/m/Y', strtotime($b['tanggal'])) ?></td>
                <td class="small"><?= e($b['jam_label'] ?? '-') ?></td>
                <td>
                  <span class="badge badge-<?= e($b['status']) ?>">
                    <?= e(labelStatus($b['status'])) ?>
                  </span>
                </td>
                <td>
                  <a href="<?= baseUrl('booking/detail.php?id=' . $b['id']) ?>"
                     class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-eye"></i>
                  </a>
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

<?php require_once BASE_PATH . '/views/partials/footer.php'; ?>
