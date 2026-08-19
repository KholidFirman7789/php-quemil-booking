<?php
/**
 * Partial: Navbar (public + user pages)
 */
$user = currentUser();
?>
<nav class="navbar navbar-expand-lg sticky-top" id="mainNavbar">
  <div class="container">
    <!-- Brand -->
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= baseUrl('/') ?>">
      <span class="brand-logo">Q</span>
      <span class="brand-name">Quemil <span class="text-rose">Makeup</span></span>
    </a>

    <!-- Mobile hamburger -->
    <button class="navbar-toggler d-lg-none" type="button"
            data-bs-toggle="collapse" data-bs-target="#navMain"
            aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Nav links -->
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav mx-auto align-items-lg-center gap-lg-1">
        <li class="nav-item"><a class="nav-link" href="<?= baseUrl('/') ?>">Beranda</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= baseUrl('/') ?>#portfolio">Portofolio</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= baseUrl('/') ?>#layanan">Layanan</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= baseUrl('/') ?>#kontak">Kontak</a></li>
      </ul>
      <ul class="navbar-nav align-items-lg-center gap-2">
        <!-- Dark mode toggle -->
        <li class="nav-item">
          <button id="themeToggleBtn" class="btn btn-sm btn-outline-secondary px-2" title="Toggle dark mode" aria-label="Toggle dark mode">
            <i class="bi bi-moon-stars" id="themeIcon"></i>
          </button>
        </li>
        <?php if ($user): ?>
          <?php if ($user['role'] === 'admin'): ?>
          <li class="nav-item">
            <a class="btn btn-outline-rose btn-sm px-3" href="<?= baseUrl('admin/dashboard.php') ?>">
              <i class="bi bi-speedometer2 me-1"></i>Dashboard Admin
            </a>
          </li>
          <?php else: ?>
          <li class="nav-item">
            <a class="btn btn-outline-rose btn-sm px-3" href="<?= baseUrl('user/dashboard.php') ?>">
              <i class="bi bi-person-circle me-1"></i><?= e($user['name']) ?>
            </a>
          </li>
          <?php endif; ?>
          <li class="nav-item">
            <a class="btn btn-rose btn-sm px-3" href="<?= baseUrl('auth/logout.php') ?>">
              <i class="bi bi-box-arrow-right me-1"></i>Keluar
            </a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="btn btn-outline-rose btn-sm px-3" href="<?= baseUrl('auth/login.php') ?>">Masuk</a>
          </li>
          <li class="nav-item">
            <a class="btn btn-rose btn-sm px-3" href="<?= baseUrl('auth/register.php') ?>">Daftar</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
