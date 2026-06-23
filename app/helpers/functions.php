<?php
/**
 * Helper Functions Global
 * Quemil Booking System
 */

defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));

// ============================================================
// SESSION
// ============================================================

function startSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}

function isLoggedIn(): bool
{
    startSession();
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool
{
    startSession();
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect(APP_URL . '/auth/login.php?redirect=' . urlencode(currentUrl()));
    }
}

function requireAdmin(): void
{
    if (!isLoggedIn() || !isAdmin()) {
        redirect(APP_URL . '/auth/login.php');
    }
}

function currentUser(): ?array
{
    if (!isLoggedIn()) return null;
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role'  => $_SESSION['role'],
    ];
}

// ============================================================
// CSRF
// ============================================================

function csrfToken(): string
{
    startSession();
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function csrfField(): string
{
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . csrfToken() . '">';
}

function verifyCsrf(): void
{
    startSession();
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (!hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $token)) {
        http_response_code(403);
        die('CSRF token tidak valid.');
    }
}

// ============================================================
// REDIRECT & URL
// ============================================================

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function currentUrl(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

function baseUrl(string $path = ''): string
{
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

// ============================================================
// FLASH MESSAGES
// ============================================================

function setFlash(string $type, string $message): void
{
    startSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    startSession();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function renderFlash(): void
{
    $flash = getFlash();
    if (!$flash) return;
    $map = ['error' => 'danger', 'success' => 'success', 'warning' => 'warning', 'info' => 'info'];
    $cls = $map[$flash['type']] ?? 'info';
    echo '<div class="alert alert-' . $cls . ' alert-dismissible fade show" role="alert">';
    echo htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8');
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    echo '</div>';
}

// ============================================================
// FORMAT & SANITASI
// ============================================================

function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function sanitize(string $input): string
{
    return trim(htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8'));
}

function formatRupiah(float $amount): string
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function formatTanggal(string $date): string
{
    $days   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    $months = ['','Januari','Februari','Maret','April','Mei','Juni',
               'Juli','Agustus','September','Oktober','November','Desember'];
    $ts = strtotime($date);
    return $days[date('w', $ts)] . ', ' . date('j', $ts) . ' '
         . $months[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

function labelStatus(string $status): string
{
    $map = [
        'pending'               => 'Menunggu',
        'pending_negotiation'   => 'Negosiasi',
        'waiting_payment'       => 'Belum Bayar',
        'waiting_confirmation'  => 'Menunggu Konfirmasi',
        'confirmed'             => 'Terkonfirmasi',
        'completed'             => 'Selesai',
        'cancelled'             => 'Dibatalkan',
    ];
    return $map[$status] ?? $status;
}

// ============================================================
// BOOKING
// ============================================================

function generateKodeBooking(): string
{
    return 'QB-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
}

function hitungBiaya(float $hargaJasa, float $biayaTransport): array
{
    $total     = $hargaJasa + $biayaTransport;
    $dp        = (float) floor($total * DP_PERCENT / 100);
    $pelunasan = $total - $dp;
    return [
        'harga_jasa'       => $hargaJasa,
        'biaya_transport'  => $biayaTransport,
        'total_biaya'      => $total,
        'dp_amount'        => $dp,
        'pelunasan_amount' => $pelunasan,
    ];
}

function validasiProvinsi(string $provinsi): string
{
    $p = strtolower(trim($provinsi));
    $jatim = ['jawa timur', 'jatim'];
    $jawa  = [
        'jawa barat', 'jabar', 'jawa tengah', 'jateng',
        'dki jakarta', 'jakarta', 'banten',
        'yogyakarta', 'di yogyakarta', 'diy',
    ];
    if (in_array($p, $jatim, true)) return 'jatim';
    if (in_array($p, $jawa,  true)) return 'jawa';
    return 'luar_jawa';
}

// ============================================================
// UPLOAD FILE
// ============================================================

function uploadFile(array $file, string $subfolder, string $prefix = ''): string|false
{
    if ($file['error'] !== UPLOAD_ERR_OK)           return false;
    if ($file['size']  >  MAX_FILE_SIZE)             return false;
    if (!in_array($file['type'], ALLOWED_IMAGES, true)) return false;

    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = ($prefix ? $prefix . '_' : '') . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destDir  = UPLOAD_PATH . '/' . trim($subfolder, '/');
    $destPath = $destDir    . '/' . $filename;

    if (!is_dir($destDir)) mkdir($destDir, 0755, true);
    if (!move_uploaded_file($file['tmp_name'], $destPath)) return false;

    return $subfolder . '/' . $filename;
}

// ============================================================
// PAGINATION
// ============================================================

function paginate(int $total, int $perPage, int $currentPage): array
{
    $totalPages = (int) ceil($total / $perPage);
    $offset     = ($currentPage - 1) * $perPage;
    return [
        'total'        => $total,
        'per_page'     => $perPage,
        'current_page' => $currentPage,
        'total_pages'  => $totalPages,
        'offset'       => max(0, $offset),
        'has_prev'     => $currentPage > 1,
        'has_next'     => $currentPage < $totalPages,
    ];
}
