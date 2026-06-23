<?php
/**
 * Homepage - Landing Page
 * Fase 2 - Landing Page
 */

defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/models/Portofolio.php';
require_once BASE_PATH . '/app/models/JenisMakeup.php';

startSession();

// Auto-cancel booking expired (jalankan di setiap request)
if (isLoggedIn()) {
    require_once BASE_PATH . '/app/models/Booking.php';
    (new Booking())->cancelExpiredBookings();
}

$portofolioModel = new Portofolio();
$jenisMakeup     = (new JenisMakeup())->getActive();
$portfolios      = $portofolioModel->getActive(9);

$pageTitle = 'Beranda';
require_once BASE_PATH . '/views/partials/header.php';
require_once BASE_PATH . '/views/partials/navbar.php';
?>

<!-- ============================================================
     HERO SECTION
     ============================================================ -->
<section class="hero-section" id="beranda">
  <div class="container">
    <div class="row align-items-center g-5">

      <!-- Kiri: teks -->
      <div class="col-lg-6">
        <span class="badge rounded-pill px-3 py-2 mb-3 d-inline-flex align-items-center gap-1"
              style="background:var(--rose-light);color:var(--rose);font-size:.8rem;">
          <i class="bi bi-star-fill"></i> Makeup Artist Profesional
        </span>
        <h1 class="hero-title mb-3">
          Tampil Cantik &amp;<br>
          <span class="text-rose">Percaya Diri</span><br>
          di Setiap Momen
        </h1>
        <p class="hero-subtitle mb-4">
          Quemil Makeup hadir untuk membuat penampilan Anda sempurna di hari spesial.
          Wisuda, pernikahan, karnaval &mdash; kami siap merias dengan harga terjangkau.
        </p>
        <div class="d-flex flex-wrap gap-3 mb-4">
          <a href="<?= baseUrl('booking/index.php') ?>" class="btn btn-rose btn-lg px-4">
            <i class="bi bi-calendar-check me-2"></i>Booking Sekarang
          </a>
          <a href="#portfolio" class="btn btn-outline-rose btn-lg px-4">
            <i class="bi bi-images me-2"></i>Lihat Portofolio
          </a>
        </div>
        <!-- Mini stats -->
        <div class="d-flex gap-4">
          <div class="text-center">
            <div class="fw-bold fs-4 text-rose">200+</div>
            <div class="text-muted small">Klien Puas</div>
          </div>
          <div class="vr"></div>
          <div class="text-center">
            <div class="fw-bold fs-4 text-rose">5+</div>
            <div class="text-muted small">Tahun Pengalaman</div>
          </div>
          <div class="vr"></div>
          <div class="text-center">
            <div class="fw-bold fs-4 text-rose">4.9 <i class="bi bi-star-fill" style="font-size:1rem"></i></div>
            <div class="text-muted small">Rating</div>
          </div>
        </div>
      </div>

      <!-- Kanan: foto hero -->
      <div class="col-lg-6 d-none d-lg-block">
        <div class="hero-image-wrapper">
          <img src="<?= baseUrl('assets/img/hero.jpg') ?>"
               onerror="this.src='https://placehold.co/480x560/f8e6ea/c9637a?text=Quemil+Makeup'"
               alt="Quemil Makeup Artist"
               class="hero-img img-fluid">
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ============================================================
     STATS BANNER
     ============================================================ -->
<section class="stats-banner">
  <div class="container">
    <div class="row text-center g-4">
      <div class="col-6 col-md-3">
        <div class="stat-number">200+</div>
        <div class="small opacity-75">Klien Terlayani</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-number"><?= count($jenisMakeup) ?></div>
        <div class="small opacity-75">Jenis Layanan</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-number">100%</div>
        <div class="small opacity-75">Booking Online</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-number">4.9 <i class="bi bi-star-fill" style="font-size:1.5rem"></i></div>
        <div class="small opacity-75">Rating Pelanggan</div>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     LAYANAN
     ============================================================ -->
<section class="py-5" id="layanan">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title">Layanan Kami</h2>
      <div class="section-divider"></div>
      <p class="text-muted">Pilih jenis layanan makeup yang sesuai dengan kebutuhan Anda</p>
    </div>
    <div class="row g-4 justify-content-center">
      <?php
      $icons = [
        'bi-mortarboard', 'bi-heart', 'bi-stars',
        'bi-calendar-event', 'bi-camera'
      ];
      foreach ($jenisMakeup as $i => $jenis):
        $icon = $icons[$i % count($icons)];
      ?>
      <div class="col-sm-6 col-lg-4">
        <div class="service-card">
          <div class="service-icon">
            <i class="bi <?= $icon ?>"></i>
          </div>
          <h5 class="fw-semibold mb-2"><?= e($jenis['nama']) ?></h5>
          <p class="text-muted small mb-3"><?= e($jenis['deskripsi'] ?? '') ?></p>
          <div class="service-price mb-3"><?= formatRupiah((float)$jenis['harga']) ?></div>
          <a href="<?= baseUrl('booking/index.php?jenis_id=' . $jenis['id']) ?>"
             class="btn btn-rose btn-sm w-100">
            <i class="bi bi-calendar-check me-1"></i>Booking Sekarang
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============================================================
     PORTOFOLIO
     ============================================================ -->
<section class="py-5 bg-light" id="portfolio">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title">Portofolio</h2>
      <div class="section-divider"></div>
      <p class="text-muted">Hasil karya terbaik Quemil Makeup untuk klien kami</p>
    </div>

    <?php if (empty($portfolios)): ?>
    <div class="text-center py-5 text-muted">
      <i class="bi bi-images" style="font-size:3.5rem;opacity:.4"></i>
      <p class="mt-3 mb-0">Belum ada portofolio yang tersedia.</p>
    </div>
    <?php else: ?>
    <div class="row g-4">
      <?php foreach ($portfolios as $item): ?>
      <div class="col-sm-6 col-lg-4">
        <div class="card portfolio-card position-relative border-0">
          <img src="<?= baseUrl('uploads/' . e($item['foto'])) ?>"
               onerror="this.src='https://placehold.co/400x260/f8e6ea/c9637a?text=Quemil'"
               alt="<?= e($item['judul']) ?>"
               class="card-img-top">
          <div class="portfolio-overlay">
            <div>
              <p class="text-white fw-semibold mb-0 small"><?= e($item['judul']) ?></p>
              <?php if ($item['kategori']): ?>
              <small class="text-white-50"><?= e($item['kategori']) ?></small>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ============================================================
     CARA BOOKING
     ============================================================ -->
<section class="py-5">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title">Cara Booking</h2>
      <div class="section-divider"></div>
      <p class="text-muted">Proses booking mudah dalam 4 langkah</p>
    </div>
    <div class="row g-4 text-center">
      <?php
      $steps = [
        ['num'=>'01','icon'=>'bi-person-plus',  'title'=>'Daftar / Masuk',  'desc'=>'Buat akun atau masuk ke sistem'],
        ['num'=>'02','icon'=>'bi-calendar3',    'title'=>'Pilih Layanan',   'desc'=>'Tentukan jenis makeup, tanggal, dan slot waktu'],
        ['num'=>'03','icon'=>'bi-credit-card',  'title'=>'Bayar DP 30%',    'desc'=>'Lakukan pembayaran DP via Midtrans'],
        ['num'=>'04','icon'=>'bi-check-circle', 'title'=>'Konfirmasi',      'desc'=>'Admin konfirmasi dan booking terjadwal'],
      ];
      foreach ($steps as $step):
      ?>
      <div class="col-6 col-lg-3">
        <div class="position-relative d-inline-block mb-3">
          <div class="d-flex align-items-center justify-content-center rounded-circle"
               style="width:72px;height:72px;background:var(--rose-light);">
            <i class="bi <?= $step['icon'] ?> text-rose" style="font-size:1.75rem"></i>
          </div>
          <span class="badge rounded-pill bg-rose position-absolute"
                style="top:-4px;right:-8px;font-size:.7rem">
            <?= $step['num'] ?>
          </span>
        </div>
        <h6 class="fw-semibold"><?= $step['title'] ?></h6>
        <p class="text-muted small mb-0"><?= $step['desc'] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-5">
      <a href="<?= baseUrl('booking/index.php') ?>" class="btn btn-rose btn-lg px-5">
        <i class="bi bi-calendar-check me-2"></i>Booking Sekarang
      </a>
    </div>
  </div>
</section>

<!-- ============================================================
     TESTIMONI
     ============================================================ -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title">Testimoni Pelanggan</h2>
      <div class="section-divider"></div>
    </div>
    <div class="row g-4">
      <?php
      $testimonials = [
        ['name'=>'Siti Rahayu',    'event'=>'Wisuda S1',   'rating'=>5,
         'text'=>'Hasilnya luar biasa! Riasannya natural tapi tetap glowing. Semua teman nanya makeup dimana.'],
        ['name'=>'Dewi Kartika',   'event'=>'Pernikahan',  'rating'=>5,
         'text'=>'Sangat profesional dan on-time. Harganya juga sangat terjangkau untuk kualitas segini.'],
        ['name'=>'Rina Wulandari', 'event'=>'Karnaval',    'rating'=>5,
         'text'=>'Riasan karnavalnya kreatif banget! Sesuai tema dan tahan lama seharian penuh.'],
      ];
      foreach ($testimonials as $t):
      ?>
      <div class="col-md-4">
        <div class="testimonial-card">
          <div class="testimonial-stars mb-2">
            <?= str_repeat('<i class="bi bi-star-fill"></i> ', $t['rating']) ?>
          </div>
          <p class="text-muted fst-italic mb-3 small">&ldquo;<?= e($t['text']) ?>&rdquo;</p>
          <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold"
                 style="width:40px;height:40px;background:var(--rose);flex-shrink:0">
              <?= strtoupper(substr($t['name'], 0, 1)) ?>
            </div>
            <div>
              <div class="fw-semibold small"><?= e($t['name']) ?></div>
              <div class="text-muted" style="font-size:.75rem"><?= e($t['event']) ?></div>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require_once BASE_PATH . '/views/partials/footer.php'; ?>
