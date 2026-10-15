-- Tanggal pelaksanaan lomba pada pengajuan prestasi siswi.
-- Jika kolom sudah ada, abaikan error Duplicate column / Duplicate column name.

ALTER TABLE `aka_reward`
  ADD COLUMN `tanggal_pelaksanaan` DATE NULL DEFAULT NULL AFTER `no_sertifikat`;
