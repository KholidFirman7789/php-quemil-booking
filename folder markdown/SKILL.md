# SKILL.md — Panduan Skill & Kemampuan Teknis

**Proyek:** Sistem Informasi Booking Quemil Makeup  
**Stack:** PHP Native + MySQL + Bootstrap 5  
**Dibuat oleh:** Abdul Khalid Firmansyah (222355201013)

> Dokumen ini berisi panduan skill teknis yang dibutuhkan dan digunakan selama pengembangan sistem. Berguna sebagai referensi mandiri saat coding.

---

## 1. PHP Native

### 1.1 Struktur Halaman

Setiap halaman PHP mengikuti pola berikut:

```php
<?php
// 1. Definisikan BASE_PATH
defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));

// 2. Load config & dependency
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/models/NamaModel.php';

// 3. Mulai session
startSession();

// 4. Proteksi route (jika perlu)
requireLogin();  // untuk halaman user
requireAdmin();  // untuk halaman admin

// 5. Handle POST (jika form)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    // proses data...
}

// 6. Ambil data untuk ditampilkan
$data = (new NamaModel())->getAll();

// 7. Set judul halaman & render view
$pageTitle = 'Judul Halaman';
require_once BASE_PATH . '/views/partials/header.php';
require_once BASE_PATH . '/views/partials/navbar.php';
?>

<!-- HTML content di sini -->

<?php require_once BASE_PATH . '/views/partials/footer.php'; ?>
```

### 1.2 PDO & Database

```php
// Ambil koneksi PDO (singleton)
$pdo = db();

// SELECT satu baris
$stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch(); // array asosiatif atau false

// SELECT banyak baris
$stmt = db()->prepare('SELECT * FROM bookings WHERE user_id = ? ORDER BY created_at ASC');
$stmt->execute([$userId]);
$rows = $stmt->fetchAll();

// INSERT
$stmt = db()->prepare('INSERT INTO portofolio (judul, foto, kategori) VALUES (?, ?, ?)');
$stmt->execute([$judul, $foto, $kategori]);
$newId = (int) db()->lastInsertId();

// UPDATE
$stmt = db()->prepare('UPDATE bookings SET status = ? WHERE id = ?');
$stmt->execute([$status, $id]);
$rowsAffected = $stmt->rowCount();

// DELETE
$stmt = db()->prepare('DELETE FROM portofolio WHERE id = ?');
$stmt->execute([$id]);

// Transaksi (wajib untuk slot locking FCFS)
db()->beginTransaction();
try {
    // operasi 1
    // operasi 2
    db()->commit();
} catch (Exception $e) {
    db()->rollBack();
    throw $e;
}
```

### 1.3 Session & Auth

```php
// Cek login
if (!isLoggedIn()) { redirect(baseUrl('auth/login.php')); }

// Cek role admin
if (!isAdmin()) { redirect(baseUrl('auth/login.php')); }

// Ambil data user yang sedang login
$user = currentUser();
// returns: ['id', 'name', 'email', 'role']

// Set session setelah login berhasil
$_SESSION['user_id']    = $user['id'];
$_SESSION['user_name']  = $user['name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['role']       = $user['role'];
session_regenerate_id(true); // wajib setelah login
```

### 1.4 Flash Message

```php
// Set flash (sebelum redirect)
setFlash('success', 'Booking berhasil dibuat!');
setFlash('error',   'Slot sudah tidak tersedia.');
setFlash('warning', 'Pembayaran akan expired dalam 1 jam.');
setFlash('info',    'Admin sedang memproses booking Anda.');

// Tampilkan flash di view (letakkan setelah <body> atau di awal konten)
<?php renderFlash(); ?>
```

### 1.5 CSRF

```php
// Di dalam form HTML
<form method="POST">
    <?= csrfField() ?>
    <!-- field lain -->
</form>

// Di awal handler POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf(); // die jika token tidak valid
    // lanjut proses...
}
```

### 1.6 Sanitasi & Output

```php
// Sanitasi input (hilangkan tag HTML + trim)
$nama = sanitize($_POST['nama'] ?? '');

// Escape output ke HTML (wajib untuk semua data dari DB/user)
<?= e($data['nama']) ?>

// Format rupiah
echo formatRupiah(150000); // Rp 150.000

// Format tanggal Indonesia
echo formatTanggal('2026-07-15'); // Rabu, 15 Juli 2026

// Redirect
redirect(baseUrl('user/dashboard.php'));

// Generate kode booking
$kode = generateKodeBooking(); // QB-20260715-A3F9
```

---

## 2. MySQL / SQL

### 2.1 Query FCFS

```sql
-- Daftar booking diurutkan dari yang paling awal pesan (FCFS)
SELECT
    b.id,
    b.kode_booking,
    b.tanggal,
    b.status,
    b.created_at,
    u.name  AS nama_user,
    u.phone AS wa_user,
    j.nama  AS jenis_makeup,
    jt.label AS slot_jam
FROM bookings b
JOIN users u         ON b.user_id = u.id
JOIN jenis_makeup j  ON b.jenis_makeup_id = j.id
JOIN jam_tersedia jt ON b.jam_id = jt.id
ORDER BY b.created_at ASC;
```

### 2.2 Cek Ketersediaan Slot

```sql
-- Slot tersedia jika COUNT = 0
SELECT COUNT(*) AS terpakai
FROM bookings
WHERE tanggal    = '2026-07-15'
  AND jam_id     = 2
  AND slot_locked = 1
  AND status NOT IN ('cancelled');
```

### 2.3 Slot yang Sudah Terpakai pada Tanggal Tertentu

```sql
-- Dipakai untuk render tombol slot disabled di form booking
SELECT jam_id
FROM bookings
WHERE tanggal    = '2026-07-15'
  AND slot_locked = 1
  AND status NOT IN ('cancelled');
```

### 2.4 Auto-Cancel Booking Expired

```sql
-- Jalankan via cron job atau di awal setiap request
UPDATE bookings b
JOIN payments p ON p.booking_id = b.id
SET
    b.status      = 'cancelled',
    b.slot_locked = 0,
    p.status      = 'expired'
WHERE b.status  = 'waiting_payment'
  AND p.expired_at IS NOT NULL
  AND p.expired_at < NOW();
```

### 2.5 Statistik Dashboard Admin

```sql
SELECT
    COUNT(*) AS total,
    SUM(status = 'pending')               AS pending,
    SUM(status = 'pending_negotiation')   AS negosiasi,
    SUM(status = 'waiting_payment')       AS menunggu_bayar,
    SUM(status = 'waiting_confirmation')  AS menunggu_konfirmasi,
    SUM(status = 'confirmed')             AS terkonfirmasi,
    SUM(status = 'completed')             AS selesai,
    SUM(status = 'cancelled')             AS dibatalkan
FROM bookings;
```

---

## 3. Bootstrap 5

### 3.1 Komponen yang Digunakan

| Komponen | Kegunaan |
|----------|----------|
| `btn btn-rose` / `btn-outline-rose` | Tombol custom warna rose |
| `alert alert-{type}` | Flash message |
| `badge` | Label status booking |
| `card` | Card layanan, portofolio, dashboard |
| `table table-hover` | Tabel daftar booking |
| `modal` | Konfirmasi hapus, detail booking |
| `navbar` | Navigasi responsif |
| `collapse` | Sidebar mobile |
| `form-control`, `form-select` | Input form booking |
| `spinner-border` | Loading state saat proses Midtrans |

### 3.2 Badge Status Booking

```html
<!-- Gunakan class CSS custom sesuai status -->
<span class="badge badge-<?= e($booking['status']) ?>">
    <?= e(labelStatus($booking['status'])) ?>
</span>
```

```php
// Helper untuk label status dalam Bahasa Indonesia
function labelStatus(string $status): string {
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
```

### 3.3 Slot Jam (Disabled jika Terpakai)

```html
<?php foreach ($jamList as $jam): ?>
<?php $isBooked = in_array($jam['id'], $bookedSlots); ?>
<button
    type="button"
    class="slot-btn <?= $isBooked ? 'disabled' : '' ?>"
    data-jam-id="<?= $jam['id'] ?>"
    <?= $isBooked ? 'disabled' : '' ?>>
    <?= e($jam['label']) ?>
    <?php if ($isBooked): ?>
        <small class="d-block" style="font-size:.7rem">Terpakai</small>
    <?php endif; ?>
</button>
<?php endforeach; ?>
```

---

## 4. Midtrans Snap

### 4.1 Membuat Snap Token (Backend)

```php
// Di payment/process.php
$serverKey  = MIDTRANS_SERVER_KEY;
$orderId    = 'QB-' . $bookingId . '-' . time();
$grossAmount = (int) $dpAmount; // Midtrans minta integer

$payload = [
    'transaction_details' => [
        'order_id'     => $orderId,
        'gross_amount' => $grossAmount,
    ],
    'customer_details' => [
        'first_name' => $user['name'],
        'email'      => $user['email'],
        'phone'      => $user['phone'],
    ],
    'expiry' => [
        'unit'     => 'hours',
        'duration' => PAYMENT_EXPIRED_HOURS, // 24
    ],
];

$auth     = base64_encode($serverKey . ':');
$url      = MIDTRANS_IS_PRODUCTION
    ? 'https://app.midtrans.com/snap/v1/transactions'
    : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Basic ' . $auth,
    ],
]);
$response = json_decode(curl_exec($ch), true);
curl_close($ch);

$snapToken = $response['token'] ?? null;
```

### 4.2 Tampilkan Popup Midtrans (Frontend)

```html
<!-- Load snap.js sesuai environment -->
<script src="<?= MIDTRANS_SNAP_URL ?>" data-client-key="<?= MIDTRANS_CLIENT_KEY ?>"></script>

<button id="btnBayar" class="btn btn-rose">Bayar DP Sekarang</button>

<script>
document.getElementById('btnBayar').addEventListener('click', function() {
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';

    window.snap.pay('<?= $snapToken ?>', {
        onSuccess: function(result) {
            window.location.href = '<?= baseUrl('user/dashboard.php') ?>?payment=success';
        },
        onPending: function(result) {
            window.location.href = '<?= baseUrl('user/dashboard.php') ?>?payment=pending';
        },
        onError: function(result) {
            alert('Pembayaran gagal. Silakan coba lagi.');
            document.getElementById('btnBayar').disabled = false;
            document.getElementById('btnBayar').innerHTML = 'Bayar DP Sekarang';
        },
        onClose: function() {
            document.getElementById('btnBayar').disabled = false;
            document.getElementById('btnBayar').innerHTML = 'Bayar DP Sekarang';
        }
    });
});
</script>
```

### 4.3 Webhook Notification (Backend)

```php
// payment/notification.php
$payload = json_decode(file_get_contents('php://input'), true);

// Verifikasi signature Midtrans
$signatureKey = hash('sha512',
    $payload['order_id'] .
    $payload['status_code'] .
    $payload['gross_amount'] .
    MIDTRANS_SERVER_KEY
);

if ($signatureKey !== $payload['signature_key']) {
    http_response_code(403);
    exit('Invalid signature');
}

$orderId           = $payload['order_id'];
$transactionStatus = $payload['transaction_status'];
$fraudStatus       = $payload['fraud_status'] ?? '';

// Tentukan status payment sistem
if ($transactionStatus === 'settlement' ||
    ($transactionStatus === 'capture' && $fraudStatus === 'accept')) {
    $paymentStatus = 'success';
} elseif (in_array($transactionStatus, ['deny', 'cancel'])) {
    $paymentStatus = 'failed';
} elseif ($transactionStatus === 'expire') {
    $paymentStatus = 'expired';
} else {
    $paymentStatus = 'pending';
}

// Update payment
(new Payment())->updateStatus($orderId, $paymentStatus, $payload['payment_type'] ?? null, $payload);

// Jika sukses: kunci slot
if ($paymentStatus === 'success') {
    $payment = (new Payment())->findByOrderId($orderId);
    (new Booking())->lockSlot($payment['booking_id']);
}

http_response_code(200);
echo 'OK';
```

---

## 5. Upload File (Portofolio)

```php
// Di admin/portofolio.php saat handle POST
if (!empty($_FILES['foto']['name'])) {
    $fotoPath = uploadFile($_FILES['foto'], 'portofolio', 'foto');
    if ($fotoPath === false) {
        setFlash('error', 'Upload foto gagal. Pastikan format JPG/PNG/WEBP dan ukuran maks 5MB.');
        redirect(baseUrl('admin/portofolio.php'));
    }
}

// Hapus foto lama saat update/delete
$oldFoto = BASE_PATH . '/public/' . $item['foto'];
if (file_exists($oldFoto)) {
    unlink($oldFoto);
}
```

---

## 6. Kalkulasi Biaya

```php
// Hitung total dan DP
function hitungBiaya(float $hargaJasa, float $biayaTransport): array
{
    $total    = $hargaJasa + $biayaTransport;
    $dp       = floor($total * DP_PERCENT / 100); // floor agar tidak lebih
    $pelunasan = $total - $dp;
    return [
        'harga_jasa'       => $hargaJasa,
        'biaya_transport'  => $biayaTransport,
        'total_biaya'      => $total,
        'dp_amount'        => $dp,
        'pelunasan_amount' => $pelunasan,
    ];
}

// Contoh penggunaan:
$biaya = hitungBiaya(150000, 40000);
// total_biaya     = 190000
// dp_amount       = 57000  (30%)
// pelunasan_amount = 133000 (70%)
```

---

## 7. Validasi Lokasi Home Service

```php
function validasiProvinsi(string $provinsi): string
{
    $p = strtolower(trim($provinsi));

    $jatim = ['jawa timur', 'jatim'];
    $jawa  = [
        'jawa barat', 'jabar', 'jawa tengah', 'jateng',
        'dki jakarta', 'jakarta', 'banten',
        'yogyakarta', 'di yogyakarta', 'diy',
    ];

    if (in_array($p, $jatim)) return 'jatim';       // proses otomatis
    if (in_array($p, $jawa))  return 'jawa';        // pending_negotiation
    return 'luar_jawa';                              // tolak
}

// Penggunaan di form booking:
$hasil = validasiProvinsi($_POST['provinsi']);
if ($hasil === 'luar_jawa') {
    setFlash('error', 'Maaf, layanan home service hanya tersedia di Pulau Jawa.');
    redirect(baseUrl('booking/index.php'));
} elseif ($hasil === 'jawa') {
    $status = 'pending_negotiation';
    $biayaTransport = 0; // admin yang tentukan
} else {
    $status = 'waiting_payment';
    // hitung biaya transport dari zona_id
}
```

---

## 8. Checklist Fitur

Gunakan checklist ini untuk memantau progress pengembangan:

### Halaman Publik
- [ ] Homepage (hero, stats, layanan, portofolio, testimoni, cara booking)
- [ ] Tombol WhatsApp floating

### Autentikasi
- [ ] Register
- [ ] Login
- [ ] Logout

### Booking
- [ ] Form booking (pilih jenis, tanggal, slot, tipe layanan)
- [ ] Cek slot real-time (AJAX atau reload)
- [ ] Validasi lokasi home service
- [ ] Kalkulasi biaya otomatis
- [ ] Konfirmasi ringkasan sebelum submit
- [ ] FCFS + slot locking dalam transaksi DB

### Pembayaran
- [ ] Generate Midtrans Snap token
- [ ] Popup Midtrans di frontend
- [ ] Webhook notification handler
- [ ] Auto-cancel expired booking

### Dashboard User
- [ ] Statistik (total, menunggu, terkonfirmasi)
- [ ] Riwayat booking + detail
- [ ] Notifikasi in-app

### Dashboard Admin
- [ ] Daftar booking (FCFS order)
- [ ] Konfirmasi / tolak booking
- [ ] Negosiasi home service luar Jatim
- [ ] Tandai selesai
- [ ] Kelola portofolio (CRUD + upload foto)
- [ ] Kelola jenis makeup (CRUD)
- [ ] Kelola slot jam (CRUD)
- [ ] Pantau status pembayaran
