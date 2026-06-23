<?php
/**
 * Dashboard Admin - Placeholder
 * Akan diisi lengkap di Fase 6
 */

defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/helpers/functions.php';

startSession();
requireAdmin();

$user      = currentUser();
$pageTitle = 'Dashboard Admin';
require_once BASE_PATH . '/views/partials/header.php';
?>

<div class="dashboard-wrapper">
  <?php $activePage = 'dashboard'; require_once BASE_PATH . '/views/partials/sidebar_admin.php'; ?>
  <div class="dashboard-content">
    <?php renderFlash(); ?>
    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <h4 class="mb-1" style="font-family:var(--font-serif)">Dashboard Admin</h4>
        <p class="text-muted mb-0">Selamat datang, <?= e($user['name']) ?></p>
      </div>
    </div>
    <div class="alert alert-info">
      <i class="bi bi-info-circle me-1"></i>
      Dashboard admin sedang dalam pengembangan (Fase 6). Login admin berhasil!
    </div>
  </div>
</div>

<?php require_once BASE_PATH . '/views/partials/footer.php'; ?>
