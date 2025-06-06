-- SQL file to create tables for the sales system

-- Create or modify penjualan table (main sales transaction)
CREATE TABLE IF NOT EXISTS `penjualan` (
  `id_penjualan` int(11) NOT NULL AUTO_INCREMENT,
  `no_invoice` varchar(20) NOT NULL,
  `tanggal_penjualan` datetime NOT NULL DEFAULT current_timestamp(),
  `total_harga` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_modal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `keuntungan` decimal(10,2) NOT NULL DEFAULT 0.00,
  `id_user` int(11) DEFAULT NULL,
  `nama_pelanggan` varchar(100) DEFAULT NULL,
  `status_pembayaran` enum('lunas','belum_lunas') NOT NULL DEFAULT 'lunas',
  `catatan` text DEFAULT NULL,
  PRIMARY KEY (`id_penjualan`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `penjualan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create or modify penjualan_detail table (sales details/items)
CREATE TABLE IF NOT EXISTS `penjualan_detail` (
  `id_penjualan_detail` int(11) NOT NULL AUTO_INCREMENT,
  `id_penjualan` int(11) NOT NULL,
  `id_menu` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1,
  `harga_satuan` decimal(10,2) NOT NULL,
  `harga_modal_satuan` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `subtotal_modal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_penjualan_detail`),
  KEY `id_penjualan` (`id_penjualan`),
  KEY `id_menu` (`id_menu`),
  CONSTRAINT `penjualan_detail_ibfk_1` FOREIGN KEY (`id_penjualan`) REFERENCES `penjualan` (`id_penjualan`) ON DELETE CASCADE,
  CONSTRAINT `penjualan_detail_ibfk_2` FOREIGN KEY (`id_menu`) REFERENCES `menu` (`id_menu`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create or modify penjualan_bahan table (tracking ingredients used in sales)
CREATE TABLE IF NOT EXISTS `penjualan_bahan` (
  `id_penjualan_bahan` int(11) NOT NULL AUTO_INCREMENT,
  `id_penjualan_detail` int(11) NOT NULL,
  `id_barang` int(11) NOT NULL,
  `jumlah` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_penjualan_bahan`),
  KEY `id_penjualan_detail` (`id_penjualan_detail`),
  KEY `id_barang` (`id_barang`),
  CONSTRAINT `penjualan_bahan_ibfk_1` FOREIGN KEY (`id_penjualan_detail`) REFERENCES `penjualan_detail` (`id_penjualan_detail`) ON DELETE CASCADE,
  CONSTRAINT `penjualan_bahan_ibfk_2` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create or modify laporan_penjualan table (daily sales reports)
CREATE TABLE IF NOT EXISTS `laporan_penjualan` (
  `id_laporan` int(11) NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `total_penjualan` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_modal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_keuntungan` decimal(10,2) NOT NULL DEFAULT 0.00,
  `jumlah_transaksi` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `id_user` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_laporan`),
  UNIQUE KEY `tanggal` (`tanggal`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `laporan_penjualan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; 