# Quemil Makeup Booking System

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)
![Midtrans](https://img.shields.io/badge/Midtrans-Snap-00A9E0?style=for-the-badge)

Aplikasi web booking layanan makeup untuk Quemil Makeup. Project ini membantu pelanggan memilih layanan, membuat booking, membayar DP melalui Midtrans Snap, dan memantau status pesanan. Admin dapat mengelola booking, layanan, portofolio, testimoni, serta foto hero dari dashboard.

## Fitur Utama

- Landing page responsif dengan daftar layanan, portofolio, testimoni, dan foto hero dinamis.
- Registrasi dan login pelanggan.
- Booking online berdasarkan layanan, tanggal, jam mulai, jam selesai, lokasi, dan jumlah orang.
- Perhitungan otomatis harga jasa, transport, total biaya, DP, dan sisa pelunasan.
- Alur approval admin sebelum pelanggan melakukan pembayaran.
- Integrasi Midtrans Snap untuk pembayaran DP.
- Callback, notification handler, dan sinkronisasi status pembayaran.
- Sistem FCFS untuk mengunci slot setelah pembayaran berhasil.
- Dashboard admin untuk booking, layanan makeup, portofolio, testimoni, dan foto hero.
- Proteksi CSRF pada form dan escaping output untuk tampilan.
- Konfigurasi lokal aman agar API key tidak ikut ter-push ke GitHub.

## Tech Stack

- PHP native dengan PDO
- MySQL atau MariaDB
- Bootstrap 5.3
- Bootstrap Icons
- AOS Animate On Scroll
- Midtrans Snap
- Laragon atau XAMPP untuk local development

## Struktur Project

```text
quemil-booking/
|-- app/
|   |-- helpers/          # Helper session, auth, csrf, format, redirect
|   `-- models/           # Model database: Booking, Payment, User, dll.
|-- config/
|   |-- config.php        # Config aman, membaca env/config.local.php
|   |-- config.local.example.php
|   `-- database.php      # Koneksi PDO
|-- database/
|   |-- quemil_booking.sql
|   `-- migration_*.sql
|-- public/
|   |-- admin/            # Dashboard dan CRUD admin
|   |-- auth/             # Login, register, logout
|   |-- booking/          # Pilih layanan, form booking, detail booking
|   |-- payment/          # Midtrans process, callback, notification
|   |-- uploads/          # Asset upload aplikasi
|   `-- index.php         # Halaman utama
|-- views/partials/       # Header, navbar, footer, sidebar admin
`-- folder markdown/      # Dokumentasi pendukung project
```

## Kebutuhan Sistem

- PHP 8.0 atau lebih baru
- MySQL/MariaDB
- Ekstensi PHP: `pdo_mysql`, `curl`, `fileinfo`
- Web server lokal seperti Laragon, XAMPP, Apache, atau Nginx
- Akun Midtrans Sandbox untuk mencoba pembayaran

## Instalasi Lokal

1. Clone repository ke folder web server lokal.

```bash
git clone https://github.com/KholidFirman7789/php-quemil-booking.git
```

Jika memakai Laragon, letakkan folder project di:

```text
C:\laragon\www\quemil-booking
```

Jika memakai XAMPP, letakkan di:

```text
C:\xampp\htdocs\quemil-booking
```

2. Buat database baru bernama `quemil_booking`.

3. Import database utama.

```bash
mysql -u root quemil_booking < database/quemil_booking.sql
```

Bisa juga import lewat phpMyAdmin dengan memilih file:

```text
database/quemil_booking.sql
```

4. Buat konfigurasi lokal dari contoh.

```bash
cp config/config.local.example.php config/config.local.php
```

Di Windows PowerShell:

```powershell
Copy-Item config\config.local.example.php config\config.local.php
```

5. Isi konfigurasi lokal sesuai environment.

```php
define('APP_URL', 'http://localhost/quemil-booking/public');

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'quemil_booking');
define('DB_USER', 'root');
define('DB_PASS', '');

define('MIDTRANS_SERVER_KEY', '<MIDTRANS_SERVER_KEY>');
define('MIDTRANS_CLIENT_KEY', '<MIDTRANS_CLIENT_KEY>');
define('MIDTRANS_IS_PRODUCTION', false);
```

6. Buka aplikasi di browser.

```text
http://localhost/quemil-booking/public
```

## Akun Admin Development

Database seed menyediakan akun admin untuk pengujian lokal:

```text
Email    : admin@quemil.com
Password : admin123
```

Segera ganti password ini jika project dipakai di server publik.

## Alur Penggunaan

1. Pelanggan registrasi atau login.
2. Pelanggan memilih layanan makeup.
3. Pelanggan mengisi tanggal, jam, lokasi, dan detail booking.
4. Booking masuk ke status menunggu approval admin.
5. Admin meninjau booking dari dashboard.
6. Jika disetujui, pelanggan dapat membayar DP melalui Midtrans Snap.
7. Setelah pembayaran sukses, slot dikunci dan booking terkonfirmasi.
8. Admin dapat menyelesaikan atau membatalkan booking sesuai kondisi lapangan.

## Status Booking

| Status | Keterangan |
| --- | --- |
| `pending` | Booking baru dibuat. |
| `pending_approval` | Booking menunggu persetujuan admin. |
| `pending_negotiation` | Booking perlu negosiasi atau evaluasi admin. |
| `waiting_payment` | Booking disetujui dan menunggu pembayaran DP. |
| `waiting_confirmation` | Pembayaran masuk, menunggu konfirmasi proses. |
| `confirmed` | Booking sudah terkonfirmasi dan slot terkunci. |
| `completed` | Layanan selesai. |
| `cancelled` | Booking dibatalkan atau expired. |

## Keamanan Konfigurasi

Project ini memakai `config/config.local.php` untuk menyimpan credential lokal. File tersebut sudah masuk `.gitignore`, jadi tidak akan ikut commit.

Jangan menulis data berikut langsung ke file yang di-track Git:

- Midtrans server key
- Midtrans client key
- Password database production
- Token, secret, atau credential lain

Gunakan salah satu dari dua cara aman berikut:

- Simpan credential di `config/config.local.php` untuk development lokal.
- Simpan credential sebagai environment variable untuk deployment.

## Deployment Singkat

- Set `APP_ENV` menjadi `production`.
- Set `APP_URL` sesuai domain production.
- Gunakan database user khusus dengan password kuat.
- Isi Midtrans production key melalui environment/config server.
- Pastikan folder `public/uploads` writable oleh web server.
- Arahkan document root web server ke folder `public`.
- Nonaktifkan tampilan error detail di production.

## Catatan Midtrans

Untuk local development, gunakan Midtrans Sandbox dan set:

```php
define('MIDTRANS_IS_PRODUCTION', false);
```

Untuk production, ganti ke credential production dan set:

```php
define('MIDTRANS_IS_PRODUCTION', true);
```

Pastikan URL notification/callback sudah dapat diakses dari internet saat memakai webhook Midtrans production.

## Lisensi

Project ini dibuat untuk kebutuhan sistem informasi booking Quemil Makeup.