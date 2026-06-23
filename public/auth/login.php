<?php
/**
 * Halaman Login
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
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $email    = sanitize($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    // Validasi input
    if (empty($email)) {
        $errors[] = 'Email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }
    if (empty($password)) {
        $errors[] = 'Password wajib diisi.';
    }

    if (empty($errors)) {
        $userModel = new User();
        $user      = $userModel->findByEmail($email);

        if ($user && $userModel->verifyPassword($password, $user['password'])) {
            // Set session
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role']       = $user['role'];
            session_regenerate_id(true);

            // Redirect ke halaman yang dituju sebelumnya (jika ada)
            $redirect = $_GET['redirect'] ?? '';
            if ($redirect && strpos($redirect, APP_URL) === 0) {
                redirect($redirect);
            }
            redirect($user['role'] === 'admin'
                ? baseUrl('admin/dashboard.php')
                : baseUrl('user/dashboard.php')
            );
        } else {
            $errors[] = 'Email atau password salah.';
        }
    }
}

$pageTitle = 'Masuk';
require_once BASE_PATH . '/views/partials/header.php';
?>

<div class="min-vh-100 d-flex align-items-center bg-light py-5">
  <div class="container">
    <div class="auth-card">

      <!-- Brand -->
      <div class="text-center mb-4">
        <a href="<?= baseUrl('/') ?>" class="text-decoration-none">
          <div class="brand-logo mx-auto mb-2" style="width:52px;height:52px;font-size:1.4rem">Q</div>
          <h4 class="auth-title mb-0">Quemil <span class="text-rose">Makeup</span></h4>
        </a>
        <p class="text-muted small mt-1">Masuk ke akun Anda</p>
      </div>

      <?php renderFlash(); ?>

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
          <label for="email" class="form-label fw-medium">Email</label>
          <input type="email" class="form-control" id="email" name="email"
                 value="<?= e($email) ?>" placeholder="email@example.com"
                 required autofocus>
        </div>

        <div class="mb-4">
          <label for="password" class="form-label fw-medium">Password</label>
          <div class="input-group">
            <input type="password" class="form-control" id="password" name="password"
                   placeholder="Masukkan password" required>
            <button class="btn btn-outline-secondary" type="button" id="togglePass">
              <i class="bi bi-eye" id="eyeIcon"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-rose w-100 py-2 fw-medium">
          <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
        </button>
      </form>

      <hr class="my-4">
      <p class="text-center text-muted small mb-0">
        Belum punya akun?
        <a href="<?= baseUrl('auth/register.php') ?>" class="text-rose fw-medium">Daftar sekarang</a>
      </p>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.getElementById('togglePass').addEventListener('click', function () {
    const input = document.getElementById('password');
    const icon  = document.getElementById('eyeIcon');
    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
      input.type = 'password';
      icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
  });
</script>
</body>
</html>
