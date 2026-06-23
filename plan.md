# plan.md — Rencana Pengembangan Bertahap

**Proyek:** Sistem Informasi Booking Quemil Makeup  
**Metode Pengembangan:** Waterfall  
**Stack:** PHP Native + MySQL + Bootstrap 5 + Midtrans  
**Dibuat oleh:** Abdul Khalid Firmansyah (222355201013)

---

## Dependency Map

Urutan fase yang wajib diikuti. Fase berikutnya tidak boleh dikerjakan sebelum fase sebelumnya selesai.

```
Fase 0 (Fondasi)
    │
    ├──> Fase 1 (Autentikasi)
    │       │
    │       ├──> Fase 3 (Booking + FCFS)
    │       │       │
    │       │       ├──> Fase 4 (Pembayaran Midtrans)
    │       │       │       │
    │       │       │       ├──> Fase 5 (Dashboard User)
    │       │       │       └──> Fase 6 (Dashboard Admin)
    │       │       │
    │       │       └──> Fase 5 & 6 (parsial, tanpa Midtrans)
    │       │
    │       └──> Fase 2 (Landing Page) -- bisa paralel dengan Fase 3
    │
    └──> Fase 7 (Pengujian Black Box) -- setelah semua fase selesai
```

---

## Fase 0 — Fondasi & Setup

> **Prasyarat:** Laragon aktif, database `quemil_booking` sudah diimport dari `database/quemil_booking.sql`

### Tujuan
Menyiapkan seluruh infrastruktur dasar yang dibutuhkan oleh semua fase berikutnya.

### Checklist Task

- [x] **Import database**
  - Diimport via MySQL CLI Laragon: `mysql -u root < database/quemil_booking.sql`
  - Verifikasi: 8 tabel terbuat + seed data masuk (users:1, jenis_makeup:5, jam_tersedia:5, zona_transport:9)
  - File SQL: `database/quemil_booking.sql` (bukan SCRIPT.md — file SQL ada di folder database/)

- [x] **Konfigurasi aplikasi**
  - File: `config/config.php` ✓
  - Konstanta: `APP_URL`, `APP_NAME`, `APP_ENV`, `DB_*`, `MIDTRANS_*`, `SESSION_NAME`, `CSRF_TOKEN_NAME`, `UPLOAD_PATH`, `DP_PERCENT`, `PAYMENT_EXPIRED_HOURS`
  - Catatan: isi `MIDTRANS_SERVER_KEY` dan `MIDTRANS_CLIENT_KEY` sebelum Fase 4

- [x] **Koneksi database**
  - File: `config/database.php` ✓
  - Class `Database` singleton PDO + fungsi helper `db()`

- [x] **Helper functions**
  - File: `app/helpers/functions.php` ✓
  - Fungsi: `startSession()`, `isLoggedIn()`, `isAdmin()`, `requireLogin()`, `requireAdmin()`, `currentUser()`
  - Fungsi: `csrfToken()`, `csrfField()`, `verifyCsrf()`
  - Fungsi: `redirect()`, `baseUrl()`, `currentUrl()`
  - Fungsi: `setFlash()`, `getFlash()`, `renderFlash()`
  - Fungsi: `e()`, `sanitize()`, `formatRupiah()`, `formatTanggal()`, `labelStatus()`
  - Fungsi: `generateKodeBooking()`, `hitungBiaya()`, `validasiProvinsi()`, `uploadFile()`, `paginate()`

- [x] **Models**
  - File: `app/models/User.php` ✓ — `findByEmail`, `findById`, `create`, `emailExists`, `verifyPassword`, `updateProfile`, `updatePassword`, `getAll`
  - File: `app/models/Booking.php` ✓ — `isSlotAvailable`, `create`, `lockSlot`, `unlockSlot`, `findById`, `findByKode`, `getByUser`, `getAll`, `updateStatus`, `getBookedSlots`, `cancelExpiredBookings`, `countByStatus`, `countAll`
  - File: `app/models/Payment.php` ✓ — `create`, `findByBookingId`, `findByOrderId`, `updateStatus`, `saveMidtransToken`
  - File: `app/models/Portofolio.php` ✓ — `getActive`, `getAll`, `findById`, `create`, `update`, `delete`
  - File: `app/models/JenisMakeup.php` ✓ — `getActive`, `getAll`, `findById`, `create`, `update`, `toggleActive`
  - File: `app/models/JamTersedia.php` ✓ — `getActive`, `getAll`, `findById`, `create`, `update`, `toggleActive`
  - File: `app/models/ZonaTransport.php` ✓ — `getActive`, `findById`, `resolveStatus`
  - File: `app/models/Notification.php` ✓ — `create`, `getByUser`, `getUnread`, `countUnread`, `markRead`, `markAllRead`

- [x] **Partials view**
  - File: `views/partials/header.php` ✓ — DOCTYPE, head, Bootstrap CDN, Google Fonts, CSS
  - File: `views/partials/navbar.php` ✓ — navigasi responsif, kondisi guest/user/admin
  - File: `views/partials/footer.php` ✓ — footer, Bootstrap JS, WhatsApp float button
  - File: `views/partials/sidebar_admin.php` ✓ — sidebar admin dengan 5 menu + logout

- [x] **CSS custom**
  - File: `public/assets/css/style.css` ✓
  - Variabel warna CSS (`--rose`, `--dark`, `--rose-light`, `--gold`), button rose, hero section, stats banner, service card, portfolio card, badge status, sidebar admin, auth card, WhatsApp float, tabel, responsive breakpoints

**Output Fase 0:** ✅ SELESAI — Semua fondasi siap. Database terisi seed data. Siap lanjut ke Fase 1.

---

## Fase 1 — Autentikasi

> **Prasyarat:** Fase 0 selesai

### Tujuan
User bisa mendaftar akun, login, dan logout. Route-route protected sudah berfungsi.

### Checklist Task

- [x] **Halaman Register**
  - File: `public/auth/register.php` ✓
  - Form: nama, email, nomor WA, password, konfirmasi password
  - Validasi: semua field wajib, email unik, password min 6 karakter, konfirmasi cocok
  - Setelah berhasil: auto-login → redirect ke `user/dashboard.php`
  - CSRF protection aktif

- [x] **Halaman Login**
  - File: `public/auth/login.php` ✓
  - Form: email + password
  - Validasi: email valid, password tidak kosong
  - Setelah berhasil: redirect ke dashboard sesuai role (admin/user)
  - Fitur: toggle show/hide password
  - CSRF protection aktif

- [x] **Logout**
  - File: `public/auth/logout.php` ✓
  - Destroy session + hapus cookie → redirect ke `auth/login.php`

- [x] **Proteksi route**
  - Fungsi `requireLogin()` ✓ → redirect ke login jika belum login
  - Fungsi `requireAdmin()` ✓ → redirect ke login jika bukan admin
  - Placeholder `user/dashboard.php` dan `admin/dashboard.php` dibuat untuk test
  - Password admin diupdate ke `admin123` (bcrypt valid)

**Output Fase 1:** ✅ SELESAI — Register, login, logout berfungsi. Session tersimpan dengan benar. No syntax errors.

---

## Fase 2 — Landing Page (Homepage)

> **Prasyarat:** Fase 0 selesai  
> **Bisa dikerjakan paralel dengan Fase 3**

### Tujuan
Halaman publik yang dapat diakses siapa saja. Menampilkan portofolio, layanan, dan CTA booking.

### Checklist Task

- [x] **Homepage**
  - File: `public/index.php` ✓
  - Section: Navbar, Hero, Stats Banner, Layanan, Portofolio, Cara Booking, Testimoni, Footer
  - Data dinamis: jenis makeup dari DB, portofolio dari DB
  - Fallback jika portofolio kosong: tampilkan empty state ✓
  - Referensi desain: `design_guest.md`

- [x] **Navbar kondisi guest vs login**
  - Guest: tombol [Masuk] + [Daftar] ✓
  - User login: nama user + tombol [Keluar] ✓
  - Admin login: tombol [Dashboard Admin] + [Keluar] ✓

- [x] **WhatsApp floating button**
  - Tampil di semua halaman via `views/partials/footer.php` ✓
  - Link ke nomor WA admin

**Output Fase 2:** ✅ SELESAI — Homepage bisa diakses, menampilkan layanan dan portofolio dari database. No syntax errors.

---

## Fase 3 — Booking + FCFS

> **Prasyarat:** Fase 0 + Fase 1 selesai

### Tujuan
User yang sudah login bisa melakukan booking. FCFS dan slot locking berfungsi dengan benar.

### Checklist Task

- [x] **Form Booking (Step 1: Pilih Layanan & Jadwal)**
  - File: `public/booking/index.php` ✓
  - Dropdown jenis makeup (dari DB, harga tampil otomatis via JS)
  - Date picker tanggal (min = H+1)
  - Render slot jam: tombol aktif vs disabled (terpakai)
  - AJAX `?action=get_slots&tanggal=` update slot real-time saat tanggal berubah

- [x] **Form Booking (Step 2: Tipe Layanan)**
  - Radio button Studio / Home Service dengan card UI ✓
  - Home Service: field alamat, kota, provinsi, pilih zona ✓
  - Validasi provinsi JS: Jatim → tampil zona, luar Jatim → sembunyikan zona ✓
  - Kalkulasi biaya transport otomatis saat zona dipilih ✓
  - Ringkasan biaya: harga jasa + transport + total + DP 30% + pelunasan 70% ✓

- [x] **Form Booking (Step 3: Konfirmasi & Submit)**
  - Ringkasan biaya real-time di Step 4 ✓
  - CSRF protection ✓
  - Submit: buat record booking (`waiting_payment` atau `pending_negotiation`) ✓
  - Generate `kode_booking` otomatis format `QB-YYYYMMDD-XXXX` ✓
  - Auto-cancel expired via `cancelExpiredBookings()` ✓
  - Kirim notifikasi in-app ke user ✓

- [x] **Logika FCFS + Slot Locking**
  - `app/models/Booking.php` method `lockSlot()` ✓
  - Transaksi DB `BEGIN...COMMIT` + `SELECT FOR UPDATE` untuk atomicity ✓
  - Slot dikunci setelah DP berhasil dibayar (Fase 4) ✓

- [x] **Halaman Detail Booking**
  - File: `public/booking/detail.php` ✓
  - Info lengkap: layanan, jadwal, tipe, alamat, biaya, status pembayaran ✓
  - Tombol Bayar DP jika status `waiting_payment` ✓
  - Data pelanggan untuk admin + tombol aksi konfirmasi/tolak ✓
  - Tombol Chat Admin via WhatsApp ✓

**Output Fase 3:** ✅ SELESAI — Booking bisa dibuat, slot terpakai tampil disabled, FCFS logic aktif. No syntax errors.

---

## Fase 4 — Pembayaran Midtrans

> **Prasyarat:** Fase 3 selesai  
> **Gunakan Midtrans Sandbox untuk development**

### Tujuan
User bisa membayar DP via Midtrans Snap. Status booking update otomatis via webhook.

### Checklist Task

- [x] **Generate Midtrans Snap Token**
  - File: `public/payment/process.php` ✓
  - Request ke Midtrans API: `order_id`, `gross_amount`, `customer_details`, `item_details`, `expiry`
  - Simpan `snap_token` ke tabel `payments` ✓
  - `expired_at` = `created_at + 24 jam` ✓
  - Jika token sudah ada (pending), reuse token lama ✓

- [x] **Tampilkan Popup Midtrans di Frontend**
  - Load `snap.js` dari CDN Midtrans sesuai environment ✓
  - `window.snap.pay(token, { onSuccess, onPending, onError, onClose })` ✓
  - Loading spinner saat proses ✓
  - Redirect ke `payment/finish.php` setelah pembayaran ✓

- [x] **Webhook Notification Handler**
  - File: `public/payment/notification.php` ✓
  - Verifikasi signature: `SHA512(order_id + status_code + gross_amount + server_key)` ✓
  - Tolak jika signature tidak cocok (HTTP 403) ✓
  - Update `payments.status` sesuai status Midtrans ✓
  - Jika `success`: `Booking::lockSlot()` → kunci slot (FCFS) ✓
  - Jika slot kalah FCFS: batalkan booking + catatan refund ✓
  - Jika `expired`/`failed`: batalkan booking ✓
  - Kirim notifikasi in-app ke user ✓

- [x] **Auto-cancel Expired Booking**
  - `Booking::cancelExpiredBookings()` dipanggil di awal setiap request ✓
  - Bebaskan `slot_locked = 0` + update `payments.status = 'expired'` ✓

- [x] **Konfigurasi Midtrans**
  - File: `config/config.php` ✓
  - `MIDTRANS_IS_PRODUCTION = false` (sandbox) ✓
  - Catatan: isi `MIDTRANS_SERVER_KEY` dan `MIDTRANS_CLIENT_KEY` dari dashboard sandbox
  - Daftarkan webhook: `{APP_URL}/payment/notification.php` di Midtrans dashboard

- [x] **Halaman Finish Payment**
  - File: `public/payment/finish.php` ✓ — redirect + flash message sesuai status

**Output Fase 4:** ✅ SELESAI — DP bisa dibayar via Midtrans Snap, slot terkunci otomatis setelah bayar, webhook handler aktif. No syntax errors.

---

## Fase 5 — Dashboard User

> **Prasyarat:** Fase 1 + Fase 3 + Fase 4 selesai

### Tujuan
User dapat memantau semua booking miliknya, melihat status, dan membayar DP yang tertunda.

### Checklist Task

- [ ] **Dashboard utama user**
  - File: `public/user/dashboard.php`
  - Greeting: "Halo, {nama}!" + tombol [Booking Sekarang]
  - Stats cards: Total Booking, Menunggu, Terkonfirmasi
  - Tabel riwayat booking: kode, jenis, tanggal, slot, tipe, status badge, aksi
  - Tombol [Bayar DP] per baris jika status `waiting_payment`
  - Tombol [Detail] per baris → redirect ke `booking/detail.php`
  - Empty state jika belum ada booking
  - Referensi desain: `design_dashboard.md` Bagian B

- [ ] **Notifikasi in-app**
  - File: `app/models/Notification.php`
  - Tampilkan notifikasi belum dibaca di dashboard
  - Klik notifikasi → tandai `is_read = 1`
  - Badge angka di navbar jika ada notifikasi baru

- [ ] **Halaman detail booking user**
  - File: `public/booking/detail.php`
  - Info lengkap: kode, jenis, tanggal, slot, tipe, alamat (jika home service)
  - Rincian biaya: harga jasa, transport, total, DP, pelunasan
  - Status pembayaran DP
  - Catatan admin (jika ada)
  - Tombol [Bayar DP] jika belum bayar
  - Tombol [Chat Admin WhatsApp]

**Output Fase 5:** User dapat melihat semua booking dan melakukan pembayaran DP dari dashboard.

---

## Fase 6 — Dashboard Admin

> **Prasyarat:** Fase 1 + Fase 3 + Fase 4 selesai

### Tujuan
Admin dapat mengelola seluruh booking, konfirmasi pembayaran, dan konten portofolio.

### Checklist Task

- [ ] **Dashboard admin (ringkasan)**
  - File: `public/admin/dashboard.php`
  - Layout: sidebar kiri + konten kanan
  - Stats cards: Total Booking, Menunggu Pembayaran, Menunggu Konfirmasi, Terkonfirmasi
  - Tabel booking terbaru (5 terakhir) + link ke halaman booking lengkap
  - Referensi desain: `design_dashboard.md` Bagian A

- [ ] **Manajemen booking admin**
  - File: `public/admin/bookings.php`
  - Tabel semua booking diurutkan `created_at ASC` (FCFS)
  - Filter by status (dropdown)
  - Tombol aksi per baris:
    - [Konfirmasi] → update status `confirmed`, kirim notifikasi user
    - [Tolak] → update status `cancelled`, bebaskan slot, kirim notifikasi user
    - [Selesai] → update status `completed`
    - [Detail] → modal atau halaman detail
  - Handle `pending_negotiation`: input biaya transport manual oleh admin

- [ ] **Kelola Portofolio**
  - File: `public/admin/portofolio.php`
  - Tampilkan grid semua foto portofolio
  - Form tambah: judul, kategori, deskripsi, upload foto, urutan, status aktif
  - Form edit: sama dengan tambah, preview foto lama
  - Hapus: konfirmasi modal + `unlink()` file foto dari disk
  - Validasi upload: MIME type (jpg/png/webp), max 5MB

- [ ] **Kelola Jenis Makeup**
  - File: `public/admin/jenis-makeup.php`
  - Tabel daftar jenis makeup + harga
  - Form tambah/edit: nama, deskripsi, harga, status aktif
  - Toggle aktif/nonaktif (tanpa hapus permanen)

- [ ] **Kelola Slot Jam**
  - File: `public/admin/slot-jam.php`
  - Tabel daftar slot jam tersedia
  - Form tambah/edit: jam mulai, jam selesai, label, status aktif
  - Toggle aktif/nonaktif

- [ ] **Sidebar admin**
  - Komponen reusable: `views/partials/sidebar_admin.php`
  - Menu: Dashboard, Booking, Portofolio, Jenis Makeup, Slot Jam, Keluar
  - Active state sesuai halaman yang sedang dibuka

**Output Fase 6:** Admin dapat mengelola booking, konfirmasi, portofolio, jenis makeup, dan slot jam.

---

## Fase 7 — Pengujian Black Box

> **Prasyarat:** Semua fase (0–6) selesai

### Tujuan
Memastikan semua fitur berjalan sesuai spesifikasi. Hasil pengujian menjadi bahan BAB IV skripsi.

### Skenario Pengujian

#### 7.1 Autentikasi

| No | Fitur | Input | Output Diharapkan | Status |
|----|-------|-------|-------------------|--------|
| 1 | Register | Data valid lengkap | Akun terbuat, auto-login, redirect dashboard | [ ] |
| 2 | Register | Email sudah terdaftar | Pesan error "Email sudah terdaftar" | [ ] |
| 3 | Register | Password tidak cocok | Pesan error "Konfirmasi password tidak cocok" | [ ] |
| 4 | Login | Email + password benar | Login berhasil, redirect sesuai role | [ ] |
| 5 | Login | Password salah | Pesan error "Email atau password salah" | [ ] |
| 6 | Logout | Klik tombol Keluar | Session dihapus, redirect ke login | [ ] |
| 7 | Akses route protected | Tanpa login | Redirect ke halaman login | [ ] |

#### 7.2 Booking

| No | Fitur | Input | Output Diharapkan | Status |
|----|-------|-------|-------------------|--------|
| 8 | Form booking | Data lengkap, slot tersedia | Booking terbuat, status `pending`/`waiting_payment` | [ ] |
| 9 | Pilih slot | Slot yang sudah terkunci | Tombol slot disabled, tidak bisa dipilih | [ ] |
| 10 | Home service Jatim | Provinsi "Jawa Timur" | Biaya transport terhitung otomatis | [ ] |
| 11 | Home service luar Jatim | Provinsi "Jawa Tengah" | Status `pending_negotiation` | [ ] |
| 12 | Home service luar Jawa | Provinsi "Bali" | Pesan tolak, booking tidak dibuat | [ ] |
| 13 | FCFS konflik slot | 2 user pesan slot sama | User yang `created_at` lebih awal menang | [ ] |
| 14 | Kode booking | Submit form | Kode format `QB-YYYYMMDD-XXXX` terbuat | [ ] |

#### 7.3 Pembayaran Midtrans

| No | Fitur | Input | Output Diharapkan | Status |
|----|-------|-------|-------------------|--------|
| 15 | Generate token | Booking valid | Snap token terbuat, popup Midtrans muncul | [ ] |
| 16 | Pembayaran sukses | Bayar via sandbox | Status payment `success`, slot terkunci | [ ] |
| 17 | Pembayaran expire | Lewati batas waktu | Status `cancelled`, slot dibebaskan | [ ] |
| 18 | Webhook signature | Signature salah | HTTP 403, tidak ada update data | [ ] |

#### 7.4 Dashboard User

| No | Fitur | Input | Output Diharapkan | Status |
|----|-------|-------|-------------------|--------|
| 19 | Lihat riwayat | Login sebagai user | Hanya booking milik user sendiri tampil | [ ] |
| 20 | Detail booking | Klik tombol Detail | Semua info booking tampil lengkap | [ ] |
| 21 | Notifikasi | Ada notif baru | Badge angka tampil, notif muncul di panel | [ ] |
| 22 | Empty state | Belum ada booking | Tampil empty state + tombol booking | [ ] |

#### 7.5 Dashboard Admin

| No | Fitur | Input | Output Diharapkan | Status |
|----|-------|-------|-------------------|--------|
| 23 | Daftar booking | Login sebagai admin | Semua booking tampil urut `created_at ASC` | [ ] |
| 24 | Konfirmasi booking | Klik Konfirmasi | Status `confirmed`, notifikasi dikirim ke user | [ ] |
| 25 | Tolak booking | Klik Tolak | Status `cancelled`, slot dibebaskan | [ ] |
| 26 | Negosiasi transport | Input biaya manual | Status berubah ke `waiting_payment` | [ ] |
| 27 | Tambah portofolio | Upload foto valid | Foto tersimpan, tampil di homepage | [ ] |
| 28 | Upload foto invalid | File > 5MB / bukan gambar | Pesan error validasi upload | [ ] |
| 29 | Hapus portofolio | Klik hapus + konfirmasi | Record DB dihapus, file foto dihapus dari disk | [ ] |
| 30 | Kelola jenis makeup | Tambah/edit/nonaktifkan | Data tersimpan, tampil di halaman booking | [ ] |

---

## Ringkasan Fase & File yang Dihasilkan

| Fase | File yang Dibuat/Dimodifikasi |
|------|------------------------------|
| **0** | `config/config.php`, `config/database.php`, `app/helpers/functions.php`, semua models, semua partials, `style.css` |
| **1** | `public/auth/login.php`, `public/auth/register.php`, `public/auth/logout.php` |
| **2** | `public/index.php` |
| **3** | `public/booking/index.php`, `public/booking/detail.php` |
| **4** | `public/payment/process.php`, `public/payment/notification.php` |
| **5** | `public/user/dashboard.php` |
| **6** | `public/admin/dashboard.php`, `public/admin/bookings.php`, `public/admin/portofolio.php`, `public/admin/jenis-makeup.php`, `public/admin/slot-jam.php`, `views/partials/sidebar_admin.php` |
| **7** | Pengujian manual (dokumentasi hasil di tabel) |

---

## Progress Tracker

Update kolom status saat mengerjakan:

| Fase | Deskripsi | Status |
|------|-----------|--------|
| Fase 0 | Fondasi & Setup | `[x] Selesai` |
| Fase 1 | Autentikasi | `[x] Selesai` |
| Fase 2 | Landing Page | `[x] Selesai` |
| Fase 3 | Booking + FCFS | `[x] Selesai` |
| Fase 4 | Pembayaran Midtrans | `[x] Selesai` |
| Fase 5 | Dashboard User | `[ ] Belum` |
| Fase 6 | Dashboard Admin | `[ ] Belum` |
| Fase 7 | Pengujian Black Box | `[ ] Belum` |
