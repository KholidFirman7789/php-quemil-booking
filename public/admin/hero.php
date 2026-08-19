<?php
/**
 * Kelola Foto Hero - Admin
 * Fase 6
 */

defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/models/SiteSetting.php';

startSession();
requireAdmin();

$settingModel = new SiteSetting();

// ============================================================
// Handle POST: upload / hapus foto hero
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = sanitize($_POST['action'] ?? '');

    if ($action === 'upload_hero') {
        if (empty($_FILES['hero_foto']['name'])) {
            setFlash('error', 'Pilih foto terlebih dahulu.');
        } else {
            $fotoPath = uploadFile($_FILES['hero_foto'], 'hero', 'foto');
            if ($fotoPath === false) {
                setFlash('error', 'Upload gagal. Format JPG/PNG/WEBP, maks 5MB.');
            } else {
                // Hapus foto hero lama jika ada
                $oldPath = $settingModel->get('hero_image');
                if ($oldPath) {
                    $absOld = BASE_PATH . '/public/uploads/' . ltrim($oldPath, '/');
                    if (file_exists($absOld)) unlink($absOld);
                }
                $settingModel->set('hero_image', $fotoPath);
                setFlash('success', 'Foto hero berhasil diperbarui.');
            }
        }
        redirect(baseUrl('admin/hero.php'));
    }

    if ($action === 'hapus_hero') {
        $oldPath = $settingModel->get('hero_image');
        if ($oldPath) {
            $absOld = BASE_PATH . '/public/uploads/' . ltrim($oldPath, '/');
            if (file_exists($absOld)) unlink($absOld);
        }
        $settingModel->set('hero_image', '');
        setFlash('success', 'Foto hero berhasil dihapus.');
        redirect(baseUrl('admin/hero.php'));
    }
}

$heroImage = $settingModel->get('hero_image');

$pageTitle = 'Kelola Foto Hero';
require_once BASE_PATH . '/views/partials/header.php';
?>

<div class="dashboard-wrapper">
  <?php $activePage = 'hero'; require_once BASE_PATH . '/views/partials/sidebar_admin.php'; ?>

  <div class="dashboard-content">

    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center gap-3">
        <button class="btn-sidebar-open" title="Buka menu" aria-label="Buka menu">
          <i class="bi bi-list"></i>
        </button>
        <div>
          <h4 class="fw-bold mb-0" style="font-family:var(--font-serif)">Kelola Foto Hero</h4>
          <p class="text-muted small mb-0">Foto utama yang tampil di halaman depan website</p>
        </div>
      </div>
    </div>

    <?php renderFlash(); ?>

    <div class="row g-4">

      <!-- Foto Hero Saat Ini -->
      <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-rose h-100">
          <div class="card-header bg-transparent border-bottom fw-semibold">
            <i class="bi bi-image me-2 text-rose"></i>Foto Hero Saat Ini
          </div>
          <div class="card-body text-center p-4">
            <?php if ($heroImage): ?>
              <img src="<?= baseUrl('uploads/' . e($heroImage)) ?>"
                   onerror="this.src='https://placehold.co/800x400/f8e6ea/c9637a?text=Quemil+Makeup'"
                   alt="Foto Hero"
                   class="img-fluid rounded-rose shadow-sm"
                   style="max-height:280px;width:100%;object-fit:cover">
              <div class="mt-3">
                <span class="badge bg-success">
                  <i class="bi bi-check-circle me-1"></i>Foto terpasang
                </span>
              </div>
              <form method="POST" class="mt-3"
                    onsubmit="return confirm('Yakin ingin menghapus foto hero? Halaman depan akan menggunakan gambar default.')">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="hapus_hero">
                <button type="submit" class="btn btn-outline-danger btn-sm">
                  <i class="bi bi-trash me-1"></i>Hapus Foto Hero
                </button>
              </form>
            <?php else: ?>
              <div id="heroPreviewWrap"
                   class="d-flex align-items-center justify-content-center rounded-rose"
                   style="height:240px;background:#f8e6ea;border:2px dashed var(--rose)">
                <div class="text-center text-muted">
                  <i class="bi bi-image" style="font-size:3rem;color:var(--rose);opacity:.5"></i>
                  <p class="mt-2 small mb-0">Belum ada foto hero</p>
                </div>
              </div>
              <img id="heroPreviewImg" src="" alt="Preview"
                   class="img-fluid rounded-rose shadow-sm mt-2"
                   style="display:none;max-height:280px;width:100%;object-fit:cover">
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Form Upload -->
      <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-rose h-100">
          <div class="card-header bg-transparent border-bottom fw-semibold">
            <i class="bi bi-cloud-upload me-2 text-rose"></i>
            <?= $heroImage ? 'Ganti Foto Hero' : 'Upload Foto Hero' ?>
          </div>
          <div class="card-body p-4">
            <p class="text-muted small mb-4">
              Foto hero adalah gambar utama yang ditampilkan di bagian atas halaman depan website.
              Gunakan foto berkualitas tinggi dengan rasio <strong>16:9</strong>
              (contoh: 1280&times;720px) untuk hasil terbaik.
            </p>

            <form method="POST" enctype="multipart/form-data">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="upload_hero">

              <div class="mb-4">
                <label class="form-label fw-medium" for="hero_foto">
                  Pilih Foto <span class="text-danger">*</span>
                </label>
                <input type="file"
                       class="form-control"
                       id="hero_foto"
                       name="hero_foto"
                       accept="image/jpeg,image/png,image/webp"
                       onchange="previewHero(this)"
                       required>
                <div class="form-text">Format: JPG, PNG, WEBP &mdash; maks 5 MB.</div>
              </div>

              <!-- Preview sebelum upload -->
              <div id="previewContainer" class="mb-4" style="display:none">
                <label class="form-label fw-medium text-muted">
                  Preview<?= $heroImage ? ' foto baru' : '' ?>:
                </label>
                <img id="heroPreviewUpload" src="" alt="Preview"
                     class="img-fluid rounded-rose shadow-sm"
                     style="max-height:200px;width:100%;object-fit:cover">
              </div>

              <div class="d-grid">
                <button type="submit" class="btn btn-rose">
                  <i class="bi bi-cloud-upload me-2"></i>
                  <?= $heroImage ? 'Ganti Foto Hero' : 'Upload Foto Hero' ?>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

    </div><!-- /.row -->

    <!-- Info box -->
    <div class="mt-4">
      <div class="alert alert-info d-flex align-items-start gap-2 border-0 shadow-sm" role="alert">
        <i class="bi bi-info-circle-fill mt-1 flex-shrink-0"></i>
        <div>
          <strong>Tips foto hero:</strong>
          Gunakan resolusi minimal 1280&times;720px. Foto akan di-crop otomatis mengikuti lebar layar,
          jadi hindari meletakkan teks atau elemen penting terlalu di tepi.
        </div>
      </div>
    </div>

  </div><!-- /.dashboard-content -->
</div><!-- /.dashboard-wrapper -->

<?php require_once BASE_PATH . '/views/partials/footer.php'; ?>

<script>
function previewHero(input) {
  if (!input.files || !input.files[0]) return;
  const reader = new FileReader();
  reader.onload = function (e) {
    // Preview di card kiri (hanya muncul saat belum ada foto terpasang)
    const previewImg  = document.getElementById('heroPreviewImg');
    const previewWrap = document.getElementById('heroPreviewWrap');
    if (previewImg && previewWrap) {
      previewImg.src          = e.target.result;
      previewImg.style.display = 'block';
      previewWrap.style.display = 'none';
    }
    // Preview di card kanan (selalu tampil)
    const previewUpload    = document.getElementById('heroPreviewUpload');
    const previewContainer = document.getElementById('previewContainer');
    if (previewUpload && previewContainer) {
      previewUpload.src             = e.target.result;
      previewContainer.style.display = 'block';
    }
  };
  reader.readAsDataURL(input.files[0]);
}
</script>
