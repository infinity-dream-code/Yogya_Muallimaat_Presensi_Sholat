-- Catatan admin saat prestasi ditolak.
-- Abaikan error Duplicate column / Duplicate column name jika sudah ada.
-- Jika error syntax (MySQL lama), hapus "IF NOT EXISTS" lalu jalankan lagi.

ALTER TABLE `aka_reward`
  ADD COLUMN IF NOT EXISTS `catatan_admin` VARCHAR(500) NULL DEFAULT NULL AFTER `approvedby`;
