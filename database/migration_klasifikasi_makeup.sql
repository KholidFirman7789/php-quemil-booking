-- ============================================================
-- MIGRATION: Revisi Klasifikasi Jenis Makeup
-- Quemil Booking - Abdul Khalid Firmansyah (222355201013)
-- ============================================================
-- CATATAN: Kolom kategori & gender sudah ditambahkan.
-- File ini hanya untuk referensi / fresh install ulang data jenis_makeup.
-- Untuk fresh install, jalankan quemil_booking.sql dari awal.
-- ============================================================

USE `quemil_booking`;

-- Hapus data lama (disable FK checks sementara)
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `jenis_makeup`;
SET FOREIGN_KEY_CHECKS = 1;

-- Masukkan data baru sesuai daftar harga resmi Quemil Makeup
INSERT INTO `jenis_makeup` (`nama`, `kategori`, `gender`, `deskripsi`, `harga`) VALUES
-- Kategori 1: Makeup Reguler - Wanita
('Makeup Natural',                   'Reguler',     'wanita', 'Riasan natural sehari-hari untuk tampilan segar dan bersih',                        100000),
('Makeup Dance',                     'Reguler',     'wanita', 'Riasan tebal dan tahan lama untuk kebutuhan pentas tari',                          100000),
('Makeup Wisuda',                    'Reguler',     'wanita', 'Riasan elegan dan natural untuk acara wisuda',                                     150000),
('Makeup Wisuda + Kebaya (Lengkap)', 'Reguler',     'wanita', 'Paket lengkap riasan wisuda termasuk kebaya dan aksesoris',                        400000),
-- Kategori 1: Makeup Reguler - Pria
('Makeup Natural Pria (Grooming)',   'Reguler',     'pria',   'Grooming ringan untuk tampilan natural dan rapi',                                  100000),
('Makeup Formal Pria (Grooming)',    'Reguler',     'pria',   'Grooming formal untuk acara resmi dan presentasi',                                 150000),
-- Kategori 2: Makeup Dayang - Wanita
('Makeup Dayang',                    'Dayang',      'wanita', 'Riasan pakem untuk dayang-dayang dalam prosesi adat',                              250000),
('Makeup Dayang + Hairdo',           'Dayang',      'wanita', 'Riasan dayang lengkap dengan penataan rambut',                                     300000),
-- Kategori 2: Makeup Dayang - Pria
('Makeup Dayang Pria',               'Dayang',      'pria',   'Riasan pakem untuk dayang pria dalam prosesi adat',                                150000),
('Makeup Dayang + Styling Rambut',   'Dayang',      'pria',   'Riasan dayang pria lengkap dengan styling rambut',                                 200000),
-- Kategori 3: Makeup Karnaval - Wanita
('Makeup Karnaval',                  'Karnaval',    'wanita', 'Riasan kreatif dan bold untuk karnaval atau parade budaya',                        200000),
('Makeup Karnaval + Kostum Mascot',  'Karnaval',    'wanita', 'Paket lengkap riasan karnaval termasuk kostum maskot',                             900000),
-- Kategori 3: Makeup Karnaval - Pria
('Makeup Karnaval Pria',             'Karnaval',    'pria',   'Riasan kreatif dan bold untuk karnaval atau parade budaya',                        200000),
('Makeup Karnaval Lengkap',          'Karnaval',    'pria',   'Paket lengkap riasan dan kostum karnaval untuk pria',                              700000),
-- Kategori 4: Makeup Pengantin - Wanita
('Makeup Pengantin',                 'Pengantin',   'wanita', 'Riasan pengantin wanita yang tahan lama dan elegan',                             1000000),
('Makeup Pengantin Lengkap',         'Pengantin',   'wanita', 'Paket riasan pengantin wanita lengkap dengan aksesoris dan busana',              3000000),
-- Kategori 4: Makeup Pengantin - Pria
('Groom Pengantin',                  'Pengantin',   'pria',   'Grooming pengantin pria untuk tampilan segar dan rapi di hari pernikahan',         300000),
('Groom Pengantin + Styling Rambut', 'Pengantin',   'pria',   'Grooming pengantin pria lengkap dengan penataan rambut',                          450000),
-- Kategori 5: Sewa Baju - Wanita
('Gaun Pengantin Basic',             'Sewa Baju',   'wanita', 'Sewa gaun pengantin model basic, cocok untuk acara pernikahan sederhana',          150000),
('Gaun Pengantin Standard',          'Sewa Baju',   'wanita', 'Sewa gaun pengantin standard dengan pilihan warna beragam',                       500000),
('Gaun Pengantin Premium',           'Sewa Baju',   'wanita', 'Sewa gaun pengantin premium dengan detail bordiran dan manik-manik',             1000000),
('Gaun Pengantin Luxury',            'Sewa Baju',   'wanita', 'Sewa gaun pengantin luxury dengan material high-end dan desain eksklusif',       1500000),
('Gaun Pengantin Exclusive',         'Sewa Baju',   'wanita', 'Sewa gaun pengantin exclusive koleksi terbatas dengan aksesoris lengkap',        2000000),
-- Kategori 5: Sewa Baju - Pria
('Jas Pengantin Basic',              'Sewa Baju',   'pria',   'Sewa jas pengantin model basic, cocok untuk acara pernikahan sederhana',           150000),
('Jas Pengantin Standard',           'Sewa Baju',   'pria',   'Sewa jas pengantin standard dengan pilihan warna dan model beragam',              250000),
('Jas Pengantin Premium',            'Sewa Baju',   'pria',   'Sewa jas pengantin premium dengan bahan berkualitas tinggi',                      350000),
('Jas Pengantin Luxury',             'Sewa Baju',   'pria',   'Sewa jas pengantin luxury dengan jas, vest, dan aksesoris lengkap',               500000),
-- Kategori 6: Sewa Sandal - Wanita
('Sandal Flat',                      'Sewa Sandal', 'wanita', 'Sewa sandal flat nyaman untuk berbagai acara',                                    100000),
('Sandal Heels (3-5 cm)',            'Sewa Sandal', 'wanita', 'Sewa sandal heels tinggi 3-5 cm, elegan dan nyaman',                              100000),
('Sandal Heels (7-10 cm)',           'Sewa Sandal', 'wanita', 'Sewa sandal heels tinggi 7-10 cm untuk tampilan memukau',                         150000),
-- Kategori 6: Sewa Sandal - Pria
('Sandal Formal Pria',               'Sewa Sandal', 'pria',   'Sewa sandal formal pria untuk acara resmi dan pernikahan',                        100000),
-- Kategori 6: Sewa Sandal - Couple
('Sandal Pengantin Couple',          'Sewa Sandal', 'couple', 'Sewa sandal couple untuk pasangan pengantin',                                     150000),
('Sandal Couple Premium',            'Sewa Sandal', 'couple', 'Sewa sandal couple premium dengan desain matching dan material berkualitas',       200000),
-- Kategori 7: Makeup Anak-anak - Reguler
('Makeup Natural Anak',              'Reguler',     'anak',   'Makeup natural ringan untuk anak-anak, cocok untuk foto, acara keluarga, atau kegiatan sehari-hari. Menggunakan produk aman khusus anak.',   75000),
('Makeup Dance Anak',                'Reguler',     'anak',   'Makeup tari untuk penampilan panggung anak-anak. Tahan lama dan tetap terlihat natural dari jarak jauh.',                                    85000),
('Makeup Pentas / Penampilan Anak',  'Reguler',     'anak',   'Makeup untuk pentas seni, drama, atau penampilan sekolah. Warna lebih ekspresif namun tetap aman di kulit anak.',                           85000),
-- Kategori 7: Makeup Anak-anak - Karnaval
('Makeup Karnaval Anak',             'Karnaval',    'anak',   'Makeup karnaval ceria dan warna-warni untuk anak-anak. Menggunakan face paint dan produk aman yang mudah dibersihkan.',                    100000),
('Makeup Karnaval Anak + Kostum',    'Karnaval',    'anak',   'Paket lengkap makeup karnaval anak dengan sewa kostum. Tampil maksimal di parade atau karnaval sekolah.',                                  400000);
