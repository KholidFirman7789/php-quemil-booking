<?php
/**
 * Kelola Testimoni - Admin
 */

defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/models/Testimoni.php';

startSession();
requireAdmin();

$model = new Testimoni();

// ============================================================
// DELETE
// ============================================================
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    verifyCsrf($_GET['_token'] ?? '');
    $model->delete((int) $_GET['id']);
    setFlash('success', 'Testimoni berhasil dihapus.');
    redirect(baseUrl('admin/testimoni.php'));
}

// ============================================================
// TOGGLE AKTIF
// ============================================================
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $model->toggleActive((int) $_GET['id']);
    setFlash('success', 'Status testimoni diperbarui.');
    redirect(baseUrl('admin/testimoni.php'));
}

// ============================================================
// CREATE / UPDATE
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action  = sanitize($_POST['action']  ?? '');
    $id      = (int)   ($_POST['id']      ?? 0);
    $nama    = sanitize($_POST['nama']    ?? '');
    $event   = sanitize($_POST['event']   ?? '');
    $teks    = sanitize($_POST['teks']    ?? '');
    $rating  = (int)   ($_POST['rating']  ?? 5);
    $urutan  = (int)   ($_POST['urutan']  ?? 0);
    $aktif   = (int)   ($_POST['is_active'] ?? 1);

    // Validasi
    if (empty($nama) || empty($event) || empty($teks)) {
        setFlash('error', 'Nama, event, dan teks wajib diisi.');
        redirect(baseUrl('admin/testimoni.php'));
    }
    $rating = max(1, min(5, $rating));

    if ($action === 'add') {
        $model->create($nama, $event, $teks, $rating, $urutan);
        setFlash('success', 'Testimoni berhasil ditambahkan.');
    } elseif ($action === 'edit' && $id) {
        $model->update($id, $nama, $event, $teks, $rating, $urutan, $aktif);
        setFlash('success', 'Testimoni berhasil diperbarui.');
    }
    redirect(baseUrl('admin/testimoni.php'));
}

$list      = $model->getAll();
$pageTitle = 'Kelola Testimoni';
require_once BASE_PATH . '/views/partials/header.php';
?>

<div class="dashboard-wrapper">
  <?php $activePage = 'testimoni'; require_once BASE_PATH . '/views/partials/sidebar_admin.php'; ?>

  <div class="dashboard-content">

    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center gap-3">
        <button class="btn-sidebar-open" title="Buka menu" aria-label="Buka menu"><i class="bi bi-list"></i></button>
        <div>
          <h4 class="fw-bold mb-0" style="font-family:var(--font-serif)">Kelola Testimoni</h4>
          <p class="text-muted small mb-0">Ulasan pelanggan yang tampil di halaman utama</p>
        </div>
      </div>
      <button class="btn btn-rose" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-lg me-1"></i>Tambah Testimoni
      </button>
    </div>

    <!-- Flash message -->
    <?php $flash = getFlash(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'error' ? 'danger' : 'info') ?> alert-dismissible fade show" role="alert">
      <?= e($flash['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Tabel -->
    <div class="card border-0" style="box-shadow:var(--card-shadow)">
      <div class="card-body p-0">
        <?php if (empty($list)): ?>
        <div class="text-center py-5 text-muted">
          <i class="bi bi-chat-quote" style="font-size:3rem;opacity:.3"></i>
          <p class="mt-3 mb-0">Belum ada testimoni. Klik "Tambah Testimoni" untuk memulai.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:40px">#</th>
                <th>Nama</th>
                <th>Event</th>
                <th>Rating</th>
                <th class="col-hide-xs">Teks</th>
                <th style="width:80px">Urutan</th>
                <th style="width:100px">Status</th>
                <th style="width:130px">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($list as $i => $t): ?>
              <tr>
                <td class="text-muted small"><?= $i + 1 ?></td>
                <td class="fw-semibold"><?= e($t['nama']) ?></td>
                <td><span class="badge bg-secondary"><?= e($t['event']) ?></span></td>
                <td>
                  <span class="text-warning">
                    <?= str_repeat('★', (int)$t['rating']) ?><?= str_repeat('☆', 5 - (int)$t['rating']) ?>
                  </span>
                  <small class="text-muted ms-1"><?= (int)$t['rating'] ?>/5</small>
                </td>
                <td class="col-hide-xs text-muted small" style="max-width:250px">
                  <span class="d-inline-block text-truncate" style="max-width:240px"><?= e($t['teks']) ?></span>
                </td>
                <td class="text-center"><?= (int)$t['urutan'] ?></td>
                <td>
                  <a href="<?= baseUrl('admin/testimoni.php?action=toggle&id=' . $t['id']) ?>"
                     class="badge text-decoration-none <?= $t['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                    <?= $t['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                  </a>
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-rose"
                            onclick='editTestimoni(<?= json_encode($t) ?>)'
                            title="Edit">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <a href="<?= baseUrl('admin/testimoni.php?action=delete&id=' . $t['id'] . '&_token=' . csrfToken()) ?>"
                       class="btn btn-sm btn-outline-danger"
                       onclick="return confirm('Hapus testimoni ini?')"
                       title="Hapus">
                      <i class="bi bi-trash"></i>
                    </a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /dashboard-content -->
</div><!-- /dashboard-wrapper -->

<!-- ============================================================
     MODAL TAMBAH
     ============================================================ -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTambahLabel">Tambah Testimoni</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= baseUrl('admin/testimoni.php') ?>">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Nama Pelanggan <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="nama" required maxlength="100" placeholder="contoh: Siti Rahayu">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Event / Acara <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="event" required maxlength="100" placeholder="contoh: Wisuda S1">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Rating <span class="text-danger">*</span></label>
            <div class="d-flex align-items-center gap-3">
              <select class="form-select w-auto" name="rating" id="addRating" onchange="updateStars('addRating','addStars')">
                <option value="5" selected>5</option>
                <option value="4">4</option>
                <option value="3">3</option>
                <option value="2">2</option>
                <option value="1">1</option>
              </select>
              <span id="addStars" class="text-warning fs-5">★★★★★</span>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Teks Testimoni <span class="text-danger">*</span></label>
            <textarea class="form-control" name="teks" rows="3" required maxlength="500"
                      placeholder="Tuliskan ulasan pelanggan..."></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Urutan Tampil</label>
            <input type="number" class="form-control" name="urutan" value="0" min="0" max="999">
            <div class="form-text">Angka kecil tampil lebih dulu.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-rose"><i class="bi bi-plus-lg me-1"></i>Tambahkan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ============================================================
     MODAL EDIT
     ============================================================ -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditLabel">Edit Testimoni</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= baseUrl('admin/testimoni.php') ?>">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="editId">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Nama Pelanggan <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="nama" id="editNama" required maxlength="100">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Event / Acara <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="event" id="editEvent" required maxlength="100">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Rating <span class="text-danger">*</span></label>
            <div class="d-flex align-items-center gap-3">
              <select class="form-select w-auto" name="rating" id="editRating" onchange="updateStars('editRating','editStars')">
                <option value="5">5</option>
                <option value="4">4</option>
                <option value="3">3</option>
                <option value="2">2</option>
                <option value="1">1</option>
              </select>
              <span id="editStars" class="text-warning fs-5">★★★★★</span>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Teks Testimoni <span class="text-danger">*</span></label>
            <textarea class="form-control" name="teks" id="editTeks" rows="3" required maxlength="500"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Urutan Tampil</label>
            <input type="number" class="form-control" name="urutan" id="editUrutan" min="0" max="999">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Status</label>
            <select class="form-select" name="is_active" id="editAktif">
              <option value="1">Aktif</option>
              <option value="0">Nonaktif</option>
            </select>
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
function updateStars(selectId, spanId) {
  var val   = parseInt(document.getElementById(selectId).value, 10);
  var stars = '★'.repeat(val) + '☆'.repeat(5 - val);
  document.getElementById(spanId).textContent = stars;
}

function editTestimoni(item) {
  document.getElementById('editId').value     = item.id;
  document.getElementById('editNama').value   = item.nama;
  document.getElementById('editEvent').value  = item.event;
  document.getElementById('editTeks').value   = item.teks;
  document.getElementById('editUrutan').value = item.urutan;
  document.getElementById('editAktif').value  = item.is_active;

  var ratingEl = document.getElementById('editRating');
  ratingEl.value = item.rating;
  updateStars('editRating', 'editStars');

  new bootstrap.Modal(document.getElementById('modalEdit')).show();
}
</script>
