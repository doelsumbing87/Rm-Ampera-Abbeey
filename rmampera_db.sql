-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 04, 2025 at 04:09 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rmampera_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$wTVglAj7ij8hApx7.Nomp.adJAcvsBDQp34XSeoZLMHpdOM1xHJ.q');

-- --------------------------------------------------------

--
-- Table structure for table `detail_transaksi`
--

CREATE TABLE `detail_transaksi` (
  `id` int(11) NOT NULL,
  `transaksi_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga_satuan` decimal(10,2) NOT NULL,
  `total_harga_item` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detail_transaksi`
--

INSERT INTO `detail_transaksi` (`id`, `transaksi_id`, `menu_id`, `jumlah`, `harga_satuan`, `total_harga_item`) VALUES
(1, 1, 5, 1, 28000.00, 28000.00),
(2, 1, 12, 1, 8000.00, 8000.00),
(3, 1, 13, 1, 12000.00, 12000.00),
(4, 1, 9, 1, 10000.00, 10000.00),
(5, 2, 13, 1, 12000.00, 12000.00),
(6, 2, 12, 1, 8000.00, 8000.00),
(7, 2, 14, 1, 15000.00, 15000.00),
(8, 2, 9, 1, 10000.00, 10000.00),
(9, 2, 4, 1, 30000.00, 30000.00);

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `harga` int(11) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `kategori` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu`
--

INSERT INTO `menu` (`id`, `nama`, `harga`, `deskripsi`, `kategori`) VALUES
(1, 'Nasi Goreng', 15000, 'Nasi goreng spesial dengan telur dan ayam', 'Makanan'),
(3, 'Es Teh Manis', 5000, 'Minuman es teh manis segar', 'Minuman'),
(4, 'Nasi Rendang Komplit', 30000, 'Nasi putih dengan rendang sapi empuk, telur balado, sayur nangka, dan sambal hijau.', 'Makanan '),
(5, 'Ayam Pop Khas', 28000, 'Ayam goreng tanpa kulit dengan bumbu khas, lembut di dalam, krispi di luar.', 'Lauk Pauk'),
(6, 'Gulai Kepala Kakap', 45000, 'Kepala ikan kakap dimasak dengan kuah santan kuning kental dan bumbu rempah melimpah.', 'Lauk Pauk'),
(7, 'Dendeng Balado', 35000, 'Irisan daging sapi tipis yang digoreng kering disiram sambal balado merah pedas.', 'Lauk Pauk'),
(8, 'Tunjang (Kikil) Gulai', 32000, 'Potongan kikil sapi yang dimasak empuk dengan kuah gulai kental.', 'Lauk Pauk'),
(9, 'Telur Dadar Padang', 10000, 'Telur dadar tebal khas Padang dengan irisan daun kunyit dan bumbu.', 'Lauk Pauk'),
(10, 'Perkedel Kentang', 6000, 'Perkedel kentang goreng dengan bumbu khas dan irisan seledri.', 'Lauk Pauk'),
(11, 'Sambal Ijo', 8000, 'Sambal cabai hijau khas Padang, cocok untuk menambah selera makan.', 'Lauk Pauk'),
(12, 'Es Teh Manis', 8000, 'Minuman teh manis dingin yang menyegarkan.', 'Minuman'),
(13, 'Es Jeruk', 12000, 'Minuman es jeruk peras segar.', 'Minuman'),
(14, 'Kopi Susu Panas', 15000, 'Kopi hitam khas Padang dicampur susu, disajikan hangat.', 'Minuman'),
(15, 'Teh Talua', 18000, 'Minuman teh dengan campuran kuning telur ayam/bebek, madu, dan gula.', 'Minuman'),
(16, 'Kerupuk Jangek', 7000, 'Kerupuk kulit sapi goreng yang renyah.', 'Camilan'),
(17, 'Kerupuk Merah', 5000, 'Kerupuk ubi merah yang sering disajikan sebagai pelengkap hidangan Padang.', 'Camilan');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL,
  `tanggal_transaksi` datetime NOT NULL,
  `total_keseluruhan` decimal(10,2) NOT NULL,
  `uang_dibayar` decimal(10,2) NOT NULL,
  `kembalian` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id`, `tanggal_transaksi`, `total_keseluruhan`, `uang_dibayar`, `kembalian`, `created_at`) VALUES
(1, '2025-06-04 07:44:41', 58000.00, 100000.00, 42000.00, '2025-06-04 00:44:41'),
(2, '2025-06-04 07:49:37', 75000.00, 100000.00, 25000.00, '2025-06-04 00:49:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaksi_id` (`transaksi_id`),
  ADD KEY `menu_id` (`menu_id`);

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD CONSTRAINT `detail_transaksi_ibfk_1` FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_transaksi_ibfk_2` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
