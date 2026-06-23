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

$jenisList = $jenisMakeupModel->getActive();
$jamList   = $jamTersediaModel->getActive();
$zonaList  = $zonaTransportModel->getActive();
$user      = currentUser();

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

    $jenisId     = (int) ($_POST['jenis_makeup_id'] ?? 0);
    $jamId       = (int) ($_POST['jam_id']          ?? 0);
    $tanggal     = sanitize($_POST['tanggal']        ?? '');
    $tipeLayanan = sanitize($_POST['tipe_layanan']   ?? '');
    $catatanUser = sanitize($_POST['catatan_user']   ?? '');
    $alamat      = sanitize($_POST['alamat_lengkap'] ?? '');
    $kota        = sanitize($_POST['kota']           ?? '');
    $provinsi    = sanitize($_POST['provinsi']       ?? '');
    $zonaId      = (int) ($_POST['zona_id']          ?? 0);

    // Validasi dasar
    if (!$jenisId)                                              $errors[] = 'Jenis makeup wajib dipilih.';
    if (!$jamId)                                                $errors[] = 'Slot waktu wajib dipilih.';
    if (empty($tanggal))                                        $errors[] = 'Tanggal wajib diisi.';
    elseif (strtotime($tanggal) < strtotime('today'))           $errors[] = 'Tanggal tidak boleh di masa lampau.';
    if (!in_array($tipeLayanan, ['studio', 'home_service']))    $errors[] = 'Tipe layanan tidak valid.';
    if ($tipeLayanan === 'home_service') {
        if (empty($alamat))   $errors[] = 'Alamat lengkap wajib diisi.';
        if (empty($kota))     $errors[] = 'Kota wajib diisi.';
        if (empty($provinsi)) $errors[] = 'Provinsi wajib diisi.';
    }

    if (empty($errors)) {
        $jenis = $jenisMakeupModel->findById($jenisId);
        if (!$jenis) $errors[] = 'Jenis makeup tidak ditemukan.';
    }

    if (empty($errors)) {
        // Cek slot tersedia
        if (!$bookingModel->isSlotAvailable($tanggal, $jamId)) {
            $errors[] = 'Maaf, slot waktu yang Anda pilih sudah tidak tersedia. Silakan pilih slot lain.';
        }
    }

    if (empty($errors)) {
        $hargaJasa      = (float) $jenis['harga'];
        $biayaTransport = 0;
        $status         = 'waiting_payment';
        $zonaIdFinal    = null;

        // Logika validasi lokasi home service
        if ($tipeLayanan === 'home_service') {
            $resolusi = validasiProvinsi($provinsi);

            if ($resolusi === 'luar_jawa') {
                $errors[] = 'Maaf, layanan home service hanya tersedia di Pulau Jawa. Silakan datang ke studio.';
            } elseif ($resolusi === 'jawa') {
                // Luar Jatim dalam Pulau Jawa: pending negotiation
                $status         = 'pending_negotiation';
                $biayaTransport = 0;
            } else {
                // Jawa Timur: hitung biaya dari zona
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
            'jam_id'           => $jamId,
            'tanggal'          => $tanggal,
            'tipe_layanan'     => $tipeLayanan,
            'alamat_lengkap'   => $tipeLayanan === 'home_service' ? $alamat   : null,
            'kota'             => $tipeLayanan === 'home_service' ? $kota     : null,
            'provinsi'         => $tipeLayanan === 'home_service' ? $provinsi : null,
            'zona_id'          => $zonaIdFinal,
            'biaya_transport'  => $biaya['biaya_transport'],
            'harga_jasa'       => $biaya['harga_jasa'],
            'total_biaya'      => $biaya['total_biaya'],
            'dp_amount'        => $biaya['dp_amount'],
            'pelunasan_amount' => $biaya['pelunasan_amount'],
            'status'           => $status,
            'catatan_user'     => $catatanUser ?: null,
        ]);

        // Kirim notifikasi ke user
        (new Notification())->create(
            $user['id'],
            'Booking Berhasil Dibuat',
            'Booking ' . $kodeBooking . ' berhasil dibuat. ' .
            ($status === 'pending_negotiation'
                ? 'Menunggu konfirmasi admin untuk biaya transport.'
                : 'Silakan lanjutkan pembayaran DP.'),
            $bookingId
        );

        setFlash('success', 'Booking berhasil! Kode: <strong>' . e($kodeBooking) . '</strong>. Silakan selesaikan pembayaran DP.');
        redirect(baseUrl('booking/detail.php?id=' . $bookingId));
    }
}

$preJenisId = (int) ($_GET['jenis_id'] ?? 0);
$pageTitle  = 'Booking Layanan';
require_once BASE_PATH . '/views/partials/header.php';
require_once BASE_PATH . '/views/partials/navbar.php';
?>

<div class="container py-5" style="max-width:860px">

  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= baseUrl('/') ?>" class="text-rose">Beranda</a></li>
      <li class="breadcrumb-item active">Booking Layanan</li>
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

    <!-- STEP 1: Pilih Layanan -->
    <div class="card border-0 shadow-sm rounded-rose mb-4">
      <div class="card-body p-4">
        <div class="booking-step">
          <h5 class="fw-semibold mb-1"><span class="text-rose">01</span> Pilih Layanan</h5>
          <p class="text-muted small mb-0">Pilih jenis makeup yang Anda inginkan</p>
        </div>
        <div class="row g-3 mt-1">
          <div class="col-md-6">
            <label for="jenis_makeup_id" class="form-label fw-medium">Jenis Makeup <span class="text-danger">*</span></label>
            <select class="form-select" id="jenis_makeup_id" name="jenis_makeup_id" required>
              <option value="">-- Pilih jenis makeup --</option>
              <?php foreach ($jenisList as $j): ?>
              <option value="<?= $j['id'] ?>" data-harga="<?= $j['harga'] ?>"
                      <?= $preJenisId == $j['id'] ? 'selected' : '' ?>>
                <?= e($j['nama']) ?> &mdash; <?= formatRupiah((float)$j['harga']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-medium">Harga Jasa</label>
            <div class="form-control bg-light fw-semibold text-rose" id="displayHarga">Rp 0</div>
          </div>
        </div>
      </div>
    </div>

    <!-- STEP 2: Pilih Jadwal -->
    <div class="card border-0 shadow-sm rounded-rose mb-4">
      <div class="card-body p-4">
        <div class="booking-step">
          <h5 class="fw-semibold mb-1"><span class="text-rose">02</span> Pilih Jadwal</h5>
          <p class="text-muted small mb-0">Pilih tanggal dan slot waktu yang tersedia</p>
        </div>
        <div class="row g-3 mt-1">
          <div class="col-md-6">
            <label for="tanggal" class="form-label fw-medium">Tanggal <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="tanggal" name="tanggal"
                   min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                   value="<?= e($_POST['tanggal'] ?? '') ?>" required>
            <div class="form-text">Booking minimal H+1 dari hari ini.</div>
          </div>
        </div>
        <div class="mt-3">
          <label class="form-label fw-medium">Slot Waktu <span class="text-danger">*</span></label>
          <input type="hidden" id="jam_id" name="jam_id" value="<?= e($_POST['jam_id'] ?? '') ?>">
          <div id="slotContainer" class="d-flex flex-wrap gap-2">
            <?php foreach ($jamList as $jam): ?>
            <button type="button" class="slot-btn <?= (($_POST['jam_id'] ?? '') == $jam['id']) ? 'selected' : '' ?>"
                    data-jam-id="<?= $jam['id'] ?>">
              <?= e($jam['label']) ?>
            </button>
            <?php endforeach; ?>
          </div>
          <div class="form-text mt-2 d-flex align-items-center gap-2">
            <span class="slot-btn" style="pointer-events:none;font-size:.72rem;padding:.2rem .5rem;min-width:auto">Tersedia</span>
            <span class="slot-btn disabled" style="pointer-events:none;font-size:.72rem;padding:.2rem .5rem;min-width:auto">Terpakai</span>
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
              <label for="kota" class="form-label fw-medium">Kota / Kabupaten <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="kota" name="kota"
                     value="<?= e($_POST['kota'] ?? '') ?>" placeholder="Contoh: Jombang">
            </div>
            <div class="col-md-6">
              <label for="provinsi" class="form-label fw-medium">Provinsi <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="provinsi" name="provinsi"
                     value="<?= e($_POST['provinsi'] ?? '') ?>" placeholder="Contoh: Jawa Timur">
              <div class="form-text">Tulis nama provinsi dengan lengkap.</div>
            </div>
            <div class="col-md-6" id="zonaField">
              <label for="zona_id" class="form-label fw-medium">Zona Transport <span class="text-danger">*</span></label>
              <select class="form-select" id="zona_id" name="zona_id">
                <option value="">-- Pilih zona wilayah --</option>
                <?php foreach ($zonaList as $zona): ?>
                <option value="<?= $zona['id'] ?>" data-biaya="<?= $zona['biaya'] ?>"
                        <?= (($_POST['zona_id'] ?? '') == $zona['id']) ? 'selected' : '' ?>>
                  <?= e($zona['nama_zona']) ?>
                  <?php if ($zona['keterangan']): ?>&mdash; <?= e($zona['keterangan']) ?><?php endif; ?>
                  (<?= $zona['biaya'] > 0 ? formatRupiah((float)$zona['biaya']) : 'Gratis' ?>)
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-medium">Biaya Transport</label>
              <div class="form-control bg-light fw-semibold text-rose" id="displayTransport">Rp 0</div>
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
            <span class="text-muted">Harga Jasa</span><span id="ringHarga" class="fw-medium">Rp 0</span>
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
      <button type="submit" class="btn btn-rose btn-lg py-3">
        <i class="bi bi-calendar-check me-2"></i>Konfirmasi &amp; Lanjutkan ke Pembayaran DP
      </button>
    </div>
  </form>
</div>

<?php require_once BASE_PATH . '/views/partials/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {

  // --- Kalkulasi biaya ---
  let hargaJasa = 0, biayaTransport = 0;

  function fmt(n) {
    return 'Rp ' + Math.floor(n).toLocaleString('id-ID');
  }

  function updateSummary() {
    var total = hargaJasa + biayaTransport;
    var dp    = Math.floor(total * 30 / 100);
    var pel   = total - dp;
    document.getElementById('displayHarga').textContent     = fmt(hargaJasa);
    document.getElementById('displayTransport').textContent  = fmt(biayaTransport);
    document.getElementById('ringHarga').textContent        = fmt(hargaJasa);
    document.getElementById('ringTransport').textContent    = fmt(biayaTransport);
    document.getElementById('ringTotal').textContent        = fmt(total);
    document.getElementById('ringDP').textContent           = fmt(dp);
    document.getElementById('ringPelunasan').textContent    = fmt(pel);
  }

  // Jenis makeup berubah
  var jenisSelect = document.getElementById('jenis_makeup_id');
  jenisSelect.addEventListener('change', function () {
    hargaJasa = parseFloat(this.options[this.selectedIndex].dataset.harga || 0);
    updateSummary();
  });
  if (jenisSelect.value) {
    hargaJasa = parseFloat(jenisSelect.options[jenisSelect.selectedIndex].dataset.harga || 0);
    updateSummary();
  }

  // Zona transport berubah
  document.getElementById('zona_id').addEventListener('change', function () {
    biayaTransport = parseFloat(this.options[this.selectedIndex].dataset.biaya || 0);
    updateSummary();
  });

  // --- Slot jam ---
  var tanggalInput  = document.getElementById('tanggal');
  var jamIdInput    = document.getElementById('jam_id');
  var slotContainer = document.getElementById('slotContainer');

  function loadSlots(tanggal) {
    fetch('?action=get_slots&tanggal=' + encodeURIComponent(tanggal))
      .then(function(r) { return r.json(); })
      .then(function(data) {
        var booked = data.booked || [];
        slotContainer.querySelectorAll('.slot-btn').forEach(function(btn) {
          var id = parseInt(btn.dataset.jamId);
          if (booked.includes(id)) {
            btn.disabled = true;
            btn.classList.remove('selected');
            if (parseInt(jamIdInput.value) === id) jamIdInput.value = '';
          } else {
            btn.disabled = false;
          }
        });
      })
      .catch(function() {});
  }

  tanggalInput.addEventListener('change', function () { loadSlots(this.value); });
  if (tanggalInput.value) loadSlots(tanggalInput.value);

  slotContainer.querySelectorAll('.slot-btn').forEach(function(btn) {
    btn.addEventListener('click', function () {
      if (this.disabled) return;
      slotContainer.querySelectorAll('.slot-btn').forEach(function(b) { b.classList.remove('selected'); });
      this.classList.add('selected');
      jamIdInput.value = this.dataset.jamId;
    });
  });

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

  // Sembunyikan zona jika luar Jatim
  document.getElementById('provinsi').addEventListener('input', function () {
    var p = this.value.toLowerCase().trim();
    var jatim = ['jawa timur', 'jatim'];
    var jawa  = ['jawa barat', 'jabar', 'jawa tengah', 'jateng',
                 'dki jakarta', 'jakarta', 'banten', 'yogyakarta', 'di yogyakarta', 'diy'];
    if (jatim.some(function(v) { return p === v; })) {
      zonaField.style.display = 'block';
    } else if (jawa.some(function(v) { return p === v; })) {
      zonaField.style.display    = 'none';
      biayaTransport = 0;
      updateSummary();
    } else {
      zonaField.style.display = 'block';
    }
  });

  tipeStudio.addEventListener('change', updateTipeUI);
  tipeHome.addEventListener('change', updateTipeUI);
  updateTipeUI();
});
</script>
