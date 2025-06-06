-- SQL script to update log_aktivitas table structure
-- Change id_pengguna to id_user

-- First, make a backup of the existing data
CREATE TABLE IF NOT EXISTS log_aktivitas_backup AS SELECT * FROM log_aktivitas;

-- Drop the existing table
DROP TABLE IF EXISTS log_aktivitas;

-- Create the table with the correct structure
CREATE TABLE `log_aktivitas` (
  `id_log` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) DEFAULT NULL,
  `waktu` datetime DEFAULT NULL,
  `aktivitas` text DEFAULT NULL,
  PRIMARY KEY (`id_log`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Restore the data from the backup, mapping id_user from backup to id_user in new table
-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS=0;

INSERT INTO log_aktivitas (id_log, id_user, waktu, aktivitas)
SELECT id_log, id_user, waktu, aktivitas FROM log_aktivitas_backup;

-- Enable foreign key checks again
SET FOREIGN_KEY_CHECKS=1;

-- Add the foreign key constraint after data is inserted
-- Comment this out if you still have issues
-- ALTER TABLE `log_aktivitas` 
-- ADD CONSTRAINT `log_aktivitas_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Drop the backup table when done
-- DROP TABLE log_aktivitas_backup;

-- Verify changes
-- SELECT * FROM information_schema.columns WHERE table_name = 'log_aktivitas' AND column_name = 'id_user'; 