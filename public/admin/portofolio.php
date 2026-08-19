<?php
/**
 * Kelola Portofolio - Admin
 * Fase 6
 */

defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/models/Portofolio.php';

startSession();
requireAdmin();

$model = new Portofolio();

// ============================================================
// Handle POST: tambah / edit
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $action  = sanitize($_POST['action'] ?? '');
    $id      = (int) ($_POST['id'] ?? 0);
    $judul   = sanitize($_POST['judul']     ?? '');
    $kat     = sanitize($_POST['kategori']  ?? '');
    $desk    = sanitize($_POST['deskripsi'] ?? '');
    $urutan  = (int) ($_POST['urutan']      ?? 0);
    $aktif   = (int) ($_POST['is_active']   ?? 1);

    if (empty($judul)) {
        setFlash('error', 'Judul foto wajib diisi.');
        redirect(baseUrl('admin/portofolio.php'));
    }

    if ($action === 'add') {
        if (empty($_FILES['foto']['name'])) {
            setFlash('error', 'Foto wajib diupload.');
            redirect(baseUrl('admin/portofolio.php'));
        }
        $fotoPath = uploadFile($_FILES['foto'], 'portofolio', 'foto');
        if ($fotoPath === false) {
            setFlash('error', 'Upload foto gagal. Format JPG/PNG/WEBP, maks 5MB.');
            redirect(baseUrl('admin/portofolio.php'));
        }
        $model->create($judul, $desk, $fotoPath, $kat, $urutan);
        setFlash('success', 'Foto portofolio berhasil ditambahkan.');

    } elseif ($action === 'edit' && $id) {
        $fotoPath = null;
        if (!empty($_FILES['foto']['name'])) {
            $fotoPath = uploadFile($_FILES['foto'], 'portofolio', 'foto');
            if ($fotoPath === false) {
                setFlash('error', 'Upload foto gagal. Format JPG/PNG/WEBP, maks 5MB.');
                redirect(baseUrl('admin/portofolio.php'));
            }
            // Hapus foto lama
            $old = $model->findById($id);
            if ($old && $fotoPath) {
                $oldPath = BASE_PATH . '/public/' . $old['foto'];
                if (file_exists($oldPath)) unlink($oldPath);
            }
        }
        $model->update($id, $judul, $desk, $fotoPath, $kat, $urutan, $aktif);
        setFlash('success', 'Portofolio berhasil diperbarui.');
    }

    redirect(baseUrl('admin/portofolio.php'));
}

// Handle DELETE
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id      = (int) $_GET['id'];
    $fotoRel = $model->delete($id);
    if ($fotoRel) {
        $fotoAbs = BASE_PATH . '/public/' . $fotoRel;
        if (file_exists($fotoAbs)) unlink($fotoAbs);
    }
    setFlash('success', 'Foto portofolio berhasil dihapus.');
    redirect(baseUrl('admin/portofolio.php'));
}

$portfolios = $model->getAll();

$pageTitle = 'Kelola Portofolio';
require_once BASE_PATH . '/views/partials/header.php';
?>

<div class="dashboard-wrapper">
  <?php $activePage = 'portofolio'; require_once BASE_PATH . '/views/partials/sidebar_admin.php'; ?>

  <div class="dashboard-content">
    <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center gap-3">
        <button class="btn-sidebar-open" title="Buka menu" aria-label="Buka menu"><i class="bi bi-list"></i></button>
        <div>
          <h4 class="fw-bold mb-0" style="font-family:var(--font-serif)">Kelola Portofolio</h4>
          <p class="text-muted small mb-0">Galeri foto hasil karya Quemil Makeup</p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-rose" data-bs-toggle="modal" data-bs-target="#modalTambah">
          <i class="bi bi-plus-circle me-1"></i>Tambah Foto
        </button>
      </div>
    </div>

    <?php renderFlash(); ?>

    <?php if (empty($portfolios)): ?>
    <div class="text-center py-5 text-muted">
      <i class="bi bi-images" style="font-size:3rem;opacity:.4"></i>
      <p class="mt-3">Belum ada foto portofolio. Klik tombol di atas untuk menambahkan.</p>
    </div>
    <?php else: ?>
    <div class="row g-3">
      <?php foreach ($portfolios as $item): ?>
      <div class="col-sm-6 col-lg-4">
        <div class="card border-0 shadow-sm rounded-rose h-100">
          <div class="position-relative">
            <img src="<?= baseUrl('uploads/' . e($item['foto'])) ?>"
                 onerror="this.src='https://placehold.co/400x220/f8e6ea/c9637a?text=Quemil'"
                 alt="<?= e($item['judul']) ?>"
                 class="card-img-top" style="height:200px;object-fit:cover">
            <?php if (!$item['is_active']): ?>
            <span class="badge bg-secondary position-absolute top-0 start-0 m-2">Nonaktif</span>
            <?php endif; ?>
          </div>
          <div class="card-body p-3">
            <h6 class="fw-semibold mb-1"><?= e($item['judul']) ?></h6>
            <?php if ($item['kategori']): ?>
            <span class="badge" style="background:var(--rose-light);color:var(--rose)"><?= e($item['kategori']) ?></span>
            <?php endif; ?>
            <p class="text-muted small mt-2 mb-3"><?= e($item['deskripsi'] ?? '') ?></p>
            <div class="d-flex gap-2">
              <button class="btn btn-sm btn-outline-rose flex-fill"
                      onclick="editPortofolio(<?= htmlspecialchars(json_encode($item), ENT_QUOTES) ?>)">
                <i class="bi bi-pencil me-1"></i>Edit
              </button>
              <a href="<?= baseUrl('admin/portofolio.php?action=delete&id=' . $item['id']) ?>"
                 class="btn btn-sm btn-outline-danger"
                 onclick="return confirm('Hapus foto ini? Tindakan tidak dapat dibatalkan.')">
                <i class="bi bi-trash"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add">
        <div class="modal-header">
          <h5 class="modal-title">Tambah Foto Portofolio</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-medium">Judul <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="judul" required placeholder="Contoh: Makeup Pengantin Modern">
          </div>
          <div class="mb-3">
            <label class="form-label fw-medium">Kategori</label>
            <input type="text" class="form-control" name="kategori" placeholder="Pengantin, Wisuda, Karnaval, dll">
          </div>
          <div class="mb-3">
            <label class="form-label fw-medium">Deskripsi</label>
            <textarea class="form-control" name="deskripsi" rows="2"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label fw-medium">Upload Foto <span class="text-danger">*</span></label>
            <input type="file" class="form-control" name="foto" accept="image/jpeg,image/png,image/webp" required>
            <div class="form-text">Format: JPG, PNG, WEBP. Maks 5MB.</div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-medium">Urutan Tampil</label>
            <input type="number" class="form-control" name="urutan" value="0" min="0">
            <div class="form-text">Angka kecil tampil duluan.</div>
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

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" enctype="multipart/form-data" id="formEdit">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="editId">
        <div class="modal-header">
          <h5 class="modal-title">Edit Portofolio</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-medium">Judul <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="judul" id="editJudul" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-medium">Kategori</label>
            <input type="text" class="form-control" name="kategori" id="editKategori">
          </div>
          <div class="mb-3">
            <label class="form-label fw-medium">Deskripsi</label>
            <textarea class="form-control" name="deskripsi" id="editDeskripsi" rows="2"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label fw-medium">Ganti Foto (opsional)</label>
            <input type="file" class="form-control" name="foto" accept="image/jpeg,image/png,image/webp">
            <div class="form-text">Kosongkan jika tidak ingin mengganti foto.</div>
          </div>
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label fw-medium">Urutan</label>
              <input type="number" class="form-control" name="urutan" id="editUrutan" min="0">
            </div>
            <div class="col-6">
              <label class="form-label fw-medium">Status</label>
              <select class="form-select" name="is_active" id="editAktif">
                <option value="1">Aktif</option>
                <option value="0">Nonaktif</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-rose">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once BASE_PATH . '/views/partials/footer.php'; ?>

<script>
function editPortofolio(item) {
  document.getElementById('editId').value        = item.id;
  document.getElementById('editJudul').value     = item.judul;
  document.getElementById('editKategori').value  = item.kategori || '';
  document.getElementById('editDeskripsi').value = item.deskripsi || '';
  document.getElementById('editUrutan').value    = item.urutan;
  document.getElementById('editAktif').value     = item.is_active;
  new bootstrap.Modal(document.getElementById('modalEdit')).show();
}
</script>
