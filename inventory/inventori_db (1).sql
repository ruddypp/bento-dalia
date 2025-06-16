-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 21, 2025 at 01:38 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inventori_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `aktor`
--

CREATE TABLE `aktor` (
  `id_aktor` int(11) NOT NULL,
  `nama_aktor` enum('administrator','forecasting','head_bar','head_kitchen','manajer') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `aktor`
--

INSERT INTO `aktor` (`id_aktor`, `nama_aktor`) VALUES
(1, 'administrator'),
(2, 'manajer'),
(3, 'forecasting'),
(4, 'head_bar'),
(5, 'head_kitchen'),
(6, 'administrator'),
(7, 'manajer'),
(8, 'forecasting'),
(9, 'head_bar'),
(10, 'head_kitchen'),
(11, 'administrator'),
(12, 'manajer'),
(13, 'forecasting'),
(14, 'head_bar'),
(15, 'head_kitchen');

-- --------------------------------------------------------

--
-- Table structure for table `barang`
--

CREATE TABLE `barang` (
  `id_barang` int(11) NOT NULL,
  `nama_barang` varchar(100) DEFAULT NULL,
  `satuan` varchar(50) DEFAULT NULL,
  `jenis` varchar(50) DEFAULT NULL,
  `stok` int(11) DEFAULT 0,
  `stok_minimum` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barang`
--

INSERT INTO `barang` (`id_barang`, `nama_barang`, `satuan`, `jenis`, `stok`, `stok_minimum`) VALUES
(1, 'susu', 'liter', 'minuman', 20, 10),
(2, 'kopi', 'pack', 'minuman', 12, 10),
(3, 'rudy', '1kg', 'kopi', 1, 10);

-- --------------------------------------------------------

--
-- Table structure for table `barang_keluar`
--

CREATE TABLE `barang_keluar` (
  `id_keluar` int(11) NOT NULL,
  `id_barang` int(11) NOT NULL,
  `tanggal_keluar` datetime NOT NULL DEFAULT current_timestamp(),
  `id_user` int(11) DEFAULT NULL,
  `qty_keluar` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `barang_masuk`
--

CREATE TABLE `barang_masuk` (
  `id_masuk` int(11) NOT NULL,
  `id_barang` int(11) NOT NULL,
  `tanggal_masuk` datetime NOT NULL DEFAULT current_timestamp(),
  `id_user` int(11) NOT NULL,
  `qty_masuk` int(11) NOT NULL,
  `id_supplier` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `data_toko`
--

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

--
-- Dumping data for table `data_toko`
--

INSERT INTO `data_toko` (`id_toko`, `nama_toko`, `alamat`, `kontak`, `email`, `website`, `deskripsi`, `logo`) VALUES
(1, 'Toko Inventori', 'Jl. Contoh No. 123, Jakarta', '021-1234567', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `detail_keluar`
--

CREATE TABLE `detail_keluar` (
  `id_detail_keluar` int(11) NOT NULL,
  `id_pengeluaran` int(11) DEFAULT NULL,
  `id_barang` int(11) DEFAULT NULL,
  `jumlah_keluar` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `detail_laporan_keluar`
--

CREATE TABLE `detail_laporan_keluar` (
  `id_detail_keluar` int(11) NOT NULL,
  `id_laporan` int(11) DEFAULT NULL,
  `id_keluar` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `detail_terima`
--

CREATE TABLE `detail_terima` (
  `id_detail_terima` int(11) NOT NULL,
  `id_penerimaan` int(11) DEFAULT NULL,
  `id_barang` int(11) DEFAULT NULL,
  `jumlah_diterima` int(11) DEFAULT NULL,
  `kualitas` text DEFAULT NULL,
  `tanggal_expired` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `laporan_keluar`
--

CREATE TABLE `laporan_keluar` (
  `id_laporan_keluar` int(11) NOT NULL,
  `tanggal_laporan` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `laporan_masuk`
--

CREATE TABLE `laporan_masuk` (
  `id_laporan_masuk` int(11) NOT NULL,
  `tanggal_laporan` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `laporan_masuk`
--

INSERT INTO `laporan_masuk` (`id_laporan_masuk`, `tanggal_laporan`, `created_by`, `created_at`) VALUES
(6, '2025-05-21', 1, '2025-05-21 17:00:15'),
(7, '2025-05-21', 1, '2025-05-21 17:00:36'),
(8, '2025-05-21', 1, '2025-05-21 17:09:25'),
(9, '2025-05-21', 1, '2025-05-21 17:31:11');

-- --------------------------------------------------------

--
-- Table structure for table `laporan_masuk_detail`
--

CREATE TABLE `laporan_masuk_detail` (
  `id_detail_masuk` int(11) NOT NULL,
  `id_laporan` int(11) DEFAULT NULL,
  `id_masuk` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id_log` int(11) NOT NULL,
  `id_pengguna` int(11) DEFAULT NULL,
  `waktu` datetime DEFAULT NULL,
  `aktivitas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_aktivitas`
--

INSERT INTO `log_aktivitas` (`id_log`, `id_pengguna`, `waktu`, `aktivitas`) VALUES
(1, 1, '2025-05-02 01:44:59', 'Login ke sistem'),
(2, 1, '2025-05-16 09:31:13', 'Login ke sistem'),
(3, 1, '2025-05-16 09:41:50', 'Menambahkan barang baru: susu'),
(4, 1, '2025-05-16 09:42:27', 'Menambahkan barang baru: kopi'),
(5, 1, '2025-05-16 09:44:53', 'Menambahkan pengguna baru: purchase'),
(6, 1, '2025-05-16 09:46:02', 'Menambahkan pengguna baru: headkitchen'),
(7, 1, '2025-05-19 22:11:00', 'Login ke sistem'),
(8, 1, '2025-05-19 22:17:07', 'Logout dari sistem'),
(9, 1, '2025-05-19 23:19:28', 'Login ke sistem'),
(10, 1, '2025-05-19 23:23:54', 'Logout dari sistem'),
(11, 1, '2025-05-19 23:23:57', 'Login ke sistem'),
(12, 1, '2025-05-19 23:31:10', 'Logout dari sistem'),
(13, 1, '2025-05-19 23:32:57', 'Login ke sistem'),
(14, 1, '2025-05-19 23:33:06', 'Logout dari sistem'),
(15, 1, '2025-05-20 00:25:40', 'Login ke sistem'),
(16, 1, '2025-05-20 00:45:04', 'Logout dari sistem'),
(17, 1, '2025-05-20 00:45:07', 'Login ke sistem'),
(18, 1, '2025-05-20 10:15:23', 'Login ke sistem'),
(19, 1, '2025-05-20 10:22:56', 'Logout dari sistem'),
(20, 1, '2025-05-20 10:24:56', 'Login ke sistem'),
(21, 1, '2025-05-20 10:26:52', 'Logout dari sistem'),
(22, 1, '2025-05-20 10:29:39', 'Login ke sistem'),
(23, 1, '2025-05-20 12:29:17', 'Login ke sistem'),
(24, 1, '2025-05-20 16:29:45', 'Logout dari sistem'),
(25, 1, '2025-05-20 16:29:56', 'Login ke sistem'),
(26, 1, '2025-05-21 13:35:04', 'Logout dari sistem'),
(27, 1, '2025-05-21 13:35:20', 'Login ke sistem'),
(28, 1, '2025-05-21 14:40:00', 'Menambahkan barang baru: rudy'),
(29, 1, '2025-05-21 17:00:16', 'Membuat laporan barang masuk baru #6'),
(30, 1, '2025-05-21 17:00:36', 'Membuat laporan barang masuk baru #7'),
(31, 1, '2025-05-21 17:09:25', 'Membuat laporan barang masuk baru #8'),
(32, 1, '2025-05-21 17:31:11', 'Membuat laporan barang masuk baru #9');

-- --------------------------------------------------------

--
-- Table structure for table `penerimaan`
--

CREATE TABLE `penerimaan` (
  `id_penerimaan` int(11) NOT NULL,
  `tanggal_terima` date DEFAULT NULL,
  `id_supplier` int(11) DEFAULT NULL,
  `id_pengguna` int(11) DEFAULT NULL,
  `status_penerimaan` enum('diterima','diretur') DEFAULT 'diterima'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengeluaran`
--

CREATE TABLE `pengeluaran` (
  `id_pengeluaran` int(11) NOT NULL,
  `tanggal_keluar` date DEFAULT NULL,
  `id_pengguna` int(11) DEFAULT NULL,
  `keperluan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengguna`
--

CREATE TABLE `pengguna` (
  `id_pengguna` int(11) NOT NULL,
  `nama_pengguna` varchar(100) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `id_aktor` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengguna`
--

INSERT INTO `pengguna` (`id_pengguna`, `nama_pengguna`, `username`, `password`, `email`, `id_aktor`) VALUES
(1, 'Administrator', 'admin', '$2y$10$DwySA7qsctlLKnDwOhN1wexN0XTTriZkPJ.vWAd/eorLlMtfza1Yi', 'admin@example.com', 1),
(2, 'purchasing', 'purchase', '$2y$10$P6KzBy19Qd3wa8KEG.i4KuD1Q5vN03OseEwljaz2/2b1gtRpJJVIW', 'purchasing@gmail.com', 13),
(3, 'kitchen', 'headkitchen', '$2y$10$XkE1/yRjQh7/4AZ2AD7qyeKfK/bwIAv4DrNuWoLMKYa9zGZONnn5K', 'hkitchen@gmail.com', 5);

-- --------------------------------------------------------

--
-- Table structure for table `retur_barang`
--

CREATE TABLE `retur_barang` (
  `id_retur` int(11) NOT NULL,
  `id_penerimaan` int(11) DEFAULT NULL,
  `tanggal_retur` date DEFAULT NULL,
  `alasan_retur` text DEFAULT NULL,
  `id_pengguna` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stok_opname`
--

CREATE TABLE `stok_opname` (
  `id_opname` int(11) NOT NULL,
  `id_barang` int(11) DEFAULT NULL,
  `tanggal_opname` date DEFAULT NULL,
  `stok_fisik` int(11) DEFAULT NULL,
  `stok_sistem` int(11) DEFAULT NULL,
  `selisih` int(11) DEFAULT NULL,
  `id_pengguna` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

CREATE TABLE `supplier` (
  `id_supplier` int(11) NOT NULL,
  `nama_supplier` varchar(100) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `kontak` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `aktor`
--
ALTER TABLE `aktor`
  ADD PRIMARY KEY (`id_aktor`);

--
-- Indexes for table `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`id_barang`);

--
-- Indexes for table `barang_keluar`
--
ALTER TABLE `barang_keluar`
  ADD PRIMARY KEY (`id_keluar`),
  ADD KEY `id_barang` (`id_barang`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `barang_masuk`
--
ALTER TABLE `barang_masuk`
  ADD PRIMARY KEY (`id_masuk`),
  ADD KEY `id_barang` (`id_barang`),
  ADD KEY `id_supplier` (`id_supplier`),
  ADD KEY `barang_masuk_ibfk_3` (`id_user`);

--
-- Indexes for table `data_toko`
--
ALTER TABLE `data_toko`
  ADD PRIMARY KEY (`id_toko`);

--
-- Indexes for table `detail_keluar`
--
ALTER TABLE `detail_keluar`
  ADD PRIMARY KEY (`id_detail_keluar`),
  ADD KEY `id_pengeluaran` (`id_pengeluaran`),
  ADD KEY `id_barang` (`id_barang`);

--
-- Indexes for table `detail_laporan_keluar`
--
ALTER TABLE `detail_laporan_keluar`
  ADD PRIMARY KEY (`id_detail_keluar`),
  ADD KEY `id_laporan` (`id_laporan`),
  ADD KEY `id_keluar` (`id_keluar`);

--
-- Indexes for table `detail_terima`
--
ALTER TABLE `detail_terima`
  ADD PRIMARY KEY (`id_detail_terima`),
  ADD KEY `id_penerimaan` (`id_penerimaan`),
  ADD KEY `id_barang` (`id_barang`);

--
-- Indexes for table `laporan_keluar`
--
ALTER TABLE `laporan_keluar`
  ADD PRIMARY KEY (`id_laporan_keluar`);

--
-- Indexes for table `laporan_masuk`
--
ALTER TABLE `laporan_masuk`
  ADD PRIMARY KEY (`id_laporan_masuk`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `laporan_masuk_detail`
--
ALTER TABLE `laporan_masuk_detail`
  ADD PRIMARY KEY (`id_detail_masuk`),
  ADD KEY `id_laporan` (`id_laporan`),
  ADD KEY `id_masuk` (`id_masuk`);

--
-- Indexes for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `id_pengguna` (`id_pengguna`);

--
-- Indexes for table `penerimaan`
--
ALTER TABLE `penerimaan`
  ADD PRIMARY KEY (`id_penerimaan`),
  ADD KEY `id_supplier` (`id_supplier`),
  ADD KEY `id_pengguna` (`id_pengguna`);

--
-- Indexes for table `pengeluaran`
--
ALTER TABLE `pengeluaran`
  ADD PRIMARY KEY (`id_pengeluaran`),
  ADD KEY `id_pengguna` (`id_pengguna`);

--
-- Indexes for table `pengguna`
--
ALTER TABLE `pengguna`
  ADD PRIMARY KEY (`id_pengguna`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `id_aktor` (`id_aktor`);

--
-- Indexes for table `retur_barang`
--
ALTER TABLE `retur_barang`
  ADD PRIMARY KEY (`id_retur`),
  ADD KEY `id_penerimaan` (`id_penerimaan`),
  ADD KEY `id_pengguna` (`id_pengguna`);

--
-- Indexes for table `stok_opname`
--
ALTER TABLE `stok_opname`
  ADD PRIMARY KEY (`id_opname`),
  ADD KEY `id_barang` (`id_barang`),
  ADD KEY `id_pengguna` (`id_pengguna`);

--
-- Indexes for table `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`id_supplier`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `aktor`
--
ALTER TABLE `aktor`
  MODIFY `id_aktor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `barang`
--
ALTER TABLE `barang`
  MODIFY `id_barang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `barang_keluar`
--
ALTER TABLE `barang_keluar`
  MODIFY `id_keluar` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `barang_masuk`
--
ALTER TABLE `barang_masuk`
  MODIFY `id_masuk` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `data_toko`
--
ALTER TABLE `data_toko`
  MODIFY `id_toko` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `detail_keluar`
--
ALTER TABLE `detail_keluar`
  MODIFY `id_detail_keluar` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `detail_laporan_keluar`
--
ALTER TABLE `detail_laporan_keluar`
  MODIFY `id_detail_keluar` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `detail_terima`
--
ALTER TABLE `detail_terima`
  MODIFY `id_detail_terima` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `laporan_keluar`
--
ALTER TABLE `laporan_keluar`
  MODIFY `id_laporan_keluar` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `laporan_masuk`
--
ALTER TABLE `laporan_masuk`
  MODIFY `id_laporan_masuk` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `laporan_masuk_detail`
--
ALTER TABLE `laporan_masuk_detail`
  MODIFY `id_detail_masuk` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `penerimaan`
--
ALTER TABLE `penerimaan`
  MODIFY `id_penerimaan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pengeluaran`
--
ALTER TABLE `pengeluaran`
  MODIFY `id_pengeluaran` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pengguna`
--
ALTER TABLE `pengguna`
  MODIFY `id_pengguna` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `retur_barang`
--
ALTER TABLE `retur_barang`
  MODIFY `id_retur` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stok_opname`
--
ALTER TABLE `stok_opname`
  MODIFY `id_opname` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier`
--
ALTER TABLE `supplier`
  MODIFY `id_supplier` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `barang_keluar`
--
ALTER TABLE `barang_keluar`
  ADD CONSTRAINT `barang_keluar_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`),
  ADD CONSTRAINT `barang_keluar_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `pengguna` (`id_pengguna`);

--
-- Constraints for table `barang_masuk`
--
ALTER TABLE `barang_masuk`
  ADD CONSTRAINT `barang_masuk_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`),
  ADD CONSTRAINT `barang_masuk_ibfk_2` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`),
  ADD CONSTRAINT `barang_masuk_ibfk_3` FOREIGN KEY (`id_user`) REFERENCES `pengguna` (`id_pengguna`);

--
-- Constraints for table `detail_keluar`
--
ALTER TABLE `detail_keluar`
  ADD CONSTRAINT `detail_keluar_ibfk_1` FOREIGN KEY (`id_pengeluaran`) REFERENCES `pengeluaran` (`id_pengeluaran`),
  ADD CONSTRAINT `detail_keluar_ibfk_2` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`);

--
-- Constraints for table `detail_laporan_keluar`
--
ALTER TABLE `detail_laporan_keluar`
  ADD CONSTRAINT `detail_laporan_keluar_ibfk_1` FOREIGN KEY (`id_laporan`) REFERENCES `laporan_keluar` (`id_laporan_keluar`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_laporan_keluar_ibfk_2` FOREIGN KEY (`id_keluar`) REFERENCES `barang_keluar` (`id_keluar`) ON DELETE CASCADE;

--
-- Constraints for table `detail_terima`
--
ALTER TABLE `detail_terima`
  ADD CONSTRAINT `detail_terima_ibfk_1` FOREIGN KEY (`id_penerimaan`) REFERENCES `penerimaan` (`id_penerimaan`),
  ADD CONSTRAINT `detail_terima_ibfk_2` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`);

--
-- Constraints for table `laporan_masuk`
--
ALTER TABLE `laporan_masuk`
  ADD CONSTRAINT `laporan_masuk_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `pengguna` (`id_pengguna`);

--
-- Constraints for table `laporan_masuk_detail`
--
ALTER TABLE `laporan_masuk_detail`
  ADD CONSTRAINT `laporan_masuk_detail_ibfk_1` FOREIGN KEY (`id_laporan`) REFERENCES `laporan_masuk` (`id_laporan_masuk`) ON DELETE CASCADE,
  ADD CONSTRAINT `laporan_masuk_detail_ibfk_2` FOREIGN KEY (`id_masuk`) REFERENCES `barang_masuk` (`id_masuk`) ON DELETE CASCADE;

--
-- Constraints for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD CONSTRAINT `log_aktivitas_ibfk_1` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`);

--
-- Constraints for table `penerimaan`
--
ALTER TABLE `penerimaan`
  ADD CONSTRAINT `penerimaan_ibfk_1` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`),
  ADD CONSTRAINT `penerimaan_ibfk_2` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`);

--
-- Constraints for table `pengeluaran`
--
ALTER TABLE `pengeluaran`
  ADD CONSTRAINT `pengeluaran_ibfk_1` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`);

--
-- Constraints for table `pengguna`
--
ALTER TABLE `pengguna`
  ADD CONSTRAINT `pengguna_ibfk_1` FOREIGN KEY (`id_aktor`) REFERENCES `aktor` (`id_aktor`);

--
-- Constraints for table `retur_barang`
--
ALTER TABLE `retur_barang`
  ADD CONSTRAINT `retur_barang_ibfk_1` FOREIGN KEY (`id_penerimaan`) REFERENCES `penerimaan` (`id_penerimaan`),
  ADD CONSTRAINT `retur_barang_ibfk_2` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`);

--
-- Constraints for table `stok_opname`
--
ALTER TABLE `stok_opname`
  ADD CONSTRAINT `stok_opname_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`),
  ADD CONSTRAINT `stok_opname_ibfk_2` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
