<?php
/**
 * Halaman Pilih Layanan Makeup
 * User memilih jenis makeup sebelum mengisi form booking
 */

defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/models/JenisMakeup.php';

startSession();
requireLogin();

$jenisMakeupModel = new JenisMakeup();
$jenisGrouped     = $jenisMakeupModel->getActiveGrouped();

// Semua item flat untuk tab "Semua"
$jenisAll = $jenisMakeupModel->getActive();

$pageTitle = 'Pilih Layanan Makeup';
require_once BASE_PATH . '/views/partials/header.php';
require_once BASE_PATH . '/views/partials/navbar.php';

$kategoriIcon = [
    'Reguler'     => 'bi-stars',
    'Dayang'      => 'bi-flower1',
    'Karnaval'    => 'bi-balloon-heart',
    'Pengantin'   => 'bi-heart',
    'Sewa Baju'   => 'bi-bag-heart',
    'Sewa Sandal' => 'bi-heart-eyes',
];

$genderBadge = [
    'wanita' => ['label' => 'Wanita', 'class' => 'badge-wanita'],
    'pria'   => ['label' => 'Pria',   'class' => 'badge-pria'],
    'couple' => ['label' => 'Couple', 'class' => 'badge-couple'],
    'anak'   => ['label' => 'Anak',   'class' => 'badge-anak'],
];

// Gambar per item — ganti URL di bawah dengan path foto asli kamu nanti
// Format lokal: baseUrl('uploads/jenis-makeup/nama-file.jpg')
$itemImg = [
    // Reguler - Wanita
    'Makeup Natural'                   => 'https://placehold.co/400x260/f8c8d4/8b4a6b?text=Makeup+Natural',
    'Makeup Dance'                     => 'https://placehold.co/400x260/f8c8d4/8b4a6b?text=Makeup+Dance',
    'Makeup Wisuda'                    => 'https://placehold.co/400x260/f8c8d4/8b4a6b?text=Makeup+Wisuda',
    'Makeup Wisuda + Kebaya (Lengkap)' => 'https://placehold.co/400x260/f8c8d4/8b4a6b?text=Makeup+Wisuda+Kebaya',
    // Reguler - Pria
    'Makeup Natural Pria (Grooming)'   => 'https://placehold.co/400x260/cce5ff/004085?text=Grooming+Natural',
    'Makeup Formal Pria (Grooming)'    => 'https://placehold.co/400x260/cce5ff/004085?text=Grooming+Formal',
    // Dayang - Wanita
    'Makeup Dayang'                    => 'https://placehold.co/400x260/f8c8d4/8b4a6b?text=Makeup+Dayang',
    'Makeup Dayang + Hairdo'           => 'https://placehold.co/400x260/f8c8d4/8b4a6b?text=Dayang+Hairdo',
    // Dayang - Pria
    'Makeup Dayang Pria'               => 'https://placehold.co/400x260/cce5ff/004085?text=Dayang+Pria',
    'Makeup Dayang + Styling Rambut'   => 'https://placehold.co/400x260/cce5ff/004085?text=Dayang+Styling',
    // Karnaval - Wanita
    'Makeup Karnaval'                  => 'https://placehold.co/400x260/fff3cd/856404?text=Makeup+Karnaval',
    'Makeup Karnaval + Kostum Mascot'  => 'https://placehold.co/400x260/fff3cd/856404?text=Karnaval+Mascot',
    // Karnaval - Pria
    'Makeup Karnaval Pria'             => 'https://placehold.co/400x260/fff3cd/856404?text=Karnaval+Pria',
    'Makeup Karnaval Lengkap'          => 'https://placehold.co/400x260/fff3cd/856404?text=Karnaval+Lengkap',
    // Pengantin - Wanita
    'Makeup Pengantin'                 => 'https://placehold.co/400x260/f8c8d4/8b4a6b?text=Makeup+Pengantin',
    'Makeup Pengantin Lengkap'         => 'https://placehold.co/400x260/f8c8d4/8b4a6b?text=Pengantin+Lengkap',
    // Pengantin - Pria
    'Groom Pengantin'                  => 'https://placehold.co/400x260/cce5ff/004085?text=Groom+Pengantin',
    'Groom Pengantin + Styling Rambut' => 'https://placehold.co/400x260/cce5ff/004085?text=Groom+Styling',
    // Sewa Baju - Wanita
    'Gaun Pengantin Basic'             => 'https://placehold.co/400x260/f8c8d4/8b4a6b?text=Gaun+Basic',
    'Gaun Pengantin Standard'          => 'https://placehold.co/400x260/f8c8d4/8b4a6b?text=Gaun+Standard',
    'Gaun Pengantin Premium'           => 'https://placehold.co/400x260/f8c8d4/8b4a6b?text=Gaun+Premium',
    'Gaun Pengantin Luxury'            => 'https://placehold.co/400x260/f8c8d4/8b4a6b?text=Gaun+Luxury',
    'Gaun Pengantin Exclusive'         => 'https://placehold.co/400x260/f8c8d4/8b4a6b?text=Gaun+Exclusive',
    // Sewa Baju - Pria
    'Jas Pengantin Basic'              => 'https://placehold.co/400x260/cce5ff/004085?text=Jas+Basic',
    'Jas Pengantin Standard'           => 'https://placehold.co/400x260/cce5ff/004085?text=Jas+Standard',
    'Jas Pengantin Premium'            => 'https://placehold.co/400x260/cce5ff/004085?text=Jas+Premium',
    'Jas Pengantin Luxury'             => 'https://placehold.co/400x260/cce5ff/004085?text=Jas+Luxury',
    // Sewa Sandal - Wanita
    'Sandal Flat'                      => 'https://placehold.co/400x260/d4edda/155724?text=Sandal+Flat',
    'Sandal Heels (3-5 cm)'            => 'https://placehold.co/400x260/d4edda/155724?text=Heels+3-5cm',
    'Sandal Heels (7-10 cm)'           => 'https://placehold.co/400x260/d4edda/155724?text=Heels+7-10cm',
    // Sewa Sandal - Pria
    'Sandal Formal Pria'               => 'https://placehold.co/400x260/cce5ff/004085?text=Sandal+Formal',
    // Sewa Sandal - Couple
    'Sandal Pengantin Couple'          => 'https://placehold.co/400x260/d4edda/155724?text=Sandal+Couple',
    'Sandal Couple Premium'            => 'https://placehold.co/400x260/d4edda/155724?text=Sandal+Premium',
    // Reguler - Anak
    'Makeup Natural Anak'              => 'https://placehold.co/400x260/fde8f5/a3386b?text=Natural+Anak',
    'Makeup Dance Anak'                => 'https://placehold.co/400x260/fde8f5/a3386b?text=Dance+Anak',
    'Makeup Pentas / Penampilan Anak'  => 'https://placehold.co/400x260/fde8f5/a3386b?text=Pentas+Anak',
    // Karnaval - Anak
    'Makeup Karnaval Anak'             => 'https://placehold.co/400x260/fff3cd/856404?text=Karnaval+Anak',
    'Makeup Karnaval Anak + Kostum'    => 'https://placehold.co/400x260/fff3cd/856404?text=Karnaval+Anak+Kostum',
];

// Fallback per kategori
$placeholderImg = [
    'Reguler'     => 'https://placehold.co/400x260/f8c8d4/8b4a6b?text=Reguler',
    'Dayang'      => 'https://placehold.co/400x260/f8c8d4/8b4a6b?text=Dayang',
    'Karnaval'    => 'https://placehold.co/400x260/fff3cd/856404?text=Karnaval',
    'Pengantin'   => 'https://placehold.co/400x260/f8c8d4/8b4a6b?text=Pengantin',
    'Sewa Baju'   => 'https://placehold.co/400x260/f8c8d4/8b4a6b?text=Sewa+Baju',
    'Sewa Sandal' => 'https://placehold.co/400x260/d4edda/155724?text=Sewa+Sandal',
];
?>

<style>
.badge-wanita  { background-color: #f8c8d4; color: #8b4a6b; }
.badge-pria    { background-color: #cce5ff; color: #004085; }
.badge-couple  { background-color: #d4edda; color: #155724; }
.badge-anak    { background-color: #fde8f5; color: #a3386b; }
.tab-filter .nav-link {
  color: #6c757d;
  border-radius: 20px;
  padding: .4rem 1.1rem;
  font-size: .9rem;
}
.tab-filter .nav-link.active {
  background: var(--rose-primary, #c8748c);
  color: #fff;
  border-color: transparent;
}
.kategori-header {
  font-family: var(--font-serif, 'Playfair Display', serif);
  color: var(--rose-primary, #c8748c);
  border-bottom: 2px solid var(--rose-light, #fce8ef);
  padding-bottom: .4rem;
  margin-bottom: 1rem;
}

/* List item refined */
.layanan-list {
  background: #fff;
  border: 1.5px solid #f0e6ea;
  border-radius: 16px;
  overflow: hidden;
}
.layanan-list-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: .85rem 1rem;
  cursor: pointer;
  transition: background .15s;
  border-bottom: 1px solid #fce8ef;
  position: relative;
}
.layanan-list-item:last-child {
  border-bottom: none;
}
.layanan-list-item:hover {
  background: #fffafc;
}
.layanan-list-item:hover .layanan-nama {
  color: var(--rose-primary, #c8748c);
}

/* Foto square */
.layanan-foto-wrap {
  flex-shrink: 0;
  width: 90px;
  height: 90px;
  border-radius: 12px;
  overflow: hidden;
  background: linear-gradient(135deg, #fce8ef, #f8c8d4);
  display: flex;
  align-items: center;
  justify-content: center;
}
.layanan-foto-wrap img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  display: block;
}
.layanan-foto-placeholder {
  color: #c8748c;
  font-size: 1.8rem;
}

/* Body */
.layanan-body {
  flex: 1;
  min-width: 0;
}
.layanan-nama {
  font-weight: 700;
  font-size: .97rem;
  color: #2d2d2d;
  margin-bottom: .18rem;
  transition: color .15s;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.layanan-desk {
  font-size: .78rem;
  color: #999;
  margin-bottom: .3rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Kanan: harga + tombol */
.layanan-right {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: .4rem;
  min-width: 90px;
}
.harga-tag {
  color: var(--rose-primary, #c8748c);
  font-weight: 700;
  font-size: .95rem;
  white-space: nowrap;
}
.btn-pilih {
  background: var(--rose-primary, #c8748c);
  color: #fff;
  border: none;
  border-radius: 20px;
  padding: .28rem .85rem;
  font-size: .78rem;
  font-weight: 600;
  cursor: pointer;
  transition: background .15s;
  white-space: nowrap;
}
.btn-pilih:hover { background: #b5607a; }

@media (max-width: 480px) {
  .layanan-foto-wrap { width: 68px; height: 68px; }
  .layanan-nama { font-size: .88rem; }
  .harga-tag { font-size: .85rem; }
  .layanan-right { min-width: 76px; }
}
</style>

<div class="container py-5">

  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= baseUrl('/') ?>" class="text-rose">Beranda</a></li>
      <li class="breadcrumb-item active">Pilih Layanan</li>
    </ol>
  </nav>

  <!-- Header -->
  <div class="text-center mb-5">
    <h2 class="section-title">Pilih Layanan Makeup</h2>
    <div class="section-divider"></div>
    <p class="text-muted">Pilih layanan yang sesuai kebutuhan Anda, lalu lanjutkan ke form booking</p>
  </div>

  <?php renderFlash(); ?>

  <!-- Tab Filter Gender -->
  <ul class="nav tab-filter justify-content-center mb-5 gap-2 flex-wrap" id="tabGender" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" data-gender="semua" type="button">
        <i class="bi bi-grid me-1"></i>Semua
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" data-gender="wanita" type="button">
        <i class="bi bi-gender-female me-1"></i>Wanita
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" data-gender="pria" type="button">
        <i class="bi bi-gender-male me-1"></i>Pria
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" data-gender="couple" type="button">
        <i class="bi bi-people me-1"></i>Couple
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" data-gender="anak" type="button">
        <i class="bi bi-emoji-smile me-1"></i>Anak-anak
      </button>
    </li>
  </ul>

  <!-- Konten per Kategori -->
  <?php foreach ($jenisGrouped as $kategori => $genderMap): ?>
  <?php
    // Kumpulkan semua item di kategori ini untuk cek apakah ada yang tampil per filter
    $allItemsInKategori = [];
    foreach ($genderMap as $gItems) {
        foreach ($gItems as $item) {
            $allItemsInKategori[] = $item;
        }
    }
  ?>
  <div class="kategori-section mb-5" data-kategori="<?= e($kategori) ?>">
    <h4 class="kategori-header">
      <i class="bi <?= $kategoriIcon[$kategori] ?? 'bi-tag' ?> me-2"></i><?= e($kategori) ?>
    </h4>

    <div class="layanan-list">
      <?php foreach ($genderMap as $gender => $items): ?>
      <?php foreach ($items as $item): ?>
      <?php
        $fotoSrc = !empty($item['foto'])
          ? e(APP_URL . '/uploads/' . ltrim($item['foto'], '/'))
          : ($itemImg[$item['nama']] ?? $placeholderImg[$kategori] ?? '');
      ?>
      <div class="layanan-list-item item-card" data-gender="<?= e($item['gender']) ?>"
           onclick="pilihLayanan(<?= (int)$item['id'] ?>)" role="button" tabindex="0"
           onkeydown="if(event.key==='Enter')pilihLayanan(<?= (int)$item['id'] ?>)">

        <!-- Foto square -->
        <div class="layanan-foto-wrap">
          <?php if ($fotoSrc): ?>
            <img src="<?= $fotoSrc ?>" alt="<?= e($item['nama']) ?>" loading="lazy">
          <?php else: ?>
            <div class="layanan-foto-placeholder">
              <i class="bi <?= $kategoriIcon[$kategori] ?? 'bi-stars' ?>"></i>
            </div>
          <?php endif; ?>
        </div>

        <!-- Body -->
        <div class="layanan-body">
          <div class="layanan-nama"><?= e($item['nama']) ?></div>
          <?php if (!empty($item['deskripsi'])): ?>
          <div class="layanan-desk"><?= e($item['deskripsi']) ?></div>
          <?php endif; ?>
          <div class="d-flex gap-1 flex-wrap mt-1">
            <span class="badge <?= $genderBadge[$item['gender']]['class'] ?? 'bg-secondary' ?> rounded-pill" style="font-size:.68rem">
              <?= $genderBadge[$item['gender']]['label'] ?? e($item['gender']) ?>
            </span>
            <span class="badge bg-light text-secondary border rounded-pill" style="font-size:.68rem">
              <?= e($kategori) ?>
            </span>
          </div>
        </div>

        <!-- Harga + Tombol -->
        <div class="layanan-right">
          <span class="harga-tag"><?= formatRupiah((float)$item['harga']) ?></span>
          <button class="btn-pilih"
                  onclick="event.stopPropagation(); pilihLayanan(<?= (int)$item['id'] ?>)">
            Pilih &rarr;
          </button>
        </div>

      </div>
      <?php endforeach; ?>
      <?php endforeach; ?>
    </div>

    <!-- Pesan kosong per kategori saat filter aktif -->
    <div class="kategori-empty text-center py-3 text-muted small" style="display:none">
      <i class="bi bi-info-circle me-1"></i>Tidak ada layanan di kategori ini untuk filter yang dipilih.
    </div>
  </div>
  <?php endforeach; ?>

  <!-- Pesan kosong global -->
  <div id="emptyAll" class="text-center py-5" style="display:none">
    <i class="bi bi-search display-4 text-muted"></i>
    <p class="text-muted mt-3">Tidak ada layanan ditemukan untuk filter ini.</p>
  </div>

</div>

<?php require_once BASE_PATH . '/views/partials/footer.php'; ?>

<script>
function pilihLayanan(jenisId) {
  window.location.href = '<?= baseUrl('booking/index.php') ?>?jenis_id=' + jenisId;
}

document.addEventListener('DOMContentLoaded', function () {
  var tabs  = document.querySelectorAll('#tabGender .nav-link');
  var cards = document.querySelectorAll('.item-card');
  var sections = document.querySelectorAll('.kategori-section');

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      tabs.forEach(function (t) { t.classList.remove('active'); });
      this.classList.add('active');

      var gender = this.dataset.gender;
      var totalVisible = 0;

      sections.forEach(function (section) {
        var sectionCards = section.querySelectorAll('.item-card');
        var visibleInSection = 0;

        sectionCards.forEach(function (card) {
          if (gender === 'semua' || card.dataset.gender === gender) {
            card.style.display = '';
            visibleInSection++;
            totalVisible++;
          } else {
            card.style.display = 'none';
          }
        });

        // Tampilkan/sembunyikan pesan kosong per kategori
        var emptyMsg = section.querySelector('.kategori-empty');
        var rowEl    = section.querySelector('.row');
        if (visibleInSection === 0) {
          if (emptyMsg) emptyMsg.style.display = 'block';
          if (rowEl)    rowEl.style.display    = 'none';
        } else {
          if (emptyMsg) emptyMsg.style.display = 'none';
          if (rowEl)    rowEl.style.display    = '';
        }
      });

      // Pesan global jika benar-benar kosong
      var emptyAll = document.getElementById('emptyAll');
      if (emptyAll) emptyAll.style.display = (totalVisible === 0) ? 'block' : 'none';
    });
  });
});
</script>
