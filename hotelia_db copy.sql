-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for hotelia_db
CREATE DATABASE IF NOT EXISTS `hotelia_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `hotelia_db`;

-- Dumping structure for table hotelia_db.accounts
CREATE TABLE IF NOT EXISTS `accounts` (
  `account_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `account_type` enum('staff','guest') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`account_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.accounts: ~7 rows (approximately)
INSERT INTO `accounts` (`account_id`, `username`, `password`, `account_type`, `created_at`) VALUES
	(1, 'loki', '$2y$10$8aSb4EJat1Pci73csLoXtevrVxoZnWf1o2ESKNcYKGWL88mWnyfq2', 'guest', '2025-09-16 05:59:12'),
	(2, 'LokiMobile', '$2y$10$MEYg9zMvgkK9MQ.LAMc0t.cXL5kF7CqByyTAcewup2JehXTEl/j5O', 'guest', '2025-09-29 15:30:56'),
	(4, 'reception_staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', '2025-10-28 14:41:50'),
	(6, 'niel123', '$2y$10$VUadkOQUcbvxOdMLsgB1becZt6U7XAMDK3V2bb1rVOBI94GdkRTjy', 'guest', '2025-11-04 05:21:12'),
	(7, 'lokiit', '$2y$10$9Zw2a3vts.oLgt67hQd9x.mB9lE9vYINalHrg2nbXo22av6MSM8sa', 'guest', '2025-11-04 13:54:51'),
	(8, 'kd', '$2y$10$vqGnVkJmiD5JZdfQL0CI6eqc.4oiPyYIUU6veN1MV7fWcns7JdXTC', 'guest', '2025-11-05 03:17:47'),
	(9, 'alvin', '$2y$10$dROCyMOm82tfJmCQEv0xHOTb5fhFTl5pSTWMjWBl4YiU3uR/GGi6q', 'guest', '2025-11-05 03:20:38');

-- Dumping structure for table hotelia_db.amenities
CREATE TABLE IF NOT EXISTS `amenities` (
  `amenity_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`amenity_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.amenities: ~20 rows (approximately)
INSERT INTO `amenities` (`amenity_id`, `name`, `icon`) VALUES
	(5, 'WiFi', 'wifi'),
	(6, 'Air Conditioning', 'snowflake'),
	(7, 'Television', 'tv'),
	(8, 'Hot and Cold Shower', 'droplet'),
	(9, 'Refrigerator', 'fridge'),
	(10, 'Mini Bar', 'coffee'),
	(11, 'Towels', 'towel'),
	(12, 'Toiletries', 'soap'),
	(13, 'Wardrobe or Closet', 'hanger'),
	(14, 'Balcony or Terrace', 'sun'),
	(15, 'Desk and Chair', 'briefcase'),
	(16, 'Complimentary Breakfast', 'coffee-cup'),
	(17, 'Room Service', 'bell'),
	(18, 'Safety Deposit Box', 'shield'),
	(19, 'Electric Kettle', 'cup-saucer'),
	(20, 'Free Parking', 'car'),
	(21, '24-hour Reception', 'clock'),
	(22, 'Laundry Service', 'wash'),
	(23, 'Swimming Pool Access', 'waves'),
	(24, 'Fitness Gym Access', 'dumbbell');

-- Dumping structure for table hotelia_db.bed_types
CREATE TABLE IF NOT EXISTS `bed_types` (
  `bed_type_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`bed_type_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.bed_types: ~6 rows (approximately)
INSERT INTO `bed_types` (`bed_type_id`, `name`) VALUES
	(10, 'Bunk Bed'),
	(7, 'Double Bed'),
	(9, 'King Bed'),
	(8, 'Queen Bed'),
	(5, 'Single Bed'),
	(6, 'Twin Bed');

-- Dumping structure for table hotelia_db.bookings
CREATE TABLE IF NOT EXISTS `bookings` (
  `booking_id` int NOT NULL AUTO_INCREMENT,
  `reference_no` varchar(30) DEFAULT NULL,
  `guest_id` int NOT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `nights` int NOT NULL,
  `base_amount` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending_verification','pending_payment','pending','confirmed','assigned','checked_in','checked_out','cancelled') DEFAULT 'pending_verification',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `tax_amount` decimal(10,2) DEFAULT '0.00',
  `extra_person_charges` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`booking_id`),
  UNIQUE KEY `reference_no` (`reference_no`),
  KEY `guest_id` (`guest_id`),
  KEY `idx_booking_dates` (`check_in_date`,`check_out_date`),
  KEY `idx_room_type_dates` (`check_in_date`,`check_out_date`),
  CONSTRAINT `fk_bookings_guests` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.bookings: ~3 rows (approximately)
INSERT INTO `bookings` (`booking_id`, `reference_no`, `guest_id`, `check_in_date`, `check_out_date`, `nights`, `base_amount`, `total_amount`, `status`, `created_at`, `tax_amount`, `extra_person_charges`) VALUES
	(1, 'HTL251108-1383', 1, '2025-11-08', '2025-11-09', 1, 5500.00, 6160.00, 'confirmed', '2025-11-08 07:14:25', 660.00, 0.00),
	(2, 'HTL251108-3178', 1, '2025-11-08', '2025-11-10', 2, 3000.00, 3360.00, 'checked_in', '2025-11-08 07:21:39', 360.00, 0.00),
	(3, 'HTL251108-3559', 1, '2025-11-08', '2025-11-10', 2, 3200.00, 3584.00, 'confirmed', '2025-11-08 07:24:58', 384.00, 0.00),
	(4, 'HTL251118-2404', 1, '2025-11-19', '2025-11-20', 1, 3000.00, 4032.00, 'cancelled', '2025-11-18 13:16:18', 432.00, 600.00);

-- Dumping structure for table hotelia_db.booking_items
CREATE TABLE IF NOT EXISTS `booking_items` (
  `booking_item_id` int NOT NULL AUTO_INCREMENT,
  `booking_id` int NOT NULL,
  `room_type_id` int NOT NULL,
  `room_id` int DEFAULT NULL,
  `adults` int DEFAULT '1',
  `children` int DEFAULT '0',
  `item_price` decimal(10,2) NOT NULL,
  `status` enum('pending_verification','pending_payment','pending','confirmed','assigned','checked_in','checked_out','cancelled') DEFAULT 'pending_verification',
  `base_price` decimal(10,2) DEFAULT '0.00',
  `extra_person_charges` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`booking_item_id`),
  KEY `room_type_id` (`room_type_id`),
  KEY `room_id` (`room_id`),
  KEY `idx_booking_item_status` (`booking_id`,`status`),
  CONSTRAINT `booking_items_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  CONSTRAINT `booking_items_ibfk_2` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`room_type_id`),
  CONSTRAINT `booking_items_ibfk_3` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.booking_items: ~6 rows (approximately)
INSERT INTO `booking_items` (`booking_item_id`, `booking_id`, `room_type_id`, `room_id`, `adults`, `children`, `item_price`, `status`, `base_price`, `extra_person_charges`) VALUES
	(1, 1, 10, NULL, 1, 0, 5500.00, 'confirmed', 5500.00, 0.00),
	(2, 2, 4, NULL, 1, 0, 3000.00, 'checked_in', 3000.00, 0.00),
	(3, 3, 14, NULL, 1, 1, 1600.00, 'confirmed', 1600.00, 0.00),
	(4, 3, 14, NULL, 1, 0, 1600.00, 'confirmed', 1600.00, 0.00),
	(5, 4, 4, NULL, 3, 0, 1800.00, 'cancelled', 1500.00, 300.00),
	(6, 4, 4, NULL, 1, 2, 1800.00, 'cancelled', 1500.00, 300.00);

-- Dumping structure for table hotelia_db.guests
CREATE TABLE IF NOT EXISTS `guests` (
  `guest_id` int NOT NULL AUTO_INCREMENT,
  `account_id` int DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `guest_type` enum('room_guest','fnb_guest') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`guest_id`),
  UNIQUE KEY `account_id` (`account_id`),
  CONSTRAINT `fk_guests_accounts` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.guests: ~7 rows (approximately)
INSERT INTO `guests` (`guest_id`, `account_id`, `full_name`, `phone`, `email`, `guest_type`, `created_at`) VALUES
	(1, 1, 'Loki Baltazar', '09917344444', 'loki@gmail.com', 'room_guest', '2025-09-16 05:59:12'),
	(2, 2, 'Loki Mobile Test', '09146774461', 'lokimobile@gmail.com', 'room_guest', '2025-09-29 15:30:56'),
	(3, 6, 'niel alvin barangan', '09164424647', 'nielbars@gmail.com', 'room_guest', '2025-11-04 05:21:12'),
	(4, 7, 'lokiit jasjas', '09912334455', 'lokiit@gmail.com', 'room_guest', '2025-11-04 13:54:51'),
	(5, NULL, 'Rodolfo Antonio Baltazar', '09917344444', 'lckybaltazar@gmail.com', 'room_guest', '2025-11-04 14:58:34'),
	(6, 8, 'khyle sibucao', '09123456789', 'kd@a.com', 'room_guest', '2025-11-05 03:17:47'),
	(7, 9, 'alvin barangan', '09923096293', 'example@gmail.com', 'room_guest', '2025-11-05 03:20:38');

-- Dumping structure for table hotelia_db.guest_details
CREATE TABLE IF NOT EXISTS `guest_details` (
  `detail_id` int NOT NULL AUTO_INCREMENT,
  `guest_id` int NOT NULL,
  `id_type` varchar(50) DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `address` text,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `emergency_contact_number` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `id_document_photo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`detail_id`),
  UNIQUE KEY `unique_guest_id` (`guest_id`),
  KEY `guest_id` (`guest_id`),
  CONSTRAINT `fk_guest_details` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.guest_details: ~2 rows (approximately)
INSERT INTO `guest_details` (`detail_id`, `guest_id`, `id_type`, `id_number`, `address`, `emergency_contact`, `emergency_contact_number`, `profile_picture`, `id_document_photo`) VALUES
	(8, 1, 'Driver\'s License', 'A66-32-6152433', 'Brgy 17 Sta. Asuncion', 'Rodolfo Antonio Baltazar', '09918226634', '68da1fe1c9f49_1759125473.png', '68ee4d34da455_1760447796.jpg'),
	(9, 2, 'Driver\'s License', 'A11-11-1234567', '', '', '', '68daa6cc46f31_1759160012.jpg', NULL),
	(10, 5, 'Driver\'s License', 'A66-32-615243', 'sitio 7', 'Rodolfo Antonio Baltazar', '09999999999', NULL, NULL);

-- Dumping structure for table hotelia_db.login_attempts
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `success` tinyint(1) DEFAULT '0',
  `attempted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `username` (`username`),
  KEY `ip_address` (`ip_address`),
  KEY `attempted_at` (`attempted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=167 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.login_attempts: ~147 rows (approximately)
INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `success`, `attempted_at`) VALUES
	(1, 'loki', '127.0.0.1', 1, '2025-09-16 05:59:18'),
	(2, 'loki', '127.0.0.1', 1, '2025-09-16 12:01:50'),
	(3, 'loki', '127.0.0.1', 1, '2025-09-28 07:17:13'),
	(4, 'loki', '127.0.0.1', 1, '2025-09-28 08:38:19'),
	(5, 'loki', '127.0.0.1', 0, '2025-09-28 10:57:20'),
	(6, 'loki', '127.0.0.1', 1, '2025-09-28 10:57:29'),
	(7, 'loki', '127.0.0.1', 1, '2025-09-28 11:47:35'),
	(8, 'dsada', '127.0.0.1', 0, '2025-09-28 13:11:42'),
	(9, 'loki', '127.0.0.1', 1, '2025-09-28 13:34:33'),
	(10, 'loki', '127.0.0.1', 1, '2025-09-28 13:43:32'),
	(11, 'loki', '127.0.0.1', 1, '2025-09-29 05:17:26'),
	(12, 'loki', '127.0.0.1', 1, '2025-09-29 05:58:30'),
	(13, 'loki', '127.0.0.1', 1, '2025-09-29 14:49:14'),
	(14, 'LokiMobile', '192.168.0.100', 0, '2025-09-29 15:28:22'),
	(15, 'LokiMobile', '192.168.0.100', 1, '2025-09-29 15:31:13'),
	(16, 'loki', '127.0.0.1', 1, '2025-09-29 15:42:02'),
	(17, 'loki', '127.0.0.1', 1, '2025-09-30 13:04:00'),
	(18, 'loki', '127.0.0.1', 1, '2025-10-01 16:41:25'),
	(19, 'loki', '127.0.0.1', 1, '2025-10-01 18:57:43'),
	(20, 'loki', '127.0.0.1', 1, '2025-10-02 05:05:56'),
	(21, 'loki', '127.0.0.1', 0, '2025-10-02 06:36:01'),
	(22, 'loki', '127.0.0.1', 1, '2025-10-02 06:36:08'),
	(23, 'loki', '127.0.0.1', 1, '2025-10-03 05:14:19'),
	(24, 'loki', '127.0.0.1', 1, '2025-10-03 07:43:59'),
	(25, 'asdasdas', '127.0.0.1', 0, '2025-10-03 07:53:00'),
	(26, 'loki', '127.0.0.1', 1, '2025-10-03 07:53:12'),
	(27, 'LokiMobile', '192.168.0.100', 1, '2025-10-03 10:58:28'),
	(28, 'loki', '127.0.0.1', 0, '2025-10-03 15:05:03'),
	(29, 'loki', '127.0.0.1', 1, '2025-10-03 15:05:12'),
	(30, 'loki', '127.0.0.1', 0, '2025-10-03 18:08:43'),
	(31, 'loki', '127.0.0.1', 1, '2025-10-03 18:08:49'),
	(32, 'loki', '127.0.0.1', 1, '2025-10-03 19:52:06'),
	(33, 'loki', '127.0.0.1', 1, '2025-10-05 15:11:00'),
	(34, 'loki', '127.0.0.1', 1, '2025-10-05 18:43:48'),
	(35, 'loki', '127.0.0.1', 1, '2025-10-06 05:44:26'),
	(36, 'loki', '127.0.0.1', 1, '2025-10-06 14:17:58'),
	(37, 'loki', '127.0.0.1', 1, '2025-10-07 14:40:55'),
	(38, 'loki', '127.0.0.1', 1, '2025-10-07 14:44:12'),
	(39, 'loki', '127.0.0.1', 1, '2025-10-07 14:59:15'),
	(40, 'loki', '127.0.0.1', 1, '2025-10-07 15:01:29'),
	(41, 'loki', '127.0.0.1', 1, '2025-10-07 15:16:45'),
	(42, 'loki', '127.0.0.1', 1, '2025-10-08 12:21:07'),
	(43, 'loki', '127.0.0.1', 1, '2025-10-08 14:50:04'),
	(44, 'loki', '127.0.0.1', 1, '2025-10-09 03:59:39'),
	(45, 'loki', '127.0.0.1', 1, '2025-10-09 07:47:13'),
	(46, 'loki', '127.0.0.1', 1, '2025-10-10 04:20:06'),
	(47, 'loki', '127.0.0.1', 1, '2025-10-10 10:25:43'),
	(48, 'loki', '127.0.0.1', 1, '2025-10-11 04:21:31'),
	(49, 'loki', '127.0.0.1', 1, '2025-10-11 05:44:23'),
	(50, 'loki', '127.0.0.1', 1, '2025-10-11 11:54:40'),
	(51, 'loki', '127.0.0.1', 1, '2025-10-12 13:31:00'),
	(52, 'loki', '127.0.0.1', 0, '2025-10-12 15:44:24'),
	(53, 'loki', '127.0.0.1', 1, '2025-10-12 15:44:31'),
	(54, 'loki', '127.0.0.1', 1, '2025-10-13 02:30:20'),
	(55, 'loki', '127.0.0.1', 1, '2025-10-13 05:04:01'),
	(56, 'loki', '127.0.0.1', 1, '2025-10-13 06:48:57'),
	(57, 'loki', '127.0.0.1', 0, '2025-10-13 13:22:24'),
	(58, 'loki', '127.0.0.1', 1, '2025-10-13 13:22:30'),
	(59, 'loki', '127.0.0.1', 1, '2025-10-13 16:54:15'),
	(60, 'loki', '127.0.0.1', 1, '2025-10-13 17:11:36'),
	(61, 'loki', '127.0.0.1', 1, '2025-10-14 04:32:31'),
	(62, 'loki', '127.0.0.1', 1, '2025-10-14 04:35:58'),
	(63, 'loki', '127.0.0.1', 1, '2025-10-14 07:59:29'),
	(64, 'loki', '127.0.0.1', 1, '2025-10-14 11:41:39'),
	(65, 'loki', '192.168.0.102', 1, '2025-10-14 13:14:20'),
	(66, 'loki', '127.0.0.1', 1, '2025-10-14 15:48:18'),
	(67, 'loki', '127.0.0.1', 1, '2025-10-14 18:26:38'),
	(68, 'loki', '127.0.0.1', 1, '2025-10-15 08:09:35'),
	(69, 'loki', '127.0.0.1', 1, '2025-10-16 08:25:24'),
	(70, 'loki', '127.0.0.1', 1, '2025-10-17 04:40:37'),
	(71, 'loki', '127.0.0.1', 1, '2025-10-17 14:41:40'),
	(72, 'loki', '127.0.0.1', 1, '2025-10-18 05:52:05'),
	(73, 'loki', '127.0.0.1', 1, '2025-10-18 06:56:29'),
	(74, 'loki', '127.0.0.1', 0, '2025-10-18 12:17:15'),
	(75, 'loki', '127.0.0.1', 0, '2025-10-18 12:17:20'),
	(76, 'loki', '127.0.0.1', 1, '2025-10-18 12:17:30'),
	(77, 'loki', '127.0.0.1', 1, '2025-10-19 02:02:05'),
	(78, 'loki', '127.0.0.1', 1, '2025-10-19 06:03:23'),
	(79, 'loki', '127.0.0.1', 1, '2025-10-19 10:19:28'),
	(80, 'loki', '127.0.0.1', 1, '2025-10-19 15:08:11'),
	(81, 'loki', '127.0.0.1', 1, '2025-10-19 20:13:54'),
	(82, 'loki', '127.0.0.1', 0, '2025-10-19 20:21:39'),
	(83, 'loki', '127.0.0.1', 0, '2025-10-19 20:21:43'),
	(84, 'loki', '127.0.0.1', 1, '2025-10-19 20:21:53'),
	(85, 'loki', '127.0.0.1', 1, '2025-10-19 20:55:51'),
	(86, 'loki', '127.0.0.1', 1, '2025-10-19 20:56:40'),
	(87, 'loki', '127.0.0.1', 1, '2025-10-20 12:53:18'),
	(88, 'loki', '127.0.0.1', 1, '2025-10-21 10:45:25'),
	(89, 'loki', '127.0.0.1', 1, '2025-10-21 11:09:12'),
	(90, 'loki', '127.0.0.1', 1, '2025-10-21 14:09:02'),
	(91, 'loki', '127.0.0.1', 1, '2025-10-22 12:27:02'),
	(92, 'loki', '127.0.0.1', 1, '2025-10-22 13:50:38'),
	(93, 'loki', '127.0.0.1', 1, '2025-10-26 15:16:24'),
	(94, 'loki', '127.0.0.1', 1, '2025-10-26 18:28:15'),
	(95, 'loki', '127.0.0.1', 1, '2025-10-27 04:51:32'),
	(96, 'loki', '127.0.0.1', 1, '2025-10-27 11:22:14'),
	(97, 'loki', '127.0.0.1', 1, '2025-10-27 13:27:49'),
	(98, 'loki', '127.0.0.1', 1, '2025-10-28 12:38:40'),
	(99, 'loki', '127.0.0.1', 1, '2025-10-28 14:12:20'),
	(100, 'reception1', '127.0.0.1', 0, '2025-10-28 14:27:45'),
	(101, 'reception1', '127.0.0.1', 0, '2025-10-28 14:28:10'),
	(102, 'reception1', '127.0.0.1', 0, '2025-10-28 14:36:13'),
	(103, 'reception1', '127.0.0.1', 0, '2025-10-28 14:36:26'),
	(104, 'reception_staff', '127.0.0.1', 0, '2025-10-28 14:46:44'),
	(105, 'reception_staff', '127.0.0.1', 0, '2025-10-28 14:46:55'),
	(106, 'reception_staff', '127.0.0.1', 0, '2025-10-28 14:47:13'),
	(107, 'reception_staff', '127.0.0.1', 0, '2025-10-28 14:48:52'),
	(108, 'reception_staff', '127.0.0.1', 0, '2025-10-28 14:49:06'),
	(109, 'loki', '127.0.0.1', 1, '2025-10-28 14:50:11'),
	(110, 'reception_staff', '127.0.0.1', 1, '2025-10-28 14:55:00'),
	(111, 'reception1', '127.0.0.1', 0, '2025-10-29 00:00:31'),
	(112, 'reception1', '127.0.0.1', 0, '2025-10-29 00:00:41'),
	(113, 'reception_staff', '127.0.0.1', 0, '2025-10-29 00:01:25'),
	(114, 'reception_staff', '127.0.0.1', 1, '2025-10-29 00:01:32'),
	(115, 'loki', '127.0.0.1', 1, '2025-10-29 02:39:05'),
	(116, 'reception_staff', '127.0.0.1', 1, '2025-10-29 13:19:23'),
	(117, 'reception_staff', '127.0.0.1', 1, '2025-10-30 00:54:42'),
	(118, 'loki', '127.0.0.1', 1, '2025-10-30 01:14:23'),
	(119, 'loki', '127.0.0.1', 1, '2025-10-30 03:28:53'),
	(120, 'reception_staff', '127.0.0.1', 1, '2025-10-30 08:29:17'),
	(121, 'loki', '127.0.0.1', 1, '2025-10-30 11:37:45'),
	(122, 'reception_staff', '127.0.0.1', 1, '2025-10-30 11:46:04'),
	(123, 'loki', '127.0.0.1', 1, '2025-10-30 12:41:15'),
	(124, 'reception_staff', '127.0.0.1', 1, '2025-10-30 13:00:15'),
	(125, 'loki', '127.0.0.1', 1, '2025-10-30 23:03:36'),
	(126, 'reception_staff', '127.0.0.1', 1, '2025-10-30 23:04:23'),
	(127, 'loki', '127.0.0.1', 1, '2025-10-31 01:24:20'),
	(128, 'reception_staff', '127.0.0.1', 1, '2025-10-31 10:38:42'),
	(129, 'reception_staff', '127.0.0.1', 1, '2025-10-31 12:24:13'),
	(130, 'reception_staff', '127.0.0.1', 1, '2025-11-01 05:28:27'),
	(131, 'reception_staff', '127.0.0.1', 1, '2025-11-01 07:44:13'),
	(132, 'loki', '127.0.0.1', 1, '2025-11-01 08:37:52'),
	(133, 'reception_staff', '127.0.0.1', 1, '2025-11-03 03:11:30'),
	(134, 'reception_staff', '127.0.0.1', 1, '2025-11-03 05:29:40'),
	(135, 'reception_staff', '127.0.0.1', 1, '2025-11-04 03:42:19'),
	(136, 'loki', '127.0.0.1', 1, '2025-11-04 03:46:41'),
	(137, 'reception_staff', '127.0.0.1', 1, '2025-11-04 04:48:26'),
	(138, 'nielbars@gmail.com', '127.0.0.1', 0, '2025-11-04 05:21:29'),
	(139, 'nielbars@gmail.com', '127.0.0.1', 0, '2025-11-04 05:21:40'),
	(140, 'nielbars@gmail.com', '127.0.0.1', 0, '2025-11-04 05:23:04'),
	(141, 'loni', '127.0.0.1', 0, '2025-11-04 05:23:23'),
	(142, 'loni', '127.0.0.1', 0, '2025-11-04 05:23:33'),
	(143, 'loki', '127.0.0.1', 1, '2025-11-04 05:23:46'),
	(144, 'reception_staff', '127.0.0.1', 1, '2025-11-04 10:57:10'),
	(145, 'niel123', '127.0.0.1', 1, '2025-11-04 11:37:56'),
	(146, 'loki', '127.0.0.1', 1, '2025-11-04 12:04:39'),
	(147, 'reception_staff', '::1', 1, '2025-11-04 12:29:55'),
	(148, 'reception_staff', '::1', 1, '2025-11-04 12:38:37'),
	(149, 'lokiit', '127.0.0.1', 1, '2025-11-04 13:55:03'),
	(150, 'loki', '127.0.0.1', 1, '2025-11-04 14:43:16'),
	(151, 'loki', '127.0.0.1', 1, '2025-11-04 15:19:24'),
	(152, 'reception_staff', '127.0.0.1', 1, '2025-11-04 15:20:42'),
	(153, 'kd@a.com', '::1', 0, '2025-11-05 03:18:01'),
	(154, 'kd@a.com', '::1', 0, '2025-11-05 03:18:12'),
	(155, 'example@gmail.com', '::1', 0, '2025-11-05 03:20:55'),
	(156, 'alvin', '::1', 1, '2025-11-05 03:23:59'),
	(157, 'reception_staff', '127.0.0.1', 1, '2025-11-05 03:27:22'),
	(158, 'loki', '127.0.0.1', 1, '2025-11-08 00:07:33'),
	(159, 'loki', '127.0.0.1', 1, '2025-11-08 07:09:59'),
	(160, 'reception_staff', '127.0.0.1', 1, '2025-11-08 07:16:03'),
	(161, 'loki', '127.0.0.1', 1, '2025-11-18 12:45:00'),
	(162, 'loki', '127.0.0.1', 1, '2025-11-18 12:50:42'),
	(163, 'loki', '127.0.0.1', 1, '2025-11-18 13:04:21'),
	(164, 'reception_staff', '127.0.0.1', 0, '2025-11-18 14:58:32'),
	(165, 'reception_staff', '127.0.0.1', 1, '2025-11-18 14:58:41'),
	(166, 'loki', '127.0.0.1', 1, '2025-12-03 02:27:50');

-- Dumping structure for table hotelia_db.payments
CREATE TABLE IF NOT EXISTS `payments` (
  `payment_id` int NOT NULL AUTO_INCREMENT,
  `booking_id` int DEFAULT NULL,
  `guest_id` int DEFAULT NULL,
  `payment_method_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `user_reference` varchar(100) DEFAULT NULL,
  `verification_notes` text,
  `status` enum('pending_verification','pending','completed','failed','refunded') DEFAULT 'pending_verification',
  `verified_by_staff` int DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `verified_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`payment_id`),
  KEY `booking_id` (`booking_id`),
  KEY `payment_method_id` (`payment_method_id`),
  KEY `guest_id` (`guest_id`),
  KEY `idx_payment_verification` (`user_reference`,`status`,`guest_id`),
  KEY `fk_payments_verified_by` (`verified_by_staff`),
  CONSTRAINT `fk_payments_bookings` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_payment_methods` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`method_id`),
  CONSTRAINT `fk_payments_verified_by` FOREIGN KEY (`verified_by_staff`) REFERENCES `staff` (`staff_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.payments: ~4 rows (approximately)
INSERT INTO `payments` (`payment_id`, `booking_id`, `guest_id`, `payment_method_id`, `amount`, `payment_reference`, `user_reference`, `verification_notes`, `status`, `verified_by_staff`, `payment_date`, `verified_at`) VALUES
	(1, 1, 1, 3, 3080.00, 'GC20251108071321436', 'GC20251108071321436', '', 'completed', NULL, '2025-11-08 07:13:21', '2025-11-08 07:18:16'),
	(2, 2, 1, 3, 2000.00, 'GC20251108072123297', 'GC20251108072123297', '', 'completed', NULL, '2025-11-08 07:21:23', '2025-11-08 07:21:54'),
	(3, 3, 1, 3, 3000.00, 'GC20251108072449415', 'GC20251108072449415', '', 'completed', NULL, '2025-11-08 07:24:49', '2025-11-08 07:25:22'),
	(4, 4, 1, 3, 4032.00, 'GC20251118131245912', 'GC20251118131245912', NULL, 'pending_verification', NULL, '2025-11-18 13:12:45', NULL);

-- Dumping structure for table hotelia_db.payment_gateway_sessions
CREATE TABLE IF NOT EXISTS `payment_gateway_sessions` (
  `session_id` int NOT NULL AUTO_INCREMENT,
  `guest_id` int NOT NULL,
  `booking_id` int DEFAULT NULL,
  `payment_method_id` int NOT NULL,
  `session_token` varchar(128) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `deposit_amount` decimal(10,2) NOT NULL,
  `reference_no` varchar(50) DEFAULT NULL,
  `paid_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `gateway_data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`session_id`),
  KEY `guest_id` (`guest_id`),
  KEY `booking_id` (`booking_id`),
  KEY `payment_method_id` (`payment_method_id`),
  CONSTRAINT `payment_gateway_sessions_ibfk_1` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`),
  CONSTRAINT `payment_gateway_sessions_ibfk_2` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`),
  CONSTRAINT `payment_gateway_sessions_ibfk_3` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`method_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.payment_gateway_sessions: ~0 rows (approximately)

-- Dumping structure for table hotelia_db.payment_methods
CREATE TABLE IF NOT EXISTS `payment_methods` (
  `method_id` int NOT NULL AUTO_INCREMENT,
  `method_code` varchar(20) NOT NULL,
  `method_name` varchar(50) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `description` text,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `requires_gateway` tinyint(1) DEFAULT '1',
  `gateway_type` enum('ewallet','card','bank','cash') DEFAULT 'ewallet',
  `instructions` text,
  PRIMARY KEY (`method_id`),
  UNIQUE KEY `method_code` (`method_code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.payment_methods: ~6 rows (approximately)
INSERT INTO `payment_methods` (`method_id`, `method_code`, `method_name`, `icon`, `description`, `is_active`, `created_at`, `requires_gateway`, `gateway_type`, `instructions`) VALUES
	(1, 'credit_card', 'Credit Card', 'credit-card', 'Pay with Visa, MasterCard, or American Express', 1, '2025-10-16 13:16:50', 1, 'card', NULL),
	(2, 'debit_card', 'Debit Card', 'credit-card', 'Pay with your debit card', 1, '2025-10-16 13:16:50', 1, 'card', NULL),
	(3, 'gcash', 'GCash', 'smartphone', 'Pay using GCash mobile wallet', 1, '2025-10-16 13:16:50', 1, 'ewallet', NULL),
	(4, 'paymaya', 'PayMaya', 'smartphone', 'Pay using PayMaya mobile wallet', 1, '2025-10-16 13:16:50', 1, 'ewallet', NULL),
	(5, 'bank_transfer', 'Bank Transfer', 'landmark', 'Direct bank transfer', 1, '2025-10-16 13:16:50', 0, 'bank', NULL),
	(6, 'cash', 'Cash', 'dollar-sign', 'Pay in cash at the hotel', 1, '2025-10-16 13:16:50', 0, 'cash', NULL);

-- Dumping structure for table hotelia_db.pos_transactions
CREATE TABLE IF NOT EXISTS `pos_transactions` (
  `transaction_id` int NOT NULL AUTO_INCREMENT,
  `booking_id` int NOT NULL,
  `reference_no` varchar(30) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method_id` int NOT NULL,
  `account_number` varchar(100) DEFAULT NULL,
  `last_four_digits` char(4) DEFAULT NULL,
  `cardholder_name` varchar(100) DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('completed','void','refunded') DEFAULT 'completed',
  PRIMARY KEY (`transaction_id`),
  KEY `idx_booking` (`booking_id`),
  KEY `idx_method` (`payment_method_id`),
  KEY `idx_ref` (`reference_no`),
  KEY `idx_date` (`processed_at`),
  KEY `idx_staff` (`staff_id`),
  CONSTRAINT `fk_pos_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pos_payment_method` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`method_id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pos_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.pos_transactions: ~0 rows (approximately)

-- Dumping structure for table hotelia_db.request_updates
CREATE TABLE IF NOT EXISTS `request_updates` (
  `update_id` int NOT NULL AUTO_INCREMENT,
  `request_id` int NOT NULL,
  `staff_id` int DEFAULT NULL,
  `note` text NOT NULL,
  `status_change` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`update_id`),
  KEY `request_id` (`request_id`),
  KEY `staff_id` (`staff_id`),
  CONSTRAINT `request_updates_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`request_id`),
  CONSTRAINT `request_updates_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.request_updates: ~1 rows (approximately)
INSERT INTO `request_updates` (`update_id`, `request_id`, `staff_id`, `note`, `status_change`, `created_at`) VALUES
	(1, 7, 2, 'Status changed to Completed', 'Status changed to Completed', '2025-11-08 07:30:27');

-- Dumping structure for table hotelia_db.rooms
CREATE TABLE IF NOT EXISTS `rooms` (
  `room_id` int NOT NULL AUTO_INCREMENT,
  `room_number` varchar(10) NOT NULL,
  `floor` int NOT NULL,
  `room_type_id` int NOT NULL,
  `bed_type_id` int NOT NULL,
  `view_type_id` int NOT NULL,
  `capacity` tinyint NOT NULL,
  `max_extra` tinyint NOT NULL DEFAULT '0',
  `extra_person_rate` decimal(10,2) DEFAULT '0.00',
  `base_price` decimal(10,2) NOT NULL,
  `status` enum('available','occupied','maintenance','reserved') DEFAULT 'available',
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `maintenance_status` enum('good','cleaning','under_repair') DEFAULT 'good',
  PRIMARY KEY (`room_id`),
  UNIQUE KEY `room_number` (`room_number`),
  KEY `room_type_id` (`room_type_id`),
  KEY `bed_type_id` (`bed_type_id`),
  KEY `view_type_id` (`view_type_id`),
  CONSTRAINT `fk_rooms_bed_types` FOREIGN KEY (`bed_type_id`) REFERENCES `bed_types` (`bed_type_id`),
  CONSTRAINT `fk_rooms_room_types` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`room_type_id`),
  CONSTRAINT `fk_rooms_view_types` FOREIGN KEY (`view_type_id`) REFERENCES `view_types` (`view_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.rooms: ~21 rows (approximately)
INSERT INTO `rooms` (`room_id`, `room_number`, `floor`, `room_type_id`, `bed_type_id`, `view_type_id`, `capacity`, `max_extra`, `extra_person_rate`, `base_price`, `status`, `description`, `created_at`, `maintenance_status`) VALUES
	(7, 'G101', 1, 4, 7, 7, 2, 1, 300.00, 1500.00, 'reserved', 'Cozy standard room with garden view', '2025-10-06 05:32:56', 'good'),
	(8, 'G102', 1, 4, 6, 11, 2, 1, 300.00, 1500.00, 'available', 'Standard room with courtyard view', '2025-10-06 05:32:56', 'good'),
	(9, 'G103', 1, 4, 7, 6, 2, 1, 300.00, 1500.00, 'available', 'Comfortable standard room with pool view', '2025-10-06 05:32:56', 'good'),
	(10, 'G104', 1, 14, 10, 11, 6, 0, 0.00, 800.00, 'available', 'Budget-friendly dormitory with 6 bunk beds', '2025-10-06 05:32:56', 'good'),
	(11, 'G105', 1, 14, 10, 11, 6, 0, 0.00, 800.00, 'available', 'Shared dormitory room, perfect for backpackers', '2025-10-06 05:32:56', 'good'),
	(12, '201', 2, 5, 8, 5, 2, 1, 400.00, 2500.00, 'available', 'Spacious deluxe room with stunning sea view', '2025-10-06 05:32:56', 'good'),
	(13, '202', 2, 5, 9, 6, 2, 1, 400.00, 2500.00, 'available', 'Deluxe room overlooking the pool', '2025-10-06 05:32:56', 'good'),
	(14, '203', 2, 5, 8, 10, 2, 1, 400.00, 2800.00, 'reserved', 'Deluxe room with beautiful sunset view', '2025-10-06 05:32:56', 'good'),
	(15, '204', 2, 6, 9, 5, 2, 1, 500.00, 3500.00, 'available', 'Executive room with premium sea view', '2025-10-06 05:32:56', 'good'),
	(16, '205', 2, 6, 9, 7, 2, 1, 500.00, 3200.00, 'reserved', 'Executive room with garden view', '2025-10-06 05:32:56', 'good'),
	(17, '206', 2, 7, 9, 7, 4, 2, 400.00, 3500.00, 'available', 'Large family room with garden view, sleeps up to 6', '2025-10-06 05:32:56', 'good'),
	(18, '207', 2, 7, 9, 6, 4, 2, 400.00, 3500.00, 'available', 'Spacious family room with pool view', '2025-10-06 05:32:56', 'good'),
	(19, '301', 3, 8, 9, 5, 2, 1, 600.00, 4500.00, 'reserved', 'Elegant suite with panoramic sea view', '2025-10-06 05:32:56', 'good'),
	(20, '302', 3, 8, 9, 10, 2, 1, 600.00, 4800.00, 'reserved', 'Luxurious suite with stunning sunset view', '2025-10-06 05:32:56', 'good'),
	(21, '303', 3, 9, 9, 5, 2, 1, 550.00, 4000.00, 'available', 'Modern junior suite with sea view', '2025-10-06 05:32:56', 'good'),
	(22, '304', 3, 9, 9, 7, 2, 1, 550.00, 3800.00, 'available', 'Junior suite with peaceful garden view', '2025-10-06 05:32:56', 'good'),
	(23, '305', 3, 10, 9, 5, 2, 1, 700.00, 5500.00, 'available', 'Premium executive suite with sea view and work area', '2025-10-06 05:32:56', 'good'),
	(24, '306', 3, 10, 9, 10, 2, 1, 700.00, 5800.00, 'available', 'Executive suite with magnificent sunset view', '2025-10-06 05:32:56', 'good'),
	(25, '307', 3, 11, 9, 5, 4, 2, 1000.00, 12000.00, 'available', 'Ultimate luxury presidential suite with panoramic sea view and private terrace', '2025-10-06 05:32:56', 'good'),
	(26, '308', 3, 12, 9, 10, 3, 2, 900.00, 10000.00, 'available', 'Prestigious governor suite with sunset terrace and ocean breeze', '2025-10-06 05:32:56', 'good'),
	(27, '309', 3, 13, 9, 5, 3, 2, 900.00, 9500.00, 'available', 'Exclusive mayor suite with sweeping sea and garden views', '2025-10-06 05:32:56', 'good');

-- Dumping structure for table hotelia_db.room_amenities
CREATE TABLE IF NOT EXISTS `room_amenities` (
  `room_id` int NOT NULL,
  `amenity_id` int NOT NULL,
  PRIMARY KEY (`room_id`,`amenity_id`),
  KEY `amenity_id` (`amenity_id`),
  CONSTRAINT `fk_room_amenities_amenities` FOREIGN KEY (`amenity_id`) REFERENCES `amenities` (`amenity_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_room_amenities_rooms` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.room_amenities: ~0 rows (approximately)

-- Dumping structure for table hotelia_db.room_assignments
CREATE TABLE IF NOT EXISTS `room_assignments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_item_id` int NOT NULL,
  `new_room_id` int DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_booking_item` (`booking_item_id`),
  KEY `idx_booking_item` (`booking_item_id`),
  KEY `idx_room` (`new_room_id`),
  CONSTRAINT `room_assignments_ibfk_1` FOREIGN KEY (`booking_item_id`) REFERENCES `booking_items` (`booking_item_id`) ON DELETE CASCADE,
  CONSTRAINT `room_assignments_ibfk_2` FOREIGN KEY (`new_room_id`) REFERENCES `rooms` (`room_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.room_assignments: ~6 rows (approximately)
INSERT INTO `room_assignments` (`id`, `booking_item_id`, `new_room_id`, `assigned_at`) VALUES
	(1, 1, 8, '2025-10-30 23:21:47'),
	(2, 2, 13, '2025-10-30 23:21:47'),
	(3, 5, 7, '2025-11-04 05:29:31'),
	(4, 6, 14, '2025-11-04 12:03:43'),
	(5, 9, 20, '2025-11-04 14:01:24'),
	(6, 7, 19, '2025-11-04 14:01:32'),
	(7, 10, 16, '2025-11-04 14:26:04'),
	(8, 13, 7, '2025-11-05 03:38:27');

-- Dumping structure for view hotelia_db.room_availability
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `room_availability` (
	`room_id` INT NOT NULL,
	`room_number` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`floor` INT NOT NULL,
	`room_type` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`bed_type` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`view_type` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`capacity` TINYINT NOT NULL,
	`max_extra` TINYINT NOT NULL,
	`base_price` DECIMAL(10,2) NOT NULL,
	`status` ENUM('available','occupied','maintenance','reserved') NULL COLLATE 'utf8mb4_0900_ai_ci',
	`maintenance_status` ENUM('good','cleaning','under_repair') NULL COLLATE 'utf8mb4_0900_ai_ci',
	`availability_status` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_general_ci'
) ENGINE=MyISAM;

-- Dumping structure for table hotelia_db.room_selections
CREATE TABLE IF NOT EXISTS `room_selections` (
  `selection_id` int NOT NULL AUTO_INCREMENT,
  `guest_id` int NOT NULL,
  `room_type_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1' COMMENT 'Number of rooms of this type',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `adults` int DEFAULT '1',
  `children` int DEFAULT '0',
  `check_in_date` date DEFAULT NULL,
  `check_out_date` date DEFAULT NULL,
  `nights` int DEFAULT NULL,
  PRIMARY KEY (`selection_id`),
  UNIQUE KEY `unique_guest_room_type` (`guest_id`,`room_type_id`),
  KEY `idx_guest_id` (`guest_id`),
  KEY `idx_room_type_id` (`room_type_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_booking_dates` (`check_in_date`,`check_out_date`),
  KEY `idx_guest_dates` (`guest_id`,`check_in_date`),
  CONSTRAINT `fk_selection_guest` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_selection_room_type` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`room_type_id`) ON DELETE CASCADE,
  CONSTRAINT `chk_quantity_positive` CHECK ((`quantity` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Stores guest room selections before booking confirmation';

-- Dumping data for table hotelia_db.room_selections: ~0 rows (approximately)

-- Dumping structure for table hotelia_db.room_selection_instances
CREATE TABLE IF NOT EXISTS `room_selection_instances` (
  `instance_id` int NOT NULL AUTO_INCREMENT,
  `selection_id` int NOT NULL,
  `room_type_id` int NOT NULL,
  `guest_id` int NOT NULL,
  `adults` int NOT NULL DEFAULT '1',
  `children` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `base_price_total` decimal(10,2) DEFAULT '0.00',
  `extra_person_total` decimal(10,2) DEFAULT '0.00',
  `room_total` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`instance_id`),
  KEY `idx_instance_guest` (`guest_id`),
  KEY `idx_instance_selection` (`selection_id`),
  KEY `idx_instance_room_type` (`room_type_id`),
  CONSTRAINT `room_selection_instances_ibfk_1` FOREIGN KEY (`selection_id`) REFERENCES `room_selections` (`selection_id`) ON DELETE CASCADE,
  CONSTRAINT `room_selection_instances_ibfk_2` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`room_type_id`) ON DELETE CASCADE,
  CONSTRAINT `room_selection_instances_ibfk_3` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.room_selection_instances: ~0 rows (approximately)

-- Dumping structure for table hotelia_db.room_types
CREATE TABLE IF NOT EXISTS `room_types` (
  `room_type_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `base_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `description` text,
  `max_capacity` int DEFAULT '2',
  PRIMARY KEY (`room_type_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.room_types: ~11 rows (approximately)
INSERT INTO `room_types` (`room_type_id`, `name`, `base_price`, `description`, `max_capacity`) VALUES
	(4, 'Standard Room', 1500.00, 'Comfortable and affordable rooms with essential amenities', 2),
	(5, 'Deluxe Room', 2500.00, 'Spacious rooms with premium amenities and beautiful views', 2),
	(6, 'Executive Room', 3200.00, 'Professional accommodations with work-friendly features', 2),
	(7, 'Family Room', 3500.00, 'Large rooms designed for families with extra space and comfort', 4),
	(8, 'Suite', 4500.00, 'Elegant suites with separate living areas and luxury amenities', 2),
	(9, 'Junior Suite', 4000.00, 'Compact suites offering luxury in a cozy space', 2),
	(10, 'Executive Suite', 5500.00, 'Premium suites with executive services and panoramic views', 2),
	(11, 'Presidential Suite', 12000.00, 'The ultimate in luxury with exclusive amenities and services', 4),
	(12, 'Governor Suite', 10000.00, 'Prestigious accommodations fit for dignitaries', 3),
	(13, 'Mayor Suite', 9500.00, 'Exclusive suites with VIP treatment and amenities', 4),
	(14, 'Dormitory Room', 800.00, 'Budget-friendly shared accommodations perfect for backpackers', 6);

-- Dumping structure for view hotelia_db.room_types_with_capacity
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `room_types_with_capacity` (
	`room_type_id` INT NULL,
	`name` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`base_price` DECIMAL(10,2) NULL,
	`description` TEXT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`max_capacity` INT NULL,
	`calculated_max_capacity` BIGINT NULL
) ENGINE=MyISAM;

-- Dumping structure for table hotelia_db.room_type_amenities
CREATE TABLE IF NOT EXISTS `room_type_amenities` (
  `room_type_id` int NOT NULL,
  `amenity_id` int NOT NULL,
  PRIMARY KEY (`room_type_id`,`amenity_id`),
  KEY `fk_room_type_amenities_amenities` (`amenity_id`),
  CONSTRAINT `fk_room_type_amenities_amenities` FOREIGN KEY (`amenity_id`) REFERENCES `amenities` (`amenity_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_room_type_amenities_room_types` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`room_type_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.room_type_amenities: ~147 rows (approximately)
INSERT INTO `room_type_amenities` (`room_type_id`, `amenity_id`) VALUES
	(4, 5),
	(5, 5),
	(6, 5),
	(7, 5),
	(8, 5),
	(9, 5),
	(10, 5),
	(11, 5),
	(12, 5),
	(13, 5),
	(14, 5),
	(4, 6),
	(5, 6),
	(6, 6),
	(7, 6),
	(8, 6),
	(9, 6),
	(10, 6),
	(11, 6),
	(12, 6),
	(13, 6),
	(14, 6),
	(4, 7),
	(5, 7),
	(6, 7),
	(7, 7),
	(8, 7),
	(9, 7),
	(10, 7),
	(11, 7),
	(12, 7),
	(13, 7),
	(4, 8),
	(5, 8),
	(6, 8),
	(7, 8),
	(8, 8),
	(9, 8),
	(10, 8),
	(11, 8),
	(12, 8),
	(13, 8),
	(14, 8),
	(5, 9),
	(6, 9),
	(7, 9),
	(8, 9),
	(9, 9),
	(10, 9),
	(11, 9),
	(12, 9),
	(13, 9),
	(8, 10),
	(9, 10),
	(10, 10),
	(11, 10),
	(12, 10),
	(13, 10),
	(4, 11),
	(5, 11),
	(6, 11),
	(7, 11),
	(8, 11),
	(9, 11),
	(10, 11),
	(11, 11),
	(12, 11),
	(13, 11),
	(14, 11),
	(4, 12),
	(5, 12),
	(6, 12),
	(7, 12),
	(8, 12),
	(9, 12),
	(10, 12),
	(11, 12),
	(12, 12),
	(13, 12),
	(14, 12),
	(5, 13),
	(6, 13),
	(7, 13),
	(8, 13),
	(9, 13),
	(10, 13),
	(11, 13),
	(12, 13),
	(13, 13),
	(14, 13),
	(7, 14),
	(8, 14),
	(9, 14),
	(10, 14),
	(11, 14),
	(12, 14),
	(13, 14),
	(5, 15),
	(6, 15),
	(8, 15),
	(9, 15),
	(10, 15),
	(11, 15),
	(12, 15),
	(13, 15),
	(7, 16),
	(10, 16),
	(11, 16),
	(12, 16),
	(13, 16),
	(6, 17),
	(8, 17),
	(9, 17),
	(10, 17),
	(11, 17),
	(12, 17),
	(13, 17),
	(6, 18),
	(8, 18),
	(10, 18),
	(11, 18),
	(12, 18),
	(13, 18),
	(5, 19),
	(6, 19),
	(7, 19),
	(8, 19),
	(9, 19),
	(10, 19),
	(11, 19),
	(12, 19),
	(13, 19),
	(11, 20),
	(12, 20),
	(13, 20),
	(11, 21),
	(10, 22),
	(11, 22),
	(12, 22),
	(13, 22),
	(7, 23),
	(11, 23),
	(12, 23),
	(13, 23),
	(11, 24),
	(12, 24),
	(13, 24);

-- Dumping structure for view hotelia_db.room_type_availability
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `room_type_availability` (
	`room_type_id` INT NOT NULL,
	`room_type_name` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`base_price` DECIMAL(10,2) NOT NULL,
	`max_capacity` INT NULL,
	`total_rooms` BIGINT NULL,
	`physically_available` BIGINT NULL,
	`prebooked_count` BIGINT NOT NULL,
	`occupied_rooms` BIGINT NULL,
	`maintenance_rooms` BIGINT NULL,
	`available_for_booking` BIGINT NULL,
	`availability_status` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_general_ci'
) ENGINE=MyISAM;

-- Dumping structure for table hotelia_db.room_type_images
CREATE TABLE IF NOT EXISTS `room_type_images` (
  `image_id` int NOT NULL AUTO_INCREMENT,
  `room_type_id` int NOT NULL,
  `image_filename` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  `display_order` tinyint DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`image_id`),
  KEY `room_type_id` (`room_type_id`),
  CONSTRAINT `fk_room_type_images` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`room_type_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=141 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.room_type_images: ~110 rows (approximately)
INSERT INTO `room_type_images` (`image_id`, `room_type_id`, `image_filename`, `is_primary`, `display_order`, `created_at`) VALUES
	(31, 4, 'standard_room_1.jpg', 1, 1, '2025-10-06 05:32:56'),
	(32, 4, 'standard_room_2.jpg', 0, 2, '2025-10-06 05:32:56'),
	(33, 4, 'standard_room_3.jpg', 0, 3, '2025-10-06 05:32:56'),
	(34, 4, 'standard_room_4.jpg', 0, 4, '2025-10-06 05:32:56'),
	(35, 4, 'standard_room_5.jpg', 0, 5, '2025-10-06 05:32:56'),
	(36, 4, 'standard_room_6.jpg', 0, 6, '2025-10-06 05:32:56'),
	(37, 4, 'standard_room_7.jpg', 0, 7, '2025-10-06 05:32:56'),
	(38, 4, 'standard_room_8.jpg', 0, 8, '2025-10-06 05:32:56'),
	(39, 4, 'standard_room_9.jpg', 0, 9, '2025-10-06 05:32:56'),
	(40, 4, 'standard_room_10.jpg', 0, 10, '2025-10-06 05:32:56'),
	(41, 5, 'deluxe_room_1.jpg', 1, 1, '2025-10-06 05:32:56'),
	(42, 5, 'deluxe_room_2.jpg', 0, 2, '2025-10-06 05:32:56'),
	(43, 5, 'deluxe_room_3.jpg', 0, 3, '2025-10-06 05:32:56'),
	(44, 5, 'deluxe_room_4.jpg', 0, 4, '2025-10-06 05:32:56'),
	(45, 5, 'deluxe_room_5.jpg', 0, 5, '2025-10-06 05:32:56'),
	(46, 5, 'deluxe_room_6.jpg', 0, 6, '2025-10-06 05:32:56'),
	(47, 5, 'deluxe_room_7.jpg', 0, 7, '2025-10-06 05:32:56'),
	(48, 5, 'deluxe_room_8.jpg', 0, 8, '2025-10-06 05:32:56'),
	(49, 5, 'deluxe_room_9.jpg', 0, 9, '2025-10-06 05:32:56'),
	(50, 5, 'deluxe_room_10.jpg', 0, 10, '2025-10-06 05:32:56'),
	(51, 6, 'executive_room_1.jpg', 1, 1, '2025-10-06 05:32:56'),
	(52, 6, 'executive_room_2.jpg', 0, 2, '2025-10-06 05:32:56'),
	(53, 6, 'executive_room_3.jpg', 0, 3, '2025-10-06 05:32:56'),
	(54, 6, 'executive_room_4.jpg', 0, 4, '2025-10-06 05:32:56'),
	(55, 6, 'executive_room_5.jpg', 0, 5, '2025-10-06 05:32:56'),
	(56, 6, 'executive_room_6.jpg', 0, 6, '2025-10-06 05:32:56'),
	(57, 6, 'executive_room_7.jpg', 0, 7, '2025-10-06 05:32:56'),
	(58, 6, 'executive_room_8.jpg', 0, 8, '2025-10-06 05:32:56'),
	(59, 6, 'executive_room_9.jpg', 0, 9, '2025-10-06 05:32:56'),
	(60, 6, 'executive_room_10.jpg', 0, 10, '2025-10-06 05:32:56'),
	(61, 7, 'family_room_1.jpg', 1, 1, '2025-10-06 05:32:56'),
	(62, 7, 'family_room_2.jpg', 0, 2, '2025-10-06 05:32:56'),
	(63, 7, 'family_room_3.jpg', 0, 3, '2025-10-06 05:32:56'),
	(64, 7, 'family_room_4.jpg', 0, 4, '2025-10-06 05:32:56'),
	(65, 7, 'family_room_5.jpg', 0, 5, '2025-10-06 05:32:56'),
	(66, 7, 'family_room_6.jpg', 0, 6, '2025-10-06 05:32:56'),
	(67, 7, 'family_room_7.jpg', 0, 7, '2025-10-06 05:32:56'),
	(68, 7, 'family_room_8.jpg', 0, 8, '2025-10-06 05:32:56'),
	(69, 7, 'family_room_9.jpg', 0, 9, '2025-10-06 05:32:56'),
	(70, 7, 'family_room_10.jpg', 0, 10, '2025-10-06 05:32:56'),
	(71, 8, 'suite_1.jpg', 1, 1, '2025-10-06 05:32:56'),
	(72, 8, 'suite_2.jpg', 0, 2, '2025-10-06 05:32:56'),
	(73, 8, 'suite_3.jpg', 0, 3, '2025-10-06 05:32:56'),
	(74, 8, 'suite_4.jpg', 0, 4, '2025-10-06 05:32:56'),
	(75, 8, 'suite_5.jpg', 0, 5, '2025-10-06 05:32:56'),
	(76, 8, 'suite_6.jpg', 0, 6, '2025-10-06 05:32:56'),
	(77, 8, 'suite_7.jpg', 0, 7, '2025-10-06 05:32:56'),
	(78, 8, 'suite_8.jpg', 0, 8, '2025-10-06 05:32:56'),
	(79, 8, 'suite_9.jpg', 0, 9, '2025-10-06 05:32:56'),
	(80, 8, 'suite_10.jpg', 0, 10, '2025-10-06 05:32:56'),
	(81, 9, 'junior_suite_1.jpg', 1, 1, '2025-10-06 05:32:56'),
	(82, 9, 'junior_suite_2.jpg', 0, 2, '2025-10-06 05:32:56'),
	(83, 9, 'junior_suite_3.jpg', 0, 3, '2025-10-06 05:32:56'),
	(84, 9, 'junior_suite_4.jpg', 0, 4, '2025-10-06 05:32:56'),
	(85, 9, 'junior_suite_5.jpg', 0, 5, '2025-10-06 05:32:56'),
	(86, 9, 'junior_suite_6.jpg', 0, 6, '2025-10-06 05:32:56'),
	(87, 9, 'junior_suite_7.jpg', 0, 7, '2025-10-06 05:32:56'),
	(88, 9, 'junior_suite_8.jpg', 0, 8, '2025-10-06 05:32:56'),
	(89, 9, 'junior_suite_9.jpg', 0, 9, '2025-10-06 05:32:56'),
	(90, 9, 'junior_suite_10.jpg', 0, 10, '2025-10-06 05:32:56'),
	(91, 10, 'executive_suite_1.jpg', 1, 1, '2025-10-06 05:32:56'),
	(92, 10, 'executive_suite_2.jpg', 0, 2, '2025-10-06 05:32:56'),
	(93, 10, 'executive_suite_3.jpg', 0, 3, '2025-10-06 05:32:56'),
	(94, 10, 'executive_suite_4.jpg', 0, 4, '2025-10-06 05:32:56'),
	(95, 10, 'executive_suite_5.jpg', 0, 5, '2025-10-06 05:32:56'),
	(96, 10, 'executive_suite_6.jpg', 0, 6, '2025-10-06 05:32:56'),
	(97, 10, 'executive_suite_7.jpg', 0, 7, '2025-10-06 05:32:56'),
	(98, 10, 'executive_suite_8.jpg', 0, 8, '2025-10-06 05:32:56'),
	(99, 10, 'executive_suite_9.jpg', 0, 9, '2025-10-06 05:32:56'),
	(100, 10, 'executive_suite_10.jpg', 0, 10, '2025-10-06 05:32:56'),
	(101, 11, 'presidential_suite_1.jpg', 1, 1, '2025-10-06 05:32:56'),
	(102, 11, 'presidential_suite_2.jpg', 0, 2, '2025-10-06 05:32:56'),
	(103, 11, 'presidential_suite_3.jpg', 0, 3, '2025-10-06 05:32:56'),
	(104, 11, 'presidential_suite_4.jpg', 0, 4, '2025-10-06 05:32:56'),
	(105, 11, 'presidential_suite_5.jpg', 0, 5, '2025-10-06 05:32:56'),
	(106, 11, 'presidential_suite_6.jpg', 0, 6, '2025-10-06 05:32:56'),
	(107, 11, 'presidential_suite_7.jpg', 0, 7, '2025-10-06 05:32:56'),
	(108, 11, 'presidential_suite_8.jpg', 0, 8, '2025-10-06 05:32:56'),
	(109, 11, 'presidential_suite_9.jpg', 0, 9, '2025-10-06 05:32:56'),
	(110, 11, 'presidential_suite_10.jpg', 0, 10, '2025-10-06 05:32:56'),
	(111, 12, 'governor_suite_1.jpg', 1, 1, '2025-10-06 05:32:56'),
	(112, 12, 'governor_suite_2.jpg', 0, 2, '2025-10-06 05:32:56'),
	(113, 12, 'governor_suite_3.jpg', 0, 3, '2025-10-06 05:32:56'),
	(114, 12, 'governor_suite_4.jpg', 0, 4, '2025-10-06 05:32:56'),
	(115, 12, 'governor_suite_5.jpg', 0, 5, '2025-10-06 05:32:56'),
	(116, 12, 'governor_suite_6.jpg', 0, 6, '2025-10-06 05:32:56'),
	(117, 12, 'governor_suite_7.jpg', 0, 7, '2025-10-06 05:32:56'),
	(118, 12, 'governor_suite_8.jpg', 0, 8, '2025-10-06 05:32:56'),
	(119, 12, 'governor_suite_9.jpg', 0, 9, '2025-10-06 05:32:56'),
	(120, 12, 'governor_suite_10.jpg', 0, 10, '2025-10-06 05:32:56'),
	(121, 13, 'mayor_suite_1.jpg', 1, 1, '2025-10-06 05:32:56'),
	(122, 13, 'mayor_suite_2.jpg', 0, 2, '2025-10-06 05:32:56'),
	(123, 13, 'mayor_suite_3.jpg', 0, 3, '2025-10-06 05:32:56'),
	(124, 13, 'mayor_suite_4.jpg', 0, 4, '2025-10-06 05:32:56'),
	(125, 13, 'mayor_suite_5.jpg', 0, 5, '2025-10-06 05:32:56'),
	(126, 13, 'mayor_suite_6.jpg', 0, 6, '2025-10-06 05:32:56'),
	(127, 13, 'mayor_suite_7.jpg', 0, 7, '2025-10-06 05:32:56'),
	(128, 13, 'mayor_suite_8.jpg', 0, 8, '2025-10-06 05:32:56'),
	(129, 13, 'mayor_suite_9.jpg', 0, 9, '2025-10-06 05:32:56'),
	(130, 13, 'mayor_suite_10.jpg', 0, 10, '2025-10-06 05:32:56'),
	(131, 14, 'dormitory_room_1.jpg', 1, 1, '2025-10-06 05:32:56'),
	(132, 14, 'dormitory_room_2.jpg', 0, 2, '2025-10-06 05:32:56'),
	(133, 14, 'dormitory_room_3.jpg', 0, 3, '2025-10-06 05:32:56'),
	(134, 14, 'dormitory_room_4.jpg', 0, 4, '2025-10-06 05:32:56'),
	(135, 14, 'dormitory_room_5.jpg', 0, 5, '2025-10-06 05:32:56'),
	(136, 14, 'dormitory_room_6.jpg', 0, 6, '2025-10-06 05:32:56'),
	(137, 14, 'dormitory_room_7.jpg', 0, 7, '2025-10-06 05:32:56'),
	(138, 14, 'dormitory_room_8.jpg', 0, 8, '2025-10-06 05:32:56'),
	(139, 14, 'dormitory_room_9.jpg', 0, 9, '2025-10-06 05:32:56'),
	(140, 14, 'dormitory_room_10.jpg', 0, 10, '2025-10-06 05:32:56');

-- Dumping structure for table hotelia_db.service_categories
CREATE TABLE IF NOT EXISTS `service_categories` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `icon` varchar(50) NOT NULL,
  `description` text,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.service_categories: ~8 rows (approximately)
INSERT INTO `service_categories` (`category_id`, `name`, `icon`, `description`, `is_active`, `created_at`) VALUES
	(1, 'Room Amenities', 'shirt', 'Extra towels, pillows, toiletries and room items', 1, '2025-10-30 12:47:22'),
	(2, 'Housekeeping', 'sparkles', 'Cleaning services and room maintenance', 1, '2025-10-30 12:47:22'),
	(3, 'Maintenance', 'wrench', 'Repair requests and technical issues', 1, '2025-10-30 12:47:22'),
	(4, 'Food & Beverage', 'utensils', 'Room service and dining requests', 1, '2025-10-30 12:47:22'),
	(5, 'Concierge', 'concierge-bell', 'Local recommendations and assistance', 1, '2025-10-30 12:47:22'),
	(6, 'Transportation', 'car', 'Taxi, shuttle and transportation services', 1, '2025-10-30 12:47:22'),
	(7, 'General Help', 'help-circle', 'Other questions and assistance', 1, '2025-10-30 12:47:22'),
	(8, 'Emergency', 'alert-triangle', 'Urgent situations requiring immediate attention', 1, '2025-10-30 12:47:22');

-- Dumping structure for table hotelia_db.service_forms
CREATE TABLE IF NOT EXISTS `service_forms` (
  `form_id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `form_name` varchar(100) NOT NULL,
  `form_config` json NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`form_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `service_forms_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `service_categories` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.service_forms: ~2 rows (approximately)
INSERT INTO `service_forms` (`form_id`, `category_id`, `form_name`, `form_config`, `is_active`, `created_at`) VALUES
	(1, 1, 'Extra Towels & Linens', '{"fields": [{"name": "item_type", "type": "dropdown", "label": "What do you need?", "options": [{"label": "Bath Towels", "value": "bath_towels"}, {"label": "Hand Towels", "value": "hand_towels"}, {"label": "Face Towels", "value": "face_towels"}, {"label": "Bath Mat", "value": "bath_mat"}, {"label": "Extra Pillows", "value": "extra_pillows"}, {"label": "Extra Blankets", "value": "extra_blankets"}], "required": true}, {"max": 10, "min": 1, "name": "quantity", "type": "number", "label": "How many?", "default": 2, "required": true}, {"name": "special_instructions", "type": "textarea", "label": "Special Instructions", "required": false, "placeholder": "Any special requests or delivery instructions..."}]}', 1, '2025-10-30 12:47:22'),
	(2, 3, 'Air Conditioning Issues', '{"fields": [{"name": "issue_type", "type": "dropdown", "label": "What\'s the problem?", "options": [{"label": "Not cooling", "value": "not_cooling"}, {"label": "Won\'t turn on", "value": "not_turning_on"}, {"label": "Making strange noise", "value": "making_noise"}, {"label": "Water leaking", "value": "leaking"}, {"label": "Strange smell", "value": "smelling"}, {"label": "Other issue", "value": "other"}], "required": true}, {"name": "description", "type": "textarea", "label": "Please describe the issue", "required": true, "placeholder": "When did it start? What have you noticed?"}, {"name": "urgent", "type": "checkbox", "label": "This is urgent - room is uncomfortable", "required": false}]}', 1, '2025-10-30 12:47:22');

-- Dumping structure for table hotelia_db.service_requests
CREATE TABLE IF NOT EXISTS `service_requests` (
  `request_id` int NOT NULL AUTO_INCREMENT,
  `guest_id` int NOT NULL,
  `booking_id` int DEFAULT NULL,
  `category_id` int NOT NULL,
  `form_id` int DEFAULT NULL,
  `room_id` int DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `request_data` json DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('pending','assigned','in_progress','completed','cancelled','done') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'pending',
  `guest_room_number` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`request_id`),
  KEY `guest_id` (`guest_id`),
  KEY `category_id` (`category_id`),
  KEY `form_id` (`form_id`),
  KEY `room_id` (`room_id`),
  KEY `idx_booking_id` (`booking_id`),
  CONSTRAINT `fk_service_requests_bookings` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE SET NULL,
  CONSTRAINT `service_requests_ibfk_1` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`),
  CONSTRAINT `service_requests_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `service_categories` (`category_id`),
  CONSTRAINT `service_requests_ibfk_3` FOREIGN KEY (`form_id`) REFERENCES `service_forms` (`form_id`),
  CONSTRAINT `service_requests_ibfk_4` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.service_requests: ~7 rows (approximately)
INSERT INTO `service_requests` (`request_id`, `guest_id`, `booking_id`, `category_id`, `form_id`, `room_id`, `title`, `description`, `request_data`, `priority`, `status`, `guest_room_number`, `created_at`, `updated_at`) VALUES
	(3, 1, 1, 2, NULL, NULL, 'Housekeeping Request', 'asd', '{"quantity": "1", "amenity_type": "bath_mat", "special_instructions": "asd"}', 'medium', 'completed', NULL, '2025-10-30 14:43:20', '2025-11-04 15:11:22'),
	(4, 1, 1, 1, 1, 8, 'Room Amenities Request', 'adasdasdaasd', '{"request_type": "towel_change", "special_instructions": "asdasdasdasd asdasd asdasd"}', 'medium', 'completed', 'G102', '2025-10-30 23:22:38', '2025-11-04 15:11:06'),
	(5, 1, NULL, 3, 2, 7, 'Maintenance Request', '', '{"urgent": "1", "issue_type": "tv_internet", "description": "no internet"}', 'medium', 'completed', 'G101', '2025-11-04 05:35:11', '2025-11-04 15:11:01'),
	(6, 7, 11, 3, 2, 7, 'Maintenance Request', '', '{"issue_type": "tv_internet", "description": "asdasd"}', 'medium', 'assigned', 'G101', '2025-11-05 03:39:31', '2025-11-05 03:39:45'),
	(7, 1, 2, 1, 1, 13, 'Room Amenities Request', '', '{"request_type": "towel_change", "special_instructions": ""}', 'medium', 'completed', '202', '2025-11-08 07:29:53', '2025-11-08 07:30:27'),
	(8, 1, 2, 1, 1, 13, 'Room Amenities Request', 'sadadadsa', '{"request_type": "bed_linen_change", "special_instructions": "sadadsa"}', 'medium', 'completed', '202', '2025-11-18 13:32:02', '2025-11-18 13:32:12'),
	(9, 1, 2, 1, 1, 13, 'Room Amenities Request', 'asdadad', '{"request_type": "towel_change", "special_instructions": "dasdad"}', 'medium', 'pending', '202', '2025-11-18 13:32:22', '2025-11-18 13:32:22');

-- Dumping structure for table hotelia_db.staff
CREATE TABLE IF NOT EXISTS `staff` (
  `staff_id` int NOT NULL AUTO_INCREMENT,
  `account_id` int NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','reception','fnb') NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`staff_id`),
  UNIQUE KEY `account_id` (`account_id`),
  CONSTRAINT `fk_staff_accounts` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.staff: ~0 rows (approximately)
INSERT INTO `staff` (`staff_id`, `account_id`, `full_name`, `role`, `status`, `email`, `phone`) VALUES
	(2, 4, 'Reception Staff', 'reception', 'active', 'reception@hotelia.com', '123-456-7890');

-- Dumping structure for table hotelia_db.user_sessions
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `session_id` int NOT NULL AUTO_INCREMENT,
  `account_id` int NOT NULL,
  `session_token` varchar(128) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL,
  PRIMARY KEY (`session_id`),
  KEY `session_token` (`session_token`),
  KEY `account_id` (`account_id`),
  CONSTRAINT `fk_sessions_accounts` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=133 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.user_sessions: ~1 rows (approximately)
INSERT INTO `user_sessions` (`session_id`, `account_id`, `session_token`, `ip_address`, `user_agent`, `created_at`, `expires_at`) VALUES
	(132, 1, 'po322p0ad2hhqd67avreu1u5ao', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 02:27:50', '2025-12-03 06:52:02');

-- Dumping structure for table hotelia_db.view_types
CREATE TABLE IF NOT EXISTS `view_types` (
  `view_type_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`view_type_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.view_types: ~7 rows (approximately)
INSERT INTO `view_types` (`view_type_id`, `name`) VALUES
	(8, 'City View'),
	(11, 'Courtyard View'),
	(7, 'Garden View'),
	(9, 'Mountain View'),
	(6, 'Pool View'),
	(5, 'Sea View'),
	(10, 'Sunset View');

-- Dumping structure for trigger hotelia_db.trg_generate_reference_no
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `trg_generate_reference_no` BEFORE INSERT ON `bookings` FOR EACH ROW BEGIN
  DECLARE ref_candidate VARCHAR(20);
  DECLARE duplicate_found INT DEFAULT 1;

  WHILE duplicate_found = 1 DO
    SET ref_candidate = CONCAT(
      'HTL',
      DATE_FORMAT(NOW(), '%y%m%d'),
      '-',
      LPAD(FLOOR(RAND() * 9000 + 1000), 4, '0')
    );

    -- Check if it already exists, with explicit collation to match the column
    SELECT COUNT(*) INTO duplicate_found 
    FROM bookings 
    WHERE reference_no COLLATE utf8mb4_0900_ai_ci = ref_candidate COLLATE utf8mb4_0900_ai_ci;
  END WHILE;

  SET NEW.reference_no = ref_candidate;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `room_availability`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `room_availability` AS select `r`.`room_id` AS `room_id`,`r`.`room_number` AS `room_number`,`r`.`floor` AS `floor`,`rt`.`name` AS `room_type`,`bt`.`name` AS `bed_type`,`vt`.`name` AS `view_type`,`r`.`capacity` AS `capacity`,`r`.`max_extra` AS `max_extra`,`r`.`base_price` AS `base_price`,`r`.`status` AS `status`,`r`.`maintenance_status` AS `maintenance_status`,(case when ((`r`.`status` = 'available') and (`r`.`maintenance_status` = 'good')) then 'Available' when (`r`.`status` = 'occupied') then 'Occupied' when (`r`.`status` = 'maintenance') then 'Under Maintenance' when (`r`.`status` = 'reserved') then 'Reserved' when (`r`.`maintenance_status` = 'cleaning') then 'Cleaning' when (`r`.`maintenance_status` = 'under_repair') then 'Under Repair' else 'Not Available' end) AS `availability_status` from (((`rooms` `r` join `room_types` `rt` on((`r`.`room_type_id` = `rt`.`room_type_id`))) join `bed_types` `bt` on((`r`.`bed_type_id` = `bt`.`bed_type_id`))) join `view_types` `vt` on((`r`.`view_type_id` = `vt`.`view_type_id`))) order by `r`.`floor`,`r`.`room_number`;

-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `room_types_with_capacity`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `room_types_with_capacity` AS select `rt`.`room_type_id` AS `room_type_id`,`rt`.`name` AS `name`,`rt`.`base_price` AS `base_price`,`rt`.`description` AS `description`,`rt`.`max_capacity` AS `max_capacity`,coalesce((select max((`rooms`.`capacity` + `rooms`.`max_extra`)) from `rooms` where (`rooms`.`room_type_id` = `rt`.`room_type_id`)),`rt`.`max_capacity`) AS `calculated_max_capacity` from `room_types` `rt`;

-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `room_type_availability`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `room_type_availability` AS with `prebooked_counts` as (select `bi`.`room_type_id` AS `room_type_id`,count(distinct `bi`.`booking_item_id`) AS `prebooked_count` from ((`booking_items` `bi` join `bookings` `b` on((`bi`.`booking_id` = `b`.`booking_id`))) join `payments` `p` on((`b`.`booking_id` = `p`.`booking_id`))) where ((`bi`.`room_id` is null) and (`b`.`status` in ('confirmed','pending_verification')) and (`p`.`status` in ('completed','pending_verification')) and (curdate() between `b`.`check_in_date` and `b`.`check_out_date`)) group by `bi`.`room_type_id`), `room_counts` as (select `rooms`.`room_type_id` AS `room_type_id`,count(0) AS `total_rooms`,count((case when ((`rooms`.`status` = 'available') and (`rooms`.`maintenance_status` = 'good')) then 1 end)) AS `available_rooms`,count((case when (`rooms`.`status` = 'occupied') then 1 end)) AS `occupied_rooms`,count((case when ((`rooms`.`status` = 'maintenance') or (`rooms`.`maintenance_status` <> 'good')) then 1 end)) AS `maintenance_rooms` from `rooms` group by `rooms`.`room_type_id`) select `rt`.`room_type_id` AS `room_type_id`,`rt`.`name` AS `room_type_name`,`rt`.`base_price` AS `base_price`,`rt`.`max_capacity` AS `max_capacity`,`rc`.`total_rooms` AS `total_rooms`,`rc`.`available_rooms` AS `physically_available`,coalesce(`pc`.`prebooked_count`,0) AS `prebooked_count`,`rc`.`occupied_rooms` AS `occupied_rooms`,`rc`.`maintenance_rooms` AS `maintenance_rooms`,greatest((`rc`.`available_rooms` - coalesce(`pc`.`prebooked_count`,0)),0) AS `available_for_booking`,(case when (greatest((`rc`.`available_rooms` - coalesce(`pc`.`prebooked_count`,0)),0) > 0) then 'Available' else 'Fully Booked' end) AS `availability_status` from ((`room_types` `rt` left join `room_counts` `rc` on((`rt`.`room_type_id` = `rc`.`room_type_id`))) left join `prebooked_counts` `pc` on((`rt`.`room_type_id` = `pc`.`room_type_id`))) order by `rt`.`room_type_id`;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
