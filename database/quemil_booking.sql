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
  `kategori`   VARCHAR(50)  NOT NULL DEFAULT 'Reguler',
  `gender`     ENUM('wanita','pria','couple','anak') NOT NULL DEFAULT 'wanita',
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
  `jam_id`           INT UNSIGNED NULL DEFAULT NULL,
  `tanggal`          DATE NOT NULL,
  `tipe_layanan`     ENUM('studio','home_service') NOT NULL DEFAULT 'studio',
  `alamat_lengkap`   TEXT,
  `kota`             VARCHAR(100),
  `provinsi`         VARCHAR(100),
  `zona_id`          INT UNSIGNED DEFAULT NULL,
  `maps_url`         VARCHAR(500) DEFAULT NULL,
  `jumlah_orang`     TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `biaya_transport`  DECIMAL(12,2) NOT NULL DEFAULT 0,
  `harga_jasa`       DECIMAL(12,2) NOT NULL DEFAULT 0,
  `total_biaya`      DECIMAL(12,2) NOT NULL DEFAULT 0,
  `dp_amount`        DECIMAL(12,2) NOT NULL DEFAULT 0,
  `pelunasan_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `status`           ENUM(
                       'pending',
                       'pending_approval',
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
  CONSTRAINT `fk_bookings_zona`  FOREIGN KEY (`zona_id`)        REFERENCES `zona_transport`(`id`)
) ENGINE=InnoDB;

CREATE INDEX `idx_bookings_tanggal`    ON `bookings`(`tanggal`);
CREATE INDEX `idx_bookings_status`     ON `bookings`(`status`);
CREATE INDEX `idx_bookings_created_at` ON `bookings`(`created_at`);
CREATE INDEX `idx_bookings_user`       ON `bookings`(`user_id`);

-- ============================================================
-- MIGRATION: Jalankan query ini jika database sudah ada
-- (tidak perlu jika install fresh dari file ini)
-- ============================================================
-- Revisi: tambah kolom kategori & gender ke jenis_makeup
-- ALTER TABLE `jenis_makeup` ADD COLUMN `kategori` VARCHAR(50) NOT NULL DEFAULT 'Reguler' AFTER `nama`;
-- ALTER TABLE `jenis_makeup` ADD COLUMN `gender` ENUM('wanita','pria','couple') NOT NULL DEFAULT 'wanita' AFTER `kategori`;
-- Revisi: tambah kolom jumlah_orang ke bookings
-- ALTER TABLE `bookings` ADD COLUMN `jumlah_orang` TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER `maps_url`;
-- ALTER TABLE `bookings` MODIFY COLUMN `jam_id` INT UNSIGNED NULL DEFAULT NULL;
-- ALTER TABLE `bookings` DROP FOREIGN KEY `fk_bookings_jam`;
-- DROP INDEX `idx_bookings_tanggal_jam` ON `bookings`;
-- ALTER TABLE `bookings` DROP COLUMN IF EXISTS `latitude`;
-- ALTER TABLE `bookings` DROP COLUMN IF EXISTS `longitude`;
-- ALTER TABLE `bookings` ADD COLUMN `maps_url` VARCHAR(500) DEFAULT NULL AFTER `zona_id`;

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

-- Jenis Makeup (kategori & gender sesuai daftar harga resmi Quemil Makeup)
INSERT INTO `jenis_makeup` (`nama`, `kategori`, `gender`, `deskripsi`, `harga`) VALUES
-- Kategori 1: Makeup Reguler - Wanita
('Makeup Natural',                   'Reguler',   'wanita', 'Riasan natural sehari-hari untuk tampilan segar dan bersih',                          100000),
('Makeup Dance',                     'Reguler',   'wanita', 'Riasan tebal dan tahan lama untuk kebutuhan pentas tari',                            100000),
('Makeup Wisuda',                    'Reguler',   'wanita', 'Riasan elegan dan natural untuk acara wisuda',                                       150000),
('Makeup Wisuda + Kebaya (Lengkap)', 'Reguler',   'wanita', 'Paket lengkap riasan wisuda termasuk kebaya dan aksesoris',                          400000),
-- Kategori 1: Makeup Reguler - Pria
('Makeup Natural Pria (Grooming)',   'Reguler',   'pria',   'Grooming ringan untuk tampilan natural dan rapi',                                    100000),
('Makeup Formal Pria (Grooming)',    'Reguler',   'pria',   'Grooming formal untuk acara resmi dan presentasi',                                   150000),
-- Kategori 2: Makeup Dayang - Wanita
('Makeup Dayang',                    'Dayang',    'wanita', 'Riasan pakem untuk dayang-dayang dalam prosesi adat',                                250000),
('Makeup Dayang + Hairdo',           'Dayang',    'wanita', 'Riasan dayang lengkap dengan penataan rambut',                                       300000),
-- Kategori 2: Makeup Dayang - Pria
('Makeup Dayang Pria',               'Dayang',    'pria',   'Riasan pakem untuk dayang pria dalam prosesi adat',                                  150000),
('Makeup Dayang + Styling Rambut',   'Dayang',    'pria',   'Riasan dayang pria lengkap dengan styling rambut',                                   200000),
-- Kategori 3: Makeup Karnaval - Wanita
('Makeup Karnaval',                  'Karnaval',  'wanita', 'Riasan kreatif dan bold untuk karnaval atau parade budaya',                          200000),
('Makeup Karnaval + Kostum Mascot',  'Karnaval',  'wanita', 'Paket lengkap riasan karnaval termasuk kostum maskot',                               900000),
-- Kategori 3: Makeup Karnaval - Pria
('Makeup Karnaval Pria',             'Karnaval',  'pria',   'Riasan kreatif dan bold untuk karnaval atau parade budaya',                          200000),
('Makeup Karnaval Lengkap',          'Karnaval',  'pria',   'Paket lengkap riasan dan kostum karnaval untuk pria',                                700000),
-- Kategori 4: Makeup Pengantin - Wanita
('Makeup Pengantin',                 'Pengantin', 'wanita', 'Riasan pengantin wanita yang tahan lama dan elegan',                               1000000),
('Makeup Pengantin Lengkap',         'Pengantin', 'wanita', 'Paket riasan pengantin wanita lengkap dengan aksesoris dan busana',                3000000),
-- Kategori 4: Makeup Pengantin - Pria
('Groom Pengantin',                  'Pengantin', 'pria',   'Grooming pengantin pria untuk tampilan segar dan rapi di hari pernikahan',           300000),
('Groom Pengantin + Styling Rambut', 'Pengantin', 'pria',   'Grooming pengantin pria lengkap dengan penataan rambut',                            450000),
-- Kategori 5: Sewa Baju - Wanita
('Gaun Pengantin Basic',             'Sewa Baju', 'wanita', 'Sewa gaun pengantin model basic, cocok untuk acara pernikahan sederhana',            150000),
('Gaun Pengantin Standard',          'Sewa Baju', 'wanita', 'Sewa gaun pengantin model standard dengan pilihan warna beragam',                   500000),
('Gaun Pengantin Premium',           'Sewa Baju', 'wanita', 'Sewa gaun pengantin premium dengan detail bordiran dan manik-manik',               1000000),
('Gaun Pengantin Luxury',            'Sewa Baju', 'wanita', 'Sewa gaun pengantin luxury dengan material high-end dan desain eksklusif',         1500000),
('Gaun Pengantin Exclusive',         'Sewa Baju', 'wanita', 'Sewa gaun pengantin exclusive koleksi terbatas dengan aksesoris lengkap',          2000000),
-- Kategori 5: Sewa Baju - Pria
('Jas Pengantin Basic',              'Sewa Baju', 'pria',   'Sewa jas pengantin model basic, cocok untuk acara pernikahan sederhana',             150000),
('Jas Pengantin Standard',           'Sewa Baju', 'pria',   'Sewa jas pengantin standard dengan pilihan warna dan model beragam',                250000),
('Jas Pengantin Premium',            'Sewa Baju', 'pria',   'Sewa jas pengantin premium dengan bahan berkualitas tinggi',                        350000),
('Jas Pengantin Luxury',             'Sewa Baju', 'pria',   'Sewa jas pengantin luxury dengan jas, vest, dan aksesoris lengkap',                 500000),
-- Kategori 6: Sewa Sandal - Wanita
('Sandal Flat',                      'Sewa Sandal', 'wanita', 'Sewa sandal flat nyaman untuk berbagai acara',                                    100000),
('Sandal Heels (3-5 cm)',            'Sewa Sandal', 'wanita', 'Sewa sandal heels tinggi 3-5 cm, elegan dan nyaman',                              100000),
('Sandal Heels (7-10 cm)',           'Sewa Sandal', 'wanita', 'Sewa sandal heels tinggi 7-10 cm untuk tampilan memukau',                         150000),
-- Kategori 6: Sewa Sandal - Pria
('Sandal Formal Pria',               'Sewa Sandal', 'pria',   'Sewa sandal formal pria untuk acara resmi dan pernikahan',                        100000),
-- Kategori 6: Sewa Sandal - Couple
('Sandal Pengantin Couple',          'Sewa Sandal', 'couple', 'Sewa sandal couple untuk pasangan pengantin',                                     150000),
('Sandal Couple Premium',            'Sewa Sandal', 'couple', 'Sewa sandal couple premium dengan desain matching dan material berkualitas',       200000);

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
('Zona 4',         'Banyuwangi, Jember, Bondowoso, Situbondo, Pacitan, Sumenep, Pamekasan, Sampang, Bangkalan', 170000),
('Zona 5 - Luar Jatim', 'Jawa Tengah, DI Yogyakarta, Jawa Barat, DKI Jakarta, Banten',                  0);

-- ============================================================
-- 9. TABEL testimoni
-- ============================================================
CREATE TABLE IF NOT EXISTS `testimoni` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nama`       VARCHAR(100) NOT NULL,
  `event`      VARCHAR(100) NOT NULL,
  `teks`       TEXT NOT NULL,
  `rating`     TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `urutan`     INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Seed 3 testimoni default
INSERT IGNORE INTO `testimoni` (`id`, `nama`, `event`, `teks`, `rating`, `urutan`, `is_active`) VALUES
(1, 'Siti Rahayu',    'Wisuda S1',  'Hasilnya luar biasa! Riasannya natural tapi tetap glowing. Semua teman nanya makeup dimana.', 5, 1, 1),
(2, 'Dewi Kartika',   'Pernikahan', 'Sangat profesional dan on-time. Harganya juga sangat terjangkau untuk kualitas segini.',      5, 2, 1),
(3, 'Rina Wulandari', 'Karnaval',   'Riasan karnavalnya kreatif banget! Sesuai tema dan tahan lama seharian penuh.',               5, 3, 1);

-- ============================================================
-- MIGRATION: Jalankan jika database sudah ada (belum ada tabel testimoni)
-- CREATE TABLE IF NOT EXISTS `testimoni` ... (jalankan CREATE TABLE di atas)
-- ============================================================

-- ============================================================
-- 10. TABEL site_settings
-- ============================================================
CREATE TABLE IF NOT EXISTS `site_settings` (
  `setting_key`   VARCHAR(100) NOT NULL PRIMARY KEY,
  `setting_value` TEXT         NOT NULL DEFAULT ''
) ENGINE=InnoDB;

-- Seed default hero image (kosong, diisi via admin)
INSERT IGNORE INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('hero_image', '');
