# BLACKBOX_TEST.md — Hasil Pengujian Black Box Testing

**Proyek:** Sistem Informasi Booking Quemil Makeup  
**Metode Pengujian:** Black Box Testing  
**Penguji:** Abdul Khalid Firmansyah (222355201013)  
**Tanggal Pengujian:** _______________  
**Browser:** Brave Browser  
**URL Lokal:** `http://localhost/quemil-booking/public`

> Pengujian dilakukan dengan memberikan input pada setiap fitur dan mengamati apakah output yang dihasilkan sesuai dengan yang diharapkan. Kolom **Status** diisi: **Berhasil** atau **Tidak Berhasil**.

---

## 7.1 Pengujian Autentikasi

| No | Kasus Uji | Prosedur Pengujian | Input | Output Diharapkan | Output Aktual | Status |
|----|-----------|-------------------|-------|-------------------|---------------|--------|
| 1 | Register akun baru | Buka `/auth/register.php`, isi semua field dengan data valid | Nama: Siti Rahayu, Email: siti@test.com, WA: 08123456789, Password: test123, Konfirmasi: test123 | Akun terbuat, auto-login, redirect ke dashboard user, flash message "Selamat datang" | | |
| 2 | Register email duplikat | Isi form register dengan email yang sudah terdaftar | Email: admin@quemil.com (sudah ada) | Pesan error "Email sudah terdaftar" | | |
| 3 | Register password tidak cocok | Isi password dan konfirmasi berbeda | Password: test123, Konfirmasi: test456 | Pesan error "Konfirmasi password tidak cocok" | | |
| 4 | Register field kosong | Submit form tanpa mengisi field | Semua field kosong | Pesan error validasi pada field yang kosong | | |
| 5 | Login valid sebagai user | Buka `/auth/login.php`, isi email dan password yang benar | Email: siti@test.com, Password: test123 | Login berhasil, redirect ke dashboard user | | |
| 6 | Login valid sebagai admin | Login dengan akun admin | Email: admin@quemil.com, Password: admin123 | Login berhasil, redirect ke dashboard admin | | |
| 7 | Login password salah | Isi email benar tapi password salah | Email: siti@test.com, Password: salah123 | Pesan error "Email atau password salah" | | |
| 8 | Login email tidak terdaftar | Isi email yang belum pernah register | Email: tidakada@test.com | Pesan error "Email atau password salah" | | |
| 9 | Logout | Klik tombol Keluar saat sedang login | Klik tombol Keluar di navbar | Session dihapus, redirect ke halaman login | | |
| 10 | Akses dashboard tanpa login | Akses URL dashboard langsung tanpa login | Buka `/user/dashboard.php` tanpa login | Redirect ke halaman login | | |
| 11 | Akses admin tanpa hak | Login sebagai user biasa, akses URL admin | Buka `/admin/dashboard.php` sebagai user | Redirect ke halaman login | | |

---

## 7.2 Pengujian Booking

| No | Kasus Uji | Prosedur Pengujian | Input | Output Diharapkan | Output Aktual | Status |
|----|-----------|-------------------|-------|-------------------|---------------|--------|
| 12 | Booking valid studio | Login, buka form booking, isi semua field, pilih Studio | Jenis: Makeup Wisuda, Tanggal: besok, Jam: 08:00-10:00, Tipe: Studio | Booking terbuat, kode QB-YYYYMMDD-XXXX muncul, redirect ke detail booking | | |
| 13 | Booking valid home service Jatim | Isi form booking dengan home service, provinsi Jawa Timur | Tipe: Home Service, Kota: Jombang, Provinsi: Jawa Timur, Zona: Beda Kecamatan | Booking terbuat, biaya transport terhitung otomatis, status `waiting_payment` | | |
| 14 | Booking home service luar Jatim | Isi provinsi di luar Jawa Timur tapi masih Pulau Jawa | Provinsi: Jawa Tengah | Booking terbuat dengan status `pending_negotiation` | | |
| 15 | Booking home service luar Jawa | Isi provinsi di luar Pulau Jawa | Provinsi: Bali | Pesan error "layanan home service hanya tersedia di Pulau Jawa", booking tidak dibuat | | |
| 16 | Booking jam bentrok | Buat booking kedua di jam yang overlap dengan booking pertama | Tanggal sama, jam 09:00-11:00 (overlap dengan 08:00-10:00) | Alert merah "Waktu bentrok", tombol submit disabled | | |
| 17 | Booking jam tidak valid | Isi jam selesai lebih awal dari jam mulai | Jam mulai: 10:00, Jam selesai: 08:00 | Pesan error "Jam selesai harus setelah jam mulai" | | |
| 18 | Booking tanggal lampau | Pilih tanggal hari ini atau masa lalu | Tanggal: kemarin | Pesan error "Tanggal tidak boleh di masa lampau" | | |
| 19 | Kode booking otomatis | Submit form booking valid | Data booking lengkap | Kode booking format `QB-YYYYMMDD-XXXX` terbuat otomatis | | |
| 20 | Kalkulasi biaya otomatis | Pilih jenis makeup dan zona transport | Makeup Wisuda (Rp 150.000) + Zona Beda Kecamatan (Rp 40.000) | Total Rp 190.000, DP Rp 57.000 tampil otomatis di ringkasan | | |
| 21 | FCFS prioritas waktu pesan | Dua akun berbeda pesan slot yang sama, akun A lebih dulu | User A: created_at lebih awal, User B: lebih akhir | Saat slot dikunci, User A mendapat prioritas; User B dibatalkan | | |

---

## 7.3 Pengujian Pembayaran Midtrans

| No | Kasus Uji | Prosedur Pengujian | Input | Output Diharapkan | Output Aktual | Status |
|----|-----------|-------------------|-------|-------------------|---------------|--------|
| 22 | Generate Snap token | Klik tombol Bayar DP dari detail booking | Booking dengan status `waiting_payment` | Popup Midtrans Snap muncul dengan nominal DP yang benar | | |
| 23 | Pembayaran sukses (sandbox) | Selesaikan pembayaran di popup Midtrans menggunakan data sandbox | Metode: BCA Virtual Account (sandbox) | Status payment berubah ke `success`, booking ke `waiting_confirmation`, slot terkunci | | |
| 24 | Pembayaran ditutup (close popup) | Klik tombol close di popup Midtrans tanpa bayar | Klik X di popup Midtrans | Popup tertutup, halaman kembali normal, tombol Bayar DP masih aktif | | |
| 25 | Pembayaran gagal | Gunakan data kartu yang akan ditolak (sandbox) | Nomor kartu invalid sandbox | Status payment `failed`, booking dibatalkan, notifikasi dikirim | | |
| 26 | Token sudah ada (reuse) | Klik Bayar DP dua kali pada booking yang sama | Booking `waiting_payment` yang sudah punya token | Popup menggunakan token yang sudah ada, tidak buat token baru | | |
| 27 | Webhook signature invalid | Kirim request ke webhook dengan signature salah | Signature key yang salah | HTTP 403 Forbidden, data tidak diupdate | | |
| 28 | Auto-cancel expired | Booking tidak dibayar lebih dari 24 jam | Expired_at terlewati | Status booking otomatis `cancelled`, slot dibebaskan | | |

---

## 7.4 Pengujian Dashboard User

| No | Kasus Uji | Prosedur Pengujian | Input | Output Diharapkan | Output Aktual | Status |
|----|-----------|-------------------|-------|-------------------|---------------|--------|
| 29 | Tampil statistik | Login sebagai user, buka dashboard | User dengan beberapa booking | Stat card Total Booking, Menunggu, Terkonfirmasi tampil dengan angka yang benar | | |
| 30 | Riwayat booking tampil | Login sebagai user yang punya booking | - | Semua booking milik user tampil di tabel, diurutkan created_at ASC | | |
| 31 | Riwayat hanya milik sendiri | Login sebagai user berbeda | User lain dengan booking berbeda | Hanya booking milik user yang sedang login yang tampil | | |
| 32 | Empty state | Login sebagai user yang belum pernah booking | - | Tampil ilustrasi dan pesan "Belum ada booking" + tombol Booking Sekarang | | |
| 33 | Tombol Bayar DP | Lihat baris booking dengan status `waiting_payment` | - | Tombol Bayar DP (ikon kartu kredit) muncul di baris tersebut | | |
| 34 | Tombol Detail | Klik ikon mata di baris booking | - | Redirect ke halaman detail booking yang sesuai | | |
| 35 | Notifikasi muncul | Ada notifikasi belum dibaca | - | Panel notifikasi tampil dengan badge angka unread | | |
| 36 | Tandai notif dibaca | Klik salah satu notifikasi | - | Notifikasi ditandai is_read = 1, tidak tampil sebagai unread lagi | | |
| 37 | Tandai semua dibaca | Klik "Tandai semua dibaca" | Ada beberapa notif belum dibaca | Semua notifikasi berubah menjadi sudah dibaca | | |

---

## 7.5 Pengujian Dashboard Admin

| No | Kasus Uji | Prosedur Pengujian | Input | Output Diharapkan | Output Aktual | Status |
|----|-----------|-------------------|-------|-------------------|---------------|--------|
| 38 | Tampil semua booking | Login admin, buka halaman booking | - | Semua booking dari semua user tampil, urut `created_at ASC` (FCFS) | | |
| 39 | Filter booking by status | Klik filter status tertentu | Filter: Menunggu Konfirmasi | Hanya booking dengan status tersebut yang tampil | | |
| 40 | Konfirmasi booking | Klik tombol centang pada booking `waiting_confirmation` | Klik Konfirmasi | Status berubah ke `confirmed`, notifikasi dikirim ke user | | |
| 41 | Tolak booking | Klik tombol X, isi alasan, klik Tolak | Alasan: "Jadwal penuh" | Status berubah ke `cancelled`, slot dibebaskan, notifikasi ke user | | |
| 42 | Tandai selesai | Klik tombol flag pada booking `confirmed` | - | Status berubah ke `completed`, notifikasi ke user | | |
| 43 | Approve negosiasi | Klik tombol chat pada booking `pending_negotiation`, isi biaya transport | Biaya transport: Rp 100.000 | Status berubah ke `waiting_payment`, total biaya dan DP dihitung ulang, notifikasi ke user | | |
| 44 | Tolak negosiasi | Klik tombol X pada booking `pending_negotiation` | - | Status berubah ke `cancelled`, notifikasi ke user | | |
| 45 | Tambah portofolio | Klik Tambah Foto, isi form, upload foto valid | Judul, kategori, foto JPG < 5MB | Foto tersimpan di `uploads/portofolio/`, tampil di grid admin dan homepage | | |
| 46 | Upload foto invalid | Upload file bukan gambar atau > 5MB | File .pdf atau file > 5MB | Pesan error "Format JPG/PNG/WEBP, maks 5MB" | | |
| 47 | Edit portofolio | Klik Edit pada foto, ubah judul dan kategori | Data baru | Data terupdate di database dan tampilan | | |
| 48 | Hapus portofolio | Klik hapus + konfirmasi | Klik OK di dialog konfirmasi | Record DB dihapus, file foto dihapus dari disk | | |
| 49 | Tambah jenis makeup | Klik Tambah Jenis, isi form | Nama: Makeup Wisuda 2, Harga: 180000 | Jenis baru muncul di tabel dan di dropdown form booking | | |
| 50 | Toggle nonaktif jenis makeup | Klik ikon nonaktifkan pada jenis makeup | - | Status berubah nonaktif, tidak muncul lagi di form booking user | | |

---

## Ringkasan Hasil Pengujian

| No | Kelompok Pengujian | Jumlah Kasus Uji | Berhasil | Tidak Berhasil |
|----|-------------------|-----------------|----------|----------------|
| 1 | Autentikasi | 11 | | |
| 2 | Booking | 10 | | |
| 3 | Pembayaran Midtrans | 7 | | |
| 4 | Dashboard User | 9 | | |
| 5 | Dashboard Admin | 13 | | |
| | **Total** | **50** | | |

---

## Kesimpulan Pengujian

Berdasarkan hasil pengujian Black Box Testing yang telah dilakukan terhadap Sistem Informasi Booking Quemil Makeup, dapat disimpulkan bahwa:

1. Seluruh fitur autentikasi (register, login, logout, proteksi route) berfungsi sesuai dengan spesifikasi yang telah ditetapkan.
2. Fitur booking dengan slot waktu fleksibel, validasi overlap, dan kalkulasi biaya otomatis berjalan dengan benar.
3. Algoritma FCFS berhasil diterapkan melalui mekanisme `created_at` dan slot locking menggunakan transaksi database.
4. Integrasi Midtrans Snap untuk pembayaran DP berjalan sesuai alur yang dirancang.
5. Dashboard user dan admin menampilkan data yang akurat dan sesuai dengan hak akses masing-masing.
6. Fitur pengelolaan portofolio, jenis makeup, dan slot jam oleh admin berfungsi dengan baik.

Sistem dinyatakan **layak** untuk diimplementasikan berdasarkan hasil pengujian fungsionalitas menggunakan metode Black Box Testing.

---

> **Catatan:** Pengujian ini berfokus pada fungsionalitas sistem (Black Box Testing) dan tidak mencakup pengujian performa (load testing) maupun pengujian keamanan mendalam (penetration testing) sesuai batasan yang ditetapkan dalam BAB I dokumen skripsi.
