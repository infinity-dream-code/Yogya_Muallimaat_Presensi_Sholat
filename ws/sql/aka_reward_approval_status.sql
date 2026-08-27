-- Pilihan di kolom aka_reward.isapproved: pending | approve | canceled
-- Bukan 0/1/NULL. Di Navicat tampil sebagai dropdown ENUM.
-- Jalankan berurutan.

ALTER TABLE `aka_reward`
  MODIFY COLUMN `isapproved` VARCHAR(20) NOT NULL DEFAULT 'pending';

UPDATE `aka_reward` SET `isapproved` = 'pending' WHERE `isapproved` IN ('0', '');
UPDATE `aka_reward` SET `isapproved` = 'approve' WHERE `isapproved` IN ('1');
UPDATE `aka_reward` SET `isapproved` = 'pending' WHERE `isapproved` NOT IN ('pending', 'approve', 'canceled');

ALTER TABLE `aka_reward`
  MODIFY COLUMN `isapproved` ENUM('pending','approve','canceled') NOT NULL DEFAULT 'pending';
