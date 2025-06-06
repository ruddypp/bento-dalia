-- SQL script to update all indexes and keys that still use id_pengguna

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS=0;

-- Periksa dan perbaiki indeks di berbagai tabel

-- Untuk tabel log_aktivitas
SET @check_index1 = (SELECT COUNT(*) FROM information_schema.statistics 
                     WHERE table_schema = DATABASE() 
                     AND table_name = 'log_aktivitas'
                     AND index_name = 'id_pengguna');

-- Drop indeks id_pengguna jika ada di log_aktivitas
SET @drop_index_sql = IF(@check_index1 > 0, 
                         'ALTER TABLE `log_aktivitas` DROP INDEX `id_pengguna`',
                         'SELECT 1');

PREPARE drop_index_stmt FROM @drop_index_sql;
EXECUTE drop_index_stmt;
DEALLOCATE PREPARE drop_index_stmt;

-- Tambahkan indeks id_user jika belum ada di log_aktivitas
SET @check_index2 = (SELECT COUNT(*) FROM information_schema.statistics 
                     WHERE table_schema = DATABASE() 
                     AND table_name = 'log_aktivitas'
                     AND index_name = 'id_user');

SET @add_index_sql = IF(@check_index2 = 0, 
                        'ALTER TABLE `log_aktivitas` ADD KEY `id_user` (`id_user`)',
                        'SELECT 1');

PREPARE add_index_stmt FROM @add_index_sql;
EXECUTE add_index_stmt;
DEALLOCATE PREPARE add_index_stmt;

-- Untuk tabel penerimaan
SET @check_index3 = (SELECT COUNT(*) FROM information_schema.statistics 
                     WHERE table_schema = DATABASE() 
                     AND table_name = 'penerimaan'
                     AND index_name = 'id_pengguna');

-- Drop indeks id_pengguna jika ada di penerimaan
SET @drop_index_sql2 = IF(@check_index3 > 0, 
                          'ALTER TABLE `penerimaan` DROP INDEX `id_pengguna`',
                          'SELECT 1');

PREPARE drop_index_stmt2 FROM @drop_index_sql2;
EXECUTE drop_index_stmt2;
DEALLOCATE PREPARE drop_index_stmt2;

-- Tambahkan indeks id_user jika belum ada di penerimaan
SET @check_index4 = (SELECT COUNT(*) FROM information_schema.statistics 
                     WHERE table_schema = DATABASE() 
                     AND table_name = 'penerimaan'
                     AND index_name = 'id_user');

SET @add_index_sql2 = IF(@check_index4 = 0, 
                         'ALTER TABLE `penerimaan` ADD KEY `id_user` (`id_user`)',
                         'SELECT 1');

PREPARE add_index_stmt2 FROM @add_index_sql2;
EXECUTE add_index_stmt2;
DEALLOCATE PREPARE add_index_stmt2;

-- Untuk tabel stok_opname
SET @check_index5 = (SELECT COUNT(*) FROM information_schema.statistics 
                     WHERE table_schema = DATABASE() 
                     AND table_name = 'stok_opname'
                     AND index_name = 'id_pengguna');

-- Drop indeks id_pengguna jika ada di stok_opname
SET @drop_index_sql3 = IF(@check_index5 > 0, 
                          'ALTER TABLE `stok_opname` DROP INDEX `id_pengguna`',
                          'SELECT 1');

PREPARE drop_index_stmt3 FROM @drop_index_sql3;
EXECUTE drop_index_stmt3;
DEALLOCATE PREPARE drop_index_stmt3;

-- Tambahkan indeks id_user jika belum ada di stok_opname
SET @check_index6 = (SELECT COUNT(*) FROM information_schema.statistics 
                     WHERE table_schema = DATABASE() 
                     AND table_name = 'stok_opname'
                     AND index_name = 'id_user');

SET @add_index_sql3 = IF(@check_index6 = 0, 
                         'ALTER TABLE `stok_opname` ADD KEY `id_user` (`id_user`)',
                         'SELECT 1');

PREPARE add_index_stmt3 FROM @add_index_sql3;
EXECUTE add_index_stmt3;
DEALLOCATE PREPARE add_index_stmt3;

-- Enable foreign key checks again
SET FOREIGN_KEY_CHECKS=1;

-- Verify changes
-- SELECT * FROM information_schema.statistics WHERE table_schema = DATABASE() AND index_name = 'id_pengguna';
-- SELECT * FROM information_schema.statistics WHERE table_schema = DATABASE() AND index_name = 'id_user'; 