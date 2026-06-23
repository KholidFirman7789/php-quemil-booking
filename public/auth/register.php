<?php
/**
 * Halaman Register
 * Fase 1 - Autentikasi
 */

defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/models/User.php';

startSession();

// Redirect jika sudah login
if (isLoggedIn()) {
    redirect(isAdmin() ? baseUrl('admin/dashboard.php') : baseUrl('user/dashboard.php'));
}

$errors = [];
$data   = ['name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $data['name']  = sanitize($_POST['name']  ?? '');
    $data['email'] = sanitize($_POST['email'] ?? '');
    $data['phone'] = sanitize($_POST['phone'] ?? '');
    $password      = $_POST['password']         ?? '';
    $passConfirm   = $_POST['password_confirm'] ?? '';

    // Validasi
    if (empty($data['name']))                                    $errors[] = 'Nama lengkap wajib diisi.';
    elseif (strlen($data['name']) < 3)                           $errors[] = 'Nama minimal 3 karakter.';
    if (empty($data['email']))                                   $errors[] = 'Email wajib diisi.';
    elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL))  $errors[] = 'Format email tidak valid.';
    if (empty($data['phone']))                                   $errors[] = 'Nomor WhatsApp wajib diisi.';
    elseif (!preg_match('/^[0-9+\-\s]{8,15}$/', $data['phone'])) $errors[] = 'Format nomor telepon tidak valid.';
    if (strlen($password) < 6)                                   $errors[] = 'Password minimal 6 karakter.';
    if ($password !== $passConfirm)                              $errors[] = 'Konfirmasi password tidak cocok.';

    if (empty($errors)) {
        $userModel = new User();
        if ($userModel->emailExists($data['email'])) {
            $errors[] = 'Email sudah terdaftar. Silakan gunakan email lain atau masuk.';
        } else {
            $userId = $userModel->create($data['name'], $data['email'], $data['phone'], $password);
            $user   = $userModel->findById($userId);

            // Auto login setelah register
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role']       = $user['role'];
            session_regenerate_id(true);

            setFlash('success', 'Selamat datang, ' . e($user['name']) . '! Akun Anda berhasil dibuat.');
            redirect(baseUrl('user/dashboard.php'));
        }
    }
}

$pageTitle = 'Daftar Akun';
require_once BASE_PATH . '/views/partials/header.php';
?>

<div class="min-vh-100 d-flex align-items-center bg-light py-5">
  <div class="container">
    <div class="auth-card" style="max-width:520px">

      <!-- Brand -->
      <div class="text-center mb-4">
        <a href="<?= baseUrl('/') ?>" class="text-decoration-none">
          <div class="brand-logo mx-auto mb-2" style="width:52px;height:52px;font-size:1.4rem">Q</div>
          <h4 class="auth-title mb-0">Quemil <span class="text-rose">Makeup</span></h4>
        </a>
        <p class="text-muted small mt-1">Buat akun untuk mulai booking</p>
      </div>

      <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
          <?php foreach ($errors as $err): ?>
          <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <?= csrfField() ?>

        <div class="mb-3">
          <label for="name" class="form-label fw-medium">Nama Lengkap</label>
          <input type="text" class="form-control <?= !empty($errors) && empty($data['name']) ? 'is-invalid' : '' ?>"
                 id="name" name="name" value="<?= e($data['name']) ?>"
                 placeholder="Masukkan nama lengkap" required autofocus>
        </div>

        <div class="mb-3">
          <label for="email" class="form-label fw-medium">Email</label>
          <input type="email" class="form-control"
                 id="email" name="email" value="<?= e($data['email']) ?>"
                 placeholder="email@example.com" required>
        </div>

        <div class="mb-3">
          <label for="phone" class="form-label fw-medium">Nomor WhatsApp</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-whatsapp text-success"></i></span>
            <input type="tel" class="form-control"
                   id="phone" name="phone" value="<?= e($data['phone']) ?>"
                   placeholder="08xxxxxxxxxx" required>
          </div>
          <div class="form-text">Nomor ini digunakan untuk konfirmasi booking.</div>
        </div>

        <div class="mb-3">
          <label for="password" class="form-label fw-medium">Password</label>
          <div class="input-group">
            <input type="password" class="form-control" id="password" name="password"
                   placeholder="Minimal 6 karakter" required>
            <button class="btn btn-outline-secondary" type="button" id="togglePass">
              <i class="bi bi-eye" id="eyeIcon1"></i>
            </button>
          </div>
        </div>

        <div class="mb-4">
          <label for="password_confirm" class="form-label fw-medium">Konfirmasi Password</label>
          <div class="input-group">
            <input type="password" class="form-control" id="password_confirm" name="password_confirm"
                   placeholder="Ulangi password" required>
            <button class="btn btn-outline-secondary" type="button" id="togglePassConfirm">
              <i class="bi bi-eye" id="eyeIcon2"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-rose w-100 py-2 fw-medium">
          <i class="bi bi-person-check me-1"></i> Buat Akun
        </button>
      </form>

      <hr class="my-4">
      <p class="text-center text-muted small mb-0">
        Sudah punya akun?
        <a href="<?= baseUrl('auth/login.php') ?>" class="text-rose fw-medium">Masuk di sini</a>
      </p>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
      input.type = 'password';
      icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
  }
  document.getElementById('togglePass').addEventListener('click', () => togglePassword('password', 'eyeIcon1'));
  document.getElementById('togglePassConfirm').addEventListener('click', () => togglePassword('password_confirm', 'eyeIcon2'));
</script>
</body>
</html>
