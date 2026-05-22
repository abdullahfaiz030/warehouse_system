-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 22, 2026 at 06:52 AM
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
-- Database: `warehouse_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `code`, `name`, `created_at`) VALUES
(1, 'MAIN', 'Main Store', '2026-02-03 06:44:48'),
(2, 'CMB-01', 'Main Branch - Colombo', '2026-02-03 06:50:05'),
(3, 'SAM-02', 'Branch - Sainthamaruthu', '2026-02-03 06:50:05'),
(4, 'HTN-03', 'Branch - Hatton', '2026-02-03 06:50:05'),
(6, 'SUPREME-HEAD', 'KINNIYA', '2026-02-10 06:14:54');

-- --------------------------------------------------------

--
-- Table structure for table `movements`
--

CREATE TABLE `movements` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `movement_type` enum('IN','OUT') NOT NULL,
  `qty` int(11) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `movements`
--

INSERT INTO `movements` (`id`, `product_id`, `location_id`, `movement_type`, `qty`, `note`, `created_at`) VALUES
(1, 9, 1, 'IN', 10, 'Stock adjustment from product page', '2026-02-03 06:44:48'),
(2, 9, 1, 'OUT', 6, 'sale', '2026-02-03 06:45:15'),
(3, 9, 3, 'IN', 4, 'Adjustment from product page', '2026-02-03 06:51:54'),
(4, 8, 3, 'IN', 6, 'Adjustment from product page', '2026-02-03 06:52:20'),
(5, 9, 4, 'IN', 8, 'Adjustment from product page', '2026-02-03 07:01:15'),
(6, 9, 3, 'OUT', 2, '', '2026-02-03 08:04:56'),
(8, 9, 3, 'IN', 12, 'Adjustment from product page', '2026-02-03 08:06:43'),
(9, 9, 3, 'OUT', 4, '', '2026-02-03 08:07:07'),
(10, 9, 3, 'IN', 5, '', '2026-02-03 08:08:13'),
(11, 9, 3, 'OUT', 5, '', '2026-02-03 08:13:24'),
(12, 9, 3, 'OUT', 3, '', '2026-02-03 08:29:53'),
(13, 9, 3, 'OUT', 2, '', '2026-02-03 08:35:02'),
(14, 7, 4, 'IN', 8, 'Adjustment from product page', '2026-02-03 08:40:19'),
(17, 9, 4, 'OUT', 1, '', '2026-02-03 08:49:35'),
(18, 9, 4, 'OUT', 2, 'Transferred from Branch - Sainthamaruthu to Branch - Hatton. ', '2026-02-03 08:49:59'),
(19, 9, 3, 'IN', 2, 'Transferred from Branch - Sainthamaruthu to Branch - Hatton. ', '2026-02-03 08:49:59'),
(20, 9, 4, 'OUT', 2, 'Transferred from Branch - Sainthamaruthu to Branch - Hatton. ', '2026-02-03 08:50:29'),
(21, 9, 3, 'IN', 2, 'Transferred from Branch - Sainthamaruthu to Branch - Hatton. ', '2026-02-03 08:50:29'),
(22, 9, 4, 'IN', 10, 'Adjustment from product page', '2026-02-10 05:48:04'),
(23, 8, 3, 'OUT', 4, 'Adjustment from product page', '2026-02-10 05:51:26'),
(24, 11, 6, 'IN', 10, 'Initial stock from product page', '2026-02-10 06:36:04');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `purchase_price` decimal(10,2) DEFAULT 0.00,
  `selling_price` decimal(10,2) DEFAULT 0.00,
  `category` varchar(100) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `min_stock_level` int(11) DEFAULT 5,
  `supplier_name` varchar(100) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `supplier_id` int(11) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `max_stock_level` int(11) DEFAULT 100,
  `weight` decimal(10,2) DEFAULT NULL,
  `unit` varchar(20) DEFAULT 'pcs'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `sku`, `name`, `brand`, `description`, `purchase_price`, `selling_price`, `category`, `unit_price`, `min_stock_level`, `supplier_name`, `status`, `expiry_date`, `created_at`, `supplier_id`, `image_path`, `max_stock_level`, `weight`, `unit`) VALUES
(7, '0110', 'TV', 'LG', '', 40000.00, 100000.00, 'Electronics', 100000.00, 5, 'Ruskan', 'Active', NULL, '2026-01-27 05:36:07', NULL, NULL, 100, NULL, 'pcs'),
(8, '1', 'TV', 'Panasonic', 'OLED Curved 98 Inches Display', 110000.00, 125000.00, 'electric', 125000.00, 1, 'Imran', 'Active', NULL, '2026-02-03 05:36:49', 2, NULL, 100, NULL, 'pcs'),
(9, '002', 'A04s', 'samsung', '256 storage, 16Gb Ram', 30000.00, 50000.00, 'Electronics', 50000.00, 5, 'Ruskan', 'Inactive', NULL, '2026-02-03 06:06:10', 1, NULL, 100, NULL, 'pcs'),
(11, '1234', 'Redmi Note 15 Pro', 'Redmi', '8GB RAM 256GB ROM SNAPDRAGON ELITE 8 GEN', 85000.00, 95000.00, '', 95000.00, 5, 'Rikas', 'Active', NULL, '2026-02-10 06:36:04', 3, NULL, 100, NULL, 'pcs');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `location_id` int(11) NOT NULL,
  `payment_method` enum('Cash','Card','Transfer') NOT NULL,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `discount_amount` decimal(15,2) DEFAULT 0.00,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `net_amount` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `invoice_no`, `location_id`, `payment_method`, `total_amount`, `discount_amount`, `tax_amount`, `net_amount`, `created_at`) VALUES
(1, 'INV-20260210063108', 3, 'Cash', 150000.00, 10000.00, 0.00, 140000.00, '2026-02-10 05:31:08'),
(2, 'INV-20260210073747', 6, 'Cash', 570000.00, 5000.00, 2825.00, 567825.00, '2026-02-10 06:37:47'),
(3, 'INV-20260214120749', 4, 'Cash', 100000.00, 0.00, 0.00, 100000.00, '2026-02-14 11:07:49');

-- --------------------------------------------------------

--
-- Table structure for table `sales_items`
--

CREATE TABLE `sales_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_items`
--

INSERT INTO `sales_items` (`id`, `sale_id`, `product_id`, `qty`, `unit_price`, `subtotal`) VALUES
(1, 1, 9, 3, 50000.00, 150000.00),
(2, 2, 11, 6, 95000.00, 570000.00),
(3, 3, 7, 1, 100000.00, 100000.00);

-- --------------------------------------------------------

--
-- Table structure for table `stock`
--

CREATE TABLE `stock` (
  `product_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock`
--

INSERT INTO `stock` (`product_id`, `location_id`, `quantity`) VALUES
(7, 4, 7),
(8, 3, 2),
(9, 1, 4),
(9, 3, 6),
(9, 4, 13),
(11, 6, 4);

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `phone`, `email`, `address`, `created_at`) VALUES
(1, 'Ruskan', NULL, NULL, NULL, NULL, '2026-02-10 05:48:04'),
(2, 'Imran', NULL, NULL, NULL, NULL, '2026-02-10 05:51:26'),
(3, 'Rikas', NULL, NULL, NULL, NULL, '2026-02-10 06:36:04');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Warehouse Staff','Cashier') NOT NULL DEFAULT 'Warehouse Staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$cwwL1vVmgr3Z6a0AlyZiSO3ezzERbow8hyHWzqbLX1aSd2oNuz2ZK', 'Admin', '2026-02-10 05:26:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `movements`
--
ALTER TABLE `movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mov_product` (`product_id`),
  ADD KEY `fk_mov_location` (`location_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `fk_product_supplier` (`supplier_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no` (`invoice_no`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `sales_items`
--
ALTER TABLE `sales_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `stock`
--
ALTER TABLE `stock`
  ADD PRIMARY KEY (`product_id`,`location_id`),
  ADD KEY `fk_stock_location` (`location_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `movements`
--
ALTER TABLE `movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sales_items`
--
ALTER TABLE `sales_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `movements`
--
ALTER TABLE `movements`
  ADD CONSTRAINT `fk_mov_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mov_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`);

--
-- Constraints for table `sales_items`
--
ALTER TABLE `sales_items`
  ADD CONSTRAINT `sales_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `stock`
--
ALTER TABLE `stock`
  ADD CONSTRAINT `fk_stock_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_stock_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
