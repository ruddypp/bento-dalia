-- Table for Laporan Masuk (Inventory Intake Reports)
CREATE TABLE IF NOT EXISTS `laporan_masuk` (
  `id_laporan_masuk` int(11) NOT NULL AUTO_INCREMENT,
  `tanggal_laporan` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_laporan_masuk`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `laporan_masuk_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `pengguna` (`id_pengguna`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table for Laporan Masuk Detail (Inventory Intake Report Details)
CREATE TABLE IF NOT EXISTS `laporan_masuk_detail` (
  `id_detail` int(11) NOT NULL AUTO_INCREMENT,
  `id_laporan_masuk` int(11) DEFAULT NULL,
  `nama_barang` varchar(100) DEFAULT NULL,
  `jumlah` decimal(10,2) DEFAULT NULL,
  `satuan` varchar(50) DEFAULT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_detail`),
  KEY `id_laporan_masuk` (`id_laporan_masuk`),
  CONSTRAINT `laporan_masuk_detail_ibfk_1` FOREIGN KEY (`id_laporan_masuk`) REFERENCES `laporan_masuk` (`id_laporan_masuk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; 