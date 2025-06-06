-- SQL script to update all tables from id_pengguna to id_user

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS=0;

-- Periksa apakah kolom id_pengguna ada di tabel penerimaan
-- Jika ada, ubah namanya menjadi id_user
-- Jika tidak, tambahkan kolom id_user jika belum ada
SET @penerimaan_check = (SELECT COUNT(*) FROM information_schema.columns 
                         WHERE table_schema = DATABASE() 
                         AND table_name = 'penerimaan' 
                         AND column_name = 'id_pengguna');

SET @penerimaan_user_check = (SELECT COUNT(*) FROM information_schema.columns 
                              WHERE table_schema = DATABASE() 
                              AND table_name = 'penerimaan' 
                              AND column_name = 'id_user');

-- Untuk tabel penerimaan
SET @penerimaan_sql = IF(@penerimaan_check > 0, 
                         'ALTER TABLE `penerimaan` CHANGE `id_pengguna` `id_user` INT(11) NULL DEFAULT NULL',
                         IF(@penerimaan_user_check = 0,
                            'ALTER TABLE `penerimaan` ADD `id_user` INT(11) NULL DEFAULT NULL',
                            'SELECT 1'));

PREPARE penerimaan_stmt FROM @penerimaan_sql;
EXECUTE penerimaan_stmt;
DEALLOCATE PREPARE penerimaan_stmt;

-- Periksa apakah kolom id_pengguna ada di tabel stok_opname
-- Jika ada, ubah namanya menjadi id_user
-- Jika tidak, tambahkan kolom id_user jika belum ada
SET @stok_opname_check = (SELECT COUNT(*) FROM information_schema.columns 
                          WHERE table_schema = DATABASE() 
                          AND table_name = 'stok_opname' 
                          AND column_name = 'id_pengguna');

SET @stok_opname_user_check = (SELECT COUNT(*) FROM information_schema.columns 
                               WHERE table_schema = DATABASE() 
                               AND table_name = 'stok_opname' 
                               AND column_name = 'id_user');

-- Untuk tabel stok_opname
SET @stok_opname_sql = IF(@stok_opname_check > 0, 
                          'ALTER TABLE `stok_opname` CHANGE `id_pengguna` `id_user` INT(11) NULL DEFAULT NULL',
                          IF(@stok_opname_user_check = 0,
                             'ALTER TABLE `stok_opname` ADD `id_user` INT(11) NULL DEFAULT NULL',
                             'SELECT 1'));

PREPARE stok_opname_stmt FROM @stok_opname_sql;
EXECUTE stok_opname_stmt;
DEALLOCATE PREPARE stok_opname_stmt;

-- Periksa apakah kolom id_pengguna ada di tabel log_aktivitas
-- Jika ada, ubah namanya menjadi id_user
-- Jika tidak, tambahkan kolom id_user jika belum ada
SET @log_aktivitas_check = (SELECT COUNT(*) FROM information_schema.columns 
                            WHERE table_schema = DATABASE() 
                            AND table_name = 'log_aktivitas' 
                            AND column_name = 'id_pengguna');

SET @log_aktivitas_user_check = (SELECT COUNT(*) FROM information_schema.columns 
                                 WHERE table_schema = DATABASE() 
                                 AND table_name = 'log_aktivitas' 
                                 AND column_name = 'id_user');

-- Untuk tabel log_aktivitas
SET @log_aktivitas_sql = IF(@log_aktivitas_check > 0, 
                            'ALTER TABLE `log_aktivitas` CHANGE `id_pengguna` `id_user` INT(11) NULL DEFAULT NULL',
                            IF(@log_aktivitas_user_check = 0,
                               'ALTER TABLE `log_aktivitas` ADD `id_user` INT(11) NULL DEFAULT NULL',
                               'SELECT 1'));

PREPARE log_aktivitas_stmt FROM @log_aktivitas_sql;
EXECUTE log_aktivitas_stmt;
DEALLOCATE PREPARE log_aktivitas_stmt;

-- Set NULL for invalid references (id_user values that don't exist in users table)
UPDATE `log_aktivitas` SET `id_user` = NULL 
WHERE `id_user` IS NOT NULL AND `id_user` NOT IN (SELECT `id_user` FROM `users`);

UPDATE `penerimaan` SET `id_user` = NULL 
WHERE `id_user` IS NOT NULL AND `id_user` NOT IN (SELECT `id_user` FROM `users`);

UPDATE `stok_opname` SET `id_user` = NULL 
WHERE `id_user` IS NOT NULL AND `id_user` NOT IN (SELECT `id_user` FROM `users`);

-- Enable foreign key checks again
SET FOREIGN_KEY_CHECKS=1;

-- Verify changes
-- SELECT * FROM information_schema.columns WHERE table_name = 'log_aktivitas' AND column_name = 'id_user';
-- SELECT * FROM information_schema.columns WHERE table_name = 'penerimaan' AND column_name = 'id_user';
-- SELECT * FROM information_schema.columns WHERE table_name = 'stok_opname' AND column_name = 'id_user'; 