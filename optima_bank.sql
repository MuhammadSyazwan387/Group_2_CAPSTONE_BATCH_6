-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 25, 2025 at 12:12 PM
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
-- Database: `optima_bank`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `voucher_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `voucher_id`, `user_id`, `quantity`) VALUES
(19, 2, 1, 1),
(20, 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `cart_item_history`
--

CREATE TABLE `cart_item_history` (
  `id` int(11) NOT NULL,
  `voucher_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `completed_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_item_history`
--

INSERT INTO `cart_item_history` (`id`, `voucher_id`, `user_id`, `quantity`, `completed_date`) VALUES
(1, 2, 1, 1, '2025-09-24'),
(2, 3, 1, 2, '2025-09-24'),
(3, 2, 1, 2, '2025-09-24'),
(4, 3, 1, 1, '2025-09-24'),
(5, 2, 1, 1, '2025-09-24'),
(6, 3, 1, 2, '2025-09-24'),
(7, 2, 1, 1, '2025-09-25'),
(8, 2, 1, 1, '2025-09-25'),
(9, 2, 1, 1, '2025-09-25'),
(10, 2, 1, 1, '2025-09-25'),
(11, 2, 1, 1, '2025-09-25');

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`id`, `name`) VALUES
(1, 'Food and Drinks');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `email` varchar(255) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `fullname` varchar(50) NOT NULL,
  `phone_number` varchar(13) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `profile_image` mediumblob DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `address` varchar(255) NOT NULL,
  `about_me` varchar(255) DEFAULT NULL,
  `id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`email`, `google_id`, `fullname`, `phone_number`, `password`, `profile_image`, `is_active`, `points`, `address`, `about_me`, `id`, `created_at`) VALUES
('dukun123@gmail.com', NULL, 'SHAZARIF AFWAN', '', '$2y$10$neNCk44qgbVSfVYypfD0WOXzN5hWqrnR9VfKqPnzr3Cnu8SAtB0aO', NULL, 1, 900, '', NULL, 1, '2025-09-15 15:24:21'),
('abc123@gmail.com', NULL, 'Muhammad Syazwan', '', '$2y$10$ADf0zeVvveAaMb5zeB2xOOx6NI7YVKrFyyOjbol3S1e3c5EJHq9vq', NULL, 1, 0, '', NULL, 14, '2025-09-17 10:48:40');

-- --------------------------------------------------------

--
-- Table structure for table `voucher`
--

CREATE TABLE `voucher` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `image` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `terms_and_condition` varchar(500) NOT NULL,
  `total_redeem` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `voucher`
--

INSERT INTO `voucher` (`id`, `category_id`, `points`, `title`, `image`, `description`, `terms_and_condition`, `total_redeem`) VALUES
(1, 1, 50, 'Zus Coffee Free Hot Cup', 'images/zus_voucher.jpg', 'Enjoy premium coffee at ZUS Coffee with this exclusive e-voucher! Perfect for your daily caffeine fix or treating a friend to quality artisanal coffee.', 'TERMS & CONDITIONS - ZUS Coffee Dine-In e-Voucher\r\n\r\nVALIDITY & USAGE:\r\n• Valid for DINE-IN ONLY at participating ZUS Coffee outlets\r\n• Cannot be used for takeaway, delivery, or drive-through orders\r\n• Must be consumed on the premises\r\n• Valid for 12 months from date of purchase\r\n• One-time use only, cannot be refunded or exchanged for cash\r\n\r\nRESTRICTIONS:\r\n• STRICTLY DINE-IN ONLY - No takeaway or delivery permitted\r\n• Valid during regular operating hours only\r\n• Not valid during special events', 0),
(2, 1, 100, 'KFC RM5 Voucher', 'images/kfc_voucher_RM5.jpg', 'Enjoy delicious KFC meals at a discounted price! Redeem this voucher and get RM5 off your next purchase at participating KFC outlets. Perfect for satisfying your cravings for crispy fried chicken and more.', 'This voucher is worth RM5 and can be used at participating KFC outlets in Malaysia.\r\n\r\nVoucher is valid for dine-in and takeaway only (not applicable for delivery unless stated).\r\n\r\nOnly one voucher can be used per transaction.\r\n\r\nVoucher is non-refundable, non-exchangeable, and cannot be redeemed for cash.\r\n\r\nNot valid with other promotions, discounts, or set meals unless otherwise stated.\r\n\r\nKFC reserves the right to change the terms & conditions without prior notice.\r\n\r\nThe image of food is f', 9),
(3, 1, 100, 'Tealive RM10 Voucher', 'images/tealive_voucher.jpg', 'Sip, relax, and enjoy your favorite Tealive drinks with this RM10 voucher. Whether you love bubble milk tea, smoothies, or refreshing fruit teas, this voucher is the perfect treat for yourself or a friend. Redeemable at participating Tealive outlets natio', 'This voucher is worth RM10 and can be used at participating Tealive outlets in Malaysia.\r\n\r\nValid for any drink purchase unless otherwise stated.\r\n\r\nVoucher must be presented before payment.\r\n\r\nOnly one voucher can be redeemed per transaction.\r\n\r\nVoucher is non-refundable, non-transferable, and cannot be exchanged for cash.\r\n\r\nNot valid with other promotions, discounts, or special offers unless specified.\r\n\r\nTealive reserves the right to amend the terms & conditions without prior notice.\r\n\r\nImag', 5);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `voucher_id` (`voucher_id`);

--
-- Indexes for table `cart_item_history`
--
ALTER TABLE `cart_item_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `voucher_id` (`voucher_id`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `google_id` (`google_id`),
  ADD KEY `phone_number_2` (`phone_number`);

--
-- Indexes for table `voucher`
--
ALTER TABLE `voucher`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `cart_item_history`
--
ALTER TABLE `cart_item_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `voucher`
--
ALTER TABLE `voucher`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`voucher_id`) REFERENCES `voucher` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `cart_item_history`
--
ALTER TABLE `cart_item_history`
  ADD CONSTRAINT `cart_item_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cart_item_history_ibfk_2` FOREIGN KEY (`voucher_id`) REFERENCES `voucher` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `voucher`
--
ALTER TABLE `voucher`
  ADD CONSTRAINT `voucher_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
