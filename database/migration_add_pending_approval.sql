-- ============================================================
-- MIGRATION: Tambah status pending_approval
-- Untuk mengaktifkan approval admin sebelum user bisa bayar
-- Jalankan di Laragon: Klik kanan MySQL > Open
-- Atau via CLI: mysql -u root quemil_booking < database/migration_add_pending_approval.sql
-- ============================================================

USE `quemil_booking`;

-- Tambah nilai 'pending_approval' ke ENUM status di tabel bookings
ALTER TABLE `bookings` 
MODIFY COLUMN `status` ENUM(
  'pending',
  'pending_approval',
  'pending_negotiation',
  'waiting_payment',
  'waiting_confirmation',
  'confirmed',
  'completed',
  'cancelled'
) NOT NULL DEFAULT 'pending';

-- Selesai
SELECT 'Migration berhasil: status pending_approval telah ditambahkan' AS result;
