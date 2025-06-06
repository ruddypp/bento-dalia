-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 06 Jun 2025 pada 11.33
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inventori_db3`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `aktor`
--

CREATE TABLE `aktor` (
  `id_aktor` int(11) NOT NULL,
  `nama_aktor` enum('administrator','forecasting','head_bar','head_kitchen','manajer') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `aktor`
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
-- Struktur dari tabel `bahan_baku`
--

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

--
-- Dumping data untuk tabel `bahan_baku`
--

INSERT INTO `bahan_baku` (`id_bahan_baku`, `id_barang`, `qty`, `periode`, `harga_satuan`, `total`, `lokasi`, `tanggal_input`, `id_user`, `status`, `jumlah_retur`, `jumlah_masuk`, `catatan_retur`, `id_pesanan`) VALUES
(68, 13, 30, 2, 3500.00, 105000.00, 'kitchen', '2025-06-04 21:00:16', 22, 'retur', 29, 1, 'tes', 14),
(69, 13, 30, 2, 3500.00, 105000.00, 'kitchen', '2025-06-04 21:30:35', 22, 'approved', 0, 30, NULL, 14),
(70, 14, 100, 1, 3000.00, 300000.00, 'kitchen', '2025-06-04 21:32:38', 22, 'approved', 0, 100, NULL, 15),
(71, 13, 100, 1, 3000.00, 300000.00, 'kitchen', '2025-06-04 22:21:28', 22, 'approved', 0, 100, NULL, 16),
(72, 14, 20, 1, 3000.00, 60000.00, 'kitchen', '2025-06-04 22:29:22', 22, 'approved', 0, 20, NULL, 17),
(74, 14, 50, 1, 3000.00, 150000.00, 'kitchen', '2025-06-04 22:48:21', 22, 'approved', 0, 0, NULL, 18),
(76, 13, 5, 1, 3000.00, 15000.00, 'kitchen', '2025-06-04 23:22:23', 22, 'approved', 0, 0, NULL, 19),
(82, 13, 6, 1, 3000.00, 18000.00, 'kitchen', '2025-06-04 23:38:37', 22, 'approved', 0, 6, NULL, 22),
(83, 14, 2, 1, 3000.00, 6000.00, 'kitchen', '2025-06-04 23:40:58', 22, 'approved', 0, 0, NULL, 22),
(84, 13, 1, 1, 3000.00, 3000.00, 'kitchen', '2025-06-05 00:02:11', 22, 'approved', 0, 1, NULL, 23),
(85, 14, 1, 1, 3000.00, 3000.00, 'kitchen', '2025-06-05 00:03:13', 22, 'approved', 0, 0, NULL, 22),
(90, 14, 1, 1, 3000.00, 3000.00, 'kitchen', '2025-06-05 00:21:44', 22, 'approved', 0, 0, NULL, 21),
(91, 13, 1, 2, 5000.00, 5000.00, 'bar', '2025-06-05 00:22:50', 22, 'approved', 0, 0, NULL, NULL),
(92, 14, 10, 1, 3000.00, 30000.00, 'kitchen', '2025-06-05 00:30:24', 22, 'approved', 0, 0, NULL, 18),
(93, 13, 1, 2, 3500.00, 3500.00, 'kitchen', '2025-06-05 00:47:56', 22, 'approved', 0, 0, NULL, 14),
(98, 13, 30, 1, 3000.00, 90000.00, 'kitchen', '2025-06-05 00:58:39', 22, 'approved', 0, 30, NULL, 25),
(99, 14, 30, 1, 3000.00, 90000.00, 'kitchen', '2025-06-05 00:58:39', 22, 'retur', 15, 15, 'hai', 25),
(100, 15, 30, 1, 4000.00, 120000.00, 'kitchen', '2025-06-05 00:58:39', 22, 'approved', 0, 30, NULL, 25),
(101, 16, 30, 1, 10000.00, 300000.00, 'kitchen', '2025-06-05 00:58:39', 22, 'retur', 15, 15, 'hai', 25),
(102, 14, 15, 1, 3000.00, 45000.00, 'kitchen', '2025-06-05 01:00:20', 22, 'approved', 0, 0, NULL, 25),
(103, 16, 15, 1, 10000.00, 150000.00, 'kitchen', '2025-06-05 01:00:31', 22, 'approved', 0, 0, NULL, 25),
(104, 14, 1, 1, 3000.00, 3000.00, 'kitchen', '2025-06-05 02:06:40', 22, 'retur', 0, 0, 'Pesanan dibatalkan oleh admin', 26),
(105, 14, 1, 1, 6000.00, 6000.00, 'kitchen', '2025-06-05 02:24:10', 22, 'dibatalkan', 0, 0, 'Pesanan dibatalkan oleh admin', 27),
(106, 13, 5, 1, 3000.00, 15000.00, 'kitchen', '2025-06-05 17:01:25', 22, 'dibatalkan', 0, 0, NULL, 28),
(107, 14, 6, 1, 3000.00, 18000.00, 'kitchen', '2025-06-05 17:01:25', 22, 'retur', 3, 3, 'ok', 28),
(108, 15, 4, 1, 4000.00, 16000.00, 'kitchen', '2025-06-05 17:01:25', 22, 'approved', 0, 4, NULL, 28),
(109, 14, 3, 1, 3000.00, 9000.00, 'kitchen', '2025-06-05 17:03:45', 22, 'approved', 0, 0, NULL, 28),
(110, 13, 6, 1, 3000.00, 18000.00, 'kitchen', '2025-06-05 17:30:59', 22, 'approved', 0, 6, NULL, 29),
(111, 14, 4, 1, 3000.00, 12000.00, 'kitchen', '2025-06-05 17:30:59', 22, 'retur', 2, 2, 'basi', 29),
(113, 14, 2, 1, 3000.00, 6000.00, 'kitchen', '2025-06-05 17:32:00', 22, 'approved', 0, 0, NULL, 29),
(114, 13, 2, 2, 3000.00, 6000.00, 'kitchen', '2025-06-06 10:07:34', 22, 'retur', 1, 1, 'y', 30),
(115, 13, 1, 2, 3000.00, 3000.00, 'kitchen', '2025-06-06 10:08:27', 22, 'approved', 0, 0, NULL, 30);

-- --------------------------------------------------------

--
-- Struktur dari tabel `barang`
--

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

--
-- Dumping data untuk tabel `barang`
--

INSERT INTO `barang` (`id_barang`, `nama_barang`, `satuan`, `jenis`, `stok`, `stok_minimum`, `harga`, `lokasi`, `periode`, `id_supplier`, `periode_1`, `periode_2`) VALUES
(13, 'kopi', 'pack', 'minuman', 125, 10, 3000.00, 'kitchen', NULL, 3, NULL, NULL),
(14, 'gula', 'gr', 'bahan baku', 107, 10, 3000.00, 'kitchen', NULL, 3, NULL, NULL),
(15, 'teh', 'pack', 'minuman', 48, 10, 4000.00, 'bar', NULL, 3, NULL, NULL),
(16, 'susu', 'pack', 'minuman', 64, 10, 10000.00, 'bar', NULL, 3, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `barang_keluar`
--

CREATE TABLE `barang_keluar` (
  `id_keluar` int(11) NOT NULL,
  `id_barang` int(11) NOT NULL,
  `tanggal_keluar` datetime NOT NULL DEFAULT current_timestamp(),
  `id_user` int(11) DEFAULT NULL,
  `qty_keluar` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `barang_keluar`
--

INSERT INTO `barang_keluar` (`id_keluar`, `id_barang`, `tanggal_keluar`, `id_user`, `qty_keluar`) VALUES
(37, 14, '2025-06-04 23:46:47', 22, 1),
(38, 13, '2025-06-04 23:46:47', 22, 1),
(39, 13, '2025-06-05 01:15:06', 22, 0),
(40, 16, '2025-06-05 01:15:06', 22, 0),
(41, 14, '2025-06-05 01:15:06', 22, 0),
(42, 14, '2025-06-05 19:46:03', 22, 2),
(43, 13, '2025-06-05 19:46:03', 22, 2);

-- --------------------------------------------------------

--
-- Struktur dari tabel `barang_masuk`
--

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

--
-- Dumping data untuk tabel `barang_masuk`
--

INSERT INTO `barang_masuk` (`id_masuk`, `id_barang`, `tanggal_masuk`, `id_user`, `qty_masuk`, `id_supplier`, `lokasi`, `harga_satuan`, `periode`) VALUES
(115, 13, '2025-06-04 21:31:37', NULL, 30, NULL, 'kitchen', 3500.00, 2),
(116, 14, '2025-06-04 21:41:38', NULL, 100, NULL, 'kitchen', 3000.00, 1),
(117, 13, '2025-06-04 22:21:44', NULL, 100, NULL, 'kitchen', 3000.00, 1),
(118, 14, '2025-06-04 22:29:30', 22, 20, 3, '0', 3000.00, 1),
(119, 14, '2025-06-04 22:48:21', 22, 50, 3, '0', 3000.00, 1),
(120, 13, '2025-06-04 23:22:23', 22, 5, 3, '0', 3000.00, 1),
(121, 13, '2025-06-04 23:39:17', 22, 6, 3, '0', 3000.00, 1),
(122, 14, '2025-06-04 23:40:58', 22, 2, 3, '0', 3000.00, 1),
(123, 13, '2025-06-05 00:02:29', 22, 1, 3, '0', 3000.00, 1),
(124, 14, '2025-06-05 00:03:13', 22, 1, 3, '0', 3000.00, 1),
(129, 14, '2025-06-05 00:21:44', 22, 1, 3, '0', 3000.00, 1),
(130, 13, '2025-06-05 00:22:50', 22, 1, 3, '0', 5000.00, 2),
(131, 14, '2025-06-05 00:30:24', 22, 10, 3, '0', 3000.00, 1),
(132, 13, '2025-06-05 00:47:57', 22, 1, 3, '0', 3500.00, 2),
(133, 14, '2025-06-05 01:00:20', 22, 15, 3, '0', 3000.00, 1),
(134, 16, '2025-06-05 01:00:31', 22, 15, 3, '0', 10000.00, 1),
(135, 13, '2025-06-05 01:00:36', 22, 30, 3, '0', 3000.00, 1),
(136, 15, '2025-06-05 01:00:45', 22, 30, 3, '0', 4000.00, 1),
(137, 15, '2025-06-05 17:01:58', 22, 4, 3, '0', 4000.00, 1),
(138, 14, '2025-06-05 17:03:45', 22, 3, 3, '0', 3000.00, 1),
(139, 13, '2025-06-05 17:31:37', 22, 6, 3, '0', 3000.00, 1),
(140, 14, '2025-06-05 17:32:00', 22, 2, 3, '0', 3000.00, 1),
(141, 13, '2025-06-06 10:08:27', 22, 1, 3, '0', 3000.00, 2);

-- --------------------------------------------------------

--
-- Struktur dari tabel `barang_supplier`
--

CREATE TABLE `barang_supplier` (
  `id` int(11) NOT NULL,
  `id_barang` int(11) NOT NULL,
  `id_supplier` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `data_toko`
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
-- Dumping data untuk tabel `data_toko`
--

INSERT INTO `data_toko` (`id_toko`, `nama_toko`, `alamat`, `kontak`, `email`, `website`, `deskripsi`, `logo`) VALUES
(1, 'Toko Inventori', 'Jl. Contoh No. 123, Jakarta', '021-1234567', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_keluar`
--

CREATE TABLE `detail_keluar` (
  `id_detail_keluar` int(11) NOT NULL,
  `id_pengeluaran` int(11) DEFAULT NULL,
  `id_barang` int(11) DEFAULT NULL,
  `jumlah_keluar` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_pesanan`
--

CREATE TABLE `detail_pesanan` (
  `id_detail_pesanan` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `id_item` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1,
  `harga_saat_pesan` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_terima`
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
-- Struktur dari tabel `laporan_keluar`
--

CREATE TABLE `laporan_keluar` (
  `id_laporan_keluar` int(11) NOT NULL,
  `tanggal_laporan` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `laporan_keluar`
--

INSERT INTO `laporan_keluar` (`id_laporan_keluar`, `tanggal_laporan`) VALUES
(3, '2025-05-23'),
(26, '2025-05-23');

-- --------------------------------------------------------

--
-- Struktur dari tabel `laporan_keluar_detail`
--

CREATE TABLE `laporan_keluar_detail` (
  `id_detail_keluar` int(11) NOT NULL,
  `id_laporan` int(11) NOT NULL,
  `id_keluar` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `laporan_keluar_detail`
--

INSERT INTO `laporan_keluar_detail` (`id_detail_keluar`, `id_laporan`, `id_keluar`) VALUES
(0, 3, 1),
(31, 26, 30);

-- --------------------------------------------------------

--
-- Struktur dari tabel `laporan_masuk`
--

CREATE TABLE `laporan_masuk` (
  `id_laporan_masuk` int(11) NOT NULL,
  `tanggal_laporan` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `status` enum('pending','approved') NOT NULL DEFAULT 'pending',
  `periode` int(11) DEFAULT 1 COMMENT '1, 2, 3, or 4'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `laporan_masuk`
--

INSERT INTO `laporan_masuk` (`id_laporan_masuk`, `tanggal_laporan`, `created_by`, `created_at`, `status`, `periode`) VALUES
(42, '2025-06-04', NULL, '2025-06-04 21:31:37', 'approved', 2),
(44, '2025-06-04', NULL, '2025-06-04 22:21:44', 'approved', 1),
(45, '2025-06-05', NULL, '2025-06-05 17:01:58', 'approved', 1),
(46, '2025-06-06', NULL, '2025-06-06 10:08:27', 'approved', 2);

-- --------------------------------------------------------

--
-- Struktur dari tabel `laporan_masuk_detail`
--

CREATE TABLE `laporan_masuk_detail` (
  `id_detail` int(11) NOT NULL,
  `id_laporan` int(11) DEFAULT NULL,
  `id_masuk` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `laporan_masuk_detail`
--

INSERT INTO `laporan_masuk_detail` (`id_detail`, `id_laporan`, `id_masuk`) VALUES
(45, 42, 115),
(47, 44, 117),
(48, 44, 118),
(49, 44, 119),
(50, 44, 120),
(51, 44, 121),
(52, 44, 122),
(53, 44, 123),
(54, 44, 124),
(55, 44, 129),
(56, 42, 130),
(57, 44, 131),
(58, 42, 132),
(59, 44, 133),
(60, 44, 134),
(61, 44, 135),
(62, 44, 136),
(63, 45, 137),
(64, 45, 138),
(65, 45, 139),
(66, 45, 140),
(67, 46, 141);

-- --------------------------------------------------------

--
-- Struktur dari tabel `laporan_penjualan`
--

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

--
-- Dumping data untuk tabel `laporan_penjualan`
--

INSERT INTO `laporan_penjualan` (`id_laporan`, `tanggal`, `total_penjualan`, `total_modal`, `total_keuntungan`, `jumlah_transaksi`, `created_at`, `id_user`) VALUES
(1, '2025-06-03', 31000.00, 21500.00, 9500.00, 2, '2025-06-03 10:53:18', 22),
(2, '2025-06-04', 38000.00, 18300.00, 19700.00, 2, '2025-06-04 23:46:47', 22),
(3, '2025-06-05', 38000.00, 12600.00, 25400.00, 1, '2025-06-05 19:46:03', 22);

-- --------------------------------------------------------

--
-- Struktur dari tabel `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id_log` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `waktu` datetime DEFAULT NULL,
  `aktivitas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `log_aktivitas`
--

INSERT INTO `log_aktivitas` (`id_log`, `id_user`, `waktu`, `aktivitas`) VALUES
(1, 22, '2025-06-04 01:42:24', 'Mengubah data supplier: rudy'),
(2, 22, '2025-06-04 01:42:53', 'Menambahkan barang baru: ayampok'),
(3, 22, '2025-06-04 01:43:34', 'Menambahkan bahan baku dari pesanan: id_barang #10, qty: 2, periode: 1'),
(4, 22, '2025-06-04 01:43:34', 'Membuat pesanan baru ke supplier: rudy dengan total Rp 18.000'),
(5, 22, '2025-06-04 01:43:56', 'Menyetujui bahan baku: ayampok, qty: 2'),
(6, 22, '2025-06-04 01:44:29', 'Menambahkan bahan baku dari pesanan: id_barang #10, qty: 2, periode: 1'),
(7, 22, '2025-06-04 01:44:29', 'Membuat pesanan baru ke supplier: rudy dengan total Rp 16.000'),
(8, 22, '2025-06-04 01:45:04', 'Melakukan retur bahan baku: ayampok, qty: 1'),
(9, 22, '2025-06-04 02:14:35', 'Menambahkan bahan baku dari pesanan: id_barang #10, qty: 8, periode: 2'),
(10, 22, '2025-06-04 02:14:35', 'Membuat pesanan baru ke supplier: rudy dengan total Rp 64.000'),
(11, 22, '2025-06-04 02:43:22', 'Mengubah status pesanan #12 menjadi approved'),
(12, 22, '2025-06-04 02:43:22', 'Melakukan retur bahan baku: ayampok, qty: 3'),
(13, 22, '2025-06-04 02:49:56', 'Melakukan retur bahan baku: ayampok, qty: 1'),
(14, 1, '2025-06-04 10:42:14', 'Logout dari sistem'),
(15, 22, '2025-06-04 10:44:26', 'Login ke sistem'),
(16, 22, '2025-06-04 10:44:46', 'Mengubah data supplier: rudy'),
(17, 22, '2025-06-04 10:45:19', 'Menambahkan barang baru dari pesanan: ayambuk'),
(18, 22, '2025-06-04 10:45:19', 'Menambahkan bahan baku dari pesanan: id_barang #11, qty: 2, periode: 2'),
(19, 22, '2025-06-04 10:45:19', 'Membuat pesanan baru ke supplier: rudy dengan total Rp 50.000'),
(20, 22, '2025-06-04 10:46:02', 'Mengubah status pesanan #13 menjadi approved'),
(21, 22, '2025-06-04 10:46:02', 'Menyetujui bahan baku: ayambuk, qty: 2'),
(22, 22, '2025-06-04 11:03:33', 'Menambahkan barang baru: 2s'),
(23, 22, '2025-06-04 11:04:03', 'Mengubah data supplier: Beno'),
(24, 22, '2025-06-04 19:33:48', 'Menghapus barang: 2s'),
(25, 22, '2025-06-04 20:08:26', 'Menghapus barang dan 1 transaksi terkait: ayambuk'),
(26, 22, '2025-06-04 20:13:15', 'Menghapus barang dan transaksi terkait: ayampok'),
(27, 22, '2025-06-04 20:34:50', 'Menghapus barang dan transaksi terkait: susu'),
(28, 22, '2025-06-04 20:40:53', 'Mengubah data barang: ayampok'),
(29, 22, '2025-06-04 20:41:53', 'Admin menambah stok gula dari 580 menjadi 900 (Adjustment: 320)'),
(30, 22, '2025-06-04 20:42:10', 'Mengubah data barang: gula'),
(31, 22, '2025-06-04 20:44:41', 'Menghapus barang dan transaksi terkait: Sawi'),
(32, 22, '2025-06-04 20:45:13', 'Menghapus barang dan transaksi terkait: gula'),
(33, 22, '2025-06-04 20:45:39', 'Menghapus barang dan transaksi terkait: mie'),
(34, 22, '2025-06-04 20:45:44', 'Menghapus barang dan transaksi terkait: ayampok'),
(35, 22, '2025-06-04 20:45:48', 'Menghapus barang dan transaksi terkait: s2'),
(36, 22, '2025-06-04 20:45:59', 'Menghapus supplier: Beno'),
(37, 22, '2025-06-04 20:51:53', 'Menghapus supplier dan data terkait: rudy'),
(38, 22, '2025-06-04 20:53:15', 'Menambahkan supplier baru: rudy'),
(39, 22, '2025-06-04 20:56:16', 'Menghapus laporan barang masuk #26'),
(40, 22, '2025-06-04 20:56:20', 'Menghapus laporan barang masuk #27'),
(41, 22, '2025-06-04 20:56:22', 'Menghapus laporan barang masuk #41'),
(42, 22, '2025-06-04 20:56:24', 'Menghapus laporan barang masuk #37'),
(43, 22, '2025-06-04 20:56:28', 'Menghapus laporan barang masuk #29'),
(44, 22, '2025-06-04 20:56:31', 'Menghapus laporan barang masuk #33'),
(45, 22, '2025-06-04 20:56:33', 'Menghapus laporan barang masuk #34'),
(46, 22, '2025-06-04 20:56:36', 'Menghapus laporan barang masuk #35'),
(47, 22, '2025-06-04 20:56:38', 'Menghapus laporan barang masuk #36'),
(48, 22, '2025-06-04 20:56:41', 'Menghapus laporan barang masuk #30'),
(49, 22, '2025-06-04 20:56:44', 'Menghapus laporan barang masuk #31'),
(50, 22, '2025-06-04 20:56:46', 'Menghapus laporan barang masuk #32'),
(51, 22, '2025-06-04 20:59:11', 'Menambahkan barang baru: kopi'),
(52, 22, '2025-06-04 21:00:16', 'Menambahkan bahan baku dari pesanan: id_barang #13, qty: 30, periode: 2'),
(53, 22, '2025-06-04 21:00:16', 'Membuat pesanan baru ke supplier: rudy dengan total Rp 105.000'),
(54, 22, '2025-06-04 21:00:50', 'Mengubah data bahan baku: kopi, status: retur'),
(55, 22, '2025-06-04 21:30:35', 'Menambahkan bahan baku: kopi, qty: 30, periode: 2 dari pesanan #14'),
(56, 22, '2025-06-04 21:30:35', 'Memproses pesanan #14 dari supplier: rudy'),
(57, NULL, '2025-06-04 21:31:37', 'Menyetujui bahan baku kopi dengan jumlah 30 '),
(58, 22, '2025-06-04 21:32:38', 'Menambahkan barang baru dari pesanan: gula'),
(59, 22, '2025-06-04 21:32:38', 'Menambahkan bahan baku dari pesanan: id_barang #14, qty: 100, periode: 1'),
(60, 22, '2025-06-04 21:32:38', 'Membuat pesanan baru ke supplier: rudy dengan total Rp 300.000'),
(61, NULL, '2025-06-04 21:41:38', 'Menyetujui bahan baku gula dengan jumlah 100 '),
(62, 22, '2025-06-04 21:42:22', 'Mengubah data barang: gula'),
(63, 22, '2025-06-04 21:54:30', 'Menghapus laporan barang masuk #43'),
(64, 22, '2025-06-04 22:21:28', 'Menambahkan bahan baku dari pesanan: id_barang #13, qty: 100, periode: 1'),
(65, 22, '2025-06-04 22:21:28', 'Membuat pesanan baru ke supplier: rudy dengan total Rp 300.000'),
(66, NULL, '2025-06-04 22:21:44', 'Menyetujui bahan baku kopi dengan jumlah 100 '),
(67, 22, '2025-06-04 22:29:22', 'Menambahkan bahan baku dari pesanan: id_barang #14, qty: 20, periode: 1'),
(68, 22, '2025-06-04 22:29:22', 'Membuat pesanan baru ke supplier: rudy dengan total Rp 60.000'),
(69, NULL, '2025-06-04 22:29:30', 'Menyetujui bahan baku gula dengan jumlah 20 '),
(70, 22, '2025-06-04 22:48:06', 'Menambahkan bahan baku dari pesanan: id_barang #14, qty: 70, periode: 1'),
(71, 22, '2025-06-04 22:48:06', 'Membuat pesanan baru ke supplier: rudy dengan total Rp 210.000'),
(72, 22, '2025-06-04 22:48:21', 'Mengubah status pesanan #18 menjadi approved'),
(73, 22, '2025-06-04 22:48:21', 'Melakukan retur bahan baku: gula, qty: 20'),
(74, 22, '2025-06-04 23:21:42', 'Menambahkan bahan baku dari pesanan: id_barang #13, qty: 10, periode: 1'),
(75, 22, '2025-06-04 23:21:42', 'Membuat pesanan baru ke supplier: rudy dengan total Rp 30.000'),
(76, 22, '2025-06-04 23:22:23', 'Menyelesaikan pesanan #19 karena semua item telah diproses'),
(77, 22, '2025-06-04 23:22:23', 'Melakukan retur bahan baku kopi sebanyak 5 pack dari total 10 pack'),
(78, 22, '2025-06-04 23:34:53', 'Menambahkan bahan baku: kopi, qty: 2, periode: 2'),
(79, 22, '2025-06-04 23:35:55', 'Menambahkan bahan baku dari pesanan: id_barang #14, qty: 1, periode: 1'),
(80, 22, '2025-06-04 23:35:55', 'Membuat pesanan baru ke supplier: rudy dengan total Rp 3.000'),
(81, 22, '2025-06-04 23:37:00', 'Mengubah data supplier: rudy'),
(82, 22, '2025-06-04 23:37:48', 'Menambahkan bahan baku dari pesanan: id_barang #13, qty: 4, periode: 1'),
(83, 22, '2025-06-04 23:37:48', 'Menambahkan bahan baku dari pesanan: id_barang #14, qty: 5, periode: 1'),
(84, 22, '2025-06-04 23:37:48', 'Membuat pesanan baru ke supplier: rudy dengan total Rp 27.000'),
(85, 22, '2025-06-04 23:38:05', 'Membatalkan pesanan #21 dari supplier: rudy'),
(86, 22, '2025-06-04 23:38:37', 'Menambahkan bahan baku dari pesanan: id_barang #14, qty: 4, periode: 1'),
(87, 22, '2025-06-04 23:38:37', 'Menambahkan bahan baku dari pesanan: id_barang #13, qty: 6, periode: 1'),
(88, 22, '2025-06-04 23:38:37', 'Membuat pesanan baru ke supplier: rudy dengan total Rp 30.000'),
(89, NULL, '2025-06-04 23:39:17', 'Menyetujui bahan baku kopi dengan jumlah 6 '),
(90, 22, '2025-06-04 23:40:58', 'Menyelesaikan pesanan #22 karena semua item telah diproses'),
(91, 22, '2025-06-04 23:40:58', 'Melakukan retur bahan baku gula sebanyak 2 gr dari total 4 gr'),
(92, 22, '2025-06-04 23:41:48', 'Mengubah status pesanan #22 menjadi diproses'),
(93, 22, '2025-06-04 23:41:48', 'Menghapus data retur bahan baku ID: 81'),
(94, 22, '2025-06-04 23:43:49', 'Menambahkan barang lost: kopi (Jumlah: 1, Alasan: Rusak)'),
(95, 22, '2025-06-04 23:46:12', 'Menambahkan menu minuman baru: coffe latte'),
(96, 22, '2025-06-04 23:46:47', 'Membuat transaksi penjualan #INV202506045308 senilai Rp 19.000'),
(97, 22, '2025-06-05 00:02:11', 'Menambahkan bahan baku dari pesanan: id_barang #13, qty: 1, periode: 1'),
(98, 22, '2025-06-05 00:02:11', 'Membuat pesanan baru ke supplier: rudy dengan total Rp 3.000'),
(99, NULL, '2025-06-05 00:02:29', 'Menyetujui bahan baku kopi dengan jumlah 1 '),
(100, 22, '2025-06-05 00:03:13', 'Menyelesaikan pesanan #22 karena semua item telah diproses'),
(101, 22, '2025-06-05 00:03:13', 'Melakukan retur bahan baku gula sebanyak 3 gr dari total 4 gr'),
(102, 22, '2025-06-05 00:06:12', 'Mengubah status pesanan #22 menjadi diproses'),
(103, 22, '2025-06-05 00:06:12', 'Menghapus data retur langsung untuk barang: gula'),
(104, 22, '2025-06-05 00:20:13', 'Mengubah status pesanan #18 menjadi diproses'),
(105, 22, '2025-06-05 00:20:13', 'Menghapus data retur bahan baku ID: 73'),
(106, 22, '2025-06-05 00:20:23', 'Mengubah status pesanan #21 menjadi diproses'),
(107, 22, '2025-06-05 00:20:23', 'Menghapus data retur bahan baku ID: 80'),
(108, 22, '2025-06-05 00:21:44', 'Menyelesaikan pesanan #21 karena semua item telah diproses'),
(109, 22, '2025-06-05 00:21:44', 'Melakukan retur bahan baku gula sebanyak 4 gr dari total 5 gr'),
(110, 22, '2025-06-05 00:22:50', 'Melakukan retur bahan baku kopi sebanyak 1 pack dari total 2 pack'),
(111, 22, '2025-06-05 00:29:46', 'Mengubah status pesanan #21 menjadi diproses'),
(112, 22, '2025-06-05 00:29:46', 'Menghapus data retur langsung untuk barang: gula'),
(113, 22, '2025-06-05 00:29:51', 'Menghapus data retur langsung untuk barang: kopi'),
(114, 22, '2025-06-05 00:30:24', 'Menyelesaikan pesanan #18 karena semua item telah diproses'),
(115, 22, '2025-06-05 00:30:24', 'Melakukan retur bahan baku gula sebanyak 10 gr dari total 20 gr'),
(116, 22, '2025-06-05 00:32:58', 'Mengubah status pesanan #22 menjadi diproses'),
(117, 22, '2025-06-05 00:32:58', 'Menghapus data retur bahan baku ID: 81'),
(118, 22, '2025-06-05 00:33:04', 'Mengubah status pesanan #21 menjadi diproses'),
(119, 22, '2025-06-05 00:33:04', 'Menghapus data retur bahan baku ID: 79'),
(120, 22, '2025-06-05 00:33:13', 'Mengubah status pesanan #21 menjadi diproses'),
(121, 22, '2025-06-05 00:33:13', 'Menghapus data retur bahan baku ID: 80'),
(122, 22, '2025-06-05 00:33:17', 'Menghapus data retur bahan baku ID: 77'),
(123, 22, '2025-06-05 00:33:21', 'Mengubah status pesanan #19 menjadi diproses'),
(124, 22, '2025-06-05 00:33:21', 'Menghapus data retur bahan baku ID: 75'),
(125, 22, '2025-06-05 00:33:24', 'Mengubah status pesanan #18 menjadi diproses'),
(126, 22, '2025-06-05 00:33:24', 'Menghapus data retur bahan baku ID: 73'),
(127, 22, '2025-06-05 00:33:28', 'Mengubah status pesanan #14 menjadi diproses'),
(128, 22, '2025-06-05 00:33:28', 'Menghapus data retur bahan baku ID: 68'),
(129, 22, '2025-06-05 00:33:31', 'Mengubah status pesanan #18 menjadi diproses'),
(130, 22, '2025-06-05 00:33:31', 'Menghapus data retur langsung untuk barang: gula'),
(131, 1, '2025-06-05 00:46:42', 'Menghapus bahan baku: gula'),
(132, 1, '2025-06-05 00:46:46', 'Menghapus bahan baku: kopi'),
(133, 1, '2025-06-05 00:46:50', 'Menghapus bahan baku: gula'),
(134, 1, '2025-06-05 00:46:57', 'Menghapus bahan baku: gula'),
(135, 1, '2025-06-05 00:47:10', 'Menghapus bahan baku: gula'),
(136, 1, '2025-06-05 00:47:14', 'Menghapus bahan baku: kopi'),
(137, 1, '2025-06-05 00:47:17', 'Menghapus bahan baku: kopi'),
(138, 22, '2025-06-05 00:47:57', 'Menyelesaikan pesanan #14 karena semua item telah diproses'),
(139, 22, '2025-06-05 00:47:57', 'Melakukan retur bahan baku kopi sebanyak 29 pack dari total 30 pack'),
(140, 22, '2025-06-05 00:49:51', 'Membatalkan pesanan #20 dari supplier: rudy'),
(141, 22, '2025-06-05 00:52:29', 'Menambahkan barang baru: teh'),
(142, 22, '2025-06-05 00:52:49', 'Menambahkan barang baru: susu'),
(143, 22, '2025-06-05 00:53:48', 'Menambahkan bahan baku dari pesanan: id_barang #13, qty: 50, periode: 1'),
(144, 22, '2025-06-05 00:53:48', 'Menambahkan bahan baku dari pesanan: id_barang #14, qty: 50, periode: 1'),
(145, 22, '2025-06-05 00:53:48', 'Menambahkan bahan baku dari pesanan: id_barang #15, qty: 1, periode: 1'),
(146, 22, '2025-06-05 00:53:48', 'Menambahkan bahan baku dari pesanan: id_barang #16, qty: 1, periode: 1'),
(147, 22, '2025-06-05 00:53:48', 'Membuat pesanan baru ke supplier: rudy dengan total Rp 314.000'),
(148, 22, '2025-06-05 00:53:58', 'Membatalkan pesanan #24 dari supplier: rudy'),
(149, 22, '2025-06-05 00:58:39', 'Menambahkan bahan baku dari pesanan: id_barang #13, qty: 30, periode: 1'),
(150, 22, '2025-06-05 00:58:39', 'Menambahkan bahan baku dari pesanan: id_barang #14, qty: 30, periode: 1'),
(151, 22, '2025-06-05 00:58:39', 'Menambahkan bahan baku dari pesanan: id_barang #15, qty: 30, periode: 1'),
(152, 22, '2025-06-05 00:58:39', 'Menambahkan bahan baku dari pesanan: id_barang #16, qty: 30, periode: 1'),
(153, 22, '2025-06-05 00:58:39', 'Membuat pesanan baru ke supplier: rudy dengan total Rp 600.000'),
(154, 22, '2025-06-05 01:00:20', 'Mengupdate status pesanan #25 menjadi diproses karena sebagian item sudah diproses'),
(155, 22, '2025-06-05 01:00:20', 'Melakukan retur bahan baku gula sebanyak 15 gr dari total 30 gr'),
(156, 22, '2025-06-05 01:00:31', 'Mengupdate status pesanan #25 menjadi diproses karena sebagian item sudah diproses'),
(157, 22, '2025-06-05 01:00:31', 'Melakukan retur bahan baku susu sebanyak 15 pack dari total 30 pack'),
(158, NULL, '2025-06-05 01:00:36', 'Menyetujui bahan baku kopi dengan jumlah 30 '),
(159, NULL, '2025-06-05 01:00:45', 'Menyetujui bahan baku teh dengan jumlah 30 '),
(160, 22, '2025-06-05 01:01:01', 'Mengubah status pesanan #24 menjadi diproses'),
(161, 22, '2025-06-05 01:01:01', 'Menghapus data retur bahan baku ID: 94'),
(162, 22, '2025-06-05 01:01:06', 'Mengubah status pesanan #24 menjadi diproses'),
(163, 22, '2025-06-05 01:01:06', 'Menghapus data retur bahan baku ID: 95'),
(164, 22, '2025-06-05 01:01:12', 'Mengubah status pesanan #24 menjadi diproses'),
(165, 22, '2025-06-05 01:01:12', 'Menghapus data retur bahan baku ID: 96'),
(166, 22, '2025-06-05 01:01:15', 'Mengubah status pesanan #24 menjadi diproses'),
(167, 22, '2025-06-05 01:01:15', 'Menghapus data retur bahan baku ID: 97'),
(168, 22, '2025-06-05 01:15:06', 'Membuat transaksi penjualan #INV202506042330 senilai Rp 19.000'),
(169, 22, '2025-06-05 02:06:40', 'Menambahkan bahan baku dari pesanan: id_barang #14, qty: 1, periode: 1'),
(170, 22, '2025-06-05 02:06:40', 'Membuat pesanan baru ke supplier: rudy dengan total Rp 3.000'),
(171, 22, '2025-06-05 02:06:49', 'Membatalkan pesanan #26 dari supplier: rudy'),
(172, 22, '2025-06-05 02:24:10', 'Menambahkan bahan baku dari pesanan: id_barang #14, qty: 1, periode: 1'),
(173, 22, '2025-06-05 02:24:10', 'Membuat pesanan baru ke supplier: rudy dengan total Rp 6.000'),
(174, 22, '2025-06-05 02:24:18', 'Membatalkan pesanan #27 dari supplier: rudy'),
(175, 22, '2025-06-05 16:55:33', 'Login ke sistem'),
(176, 22, '2025-06-05 17:01:25', 'Menambahkan bahan baku dari pesanan: id_barang #13, qty: 5, periode: 1'),
(177, 22, '2025-06-05 17:01:25', 'Menambahkan bahan baku dari pesanan: id_barang #14, qty: 6, periode: 1'),
(178, 22, '2025-06-05 17:01:25', 'Menambahkan bahan baku dari pesanan: id_barang #15, qty: 4, periode: 1'),
(179, 22, '2025-06-05 17:01:25', 'Membuat pesanan baru ke supplier: rudy dengan total Rp 49.000'),
(180, NULL, '2025-06-05 17:01:58', 'Menyetujui bahan baku teh dengan jumlah 4 '),
(181, 22, '2025-06-05 17:03:45', 'Mengupdate status pesanan #28 menjadi diproses karena sebagian item sudah diproses'),
(182, 22, '2025-06-05 17:03:45', 'Melakukan retur bahan baku gula sebanyak 3 gr dari total 6 gr'),
(183, 22, '2025-06-05 17:15:27', 'Mengubah data bahan baku: kopi, status: dibatalkan'),
(184, 22, '2025-06-05 17:30:59', 'Menambahkan bahan baku dari pesanan: id_barang #13, qty: 6, periode: 1'),
(185, 22, '2025-06-05 17:30:59', 'Menambahkan bahan baku dari pesanan: id_barang #14, qty: 4, periode: 1'),
(186, 22, '2025-06-05 17:30:59', 'Menambahkan bahan baku dari pesanan: id_barang #15, qty: 5, periode: 1'),
(187, 22, '2025-06-05 17:30:59', 'Membuat pesanan baru ke supplier: rudy dengan total Rp 50.000'),
(188, NULL, '2025-06-05 17:31:37', 'Menyetujui bahan baku kopi dengan jumlah 6 '),
(189, 22, '2025-06-05 17:32:01', 'Mengupdate status pesanan #29 menjadi diproses karena sebagian item sudah diproses'),
(190, 22, '2025-06-05 17:32:01', 'Melakukan retur bahan baku gula sebanyak 2 gr dari total 4 gr'),
(191, 22, '2025-06-05 19:46:03', 'Membuat transaksi penjualan #INV202506055999 senilai Rp 38.000'),
(192, 22, '2025-06-06 10:07:34', 'Menambahkan bahan baku dari pesanan: id_barang #13, qty: 2, periode: 2'),
(193, 22, '2025-06-06 10:07:34', 'Membuat pesanan baru ke supplier: rudy dengan total Rp 6.000'),
(194, 1, '2025-06-06 10:07:58', 'Menghapus bahan baku: kopi'),
(195, 1, '2025-06-06 10:08:01', 'Menghapus bahan baku: gula'),
(196, 1, '2025-06-06 10:08:04', 'Menghapus bahan baku: teh'),
(197, 1, '2025-06-06 10:08:08', 'Menghapus bahan baku: susu'),
(198, 1, '2025-06-06 10:08:13', 'Menghapus bahan baku: teh'),
(199, 22, '2025-06-06 10:08:27', 'Menyelesaikan pesanan #30 karena semua item telah diproses'),
(200, 22, '2025-06-06 10:08:27', 'Melakukan retur bahan baku kopi sebanyak 1 pack dari total 2 pack'),
(201, 22, '2025-06-06 12:45:25', 'Login ke sistem'),
(202, 22, '2025-06-06 14:37:28', 'Logout dari sistem'),
(203, 24, '2025-06-06 14:37:32', 'Login ke sistem'),
(204, 24, '2025-06-06 14:38:24', 'Logout dari sistem'),
(205, 27, '2025-06-06 14:38:29', 'Login ke sistem'),
(206, 27, '2025-06-06 14:41:39', 'Logout dari sistem'),
(207, 22, '2025-06-06 14:46:58', 'Login ke sistem'),
(208, 22, '2025-06-06 15:02:11', 'Logout dari sistem'),
(209, 25, '2025-06-06 15:02:23', 'Login ke sistem'),
(210, 25, '2025-06-06 15:02:30', 'Logout dari sistem'),
(211, 24, '2025-06-06 15:02:49', 'Login ke sistem'),
(212, 24, '2025-06-06 15:03:36', 'Logout dari sistem'),
(213, 22, '2025-06-06 15:03:40', 'Login ke sistem'),
(214, 22, '2025-06-06 15:03:45', 'Logout dari sistem'),
(215, 27, '2025-06-06 15:03:50', 'Login ke sistem'),
(216, 27, '2025-06-06 15:04:08', 'Logout dari sistem'),
(217, 27, '2025-06-06 15:05:06', 'Login ke sistem'),
(218, 27, '2025-06-06 15:19:20', 'Logout dari sistem'),
(219, 25, '2025-06-06 15:19:26', 'Login ke sistem'),
(220, 25, '2025-06-06 15:19:59', 'Logout dari sistem'),
(221, 26, '2025-06-06 15:20:06', 'Login ke sistem'),
(222, 26, '2025-06-06 15:20:28', 'Logout dari sistem'),
(223, 25, '2025-06-06 16:32:27', 'Login ke sistem'),
(224, 25, '2025-06-06 16:32:38', 'Logout dari sistem');

-- --------------------------------------------------------

--
-- Struktur dari tabel `lost_barang`
--

CREATE TABLE `lost_barang` (
  `id_lost` int(11) NOT NULL,
  `id_barang` int(11) NOT NULL,
  `jumlah` decimal(10,2) NOT NULL,
  `alasan` text NOT NULL,
  `foto_bukti` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `dibuat_oleh` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `lost_barang`
--

INSERT INTO `lost_barang` (`id_lost`, `id_barang`, `jumlah`, `alasan`, `foto_bukti`, `created_at`, `dibuat_oleh`) VALUES
(2, 13, 1.00, 'Rusak', '1749055429_7.jpg', '2025-06-04 23:43:49', 22);

-- --------------------------------------------------------

--
-- Struktur dari tabel `menu`
--

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

--
-- Dumping data untuk tabel `menu`
--

INSERT INTO `menu` (`id_menu`, `nama_menu`, `kategori`, `harga`, `bahan`, `deskripsi`, `foto`, `created_at`, `updated_at`, `status`, `harga_modal`, `keuntungan`) VALUES
(1, 'MIE AYAM', 'makanan', 12000.00, 'mie:1.2, Sawi:0.5, gula:0.5', 'hgshsh', '1747959687_mie ayam enak.png', '2025-05-23 00:21:27', '2025-06-03 03:42:55', 'available', 9500.00, 2500.00),
(2, 'kopi', 'minuman', 19000.00, 'kopi:0.5, susu:0.3, gula:0.2', 'nkjnAKDKA', '1747966347_logo bento kopi.jpeg', '2025-05-23 02:12:27', '2025-06-02 16:32:51', 'available', 12000.00, 7000.00),
(3, 'Nasi Goreng', 'makanan', 15000.00, 'nasi:1, telur:1, bumbu:0.5', 'Nasi goreng spesial dengan telur', NULL, '2025-06-02 16:32:51', '2025-06-02 16:32:51', 'available', 7500.00, 7500.00),
(4, 'Es Teh', 'minuman', 5000.00, 'teh:0.1, gula:0.2', 'Es teh manis segar', NULL, '2025-06-02 16:32:51', '2025-06-02 16:32:51', 'available', 2000.00, 3000.00),
(5, 'coffe latte', 'minuman', 19000.00, 'gula:1.1, kopi:1', '', '', '2025-06-04 16:46:12', '2025-06-04 16:46:12', 'available', 6300.00, 12700.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `penerimaan`
--

CREATE TABLE `penerimaan` (
  `id_penerimaan` int(11) NOT NULL,
  `tanggal_terima` date DEFAULT NULL,
  `id_supplier` int(11) DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `status_penerimaan` enum('diterima','diretur') DEFAULT 'diterima'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `penerimaan`
--

INSERT INTO `penerimaan` (`id_penerimaan`, `tanggal_terima`, `id_supplier`, `id_user`, `status_penerimaan`) VALUES
(3, '2025-06-02', NULL, 22, 'diterima'),
(4, '2025-06-02', NULL, 22, 'diterima'),
(5, '2025-06-02', NULL, 22, 'diretur');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengeluaran`
--

CREATE TABLE `pengeluaran` (
  `id_pengeluaran` int(11) NOT NULL,
  `tanggal_keluar` date DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `keperluan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `penjualan`
--

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

--
-- Dumping data untuk tabel `penjualan`
--

INSERT INTO `penjualan` (`id_penjualan`, `no_invoice`, `tanggal_penjualan`, `total_harga`, `total_modal`, `keuntungan`, `id_user`, `nama_pelanggan`, `status_pembayaran`, `catatan`) VALUES
(1, 'INV202506033461', '2025-06-03 10:53:18', 12000.00, 9500.00, 2500.00, 22, '', 'lunas', ''),
(2, 'INV202506038782', '2025-06-03 10:55:12', 19000.00, 12000.00, 7000.00, 22, '', 'lunas', ''),
(3, 'INV202506045308', '2025-06-04 23:46:47', 19000.00, 6300.00, 12700.00, 22, '', 'lunas', ''),
(4, 'INV202506042330', '2025-06-05 01:15:06', 19000.00, 12000.00, 7000.00, 22, '', 'lunas', ''),
(5, 'INV202506055999', '2025-06-05 19:46:03', 38000.00, 12600.00, 25400.00, 22, '', 'lunas', '');

-- --------------------------------------------------------

--
-- Struktur dari tabel `penjualan_bahan`
--

CREATE TABLE `penjualan_bahan` (
  `id_penjualan_bahan` int(11) NOT NULL,
  `id_penjualan_detail` int(11) NOT NULL,
  `id_barang` int(11) NOT NULL,
  `jumlah` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `penjualan_bahan`
--

INSERT INTO `penjualan_bahan` (`id_penjualan_bahan`, `id_penjualan_detail`, `id_barang`, `jumlah`) VALUES
(7, 3, 14, 1.10),
(8, 3, 13, 1.00),
(9, 4, 13, 0.50),
(10, 4, 16, 0.30),
(11, 4, 14, 0.20),
(12, 5, 14, 2.20),
(13, 5, 13, 2.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `penjualan_detail`
--

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

--
-- Dumping data untuk tabel `penjualan_detail`
--

INSERT INTO `penjualan_detail` (`id_penjualan_detail`, `id_penjualan`, `id_menu`, `jumlah`, `harga_satuan`, `harga_modal_satuan`, `subtotal`, `subtotal_modal`) VALUES
(1, 1, 1, 1, 12000.00, 9500.00, 12000.00, 9500.00),
(2, 2, 2, 1, 19000.00, 12000.00, 19000.00, 12000.00),
(3, 3, 5, 1, 19000.00, 6300.00, 19000.00, 6300.00),
(4, 4, 2, 1, 19000.00, 12000.00, 19000.00, 12000.00),
(5, 5, 5, 2, 19000.00, 6300.00, 38000.00, 12600.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `pesanan_barang`
--

CREATE TABLE `pesanan_barang` (
  `id_pesanan` int(11) NOT NULL,
  `id_supplier` int(11) NOT NULL,
  `tanggal_pesan` date NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `status` enum('pending','diproses','selesai','dibatalkan','approved') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pesanan_barang`
--

INSERT INTO `pesanan_barang` (`id_pesanan`, `id_supplier`, `tanggal_pesan`, `id_user`, `catatan`, `status`, `created_at`) VALUES
(14, 3, '2025-06-04', 22, '', 'selesai', '2025-06-04 14:00:16'),
(15, 3, '2025-06-04', 22, '', 'selesai', '2025-06-04 14:32:38'),
(16, 3, '2025-06-04', 22, '', 'selesai', '2025-06-04 15:21:28'),
(17, 3, '2025-06-04', 22, '', 'selesai', '2025-06-04 15:29:22'),
(20, 3, '2025-06-04', 22, '', 'dibatalkan', '2025-06-04 16:35:55'),
(23, 3, '2025-06-04', 22, '', 'selesai', '2025-06-04 17:02:11'),
(25, 3, '2025-06-04', 22, 'pesanan kedua', 'selesai', '2025-06-04 17:58:39'),
(26, 3, '2025-06-04', 22, '', 'dibatalkan', '2025-06-04 19:06:40'),
(27, 3, '2025-06-04', 22, '', 'dibatalkan', '2025-06-04 19:24:10'),
(30, 3, '2025-06-06', 22, '', 'selesai', '2025-06-06 03:07:34');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pesanan_detail`
--

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

--
-- Dumping data untuk tabel `pesanan_detail`
--

INSERT INTO `pesanan_detail` (`id_detail`, `id_pesanan`, `id_barang`, `qty`, `periode`, `harga_satuan`, `total`, `lokasi`) VALUES
(13, 14, 13, 30, 2, 3500.00, 105000.00, 'kitchen'),
(14, 15, 14, 100, 1, 3000.00, 300000.00, 'kitchen'),
(15, 16, 13, 100, 1, 3000.00, 300000.00, 'kitchen'),
(16, 17, 14, 20, 1, 3000.00, 60000.00, 'kitchen'),
(19, 20, 14, 1, 1, 3000.00, 3000.00, 'kitchen'),
(24, 23, 13, 1, 1, 3000.00, 3000.00, 'kitchen'),
(29, 25, 13, 30, 1, 3000.00, 90000.00, 'kitchen'),
(30, 25, 14, 30, 1, 3000.00, 90000.00, 'kitchen'),
(31, 25, 15, 30, 1, 4000.00, 120000.00, 'kitchen'),
(32, 25, 16, 30, 1, 10000.00, 300000.00, 'kitchen'),
(33, 26, 14, 1, 1, 3000.00, 3000.00, 'kitchen'),
(34, 27, 14, 1, 1, 6000.00, 6000.00, 'kitchen'),
(41, 30, 13, 2, 2, 3000.00, 6000.00, 'kitchen');

-- --------------------------------------------------------

--
-- Struktur dari tabel `retur_barang`
--

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

--
-- Dumping data untuk tabel `retur_barang`
--

INSERT INTO `retur_barang` (`id_retur`, `id_barang`, `qty_retur`, `tanggal_retur`, `alasan_retur`, `id_user`, `supplier`, `harga_satuan`, `total`, `periode`, `id_pesanan`) VALUES
(1, 14, 3, '2025-06-05 17:03:45', 'ok', 22, 'rudy', 3000.00, 9000.00, 1, 28),
(2, 14, 2, '2025-06-05 17:32:00', 'basi', 22, 'rudy', 3000.00, 6000.00, 1, 29),
(3, 13, 1, '2025-06-06 10:08:27', 'y', 22, 'rudy', 3000.00, 3000.00, 2, 30);

-- --------------------------------------------------------

--
-- Struktur dari tabel `roles`
--

CREATE TABLE `roles` (
  `id_role` int(11) NOT NULL,
  `nama_role` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `roles`
--

INSERT INTO `roles` (`id_role`, `nama_role`) VALUES
(1, 'admin'),
(2, 'purchasing'),
(3, 'kasir'),
(4, 'headproduksi'),
(5, 'crew');

-- --------------------------------------------------------

--
-- Struktur dari tabel `stok_opname`
--

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

--
-- Dumping data untuk tabel `stok_opname`
--

INSERT INTO `stok_opname` (`id_opname`, `id_barang`, `tanggal_opname`, `stok_fisik`, `stok_sistem`, `selisih`, `jenis`, `id_user`) VALUES
(2, 13, '2025-06-04', 160, 161, -1, 'kerugian', 22);

-- --------------------------------------------------------

--
-- Struktur dari tabel `supplier`
--

CREATE TABLE `supplier` (
  `id_supplier` int(11) NOT NULL,
  `nama_supplier` varchar(100) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `kontak` varchar(50) DEFAULT NULL,
  `bahan_baku` text DEFAULT NULL,
  `satuan` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `supplier`
--

INSERT INTO `supplier` (`id_supplier`, `nama_supplier`, `alamat`, `kontak`, `bahan_baku`, `satuan`) VALUES
(3, 'rudy', 'jalan aren 1', '0812212121', 'gula, kopi, teh, susu', 'gr, pack, pack, pack');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

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

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id_user`, `username`, `password`, `role_id`, `nama_lengkap`, `jenis_kelamin`, `alamat_user`, `no_tlp_user`, `tanggal_daftar`) VALUES
(22, 'admin', 'admin', 1, 'Admin', 'Laki - Laki', 'test', 123, '2025-05-22 18:48:41'),
(24, 'kasir', 'kasir', 3, 'Kasir', 'Perempuan', 'testalamat', 32221, '2025-05-23 04:11:58'),
(25, 'headproduksi', 'headproduksi', 4, 'headproduksi', 'Laki - Laki', 'alamattest', 563442, '2025-05-23 04:12:51'),
(26, 'purchasing', 'purchasing', 2, 'Staff Purchasing', 'Perempuan', 'alamat 312', 83914, '2025-05-23 04:13:22'),
(27, 'crew', 'crew', 5, 'crew', 'Laki - Laki', NULL, NULL, '2025-05-23 04:14:12');

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `v_bahan_baku_report`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `v_bahan_baku_report` (
`id_bahan_baku` int(11)
,`nama_barang` varchar(100)
,`jumlah_total` int(11)
,`jumlah_retur` int(11)
,`jumlah_masuk` int(11)
,`status` enum('pending','approved','retur','dibatalkan')
,`tanggal_input` datetime
,`catatan_retur` text
,`nama_pengguna` varchar(100)
);

-- --------------------------------------------------------

--
-- Struktur untuk view `v_bahan_baku_report`
--
DROP TABLE IF EXISTS `v_bahan_baku_report`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_bahan_baku_report`  AS SELECT `bb`.`id_bahan_baku` AS `id_bahan_baku`, `b`.`nama_barang` AS `nama_barang`, `bb`.`qty` AS `jumlah_total`, coalesce(`bb`.`jumlah_retur`,0) AS `jumlah_retur`, coalesce(`bb`.`jumlah_masuk`,0) AS `jumlah_masuk`, `bb`.`status` AS `status`, `bb`.`tanggal_input` AS `tanggal_input`, `bb`.`catatan_retur` AS `catatan_retur`, `u`.`nama_lengkap` AS `nama_pengguna` FROM ((`bahan_baku` `bb` join `barang` `b` on(`bb`.`id_barang` = `b`.`id_barang`)) left join `users` `u` on(`bb`.`id_user` = `u`.`id_user`)) ;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `aktor`
--
ALTER TABLE `aktor`
  ADD PRIMARY KEY (`id_aktor`);

--
-- Indeks untuk tabel `bahan_baku`
--
ALTER TABLE `bahan_baku`
  ADD PRIMARY KEY (`id_bahan_baku`),
  ADD KEY `id_barang` (`id_barang`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `idx_bahan_baku_periode` (`periode`),
  ADD KEY `idx_bahan_baku_status` (`status`),
  ADD KEY `idx_bahan_baku_id_pesanan` (`id_pesanan`);

--
-- Indeks untuk tabel `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`id_barang`),
  ADD KEY `fk_barang_supplier` (`id_supplier`);

--
-- Indeks untuk tabel `barang_keluar`
--
ALTER TABLE `barang_keluar`
  ADD PRIMARY KEY (`id_keluar`),
  ADD KEY `id_barang` (`id_barang`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `barang_masuk`
--
ALTER TABLE `barang_masuk`
  ADD PRIMARY KEY (`id_masuk`),
  ADD KEY `id_barang` (`id_barang`),
  ADD KEY `id_supplier` (`id_supplier`),
  ADD KEY `barang_masuk_ibfk_3` (`id_user`);

--
-- Indeks untuk tabel `barang_supplier`
--
ALTER TABLE `barang_supplier`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_barang` (`id_barang`),
  ADD KEY `id_supplier` (`id_supplier`);

--
-- Indeks untuk tabel `data_toko`
--
ALTER TABLE `data_toko`
  ADD PRIMARY KEY (`id_toko`);

--
-- Indeks untuk tabel `detail_keluar`
--
ALTER TABLE `detail_keluar`
  ADD PRIMARY KEY (`id_detail_keluar`),
  ADD KEY `id_pengeluaran` (`id_pengeluaran`),
  ADD KEY `id_barang` (`id_barang`);

--
-- Indeks untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD PRIMARY KEY (`id_detail_pesanan`),
  ADD KEY `id_pesanan` (`id_pesanan`),
  ADD KEY `id_item` (`id_item`);

--
-- Indeks untuk tabel `detail_terima`
--
ALTER TABLE `detail_terima`
  ADD PRIMARY KEY (`id_detail_terima`),
  ADD KEY `id_penerimaan` (`id_penerimaan`),
  ADD KEY `id_barang` (`id_barang`);

--
-- Indeks untuk tabel `laporan_keluar`
--
ALTER TABLE `laporan_keluar`
  ADD PRIMARY KEY (`id_laporan_keluar`);

--
-- Indeks untuk tabel `laporan_keluar_detail`
--
ALTER TABLE `laporan_keluar_detail`
  ADD PRIMARY KEY (`id_detail_keluar`),
  ADD KEY `id_laporan` (`id_laporan`),
  ADD KEY `id_keluar` (`id_keluar`);

--
-- Indeks untuk tabel `laporan_masuk`
--
ALTER TABLE `laporan_masuk`
  ADD PRIMARY KEY (`id_laporan_masuk`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_laporan_masuk_tanggal_periode` (`tanggal_laporan`,`periode`);

--
-- Indeks untuk tabel `laporan_masuk_detail`
--
ALTER TABLE `laporan_masuk_detail`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_laporan` (`id_laporan`),
  ADD KEY `id_masuk` (`id_masuk`);

--
-- Indeks untuk tabel `laporan_penjualan`
--
ALTER TABLE `laporan_penjualan`
  ADD PRIMARY KEY (`id_laporan`),
  ADD UNIQUE KEY `tanggal` (`tanggal`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `lost_barang`
--
ALTER TABLE `lost_barang`
  ADD PRIMARY KEY (`id_lost`),
  ADD KEY `id_barang` (`id_barang`),
  ADD KEY `dibuat_oleh` (`dibuat_oleh`);

--
-- Indeks untuk tabel `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id_menu`);

--
-- Indeks untuk tabel `penerimaan`
--
ALTER TABLE `penerimaan`
  ADD PRIMARY KEY (`id_penerimaan`),
  ADD KEY `id_supplier` (`id_supplier`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `pengeluaran`
--
ALTER TABLE `pengeluaran`
  ADD PRIMARY KEY (`id_pengeluaran`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `penjualan`
--
ALTER TABLE `penjualan`
  ADD PRIMARY KEY (`id_penjualan`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `penjualan_bahan`
--
ALTER TABLE `penjualan_bahan`
  ADD PRIMARY KEY (`id_penjualan_bahan`),
  ADD KEY `id_penjualan_detail` (`id_penjualan_detail`),
  ADD KEY `id_barang` (`id_barang`);

--
-- Indeks untuk tabel `penjualan_detail`
--
ALTER TABLE `penjualan_detail`
  ADD PRIMARY KEY (`id_penjualan_detail`),
  ADD KEY `id_penjualan` (`id_penjualan`),
  ADD KEY `id_menu` (`id_menu`);

--
-- Indeks untuk tabel `pesanan_barang`
--
ALTER TABLE `pesanan_barang`
  ADD PRIMARY KEY (`id_pesanan`),
  ADD KEY `id_supplier` (`id_supplier`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `pesanan_detail`
--
ALTER TABLE `pesanan_detail`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_pesanan` (`id_pesanan`),
  ADD KEY `id_barang` (`id_barang`);

--
-- Indeks untuk tabel `retur_barang`
--
ALTER TABLE `retur_barang`
  ADD PRIMARY KEY (`id_retur`),
  ADD KEY `id_barang` (`id_barang`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_role`);

--
-- Indeks untuk tabel `stok_opname`
--
ALTER TABLE `stok_opname`
  ADD PRIMARY KEY (`id_opname`),
  ADD KEY `id_barang` (`id_barang`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`id_supplier`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `fk_user_role` (`role_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `aktor`
--
ALTER TABLE `aktor`
  MODIFY `id_aktor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `bahan_baku`
--
ALTER TABLE `bahan_baku`
  MODIFY `id_bahan_baku` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=116;

--
-- AUTO_INCREMENT untuk tabel `barang`
--
ALTER TABLE `barang`
  MODIFY `id_barang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `barang_keluar`
--
ALTER TABLE `barang_keluar`
  MODIFY `id_keluar` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT untuk tabel `barang_masuk`
--
ALTER TABLE `barang_masuk`
  MODIFY `id_masuk` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;

--
-- AUTO_INCREMENT untuk tabel `barang_supplier`
--
ALTER TABLE `barang_supplier`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `data_toko`
--
ALTER TABLE `data_toko`
  MODIFY `id_toko` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `detail_keluar`
--
ALTER TABLE `detail_keluar`
  MODIFY `id_detail_keluar` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `detail_terima`
--
ALTER TABLE `detail_terima`
  MODIFY `id_detail_terima` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `laporan_keluar`
--
ALTER TABLE `laporan_keluar`
  MODIFY `id_laporan_keluar` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT untuk tabel `laporan_masuk`
--
ALTER TABLE `laporan_masuk`
  MODIFY `id_laporan_masuk` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT untuk tabel `laporan_masuk_detail`
--
ALTER TABLE `laporan_masuk_detail`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT untuk tabel `laporan_penjualan`
--
ALTER TABLE `laporan_penjualan`
  MODIFY `id_laporan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=225;

--
-- AUTO_INCREMENT untuk tabel `lost_barang`
--
ALTER TABLE `lost_barang`
  MODIFY `id_lost` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `menu`
--
ALTER TABLE `menu`
  MODIFY `id_menu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `penerimaan`
--
ALTER TABLE `penerimaan`
  MODIFY `id_penerimaan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `pengeluaran`
--
ALTER TABLE `pengeluaran`
  MODIFY `id_pengeluaran` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `penjualan`
--
ALTER TABLE `penjualan`
  MODIFY `id_penjualan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `penjualan_bahan`
--
ALTER TABLE `penjualan_bahan`
  MODIFY `id_penjualan_bahan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `penjualan_detail`
--
ALTER TABLE `penjualan_detail`
  MODIFY `id_penjualan_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `pesanan_barang`
--
ALTER TABLE `pesanan_barang`
  MODIFY `id_pesanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT untuk tabel `pesanan_detail`
--
ALTER TABLE `pesanan_detail`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT untuk tabel `retur_barang`
--
ALTER TABLE `retur_barang`
  MODIFY `id_retur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `stok_opname`
--
ALTER TABLE `stok_opname`
  MODIFY `id_opname` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `supplier`
--
ALTER TABLE `supplier`
  MODIFY `id_supplier` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `bahan_baku`
--
ALTER TABLE `bahan_baku`
  ADD CONSTRAINT `bahan_baku_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `barang`
--
ALTER TABLE `barang`
  ADD CONSTRAINT `fk_barang_supplier` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `barang_keluar`
--
ALTER TABLE `barang_keluar`
  ADD CONSTRAINT `barang_keluar_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`);

--
-- Ketidakleluasaan untuk tabel `barang_masuk`
--
ALTER TABLE `barang_masuk`
  ADD CONSTRAINT `barang_masuk_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`),
  ADD CONSTRAINT `barang_masuk_ibfk_2` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`);

--
-- Ketidakleluasaan untuk tabel `barang_supplier`
--
ALTER TABLE `barang_supplier`
  ADD CONSTRAINT `barang_supplier_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`),
  ADD CONSTRAINT `barang_supplier_ibfk_2` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`);

--
-- Ketidakleluasaan untuk tabel `detail_keluar`
--
ALTER TABLE `detail_keluar`
  ADD CONSTRAINT `detail_keluar_ibfk_1` FOREIGN KEY (`id_pengeluaran`) REFERENCES `pengeluaran` (`id_pengeluaran`),
  ADD CONSTRAINT `detail_keluar_ibfk_2` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`);

--
-- Ketidakleluasaan untuk tabel `detail_terima`
--
ALTER TABLE `detail_terima`
  ADD CONSTRAINT `detail_terima_ibfk_1` FOREIGN KEY (`id_penerimaan`) REFERENCES `penerimaan` (`id_penerimaan`),
  ADD CONSTRAINT `detail_terima_ibfk_2` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`);

--
-- Ketidakleluasaan untuk tabel `laporan_masuk_detail`
--
ALTER TABLE `laporan_masuk_detail`
  ADD CONSTRAINT `laporan_masuk_detail_ibfk_1` FOREIGN KEY (`id_laporan`) REFERENCES `laporan_masuk` (`id_laporan_masuk`) ON DELETE CASCADE,
  ADD CONSTRAINT `laporan_masuk_detail_ibfk_2` FOREIGN KEY (`id_masuk`) REFERENCES `barang_masuk` (`id_masuk`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `laporan_penjualan`
--
ALTER TABLE `laporan_penjualan`
  ADD CONSTRAINT `laporan_penjualan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `lost_barang`
--
ALTER TABLE `lost_barang`
  ADD CONSTRAINT `lost_barang_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`) ON DELETE CASCADE,
  ADD CONSTRAINT `lost_barang_ibfk_2` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id_user`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `penerimaan`
--
ALTER TABLE `penerimaan`
  ADD CONSTRAINT `penerimaan_ibfk_1` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`);

--
-- Ketidakleluasaan untuk tabel `penjualan`
--
ALTER TABLE `penjualan`
  ADD CONSTRAINT `penjualan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `penjualan_bahan`
--
ALTER TABLE `penjualan_bahan`
  ADD CONSTRAINT `penjualan_bahan_ibfk_1` FOREIGN KEY (`id_penjualan_detail`) REFERENCES `penjualan_detail` (`id_penjualan_detail`) ON DELETE CASCADE,
  ADD CONSTRAINT `penjualan_bahan_ibfk_2` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `penjualan_detail`
--
ALTER TABLE `penjualan_detail`
  ADD CONSTRAINT `penjualan_detail_ibfk_1` FOREIGN KEY (`id_penjualan`) REFERENCES `penjualan` (`id_penjualan`) ON DELETE CASCADE,
  ADD CONSTRAINT `penjualan_detail_ibfk_2` FOREIGN KEY (`id_menu`) REFERENCES `menu` (`id_menu`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pesanan_barang`
--
ALTER TABLE `pesanan_barang`
  ADD CONSTRAINT `pesanan_barang_ibfk_1` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`) ON DELETE CASCADE,
  ADD CONSTRAINT `pesanan_barang_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `pesanan_detail`
--
ALTER TABLE `pesanan_detail`
  ADD CONSTRAINT `pesanan_detail_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan_barang` (`id_pesanan`) ON DELETE CASCADE,
  ADD CONSTRAINT `pesanan_detail_ibfk_2` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `retur_barang`
--
ALTER TABLE `retur_barang`
  ADD CONSTRAINT `retur_barang_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`),
  ADD CONSTRAINT `retur_barang_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`);

--
-- Ketidakleluasaan untuk tabel `stok_opname`
--
ALTER TABLE `stok_opname`
  ADD CONSTRAINT `stok_opname_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`);

--
-- Ketidakleluasaan untuk tabel `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id_role`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
