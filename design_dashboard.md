# design_dashboard.md — Desain Dashboard

**Proyek:** Sistem Informasi Booking Quemil Makeup  
**Warna Utama:** Rose `#c9637a` | Dark `#1a1a2e` | Rose Light `#f8e6ea`  
**Font:** Playfair Display (heading) + Poppins (body)  
**Framework:** Bootstrap 5.3

---

# BAGIAN A — Dashboard Admin

**File:** `public/admin/dashboard.php`  
**Akses:** Hanya role `admin`  
**Guard:** `requireAdmin()` di awal file

---

## A.1 Layout Keseluruhan

Layout terdiri dari **sidebar kiri tetap** + **area konten kanan** yang mengisi sisa lebar layar.

```
+--260px--+-----------------------------konten kanan--------------------------+
|         |                                                                   |
| SIDEBAR |  HEADER KONTEN                                                   |
|         |  (judul halaman + info admin)                                    |
|         |------------------------------------------------------------------ |
|         |                                                                   |
|         |  STATS CARDS (4 kartu sejajar)                                   |
|         |                                                                   |
|         |------------------------------------------------------------------ |
|         |                                                                   |
|         |  TABEL BOOKING                                                   |
|         |  (FCFS: diurutkan created_at ASC)                                |
|         |                                                                   |
|         |                                                                   |
+---------+-------------------------------------------------------------------+
```

**Spesifikasi layout:**
- Sidebar: `width: 260px`, `min-height: 100vh`, background `#1a1a2e` (dark)
- Konten kanan: `flex: 1`, background `#f8f9fa`, padding 1.5rem
- Keduanya dibungkus `d-flex` horizontal
- Mobile: sidebar collapse menjadi menu hamburger di atas

---

## A.2 Sidebar

```
+--------------------------+
|  [Q] Quemil Makeup       |  <- logo + brand, padding atas
|                          |
|  [grid] Dashboard        |  <- active: background rose 30%
|  [calendar] Booking      |
|  [images] Portofolio     |
|  [stars] Jenis Makeup    |
|  [clock] Slot Jam        |
|                          |
|  ~~~~~~~~~~~~~~~~~~~~~~~~|
|                          |
|  [exit] Keluar           |  <- di bagian bawah sidebar
+--------------------------+
```

**Spesifikasi sidebar:**
- Background: `#1a1a2e`
- Logo: lingkaran rose 36x36 + teks "Quemil Makeup" font Playfair putih
- Menu item: padding `.6rem 1rem`, border-radius 8px, icon Bootstrap Icons
- Warna link normal: `rgba(255,255,255,0.7)`
- Hover & active: warna putih, background `rgba(201,99,122,0.3)` (rose transparan)
- Tombol Keluar: di paling bawah, warna rose
- Lebar tetap 260px pada desktop

**Menu Sidebar Admin:**

| Icon | Label | File Tujuan |
|------|-------|-------------|
| `bi-speedometer2` | Dashboard | `admin/dashboard.php` |
| `bi-calendar-check` | Booking | `admin/bookings.php` |
| `bi-images` | Portofolio | `admin/portofolio.php` |
| `bi-stars` | Jenis Makeup | `admin/jenis-makeup.php` |
| `bi-clock` | Slot Jam | `admin/slot-jam.php` |
| `bi-box-arrow-right` | Keluar | `auth/logout.php` |

---

## A.3 Header Konten

```
+-------------------------------------------------------------------+
|  Dashboard Admin                    Selamat datang, Admin Quemil  |
|  Sistem Informasi Booking                                         |
+-------------------------------------------------------------------+
```

**Spesifikasi:**
- Kiri: Judul halaman (`h4`, font Playfair) + subjudul kecil muted
- Kanan: Teks sapaan + nama admin yang sedang login
- Border bawah tipis sebagai pemisah dari konten

---

## A.4 Stats Cards

```
+---------------+ +---------------+ +------------------+ +---------------+
|  [icon biru]  | | [icon kuning] | |   [icon ungu]    | | [icon hijau]  |
|               | |               | |                  | |               |
|      24       | |       8       | |        5         | |      11       |
|  Total        | | Menunggu      | | Menunggu         | | Terkonfirmasi |
|  Booking      | | Pembayaran    | | Konfirmasi       | |               |
+---------------+ +---------------+ +------------------+ +---------------+
```

**Spesifikasi per kartu:**
- Border-radius 12px, shadow ringan, background putih
- Icon lingkaran kecil (40x40) warna berbeda per kartu
- Angka besar `h3` bold
- Label kecil teks muted
- Warna icon per kartu:
  - Total Booking → biru `#0d6efd`
  - Menunggu Pembayaran → kuning `#ffc107`
  - Menunggu Konfirmasi → ungu `#6f42c1`
  - Terkonfirmasi → hijau `#198754`

---

## A.5 Tabel Booking

**Urutan:** `ORDER BY created_at ASC` (FCFS — yang pesan duluan tampil di atas)

```
+-------------------------------------------------------------------+
|  Daftar Booking                        [Filter: semua status v]   |
+----+----------+-----------+----------+-------+--------+----------+
| No | Kode     | Nama User | Jenis    | Tgl   | Status | Aksi     |
+----+----------+-----------+----------+-------+--------+----------+
| 1  | QB-..    | Siti R.   | Wisuda   | 15/07 | [konf] | [v] [x]  |
| 2  | QB-..    | Dewi K.   | Pengantin| 16/07 | [bayar]| [v] [x]  |
| 3  | QB-..    | Rina W.   | Karnaval | 17/07 | [nego] | [v] [x]  |
+----+----------+-----------+----------+-------+--------+----------+
                                                  [< 1 2 3 >]       
```

**Kolom tabel:**

| Kolom | Sumber Data | Keterangan |
|-------|-------------|------------|
| No | Loop index | Nomor urut |
| Kode Booking | `bookings.kode_booking` | Format QB-YYYYMMDD-XXXX |
| Nama User | `users.name` | Nama pelanggan |
| No. WA | `users.phone` | Klik buka WhatsApp |
| Jenis Makeup | `jenis_makeup.nama` | Nama paket |
| Tanggal | `bookings.tanggal` | Format: dd/mm/yyyy |
| Slot Jam | `jam_tersedia.label` | Contoh: 08:00-10:00 |
| Tipe | `bookings.tipe_layanan` | Studio / Home Service |
| DP | `bookings.dp_amount` | Format Rupiah |
| Status | `bookings.status` | Badge warna per status |
| Aksi | - | Tombol Konfirmasi / Tolak / Detail |

**Badge Status (sesuai `style.css`):**

| Status | Label | Warna Badge |
|--------|-------|-------------|
| `pending` | Menunggu | Kuning |
| `pending_negotiation` | Negosiasi | Oranye |
| `waiting_payment` | Belum Bayar | Biru muda |
| `waiting_confirmation` | Menunggu Konfirmasi | Ungu |
| `confirmed` | Terkonfirmasi | Hijau |
| `completed` | Selesai | Biru |
| `cancelled` | Dibatalkan | Merah |

**Tombol Aksi per baris:**
- `[v]` Konfirmasi → muncul jika status `waiting_confirmation`
- `[x]` Tolak → muncul jika status `waiting_confirmation` atau `pending_negotiation`
- `[i]` Detail → selalu tampil, buka modal detail booking
- `[chat]` WhatsApp → buka `wa.me/{phone}` di tab baru

---

## A.6 Modal Konfirmasi

Modal muncul saat admin klik tombol Konfirmasi atau Tolak:

```
+--------------------------------------------+
|  Konfirmasi Booking                    [x]  |
+--------------------------------------------+
|                                             |
|  Kode   : QB-20260715-A3F9                 |
|  User   : Siti Rahayu                       |
|  Jenis  : Makeup Wisuda                     |
|  Tanggal: Rabu, 15 Juli 2026               |
|  Slot   : 08:00 - 10:00                    |
|  DP     : Rp 45.000                         |
|                                             |
|  Catatan Admin: [textarea opsional]         |
|                                             |
|            [Batal]  [Ya, Konfirmasi]        |
+--------------------------------------------+
```

---

## A.7 Halaman Kelola Portofolio (`admin/portofolio.php`)

```
+-------------------------------------------------------------------+
|  Kelola Portofolio              [+ Tambah Foto Baru]             |
+-------------------------------------------------------------------+
|                                                                   |
|  +----------+  +----------+  +----------+  +----------+          |
|  | [foto]   |  | [foto]   |  | [foto]   |  | [foto]   |          |
|  |          |  |          |  |          |  |          |          |
|  | Judul    |  | Judul    |  | Judul    |  | Judul    |          |
|  | Wisuda   |  | Pengantin|  | Karnaval |  | Kondangan|          |
|  | [Edit][x]|  | [Edit][x]|  | [Edit][x]|  | [Edit][x]|          |
|  +----------+  +----------+  +----------+  +----------+          |
|                                                                   |
+-------------------------------------------------------------------+
```

**Form Tambah/Edit Portofolio (dalam modal atau halaman baru):**

```
+--------------------------------------------+
|  Tambah Foto Portofolio               [x]  |
+--------------------------------------------+
|  Judul Foto  : [____________________]       |
|  Kategori    : [____________________]       |
|  Deskripsi   : [textarea             ]      |
|  Upload Foto : [Pilih File]                 |
|  Urutan      : [__]                         |
|  Status      : ( ) Aktif  ( ) Nonaktif      |
|                                             |
|               [Batal]  [Simpan]             |
+--------------------------------------------+
```

---

---

# BAGIAN B — Dashboard User

**File:** `public/user/dashboard.php`  
**Akses:** Hanya user yang sudah login  
**Guard:** `requireLogin()` di awal file

---

## B.1 Layout Keseluruhan

Layout **tanpa sidebar** — menggunakan navbar standar di atas + konten full width.

```
+------------------------------------------------------------------+
| NAVBAR (logo + menu + nama user + tombol Keluar)                 |
+------------------------------------------------------------------+
|                                                                  |
|  GREETING + TOMBOL BOOKING                                       |
|                                                                  |
+------------------------------------------------------------------+
|                                                                  |
|  STATS CARDS (3 kartu sejajar)                                   |
|                                                                  |
+------------------------------------------------------------------+
|                                                                  |
|  TABEL RIWAYAT BOOKING                                           |
|                                                                  |
+------------------------------------------------------------------+
|                                                                  |
|  FOOTER                                                          |
+------------------------------------------------------------------+
```

---

## B.2 Navbar Dashboard User

```
+------------------------------------------------------------------+
| [Q] Quemil Makeup   Beranda  Portofolio   [Siti Rahayu v] [Keluar]|
+------------------------------------------------------------------+
```

**Spesifikasi:**
- Sama dengan navbar publik
- Tombol Masuk/Daftar diganti dengan nama user (dropdown opsional) + tombol Keluar
- Tombol Keluar: outline-rose kecil

---

## B.3 Greeting Section

```
+------------------------------------------------------------------+
|                                                                  |
|  Halo, Siti Rahayu!                  [+ Booking Sekarang]       |
|  Selamat datang di dashboard Anda.                               |
|                                                                  |
+------------------------------------------------------------------+
```

**Spesifikasi:**
- Kiri: Teks sapaan "Halo, {nama}!" (font Playfair, ukuran h4) + subjudul kecil muted
- Kanan: Tombol rose `[+ Booking Sekarang]` dengan icon kalender
- Background putih, padding 1.5rem, border-radius 12px, shadow ringan
- Margin bawah 1.5rem

---

## B.4 Stats Cards

```
+---------------------+ +---------------------+ +---------------------+
|   [icon kalender]   | |   [icon jam pasir]  | |   [icon centang]    |
|                     | |                     | |                     |
|          3          | |          1          | |          2          |
|   Total Booking     | |   Menunggu          | |   Terkonfirmasi     |
+---------------------+ +---------------------+ +---------------------+
```

**Spesifikasi per kartu:**
- Background putih, border-radius 12px, shadow `0 2px 12px rgba(0,0,0,.07)`
- Padding 1.5rem
- Icon Bootstrap Icons dalam lingkaran kecil (40x40)
- Angka besar `h3` bold
- Label kecil teks muted
- Warna icon:
  - Total Booking → rose `#c9637a`
  - Menunggu → kuning `#ffc107`
  - Terkonfirmasi → hijau `#198754`

---

## B.5 Tabel Riwayat Booking

**Urutan:** `ORDER BY created_at ASC` (sesuai FCFS)

```
+-------------------------------------------------------------------+
|  Riwayat Booking Saya                                            |
+------+----------+-----------+---------+-------+--------+---------+
| No   | Kode     | Jenis     | Tanggal | Slot  | Tipe   | Status  |
+------+----------+-----------+---------+-------+--------+---------+
|  1   | QB-..    | Wisuda    | 15 Jul  | 08:00 | Studio | [konf]  |
|  2   | QB-..    | Kondangan | 20 Jul  | 10:00 | Home   | [bayar] |
+------+----------+-----------+---------+-------+--------+---------+
```

**Kolom tabel:**

| Kolom | Sumber Data | Keterangan |
|-------|-------------|------------|
| No | Loop index | Nomor urut |
| Kode Booking | `bookings.kode_booking` | Format QB-YYYYMMDD-XXXX |
| Jenis Makeup | `jenis_makeup.nama` | Nama paket |
| Tanggal | `bookings.tanggal` | Format: dd M yyyy |
| Slot Jam | `jam_tersedia.label` | Contoh: 08:00-10:00 |
| Tipe Layanan | `bookings.tipe_layanan` | Studio / Home Service |
| Total Biaya | `bookings.total_biaya` | Format Rupiah |
| DP | `bookings.dp_amount` | Format Rupiah |
| Status | `bookings.status` | Badge warna |
| Aksi | - | Tombol Detail |

**Tombol Aksi per baris:**
- `[Detail]` → buka halaman `booking/detail.php?id={id}` atau modal
- `[Bayar DP]` → tampil hanya jika status `waiting_payment`, buka Midtrans Snap
- `[Chat Admin]` → buka WhatsApp admin

---

## B.6 Empty State (Belum Ada Booking)

Ditampilkan jika user belum pernah melakukan booking:

```
+-------------------------------------------------------------------+
|                                                                   |
|                  ( icon bi-calendar-x besar )                     |
|                                                                   |
|              Belum ada booking.                                   |
|        Yuk, booking layanan makeup pertamamu!                     |
|                                                                   |
|                 [ Booking Sekarang ]                              |
|                                                                   |
+-------------------------------------------------------------------+
```

**Spesifikasi:**
- Icon `bi-calendar-x` ukuran 3.5rem, warna rose muted
- Teks tengah, warna abu-abu
- Tombol rose solid di bawah
- Ditampilkan di dalam area tabel jika `count($bookings) === 0`

---

## B.7 Notifikasi In-App

Notifikasi ditampilkan sebagai panel kecil di bawah greeting (jika ada notifikasi belum dibaca):

```
+-------------------------------------------------------------------+
|  [bell] Notifikasi (2 belum dibaca)                              |
|------------------------------------------------------------------ |
|  [!] Booking QB-20260715 telah dikonfirmasi admin.   5 menit lalu |
|  [i] Pembayaran DP berhasil diterima.               10 menit lalu |
+-------------------------------------------------------------------+
```

**Spesifikasi:**
- Hanya tampil jika ada notifikasi `is_read = 0`
- Icon bell dengan badge angka jika ada yang belum dibaca
- Klik notifikasi → tandai `is_read = 1`
- Warna background per tipe: info (biru muda), success (hijau muda), warning (kuning muda)

---

## B.8 Halaman Detail Booking (`booking/detail.php`)

```
+-------------------------------------------------------------------+
|  Detail Booking                          [< Kembali ke Dashboard] |
+-------------------------------------------------------------------+
|                                                                   |
|  Kode Booking  : QB-20260715-A3F9        Status: [Terkonfirmasi] |
|  Jenis Makeup  : Makeup Wisuda                                    |
|  Tanggal       : Rabu, 15 Juli 2026                               |
|  Slot Jam      : 08:00 - 10:00                                    |
|  Tipe Layanan  : Studio                                           |
|                                                                   |
|  ---------------------------------------------------------------- |
|  Harga Jasa    : Rp 150.000                                       |
|  Biaya Transport: Rp 0                                            |
|  Total Biaya   : Rp 150.000                                       |
|  DP (30%)      : Rp 45.000             [Sudah Dibayar / Bayar DP] |
|  Pelunasan     : Rp 105.000            (dibayar pada hari H)      |
|                                                                   |
|  ---------------------------------------------------------------- |
|  Catatan Admin : -                                                |
|                                                                   |
|                               [Chat Admin via WhatsApp]           |
+-------------------------------------------------------------------+
```

---

## Ringkasan Perbedaan Admin vs User

| Aspek | Dashboard Admin | Dashboard User |
|-------|----------------|----------------|
| Layout | Sidebar kiri + konten kanan | Navbar atas + konten full |
| Akses | `requireAdmin()` | `requireLogin()` |
| Data booking | Semua booking semua user | Hanya booking milik user sendiri |
| Urutan tabel | `created_at ASC` (FCFS global) | `created_at ASC` (milik sendiri) |
| Aksi booking | Konfirmasi, Tolak, Selesai | Detail, Bayar DP |
| Fitur tambahan | Kelola portofolio, jenis makeup, slot jam | Notifikasi, detail booking |
| Stats cards | 4 kartu (total, menunggu bayar, menunggu konfirm, terkonfirmasi) | 3 kartu (total, menunggu, terkonfirmasi) |
