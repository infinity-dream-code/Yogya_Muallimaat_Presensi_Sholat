-- Unit katalog: kolom code01 di aka_prestasi_kategori.
-- Kosong = semua unit. Terisi = kategori + tingkat + poin di dalamnya hanya untuk unit itu.
-- Jika poin per unit berbeda: buat kategori terpisah (kode sama boleh) dengan code01 unit tersebut.
--
-- Jika sempat menjalankan versi tabel junction, drop dulu:

DROP TABLE IF EXISTS `aka_prestasi_katalog_unit`;
DROP TABLE IF EXISTS `aka_prestasi_poin_unit`;

ALTER TABLE `aka_prestasi_kategori`
  ADD COLUMN `code01` VARCHAR(20) NOT NULL DEFAULT '' AFTER `urut`;
