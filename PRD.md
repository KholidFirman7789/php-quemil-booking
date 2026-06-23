# PRD.md — Product Requirements Document

**Proyek:** Sistem Informasi Booking Quemil Makeup  
**Versi:** 1.0  
**Penulis:** Abdul Khalid Firmansyah (222355201013)  
**Program Studi:** Teknik Informatika, Fakultas Teknik, Universitas Darul Ulum Jombang  
**Tahun:** 2025/2026

---

## 1. Latar Belakang

Quemil Makeup adalah usaha jasa makeup artist yang berlokasi di Dusun Sawi RT 08 RW 02, Desa Sawiji, Kecamatan Jogoroto, Kabupaten Jombang, Jawa Timur. Usaha ini dikenal dengan hasil riasan berkualitas dengan harga terjangkau, khususnya untuk acara wisuda, pernikahan, dan karnaval/parade budaya.

Sistem pemesanan yang berjalan saat ini masih manual — melalui WhatsApp, telepon, dan tatap muka langsung. Seiring meningkatnya jumlah pelanggan, sistem ini menimbulkan beberapa masalah:

- Pelanggan harus menunggu balasan admin untuk cek jadwal tersedia
- Pencatatan pesanan tidak terorganisir
- Risiko **double booking** (bentrok jadwal)
- Kesulitan menentukan prioritas ketika beberapa pelanggan memesan slot yang sama secara bersamaan

---

## 2. Tujuan Sistem

1. Membangun sistem informasi booking berbasis web menggunakan **PHP Native** dan **metode pengembangan Waterfall**.
2. Menerapkan algoritma **First Come First Served (FCFS)** sebagai mekanisme penentuan prioritas pemesanan pada slot waktu yang sama.
3. Mengintegrasikan **payment gateway Midtrans** untuk pembayaran Down Payment (DP) secara online.
4. Menyediakan dashboard terstruktur bagi admin untuk mengelola booking, portofolio, dan laporan.

---

## 3. Aktor Sistem

### 3.1 User (Pelanggan)
- Mengakses sistem tanpa login untuk melihat portofolio dan informasi layanan
- Wajib login/daftar untuk melakukan booking
- Dapat memantau status pemesanan melalui dashboard

### 3.2 Admin (Pengelola Quemil Makeup)
- Login ke dashboard admin
- Mengelola konfirmasi booking
- Mengelola data portofolio, jenis makeup, dan slot jam
- Memantau semua transaksi pembayaran
- Melakukan negosiasi biaya transport untuk wilayah di luar Jawa Timur

---

## 4. Fitur Fungsional

### 4.1 Halaman Publik (Tanpa Login)

| Fitur | Deskripsi |
|-------|-----------|
| Homepage | Hero section, stats, cara booking |
| Portofolio | Galeri foto hasil karya, dikelola admin |
| Daftar Layanan | Jenis makeup beserta harga |
| Testimoni | Ulasan pelanggan (statis) |
| Kontak | Informasi lokasi studio + tombol WhatsApp floating |

### 4.2 Autentikasi

| Fitur | Deskripsi |
|-------|-----------|
| Register | Daftar akun baru (nama, email, nomor WA, password) |
| Login | Masuk dengan email + password |
| Logout | Hapus session dan redirect ke halaman login |
| Proteksi Route | Halaman booking, dashboard user, dan dashboard admin wajib login |

### 4.3 Booking

| Fitur | Deskripsi |
|-------|-----------|
| Form Booking | Pilih jenis makeup, tanggal, slot jam, tipe layanan |
| Cek Slot Real-time | Slot yang sudah terkunci tampil nonaktif |
| Tipe Layanan | Studio (datang ke Quemil) atau Home Service (MUA datang ke pelanggan) |
| Validasi Lokasi | Hanya untuk home service: Jatim → auto, luar Jatim/Jawa → negosiasi/tolak |
| Kalkulasi Biaya | Harga jasa + biaya transport (otomatis berdasarkan zona) |
| DP Otomatis | DP = 30% dari total biaya, dihitung otomatis |
| FCFS | Jika slot sama, yang `created_at` lebih awal diprioritaskan |
| Kode Booking | Dibuat otomatis: format `QB-YYYYMMDD-XXXX` |

### 4.4 Pembayaran (Midtrans)

| Fitur | Deskripsi |
|-------|-----------|
| Snap Payment | Popup Midtrans Snap untuk bayar DP |
| Metode Pembayaran | Transfer bank, GoPay, OVO, QRIS, kartu kredit/debit |
| Notifikasi Webhook | Status pembayaran diperbarui otomatis via Midtrans notification |
| Auto Expire | Booking dibatalkan otomatis jika DP tidak dibayar dalam 24 jam |
| Slot Locking | Slot dikunci setelah DP berhasil dibayar |
| Pelunasan Manual | Sisa 70% dibayar langsung ke Quemil Makeup pada hari H |

### 4.5 Dashboard User

| Fitur | Deskripsi |
|-------|-----------|
| Statistik | Total booking, menunggu, terkonfirmasi |
| Riwayat Booking | Tabel daftar semua booking milik user |
| Detail Booking | Informasi lengkap + status + kode booking |
| Tombol Booking Baru | Shortcut ke form booking |
| Notifikasi | Pemberitahuan perubahan status booking |
| Tombol WhatsApp | Hubungi admin langsung dari dashboard |

### 4.6 Dashboard Admin

| Fitur | Deskripsi |
|-------|-----------|
| Daftar Booking | Semua booking diurutkan `created_at ASC` (FCFS) |
| Konfirmasi Booking | Tombol Konfirmasi / Tolak per booking |
| Negosiasi Transport | Setujui/tolak booking `pending_negotiation` + input biaya transport |
| Kelola Portofolio | Tambah, edit, hapus foto portofolio |
| Kelola Jenis Makeup | Tambah, edit, nonaktifkan jenis layanan |
| Kelola Slot Jam | Tambah, edit, nonaktifkan slot waktu |
| Pantau Pembayaran | Status pembayaran DP semua booking |
| Tandai Selesai | Ubah status booking menjadi `completed` |

---

## 5. User Stories

### User (Pelanggan)

```
Sebagai user, saya ingin:
- Melihat portofolio makeup tanpa perlu login
  agar saya bisa menilai kualitas sebelum memutuskan booking

- Mendaftar akun dengan email dan nomor WhatsApp
  agar saya bisa menggunakan fitur booking

- Melihat slot jam yang masih tersedia pada tanggal tertentu
  agar saya tidak memesan jadwal yang sudah penuh

- Memilih tipe layanan (studio / home service)
  agar saya bisa menyesuaikan dengan kebutuhan

- Mengetahui estimasi biaya transport sebelum konfirmasi booking
  agar tidak ada biaya tersembunyi

- Membayar DP secara online via Midtrans
  agar saya tidak perlu transfer manual dan konfirmasi ke admin

- Memantau status booking dari dashboard
  agar saya tahu apakah booking sudah dikonfirmasi admin

- Menerima notifikasi ketika status booking berubah
  agar saya tidak perlu terus mengecek secara manual
```

### Admin

```
Sebagai admin, saya ingin:
- Melihat daftar booking diurutkan berdasarkan waktu pesan (FCFS)
  agar saya bisa memproses booking secara adil dan terurut

- Mengkonfirmasi atau menolak booking yang sudah membayar DP
  agar jadwal saya terkelola dengan jelas

- Mengevaluasi permintaan home service dari luar Jawa Timur
  agar saya bisa memutuskan apakah layak dilayani atau tidak

- Mengelola foto portofolio dari dashboard
  agar halaman publik selalu menampilkan karya terbaru

- Memantau status pembayaran DP semua booking
  agar saya tahu booking mana yang sudah lunas

- Menandai booking sebagai selesai setelah layanan diberikan
  agar riwayat data lebih akurat
```

---

## 6. Alur Sistem

### 6.1 Alur Booking User (Happy Path)

```
1. User buka homepage → klik "Booking Sekarang"
2. Sistem redirect ke halaman login (jika belum login)
3. User login / daftar akun baru
4. User mengisi form booking:
   a. Pilih jenis makeup
   b. Pilih tanggal
   c. Pilih slot jam (slot terpakai ditampilkan disabled)
   d. Pilih tipe layanan (studio / home service)
   e. Jika home service: isi alamat + pilih zona
5. Sistem hitung: total_biaya = harga_jasa + biaya_transport
6. Sistem hitung: dp_amount = total_biaya * 30%
7. User konfirmasi ringkasan booking
8. Sistem buat record booking (status: pending / waiting_payment)
9. Sistem buat Midtrans Snap token
10. User bayar DP via popup Midtrans
11. Midtrans kirim notifikasi ke webhook
12. Sistem update: payment.status = 'success'
13. Sistem kunci slot: booking.slot_locked = 1
14. Sistem update: booking.status = 'waiting_confirmation'
15. Admin menerima notifikasi booking baru
16. Admin konfirmasi booking
17. Sistem update: booking.status = 'confirmed'
18. User menerima notifikasi konfirmasi
```

### 6.2 Alur FCFS (Konflik Slot)

```
Skenario: User A dan User B memesan slot yang sama (Sabtu, 08:00-10:00)

User A: created_at = 2026-07-01 10:00:00.100
User B: created_at = 2026-07-01 10:00:00.950

1. Keduanya berhasil membuat record booking (status: pending)
   karena slot belum terkunci saat itu

2. User A membayar DP lebih dulu
   → Sistem coba kunci slot dalam transaksi DB
   → Tidak ada konflik → slot dikunci untuk User A
   → booking.status User A = 'waiting_confirmation'

3. User B membayar DP
   → Sistem coba kunci slot dalam transaksi DB
   → Slot sudah terkunci (User A) → gagal
   → Sistem batalkan booking User B, refund DP via Midtrans
   → User B menerima notifikasi: "Slot sudah diambil pelanggan lain"

Kesimpulan: User A menang karena created_at lebih awal (FCFS).
```

### 6.3 Validasi Lokasi Home Service

```
Provinsi input dari user
        |
        v
  [Cek provinsi]
        |
        +--[Jawa Timur]-----------> Proses otomatis
        |                           Hitung biaya transport dari tabel zona
        |                           Status: waiting_payment
        |
        +--[Luar Jatim, Pulau Jawa]--> Status: pending_negotiation
        |                              Admin evaluasi via dashboard
        |                              Jika setuju: admin set biaya transport
        |                              → Status: waiting_payment
        |                              Jika tolak: Status: cancelled
        |
        +--[Luar Pulau Jawa]---------> Ditolak langsung
                                       Pesan: "Maaf, layanan home service
                                       hanya tersedia di Pulau Jawa."
```

---

## 7. Batasan Sistem

1. Sistem berbasis **web** dan diakses melalui browser, tidak ada versi mobile native (Android/iOS). Antarmuka dibuat responsif.
2. Pembayaran hanya untuk **Down Payment (DP) 30%** secara online via Midtrans. Pelunasan 70% dilakukan secara manual pada hari H.
3. Sistem menggunakan **time-slot scheduling** — pengguna memilih dari slot jam yang tersedia (bukan input jam bebas).
4. **Pengujian** menggunakan metode **Black Box Testing** — hanya fungsionalitas, tidak mencakup pengujian performa dan keamanan mendalam.
5. Tahap **maintenance** hanya dibahas secara teoritis, tidak diimplementasikan dalam penelitian ini.
6. Sistem tidak mengembangkan fitur **notifikasi email atau SMS** — notifikasi hanya melalui in-app dan WhatsApp manual.

---

## 8. Integrasi Midtrans

### 8.1 Konfigurasi

| Parameter | Nilai |
|-----------|-------|
| Mode Sandbox | `https://app.sandbox.midtrans.com/snap/snap.js` |
| Mode Production | `https://app.midtrans.com/snap/snap.js` |
| Metode integrasi | Midtrans Snap (popup) |
| Endpoint token | `POST https://app.sandbox.midtrans.com/snap/v1/transactions` |
| Webhook URL | `{APP_URL}/payment/notification.php` |

### 8.2 Alur Pembayaran

```
1. Sistem kirim request ke Midtrans API dengan:
   - order_id: unik per transaksi (format: QB-{id}-{timestamp})
   - gross_amount: dp_amount
   - customer_details: nama, email, phone user

2. Midtrans kembalikan snap_token

3. Frontend load snap.js dan panggil window.snap.pay(snap_token)

4. User selesaikan pembayaran di popup Midtrans

5. Midtrans kirim HTTP POST ke webhook notification.php

6. Sistem verifikasi signature key Midtrans

7. Sistem update status payment dan booking
```

### 8.3 Status Mapping Midtrans

| Status Midtrans | Status Payment Sistem | Aksi |
|-----------------|----------------------|------|
| `settlement` | `success` | Kunci slot, update booking |
| `capture` | `success` | Kunci slot, update booking |
| `pending` | `pending` | Tidak ada aksi |
| `deny` / `cancel` | `failed` | Batalkan booking |
| `expire` | `expired` | Batalkan booking, bebaskan slot |

---

## 9. Kalkulasi Biaya

```
total_biaya      = harga_jasa + biaya_transport
dp_amount        = total_biaya * 30 / 100  (dibulatkan ke bawah)
pelunasan_amount = total_biaya - dp_amount
```

**Contoh:**
- Jenis makeup: Makeup Wisuda = Rp 150.000
- Tipe: Home Service, Zona "Beda Kecamatan" = Rp 40.000
- total_biaya = Rp 190.000
- dp_amount = Rp 57.000
- pelunasan_amount = Rp 133.000

---

## 10. Non-Functional Requirements

| Aspek | Requirement |
|-------|-------------|
| Responsivitas | Tampil baik di desktop, tablet, dan smartphone |
| Keamanan | CSRF protection, prepared statements, bcrypt password, session regeneration |
| Performa | Query menggunakan index pada kolom `tanggal`, `jam_id`, `created_at` |
| Browser Support | Chrome, Firefox, Edge, Brave (versi modern) |
| Server | Laragon (local), Apache/Nginx (production) |
| PHP | Versi 8.0+ |
| MySQL | Versi 8.0+ atau MariaDB 10.4+ |

---

## 11. Out of Scope

- Aplikasi mobile native (Android/iOS)
- Notifikasi email/SMS otomatis
- Sistem ulasan/rating oleh pelanggan
- Laporan keuangan / ekspor PDF
- Multi-MUA (sistem ini hanya untuk satu MUA: Quemil Makeup)
- Pengujian performa (load testing)
- Pengujian keamanan mendalam (penetration testing)
