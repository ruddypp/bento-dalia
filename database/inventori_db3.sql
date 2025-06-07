-- Fixed SQL file for Hostinger deployment
-- Removed DEFINER clauses and cleared data except users table

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Drop existing tables if they exist
DROP TABLE IF EXISTS `v_bahan_baku_report`;
DROP TABLE IF EXISTS `penjualan_bahan`;
DROP TABLE IF EXISTS `penjualan_detail`;
DROP TABLE IF EXISTS `penjualan`;
DROP TABLE IF EXISTS `detail_terima`;
DROP TABLE IF EXISTS `detail_keluar`;
DROP TABLE IF EXISTS `detail_pesanan`;
DROP TABLE IF EXISTS `pesanan_detail`;
DROP TABLE IF EXISTS `pesanan_barang`;
DROP TABLE IF EXISTS `laporan_masuk_detail`;
DROP TABLE IF EXISTS `laporan_keluar_detail`;
DROP TABLE IF EXISTS `laporan_penjualan`;
DROP TABLE IF EXISTS `laporan_masuk`;
DROP TABLE IF EXISTS `laporan_keluar`;
DROP TABLE IF EXISTS `retur_barang`;
DROP TABLE IF EXISTS `lost_barang`;
DROP TABLE IF EXISTS `stok_opname`;
DROP TABLE IF EXISTS `barang_masuk`;
DROP TABLE IF EXISTS `barang_keluar`;
DROP TABLE IF EXISTS `barang_supplier`;
DROP TABLE IF EXISTS `bahan_baku`;
DROP TABLE IF EXISTS `log_aktivitas`;
DROP TABLE IF EXISTS `menu`;
DROP TABLE IF EXISTS `penerimaan`;
DROP TABLE IF EXISTS `pengeluaran`;
DROP TABLE IF EXISTS `barang`;
DROP TABLE IF EXISTS `supplier`;
DROP TABLE IF EXISTS `data_toko`;
DROP TABLE IF EXISTS `aktor`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;

-- Create tables

CREATE TABLE `roles` (
  `id_role` int(11) NOT NULL,
  `nama_role` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `roles` (`id_role`, `nama_role`) VALUES
(1, 'admin'),
(2, 'purchasing'),
(3, 'kasir'),
(4, 'headproduksi'),
(5, 'crew');

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `jenis_kelamin` enum('Laki - Laki','Perempuan') DEFAULT NULL,
  `alamat_user` varchar(255) DEFAULT NULL,
  `no_tlp_user` int(15) DEFAULT NULL,
  `tanggal_daftar` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id_user`, `username`, `password`, `role_id`, `nama_lengkap`, `jenis_kelamin`, `alamat_user`, `no_tlp_user`, `tanggal_daftar`) VALUES
(22, 'admin', 'admin', 1, 'Admin', 'Laki - Laki', 'test', 123, '2025-05-22 18:48:41'),
(24, 'kasir', 'kasir', 3, 'Kasir', 'Perempuan', 'testalamat', 32221, '2025-05-23 04:11:58'),
(25, 'headproduksi', 'headproduksi', 4, 'headproduksi', 'Laki - Laki', 'alamattest', 563442, '2025-05-23 04:12:51'),
(26, 'purchasing', 'purchasing', 2, 'Staff Purchasing', 'Perempuan', 'alamat 312', 83914, '2025-05-23 04:13:22'),
(27, 'crew', 'crew', 5, 'crew', 'Laki - Laki', NULL, NULL, '2025-05-23 04:14:12');

CREATE TABLE `aktor` (
  `id_aktor` int(11) NOT NULL,
  `nama_aktor` enum('administrator','forecasting','head_bar','head_kitchen','manajer') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `supplier` (
  `id_supplier` int(11) NOT NULL,
  `nama_supplier` varchar(100) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `kontak` varchar(50) DEFAULT NULL,
  `bahan_baku` text DEFAULT NULL,
  `satuan` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `barang` (
  `id_barang` int(11) NOT NULL,
  `nama_barang` varchar(100) DEFAULT NULL,
  `satuan` varchar(50) DEFAULT NULL,
  `jenis` varchar(50) DEFAULT NULL,
  `stok` int(11) DEFAULT 0,
  `stok_minimum` int(11) DEFAULT NULL,
  `harga` decimal(10,2) DEFAULT 0.00,
  `lokasi` enum('kitchen','bar') DEFAULT NULL,
  `periode` enum('1','2','3','4') DEFAULT NULL,
  `id_supplier` int(11) DEFAULT NULL,
  `periode_1` decimal(20,2) DEFAULT NULL,
  `periode_2` decimal(20,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `bahan_baku` (
  `id_bahan_baku` int(11) NOT NULL,
  `id_barang` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `periode` int(11) NOT NULL COMMENT '1, 2, 3, or 4',
  `harga_satuan` decimal(20,2) NOT NULL,
  `total` decimal(20,2) NOT NULL,
  `lokasi` varchar(100) DEFAULT NULL,
  `tanggal_input` datetime NOT NULL DEFAULT current_timestamp(),
  `id_user` int(11) DEFAULT NULL,
  `status` enum('pending','approved','retur','dibatalkan') NOT NULL DEFAULT 'pending',
  `jumlah_retur` int(11) DEFAULT 0,
  `jumlah_masuk` int(11) DEFAULT 0,
  `catatan_retur` text DEFAULT NULL,
  `id_pesanan` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `barang_keluar` (
  `id_keluar` int(11) NOT NULL,
  `id_barang` int(11) NOT NULL,
  `tanggal_keluar` datetime NOT NULL DEFAULT current_timestamp(),
  `id_user` int(11) DEFAULT NULL,
  `qty_keluar` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `barang_masuk` (
  `id_masuk` int(11) NOT NULL,
  `id_barang` int(11) NOT NULL,
  `tanggal_masuk` datetime NOT NULL DEFAULT current_timestamp(),
  `id_user` int(11) DEFAULT NULL,
  `qty_masuk` int(11) NOT NULL,
  `id_supplier` int(11) DEFAULT NULL,
  `lokasi` varchar(100) DEFAULT NULL,
  `harga_satuan` decimal(20,2) DEFAULT 0.00,
  `periode` int(11) DEFAULT 1 COMMENT '1, 2, 3, or 4'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `barang_supplier` (
  `id` int(11) NOT NULL,
  `id_barang` int(11) NOT NULL,
  `id_supplier` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `data_toko` (
  `id_toko` int(11) NOT NULL,
  `nama_toko` varchar(100) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `kontak` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(100) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `data_toko` (`id_toko`, `nama_toko`, `alamat`, `kontak`, `email`, `website`, `deskripsi`, `logo`) VALUES
(1, 'Toko Inventori', 'Jl. Contoh No. 123, Jakarta', '021-1234567', NULL, NULL, NULL, NULL);

CREATE TABLE `detail_keluar` (
  `id_detail_keluar` int(11) NOT NULL,
  `id_pengeluaran` int(11) DEFAULT NULL,
  `id_barang` int(11) DEFAULT NULL,
  `jumlah_keluar` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `detail_pesanan` (
  `id_detail_pesanan` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `id_item` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1,
  `harga_saat_pesan` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `detail_terima` (
  `id_detail_terima` int(11) NOT NULL,
  `id_penerimaan` int(11) DEFAULT NULL,
  `id_barang` int(11) DEFAULT NULL,
  `jumlah_diterima` int(11) DEFAULT NULL,
  `kualitas` text DEFAULT NULL,
  `tanggal_expired` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `laporan_keluar` (
  `id_laporan_keluar` int(11) NOT NULL,
  `tanggal_laporan` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `laporan_keluar_detail` (
  `id_detail_keluar` int(11) NOT NULL,
  `id_laporan` int(11) NOT NULL,
  `id_keluar` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `laporan_masuk` (
  `id_laporan_masuk` int(11) NOT NULL,
  `tanggal_laporan` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `status` enum('pending','approved') NOT NULL DEFAULT 'pending',
  `periode` int(11) DEFAULT 1 COMMENT '1, 2, 3, or 4'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `laporan_masuk_detail` (
  `id_detail` int(11) NOT NULL,
  `id_laporan` int(11) DEFAULT NULL,
  `id_masuk` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `laporan_penjualan` (
  `id_laporan` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `total_penjualan` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_modal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_keuntungan` decimal(10,2) NOT NULL DEFAULT 0.00,
  `jumlah_transaksi` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `id_user` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `log_aktivitas` (
  `id_log` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `waktu` datetime DEFAULT NULL,
  `aktivitas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `lost_barang` (
  `id_lost` int(11) NOT NULL,
  `id_barang` int(11) NOT NULL,
  `jumlah` decimal(10,2) NOT NULL,
  `alasan` text NOT NULL,
  `foto_bukti` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `dibuat_oleh` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `menu` (
  `id_menu` int(11) NOT NULL,
  `nama_menu` varchar(100) NOT NULL,
  `kategori` enum('makanan','minuman') NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `bahan` text NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` varchar(20) DEFAULT 'available',
  `harga_modal` decimal(10,2) DEFAULT 0.00,
  `keuntungan` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `penerimaan` (
  `id_penerimaan` int(11) NOT NULL,
  `tanggal_terima` date DEFAULT NULL,
  `id_supplier` int(11) DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `status_penerimaan` enum('diterima','diretur') DEFAULT 'diterima'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `pengeluaran` (
  `id_pengeluaran` int(11) NOT NULL,
  `tanggal_keluar` date DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `keperluan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `penjualan` (
  `id_penjualan` int(11) NOT NULL,
  `no_invoice` varchar(20) NOT NULL,
  `tanggal_penjualan` datetime NOT NULL DEFAULT current_timestamp(),
  `total_harga` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_modal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `keuntungan` decimal(10,2) NOT NULL DEFAULT 0.00,
  `id_user` int(11) DEFAULT NULL,
  `nama_pelanggan` varchar(100) DEFAULT NULL,
  `status_pembayaran` enum('lunas','belum_lunas') NOT NULL DEFAULT 'lunas',
  `catatan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `penjualan_bahan` (
  `id_penjualan_bahan` int(11) NOT NULL,
  `id_penjualan_detail` int(11) NOT NULL,
  `id_barang` int(11) NOT NULL,
  `jumlah` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `penjualan_detail` (
  `id_penjualan_detail` int(11) NOT NULL,
  `id_penjualan` int(11) NOT NULL,
  `id_menu` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1,
  `harga_satuan` decimal(10,2) NOT NULL,
  `harga_modal_satuan` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `subtotal_modal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `pesanan_barang` (
  `id_pesanan` int(11) NOT NULL,
  `id_supplier` int(11) NOT NULL,
  `tanggal_pesan` date NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `status` enum('pending','diproses','selesai','dibatalkan','approved') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `pesanan_detail` (
  `id_detail` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `id_barang` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `periode` int(11) NOT NULL COMMENT '1, 2, 3, or 4',
  `harga_satuan` decimal(20,2) NOT NULL,
  `total` decimal(20,2) NOT NULL,
  `lokasi` enum('kitchen','bar') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `retur_barang` (
  `id_retur` int(11) NOT NULL,
  `id_barang` int(11) NOT NULL,
  `qty_retur` int(11) NOT NULL,
  `tanggal_retur` datetime NOT NULL DEFAULT current_timestamp(),
  `alasan_retur` text DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `harga_satuan` decimal(20,2) NOT NULL,
  `total` decimal(20,2) NOT NULL,
  `periode` int(11) NOT NULL,
  `id_pesanan` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `stok_opname` (
  `id_opname` int(11) NOT NULL,
  `id_barang` int(11) DEFAULT NULL,
  `tanggal_opname` date DEFAULT NULL,
  `stok_fisik` int(11) DEFAULT NULL,
  `stok_sistem` int(11) DEFAULT NULL,
  `selisih` int(11) DEFAULT NULL,
  `jenis` enum('opname','kerugian') NOT NULL DEFAULT 'opname',
  `id_user` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create VIEW without DEFINER clause (compatible with shared hosting)
CREATE VIEW `v_bahan_baku_report` AS 
SELECT 
    `bb`.`id_bahan_baku` AS `id_bahan_baku`, 
    `b`.`nama_barang` AS `nama_barang`, 
    `bb`.`qty` AS `jumlah_total`, 
    coalesce(`bb`.`jumlah_retur`,0) AS `jumlah_retur`, 
    coalesce(`bb`.`jumlah_masuk`,0) AS `jumlah_masuk`, 
    `bb`.`status` AS `status`, 
    `bb`.`tanggal_input` AS `tanggal_input`, 
    `bb`.`catatan_retur` AS `catatan_retur`, 
    `u`.`nama_lengkap` AS `nama_pengguna` 
FROM ((`bahan_baku` `bb` 
    join `barang` `b` on(`bb`.`id_barang` = `b`.`id_barang`)) 
    left join `users` `u` on(`bb`.`id_user` = `u`.`id_user`));

-- Add all indexes and constraints

ALTER TABLE `aktor` ADD PRIMARY KEY (`id_aktor`);
ALTER TABLE `bahan_baku` ADD PRIMARY KEY (`id_bahan_baku`), ADD KEY `id_barang` (`id_barang`), ADD KEY `id_user` (`id_user`), ADD KEY `idx_bahan_baku_periode` (`periode`), ADD KEY `idx_bahan_baku_status` (`status`), ADD KEY `idx_bahan_baku_id_pesanan` (`id_pesanan`);
ALTER TABLE `barang` ADD PRIMARY KEY (`id_barang`), ADD KEY `fk_barang_supplier` (`id_supplier`);
ALTER TABLE `barang_keluar` ADD PRIMARY KEY (`id_keluar`), ADD KEY `id_barang` (`id_barang`), ADD KEY `id_user` (`id_user`);
ALTER TABLE `barang_masuk` ADD PRIMARY KEY (`id_masuk`), ADD KEY `id_barang` (`id_barang`), ADD KEY `id_supplier` (`id_supplier`), ADD KEY `barang_masuk_ibfk_3` (`id_user`);
ALTER TABLE `barang_supplier` ADD PRIMARY KEY (`id`), ADD KEY `id_barang` (`id_barang`), ADD KEY `id_supplier` (`id_supplier`);
ALTER TABLE `data_toko` ADD PRIMARY KEY (`id_toko`);
ALTER TABLE `detail_keluar` ADD PRIMARY KEY (`id_detail_keluar`), ADD KEY `id_pengeluaran` (`id_pengeluaran`), ADD KEY `id_barang` (`id_barang`);
ALTER TABLE `detail_pesanan` ADD PRIMARY KEY (`id_detail_pesanan`), ADD KEY `id_pesanan` (`id_pesanan`), ADD KEY `id_item` (`id_item`);
ALTER TABLE `detail_terima` ADD PRIMARY KEY (`id_detail_terima`), ADD KEY `id_penerimaan` (`id_penerimaan`), ADD KEY `id_barang` (`id_barang`);
ALTER TABLE `laporan_keluar` ADD PRIMARY KEY (`id_laporan_keluar`);
ALTER TABLE `laporan_keluar_detail` ADD PRIMARY KEY (`id_detail_keluar`), ADD KEY `id_laporan` (`id_laporan`), ADD KEY `id_keluar` (`id_keluar`);
ALTER TABLE `laporan_masuk` ADD PRIMARY KEY (`id_laporan_masuk`), ADD KEY `created_by` (`created_by`), ADD KEY `idx_laporan_masuk_tanggal_periode` (`tanggal_laporan`,`periode`);
ALTER TABLE `laporan_masuk_detail` ADD PRIMARY KEY (`id_detail`), ADD KEY `id_laporan` (`id_laporan`), ADD KEY `id_masuk` (`id_masuk`);
ALTER TABLE `laporan_penjualan` ADD PRIMARY KEY (`id_laporan`), ADD UNIQUE KEY `tanggal` (`tanggal`), ADD KEY `id_user` (`id_user`);
ALTER TABLE `log_aktivitas` ADD PRIMARY KEY (`id_log`), ADD KEY `id_user` (`id_user`);
ALTER TABLE `lost_barang` ADD PRIMARY KEY (`id_lost`), ADD KEY `id_barang` (`id_barang`), ADD KEY `dibuat_oleh` (`dibuat_oleh`);
ALTER TABLE `menu` ADD PRIMARY KEY (`id_menu`);
ALTER TABLE `penerimaan` ADD PRIMARY KEY (`id_penerimaan`), ADD KEY `id_supplier` (`id_supplier`), ADD KEY `id_user` (`id_user`);
ALTER TABLE `pengeluaran` ADD PRIMARY KEY (`id_pengeluaran`), ADD KEY `id_user` (`id_user`);
ALTER TABLE `penjualan` ADD PRIMARY KEY (`id_penjualan`), ADD KEY `id_user` (`id_user`);
ALTER TABLE `penjualan_bahan` ADD PRIMARY KEY (`id_penjualan_bahan`), ADD KEY `id_penjualan_detail` (`id_penjualan_detail`), ADD KEY `id_barang` (`id_barang`);
ALTER TABLE `penjualan_detail` ADD PRIMARY KEY (`id_penjualan_detail`), ADD KEY `id_penjualan` (`id_penjualan`), ADD KEY `id_menu` (`id_menu`);
ALTER TABLE `pesanan_barang` ADD PRIMARY KEY (`id_pesanan`), ADD KEY `id_supplier` (`id_supplier`), ADD KEY `id_user` (`id_user`);
ALTER TABLE `pesanan_detail` ADD PRIMARY KEY (`id_detail`), ADD KEY `id_pesanan` (`id_pesanan`), ADD KEY `id_barang` (`id_barang`);
ALTER TABLE `retur_barang` ADD PRIMARY KEY (`id_retur`), ADD KEY `id_barang` (`id_barang`), ADD KEY `id_user` (`id_user`);
ALTER TABLE `roles` ADD PRIMARY KEY (`id_role`);
ALTER TABLE `stok_opname` ADD PRIMARY KEY (`id_opname`), ADD KEY `id_barang` (`id_barang`), ADD KEY `id_user` (`id_user`);
ALTER TABLE `supplier` ADD PRIMARY KEY (`id_supplier`);
ALTER TABLE `users` ADD PRIMARY KEY (`id_user`), ADD UNIQUE KEY `username` (`username`), ADD KEY `fk_user_role` (`role_id`);

-- AUTO_INCREMENT settings
ALTER TABLE `aktor` MODIFY `id_aktor` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `bahan_baku` MODIFY `id_bahan_baku` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `barang` MODIFY `id_barang` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `barang_keluar` MODIFY `id_keluar` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `barang_masuk` MODIFY `id_masuk` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `barang_supplier` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `data_toko` MODIFY `id_toko` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `detail_keluar` MODIFY `id_detail_keluar` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `detail_terima` MODIFY `id_detail_terima` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `laporan_keluar` MODIFY `id_laporan_keluar` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `laporan_masuk` MODIFY `id_laporan_masuk` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `laporan_masuk_detail` MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `laporan_penjualan` MODIFY `id_laporan` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `log_aktivitas` MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `lost_barang` MODIFY `id_lost` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `menu` MODIFY `id_menu` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `penerimaan` MODIFY `id_penerimaan` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `pengeluaran` MODIFY `id_pengeluaran` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `penjualan` MODIFY `id_penjualan` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `penjualan_bahan` MODIFY `id_penjualan_bahan` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `penjualan_detail` MODIFY `id_penjualan_detail` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `pesanan_barang` MODIFY `id_pesanan` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `pesanan_detail` MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `retur_barang` MODIFY `id_retur` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `stok_opname` MODIFY `id_opname` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `supplier` MODIFY `id_supplier` int(11) NOT NULL AUTO_INCREMENT;

-- Foreign key constraints
ALTER TABLE `bahan_baku` ADD CONSTRAINT `bahan_baku_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`) ON DELETE CASCADE;
ALTER TABLE `barang` ADD CONSTRAINT `fk_barang_supplier` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`) ON DELETE SET NULL;
ALTER TABLE `barang_keluar` ADD CONSTRAINT `barang_keluar_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`);
ALTER TABLE `barang_masuk` ADD CONSTRAINT `barang_masuk_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`), ADD CONSTRAINT `barang_masuk_ibfk_2` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`);
ALTER TABLE `barang_supplier` ADD CONSTRAINT `barang_supplier_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`), ADD CONSTRAINT `barang_supplier_ibfk_2` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`);
ALTER TABLE `detail_keluar` ADD CONSTRAINT `detail_keluar_ibfk_1` FOREIGN KEY (`id_pengeluaran`) REFERENCES `pengeluaran` (`id_pengeluaran`), ADD CONSTRAINT `detail_keluar_ibfk_2` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`);
ALTER TABLE `detail_terima` ADD CONSTRAINT `detail_terima_ibfk_1` FOREIGN KEY (`id_penerimaan`) REFERENCES `penerimaan` (`id_penerimaan`), ADD CONSTRAINT `detail_terima_ibfk_2` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`);
ALTER TABLE `laporan_masuk_detail` ADD CONSTRAINT `laporan_masuk_detail_ibfk_1` FOREIGN KEY (`id_laporan`) REFERENCES `laporan_masuk` (`id_laporan_masuk`) ON DELETE CASCADE, ADD CONSTRAINT `laporan_masuk_detail_ibfk_2` FOREIGN KEY (`id_masuk`) REFERENCES `barang_masuk` (`id_masuk`) ON DELETE CASCADE;
ALTER TABLE `laporan_penjualan` ADD CONSTRAINT `laporan_penjualan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL;
ALTER TABLE `lost_barang` ADD CONSTRAINT `lost_barang_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`) ON DELETE CASCADE, ADD CONSTRAINT `lost_barang_ibfk_2` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id_user`) ON DELETE SET NULL;
ALTER TABLE `penerimaan` ADD CONSTRAINT `penerimaan_ibfk_1` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`);
ALTER TABLE `penjualan` ADD CONSTRAINT `penjualan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL;
ALTER TABLE `penjualan_bahan` ADD CONSTRAINT `penjualan_bahan_ibfk_1` FOREIGN KEY (`id_penjualan_detail`) REFERENCES `penjualan_detail` (`id_penjualan_detail`) ON DELETE CASCADE, ADD CONSTRAINT `penjualan_bahan_ibfk_2` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`) ON DELETE CASCADE;
ALTER TABLE `penjualan_detail` ADD CONSTRAINT `penjualan_detail_ibfk_1` FOREIGN KEY (`id_penjualan`) REFERENCES `penjualan` (`id_penjualan`) ON DELETE CASCADE, ADD CONSTRAINT `penjualan_detail_ibfk_2` FOREIGN KEY (`id_menu`) REFERENCES `menu` (`id_menu`) ON DELETE CASCADE;
ALTER TABLE `pesanan_barang` ADD CONSTRAINT `pesanan_barang_ibfk_1` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`) ON DELETE CASCADE, ADD CONSTRAINT `pesanan_barang_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL;
ALTER TABLE `pesanan_detail` ADD CONSTRAINT `pesanan_detail_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan_barang` (`id_pesanan`) ON DELETE CASCADE, ADD CONSTRAINT `pesanan_detail_ibfk_2` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`) ON DELETE CASCADE;
ALTER TABLE `retur_barang` ADD CONSTRAINT `retur_barang_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`), ADD CONSTRAINT `retur_barang_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`);
ALTER TABLE `stok_opname` ADD CONSTRAINT `stok_opname_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`);
ALTER TABLE `users` ADD CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id_role`) ON DELETE SET NULL ON UPDATE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;