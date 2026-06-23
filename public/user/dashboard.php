<?php
/**
 * Dashboard User - Placeholder
 * Akan diisi lengkap di Fase 5
 */

defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/models/Booking.php';

startSession();
requireLogin();

$user   = currentUser();
$pageTitle = 'Dashboard';
require_once BASE_PATH . '/views/partials/header.php';
require_once BASE_PATH . '/views/partials/navbar.php';
?>

<div class="container py-5">
  <?php renderFlash(); ?>
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h4 class="mb-1" style="font-family:var(--font-serif)">Halo, <?= e($user['name']) ?>!</h4>
      <p class="text-muted mb-0">Selamat datang di dashboard Anda.</p>
    </div>
    <a href="<?= baseUrl('booking/index.php') ?>" class="btn btn-rose">
      <i class="bi bi-calendar-plus me-1"></i> Booking Sekarang
    </a>
  </div>
  <div class="alert alert-info">
    <i class="bi bi-info-circle me-1"></i>
    Dashboard user sedang dalam pengembangan (Fase 5). Login berhasil!
  </div>
</div>

<?php require_once BASE_PATH . '/views/partials/footer.php'; ?>
