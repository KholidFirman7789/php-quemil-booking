<?php
/**
 * Edit Jenis Makeup - Admin
 */

defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/models/JenisMakeup.php';

startSession();
requireAdmin();

$model = new JenisMakeup();

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    setFlash('error', 'ID tidak valid.');
    redirect(baseUrl('admin/jenis-makeup.php'));
}

$item = $model->findById($id);
if (!$item) {
    setFlash('error', 'Data tidak ditemukan.');
    redirect(baseUrl('admin/jenis-makeup.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $nama     = sanitize($_POST['nama']      ?? '');
    $kategori = sanitize($_POST['kategori']  ?? '');
    $gender   = sanitize($_POST['gender']    ?? '');
    $desk     = sanitize($_POST['deskripsi'] ?? '');
    $harga    = (float)  ($_POST['harga']    ?? 0);
    $aktif    = (int)    ($_POST['is_active'] ?? 1);

    // Validasi
    if (empty($nama) || $harga < 0) {
        setFlash('error', 'Nama dan harga wajib diisi.');
        redirect(baseUrl('admin/jenis-makeup-edit.php?id=' . $id));
    }
    if (!in_array($kategori, JenisMakeup::KATEGORI_LIST, true)) {
        setFlash('error', 'Kategori tidak valid.');
        redirect(baseUrl('admin/jenis-makeup-edit.php?id=' . $id));
    }
    if (!in_array($gender, JenisMakeup::GENDER_LIST, true)) {
        setFlash('error', 'Gender tidak valid.');
        redirect(baseUrl('admin/jenis-makeup-edit.php?id=' . $id));
    }

    // Proses upload foto jika ada
    $fotoPath = null;
    if (!empty($_FILES['foto']['name'])) {
        $uploaded = uploadFile($_FILES['foto'], 'jenis-makeup');
        if ($uploaded === false) {
            setFlash('error', 'Gagal upload foto. Pastikan format JPG/PNG/WEBP dan ukuran maks 5MB.');
            redirect(baseUrl('admin/jenis-makeup-edit.php?id=' . $id));
        }
        $fotoPath = $uploaded;

        // Hapus foto lama jika ada foto baru
        $fotoLama = sanitize($_POST['foto_lama'] ?? '');
        if (!empty($fotoLama)) {
            $fullPath = UPLOAD_PATH . '/' . ltrim($fotoLama, '/');
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }
    }

    $model->update($id, $nama, $kategori, $gender, $desk, $harga, $aktif, $fotoPath);
    setFlash('success', 'Jenis makeup berhasil diperbarui.');
    redirect(baseUrl('admin/jenis-makeup.php'));
}

$pageTitle = 'Edit Jenis Makeup';
require_once BASE_PATH . '/views/partials/header.php';
?>

<div class="dashboard-wrapper">
  <?php $activePage = 'jenis-makeup'; require_once BASE_PATH . '/views/partials/sidebar_admin.php'; ?>

  <div class="dashboard-content">
    <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center gap-3">
        <button class="btn-sidebar-open" title="Buka menu" aria-label="Buka menu"><i class="bi bi-list"></i></button>
        <div>
          <h4 class="fw-bold mb-0" style="font-family:var(--font-serif)">Edit Jenis Makeup</h4>
          <p class="text-muted small mb-0">
            <a href="<?= baseUrl('admin/jenis-makeup.php') ?>" class="text-muted text-decoration-none">
              <i class="bi bi-arrow-left me-1"></i>Kembali ke daftar
            </a>
          </p>
        </div>
      </div>
    </div>

    <?php renderFlash(); ?>

    <div class="card border-0 shadow-sm rounded-rose">
      <div class="card-body p-4">
        <form method="POST" enctype="multipart/form-data">
          <?= csrfField() ?>
          <input type="hidden" name="foto_lama" value="<?= e($item['foto'] ?? '') ?>">

          <div class="row g-3">
            <div class="col-md-12">
              <label class="form-label fw-medium">Nama Layanan <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="nama"
                     value="<?= e($item['nama']) ?>" required placeholder="Contoh: Makeup Wisuda">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-medium">Kategori <span class="text-danger">*</span></label>
              <select class="form-select" name="kategori" required>
                <?php foreach (JenisMakeup::KATEGORI_LIST as $kat): ?>
                <option value="<?= e($kat) ?>" <?= $item['kategori'] === $kat ? 'selected' : '' ?>>
                  <?= e($kat) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-medium">Gender <span class="text-danger">*</span></label>
              <select class="form-select" name="gender" required>
                <option value="wanita" <?= $item['gender'] === 'wanita' ? 'selected' : '' ?>>Wanita</option>
                <option value="pria"   <?= $item['gender'] === 'pria'   ? 'selected' : '' ?>>Pria</option>
                <option value="couple" <?= $item['gender'] === 'couple' ? 'selected' : '' ?>>Couple</option>
              </select>
            </div>

            <div class="col-md-12">
              <label class="form-label fw-medium">Deskripsi</label>
              <textarea class="form-control" name="deskripsi" rows="3"
                        placeholder="Deskripsi singkat layanan"><?= e($item['deskripsi'] ?? '') ?></textarea>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-medium">Harga (Rp) <span class="text-danger">*</span></label>
              <input type="number" class="form-control" name="harga"
                     value="<?= e($item['harga']) ?>" required min="0" step="1000">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-medium">Status</label>
              <select class="form-select" name="is_active">
                <option value="1" <?= $item['is_active'] ? 'selected' : '' ?>>Aktif</option>
                <option value="0" <?= !$item['is_active'] ? 'selected' : '' ?>>Nonaktif</option>
              </select>
            </div>

            <div class="col-md-12">
              <label class="form-label fw-medium">Foto Layanan</label>
              <?php if (!empty($item['foto'])): ?>
              <div class="mb-2">
                <img src="<?= e(APP_URL . '/uploads/' . ltrim($item['foto'], '/')) ?>"
                     alt="Foto saat ini"
                     style="width:120px;height:120px;object-fit:cover;border-radius:8px;border:1px solid #eee;">
                <div class="form-text">Foto saat ini. Upload baru untuk mengganti.</div>
              </div>
              <?php endif; ?>
              <input type="file" class="form-control" name="foto"
                     accept="image/jpeg,image/png,image/webp"
                     onchange="previewFoto(this)">
              <div class="form-text">Format JPG, PNG, WEBP. Maks 5MB. Kosongkan jika tidak ingin mengganti foto.</div>
              <div id="fotoNewPreview" class="mt-2" style="display:none;">
                <img src="" alt="Preview baru"
                     style="width:120px;height:120px;object-fit:cover;border-radius:8px;border:1px solid #eee;">
              </div>
            </div>
          </div>

          <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-rose">
              <i class="bi bi-check-circle me-1"></i>Simpan Perubahan
            </button>
            <a href="<?= baseUrl('admin/jenis-makeup.php') ?>" class="btn btn-secondary">
              Batal
            </a>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>

<?php require_once BASE_PATH . '/views/partials/footer.php'; ?>

<script>
function previewFoto(input) {
  const wrap = document.getElementById('fotoNewPreview');
  const img  = wrap.querySelector('img');
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      img.src = e.target.result;
      wrap.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
  } else {
    wrap.style.display = 'none';
    img.src = '';
  }
}
</script>
