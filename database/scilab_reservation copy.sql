-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3307/
-- Generation Time: Dec 09, 2025 at 11:34 AM
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
-- Database: `scilab_reservation`
--

-- --------------------------------------------------------

--
-- Table structure for table `chemicals`
--

CREATE TABLE `chemicals` (
  `chemical_id` int(11) NOT NULL,
  `chemical_name` varchar(100) NOT NULL,
  `formula` varchar(50) DEFAULT NULL,
  `stock_quantity` decimal(10,2) DEFAULT NULL,
  `unit` varchar(20) DEFAULT 'mL'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chemicals`
--

INSERT INTO `chemicals` (`chemical_id`, `chemical_name`, `formula`, `stock_quantity`, `unit`) VALUES
(12, 'Hydrochloric acid', 'HCl', 250.00, 'g'),
(13, 'Sulfuric acid', 'H₂SO₄', 200.00, 'g'),
(14, 'Nitric acid', 'HNO₃', 150.00, 'g'),
(15, 'Acetic acid', 'CH₃COOH', 171.00, 'g'),
(16, 'Sodium hydroxide', 'NaOH', 250.00, 'g'),
(17, 'Potassium hydroxide', 'KOH', 200.00, 'g'),
(18, 'Ammonium hydroxide', 'NH₄OH', 100.00, 'g'),
(19, 'Sodium chloride', 'NaCl', 300.00, 'g'),
(20, 'Copper(II) sulfate', 'CuSO₄·5H₂O', 100.00, 'g'),
(21, 'Calcium carbonate', 'CaCO₃', 200.00, 'g'),
(22, 'Potassium permanganate', 'KMnO₄', 49.00, 'g');

-- --------------------------------------------------------

--
-- Table structure for table `chemical_usage`
--

CREATE TABLE `chemical_usage` (
  `usage_id` int(11) NOT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `chemical_id` int(11) DEFAULT NULL,
  `quantity_used` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chemical_usage`
--

INSERT INTO `chemical_usage` (`usage_id`, `reservation_id`, `chemical_id`, `quantity_used`) VALUES
(19, 46, 15, 6.00);

-- --------------------------------------------------------

--
-- Table structure for table `lab_assets`
--

CREATE TABLE `lab_assets` (
  `asset_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `category` enum('Glassware','Equipment') NOT NULL,
  `total_stock` int(11) DEFAULT 0,
  `available_stock` int(11) DEFAULT 0,
  `condition_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_assets`
--

INSERT INTO `lab_assets` (`asset_id`, `item_name`, `category`, `total_stock`, `available_stock`, `condition_notes`) VALUES
(1, 'Beaker', 'Glassware', 10, -10, 'Good'),
(2, 'Erlenmeyer flask', 'Glassware', 10, 10, 'Good'),
(3, 'Volumetric flask', 'Glassware', 5, 5, 'Good'),
(4, 'Graduated cylinder', 'Glassware', 8, 8, 'Good'),
(5, 'Test tube', 'Glassware', 20, 20, 'Good'),
(6, 'Reagent bottle', 'Glassware', 12, 12, 'Good'),
(7, 'Pipette', 'Glassware', 15, 15, 'Good'),
(8, 'Burette', 'Glassware', 6, -6, 'Good'),
(9, 'Dropper', 'Glassware', 12, 9, 'Good'),
(10, 'Watch glass', 'Glassware', 12, 10, 'Good'),
(11, 'Bunsen burner', 'Equipment', 6, -4, 'Good'),
(12, 'Hot plate', 'Equipment', 4, -2, 'Good'),
(13, 'Alcohol lamp', 'Equipment', 4, -12, 'Good'),
(14, 'Weighing scale', 'Equipment', 2, 2, 'Good'),
(15, 'Thermometer', 'Equipment', 10, 20, 'Good'),
(16, 'Microscope', 'Equipment', 6, 6, 'Good'),
(17, 'pH meter', 'Equipment', 2, -4, 'Good'),
(18, 'Iron stand & ring stand', 'Equipment', 10, 0, 'Good'),
(19, 'Wire gauze', 'Equipment', 10, 5, 'Good'),
(20, 'Test tube rack', 'Equipment', 10, 10, 'Good'),
(21, 'Clamp and holder', 'Equipment', 12, 11, 'Good'),
(22, 'Tongs', 'Equipment', 8, 8, 'Good'),
(23, 'Mortar and pestle', 'Equipment', 4, 4, 'Good');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `reservation_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `reservation_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `year` text DEFAULT NULL,
  `professor_approval` enum('Pending','Approved','Completed','Cancelled','Declined') DEFAULT current_timestamp(),
  `section` varchar(255) DEFAULT NULL,
  `professor` varchar(255) DEFAULT NULL,
  `admin_approval` enum('Pending','Approved','Completed','Cancelled','Declined') DEFAULT 'Pending',
  `additional_note` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Ongoing','Partially Returned','Completed') NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`reservation_id`, `user_id`, `room_id`, `reservation_date`, `start_time`, `end_time`, `year`, `professor_approval`, `section`, `professor`, `admin_approval`, `additional_note`, `status`) VALUES
(40, 5, 7, '2025-12-09', '11:52:00', '00:52:00', NULL, 'Approved', NULL, 'prof prof', 'Approved', NULL, 'Completed'),
(41, 5, 7, '2025-12-09', '12:00:00', '13:00:00', NULL, 'Approved', NULL, 'prof prof', 'Approved', NULL, 'Completed'),
(42, 5, NULL, '2025-12-09', '13:35:00', '13:38:00', NULL, 'Approved', NULL, 'prof prof', 'Approved', NULL, 'Completed'),
(43, 5, 7, '2025-12-09', '13:35:00', '13:35:00', NULL, 'Approved', NULL, 'prof prof', 'Approved', NULL, 'Completed'),
(44, 1, NULL, '2025-12-09', '13:47:00', '13:49:00', '1st Year', 'Approved', '1A', 'Dr. Dua', 'Approved', NULL, 'Completed'),
(45, 1, NULL, '2025-12-09', '13:55:00', '13:56:00', '1st Year', 'Approved', '1A', 'Dr. Dua', 'Approved', NULL, 'Completed'),
(46, 1, NULL, '2025-12-09', '14:12:00', '14:13:00', '1st Year', 'Approved', '1A', 'Joshua Orlina', 'Approved', NULL, 'Completed'),
(47, 1, NULL, '2025-12-09', '14:14:00', '14:15:00', '1st Year', 'Approved', '1A', 'Joshua Orlina', 'Approved', NULL, 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `reservation_items`
--

CREATE TABLE `reservation_items` (
  `detail_id` int(11) NOT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `asset_id` int(11) DEFAULT NULL,
  `quantity_borrowed` int(11) DEFAULT NULL,
  `is_returned` tinyint(1) DEFAULT 0,
  `quantity_returned` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservation_items`
--

INSERT INTO `reservation_items` (`detail_id`, `reservation_id`, `asset_id`, `quantity_borrowed`, `is_returned`, `quantity_returned`) VALUES
(48, 40, 19, 5, 0, 5),
(49, 41, 10, 1, 0, 1),
(50, 42, 9, 1, 0, 1),
(51, 42, 10, 1, 0, 1),
(52, 44, 5, 4, 0, 4),
(53, 45, 10, 1, 0, 1),
(54, 47, 23, 4, 0, 4);

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `room_name` varchar(50) NOT NULL,
  `capacity` int(11) DEFAULT 30,
  `status` enum('Available','Maintenance','Occupied','Over Time') DEFAULT 'Available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`room_id`, `room_name`, `capacity`, `status`) VALUES
(5, 'Chemistry Laboratory', 30, 'Available'),
(6, 'Physics Laboratory', 30, 'Available'),
(7, 'Biology Laboratory', 30, 'Occupied'),
(8, 'Lecture Room', 50, 'Available');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Professor','Student') NOT NULL DEFAULT 'Student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `firstname`, `lastname`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'a', 'a', 'a', 'a@gmail.com', '$2y$10$Xoh9kVDPdQsDCRO6mKqsceKUp6jq5dmLzmFiiJh4f1fZ4eVDH9vty', 'Student', '2025-12-07 08:16:53'),
(2, 's', 's', 's', 's@gmail.com', '$2y$10$7/4R6XcTjlu/zMXxVVR/c.9URHrGMTknmnIItpGkxAmcXeBw6j3IG', 'Student', '2025-12-07 08:29:34'),
(3, 'v', 'v', 'v', 'v@gmail.com', '$2y$10$AvT9TT1CctQQUlARt6IdJe36Z90DLqxrKvSbME.Zem7dMhm/mJXd.', 'Admin', '2025-12-07 08:33:32'),
(4, 'jrofuoco', 'Joshua', 'Orlina', 'jsohuaorlina08@gmail.com', '$2y$10$5XEnMA5NCMjeRupNJggjIOdChgugv3TN2jlZ2TeaiZEHE5Bi9Kh7e', 'Student', '2025-12-07 08:50:37'),
(5, 'prof', 'prof', 'prof', 'prof@professor.com', '$2y$10$AVDB9ivpIaPVdfvndb66XuMhbKfWabTWMZZyHy7U988GeoWPj6TYO', 'Professor', '2025-12-08 04:17:02'),
(6, 'Joshua', 'Joshua', 'Orlina', 'joshuaorlina08@gmail.com', '$2y$10$/GSxdLd08TeMYuwHjvuUVem3hIuTcD7OkTjsBMEJV4L/0gscDmyWW', 'Professor', '2025-12-08 08:40:45');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chemicals`
--
ALTER TABLE `chemicals`
  ADD PRIMARY KEY (`chemical_id`);

--
-- Indexes for table `chemical_usage`
--
ALTER TABLE `chemical_usage`
  ADD PRIMARY KEY (`usage_id`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `chemical_id` (`chemical_id`);

--
-- Indexes for table `lab_assets`
--
ALTER TABLE `lab_assets`
  ADD PRIMARY KEY (`asset_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `reservation_items`
--
ALTER TABLE `reservation_items`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chemicals`
--
ALTER TABLE `chemicals`
  MODIFY `chemical_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `chemical_usage`
--
ALTER TABLE `chemical_usage`
  MODIFY `usage_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `lab_assets`
--
ALTER TABLE `lab_assets`
  MODIFY `asset_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `reservation_items`
--
ALTER TABLE `reservation_items`
  MODIFY `detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chemical_usage`
--
ALTER TABLE `chemical_usage`
  ADD CONSTRAINT `chemical_usage_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`),
  ADD CONSTRAINT `chemical_usage_ibfk_2` FOREIGN KEY (`chemical_id`) REFERENCES `chemicals` (`chemical_id`);

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`);

--
-- Constraints for table `reservation_items`
--
ALTER TABLE `reservation_items`
  ADD CONSTRAINT `reservation_items_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`),
  ADD CONSTRAINT `reservation_items_ibfk_2` FOREIGN KEY (`asset_id`) REFERENCES `lab_assets` (`asset_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
