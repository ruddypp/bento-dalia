-- SQL file to create lost_barang table

-- Create the table if it doesn't exist
CREATE TABLE IF NOT EXISTS `lost_barang` (
  `id_lost` int(11) NOT NULL AUTO_INCREMENT,
  `id_barang` int(11) NOT NULL,
  `jumlah` decimal(10,2) NOT NULL,
  `alasan` text NOT NULL,
  `foto_bukti` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `dibuat_oleh` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_lost`),
  KEY `id_barang` (`id_barang`),
  KEY `dibuat_oleh` (`dibuat_oleh`),
  CONSTRAINT `lost_barang_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`) ON DELETE CASCADE,
  CONSTRAINT `lost_barang_ibfk_2` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id_user`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add this to stok_opname table if needed to track lost items
ALTER TABLE `stok_opname` ADD COLUMN IF NOT EXISTS `jenis` enum('opname','kerugian') NOT NULL DEFAULT 'opname' AFTER `selisih`; 