-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 11, 2025 at 12:12 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `volunteer_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `volunteers`
--

CREATE TABLE `volunteers` (
  `id` int NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `kontak` varchar(20) DEFAULT NULL,
  `keahlian` varchar(100) DEFAULT NULL,
  `bio` text,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `volunteers`
--

INSERT INTO `volunteers` (`id`, `nama`, `email`, `password`, `kontak`, `keahlian`, `bio`, `role`, `created_at`) VALUES
(1, 'Adminmin', 'adminVolunteers@gmail.com', '$2y$10$FyEiMZlsacjJ0YCITnLiQO04zcvAuYWSTPdCkhIiHKJGbzB54RjXO', '08123456789', 'Koordinasi', 'Administrator utama', 'admin', '2025-06-08 21:54:32'),
(3, 'Okta', 'safitriokta131@gmail.com', '$2y$10$NXo.twB6uT9KYeUjXL.GoeOKGsf8N.dUx9QsrRSBPYJdXXnKpj1gK', '089633951775', 'Empati dan Kasih sayang', 'Volunteer muda peduli untuk semua', 'user', '2025-06-08 22:26:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `volunteers`
--
ALTER TABLE `volunteers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `volunteers`
--
ALTER TABLE `volunteers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
