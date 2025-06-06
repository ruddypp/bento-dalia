-- SQL script untuk mengubah semua referensi id_pengguna menjadi id_user di seluruh database
-- Script ini akan mengubah struktur tabel, indeks, dan foreign key

-- Nonaktifkan pemeriksaan foreign key sementara
SET FOREIGN_KEY_CHECKS=0;

-- ========== TABEL LOG_AKTIVITAS ==========
-- Ubah kolom id_pengguna menjadi id_user di tabel log_aktivitas
SET @check_log_aktivitas = (SELECT COUNT(*) FROM information_schema.columns 
                           WHERE table_schema = DATABASE() 
                           AND table_name = 'log_aktivitas' 
                           AND column_name = 'id_pengguna');

SET @log_aktivitas_sql = IF(@check_log_aktivitas > 0, 
                           'ALTER TABLE `log_aktivitas` CHANGE `id_pengguna` `id_user` INT(11) NULL DEFAULT NULL',
                           'SELECT 1');

PREPARE log_aktivitas_stmt FROM @log_aktivitas_sql;
EXECUTE log_aktivitas_stmt;
DEALLOCATE PREPARE log_aktivitas_stmt;

-- Hapus indeks id_pengguna jika ada di log_aktivitas
SET @check_log_aktivitas_idx = (SELECT COUNT(*) FROM information_schema.statistics 
                               WHERE table_schema = DATABASE() 
                               AND table_name = 'log_aktivitas'
                               AND index_name = 'id_pengguna');

SET @log_aktivitas_idx_sql = IF(@check_log_aktivitas_idx > 0, 
                              'ALTER TABLE `log_aktivitas` DROP INDEX `id_pengguna`',
                              'SELECT 1');

PREPARE log_aktivitas_idx_stmt FROM @log_aktivitas_idx_sql;
EXECUTE log_aktivitas_idx_stmt;
DEALLOCATE PREPARE log_aktivitas_idx_stmt;

-- Tambahkan indeks id_user jika belum ada di log_aktivitas
SET @check_log_aktivitas_idx2 = (SELECT COUNT(*) FROM information_schema.statistics 
                                WHERE table_schema = DATABASE() 
                                AND table_name = 'log_aktivitas'
                                AND index_name = 'id_user');

SET @log_aktivitas_idx2_sql = IF(@check_log_aktivitas_idx2 = 0, 
                               'ALTER TABLE `log_aktivitas` ADD KEY `id_user` (`id_user`)',
                               'SELECT 1');

PREPARE log_aktivitas_idx2_stmt FROM @log_aktivitas_idx2_sql;
EXECUTE log_aktivitas_idx2_stmt;
DEALLOCATE PREPARE log_aktivitas_idx2_stmt;

-- ========== TABEL PENERIMAAN ==========
-- Ubah kolom id_pengguna menjadi id_user di tabel penerimaan
SET @check_penerimaan = (SELECT COUNT(*) FROM information_schema.columns 
                        WHERE table_schema = DATABASE() 
                        AND table_name = 'penerimaan' 
                        AND column_name = 'id_pengguna');

SET @penerimaan_sql = IF(@check_penerimaan > 0, 
                        'ALTER TABLE `penerimaan` CHANGE `id_pengguna` `id_user` INT(11) NULL DEFAULT NULL',
                        'SELECT 1');

PREPARE penerimaan_stmt FROM @penerimaan_sql;
EXECUTE penerimaan_stmt;
DEALLOCATE PREPARE penerimaan_stmt;

-- Hapus indeks id_pengguna jika ada di penerimaan
SET @check_penerimaan_idx = (SELECT COUNT(*) FROM information_schema.statistics 
                            WHERE table_schema = DATABASE() 
                            AND table_name = 'penerimaan'
                            AND index_name = 'id_pengguna');

SET @penerimaan_idx_sql = IF(@check_penerimaan_idx > 0, 
                           'ALTER TABLE `penerimaan` DROP INDEX `id_pengguna`',
                           'SELECT 1');

PREPARE penerimaan_idx_stmt FROM @penerimaan_idx_sql;
EXECUTE penerimaan_idx_stmt;
DEALLOCATE PREPARE penerimaan_idx_stmt;

-- Tambahkan indeks id_user jika belum ada di penerimaan
SET @check_penerimaan_idx2 = (SELECT COUNT(*) FROM information_schema.statistics 
                             WHERE table_schema = DATABASE() 
                             AND table_name = 'penerimaan'
                             AND index_name = 'id_user');

SET @penerimaan_idx2_sql = IF(@check_penerimaan_idx2 = 0, 
                            'ALTER TABLE `penerimaan` ADD KEY `id_user` (`id_user`)',
                            'SELECT 1');

PREPARE penerimaan_idx2_stmt FROM @penerimaan_idx2_sql;
EXECUTE penerimaan_idx2_stmt;
DEALLOCATE PREPARE penerimaan_idx2_stmt;

-- ========== TABEL STOK_OPNAME ==========
-- Ubah kolom id_pengguna menjadi id_user di tabel stok_opname
SET @check_stok_opname = (SELECT COUNT(*) FROM information_schema.columns 
                         WHERE table_schema = DATABASE() 
                         AND table_name = 'stok_opname' 
                         AND column_name = 'id_pengguna');

SET @stok_opname_sql = IF(@check_stok_opname > 0, 
                         'ALTER TABLE `stok_opname` CHANGE `id_pengguna` `id_user` INT(11) NULL DEFAULT NULL',
                         'SELECT 1');

PREPARE stok_opname_stmt FROM @stok_opname_sql;
EXECUTE stok_opname_stmt;
DEALLOCATE PREPARE stok_opname_stmt;

-- Hapus indeks id_pengguna jika ada di stok_opname
SET @check_stok_opname_idx = (SELECT COUNT(*) FROM information_schema.statistics 
                             WHERE table_schema = DATABASE() 
                             AND table_name = 'stok_opname'
                             AND index_name = 'id_pengguna');

SET @stok_opname_idx_sql = IF(@check_stok_opname_idx > 0, 
                            'ALTER TABLE `stok_opname` DROP INDEX `id_pengguna`',
                            'SELECT 1');

PREPARE stok_opname_idx_stmt FROM @stok_opname_idx_sql;
EXECUTE stok_opname_idx_stmt;
DEALLOCATE PREPARE stok_opname_idx_stmt;

-- Tambahkan indeks id_user jika belum ada di stok_opname
SET @check_stok_opname_idx2 = (SELECT COUNT(*) FROM information_schema.statistics 
                              WHERE table_schema = DATABASE() 
                              AND table_name = 'stok_opname'
                              AND index_name = 'id_user');

SET @stok_opname_idx2_sql = IF(@check_stok_opname_idx2 = 0, 
                             'ALTER TABLE `stok_opname` ADD KEY `id_user` (`id_user`)',
                             'SELECT 1');

PREPARE stok_opname_idx2_stmt FROM @stok_opname_idx2_sql;
EXECUTE stok_opname_idx2_stmt;
DEALLOCATE PREPARE stok_opname_idx2_stmt;

-- ========== TABEL PENGELUARAN ==========
-- Ubah kolom id_pengguna menjadi id_user di tabel pengeluaran
SET @check_pengeluaran = (SELECT COUNT(*) FROM information_schema.columns 
                         WHERE table_schema = DATABASE() 
                         AND table_name = 'pengeluaran' 
                         AND column_name = 'id_pengguna');

SET @pengeluaran_sql = IF(@check_pengeluaran > 0, 
                         'ALTER TABLE `pengeluaran` CHANGE `id_pengguna` `id_user` INT(11) NULL DEFAULT NULL',
                         'SELECT 1');

PREPARE pengeluaran_stmt FROM @pengeluaran_sql;
EXECUTE pengeluaran_stmt;
DEALLOCATE PREPARE pengeluaran_stmt;

-- Hapus indeks id_pengguna jika ada di pengeluaran
SET @check_pengeluaran_idx = (SELECT COUNT(*) FROM information_schema.statistics 
                             WHERE table_schema = DATABASE() 
                             AND table_name = 'pengeluaran'
                             AND index_name = 'id_pengguna');

SET @pengeluaran_idx_sql = IF(@check_pengeluaran_idx > 0, 
                            'ALTER TABLE `pengeluaran` DROP INDEX `id_pengguna`',
                            'SELECT 1');

PREPARE pengeluaran_idx_stmt FROM @pengeluaran_idx_sql;
EXECUTE pengeluaran_idx_stmt;
DEALLOCATE PREPARE pengeluaran_idx_stmt;

-- Tambahkan indeks id_user jika belum ada di pengeluaran
SET @check_pengeluaran_idx2 = (SELECT COUNT(*) FROM information_schema.statistics 
                              WHERE table_schema = DATABASE() 
                              AND table_name = 'pengeluaran'
                              AND index_name = 'id_user');

SET @pengeluaran_idx2_sql = IF(@check_pengeluaran_idx2 = 0, 
                             'ALTER TABLE `pengeluaran` ADD KEY `id_user` (`id_user`)',
                             'SELECT 1');

PREPARE pengeluaran_idx2_stmt FROM @pengeluaran_idx2_sql;
EXECUTE pengeluaran_idx2_stmt;
DEALLOCATE PREPARE pengeluaran_idx2_stmt;

-- ========== PEMBARUAN FOREIGN KEY ==========
-- Hapus foreign key lama jika ada
ALTER TABLE `log_aktivitas` DROP FOREIGN KEY IF EXISTS `log_aktivitas_ibfk_1`;
ALTER TABLE `penerimaan` DROP FOREIGN KEY IF EXISTS `penerimaan_ibfk_2`;
ALTER TABLE `stok_opname` DROP FOREIGN KEY IF EXISTS `stok_opname_ibfk_2`;
ALTER TABLE `pengeluaran` DROP FOREIGN KEY IF EXISTS `pengeluaran_ibfk_1`;

-- Bersihkan data yang tidak valid
UPDATE `log_aktivitas` SET `id_user` = NULL 
WHERE `id_user` IS NOT NULL AND `id_user` NOT IN (SELECT `id_user` FROM `users`);

UPDATE `penerimaan` SET `id_user` = NULL 
WHERE `id_user` IS NOT NULL AND `id_user` NOT IN (SELECT `id_user` FROM `users`);

UPDATE `stok_opname` SET `id_user` = NULL 
WHERE `id_user` IS NOT NULL AND `id_user` NOT IN (SELECT `id_user` FROM `users`);

UPDATE `pengeluaran` SET `id_user` = NULL 
WHERE `id_user` IS NOT NULL AND `id_user` NOT IN (SELECT `id_user` FROM `users`);

-- Aktifkan kembali pemeriksaan foreign key
SET FOREIGN_KEY_CHECKS=1;

-- Verifikasi perubahan
SELECT 'Perubahan selesai. Berikut adalah kolom-kolom yang telah diubah:' AS 'Info';
SELECT table_name, column_name 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
AND column_name = 'id_user' 
AND table_name IN ('log_aktivitas', 'penerimaan', 'stok_opname', 'pengeluaran'); 