-- ============================================================
-- DATABASE: quemil_booking
-- Sistem Informasi Booking Quemil Makeup
-- Abdul Khalid Firmansyah (222355201013)
-- Universitas Darul Ulum Jombang, 2025/2026
-- ============================================================

CREATE DATABASE IF NOT EXISTS `quemil_booking`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `quemil_booking`;

-- ============================================================
-- 1. TABEL users
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(150) NOT NULL UNIQUE,
  `phone`      VARCHAR(20)  NOT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `role`       ENUM('user','admin') NOT NULL DEFAULT 'user',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 2. TABEL jenis_makeup
-- ============================================================
CREATE TABLE IF NOT EXISTS `jenis_makeup` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nama`       VARCHAR(100) NOT NULL,
  `deskripsi`  TEXT,
  `harga`      DECIMAL(12,2) NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 3. TABEL jam_tersedia
-- ============================================================
CREATE TABLE IF NOT EXISTS `jam_tersedia` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `jam_mulai`   TIME NOT NULL,
  `jam_selesai` TIME NOT NULL,
  `label`       VARCHAR(50) NOT NULL,
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ============================================================
-- 4. TABEL zona_transport
-- ============================================================
CREATE TABLE IF NOT EXISTS `zona_transport` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nama_zona`  VARCHAR(100) NOT NULL,
  `keterangan` VARCHAR(255),
  `biaya`      DECIMAL(12,2) NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ============================================================
-- 5. TABEL bookings (FCFS: created_at = DEFAULT CURRENT_TIMESTAMP)
-- ============================================================
CREATE TABLE IF NOT EXISTS `bookings` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `kode_booking`     VARCHAR(20)  NOT NULL UNIQUE,
  `user_id`          INT UNSIGNED NOT NULL,
  `jenis_makeup_id`  INT UNSIGNED NOT NULL,
  `jam_id`           INT UNSIGNED NOT NULL,
  `tanggal`          DATE NOT NULL,
  `tipe_layanan`     ENUM('studio','home_service') NOT NULL DEFAULT 'studio',
  `alamat_lengkap`   TEXT,
  `kota`             VARCHAR(100),
  `provinsi`         VARCHAR(100),
  `zona_id`          INT UNSIGNED DEFAULT NULL,
  `biaya_transport`  DECIMAL(12,2) NOT NULL DEFAULT 0,
  `harga_jasa`       DECIMAL(12,2) NOT NULL DEFAULT 0,
  `total_biaya`      DECIMAL(12,2) NOT NULL DEFAULT 0,
  `dp_amount`        DECIMAL(12,2) NOT NULL DEFAULT 0,
  `pelunasan_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `status`           ENUM(
                       'pending',
                       'pending_negotiation',
                       'waiting_payment',
                       'waiting_confirmation',
                       'confirmed',
                       'completed',
                       'cancelled'
                     ) NOT NULL DEFAULT 'pending',
  `slot_locked`      TINYINT(1) NOT NULL DEFAULT 0,
  `catatan_admin`    TEXT,
  `catatan_user`     TEXT,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_bookings_user`  FOREIGN KEY (`user_id`)        REFERENCES `users`(`id`)          ON DELETE CASCADE,
  CONSTRAINT `fk_bookings_jenis` FOREIGN KEY (`jenis_makeup_id`) REFERENCES `jenis_makeup`(`id`),
  CONSTRAINT `fk_bookings_jam`   FOREIGN KEY (`jam_id`)         REFERENCES `jam_tersedia`(`id`),
  CONSTRAINT `fk_bookings_zona`  FOREIGN KEY (`zona_id`)        REFERENCES `zona_transport`(`id`)
) ENGINE=InnoDB;

CREATE INDEX `idx_bookings_tanggal_jam` ON `bookings`(`tanggal`, `jam_id`);
CREATE INDEX `idx_bookings_status`      ON `bookings`(`status`);
CREATE INDEX `idx_bookings_created_at`  ON `bookings`(`created_at`);
CREATE INDEX `idx_bookings_user`        ON `bookings`(`user_id`);

-- ============================================================
-- 6. TABEL payments
-- ============================================================
CREATE TABLE IF NOT EXISTS `payments` (
  `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `booking_id`         INT UNSIGNED NOT NULL UNIQUE,
  `order_id`           VARCHAR(100) NOT NULL UNIQUE,
  `midtrans_token`     VARCHAR(255),
  `amount`             DECIMAL(12,2) NOT NULL,
  `metode`             VARCHAR(50),
  `status`             ENUM('pending','success','failed','expired','cancelled') NOT NULL DEFAULT 'pending',
  `midtrans_response`  JSON,
  `paid_at`            DATETIME DEFAULT NULL,
  `expired_at`         DATETIME DEFAULT NULL,
  `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_payments_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 7. TABEL portofolio (ejaan Bahasa Indonesia)
-- ============================================================
CREATE TABLE IF NOT EXISTS `portofolio` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `judul`      VARCHAR(150) NOT NULL,
  `deskripsi`  TEXT,
  `foto`       VARCHAR(255) NOT NULL,
  `kategori`   VARCHAR(100),
  `urutan`     INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 8. TABEL notifications
-- ============================================================
CREATE TABLE IF NOT EXISTS `notifications` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `booking_id` INT UNSIGNED DEFAULT NULL,
  `judul`      VARCHAR(200) NOT NULL,
  `pesan`      TEXT NOT NULL,
  `is_read`    TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_notif_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
  CONSTRAINT `fk_notif_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;


-- ============================================================
-- SEED DATA
-- ============================================================

-- Admin default (password: admin123)
INSERT INTO `users` (`name`, `email`, `phone`, `password`, `role`) VALUES
('Admin Quemil', 'admin@quemil.com', '081234567890',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uHxL7pTQK', 'admin');

-- Jenis Makeup
INSERT INTO `jenis_makeup` (`nama`, `deskripsi`, `harga`) VALUES
('Makeup Wisuda',            'Riasan natural elegan untuk acara wisuda',             150000),
('Makeup Pengantin',         'Riasan lengkap dan tahan lama untuk hari pernikahan',  500000),
('Makeup Karnaval / Parade', 'Riasan kreatif dan bold untuk karnaval/parade budaya', 200000),
('Makeup Kondangan',         'Riasan semi-formal untuk menghadiri acara undangan',   120000),
('Makeup Foto / Dokumentasi','Riasan khusus untuk sesi foto profesional',            130000);

-- Slot Jam
INSERT INTO `jam_tersedia` (`jam_mulai`, `jam_selesai`, `label`) VALUES
('06:00:00', '08:00:00', '06:00 - 08:00'),
('08:00:00', '10:00:00', '08:00 - 10:00'),
('10:00:00', '12:00:00', '10:00 - 12:00'),
('13:00:00', '15:00:00', '13:00 - 15:00'),
('15:00:00', '17:00:00', '15:00 - 17:00');

-- Zona Transport (sesuai dokumen skripsi)
INSERT INTO `zona_transport` (`nama_zona`, `keterangan`, `biaya`) VALUES
('Sama Lokasi',    'Studio Quemil Makeup (Dusun Sawi, Sawiji, Jogoroto)',                           0),
('Beda RT',        'Sekitar rumah, masih satu RT',                                              5000),
('Beda Dusun',     'Masih satu desa (Desa Sawiji)',                                            10000),
('Beda Desa',      'Masih satu kecamatan (Kec. Jogoroto)',                                     20000),
('Beda Kecamatan', 'Masih satu kabupaten (Kab. Jombang)',                                      40000),
('Zona 1',         'Mojokerto, Kediri, Nganjuk, Lamongan, Sidoarjo',                           60000),
('Zona 2',         'Gresik, Bojonegoro, Tuban, Pasuruan, Malang',                              80000),
('Zona 3',         'Madiun, Ngawi, Magetan, Ponorogo, Probolinggo, Lumajang',                 120000),
('Zona 4',         'Banyuwangi, Jember, Bondowoso, Situbondo, Pacitan, Sumenep, Pamekasan, Sampang, Bangkalan', 170000);
