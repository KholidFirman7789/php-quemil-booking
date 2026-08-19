# RULES.md — Aturan Teknis Pengembangan

**Proyek:** Sistem Informasi Booking Quemil Makeup  
**Stack:** PHP Native + MySQL + Bootstrap 5  
**Dibuat oleh:** Abdul Khalid Firmansyah (222355201013)

---

## 1. Tech Stack

| Komponen | Teknologi | Versi |
|----------|-----------|-------|
| Backend | PHP Native (tanpa framework) | 8.0+ |
| Database | MySQL / MariaDB | 8.0+ / 10.4+ |
| Frontend | Bootstrap 5 + Vanilla JS | 5.3.x |
| Icon | Bootstrap Icons | 1.11.x |
| Font | Google Fonts (Playfair Display + Poppins) | - |
| Payment | Midtrans Snap | - |
| Web Server (dev) | Laragon (Apache + MySQL) | - |
| Editor | Visual Studio Code | - |
| Browser (test) | Brave Browser | - |

---

## 2. Struktur Folder

```
quemil-booking/
├── app/
│   ├── controllers/        # Logika bisnis (opsional, bisa inline di public)
│   ├── models/             # Class model per tabel database
│   └── helpers/
│       └── functions.php   # Helper global (session, CSRF, format, redirect)
├── config/
│   ├── config.php          # Konstanta aplikasi (APP_URL, DB, Midtrans, dll)
│   └── database.php        # Singleton PDO connection + fungsi db()
├── database/
│   └── quemil_booking.sql  # Skema + seed data lengkap
├── public/                 # Document root web server
│   ├── index.php           # Homepage
│   ├── auth/
│   │   ├── login.php
│   │   ├── register.php
│   │   └── logout.php
│   ├── booking/
│   │   ├── index.php           # Form booking
│   │   └── detail.php          # Detail booking user
│   ├── user/
│   │   └── dashboard.php
│   ├── admin/
│   │   ├── dashboard.php
│   │   ├── bookings.php
│   │   ├── portofolio.php
│   │   ├── jenis-makeup.php
│   │   └── slot-jam.php
│   ├── payment/
│   │   ├── process.php         # Buat Midtrans token
│   │   └── notification.php    # Webhook Midtrans
│   ├── assets/
│   │   ├── css/style.css
│   │   ├── js/
│   │   └── img/
│   └── uploads/
│       ├── portofolio/         # Foto portofolio
│       └── payments/           # (opsional) bukti bayar manual
├── views/
│   ├── partials/
│   │   ├── header.php          # <!DOCTYPE> sampai </head> + <body>
│   │   ├── navbar.php          # Navigasi
│   │   └── footer.php          # Footer + script JS + </body></html>
│   ├── auth/                   # View khusus auth (jika dipisah)
│   ├── user/
│   └── admin/
├── SCHEMA.md
├── PRD.md
├── RULES.md
└── revisi sempro.docx
```

> **Aturan:** `public/` adalah satu-satunya folder yang dapat diakses langsung oleh browser. Folder `app/`, `config/`, `database/`, dan `views/` tidak boleh diakses langsung.

---

## 3. Konvensi Penamaan

### File & Folder

| Jenis | Konvensi | Contoh |
|-------|----------|--------|
| File PHP halaman | `kebab-case.php` | `jenis-makeup.php` |
| File PHP class | `PascalCase.php` | `Booking.php` |
| File CSS/JS | `kebab-case` | `style.css`, `booking.js` |
| Folder | `kebab-case` | `zona-transport/` |

### PHP

| Jenis | Konvensi | Contoh |
|-------|----------|--------|
| Class | PascalCase | `class Booking` |
| Method/Fungsi | camelCase | `findById()`, `getAll()` |
| Variabel | camelCase | `$bookingId`, `$totalBiaya` |
| Konstanta | SCREAMING_SNAKE_CASE | `APP_URL`, `DP_PERCENT` |
| Tabel DB | snake_case | `jenis_makeup`, `zona_transport` |
| Kolom DB | snake_case | `created_at`, `tipe_layanan` |

### Model

Setiap tabel database memiliki satu file model di `app/models/`:

| Tabel | Model |
|-------|-------|
| `users` | `User.php` |
| `bookings` | `Booking.php` |
| `payments` | `Payment.php` |
| `jenis_makeup` | `JenisMakeup.php` |
| `jam_tersedia` | `JamTersedia.php` |
| `zona_transport` | `ZonaTransport.php` |
| `portofolio` | `Portofolio.php` |
| `notifications` | `Notification.php` |

---

## 4. Aturan Keamanan

### 4.1 CSRF Protection

Semua form POST **wajib** menyertakan CSRF token:

```php
// Di dalam form HTML
<?= csrfField() ?>

// Di awal handler POST
verifyCsrf(); // akan die() jika token tidak cocok
```

### 4.2 SQL Injection Prevention

Selalu gunakan **prepared statements dengan PDO**. Dilarang string concatenation untuk query:

```php
// BENAR
$stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);

// SALAH - DILARANG
$result = db()->query("SELECT * FROM users WHERE email = '$email'");
```

### 4.3 Password

- Selalu hash dengan `password_hash($password, PASSWORD_BCRYPT)`
- Verifikasi dengan `password_verify($input, $hash)`
- Panjang minimum password: **6 karakter**
- Jangan pernah simpan plain text password

### 4.4 Session

```php
// Wajib di awal setiap halaman yang butuh auth
startSession();

// Setelah login berhasil, regenerate session ID
session_regenerate_id(true);

// Isi session yang diizinkan
$_SESSION['user_id'];    // int
$_SESSION['user_name'];  // string
$_SESSION['user_email']; // string
$_SESSION['role'];       // 'user' | 'admin'
```

### 4.5 Output Escaping

Semua data dari database atau input user yang ditampilkan ke HTML **wajib** di-escape:

```php
// BENAR
<?= e($user['name']) ?>

// SALAH - DILARANG
<?= $user['name'] ?>
```

Fungsi `e()` adalah alias dari `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')`.

### 4.6 Upload File

- Validasi MIME type (bukan hanya ekstensi): hanya `image/jpeg`, `image/png`, `image/webp`
- Maksimal ukuran file: **5 MB**
- Nama file di-generate ulang (jangan pakai nama asli dari user)
- Simpan di luar document root atau di `public/uploads/` dengan `.htaccess` yang memblokir eksekusi PHP

### 4.7 Midtrans Webhook

- Selalu verifikasi **signature key** dari notifikasi Midtrans sebelum update status
- Format: `SHA512(order_id + status_code + gross_amount + server_key)`
- Tolak request jika signature tidak cocok (return HTTP 403)

---

## 5. Aturan FCFS (First Come First Served)

### Prinsip

> Booking yang memiliki nilai `created_at` lebih kecil (lebih awal) mendapat prioritas lebih tinggi.

### Implementasi Wajib

1. **Jangan pernah** set `created_at` secara manual — biarkan MySQL isi otomatis via `DEFAULT CURRENT_TIMESTAMP`.

2. **Slot locking** dilakukan di dalam transaksi database (`BEGIN ... COMMIT`) menggunakan `SELECT ... FOR UPDATE` untuk mencegah race condition:

```php
$this->db->beginTransaction();
try {
    $stmt = $this->db->prepare('SELECT * FROM bookings WHERE id = ? FOR UPDATE');
    $stmt->execute([$bookingId]);
    // cek ulang konflik slot
    // update slot_locked = 1
    $this->db->commit();
} catch (Exception $e) {
    $this->db->rollBack();
    throw $e;
}
```

3. **Query admin** selalu urutkan dengan `ORDER BY created_at ASC` agar admin memproses booking dari yang paling awal:

```sql
SELECT * FROM bookings ORDER BY created_at ASC;
```

4. **Slot dianggap terpakai** jika ada booking lain dengan kondisi:
   - `tanggal` dan `jam_id` sama
   - `slot_locked = 1`
   - `status NOT IN ('cancelled')`

5. Jika slot sudah terkunci saat user B mencoba bayar, booking user B **dibatalkan** dan DP di-refund.

---

## 6. Aturan Status Booking

### Transisi Status yang Diizinkan

```
pending → waiting_payment
pending → pending_negotiation
pending → cancelled

pending_negotiation → waiting_payment   (admin setuju)
pending_negotiation → cancelled          (admin tolak)

waiting_payment → waiting_confirmation  (DP berhasil, slot dikunci)
waiting_payment → cancelled             (expired 24 jam)

waiting_confirmation → confirmed         (admin konfirmasi)
waiting_confirmation → cancelled         (admin tolak)

confirmed → completed
confirmed → cancelled
```

### Aturan Slot Locking

| Status | slot_locked |
|--------|-------------|
| pending | 0 |
| pending_negotiation | 0 |
| waiting_payment | 0 |
| waiting_confirmation | **1** |
| confirmed | **1** |
| completed | **1** |
| cancelled | **0** (reset saat cancel) |

---

## 7. Aturan Pembayaran

- DP = **30%** dari `total_biaya` (definisi via konstanta `DP_PERCENT = 30`)
- Batas waktu pembayaran DP = **24 jam** sejak booking dibuat (`PAYMENT_EXPIRED_HOURS = 24`)
- `order_id` ke Midtrans: format `QB-{booking_id}-{timestamp}`, unik, tidak boleh diulang
- Setelah `payment.status = 'success'` → langsung kunci slot (`slot_locked = 1`)
- Pelunasan 70% dilakukan **manual** pada hari H, tidak melalui sistem

---

## 8. Aturan Kalkulasi Biaya Transport

```php
// Urutan logika validasi provinsi untuk home service:

$provinsiJatim = ['jawa timur', 'jatim'];
$provinsiJawa  = ['jawa barat', 'jabar', 'jawa tengah', 'jateng',
                  'dki jakarta', 'jakarta', 'banten',
                  'yogyakarta', 'di yogyakarta', 'diy'];

$provinsi = strtolower(trim($input));

if (in_array($provinsi, $provinsiJatim)) {
    // Proses otomatis, hitung biaya dari tabel zona_transport
    $status = 'waiting_payment';
} elseif (in_array($provinsi, $provinsiJawa)) {
    // Negosiasi dengan admin
    $status = 'pending_negotiation';
    $biayaTransport = 0; // admin yang tentukan
} else {
    // Luar Pulau Jawa - tolak
    // Tampilkan error, jangan buat record booking
}
```

---

## 9. Aturan Upload & Path File

- Path foto disimpan **relatif** dari `public/`, bukan path absolut:
  - Benar: `uploads/portofolio/foto123.jpg`
  - Salah: `C:/laragon/www/quemil-booking/public/uploads/portofolio/foto123.jpg`
- Nama file di-generate: `{prefix}_{timestamp}_{random8hex}.{ext}`
- Folder upload: `public/uploads/portofolio/` dan `public/uploads/payments/`
- Saat hapus item portofolio: hapus juga file foto dari disk dengan `unlink()`

---

## 10. Aturan Include & BASE_PATH

Setiap file PHP **wajib** mendefinisikan `BASE_PATH` jika belum terdefinisi:

```php
defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));
// atau sesuaikan berapa level ke atas
```

Semua include menggunakan `BASE_PATH` sebagai acuan, bukan path relatif:

```php
// BENAR
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/app/helpers/functions.php';

// SALAH
require_once '../../config/config.php';
```

---

## 11. Aturan Kode Booking

- Format: `QB-YYYYMMDD-XXXX` (contoh: `QB-20260715-A3F9`)
- XXXX = 4 karakter hex uppercase dari `uniqid()`
- Dibuat via fungsi `generateKodeBooking()` di `functions.php`
- Unik di level database via constraint `UNIQUE` pada kolom `kode_booking`

---

## 12. Aturan Tampilan & UX

- Warna utama: `#c9637a` (rose) — didefinisikan sebagai CSS variable `--rose`
- Font heading: **Playfair Display** (serif)
- Font body: **Poppins** (sans-serif)
- Semua halaman wajib responsif (Bootstrap grid + breakpoints)
- Slot yang sudah terpakai ditampilkan dengan atribut `disabled` dan `text-decoration: line-through`
- Flash message menggunakan Bootstrap Alert yang bisa di-dismiss
- Tombol WhatsApp floating hadir di semua halaman publik dan dashboard user

---

## 13. Hal yang Dilarang

- Dilarang menggunakan **framework PHP** (Laravel, CodeIgniter, Symfony, dll)
- Dilarang query SQL dengan **string concatenation** dari input user
- Dilarang menyimpan **plain text password**
- Dilarang mengubah nilai `created_at` pada tabel `bookings` secara manual
- Dilarang mengakses folder di luar `public/` langsung via URL browser
- Dilarang commit **API key / Server Key Midtrans** ke repository
- Dilarang menggunakan `$_GET` / `$_POST` langsung tanpa sanitasi di output HTML
