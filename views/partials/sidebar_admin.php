<?php
/**
 * Partial: Sidebar Admin
 * @var string $activePage  nama halaman aktif, contoh: 'dashboard', 'bookings', dll
 */
$activePage = $activePage ?? '';
$menus = [
    'dashboard'    => ['icon' => 'bi-speedometer2',    'label' => 'Dashboard',    'href' => 'dashboard.php'],
    'bookings'     => ['icon' => 'bi-calendar-check',  'label' => 'Booking',      'href' => 'bookings.php'],
    'portofolio'   => ['icon' => 'bi-images',          'label' => 'Portofolio',   'href' => 'portofolio.php'],
    'jenis-makeup' => ['icon' => 'bi-stars',           'label' => 'Jenis Makeup', 'href' => 'jenis-makeup.php'],
    'slot-jam'     => ['icon' => 'bi-clock',           'label' => 'Slot Jam',     'href' => 'slot-jam.php'],
];
?>
<div class="sidebar d-flex flex-column p-3">
  <!-- Brand -->
  <a href="<?= baseUrl('admin/dashboard.php') ?>"
     class="d-flex align-items-center gap-2 mb-4 text-decoration-none">
    <span class="brand-logo">Q</span>
    <span class="brand-name text-white">Quemil <span class="text-rose">Makeup</span></span>
  </a>

  <!-- Menu -->
  <nav class="nav flex-column gap-1 flex-grow-1">
    <?php foreach ($menus as $key => $menu): ?>
    <a href="<?= baseUrl('admin/' . $menu['href']) ?>"
       class="nav-link <?= $activePage === $key ? 'active' : '' ?>">
      <i class="bi <?= $menu['icon'] ?>"></i>
      <?= $menu['label'] ?>
    </a>
    <?php endforeach; ?>
  </nav>

  <!-- Logout -->
  <div class="mt-auto pt-3 border-top border-secondary">
    <a href="<?= baseUrl('auth/logout.php') ?>" class="nav-link text-rose">
      <i class="bi bi-box-arrow-right"></i> Keluar
    </a>
  </div>
</div>
