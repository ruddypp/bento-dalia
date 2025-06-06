-- Table structure for pesanan_barang (order header)
CREATE TABLE IF NOT EXISTS `pesanan_barang` (
  `id_pesanan` int(11) NOT NULL AUTO_INCREMENT,
  `id_supplier` int(11) NOT NULL,
  `tanggal_pesan` date NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `status` enum('pending','diproses','selesai','dibatalkan') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_pesanan`),
  KEY `id_supplier` (`id_supplier`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `pesanan_barang_ibfk_1` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`) ON DELETE CASCADE,
  CONSTRAINT `pesanan_barang_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for pesanan_detail (order details)
CREATE TABLE IF NOT EXISTS `pesanan_detail` (
  `id_detail` int(11) NOT NULL AUTO_INCREMENT,
  `id_pesanan` int(11) NOT NULL,
  `id_barang` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `periode` int(11) NOT NULL COMMENT '1, 2, 3, or 4',
  `harga_satuan` decimal(20,2) NOT NULL,
  `total` decimal(20,2) NOT NULL,
  `lokasi` enum('kitchen','bar') NOT NULL,
  PRIMARY KEY (`id_detail`),
  KEY `id_pesanan` (`id_pesanan`),
  KEY `id_barang` (`id_barang`),
  CONSTRAINT `pesanan_detail_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan_barang` (`id_pesanan`) ON DELETE CASCADE,
  CONSTRAINT `pesanan_detail_ibfk_2` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; 