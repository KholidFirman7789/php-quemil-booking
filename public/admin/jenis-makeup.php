<?php
/**
 * Kelola Jenis Makeup - Admin
 * Fase 6 - Revisi: tambah kategori & gender
 */

defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/models/JenisMakeup.php';

startSession();
requireAdmin();

$model = new JenisMakeup();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action   = sanitize($_POST['action']    ?? '');
    $id       = (int)    ($_POST['id']       ?? 0);
    $nama     = sanitize($_POST['nama']      ?? '');
    $kategori = sanitize($_POST['kategori']  ?? '');
    $gender   = sanitize($_POST['gender']    ?? '');
    $desk     = sanitize($_POST['deskripsi'] ?? '');
    $harga    = (float)  ($_POST['harga']    ?? 0);
    $aktif    = (int)    ($_POST['is_active'] ?? 1);

    // Validasi
    if (empty($nama) || $harga < 0) {
        setFlash('error', 'Nama dan harga wajib diisi.');
        redirect(baseUrl('admin/jenis-makeup.php'));
    }
    if (!in_array($kategori, JenisMakeup::KATEGORI_LIST, true)) {
        setFlash('error', 'Kategori tidak valid.');
        redirect(baseUrl('admin/jenis-makeup.php'));
    }
    if (!in_array($gender, JenisMakeup::GENDER_LIST, true)) {
        setFlash('error', 'Gender tidak valid.');
        redirect(baseUrl('admin/jenis-makeup.php'));
    }

    // Proses upload foto jika ada
    $fotoPath = null;
    if (!empty($_FILES['foto']['name'])) {
        $uploaded = uploadFile($_FILES['foto'], 'jenis-makeup');
        if ($uploaded === false) {
            setFlash('error', 'Gagal upload foto. Pastikan format JPG/PNG/WEBP dan ukuran maks 5MB.');
            redirect(baseUrl('admin/jenis-makeup.php'));
        }
        $fotoPath = $uploaded;
    }

    if ($action === 'add') {
        $model->create($nama, $kategori, $gender, $desk, $harga, $fotoPath);
        setFlash('success', 'Jenis makeup berhasil ditambahkan.');
    } elseif ($action === 'edit' && $id) {
        // Hapus foto lama jika ada foto baru
        if ($fotoPath !== null) {
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
    }
    redirect(baseUrl('admin/jenis-makeup.php'));
}

if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $model->toggleActive((int) $_GET['id']);
    setFlash('success', 'Status jenis makeup diperbarui.');
    redirect(baseUrl('admin/jenis-makeup.php'));
}

$groupedList = $model->getAllGrouped();
$pageTitle   = 'Kelola Jenis Makeup';
require_once BASE_PATH . '/views/partials/header.php';

$genderLabel = ['wanita' => 'Wanita', 'pria' => 'Pria', 'couple' => 'Couple'];
?>

<div class="dashboard-wrapper">
  <?php $activePage = 'jenis-makeup'; require_once BASE_PATH . '/views/partials/sidebar_admin.php'; ?>

  <div class="dashboard-content">
    <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center gap-3">
        <button class="btn-sidebar-open" title="Buka menu" aria-label="Buka menu"><i class="bi bi-list"></i></button>
        <div>
          <h4 class="fw-bold mb-0" style="font-family:var(--font-serif)">Kelola Jenis Makeup</h4>
          <p class="text-muted small mb-0">Paket layanan yang tersedia di halaman booking</p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-rose" data-bs-toggle="modal" data-bs-target="#modalTambah">
          <i class="bi bi-plus-circle me-1"></i>Tambah Jenis
        </button>
      </div>
    </div>

    <?php renderFlash(); ?>

    <?php if (empty($groupedList)): ?>
    <div class="alert alert-info">Belum ada data jenis makeup.</div>
    <?php else: ?>

    <?php foreach ($groupedList as $kategori => $items): ?>
    <div class="card border-0 shadow-sm rounded-rose mb-4">
      <div class="card-header bg-white border-bottom pt-3 pb-2 px-4">
        <h6 class="fw-bold mb-0 text-rose">
          <i class="bi bi-tag me-2"></i><?= e($kategori) ?>
          <span class="badge bg-secondary ms-2 fw-normal"><?= count($items) ?> layanan</span>
        </h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-4">No</th>
                <th>Foto</th>
                <th>Nama Layanan</th>
                <th>Gender</th>
                <th>Deskripsi</th>
                <th>Harga</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $i => $item): ?>
              <tr class="<?= !$item['is_active'] ? 'table-secondary' : '' ?>">
                 <td class="ps-4 text-muted small"><?= $i + 1 ?></td>
                 <td>
                   <?php if (!empty($item['foto'])): ?>
                     <img src="<?= e(APP_URL . '/uploads/' . ltrim($item['foto'], '/')) ?>"
                          alt="<?= e($item['nama']) ?>"
                          style="width:52px;height:52px;object-fit:cover;border-radius:8px;border:1px solid #eee;">
                   <?php else: ?>
                     <div style="width:52px;height:52px;border-radius:8px;background:#f3f4f6;border:1px solid #eee;display:flex;align-items:center;justify-content:center;">
                       <i class="bi bi-image text-muted" style="font-size:1.3rem;"></i>
                     </div>
                   <?php endif; ?>
                 </td>
                 <td class="fw-medium"><?= e($item['nama']) ?></td>
                <td>
                  <?php
                  $badgeColor = match($item['gender']) {
                    'wanita' => 'bg-pink text-dark',
                    'pria'   => 'bg-primary',
                    'couple' => 'bg-purple',
                    default  => 'bg-secondary',
                  };
                  ?>
                  <span class="badge <?= $badgeColor ?>" style="<?= $item['gender'] === 'wanita' ? 'background-color:#f8c8d4!important' : '' ?>">
                    <?= e($genderLabel[$item['gender']] ?? $item['gender']) ?>
                  </span>
                </td>
                <td class="small text-muted"><?= e($item['deskripsi'] ?? '-') ?></td>
                <td class="fw-semibold text-rose"><?= formatRupiah((float)$item['harga']) ?></td>
                <td>
                  <?php if ($item['is_active']): ?>
                    <span class="badge bg-success">Aktif</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Nonaktif</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <a href="<?= baseUrl('admin/jenis-makeup-edit.php?id=' . $item['id']) ?>"
                       class="btn btn-sm btn-outline-rose"
                       title="Edit">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <a href="<?= baseUrl('admin/jenis-makeup.php?action=toggle&id=' . $item['id']) ?>"
                       class="btn btn-sm <?= $item['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                       title="<?= $item['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>">
                      <i class="bi bi-<?= $item['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
                    </a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endforeach; ?>

    <?php endif; ?>
  </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTambahLabel">Tambah Jenis Makeup</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-medium">Nama Layanan <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="nama" required placeholder="Contoh: Makeup Wisuda">
          </div>
          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label fw-medium">Kategori <span class="text-danger">*</span></label>
              <select class="form-select" name="kategori" required>
                <option value="">-- Pilih Kategori --</option>
                <?php foreach (JenisMakeup::KATEGORI_LIST as $kat): ?>
                <option value="<?= e($kat) ?>"><?= e($kat) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label fw-medium">Gender <span class="text-danger">*</span></label>
              <select class="form-select" name="gender" required>
                <option value="">-- Pilih Gender --</option>
                <option value="wanita">Wanita</option>
                <option value="pria">Pria</option>
                <option value="couple">Couple</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-medium">Deskripsi</label>
            <textarea class="form-control" name="deskripsi" rows="2" placeholder="Deskripsi singkat layanan"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label fw-medium">Harga (Rp) <span class="text-danger">*</span></label>
            <input type="number" class="form-control" name="harga" required min="0" step="1000" placeholder="150000">
          </div>
          <div class="mb-3">
            <label class="form-label fw-medium">Foto Layanan</label>
            <input type="file" class="form-control" name="foto" id="tambahFoto"
                   accept="image/jpeg,image/png,image/webp"
                   onchange="previewFoto(this, 'tambahPreview')">
            <div class="form-text">Format JPG, PNG, WEBP. Maks 5MB.</div>
            <div id="tambahPreview" class="mt-2" style="display:none;">
              <img src="" alt="Preview" style="width:100px;height:100px;object-fit:cover;border-radius:8px;border:1px solid #eee;">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-rose">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once BASE_PATH . '/views/partials/footer.php'; ?>
