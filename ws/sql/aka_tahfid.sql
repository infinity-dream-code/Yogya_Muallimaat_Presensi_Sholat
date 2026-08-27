-- Aplikasi Tahfid: jadwal setoran per unit + kelas, percepatan, dan setoran siswa.
-- Abaikan error jika tabel sudah ada.
-- code03 = id kelas dari mst_kelas (master kelas).
-- Jika tabel sudah ada tanpa code03:
-- ALTER TABLE aka_tahfid_jadwal ADD COLUMN code03 VARCHAR(20) NOT NULL DEFAULT '' AFTER kelas;
-- ALTER TABLE aka_tahfid_jadwal ADD KEY idx_code03 (code03);

CREATE TABLE IF NOT EXISTS `aka_tahfid_jadwal` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code01` VARCHAR(20) NOT NULL,
  `kelas` VARCHAR(80) NOT NULL,
  `code03` VARCHAR(20) NOT NULL DEFAULT '',
  `created_by` VARCHAR(50) NOT NULL DEFAULT '',
  `updated_by` VARCHAR(50) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_unit_kelas` (`code01`, `kelas`),
  KEY `idx_code01` (`code01`),
  KEY `idx_code03` (`code03`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aka_tahfid_jadwal_detail` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `jadwal_id` INT UNSIGNED NOT NULL,
  `urut` INT NOT NULL DEFAULT 1,
  `surah_nomor` TINYINT UNSIGNED NOT NULL,
  `surah_nama` VARCHAR(80) NOT NULL DEFAULT '',
  `juz_dari` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `juz_sampai` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `is_lengkap` TINYINT(1) NOT NULL DEFAULT 1,
  `ayat_dari` SMALLINT UNSIGNED NULL DEFAULT NULL,
  `ayat_sampai` SMALLINT UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_jadwal` (`jadwal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aka_tahfid_percepatan` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `custid` VARCHAR(50) NOT NULL,
  `nocust` VARCHAR(50) NOT NULL,
  `nmcust` VARCHAR(150) NOT NULL DEFAULT '',
  `code01` VARCHAR(20) NOT NULL DEFAULT '',
  `kelas_asal` VARCHAR(80) NOT NULL DEFAULT '',
  `kelas_tujuan` VARCHAR(80) NOT NULL,
  `created_by` VARCHAR(50) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_siswa_tujuan` (`custid`, `kelas_tujuan`),
  KEY `idx_custid` (`custid`),
  KEY `idx_code01` (`code01`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aka_tahfid_setoran` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `custid` VARCHAR(50) NOT NULL,
  `nocust` VARCHAR(50) NOT NULL DEFAULT '',
  `nmcust` VARCHAR(150) NOT NULL DEFAULT '',
  `code01` VARCHAR(20) NOT NULL DEFAULT '',
  `kelas` VARCHAR(80) NOT NULL DEFAULT '',
  `jadwal_detail_id` INT UNSIGNED NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'setor',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_siswa_detail` (`custid`, `jadwal_detail_id`),
  KEY `idx_custid` (`custid`),
  KEY `idx_detail` (`jadwal_detail_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aka_tahfid_progress` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `custid` VARCHAR(50) NOT NULL,
  `nocust` VARCHAR(50) NOT NULL DEFAULT '',
  `nmcust` VARCHAR(150) NOT NULL DEFAULT '',
  `code01` VARCHAR(20) NOT NULL DEFAULT '',
  `kelas` VARCHAR(80) NOT NULL DEFAULT '',
  `jadwal_detail_id` INT UNSIGNED NOT NULL,
  `ayat_dari` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `ayat_sampai` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `status` VARCHAR(20) NOT NULL DEFAULT 'proses',
  `catatan` TEXT NULL,
  `created_by` VARCHAR(50) NOT NULL DEFAULT '',
  `updated_by` VARCHAR(50) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_siswa_detail_progress` (`custid`, `jadwal_detail_id`),
  KEY `idx_custid` (`custid`),
  KEY `idx_detail` (`jadwal_detail_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aka_tahfid_progress_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `progress_id` INT UNSIGNED NOT NULL,
  `jadwal_detail_id` INT UNSIGNED NOT NULL,
  `custid` VARCHAR(50) NOT NULL,
  `ayat_dari` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `ayat_sampai` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `status` VARCHAR(20) NOT NULL DEFAULT 'proses',
  `catatan` TEXT NULL,
  `created_by` VARCHAR(50) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_progress` (`progress_id`),
  KEY `idx_custid` (`custid`),
  KEY `idx_detail` (`jadwal_detail_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
