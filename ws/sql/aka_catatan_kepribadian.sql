-- Catatan kepribadian siswi (input Musrifah).
-- Abaikan error jika tabel sudah ada.

CREATE TABLE IF NOT EXISTS `aka_catatan_kepribadian` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `custid` VARCHAR(50) NOT NULL,
  `nocust` VARCHAR(50) NOT NULL,
  `nmcust` VARCHAR(150) NOT NULL DEFAULT '',
  `kelas` VARCHAR(80) NOT NULL DEFAULT '',
  `code01` VARCHAR(20) NOT NULL DEFAULT '',
  `bta` VARCHAR(20) NOT NULL,
  `semester` TINYINT NOT NULL DEFAULT 1,
  `jenis_pelanggaran` VARCHAR(500) NOT NULL,
  `bentuk_pembinaan` VARCHAR(500) NOT NULL,
  `skor` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_by` VARCHAR(50) NOT NULL DEFAULT '',
  `updated_by` VARCHAR(50) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_nocust` (`nocust`),
  KEY `idx_bta_sem` (`bta`, `semester`),
  KEY `idx_code01` (`code01`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
