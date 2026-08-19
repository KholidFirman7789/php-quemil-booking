# SCHEMA.md — Skema Database

**Proyek:** Sistem Informasi Booking Quemil Makeup  
**Database:** `quemil_booking`  
**Engine:** MySQL 8+ / MariaDB 10.4+  
**Charset:** `utf8mb4_unicode_ci`  
**Dibuat oleh:** Abdul Khalid Firmansyah (222355201013)

---

## Daftar Tabel

| No | Nama Tabel | Deskripsi |
|----|------------|-----------|
| 1 | `users` | Data pengguna (user & admin) |
| 2 | `jenis_makeup` | Paket/jenis layanan makeup |
| 3 | `jam_tersedia` | Slot waktu yang bisa dipilih |
| 4 | `zona_transport` | Zona wilayah & biaya home service |
| 5 | `bookings` | Data pemesanan (inti sistem) |
| 6 | `payments` | Transaksi pembayaran DP via Midtrans |
| 7 | `portofolio` | Galeri hasil karya makeup |
| 8 | `notifications` | Notifikasi untuk user dan admin |

---

## 1. Tabel `users`

Menyimpan data semua pengguna sistem, baik role `user` maupun `admin`.

```sql
CREATE TABLE `users` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(150) NOT NULL UNIQUE,
  `phone`      VARCHAR(20)  NOT NULL,
  `password`   VARCHAR(255) NOT NULL,           -- bcrypt hash
  `role`       ENUM('user','admin') NOT NULL DEFAULT 'user',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
```

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | INT UNSIGNED | Primary key, auto increment |
| `name` | VARCHAR(100) | Nama lengkap pengguna |
| `email` | VARCHAR(150) | Email unik, dipakai untuk login |
| `phone` | VARCHAR(20) | Nomor WhatsApp untuk konfirmasi |
| `password` | VARCHAR(255) | Hash bcrypt (`password_hash()`) |
| `role` | ENUM | `user` = pelanggan, `admin` = pengelola |
| `created_at` | DATETIME | Waktu daftar |
| `updated_at` | DATETIME | Waktu update terakhir |

**Seed data admin:**
```sql
INSERT INTO users (name, email, phone, password, role) VALUES
('Admin Quemil', 'admin@quemil.com', '081234567890',
 '$2y$10$...bcrypt_hash_dari_password...', 'admin');
```

---

## 2. Tabel `jenis_makeup`

Menyimpan paket layanan makeup yang ditawarkan Quemil Makeup.

```sql
CREATE TABLE `jenis_makeup` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nama`       VARCHAR(100) NOT NULL,
  `deskripsi`  TEXT,
  `harga`      DECIMAL(12,2) NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
```

**Seed data:**

| id | nama | harga |
|----|------|-------|
| 1 | Makeup Wisuda | Rp 150.000 |
| 2 | Makeup Pengantin | Rp 500.000 |
| 3 | Makeup Karnaval / Parade | Rp 200.000 |
| 4 | Makeup Kondangan | Rp 120.000 |
| 5 | Makeup Foto / Dokumentasi | Rp 130.000 |

---

## 3. Tabel `jam_tersedia`

Slot waktu yang dapat dipilih pengguna saat booking (time-slot scheduling).

```sql
CREATE TABLE `jam_tersedia` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `jam_mulai`   TIME NOT NULL,
  `jam_selesai` TIME NOT NULL,
  `label`       VARCHAR(50) NOT NULL,  -- contoh: '08:00 - 10:00'
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;
```

**Seed data:**

| id | label |
|----|-------|
| 1 | 06:00 - 08:00 |
| 2 | 08:00 - 10:00 |
| 3 | 10:00 - 12:00 |
| 4 | 13:00 - 15:00 |
| 5 | 15:00 - 17:00 |

---

## 4. Tabel `zona_transport`

Menentukan biaya transport berdasarkan zona wilayah home service.

```sql
CREATE TABLE `zona_transport` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nama_zona`  VARCHAR(100) NOT NULL,
  `keterangan` VARCHAR(255),
  `biaya`      DECIMAL(12,2) NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;
```

**Seed data (sesuai dokumen skripsi):**

| No | Zona | Wilayah | Biaya |
|----|------|---------|-------|
| 1 | Sama Lokasi | Studio Quemil Makeup | Gratis |
| 2 | Beda RT | Sekitar rumah | Rp 5.000 |
| 3 | Beda Dusun | Masih satu desa | Rp 10.000 |
| 4 | Beda Desa | Masih satu kecamatan | Rp 20.000 |
| 5 | Beda Kecamatan | Masih satu kabupaten | Rp 40.000 |
| 6 | Zona 1 | Mojokerto, Kediri, Nganjuk, Lamongan, Sidoarjo | Rp 60.000 |
| 7 | Zona 2 | Gresik, Bojonegoro, Tuban, Pasuruan, Malang | Rp 80.000 |
| 8 | Zona 3 | Madiun, Ngawi, Magetan, Ponorogo, Probolinggo, Lumajang | Rp 120.000 |
| 9 | Zona 4 | Banyuwangi, Jember, Bondowoso, Situbondo, Pacitan, Sumenep, Pamekasan, Sampang, Bangkalan | Rp 170.000 |

> **Catatan:** Untuk permintaan dari luar Jawa Timur (masih Pulau Jawa) → status `pending_negotiation`, admin tentukan biaya manual via WhatsApp. Untuk luar Pulau Jawa → ditolak otomatis oleh sistem.

---

## 5. Tabel `bookings` _(inti sistem)_

Menyimpan semua data pemesanan. **FCFS ditentukan oleh kolom `created_at`** — pemesanan yang lebih awal (nilai `created_at` lebih kecil) mendapat prioritas.

```sql
CREATE TABLE `bookings` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `kode_booking`     VARCHAR(20) NOT NULL UNIQUE,  -- format: QB-YYYYMMDD-XXXX
  `user_id`          INT UNSIGNED NOT NULL,
  `jenis_makeup_id`  INT UNSIGNED NOT NULL,
  `jam_id`           INT UNSIGNED NOT NULL,
  `tanggal`          DATE NOT NULL,

  -- Tipe layanan
  `tipe_layanan`     ENUM('studio','home_service') NOT NULL DEFAULT 'studio',

  -- Kolom home service (nullable jika studio)
  `alamat_lengkap`   TEXT,
  `kota`             VARCHAR(100),
  `provinsi`         VARCHAR(100),
  `zona_id`          INT UNSIGNED DEFAULT NULL,
  `biaya_transport`  DECIMAL(12,2) NOT NULL DEFAULT 0,

  -- Kalkulasi biaya
  `harga_jasa`       DECIMAL(12,2) NOT NULL DEFAULT 0,
  `total_biaya`      DECIMAL(12,2) NOT NULL DEFAULT 0,
  `dp_amount`        DECIMAL(12,2) NOT NULL DEFAULT 0,   -- 30% dari total_biaya
  `pelunasan_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,  -- 70% sisa

  -- Status booking
  `status` ENUM(
    'pending',               -- baru dibuat, belum bayar
    'pending_negotiation',   -- home service luar Jatim, menunggu konfirm admin
    'waiting_payment',       -- menunggu pembayaran DP
    'waiting_confirmation',  -- DP lunas, menunggu konfirm admin
    'confirmed',             -- admin konfirmasi
    'completed',             -- layanan selesai
    'cancelled'              -- dibatalkan / expired
  ) NOT NULL DEFAULT 'pending',

  -- Slot locking (mencegah double booking)
  `slot_locked`    TINYINT(1) NOT NULL DEFAULT 0,

  `catatan_admin`  TEXT,
  `catatan_user`   TEXT,

  -- FCFS key: urutan prioritas berdasarkan created_at
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (`user_id`)         REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`jenis_makeup_id`) REFERENCES `jenis_makeup`(`id`),
  FOREIGN KEY (`jam_id`)          REFERENCES `jam_tersedia`(`id`),
  FOREIGN KEY (`zona_id`)         REFERENCES `zona_transport`(`id`)
) ENGINE=InnoDB;

-- Index untuk performa query FCFS dan pengecekan slot
CREATE INDEX idx_bookings_tanggal_jam ON bookings(tanggal, jam_id);
CREATE INDEX idx_bookings_status      ON bookings(status);
CREATE INDEX idx_bookings_created_at  ON bookings(created_at);
CREATE INDEX idx_bookings_user        ON bookings(user_id);
```

### Alur Status Booking

```
[User submit form booking]
        |
        v
     pending
        |
        +--[home_service + luar Jatim dalam Pulau Jawa]---> pending_negotiation
        |                                                          |
        |                                               [admin setuju + set biaya transport]
        |                                                          |
        +--[studio / home_service Jatim]--------------------------+
        |
        v
  waiting_payment  <--- Midtrans Snap token dibuat, expired_at = +24 jam
        |
    [DP lunas via Midtrans]
        |
        v
 waiting_confirmation  <--- slot_locked = 1 (slot dikunci)
        |
    [admin klik Konfirmasi]
        |
        v
    confirmed
        |
    [hari H selesai]
        |
        v
    completed

[Expired / dibatalkan] --> cancelled + slot_locked = 0
[Luar Pulau Jawa]      --> ditolak langsung, booking tidak dibuat
```

### Logika FCFS + Slot Locking

```sql
-- 1. Cek ketersediaan slot sebelum booking dibuat
SELECT COUNT(*) FROM bookings
WHERE tanggal = :tanggal
  AND jam_id = :jam_id
  AND slot_locked = 1
  AND status NOT IN ('cancelled');
-- Jika COUNT = 0 maka slot tersedia

-- 2. Kunci slot setelah DP berhasil (dalam transaksi DB untuk atomicity)
BEGIN;
  SELECT * FROM bookings WHERE id = :id FOR UPDATE;
  -- validasi ulang tidak ada slot_locked lain pada tanggal+jam yang sama
  UPDATE bookings
  SET slot_locked = 1, status = 'waiting_confirmation'
  WHERE id = :id;
COMMIT;

-- 3. FCFS: daftar booking di dashboard admin diurutkan created_at ASC
SELECT b.*, u.name, j.nama AS jenis
FROM bookings b
JOIN users u ON b.user_id = u.id
JOIN jenis_makeup j ON b.jenis_makeup_id = j.id
ORDER BY b.created_at ASC;

-- 4. Auto-cancel booking expired
UPDATE bookings b
JOIN payments p ON p.booking_id = b.id
SET b.status = 'cancelled', b.slot_locked = 0, p.status = 'expired'
WHERE b.status = 'waiting_payment'
  AND p.expired_at IS NOT NULL
  AND p.expired_at < NOW();
```

---

## 6. Tabel `payments`

Mencatat transaksi pembayaran DP via Midtrans (relasi one-to-one dengan `bookings`).

```sql
CREATE TABLE `payments` (
  `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `booking_id`         INT UNSIGNED NOT NULL UNIQUE,
  `order_id`           VARCHAR(100) NOT NULL UNIQUE,  -- dikirim ke Midtrans
  `midtrans_token`     VARCHAR(255),                  -- Snap token
  `amount`             DECIMAL(12,2) NOT NULL,
  `metode`             VARCHAR(50),                   -- bank_transfer, gopay, dll
  `status`             ENUM('pending','success','failed','expired','cancelled')
                       NOT NULL DEFAULT 'pending',
  `midtrans_response`  JSON,     -- raw notification dari Midtrans (audit trail)
  `paid_at`            DATETIME DEFAULT NULL,
  `expired_at`         DATETIME DEFAULT NULL,  -- +24 jam dari created_at booking
  `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;
```

| Kolom | Keterangan |
|-------|------------|
| `order_id` | Format: `QB-{booking_id}-{timestamp}`, unik untuk Midtrans |
| `midtrans_token` | Token dari Midtrans Snap API untuk membuka popup pembayaran |
| `midtrans_response` | Disimpan sebagai JSON untuk keperluan audit dan debug |
| `expired_at` | Booking otomatis dibatalkan jika melewati waktu ini |

---

## 7. Tabel `portofolio`

Galeri foto hasil karya yang ditampilkan di halaman publik.

```sql
CREATE TABLE `portofolio` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `judul`      VARCHAR(150) NOT NULL,
  `deskripsi`  TEXT,
  `foto`       VARCHAR(255) NOT NULL,  -- path relatif: uploads/portofolio/nama.jpg
  `kategori`   VARCHAR(100),           -- Pengantin, Wisuda, Karnaval, dll
  `urutan`     INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
```

| Kolom | Keterangan |
|-------|------------|
| `foto` | Path relatif dari `public/`, contoh: `uploads/portofolio/foto.jpg` |
| `urutan` | Angka kecil tampil duluan di homepage |
| `is_active` | 0 = disembunyikan dari halaman publik |

---

## 8. Tabel `notifications`

Notifikasi in-app untuk user dan admin.

```sql
CREATE TABLE `notifications` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `booking_id` INT UNSIGNED DEFAULT NULL,
  `judul`      VARCHAR(200) NOT NULL,
  `pesan`      TEXT NOT NULL,
  `is_read`    TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;
```

---

## Diagram Relasi Antar Tabel

```
users (1) ──────────────────── (N) bookings
                                       │
            ┌──────────────────────────┼──────────────────────────┐
            │                          │                          │
    jenis_makeup (1)           jam_tersedia (1)          zona_transport (1)
    ──────── (N)               ──────── (N)              ──────── (N)

bookings (1) ──── (1) payments
bookings (N) ──── (1) notifications  [booking_id nullable]
users    (1) ──── (N) notifications

portofolio  [berdiri sendiri, tidak berelasi dengan tabel lain]
```

---

## Catatan Penting

- Semua tabel menggunakan `ENGINE=InnoDB` agar mendukung transaksi dan foreign key.
- Kolom `created_at` pada `bookings` adalah **kunci FCFS** — jangan pernah diubah/diupdate secara manual.
- Slot locking dilakukan di dalam **transaksi DB** (`BEGIN ... COMMIT`) untuk menjamin atomicity.
- Password selalu disimpan sebagai **bcrypt hash** via `password_hash($pass, PASSWORD_BCRYPT)`.
- Kolom `midtrans_response` bertipe `JSON` — butuh MySQL 5.7.8+ atau MariaDB 10.2.7+.
- Nama tabel `portofolio` menggunakan ejaan Bahasa Indonesia (bukan `portfolio`).
