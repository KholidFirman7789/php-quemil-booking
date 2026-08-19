<?php
/**
 * Halaman Booking
 * Fase 3 - Booking + FCFS
 */

defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/models/Booking.php';
require_once BASE_PATH . '/app/models/JenisMakeup.php';
require_once BASE_PATH . '/app/models/JamTersedia.php';
require_once BASE_PATH . '/app/models/ZonaTransport.php';
require_once BASE_PATH . '/app/models/Notification.php';

startSession();
requireLogin();

$bookingModel      = new Booking();
$jenisMakeupModel  = new JenisMakeup();
$jamTersediaModel  = new JamTersedia();
$zonaTransportModel = new ZonaTransport();

// Auto-cancel expired bookings di setiap request
$bookingModel->cancelExpiredBookings();

$jenisList    = $jenisMakeupModel->getActive();
$jenisGrouped = $jenisMakeupModel->getActiveGrouped();
$jamList   = $jamTersediaModel->getActive();
$zonaList  = $zonaTransportModel->getActive();
$user      = currentUser();

// Redirect ke halaman pilih layanan jika jenis_id tidak ada di GET maupun POST
$preJenisId = (int) ($_GET['jenis_id'] ?? $_POST['jenis_makeup_id'] ?? 0);
if (!$preJenisId && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(baseUrl('booking/pilih-layanan.php'));
}

// Ambil data jenis makeup yang dipilih untuk ditampilkan di form
$jenisSelected = $preJenisId ? $jenisMakeupModel->findById($preJenisId) : null;
if (!$jenisSelected && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(baseUrl('booking/pilih-layanan.php'));
}

// ============================================================
// HANDLE AJAX: ambil slot terpakai pada tanggal tertentu
// ============================================================
if (isset($_GET['action']) && $_GET['action'] === 'get_slots') {
    header('Content-Type: application/json');
    $tanggal = sanitize($_GET['tanggal'] ?? '');
    if (!$tanggal || !strtotime($tanggal)) {
        echo json_encode(['booked' => []]);
        exit;
    }
    echo json_encode(['booked' => $bookingModel->getBookedSlots($tanggal)]);
    exit;
}

$errors = [];

// ============================================================
// HANDLE POST: Submit booking
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $jenisId     = (int)    ($_POST['jenis_makeup_id'] ?? 0);
    $jumlahOrang = max(1, (int) ($_POST['jumlah_orang'] ?? 1));
    $tanggal     = sanitize($_POST['tanggal']          ?? '');
    $jamMulai    = sanitize($_POST['jam_mulai']        ?? '');
    $jamSelesai  = sanitize($_POST['jam_selesai']      ?? '');
    $tipeLayanan = sanitize($_POST['tipe_layanan']     ?? '');
    $catatanUser = sanitize($_POST['catatan_user']     ?? '');
    $alamat      = sanitize($_POST['alamat_lengkap']   ?? '');
    $kota        = sanitize($_POST['kota']             ?? '');
    $provinsi    = sanitize($_POST['provinsi']         ?? '');
    $zonaId      = (int)    ($_POST['zona_id']         ?? 0);
    $mapsUrl     = trim($_POST['maps_url'] ?? '');

    // Validasi dasar
    if (!$jenisId)                                               $errors[] = 'Jenis makeup wajib dipilih.';
    if (empty($tanggal))                                         $errors[] = 'Tanggal wajib diisi.';
    elseif (strtotime($tanggal) < strtotime('today'))            $errors[] = 'Tanggal tidak boleh di masa lampau.';
    if (empty($jamMulai))                                        $errors[] = 'Jam mulai wajib diisi.';
    if (empty($jamSelesai))                                      $errors[] = 'Jam selesai wajib diisi.';
    if ($jamMulai && $jamSelesai && $jamMulai >= $jamSelesai)    $errors[] = 'Jam selesai harus setelah jam mulai.';
    if (!in_array($tipeLayanan, ['studio', 'home_service']))     $errors[] = 'Tipe layanan tidak valid.';
    if ($tipeLayanan === 'home_service') {
        if (empty($alamat))   $errors[] = 'Alamat lengkap wajib diisi.';
        if (empty($provinsi)) $errors[] = 'Provinsi wajib diisi.';
        elseif (validasiProvinsi($provinsi) === 'luar_jawa') $errors[] = 'Provinsi tidak valid. Pilih provinsi di Pulau Jawa.';
        if (empty($kota))     $errors[] = 'Kota / Kabupaten wajib dipilih.';
        elseif (!empty($provinsi) && !validasiKota($kota, $provinsi)) $errors[] = 'Kota / Kabupaten tidak valid untuk provinsi yang dipilih.';
        if (empty($mapsUrl)) {
            $errors[] = 'Link Google Maps lokasi rumah wajib diisi.';
        } elseif (!str_contains($mapsUrl, 'google.com/maps') && !str_contains($mapsUrl, 'maps.app.goo.gl')) {
            $errors[] = 'Link lokasi tidak valid. Gunakan link dari Google Maps (google.com/maps atau maps.app.goo.gl).';
        }
    }

    if (empty($errors)) {
        $jenis = $jenisMakeupModel->findById($jenisId);
        if (!$jenis) $errors[] = 'Jenis makeup tidak ditemukan.';
    }

    if (empty($errors)) {
        // Cek overlap waktu dengan booking lain
        $jamMulaiDb   = $jamMulai   . ':00';
        $jamSelesaiDb = $jamSelesai . ':00';
        if (!$bookingModel->isTimeRangeAvailable($tanggal, $jamMulaiDb, $jamSelesaiDb)) {
            $conflicts   = $bookingModel->getOverlappingBookings($tanggal);
            $konflikInfo = array_map(function ($b) {
                return substr($b['jam_mulai'], 0, 5) . '-' . substr($b['jam_selesai'], 0, 5);
            }, $conflicts);
            $errors[] = 'Waktu yang Anda pilih bentrok dengan booking lain pada jam: '
                      . implode(', ', $konflikInfo) . '. Silakan pilih jam yang berbeda.';
        }
    }

    if (empty($errors)) {
        $hargaJasa      = (float) $jenis['harga'] * $jumlahOrang;
        $biayaTransport = 0;
        // Studio: langsung waiting_payment (tidak perlu persetujuan admin)
        // Home service: tetap pending_approval (perlu persetujuan admin)
        $status         = ($tipeLayanan === 'studio') ? 'waiting_payment' : 'pending_approval';
        $zonaIdFinal    = null;

        // Logika validasi lokasi home service
        if ($tipeLayanan === 'home_service') {
            $resolusi = validasiProvinsi($provinsi);
            if ($resolusi === 'luar_jawa') {
                $errors[] = 'Maaf, layanan home service hanya tersedia di Pulau Jawa. Silakan datang ke studio.';
            } elseif ($resolusi === 'jawa') {
                // Luar Jatim: biaya transport belum diketahui, admin akan input saat approve
                $biayaTransport = 0;
                // status tetap pending_approval, admin akan set biaya transport saat approve
            } else {
                // Jawa Timur: biaya transport langsung dari zona
                if (!$zonaId) {
                    $errors[] = 'Zona transport wajib dipilih untuk wilayah Jawa Timur.';
                } else {
                    $zona = $zonaTransportModel->findById($zonaId);
                    if (!$zona) {
                        $errors[] = 'Zona transport tidak valid.';
                    } else {
                        $biayaTransport = (float) $zona['biaya'];
                        $zonaIdFinal    = $zonaId;
                    }
                }
            }
        }
    }

    if (empty($errors)) {
        $biaya       = hitungBiaya($hargaJasa, $biayaTransport);
        $kodeBooking = generateKodeBooking();

        $bookingId = $bookingModel->create([
            'kode_booking'     => $kodeBooking,
            'user_id'          => $user['id'],
            'jenis_makeup_id'  => $jenisId,
            'jam_mulai'        => $jamMulaiDb,
            'jam_selesai'      => $jamSelesaiDb,
            'tanggal'          => $tanggal,
            'tipe_layanan'     => $tipeLayanan,
            'alamat_lengkap'   => $tipeLayanan === 'home_service' ? $alamat   : null,
            'kota'             => $tipeLayanan === 'home_service' ? $kota     : null,
            'provinsi'         => $tipeLayanan === 'home_service' ? $provinsi : null,
            'zona_id'          => $zonaIdFinal,
            'maps_url'         => $tipeLayanan === 'home_service' ? $mapsUrl : null,
            'jumlah_orang'     => $jumlahOrang,
            'biaya_transport'  => $biaya['biaya_transport'],
            'harga_jasa'       => $biaya['harga_jasa'],
            'total_biaya'      => $biaya['total_biaya'],
            'dp_amount'        => $biaya['dp_amount'],
            'pelunasan_amount' => $biaya['pelunasan_amount'],
            'status'           => $status,
            'catatan_user'     => $catatanUser ?: null,
        ]);

        // Kirim notifikasi ke user
        if ($tipeLayanan === 'studio') {
            $notifPesan = 'Booking ' . $kodeBooking . ' berhasil dibuat. Silakan lanjutkan pembayaran DP sekarang.';
            $flashPesan = 'Booking berhasil! Kode: <strong>' . e($kodeBooking) . '</strong>. Silakan lanjutkan ke pembayaran.';
        } else {
            $notifPesan = 'Booking ' . $kodeBooking . ' berhasil dibuat. Menunggu persetujuan admin untuk melanjutkan ke pembayaran.';
            $flashPesan = 'Booking berhasil! Kode: <strong>' . e($kodeBooking) . '</strong>. Menunggu persetujuan admin.';
        }
        (new Notification())->create($user['id'], 'Booking Berhasil Dibuat', $notifPesan, $bookingId);

        setFlash('success', $flashPesan);
        redirect(baseUrl('booking/detail.php?id=' . $bookingId));
    }
}

$pageTitle  = 'Booking Layanan';
require_once BASE_PATH . '/views/partials/header.php';
require_once BASE_PATH . '/views/partials/navbar.php';

// Jika POST gagal validasi, ambil ulang data jenis dari POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$jenisSelected) {
    $jenisSelected = $preJenisId ? $jenisMakeupModel->findById($preJenisId) : null;
}
$preJumlahOrang = (int) ($_POST['jumlah_orang'] ?? 1);
if ($preJumlahOrang < 1) $preJumlahOrang = 1;
?>

<div class="container py-5" style="max-width:860px">

  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= baseUrl('/') ?>" class="text-rose">Beranda</a></li>
      <li class="breadcrumb-item"><a href="<?= baseUrl('booking/pilih-layanan.php') ?>" class="text-rose">Pilih Layanan</a></li>
      <li class="breadcrumb-item active">Form Booking</li>
    </ol>
  </nav>

  <div class="text-center mb-5">
    <h2 class="section-title">Booking Layanan Makeup</h2>
    <div class="section-divider"></div>
    <p class="text-muted">Isi form di bawah untuk memesan layanan Quemil Makeup</p>
  </div>

  <?php renderFlash(); ?>

  <?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <strong><i class="bi bi-exclamation-triangle me-1"></i>Terdapat kesalahan:</strong>
    <ul class="mb-0 mt-2 ps-3">
      <?php foreach ($errors as $err): ?><li><?= $err ?></li><?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <form method="POST" id="formBooking" novalidate>
    <?= csrfField() ?>

    <!-- STEP 1: Layanan Terpilih -->
    <div class="card border-0 shadow-sm rounded-rose mb-4">
      <div class="card-body p-4">
        <div class="booking-step">
          <h5 class="fw-semibold mb-1"><span class="text-rose">01</span> Layanan Dipilih</h5>
          <p class="text-muted small mb-0">Layanan yang Anda pilih dari katalog</p>
        </div>

        <?php if ($jenisSelected): ?>
        <!-- Info jenis makeup terpilih -->
        <input type="hidden" name="jenis_makeup_id" value="<?= (int)$jenisSelected['id'] ?>">
        <div class="mt-3 p-3 rounded-rose d-flex align-items-center justify-content-between flex-wrap gap-3"
             style="background:var(--rose-light)">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                 style="width:48px;height:48px;background:var(--rose-primary,#c8748c)">
              <i class="bi bi-stars text-white fs-5"></i>
            </div>
            <div>
              <div class="fw-semibold"><?= e($jenisSelected['nama']) ?></div>
              <div class="small text-muted">
                <?= e($jenisSelected['kategori']) ?> &bull;
                <?php
                  $gl = ['wanita' => 'Wanita', 'pria' => 'Pria', 'couple' => 'Couple'];
                  echo e($gl[$jenisSelected['gender']] ?? $jenisSelected['gender']);
                ?>
              </div>
            </div>
          </div>
          <div class="text-end">
            <div class="fw-bold text-rose fs-5" id="displayHargaSatuan"><?= formatRupiah((float)$jenisSelected['harga']) ?></div>
            <div class="small text-muted">per orang</div>
          </div>
          <a href="<?= baseUrl('booking/pilih-layanan.php') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Ganti Layanan
          </a>
        </div>

        <!-- Field jumlah orang -->
        <div class="row g-3 mt-2">
          <div class="col-md-4">
            <label for="jumlah_orang" class="form-label fw-medium">Jumlah Orang <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="jumlah_orang" name="jumlah_orang"
                   min="1" max="20" value="<?= $preJumlahOrang ?>" required>
            <div class="form-text">Maksimal 20 orang per booking</div>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-medium">Harga per Orang</label>
            <div class="form-control bg-light fw-semibold text-rose" id="hargaPerOrang">
              <?= formatRupiah((float)$jenisSelected['harga']) ?>
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-medium">Total Harga Jasa</label>
            <div class="form-control bg-light fw-semibold text-rose" id="displayHarga">
              <?= formatRupiah((float)$jenisSelected['harga'] * $preJumlahOrang) ?>
            </div>
          </div>
        </div>

        <?php else: ?>
        <div class="alert alert-warning mt-3">
          Jenis makeup tidak ditemukan. <a href="<?= baseUrl('booking/pilih-layanan.php') ?>">Kembali ke halaman pilih layanan</a>.
        </div>
        <?php endif; ?>

      </div>
    </div>

    <!-- STEP 2: Pilih Jadwal -->
    <div class="card border-0 shadow-sm rounded-rose mb-4">
      <div class="card-body p-4">
        <div class="booking-step">
          <h5 class="fw-semibold mb-1"><span class="text-rose">02</span> Pilih Jadwal</h5>
          <p class="text-muted small mb-0">Pilih tanggal dan tentukan jam layanan Anda</p>
        </div>
        <div class="row g-3 mt-1">
          <div class="col-md-4">
            <label for="tanggal" class="form-label fw-medium">Tanggal <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="tanggal" name="tanggal"
                   min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                   value="<?= e($_POST['tanggal'] ?? '') ?>" required>
            <div class="form-text">Minimal H+1 dari hari ini.</div>
          </div>
          <div class="col-md-4">
            <label for="jam_mulai" class="form-label fw-medium">Jam Mulai <span class="text-danger">*</span></label>
            <input type="time" class="form-control" id="jam_mulai" name="jam_mulai"
                   min="00:00" max="23:00"
                   value="<?= e($_POST['jam_mulai'] ?? '') ?>" required>
            <div class="form-text">Operasional 24 jam (00:00 &ndash; 23:59).</div>
          </div>
          <div class="col-md-4">
            <label for="jam_selesai" class="form-label fw-medium">Jam Selesai <span class="text-danger">*</span></label>
            <input type="time" class="form-control" id="jam_selesai" name="jam_selesai"
                   min="06:00" max="22:00"
                   value="<?= e($_POST['jam_selesai'] ?? '') ?>" required readonly
                   title="Jam selesai diisi otomatis (jam mulai + 1 jam)">
            <div class="form-text">Otomatis jam mulai + 1 jam.</div>
          </div>
        </div>

        <!-- Alert ketersediaan jam -->
        <div id="slotAlert" class="mt-3" style="display:none"></div>

        <!-- Info jam terpakai -->
        <div id="bookedInfo" class="mt-2" style="display:none">
          <div class="p-2 rounded" style="background:#fff3cd">
            <small>
              <i class="bi bi-clock-history text-warning me-1"></i>
              <strong>Jam sudah terpakai pada tanggal ini:</strong>
              <span id="bookedList" class="text-danger fw-medium"></span>
            </small>
          </div>
        </div>
      </div>
    </div>

    <!-- STEP 3: Tipe Layanan -->
    <div class="card border-0 shadow-sm rounded-rose mb-4">
      <div class="card-body p-4">
        <div class="booking-step">
          <h5 class="fw-semibold mb-1"><span class="text-rose">03</span> Tipe Layanan</h5>
          <p class="text-muted small mb-0">Datang ke studio atau home service?</p>
        </div>
        <div class="row g-3 mt-1">
          <div class="col-12">
            <div class="d-flex gap-3 flex-wrap">
              <label style="cursor:pointer;flex:1;min-width:180px">
                <input type="radio" name="tipe_layanan" value="studio" id="tipeStudio" class="d-none"
                       <?= (($_POST['tipe_layanan'] ?? 'studio') === 'studio') ? 'checked' : '' ?>>
                <div class="border rounded-rose p-3 h-100 tipe-card" id="cardStudio">
                  <i class="bi bi-building text-rose fs-4"></i>
                  <div class="fw-semibold mt-1">Studio</div>
                  <small class="text-muted">Datang ke studio Quemil Makeup</small>
                </div>
              </label>
              <label style="cursor:pointer;flex:1;min-width:180px">
                <input type="radio" name="tipe_layanan" value="home_service" id="tipeHome" class="d-none"
                       <?= (($_POST['tipe_layanan'] ?? '') === 'home_service') ? 'checked' : '' ?>>
                <div class="border rounded-rose p-3 h-100 tipe-card" id="cardHome">
                  <i class="bi bi-house-heart text-rose fs-4"></i>
                  <div class="fw-semibold mt-1">Home Service</div>
                  <small class="text-muted">MUA datang ke lokasi Anda (biaya transport berlaku)</small>
                </div>
              </label>
            </div>
          </div>
        </div>

        <!-- Field Home Service -->
        <div id="homeServiceFields" class="mt-3" style="display:none">
          <hr>
          <p class="small text-muted mb-3">
            <i class="bi bi-info-circle text-rose me-1"></i>
            Tersedia untuk wilayah Pulau Jawa. Luar Jawa Timur akan diproses melalui negosiasi admin.
          </p>
          <div class="row g-3">
            <div class="col-12">
              <label for="alamat_lengkap" class="form-label fw-medium">Alamat Lengkap <span class="text-danger">*</span></label>
              <textarea class="form-control" id="alamat_lengkap" name="alamat_lengkap" rows="2"
                        placeholder="Jalan, nomor rumah, RT/RW, dusun/kelurahan..."><?= e($_POST['alamat_lengkap'] ?? '') ?></textarea>
            </div>
            <div class="col-md-6">
              <label for="provinsi" class="form-label fw-medium">Provinsi <span class="text-danger">*</span></label>
              <select class="form-select" id="provinsi" name="provinsi">
                <option value="">-- Pilih Provinsi --</option>
                <option value="Jawa Timur"    <?= (($_POST['provinsi'] ?? '') === 'Jawa Timur')    ? 'selected' : '' ?>>Jawa Timur</option>
                <option value="Jawa Tengah"   <?= (($_POST['provinsi'] ?? '') === 'Jawa Tengah')   ? 'selected' : '' ?>>Jawa Tengah</option>
                <option value="DI Yogyakarta" <?= (($_POST['provinsi'] ?? '') === 'DI Yogyakarta') ? 'selected' : '' ?>>DI Yogyakarta</option>
                <option value="Jawa Barat"    <?= (($_POST['provinsi'] ?? '') === 'Jawa Barat')    ? 'selected' : '' ?>>Jawa Barat</option>
                <option value="DKI Jakarta"   <?= (($_POST['provinsi'] ?? '') === 'DKI Jakarta')   ? 'selected' : '' ?>>DKI Jakarta</option>
                <option value="Banten"        <?= (($_POST['provinsi'] ?? '') === 'Banten')        ? 'selected' : '' ?>>Banten</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="kota" class="form-label fw-medium">Kota / Kabupaten <span class="text-danger">*</span></label>
              <select class="form-select" id="kota" name="kota">
                <option value="">-- Pilih Provinsi dulu --</option>
              </select>
            </div>
            <div class="col-md-6" id="zonaField">
              <label for="zona_id" class="form-label fw-medium">Zona Transport <span class="text-danger">*</span></label>
              <select class="form-select" id="zona_id" name="zona_id">
                <option value="">-- Pilih zona wilayah --</option>
                <?php foreach ($zonaList as $zona):
                  $isLuarJatim = (stripos($zona['nama_zona'], 'luar jatim') !== false || stripos($zona['nama_zona'], 'luar jawa timur') !== false);
                ?>
                <option value="<?= $zona['id'] ?>"
                        data-biaya="<?= $zona['biaya'] ?>"
                        data-luarjatim="<?= $isLuarJatim ? '1' : '0' ?>"
                        <?= (($_POST['zona_id'] ?? '') == $zona['id']) ? 'selected' : '' ?>>
                  <?= e($zona['nama_zona']) ?>
                  <?php if ($zona['keterangan']): ?>&mdash; <?= e($zona['keterangan']) ?><?php endif; ?>
                  (<?= $zona['biaya'] > 0 ? formatRupiah((float)$zona['biaya']) : 'Negosiasi admin' ?>)
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-medium">Biaya Transport</label>
              <div class="form-control bg-light fw-semibold text-rose" id="displayTransport">Rp 0</div>
            </div>
          </div>

          <!-- LINK GOOGLE MAPS -->
          <div class="mt-4">
            <label for="maps_url" class="form-label fw-medium">
              <i class="bi bi-geo-alt-fill text-rose me-1"></i>Lokasi Rumah (Google Maps) <span class="text-danger">*</span>
            </label>
            <input type="url" class="form-control" id="maps_url" name="maps_url"
                   placeholder="https://maps.app.goo.gl/xxxxx atau https://www.google.com/maps?q=..."
                   value="<?= e($_POST['maps_url'] ?? '') ?>">
            <div class="form-text">
              <i class="bi bi-info-circle me-1"></i>
              Cara share: Buka Google Maps &rarr; tahan lokasi rumah Anda &rarr; tap ikon <strong>Share</strong> &rarr; salin link &rarr; paste di sini.
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- STEP 4: Catatan & Ringkasan -->
    <div class="card border-0 shadow-sm rounded-rose mb-4">
      <div class="card-body p-4">
        <div class="booking-step">
          <h5 class="fw-semibold mb-1"><span class="text-rose">04</span> Catatan &amp; Ringkasan</h5>
        </div>
        <div class="mb-4 mt-3">
          <label for="catatan_user" class="form-label fw-medium">Catatan (opsional)</label>
          <textarea class="form-control" id="catatan_user" name="catatan_user" rows="2"
                    placeholder="Contoh: tema riasan, referensi foto, dll"><?= e($_POST['catatan_user'] ?? '') ?></textarea>
        </div>

        <!-- Ringkasan Biaya -->
        <div class="p-3 rounded-rose" style="background:var(--rose-light)">
          <h6 class="fw-semibold mb-3"><i class="bi bi-receipt me-1 text-rose"></i>Ringkasan Biaya</h6>
          <div class="d-flex justify-content-between mb-1 small">
            <span class="text-muted">Harga Jasa (<span id="ringJumlah"><?= $preJumlahOrang ?></span> orang)</span>
            <span id="ringHarga" class="fw-medium">Rp 0</span>
          </div>
          <div class="d-flex justify-content-between mb-1 small">
            <span class="text-muted">Biaya Transport</span><span id="ringTransport" class="fw-medium">Rp 0</span>
          </div>
          <hr class="my-2">
          <div class="d-flex justify-content-between mb-1">
            <span class="fw-semibold">Total Biaya</span>
            <span id="ringTotal" class="fw-bold text-rose">Rp 0</span>
          </div>
          <div class="d-flex justify-content-between mb-1 small">
            <span class="text-muted">DP yang dibayar sekarang (30%)</span>
            <span id="ringDP" class="fw-bold text-rose">Rp 0</span>
          </div>
          <div class="d-flex justify-content-between small">
            <span class="text-muted">Pelunasan hari H (70%)</span>
            <span id="ringPelunasan" class="text-muted">Rp 0</span>
          </div>
        </div>
      </div>
    </div>

    <div class="d-grid">
      <button type="submit" class="btn btn-rose btn-lg py-3" id="btnSubmit">
        <i class="bi bi-calendar-check me-2"></i>Konfirmasi &amp; Lanjutkan ke Pembayaran DP
      </button>
    </div>
  </form>
</div>

<?php require_once BASE_PATH . '/views/partials/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {

  // --- Kalkulasi biaya ---
  var hargaPerOrangBase = <?= $jenisSelected ? (float)$jenisSelected['harga'] : 0 ?>;
  var hargaJasa = hargaPerOrangBase * parseInt(document.getElementById('jumlah_orang').value || 1, 10);
  var biayaTransport = 0;

  function fmt(n) {
    return 'Rp ' + Math.floor(n).toLocaleString('id-ID');
  }

  function updateSummary() {
    var jumlah = parseInt(document.getElementById('jumlah_orang').value || 1, 10);
    if (jumlah < 1) jumlah = 1;
    hargaJasa = hargaPerOrangBase * jumlah;
    var total = hargaJasa + biayaTransport;
    var dp    = Math.floor(total * 30 / 100);
    var pel   = total - dp;
    document.getElementById('displayHarga').textContent    = fmt(hargaJasa);
    document.getElementById('ringHarga').textContent       = fmt(hargaJasa);
    document.getElementById('ringTransport').textContent   = fmt(biayaTransport);
    document.getElementById('ringTotal').textContent       = fmt(total);
    document.getElementById('ringDP').textContent          = fmt(dp);
    document.getElementById('ringPelunasan').textContent   = fmt(pel);
    // Update display transport jika ada
    var dispTransport = document.getElementById('displayTransport');
    if (dispTransport) dispTransport.textContent = fmt(biayaTransport);
  }

  // Jumlah orang berubah
  document.getElementById('jumlah_orang').addEventListener('input', function () {
    var ringJumlah = document.getElementById('ringJumlah');
    if (ringJumlah) ringJumlah.textContent = this.value || 1;
    updateSummary();
  });

  // Inisialisasi summary saat page load
  updateSummary();

  // Zona transport berubah
  document.getElementById('zona_id').addEventListener('change', function () {
    biayaTransport = parseFloat(this.options[this.selectedIndex].dataset.biaya || 0);
    updateSummary();
  });

  // --- Cek overlap jam (AJAX) ---
  var tanggalInput    = document.getElementById('tanggal');
  var jamMulaiInput   = document.getElementById('jam_mulai');
  var jamSelesaiInput = document.getElementById('jam_selesai');
  var slotAlert       = document.getElementById('slotAlert');
  var bookedInfo      = document.getElementById('bookedInfo');
  var bookedList      = document.getElementById('bookedList');
  var btnSubmit       = document.getElementById('btnSubmit');
  var checkTimer      = null;

  function showAlert(type, msg) {
    var icons = { danger:'x-circle-fill', success:'check-circle-fill', warning:'exclamation-triangle-fill', info:'info-circle-fill' };
    slotAlert.style.display = 'block';
    slotAlert.innerHTML = '<div class="alert alert-' + type + ' py-2 mb-0 d-flex align-items-center gap-2">'
      + '<i class="bi bi-' + (icons[type]||'info-circle-fill') + '"></i><span>' + msg + '</span></div>';
  }

  function hideAlert() {
    slotAlert.style.display = 'none';
    slotAlert.innerHTML = '';
  }

  // Tampilkan jam terpakai saat tanggal berubah
  function loadBookedTimes(tanggal) {
    if (!tanggal) { bookedInfo.style.display = 'none'; return; }
    fetch('?action=check_overlap&tanggal=' + encodeURIComponent(tanggal) + '&jam_mulai=00:00&jam_selesai=00:01')
      .then(function(r) { return r.json(); })
      .then(function(data) {
        var conflicts = data.conflicts || [];
        if (conflicts.length > 0) {
          bookedList.textContent = conflicts.map(function(c) { return c.mulai + ' - ' + c.selesai; }).join(', ');
          bookedInfo.style.display = 'block';
        } else {
          bookedInfo.style.display = 'none';
        }
      }).catch(function() {});
  }

  // Debounce: cek overlap setelah user selesai mengisi jam
  function scheduleCheck() {
    clearTimeout(checkTimer);
    checkTimer = setTimeout(function () {
      var tanggal    = tanggalInput.value;
      var jamMulai   = jamMulaiInput.value;
      var jamSelesai = jamSelesaiInput.value;

      if (!tanggal || !jamMulai || !jamSelesai) { hideAlert(); return; }

      if (jamMulai >= jamSelesai) {
        showAlert('danger', 'Jam selesai harus setelah jam mulai.');
        btnSubmit.disabled = true;
        return;
      }

      showAlert('info', 'Memeriksa ketersediaan waktu...');
      btnSubmit.disabled = true;

      fetch('?action=check_overlap'
        + '&tanggal='    + encodeURIComponent(tanggal)
        + '&jam_mulai='  + encodeURIComponent(jamMulai)
        + '&jam_selesai='+ encodeURIComponent(jamSelesai))
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (data.available) {
            showAlert('success', 'Waktu tersedia! Anda dapat melanjutkan booking.');
            btnSubmit.disabled = false;
          } else {
            var msg = data.message || 'Waktu bentrok dengan booking lain';
            var conflicts = data.conflicts || [];
            if (conflicts.length) {
              msg += '. Jam terpakai: ' + conflicts.map(function(c) { return c.mulai + '-' + c.selesai; }).join(', ');
            }
            showAlert('danger', msg + '. Silakan pilih jam lain.');
            btnSubmit.disabled = true;
          }
        })
        .catch(function() { hideAlert(); btnSubmit.disabled = false; });
    }, 600);
  }

  // Auto-set jam selesai = jam mulai + 1 jam (reset setiap kali jam mulai berubah)
  jamMulaiInput.addEventListener('change', function () {
    if (this.value) {
      var parts = this.value.split(':');
      var h = parseInt(parts[0], 10);
      var m = parts[1];
      var newH = (h + 2).toString().padStart(2, '0');
      var newTime = newH + ':' + m;
      // Pastikan tidak melebihi 23:59
      if (h < 22) {
        jamSelesaiInput.value = newTime;
      } else {
        jamSelesaiInput.value = '23:59';
      }
    } else {
      jamSelesaiInput.value = '';
    }
    scheduleCheck();
  });

  tanggalInput.addEventListener('change', function () {
    loadBookedTimes(this.value);
    scheduleCheck();
  });
  jamSelesaiInput.addEventListener('change', scheduleCheck);

  // Load jam terpakai jika tanggal sudah terisi (setelah validasi error)
  if (tanggalInput.value) loadBookedTimes(tanggalInput.value);
  if (tanggalInput.value && jamMulaiInput.value && jamSelesaiInput.value) scheduleCheck();

  // --- Tipe layanan toggle ---
  var tipeStudio   = document.getElementById('tipeStudio');
  var tipeHome     = document.getElementById('tipeHome');
  var cardStudio   = document.getElementById('cardStudio');
  var cardHome     = document.getElementById('cardHome');
  var homeFields   = document.getElementById('homeServiceFields');
  var zonaField    = document.getElementById('zonaField');

  function updateTipeUI() {
    if (tipeHome.checked) {
      homeFields.style.display    = 'block';
      cardHome.style.borderColor  = 'var(--rose)';
      cardHome.style.background   = 'var(--rose-light)';
      cardStudio.style.borderColor = '';
      cardStudio.style.background  = '';
    } else {
      homeFields.style.display    = 'none';
      cardStudio.style.borderColor = 'var(--rose)';
      cardStudio.style.background  = 'var(--rose-light)';
      cardHome.style.borderColor   = '';
      cardHome.style.background    = '';
      biayaTransport = 0;
      updateSummary();
    }
  }

  // Data kota per provinsi (Pulau Jawa)
  var kotaData = {
    'Jawa Timur': [
      'Kab. Bangkalan','Kab. Banyuwangi','Kab. Blitar','Kab. Bojonegoro','Kab. Bondowoso',
      'Kab. Gresik','Kab. Jember','Kab. Jombang','Kab. Kediri','Kab. Lamongan',
      'Kab. Lumajang','Kab. Madiun','Kab. Magetan','Kab. Malang','Kab. Mojokerto',
      'Kab. Nganjuk','Kab. Ngawi','Kab. Pacitan','Kab. Pamekasan','Kab. Pasuruan',
      'Kab. Ponorogo','Kab. Probolinggo','Kab. Sampang','Kab. Sidoarjo','Kab. Situbondo',
      'Kab. Sumenep','Kab. Trenggalek','Kab. Tuban','Kab. Tulungagung',
      'Kota Batu','Kota Blitar','Kota Kediri','Kota Madiun','Kota Malang',
      'Kota Mojokerto','Kota Pasuruan','Kota Probolinggo','Kota Surabaya'
    ],
    'Jawa Tengah': [
      'Kab. Banjarnegara','Kab. Banyumas','Kab. Batang','Kab. Blora','Kab. Boyolali',
      'Kab. Brebes','Kab. Cilacap','Kab. Demak','Kab. Grobogan','Kab. Jepara',
      'Kab. Karanganyar','Kab. Kebumen','Kab. Kendal','Kab. Klaten','Kab. Kudus',
      'Kab. Magelang','Kab. Pati','Kab. Pekalongan','Kab. Pemalang','Kab. Purbalingga',
      'Kab. Purworejo','Kab. Rembang','Kab. Semarang','Kab. Sragen','Kab. Sukoharjo',
      'Kab. Tegal','Kab. Temanggung','Kab. Wonogiri','Kab. Wonosobo',
      'Kota Magelang','Kota Pekalongan','Kota Salatiga','Kota Semarang','Kota Surakarta','Kota Tegal'
    ],
    'DI Yogyakarta': [
      'Kab. Bantul','Kab. Gunungkidul','Kab. Kulon Progo','Kab. Sleman',
      'Kota Yogyakarta'
    ],
    'Jawa Barat': [
      'Kab. Bandung','Kab. Bandung Barat','Kab. Bekasi','Kab. Bogor','Kab. Ciamis',
      'Kab. Cianjur','Kab. Cirebon','Kab. Garut','Kab. Indramayu','Kab. Karawang',
      'Kab. Kuningan','Kab. Majalengka','Kab. Pangandaran','Kab. Purwakarta',
      'Kab. Subang','Kab. Sukabumi','Kab. Sumedang','Kab. Tasikmalaya',
      'Kota Bandung','Kota Banjar','Kota Bekasi','Kota Bogor','Kota Cimahi',
      'Kota Cirebon','Kota Depok','Kota Sukabumi','Kota Tasikmalaya'
    ],
    'DKI Jakarta': [
      'Kota Jakarta Barat','Kota Jakarta Pusat','Kota Jakarta Selatan',
      'Kota Jakarta Timur','Kota Jakarta Utara','Kab. Kepulauan Seribu'
    ],
    'Banten': [
      'Kab. Lebak','Kab. Pandeglang','Kab. Serang','Kab. Tangerang',
      'Kota Cilegon','Kota Serang','Kota Tangerang','Kota Tangerang Selatan'
    ]
  };

  var savedKota = <?= json_encode($_POST['kota'] ?? '') ?>;

  function updateKotaOptions(provinsi) {
    var kotaSelect = document.getElementById('kota');
    kotaSelect.innerHTML = '';
    if (!provinsi || !kotaData[provinsi]) {
      var opt = document.createElement('option');
      opt.value = '';
      opt.textContent = '-- Pilih Provinsi dulu --';
      kotaSelect.appendChild(opt);
      return;
    }
    var placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = '-- Pilih Kota / Kabupaten --';
    kotaSelect.appendChild(placeholder);
    kotaData[provinsi].forEach(function(kota) {
      var opt = document.createElement('option');
      opt.value = kota;
      opt.textContent = kota;
      if (kota === savedKota) opt.selected = true;
      kotaSelect.appendChild(opt);
    });
  }

  // Sembunyikan/tampilkan zona berdasarkan provinsi yang dipilih
  function updateZonaByProvinsi(provinsi) {
    var jatim = ['Jawa Timur'];
    var luarJatimJawa = ['Jawa Tengah','DI Yogyakarta','Jawa Barat','DKI Jakarta','Banten'];

    if (jatim.indexOf(provinsi) !== -1) {
      // Jawa Timur: tampilkan zona 1-4, sembunyikan zona 5
      zonaField.style.display = 'block';
      Array.from(document.getElementById('zona_id').options).forEach(function(opt) {
        if (opt.dataset.luarjatim === '1') {
          opt.style.display = 'none';
          if (opt.selected) { opt.selected = false; document.getElementById('zona_id').value = ''; }
        } else {
          opt.style.display = '';
        }
      });
    } else if (luarJatimJawa.indexOf(provinsi) !== -1) {
      // Luar Jatim tapi masih Pulau Jawa: tampilkan zona 5 saja, sembunyikan 1-4
      zonaField.style.display = 'block';
      var zonaSelect = document.getElementById('zona_id');
      zonaSelect.value = '';
      biayaTransport = 0;
      Array.from(zonaSelect.options).forEach(function(opt) {
        if (opt.dataset.luarjatim === '1') {
          opt.style.display = '';
          opt.selected = true;
          biayaTransport = parseFloat(opt.dataset.biaya || 0);
        } else {
          opt.style.display = 'none';
          opt.selected = false;
        }
      });
      updateSummary();
    } else {
      // Tidak ada provinsi dipilih
      zonaField.style.display = 'none';
      biayaTransport = 0;
      updateSummary();
    }
  }

  document.getElementById('provinsi').addEventListener('change', function () {
    var provinsi = this.value;
    updateKotaOptions(provinsi);
    updateZonaByProvinsi(provinsi);
  });

  // Inisialisasi saat page load (untuk repopulate setelah POST error)
  (function() {
    var provinsiVal = document.getElementById('provinsi').value;
    if (provinsiVal) {
      updateKotaOptions(provinsiVal);
      updateZonaByProvinsi(provinsiVal);
    }
  })();

  tipeStudio.addEventListener('change', updateTipeUI);
  tipeHome.addEventListener('change', updateTipeUI);
  updateTipeUI();
});
</script>


