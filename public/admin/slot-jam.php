<?php
/**
 * Kelola Slot Jam - Admin
 * Fase 6
 */

defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/models/JamTersedia.php';

startSession();
requireAdmin();

$model = new JamTersedia();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action     = sanitize($_POST['action']      ?? '');
    $id         = (int)    ($_POST['id']         ?? 0);
    $jamMulai   = sanitize($_POST['jam_mulai']   ?? '');
    $jamSelesai = sanitize($_POST['jam_selesai'] ?? '');
    $aktif      = (int)    ($_POST['is_active']  ?? 1);

    if (empty($jamMulai) || empty($jamSelesai)) {
        setFlash('error', 'Jam mulai dan jam selesai wajib diisi.');
        redirect(baseUrl('admin/slot-jam.php'));
    }
    if ($jamMulai >= $jamSelesai) {
        setFlash('error', 'Jam selesai harus setelah jam mulai.');
        redirect(baseUrl('admin/slot-jam.php'));
    }

    $label = $jamMulai . ' - ' . $jamSelesai;

    if ($action === 'add') {
        $model->create($jamMulai . ':00', $jamSelesai . ':00', $label);
        setFlash('success', 'Slot jam berhasil ditambahkan.');
    } elseif ($action === 'edit' && $id) {
        $model->update($id, $jamMulai . ':00', $jamSelesai . ':00', $label, $aktif);
        setFlash('success', 'Slot jam berhasil diperbarui.');
    }
    redirect(baseUrl('admin/slot-jam.php'));
}

if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $model->toggleActive((int) $_GET['id']);
    setFlash('success', 'Status slot jam diperbarui.');
    redirect(baseUrl('admin/slot-jam.php'));
}

$list      = $model->getAll();
$pageTitle = 'Kelola Slot Jam';
require_once BASE_PATH . '/views/partials/header.php';
?>

<div class="dashboard-wrapper">
  <?php $activePage = 'slot-jam'; require_once BASE_PATH . '/views/partials/sidebar_admin.php'; ?>

  <div class="dashboard-content">
    <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center gap-3">
        <button class="btn-sidebar-open" title="Buka menu" aria-label="Buka menu"><i class="bi bi-list"></i></button>
        <div>
          <h4 class="fw-bold mb-0" style="font-family:var(--font-serif)">Kelola Slot Jam</h4>
          <p class="text-muted small mb-0">Slot jam referensi untuk tampilan admin (booking menggunakan jam bebas)</p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-rose" data-bs-toggle="modal" data-bs-target="#modalTambah">
          <i class="bi bi-plus-circle me-1"></i>Tambah Slot
        </button>
      </div>
    </div>

    <?php renderFlash(); ?>

    <div class="card border-0 shadow-sm rounded-rose">
      <div class="card-body p-4">
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>No</th>
                <th>Jam Mulai</th>
                <th>Jam Selesai</th>
                <th>Label</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($list as $i => $item): ?>
              <tr class="<?= !$item['is_active'] ? 'table-secondary' : '' ?>">
                <td class="text-muted small"><?= $i + 1 ?></td>
                <td class="fw-medium"><?= substr($item['jam_mulai'], 0, 5) ?></td>
                <td class="fw-medium"><?= substr($item['jam_selesai'], 0, 5) ?></td>
                <td><?= e($item['label']) ?></td>
                <td>
                  <?php if ($item['is_active']): ?>
                    <span class="badge bg-success">Aktif</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Nonaktif</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-rose"
                            onclick="editSlot(<?= htmlspecialchars(json_encode($item), ENT_QUOTES) ?>)">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <a href="<?= baseUrl('admin/slot-jam.php?action=toggle&id=' . $item['id']) ?>"
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
  </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add">
        <div class="modal-header">
          <h5 class="modal-title">Tambah Slot Jam</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label fw-medium">Jam Mulai <span class="text-danger">*</span></label>
              <input type="time" class="form-control" name="jam_mulai" required>
            </div>
            <div class="col-6">
              <label class="form-label fw-medium">Jam Selesai <span class="text-danger">*</span></label>
              <input type="time" class="form-control" name="jam_selesai" required>
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

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="editId">
        <div class="modal-header">
          <h5 class="modal-title">Edit Slot Jam</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label fw-medium">Jam Mulai <span class="text-danger">*</span></label>
              <input type="time" class="form-control" name="jam_mulai" id="editMulai" required>
            </div>
            <div class="col-6">
              <label class="form-label fw-medium">Jam Selesai <span class="text-danger">*</span></label>
              <input type="time" class="form-control" name="jam_selesai" id="editSelesai" required>
            </div>
            <div class="col-12">
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
function editSlot(item) {
  document.getElementById('editId').value     = item.id;
  document.getElementById('editMulai').value  = item.jam_mulai.substring(0, 5);
  document.getElementById('editSelesai').value = item.jam_selesai.substring(0, 5);
  document.getElementById('editAktif').value  = item.is_active;
  new bootstrap.Modal(document.getElementById('modalEdit')).show();
}
</script>
