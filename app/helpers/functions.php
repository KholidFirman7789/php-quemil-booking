<?php
/**
 * Helper Functions Global
 * Quemil Booking System
 *
 * File ini berisi semua fungsi pembantu (helper) yang dipakai
 * di seluruh halaman sistem. Fungsi-fungsi ini bersifat global,
 * artinya bisa dipanggil dari file manapun setelah di-require.
 */

defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));

// ============================================================
// SESSION
// Fungsi-fungsi untuk mengelola sesi login pengguna.
// Sesi digunakan untuk menyimpan data user yang sedang login
// agar tidak perlu login ulang di setiap halaman.
// ============================================================

/**
 * Memulai sesi PHP dengan nama khusus.
 * Dipanggil di awal setiap halaman sebelum fungsi session lainnya.
 * Menggunakan nama sesi dari konstanta SESSION_NAME (di config.php)
 * agar lebih aman dibanding nama default PHP.
 */
function startSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}

/**
 * Mengecek apakah pengguna sudah login.
 * Cara kerjanya: cek apakah ada data 'user_id' di sesi.
 * Data ini hanya ada jika proses login berhasil.
 * Mengembalikan true jika sudah login, false jika belum.
 */
function isLoggedIn(): bool
{
    startSession();
    return isset($_SESSION['user_id']);
}

/**
 * Mengecek apakah pengguna yang login adalah admin.
 * Cara kerjanya: cek nilai 'role' di sesi, harus bernilai 'admin'.
 */
function isAdmin(): bool
{
    startSession();
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Memaksa pengguna untuk login terlebih dahulu.
 * Jika belum login, otomatis diarahkan ke halaman login.
 * Parameter 'redirect' disertakan agar setelah login,
 * pengguna kembali ke halaman yang dituju semula.
 * Dipanggil di awal halaman yang butuh login.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect(APP_URL . '/auth/login.php?redirect=' . urlencode(currentUrl()));
    }
}

/**
 * Memaksa pengguna harus login sebagai admin.
 * Jika bukan admin, diarahkan ke halaman login.
 * Dipanggil di awal semua halaman di folder admin/.
 */
function requireAdmin(): void
{
    if (!isLoggedIn() || !isAdmin()) {
        redirect(APP_URL . '/auth/login.php');
    }
}

/**
 * Mengambil data pengguna yang sedang login dari sesi.
 * Mengembalikan array berisi id, name, email, role.
 * Mengembalikan null jika tidak ada yang login.
 */
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
// CSRF (Cross-Site Request Forgery Protection)
// CSRF adalah serangan di mana situs jahat memaksa browser user
// mengirim permintaan ke sistem kita tanpa sepengetahuan user.
// Contoh: link berbahaya yang jika diklik, bisa submit form
// booking atas nama user yang sedang login.
//
// Cara proteksinya: setiap form menyertakan token rahasia acak
// yang hanya diketahui server. Saat form disubmit, token dicek.
// Jika tidak cocok, permintaan langsung ditolak.
// ============================================================

/**
 * Membuat atau mengambil CSRF token dari sesi.
 * Token berupa string acak 64 karakter (hex dari 32 byte random).
 * Token dibuat sekali per sesi, disimpan di $_SESSION.
 */
function csrfToken(): string
{
    startSession();
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        // bin2hex(random_bytes(32)) menghasilkan string acak 64 karakter
        // yang sangat sulit ditebak oleh penyerang
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Menghasilkan input hidden HTML berisi CSRF token.
 * Dipakai di dalam setiap tag <form> di seluruh halaman.
 * Contoh output: <input type="hidden" name="_csrf" value="abc123...">
 */
function csrfField(): string
{
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . csrfToken() . '">';
}

/**
 * Memeriksa CSRF token saat form disubmit (method POST).
 * Membandingkan token dari form dengan token di sesi.
 * Menggunakan hash_equals() bukan == untuk mencegah timing attack.
 * Jika token tidak cocok, langsung hentikan eksekusi dengan error 403.
 * Dipanggil di awal setiap handler POST sebelum memproses data apapun.
 */
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
        'pending_approval'      => 'Menunggu Persetujuan',
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
    // DP = 30% dari harga jasa + 100% biaya transport (transport dibayar lunas di muka)
    $dpMakeup  = (float) floor($hargaJasa * DP_PERCENT / 100);
    $dp        = $dpMakeup + $biayaTransport;
    // Pelunasan = sisa 70% dari harga jasa saja (transport sudah lunas)
    $pelunasan = $hargaJasa - $dpMakeup;
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
    $jatim = ['Jawa Timur'];
    $jawa  = ['Jawa Tengah', 'DI Yogyakarta', 'Jawa Barat', 'DKI Jakarta', 'Banten'];

    if (in_array($provinsi, $jatim, true)) return 'jatim';
    if (in_array($provinsi, $jawa,  true)) return 'jawa';
    return 'luar_jawa';
}

/**
 * Whitelist semua kota/kabupaten di Pulau Jawa.
 * Mengembalikan true jika kota valid untuk provinsi yang diberikan.
 */
function validasiKota(string $kota, string $provinsi): bool
{
    $kotaData = [
        'Jawa Timur' => [
            'Kab. Bangkalan','Kab. Banyuwangi','Kab. Blitar','Kab. Bojonegoro','Kab. Bondowoso',
            'Kab. Gresik','Kab. Jember','Kab. Jombang','Kab. Kediri','Kab. Lamongan',
            'Kab. Lumajang','Kab. Madiun','Kab. Magetan','Kab. Malang','Kab. Mojokerto',
            'Kab. Nganjuk','Kab. Ngawi','Kab. Pacitan','Kab. Pamekasan','Kab. Pasuruan',
            'Kab. Ponorogo','Kab. Probolinggo','Kab. Sampang','Kab. Sidoarjo','Kab. Situbondo',
            'Kab. Sumenep','Kab. Trenggalek','Kab. Tuban','Kab. Tulungagung',
            'Kota Batu','Kota Blitar','Kota Kediri','Kota Madiun','Kota Malang',
            'Kota Mojokerto','Kota Pasuruan','Kota Probolinggo','Kota Surabaya',
        ],
        'Jawa Tengah' => [
            'Kab. Banjarnegara','Kab. Banyumas','Kab. Batang','Kab. Blora','Kab. Boyolali',
            'Kab. Brebes','Kab. Cilacap','Kab. Demak','Kab. Grobogan','Kab. Jepara',
            'Kab. Karanganyar','Kab. Kebumen','Kab. Kendal','Kab. Klaten','Kab. Kudus',
            'Kab. Magelang','Kab. Pati','Kab. Pekalongan','Kab. Pemalang','Kab. Purbalingga',
            'Kab. Purworejo','Kab. Rembang','Kab. Semarang','Kab. Sragen','Kab. Sukoharjo',
            'Kab. Tegal','Kab. Temanggung','Kab. Wonogiri','Kab. Wonosobo',
            'Kota Magelang','Kota Pekalongan','Kota Salatiga','Kota Semarang','Kota Surakarta','Kota Tegal',
        ],
        'DI Yogyakarta' => [
            'Kab. Bantul','Kab. Gunungkidul','Kab. Kulon Progo','Kab. Sleman','Kota Yogyakarta',
        ],
        'Jawa Barat' => [
            'Kab. Bandung','Kab. Bandung Barat','Kab. Bekasi','Kab. Bogor','Kab. Ciamis',
            'Kab. Cianjur','Kab. Cirebon','Kab. Garut','Kab. Indramayu','Kab. Karawang',
            'Kab. Kuningan','Kab. Majalengka','Kab. Pangandaran','Kab. Purwakarta',
            'Kab. Subang','Kab. Sukabumi','Kab. Sumedang','Kab. Tasikmalaya',
            'Kota Bandung','Kota Banjar','Kota Bekasi','Kota Bogor','Kota Cimahi',
            'Kota Cirebon','Kota Depok','Kota Sukabumi','Kota Tasikmalaya',
        ],
        'DKI Jakarta' => [
            'Kota Jakarta Barat','Kota Jakarta Pusat','Kota Jakarta Selatan',
            'Kota Jakarta Timur','Kota Jakarta Utara','Kab. Kepulauan Seribu',
        ],
        'Banten' => [
            'Kab. Lebak','Kab. Pandeglang','Kab. Serang','Kab. Tangerang',
            'Kota Cilegon','Kota Serang','Kota Tangerang','Kota Tangerang Selatan',
        ],
    ];

    if (!isset($kotaData[$provinsi])) return false;
    return in_array($kota, $kotaData[$provinsi], true);
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
