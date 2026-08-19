<?php
/**
 * Partial: Sidebar Admin
 * @var string $activePage
 */
$activePage = $activePage ?? '';
$menus = [
    'dashboard'    => ['icon' => 'bi-speedometer2',   'label' => 'Dashboard'],
    'bookings'     => ['icon' => 'bi-calendar-check', 'label' => 'Booking'],
    'portofolio'   => ['icon' => 'bi-images',         'label' => 'Portofolio'],
    'jenis-makeup' => ['icon' => 'bi-stars',          'label' => 'Jenis Makeup'],
    'testimoni'    => ['icon' => 'bi-chat-quote',     'label' => 'Testimoni'],
    // 'slot-jam'     => ['icon' => 'bi-clock',          'label' => 'Slot Jam'],
    'hero'         => ['icon' => 'bi-image',          'label' => 'Foto Hero'],
];
$hrefMap = [
    'dashboard'    => 'admin/dashboard.php',
    'bookings'     => 'admin/bookings.php',
    'portofolio'   => 'admin/portofolio.php',
    'jenis-makeup' => 'admin/jenis-makeup.php',
    'testimoni'    => 'admin/testimoni.php',
    'slot-jam'     => 'admin/slot-jam.php',
    'hero'         => 'admin/hero.php',
];
?>

<!-- Mobile backdrop -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="adminSidebar">

  <!-- Top row: brand + collapse btn (desktop) / close btn (mobile) -->
  <div class="d-flex align-items-center mb-4">
    <a href="<?= baseUrl('admin/dashboard.php') ?>" class="sidebar-brand flex-grow-1 text-decoration-none">
      <span class="brand-logo" style="width:32px;height:32px;font-size:1rem">Q</span>
      <span class="brand-text text-white fw-semibold sidebar-label" style="font-family:var(--font-serif);font-size:1rem">
        Quemil <span class="text-rose">Makeup</span>
      </span>
    </a>
    <!-- Desktop collapse -->
    <button class="sidebar-desktop-toggle" id="sidebarCollapseBtn" title="Collapse sidebar">
      <i class="bi bi-layout-sidebar" id="collapseIcon"></i>
    </button>
    <!-- Mobile close -->
    <button class="sidebar-close-btn" id="sidebarCloseBtn" title="Tutup">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>

  <!-- Menu -->
  <nav class="nav flex-column gap-1 flex-grow-1">
    <?php foreach ($menus as $key => $menu): ?>
    <a href="<?= baseUrl($hrefMap[$key]) ?>"
       class="nav-link <?= $activePage === $key ? 'active' : '' ?>"
       title="<?= $menu['label'] ?>">
      <i class="bi <?= $menu['icon'] ?>"></i>
      <span class="sidebar-label"><?= $menu['label'] ?></span>
    </a>
    <?php endforeach; ?>
  </nav>

  <!-- Bottom: theme + logout -->
  <div class="mt-auto pt-3" style="border-top:1px solid rgba(255,255,255,.1)">
    <button class="nav-link w-100 border-0 mb-1 text-start"
            style="background:transparent"
            id="sidebarThemeBtn" title="Toggle dark mode">
      <i class="bi bi-moon-stars" id="sidebarThemeIcon"></i>
      <span class="sidebar-label" id="sidebarThemeLabel">Dark Mode</span>
    </button>
    <a href="<?= baseUrl('auth/logout.php') ?>" class="nav-link text-rose">
      <i class="bi bi-box-arrow-right"></i>
      <span class="sidebar-label">Keluar</span>
    </a>
  </div>

</aside>
