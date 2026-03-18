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
  `account_type` enum('staff','guest','admin') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`account_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.accounts: ~18 rows (approximately)
DELETE FROM `accounts`;
INSERT INTO `accounts` (`account_id`, `username`, `password`, `account_type`, `created_at`) VALUES
	(1, 'loki', '$2y$10$kwZPk4FdzMpFusU5qfu26eTyiFgI.YUy42ZB4vVQUDi7BN0Knjos2', 'guest', '2025-09-16 05:59:12'),
	(2, 'LokiMobile', '$2y$10$MEYg9zMvgkK9MQ.LAMc0t.cXL5kF7CqByyTAcewup2JehXTEl/j5O', 'guest', '2025-09-29 15:30:56'),
	(4, 'reception_staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', '2025-10-28 14:41:50'),
	(6, 'niel123', '$2y$10$VUadkOQUcbvxOdMLsgB1becZt6U7XAMDK3V2bb1rVOBI94GdkRTjy', 'guest', '2025-11-04 05:21:12'),
	(7, 'lokiit', '$2y$10$9Zw2a3vts.oLgt67hQd9x.mB9lE9vYINalHrg2nbXo22av6MSM8sa', 'guest', '2025-11-04 13:54:51'),
	(8, 'kd', '$2y$10$vqGnVkJmiD5JZdfQL0CI6eqc.4oiPyYIUU6veN1MV7fWcns7JdXTC', 'guest', '2025-11-05 03:17:47'),
	(9, 'alvin', '$2y$10$dROCyMOm82tfJmCQEv0xHOTb5fhFTl5pSTWMjWBl4YiU3uR/GGi6q', 'guest', '2025-11-05 03:20:38'),
	(10, 'waiter', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', '2026-01-05 08:13:21'),
	(12, 'kitchen', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', '2026-01-20 13:21:48'),
	(13, 'cashier', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', '2026-01-20 13:29:15'),
	(14, 'bj', '$2y$10$Z3PZqf22qGUv/k3LWC1osewbADNTogMdqGC/PLqTKULUEK/2MWIlW', 'guest', '2026-01-10 05:29:03'),
	(16, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2026-01-20 13:29:15'),
	(17, 'tirdy', '$2y$10$FeCgcpxe5PbNrFdSxYNAZedzgA7SKe/HSODhaCm9P3Y80puFHdy7G', 'staff', '2026-01-22 02:36:34'),
	(18, 'yo', '$2y$10$pjGLATETpxqlP2dbYFQSkeS7E/5qRsLg2YIjzgO9GRDdtZzpf/o/q', 'staff', '2026-01-22 02:39:06'),
	(20, 'kdick', '$2y$10$m6ozIPaQ82j2ptlbW7Htme14y32CectlKklayjYEf5iNge.zBSc2q', 'staff', '2026-01-22 02:43:37');
INSERT INTO `accounts` (`account_id`, `username`, `password`, `account_type`, `created_at`) VALUES
	(21, 'joyalrain', '$2y$10$WUHOiqwoSqP1VcooEHhnpe5W.XULGsYegdmEYGTGgtOgbdWpebZXe', 'guest', '2026-01-22 07:56:44'),
	(22, 'Lyka', '$2y$10$B7M5cr0FwTcoOqr6HiJFx.6TYK6JijW5SyQbb02jvUMs8kdIMZae2', 'guest', '2026-01-22 08:31:09'),
	(23, 'kdd', '$2y$10$Ql9.WG9TYzxmhZejuo8xXuS6K4ceuWta5y0Tx.ZWQtewS5nep/ieq', 'guest', '2026-01-22 08:32:00'),
	(24, 'rl', '$2y$10$CJf9kcSuGpjnIkBM6Z9aWOIGTpx0k8DX9VQ/r1TUbG/ecDxtZtpZG', 'guest', '2026-01-24 06:02:44'),
	(25, 'lika', '$2y$10$FQxGFS6uqjs0O2vKUC50puaBqtXlO6Ut8/UulMpHIocRPTYhIRgFe', 'staff', '2026-01-27 02:33:11'),
	(26, 'yeye', '$2y$10$KCTikyvbRNwgTK42HSp7fettDwcAzJddM1NL3rqqUazYccmMCiyPC', 'guest', '2026-02-11 07:17:28');

-- Dumping structure for table hotelia_db.amenities
CREATE TABLE IF NOT EXISTS `amenities` (
  `amenity_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`amenity_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.amenities: ~20 rows (approximately)
DELETE FROM `amenities`;
INSERT INTO `amenities` (`amenity_id`, `name`, `icon`, `updated_at`) VALUES
	(5, 'WiFi', 'wifi', '2026-01-20 12:55:12'),
	(6, 'Air Conditioning', 'snowflake', '2026-01-20 12:55:12'),
	(7, 'Television', 'tv', '2026-01-20 12:55:12'),
	(8, 'Hot and Cold Shower', 'droplet', '2026-01-20 12:55:12'),
	(9, 'Refrigerator', 'fridge', '2025-12-09 07:27:55'),
	(10, 'Mini Bar', 'coffee', '2025-12-09 07:27:55'),
	(11, 'Towels', 'towel', '2025-12-09 07:27:55'),
	(12, 'Toiletries', 'soap', '2025-12-09 07:27:55'),
	(13, 'Wardrobe or Closet', 'hanger', '2025-12-09 07:27:55'),
	(14, 'Balcony or Terrace', 'sun', '2025-12-09 07:27:55'),
	(15, 'Desk and Chair', 'briefcase', '2025-12-09 07:27:55'),
	(16, 'Complimentary Breakfast', 'coffee-cup', '2025-12-09 07:27:55'),
	(17, 'Room Service', 'bell', '2025-12-09 07:27:55'),
	(18, 'Safety Deposit Box', 'shield', '2025-12-09 07:27:55'),
	(19, 'Electric Kettle', 'cup-saucer', '2025-12-09 07:27:55'),
	(20, 'Free Parking', 'car', '2025-12-09 07:27:55'),
	(21, '24-hour Reception', 'clock', '2025-12-09 07:27:55'),
	(22, 'Laundry Service', 'wash', '2025-12-09 07:27:55'),
	(23, 'Swimming Pool Access', 'waves', '2025-12-09 07:27:55'),
	(24, 'Fitness Gym Access', 'dumbbell', '2025-12-09 07:27:55');

-- Dumping structure for table hotelia_db.bed_types
CREATE TABLE IF NOT EXISTS `bed_types` (
  `bed_type_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`bed_type_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.bed_types: ~6 rows (approximately)
DELETE FROM `bed_types`;
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
  `check_in_date` datetime NOT NULL,
  `check_out_date` datetime NOT NULL,
  `nights` int NOT NULL,
  `room_subtotal` decimal(10,2) DEFAULT '0.00',
  `extra_person_total` decimal(10,2) DEFAULT '0.00',
  `discount_amount` decimal(10,2) DEFAULT '0.00',
  `grand_total` decimal(10,2) DEFAULT '0.00',
  `status` enum('pending_payment','pending_verification','confirmed','assigned','checked_in','checked_out','cancelled','verification_failed','pending_submission') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'pending_verification',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `tax_amount` decimal(10,2) DEFAULT '0.00',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`booking_id`),
  UNIQUE KEY `reference_no` (`reference_no`),
  KEY `guest_id` (`guest_id`),
  KEY `idx_booking_dates` (`check_in_date`,`check_out_date`),
  KEY `idx_room_type_dates` (`check_in_date`,`check_out_date`),
  CONSTRAINT `fk_bookings_guests` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.bookings: ~4 rows (approximately)
DELETE FROM `bookings`;
INSERT INTO `bookings` (`booking_id`, `reference_no`, `guest_id`, `check_in_date`, `check_out_date`, `nights`, `room_subtotal`, `extra_person_total`, `discount_amount`, `grand_total`, `status`, `created_at`, `tax_amount`, `updated_at`) VALUES
	(4, 'HTL260313-8817', 20, '2026-03-13 00:00:00', '2026-03-15 00:00:00', 2, 24000.00, 0.00, 0.00, 26880.00, 'checked_out', '2026-03-13 07:58:59', 2880.00, '2026-03-13 08:56:48'),
	(5, 'HTL260313-3217', 21, '2026-03-13 00:00:00', '2026-03-14 00:00:00', 1, 10000.00, 0.00, 0.00, 11200.00, 'checked_out', '2026-03-13 08:06:18', 1200.00, '2026-03-13 08:08:59'),
	(6, 'HTL260313-4654', 22, '2026-03-13 00:00:00', '2026-03-14 00:00:00', 1, 1.00, 0.00, 0.00, 1.12, 'checked_out', '2026-03-13 08:19:41', 0.12, '2026-03-13 08:56:25'),
	(7, 'HTL260313-7001', 8, '2026-03-13 00:00:00', '2026-03-16 00:00:00', 3, 2403.00, 0.00, 0.00, 2691.36, 'confirmed', '2026-03-13 10:10:03', 288.36, '2026-03-13 10:12:06'),
	(8, 'HTL260313-7968', 8, '2026-03-13 00:00:00', '2026-03-14 00:00:00', 1, 1500.00, 0.00, 0.00, 1803.20, 'checked_out', '2026-03-13 10:20:45', 180.00, '2026-03-13 10:26:41');

-- Dumping structure for table hotelia_db.booking_items
CREATE TABLE IF NOT EXISTS `booking_items` (
  `booking_item_id` int NOT NULL AUTO_INCREMENT,
  `booking_id` int NOT NULL,
  `room_type_id` int NOT NULL,
  `room_id` int DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `assigned_by_staff` int DEFAULT NULL,
  `adults` int DEFAULT '1',
  `children` int DEFAULT '0',
  `room_rate_applied` decimal(10,2) NOT NULL DEFAULT '0.00',
  `extra_person_rate_applied` decimal(10,2) NOT NULL DEFAULT '0.00',
  `extra_persons` int DEFAULT '0',
  `item_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('pending_verification','pending_payment','pending','confirmed','assigned','checked_in','checked_out','cancelled','pending_submission') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'pending_verification',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`booking_item_id`),
  KEY `room_type_id` (`room_type_id`),
  KEY `room_id` (`room_id`),
  KEY `idx_booking_item_status` (`booking_id`,`status`),
  KEY `idx_assigned_at` (`assigned_at`),
  KEY `idx_assigned_by` (`assigned_by_staff`),
  CONSTRAINT `booking_items_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  CONSTRAINT `booking_items_ibfk_2` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`room_type_id`),
  CONSTRAINT `booking_items_ibfk_3` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`),
  CONSTRAINT `fk_assigned_by_staff` FOREIGN KEY (`assigned_by_staff`) REFERENCES `staff` (`staff_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.booking_items: ~5 rows (approximately)
DELETE FROM `booking_items`;
INSERT INTO `booking_items` (`booking_item_id`, `booking_id`, `room_type_id`, `room_id`, `assigned_at`, `assigned_by_staff`, `adults`, `children`, `room_rate_applied`, `extra_person_rate_applied`, `extra_persons`, `item_total`, `status`, `updated_at`) VALUES
	(4, 4, 11, 25, '2026-03-13 07:58:59', 2, 5, 1, 12000.00, 0.00, 2, 24000.00, 'checked_in', '2026-03-13 08:00:18'),
	(5, 5, 12, 26, '2026-03-13 08:06:18', 2, 2, 0, 10000.00, 0.00, 0, 10000.00, 'checked_in', '2026-03-13 08:07:23'),
	(6, 6, 21, 35, '2026-03-13 08:19:41', 2, 2, 0, 1.00, 0.00, 0, 1.00, 'checked_in', '2026-03-13 08:19:51'),
	(7, 7, 14, NULL, NULL, NULL, 1, 1, 800.00, 0.00, 0, 2400.00, 'confirmed', '2026-03-13 10:12:06'),
	(8, 7, 21, NULL, NULL, NULL, 1, 1, 1.00, 0.00, 0, 3.00, 'confirmed', '2026-03-13 10:12:06'),
	(9, 8, 4, 7, '2026-03-13 10:21:53', 2, 1, 0, 1500.00, 500.00, 0, 1500.00, 'checked_in', '2026-03-13 10:22:06');

-- Dumping structure for function hotelia_db.get_available_room_count
DELIMITER //
CREATE FUNCTION `get_available_room_count`(
	`room_type_id_param` INT,
	`check_in_param` DATETIME,
	`check_out_param` DATETIME
) RETURNS int
    READS SQL DATA
    DETERMINISTIC
BEGIN
    DECLARE total_physical INT;
    DECLARE total_booked INT;

    -- Get total physical rooms that are not in maintenance
    SELECT COUNT(*) INTO total_physical 
    FROM rooms 
    WHERE room_type_id = room_type_id_param 
    AND maintenance_status = 'good';

    -- Count overlapping bookings
    SELECT COUNT(*) INTO total_booked 
    FROM booking_items bi
    JOIN bookings b ON bi.booking_id = b.booking_id
    WHERE bi.room_type_id = room_type_id_param
    AND b.status IN (
    'pending_payment',
    'pending_verification',
    'confirmed',
    'assigned',
    'checked_in',
    'pending_submission'
)
    -- Overlap Logic: Requested In < Existing Out AND Requested Out > Existing In
    AND check_in_param < b.check_out_date 
    AND check_out_param > b.check_in_date;

    -- Return the result (ensuring it's not negative)
    RETURN GREATEST(0, total_physical - total_booked);
END//
DELIMITER ;

-- Dumping structure for view hotelia_db.global_update_signal
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `global_update_signal` (
	`last_change` TIMESTAMP NULL
) ENGINE=MyISAM;

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
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.guests: ~18 rows (approximately)
DELETE FROM `guests`;
INSERT INTO `guests` (`guest_id`, `account_id`, `full_name`, `phone`, `email`, `guest_type`, `created_at`) VALUES
	(1, 1, 'Loki Baltazar', '09917344888', 'loki@gmail.com', 'room_guest', '2025-09-16 05:59:12'),
	(2, 2, 'Loki Mobile Test', '09146774461', 'lokimobile@gmail.com', 'room_guest', '2025-09-29 15:30:56'),
	(3, 6, 'niel alvin barangan', '09164424647', 'nielbars@gmail.com', 'room_guest', '2025-11-04 05:21:12'),
	(4, 7, 'lokiit jasjas', '09912334455', 'lokiit@gmail.com', 'room_guest', '2025-11-04 13:54:51'),
	(6, 8, 'khyle sibucao', '09123456789', 'kd@a.com', 'room_guest', '2025-11-05 03:17:47'),
	(7, 9, 'alvin barangan', '09923096293', 'example@gmail.com', 'room_guest', '2025-11-05 03:20:38'),
	(8, 14, 'bj', '', 'cat@dog.com', 'room_guest', '2026-01-10 05:29:03'),
	(9, 21, 'JOYALRAIN MACADAEG DOMINGO', '09623324306', 'jraindomingo@gmail.com', 'room_guest', '2026-01-22 07:56:44'),
	(10, 22, 'Lovely Lyka Raymundo', '09157325460', 'raymundolovelylyka@gmail.com', 'room_guest', '2026-01-22 08:31:09'),
	(11, 23, 'kdd', '11111111111', 'kd@dog.com', 'room_guest', '2026-01-22 08:32:00'),
	(12, 24, 'ralph lawrence lagat', '11111111111', 'rl@dog.com', 'room_guest', '2026-01-24 06:02:44'),
	(13, 26, 'jorlei  laforga', '11111111111', 'yeye@dog.com', 'room_guest', '2026-02-11 07:17:28'),
	(14, NULL, 'AJ Domingo', '09654863211', 'aj@email.com', 'room_guest', '2026-02-11 07:33:24'),
	(15, NULL, 'Lovely Shock Raymundo', '09999999999', 'l@dog.com', 'room_guest', '2026-03-12 08:04:24'),
	(16, NULL, 'barney', '09999999999', 'barney@dog.com', 'room_guest', '2026-03-12 11:10:55'),
	(20, NULL, 'pogi123', '09156526156', 'pogi@dog.com', 'room_guest', '2026-03-13 07:58:59'),
	(21, NULL, 'barney', '09999999999', 'barney@dog.com', 'room_guest', '2026-03-13 08:06:18');
INSERT INTO `guests` (`guest_id`, `account_id`, `full_name`, `phone`, `email`, `guest_type`, `created_at`) VALUES
	(22, NULL, 'a', '09154421264', 'a@dog.com', 'room_guest', '2026-03-13 08:19:41');

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
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.guest_details: ~8 rows (approximately)
DELETE FROM `guest_details`;
INSERT INTO `guest_details` (`detail_id`, `guest_id`, `id_type`, `id_number`, `address`, `emergency_contact`, `emergency_contact_number`, `profile_picture`, `id_document_photo`) VALUES
	(8, 1, 'Philippine National ID (PhilSys)', '1111111111111111', 'Brgy 17 Sta. Asuncion, San Nicolas Ilocos Norte', 'Rodolfo Antonio Baltazar', '09111111111', '692fe4027e2eb_1764746242.png', '68ee4d34da455_1760447796.jpg'),
	(9, 2, 'Driver\'s License', 'A11-11-1234567', '', '', '', '68daa6cc46f31_1759160012.jpg', NULL),
	(11, 14, 'Driver\'s License', 'A01-22-123456', 'Barangay 1 Laoag City', NULL, NULL, NULL, NULL),
	(12, 15, 'Driver\'s License', 'A01-22-123456', 'Barangay 1 San Lorenzo Laoag City, Ilocos Norte', NULL, NULL, NULL, NULL),
	(13, 16, 'Driver\'s License', 'A01-22-123456', 'Barangay No. 3, Nuestra Señora del Rosario (Poblacion), Laoag City, Ilocos Norte', NULL, NULL, NULL, NULL),
	(17, 20, 'Driver\'s License', 'A01-22-123456', 'Barangay No. 44, Zamboanga, Laoag City, Ilocos Norte', NULL, NULL, NULL, NULL),
	(18, 21, 'Driver\'s License', 'A01-22-123456', 'Barangay No. 43, Cavit, Laoag City, Ilocos Norte', NULL, NULL, NULL, NULL),
	(19, 22, 'Driver\'s License', 'A01-22-123456', 'Barangay No. 41, Balacad, Laoag City, Ilocos Norte', NULL, NULL, NULL, NULL);

-- Dumping structure for table hotelia_db.inventory_categories
CREATE TABLE IF NOT EXISTS `inventory_categories` (
  `cat_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `type` enum('hotel','fnb') NOT NULL,
  PRIMARY KEY (`cat_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.inventory_categories: ~4 rows (approximately)
DELETE FROM `inventory_categories`;
INSERT INTO `inventory_categories` (`cat_id`, `name`, `type`) VALUES
	(1, 'Room Toiletries', 'hotel'),
	(2, 'Linens', 'hotel'),
	(3, 'Kitchen Seasonings', 'fnb'),
	(4, 'Beverage Stock', 'fnb');

-- Dumping structure for table hotelia_db.inventory_items
CREATE TABLE IF NOT EXISTS `inventory_items` (
  `item_id` int NOT NULL AUTO_INCREMENT,
  `item_name` varchar(100) NOT NULL,
  `category` enum('Meat','Produce','Dry Goods','Beverage Base','Dairy','Seafood') DEFAULT 'Dry Goods',
  `stock_level` decimal(10,2) DEFAULT '0.00',
  `unit_of_measure` varchar(20) DEFAULT 'pcs',
  `min_stock_threshold` decimal(10,2) DEFAULT '5.00',
  PRIMARY KEY (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.inventory_items: ~15 rows (approximately)
DELETE FROM `inventory_items`;
INSERT INTO `inventory_items` (`item_id`, `item_name`, `category`, `stock_level`, `unit_of_measure`, `min_stock_threshold`) VALUES
	(1, 'Pork Belly', 'Meat', 50.00, 'kg', 10.00),
	(2, 'Beef Shank', 'Meat', 30.00, 'kg', 5.00),
	(3, 'Whole Chicken', 'Meat', 40.00, 'pcs', 10.00),
	(4, 'Ground Pork', 'Meat', 20.00, 'kg', 5.00),
	(5, 'Squid (Large)', 'Seafood', 15.00, 'kg', 5.00),
	(6, 'Calamansi', 'Produce', 500.00, 'pcs', 100.00),
	(7, 'Rice (Jasmine)', 'Dry Goods', 100.00, 'kg', 20.00),
	(8, 'Soy Sauce', 'Dry Goods', 20.00, 'L', 5.00),
	(9, 'Vinegar', 'Dry Goods', 20.00, 'L', 5.00),
	(10, 'Cooking Oil', 'Dry Goods', 50.00, 'L', 10.00),
	(11, 'Eggplant', 'Produce', 30.00, 'pcs', 10.00),
	(12, 'Coconut Milk', 'Dairy', 40.00, 'L', 10.00),
	(13, 'Sugar (White)', 'Dry Goods', 25.00, 'kg', 5.00),
	(14, 'Coffee Beans (Barako)', 'Dry Goods', 10.00, 'kg', 2.00),
	(15, 'San Miguel Pilsen', 'Beverage Base', 120.00, 'pcs', 24.00);

-- Dumping structure for function hotelia_db.is_room_available
DELIMITER //
CREATE FUNCTION `is_room_available`(
    room_id_param INT,
    check_in_param DATE,
    check_out_param DATE
) RETURNS tinyint(1)
    READS SQL DATA
    DETERMINISTIC
BEGIN
    -- Check for overlapping bookings
    IF EXISTS (
        SELECT 1 FROM booking_items bi
        JOIN bookings b ON bi.booking_id = b.booking_id
        WHERE bi.room_id = room_id_param
        AND b.status IN ('confirmed', 'assigned', 'checked_in')
        AND check_in_param < b.check_out_date
        AND check_out_param > b.check_in_date
    ) THEN
        RETURN FALSE;
    END IF;
    
    RETURN TRUE;
END//
DELIMITER ;

-- Dumping structure for table hotelia_db.laoag_barangays
CREATE TABLE IF NOT EXISTS `laoag_barangays` (
  `barangay_id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `barangay_no` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `barangay_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `district` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`barangay_id`)
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table hotelia_db.laoag_barangays: ~80 rows (approximately)
DELETE FROM `laoag_barangays`;
INSERT INTO `laoag_barangays` (`barangay_id`, `full_name`, `barangay_no`, `barangay_name`, `district`) VALUES
	(1, 'Barangay No. 1, San Lorenzo (Poblacion)', '1', 'San Lorenzo', 'Poblacion'),
	(2, 'Barangay No. 2, Santa Joaquina (Poblacion)', '2', 'Santa Joaquina', 'Poblacion'),
	(3, 'Barangay No. 3, Nuestra Señora del Rosario (Poblacion)', '3', 'Nuestra Señora del Rosario', 'Poblacion'),
	(4, 'Barangay No. 4, San Guillermo (Poblacion)', '4', 'San Guillermo', 'Poblacion'),
	(5, 'Barangay No. 5, San Pedro (Poblacion)', '5', 'San Pedro', 'Poblacion'),
	(6, 'Barangay No. 6, San Agustin (Poblacion)', '6', 'San Agustin', 'Poblacion'),
	(7, 'Barangay No. 7-A, Nuestra Señora del Natividad (Poblacion)', '7-A', 'Nuestra Señora del Natividad', 'Poblacion'),
	(8, 'Barangay No. 7-B, Nuestra Señora del Natividad (Poblacion)', '7-B', 'Nuestra Señora del Natividad', 'Poblacion'),
	(9, 'Barangay No. 8, San Vicente (Poblacion)', '8', 'San Vicente', 'Poblacion'),
	(10, 'Barangay No. 9, Santa Angela (Poblacion)', '9', 'Santa Angela', 'Poblacion'),
	(11, 'Barangay No. 10, San Jose (Poblacion)', '10', 'San Jose', 'Poblacion'),
	(12, 'Barangay No. 11, Santa Balbina (Poblacion)', '11', 'Santa Balbina', 'Poblacion'),
	(13, 'Barangay No. 12, San Isidro (Poblacion)', '12', 'San Isidro', 'Poblacion'),
	(14, 'Barangay No. 13, Nuestra Señora de Visitacion (Poblacion)', '13', 'Nuestra Señora de Visitacion', 'Poblacion'),
	(15, 'Barangay No. 14, Santo Tomas (Poblacion)', '14', 'Santo Tomas', 'Poblacion'),
	(16, 'Barangay No. 15, San Guillermo (Poblacion)', '15', 'San Guillermo', 'Poblacion'),
	(17, 'Barangay No. 16, San Jacinto (Poblacion)', '16', 'San Jacinto', 'Poblacion'),
	(18, 'Barangay No. 17, San Francisco (Poblacion)', '17', 'San Francisco', 'Poblacion');
INSERT INTO `laoag_barangays` (`barangay_id`, `full_name`, `barangay_no`, `barangay_name`, `district`) VALUES
	(19, 'Barangay No. 18, San Quirino (Poblacion)', '18', 'San Quirino', 'Poblacion'),
	(20, 'Barangay No. 19, Santa Marcela (Poblacion)', '19', 'Santa Marcela', 'Poblacion'),
	(21, 'Barangay No. 20, San Miguel (Poblacion)', '20', 'San Miguel', 'Poblacion'),
	(22, 'Barangay No. 21, San Pedro (Poblacion)', '21', 'San Pedro', 'Poblacion'),
	(23, 'Barangay No. 22, San Andres (Poblacion)', '22', 'San Andres', 'Poblacion'),
	(24, 'Barangay No. 23, San Matias (Poblacion)', '23', 'San Matias', 'Poblacion'),
	(25, 'Barangay No. 24, Nuestra Señora de Consolacion (Poblacion)', '24', 'Nuestra Señora de Consolacion', 'Poblacion'),
	(26, 'Barangay No. 25, Santa Cayetana (Poblacion)', '25', 'Santa Cayetana', 'Poblacion'),
	(27, 'Barangay No. 26, San Marcelino (Poblacion)', '26', 'San Marcelino', 'Poblacion'),
	(28, 'Barangay No. 27, Nuestra Señora de Soledad (Poblacion)', '27', 'Nuestra Señora de Soledad', 'Poblacion'),
	(29, 'Barangay No. 28, San Bernardo (Poblacion)', '28', 'San Bernardo', 'Poblacion'),
	(30, 'Barangay No. 29, Santo Tomas (Poblacion)', '29', 'Santo Tomas', 'Poblacion'),
	(31, 'Barangay No. 30-A, Suyo', '30-A', 'Suyo', NULL),
	(32, 'Barangay No. 30-B, Santa Maria', '30-B', 'Santa Maria', NULL),
	(33, 'Barangay No. 31, Talingaan', '31', 'Talingaan', NULL),
	(34, 'Barangay No. 32-A, La Paz East', '32-A', 'La Paz East', NULL),
	(35, 'Barangay No. 32-B, La Paz West', '32-B', 'La Paz West', NULL),
	(36, 'Barangay No. 32-C, La Paz East', '32-C', 'La Paz East', NULL),
	(37, 'Barangay No. 33-A, La Paz Proper', '33-A', 'La Paz Proper', NULL),
	(38, 'Barangay No. 33-B, La Paz Proper', '33-B', 'La Paz Proper', NULL);
INSERT INTO `laoag_barangays` (`barangay_id`, `full_name`, `barangay_no`, `barangay_name`, `district`) VALUES
	(39, 'Barangay No. 34-A, Gabu Norte West', '34-A', 'Gabu Norte West', NULL),
	(40, 'Barangay No. 34-B, Gabu Norte East', '34-B', 'Gabu Norte East', NULL),
	(41, 'Barangay No. 35, Gabu Sur', '35', 'Gabu Sur', NULL),
	(42, 'Barangay No. 36, Araniw', '36', 'Araniw', NULL),
	(43, 'Barangay No. 37, Calayab', '37', 'Calayab', NULL),
	(44, 'Barangay No. 38-A, Mangato East', '38-A', 'Mangato East', NULL),
	(45, 'Barangay No. 38-B, Mangato West', '38-B', 'Mangato West', NULL),
	(46, 'Barangay No. 39, Santa Rosa', '39', 'Santa Rosa', NULL),
	(47, 'Barangay No. 40, Balatong', '40', 'Balatong', NULL),
	(48, 'Barangay No. 41, Balacad', '41', 'Balacad', NULL),
	(49, 'Barangay No. 42, Apaya', '42', 'Apaya', NULL),
	(50, 'Barangay No. 43, Cavit', '43', 'Cavit', NULL),
	(51, 'Barangay No. 44, Zamboanga', '44', 'Zamboanga', NULL),
	(52, 'Barangay No. 45, Tangid', '45', 'Tangid', NULL),
	(53, 'Barangay No. 46, Nalbo', '46', 'Nalbo', NULL),
	(54, 'Barangay No. 47, Bengcag', '47', 'Bengcag', NULL),
	(55, 'Barangay No. 48-A, Cabungaan North', '48-A', 'Cabungaan North', NULL),
	(56, 'Barangay No. 48-B, Cabungaan South', '48-B', 'Cabungaan South', NULL),
	(57, 'Barangay No. 49-A, Darayday', '49-A', 'Darayday', NULL),
	(58, 'Barangay No. 49-B, Raraburan', '49-B', 'Raraburan', NULL),
	(59, 'Barangay No. 50, Buttong', '50', 'Buttong', NULL),
	(60, 'Barangay No. 51-A, Nangalisan East', '51-A', 'Nangalisan East', NULL),
	(61, 'Barangay No. 51-B, Nangalisan West', '51-B', 'Nangalisan West', NULL),
	(62, 'Barangay No. 52-A, San Mateo', '52-A', 'San Mateo', NULL),
	(63, 'Barangay No. 52-B, Lataag', '52-B', 'Lataag', NULL),
	(64, 'Barangay No. 53, Rioeng', '53', 'Rioeng', NULL);
INSERT INTO `laoag_barangays` (`barangay_id`, `full_name`, `barangay_no`, `barangay_name`, `district`) VALUES
	(65, 'Barangay No. 54-A, Camangaan', '54-A', 'Camangaan', NULL),
	(66, 'Barangay No. 54-B, Lagui-Sail', '54-B', 'Lagui-Sail', NULL),
	(67, 'Barangay No. 55-A, Barit-Pandan', '55-A', 'Barit-Pandan', NULL),
	(68, 'Barangay No. 55-B, Salet-Bulangon', '55-B', 'Salet-Bulangon', NULL),
	(69, 'Barangay No. 55-C, Vira', '55-C', 'Vira', NULL),
	(70, 'Barangay No. 56-A, Bacsil North', '56-A', 'Bacsil North', NULL),
	(71, 'Barangay No. 56-B, Bacsil South', '56-B', 'Bacsil South', NULL),
	(72, 'Barangay No. 57, Pila', '57', 'Pila', NULL),
	(73, 'Barangay No. 58, Casili', '58', 'Casili', NULL),
	(74, 'Barangay No. 59-A, Dibua South', '59-A', 'Dibua South', NULL),
	(75, 'Barangay No. 59-B, Dibua North', '59-B', 'Dibua North', NULL),
	(76, 'Barangay No. 60-A, Caaoacan', '60-A', 'Caaoacan', NULL),
	(77, 'Barangay No. 60-B, Madiladig', '60-B', 'Madiladig', NULL),
	(78, 'Barangay No. 61, Cataban', '61', 'Cataban', NULL),
	(79, 'Barangay No. 62-A, Navotas North', '62-A', 'Navotas North', NULL),
	(80, 'Barangay No. 62-B, Navotas South', '62-B', 'Navotas South', NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=578 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.login_attempts: ~375 rows (approximately)
DELETE FROM `login_attempts`;
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
	(31, 'loki', '127.0.0.1', 1, '2025-10-03 18:08:49');
INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `success`, `attempted_at`) VALUES
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
	(62, 'loki', '127.0.0.1', 1, '2025-10-14 04:35:58');
INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `success`, `attempted_at`) VALUES
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
	(93, 'loki', '127.0.0.1', 1, '2025-10-26 15:16:24');
INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `success`, `attempted_at`) VALUES
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
	(121, 'loki', '127.0.0.1', 1, '2025-10-30 11:37:45');
INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `success`, `attempted_at`) VALUES
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
	(148, 'reception_staff', '::1', 1, '2025-11-04 12:38:37');
INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `success`, `attempted_at`) VALUES
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
	(166, 'loki', '127.0.0.1', 1, '2025-12-03 02:27:50'),
	(167, 'lokia', '127.0.0.1', 0, '2025-12-03 07:25:30'),
	(168, 'loki', '127.0.0.1', 1, '2025-12-03 07:25:39'),
	(169, 'loki', '127.0.0.1', 1, '2025-12-03 13:23:07'),
	(170, 'loki', '127.0.0.1', 1, '2025-12-06 12:27:55'),
	(171, 'reception_staff', '127.0.0.1', 1, '2025-12-06 12:36:12'),
	(172, 'loki', '127.0.0.1', 1, '2025-12-06 14:23:39'),
	(173, 'loki', '127.0.0.1', 1, '2025-12-08 03:58:09'),
	(174, 'reception_staff', '127.0.0.1', 1, '2025-12-08 04:09:00'),
	(175, 'loki', '127.0.0.1', 1, '2025-12-08 06:09:06'),
	(176, 'reception_staff', '127.0.0.1', 1, '2025-12-08 06:46:59'),
	(177, 'loki', '127.0.0.1', 1, '2025-12-09 04:16:08');
INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `success`, `attempted_at`) VALUES
	(178, 'loki', '127.0.0.1', 1, '2025-12-09 04:23:48'),
	(179, 'loki', '127.0.0.1', 1, '2025-12-09 04:25:34'),
	(180, 'loki', '127.0.0.1', 1, '2025-12-09 04:25:38'),
	(181, 'loki', '127.0.0.1', 1, '2025-12-09 04:25:42'),
	(182, 'loki', '127.0.0.1', 1, '2025-12-09 04:38:47'),
	(183, 'loki', '127.0.0.1', 1, '2025-12-09 04:43:03'),
	(184, 'loki', '127.0.0.1', 0, '2025-12-09 04:44:56'),
	(185, 'loki', '127.0.0.1', 0, '2025-12-09 04:45:09'),
	(186, 'loki', '127.0.0.1', 1, '2025-12-09 04:45:37'),
	(187, 'loki', '127.0.0.1', 1, '2025-12-09 04:47:55'),
	(188, 'loki', '127.0.0.1', 0, '2025-12-09 04:59:19'),
	(189, 'loki', '127.0.0.1', 1, '2025-12-09 04:59:29'),
	(190, 'loki', '127.0.0.1', 1, '2025-12-09 05:02:55'),
	(191, 'loki', '127.0.0.1', 1, '2025-12-09 06:30:19'),
	(192, 'loki', '127.0.0.1', 1, '2025-12-09 06:30:36'),
	(193, 'loki', '127.0.0.1', 1, '2025-12-09 10:56:31'),
	(194, 'loki', '127.0.0.1', 1, '2025-12-10 05:01:46'),
	(195, 'loki', '127.0.0.1', 1, '2025-12-10 14:52:12'),
	(196, 'loki', '127.0.0.1', 1, '2025-12-11 02:33:26'),
	(197, 'loki', '127.0.0.1', 1, '2025-12-11 11:31:51'),
	(198, 'loki', '127.0.0.1', 1, '2025-12-11 13:36:25'),
	(199, 'loki', '127.0.0.1', 1, '2025-12-12 04:45:49'),
	(200, 'loki', '127.0.0.1', 1, '2025-12-12 05:50:57'),
	(201, 'loki', '127.0.0.1', 1, '2025-12-12 11:34:48'),
	(202, 'loki', '127.0.0.1', 1, '2025-12-12 14:57:30'),
	(203, 'loki', '127.0.0.1', 1, '2025-12-13 04:11:57'),
	(204, 'loki', '127.0.0.1', 1, '2025-12-13 06:34:40'),
	(205, 'loki', '127.0.0.1', 1, '2025-12-13 10:59:09'),
	(206, 'loki', '127.0.0.1', 1, '2025-12-14 05:26:43'),
	(207, 'loki', '127.0.0.1', 1, '2025-12-14 11:39:19'),
	(208, 'loki', '127.0.0.1', 1, '2025-12-15 06:53:49');
INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `success`, `attempted_at`) VALUES
	(209, 'loki', '127.0.0.1', 1, '2025-12-15 11:46:40'),
	(210, 'loki', '127.0.0.1', 1, '2025-12-15 14:57:17'),
	(211, 'loki', '127.0.0.1', 1, '2025-12-16 04:55:56'),
	(212, 'loki', '127.0.0.1', 0, '2025-12-16 12:16:59'),
	(213, 'loki', '127.0.0.1', 1, '2025-12-16 12:17:02'),
	(214, 'loki', '127.0.0.1', 1, '2025-12-17 12:55:31'),
	(215, 'loki', '127.0.0.1', 1, '2025-12-18 04:28:51'),
	(216, 'loki', '127.0.0.1', 1, '2025-12-18 05:34:27'),
	(217, 'loki', '127.0.0.1', 1, '2025-12-18 14:32:25'),
	(218, 'loki', '127.0.0.1', 1, '2025-12-18 16:57:37'),
	(219, 'loki', '127.0.0.1', 1, '2025-12-19 04:26:46'),
	(220, 'loki', '127.0.0.1', 1, '2025-12-19 05:38:21'),
	(221, 'loki', '127.0.0.1', 1, '2025-12-19 12:30:38'),
	(222, 'loki', '127.0.0.1', 1, '2025-12-19 19:32:29'),
	(223, 'loki', '127.0.0.1', 1, '2025-12-19 20:36:53'),
	(224, 'loki', '127.0.0.1', 1, '2025-12-20 02:42:09'),
	(225, 'loki', '127.0.0.1', 1, '2025-12-23 05:01:44'),
	(226, 'loki', '127.0.0.1', 1, '2025-12-25 03:35:28'),
	(227, 'loki', '127.0.0.1', 1, '2025-12-26 05:26:15'),
	(228, 'loki', '127.0.0.1', 1, '2025-12-26 13:26:23'),
	(229, 'loki', '127.0.0.1', 1, '2025-12-26 16:59:17'),
	(230, 'loki', '127.0.0.1', 1, '2025-12-27 11:08:00'),
	(231, 'loki', '127.0.0.1', 1, '2025-12-27 13:20:07'),
	(232, 'loki', '127.0.0.1', 1, '2025-12-28 02:16:57'),
	(233, 'reception_staff', '127.0.0.1', 1, '2025-12-28 02:18:04'),
	(234, 'loki', '127.0.0.1', 1, '2025-12-28 14:02:51'),
	(235, 'reception_staff', '127.0.0.1', 1, '2025-12-28 14:04:55'),
	(236, 'loki', '127.0.0.1', 1, '2025-12-28 17:02:03'),
	(237, 'reception_staff', '127.0.0.1', 1, '2025-12-28 18:25:33'),
	(238, 'reception_staff', '127.0.0.1', 1, '2025-12-28 19:01:16');
INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `success`, `attempted_at`) VALUES
	(239, 'reception_staff', '127.0.0.1', 1, '2025-12-29 02:53:30'),
	(240, 'reception_staff', '127.0.0.1', 1, '2025-12-29 06:26:04'),
	(241, 'reception_staff', '127.0.0.1', 1, '2025-12-29 12:03:57'),
	(242, 'reception_staff', '127.0.0.1', 1, '2025-12-29 16:41:58'),
	(243, 'reception_staff', '127.0.0.1', 1, '2025-12-30 04:44:08'),
	(244, 'reception_staff', '127.0.0.1', 1, '2025-12-30 10:21:45'),
	(245, 'reception_staff', '127.0.0.1', 1, '2026-01-01 04:11:17'),
	(246, 'reception_staff', '127.0.0.1', 1, '2026-01-01 07:07:51'),
	(247, 'reception_staff', '127.0.0.1', 1, '2026-01-02 06:40:20'),
	(248, 'reception_staff', '127.0.0.1', 1, '2026-01-02 14:48:41'),
	(249, 'loki', '127.0.0.1', 1, '2026-01-02 15:55:00'),
	(250, 'reception_staff', '127.0.0.1', 1, '2026-01-03 01:32:34'),
	(251, 'reception_staff', '127.0.0.1', 1, '2026-01-03 07:09:06'),
	(252, 'reception_staff', '127.0.0.1', 1, '2026-01-03 12:14:07'),
	(253, 'reception_staff', '192.168.0.122', 1, '2026-01-03 13:08:10'),
	(254, 'loki', '192.168.0.122', 1, '2026-01-03 13:09:51'),
	(255, 'loki', '10.59.100.12', 1, '2026-01-03 15:01:56'),
	(256, 'reception_staff', '10.59.100.41', 1, '2026-01-03 15:05:25'),
	(257, 'reception_staff', '10.59.100.158', 1, '2026-01-03 15:19:23'),
	(258, 'reception_staff', '::1', 1, '2026-01-03 16:19:32'),
	(259, 'reception_staff', '::1', 0, '2026-01-04 06:06:39'),
	(260, 'reception_staff', '::1', 0, '2026-01-04 06:23:29'),
	(261, 'reception_staff', '::1', 0, '2026-01-04 07:23:42'),
	(262, 'reception_staff', '::1', 1, '2026-01-04 07:23:48'),
	(263, 'Loki', '10.59.100.41', 1, '2026-01-04 07:42:19'),
	(264, 'loki', '10.59.100.208', 1, '2026-01-04 13:57:37'),
	(265, 'Loki', '10.59.100.41', 1, '2026-01-04 14:28:08');
INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `success`, `attempted_at`) VALUES
	(266, 'reception_staff', '::1', 0, '2026-01-04 14:30:02'),
	(267, 'reception_staff', '::1', 1, '2026-01-04 14:30:09'),
	(268, 'reception_staff', '::1', 1, '2026-01-05 03:40:14'),
	(269, 'reception_staff', '::1', 1, '2026-01-05 08:06:33'),
	(270, 'reception_staff', '::1', 1, '2026-01-05 08:14:56'),
	(271, 'waiter', '::1', 1, '2026-01-05 08:15:32'),
	(272, 'waiter', '::1', 1, '2026-01-05 08:42:28'),
	(273, 'waiter', '::1', 1, '2026-01-05 08:52:34'),
	(274, 'waiter', '::1', 1, '2026-01-05 08:56:51'),
	(275, 'waiter', '::1', 1, '2026-01-05 09:02:44'),
	(276, 'waiter', '::1', 1, '2026-01-05 09:03:52'),
	(277, 'waiter', '::1', 1, '2026-01-06 12:03:38'),
	(278, 'waiter', '::1', 1, '2026-01-07 01:35:00'),
	(279, 'kitchen', '::1', 1, '2026-01-07 03:37:15'),
	(280, 'kitchen', '::1', 1, '2026-01-07 04:03:10'),
	(281, 'waiter', '::1', 1, '2026-01-07 04:03:28'),
	(282, 'kitchen', '::1', 1, '2026-01-07 04:04:12'),
	(283, 'kitchen', '::1', 1, '2026-01-07 12:15:21'),
	(284, 'waiter', '::1', 0, '2026-01-07 12:39:53'),
	(285, 'waiter', '::1', 1, '2026-01-07 12:39:58'),
	(286, 'kitchen', '::1', 1, '2026-01-07 12:51:48'),
	(287, 'waiter', '::1', 1, '2026-01-07 12:52:08'),
	(288, 'kitchen', '::1', 1, '2026-01-07 13:17:52'),
	(289, 'waiter', '::1', 1, '2026-01-07 13:19:01'),
	(290, 'kitchen', '::1', 1, '2026-01-07 13:35:42'),
	(291, 'waiter', '::1', 1, '2026-01-07 14:01:21'),
	(292, 'kitchen', '::1', 1, '2026-01-09 05:40:33'),
	(293, 'waiter', '::1', 1, '2026-01-09 06:04:12'),
	(294, 'waiter', '::1', 1, '2026-01-09 10:36:53'),
	(295, 'kitchen', '::1', 1, '2026-01-09 10:42:02'),
	(296, 'cashier', '::1', 1, '2026-01-09 10:46:25'),
	(297, 'cashier', '::1', 1, '2026-01-09 12:12:44');
INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `success`, `attempted_at`) VALUES
	(298, 'waiter', '::1', 1, '2026-01-09 14:40:10'),
	(299, 'cashier', '::1', 1, '2026-01-09 14:42:47'),
	(300, 'waiter', '::1', 1, '2026-01-09 15:30:02'),
	(301, 'waiter', '::1', 1, '2026-01-09 16:16:19'),
	(302, 'waiter', '::1', 1, '2026-01-09 16:41:02'),
	(303, 'waiter', '::1', 1, '2026-01-09 16:41:51'),
	(304, 'waiter', '::1', 1, '2026-01-09 16:50:55'),
	(305, 'waiter', '::1', 1, '2026-01-09 17:06:40'),
	(306, 'waiter', '::1', 1, '2026-01-10 04:28:04'),
	(307, 'cashier', '::1', 1, '2026-01-10 04:28:43'),
	(308, 'kitchen', '::1', 1, '2026-01-10 04:36:56'),
	(309, 'cashier', '::1', 1, '2026-01-10 04:38:55'),
	(310, 'waiter', '::1', 1, '2026-01-10 05:16:13'),
	(311, 'bj', '::1', 0, '2026-01-10 05:28:29'),
	(312, 'bj', '::1', 1, '2026-01-10 05:29:09'),
	(313, 'reception', '::1', 0, '2026-01-10 05:39:57'),
	(314, 'reception_staff', '::1', 1, '2026-01-10 05:40:19'),
	(315, 'waiter', '::1', 1, '2026-01-10 05:44:21'),
	(316, 'kitchen', '::1', 1, '2026-01-10 05:46:34'),
	(317, 'cashier', '::1', 1, '2026-01-10 05:46:59'),
	(318, 'bj', '::1', 1, '2026-01-10 05:52:38'),
	(319, 'waiter', '::1', 1, '2026-01-10 06:40:01'),
	(320, 'kitchen', '::1', 1, '2026-01-10 06:42:08'),
	(321, 'cashier', '::1', 1, '2026-01-10 06:42:53'),
	(322, 'bj', '::1', 1, '2026-01-10 06:55:21'),
	(323, 'cashier', '::1', 1, '2026-01-10 06:56:25'),
	(324, 'cashier', '::1', 1, '2026-01-10 07:38:06'),
	(325, 'waiter', '::1', 1, '2026-01-10 07:39:27'),
	(326, 'cashier', '::1', 1, '2026-01-10 07:52:12'),
	(327, 'waiter', '::1', 1, '2026-01-10 07:56:49'),
	(328, 'cashier', '::1', 1, '2026-01-10 07:57:22'),
	(329, 'waiter', '::1', 1, '2026-01-10 08:01:24'),
	(330, 'cashier', '::1', 1, '2026-01-10 08:01:53');
INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `success`, `attempted_at`) VALUES
	(331, 'waiter', '::1', 1, '2026-01-10 08:06:29'),
	(332, 'cashier', '::1', 1, '2026-01-10 08:27:16'),
	(333, 'waiter', '::1', 1, '2026-01-10 08:28:07'),
	(334, 'cashier', '::1', 1, '2026-01-10 08:28:43'),
	(335, 'cashier', '::1', 1, '2026-01-10 13:07:18'),
	(336, 'waiter', '::1', 1, '2026-01-10 13:13:48'),
	(337, 'waiter', '::1', 1, '2026-01-10 13:14:42'),
	(338, 'kitchen', '::1', 0, '2026-01-10 13:15:34'),
	(339, 'kitchen', '::1', 1, '2026-01-10 13:15:39'),
	(340, 'cashier', '::1', 1, '2026-01-10 13:16:23'),
	(341, 'bj', '::1', 1, '2026-01-10 13:19:50'),
	(342, 'waiter', '::1', 1, '2026-01-10 13:24:29'),
	(343, 'bj', '::1', 1, '2026-01-10 14:03:49'),
	(344, 'bj', '::1', 1, '2026-01-10 15:08:05'),
	(345, 'reception_staff', '::1', 1, '2026-01-10 15:27:11'),
	(346, 'bj', '::1', 1, '2026-01-10 15:27:39'),
	(347, 'reception_staff', '::1', 1, '2026-01-10 15:29:16'),
	(348, 'bj', '::1', 1, '2026-01-10 15:30:20'),
	(349, 'waiter', '::1', 1, '2026-01-10 15:31:27'),
	(350, 'cashier', '::1', 1, '2026-01-10 15:32:06'),
	(351, 'kitchen', '::1', 1, '2026-01-10 15:56:44'),
	(352, 'cashier', '::1', 1, '2026-01-10 16:07:34'),
	(353, 'bj', '::1', 1, '2026-01-10 16:34:55'),
	(354, 'waiter', '::1', 1, '2026-01-10 16:39:47'),
	(355, 'cashier', '::1', 1, '2026-01-10 16:40:57'),
	(356, 'bj', '::1', 1, '2026-01-16 07:57:35'),
	(357, 'reception_staff', '::1', 1, '2026-01-16 07:59:03'),
	(358, 'cashier', '::1', 1, '2026-01-16 07:59:31'),
	(359, 'waiter', '::1', 1, '2026-01-16 08:58:34'),
	(360, 'kitchen', '::1', 1, '2026-01-16 08:59:17'),
	(361, 'cashier', '::1', 1, '2026-01-16 08:59:40'),
	(362, 'bj', '::1', 1, '2026-01-16 11:40:26'),
	(363, 'reception_staff', '::1', 1, '2026-01-16 11:44:38');
INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `success`, `attempted_at`) VALUES
	(364, 'admin', '::1', 0, '2026-01-16 13:35:13'),
	(365, 'admin', '::1', 0, '2026-01-16 13:50:38'),
	(366, 'admin', '::1', 0, '2026-01-16 13:50:42'),
	(367, 'admin', '::1', 0, '2026-01-16 13:52:12'),
	(368, 'admin', '::1', 0, '2026-01-16 14:13:48'),
	(369, 'admin', '::1', 0, '2026-01-16 14:14:01'),
	(370, 'admin', '::1', 0, '2026-01-16 14:15:36'),
	(371, 'admin', '::1', 0, '2026-01-16 14:15:39'),
	(372, 'admin', '::1', 0, '2026-01-16 14:16:12'),
	(373, 'admin', '::1', 0, '2026-01-16 14:17:00'),
	(374, 'admin', '::1', 0, '2026-01-16 14:18:05'),
	(375, 'admin', '::1', 0, '2026-01-16 14:18:09'),
	(376, 'reception_staff', '::1', 1, '2026-01-20 13:13:29'),
	(377, 'waiter', '::1', 1, '2026-01-20 13:13:56'),
	(378, 'kitchen', '::1', 1, '2026-01-20 13:21:52'),
	(379, 'admin', '::1', 0, '2026-01-20 13:24:47'),
	(380, 'admin', '::1', 1, '2026-01-20 13:29:30'),
	(381, 'bj', '::1', 1, '2026-01-20 13:30:05'),
	(382, 'cashier', '192.168.0.106', 1, '2026-01-20 13:31:35'),
	(383, 'reception_staff', '192.168.0.102', 1, '2026-01-20 13:33:25'),
	(384, 'reception_staff', '::1', 1, '2026-01-20 13:36:53'),
	(385, 'admin', '::1', 1, '2026-01-20 13:57:52'),
	(386, 'reception_staff', '::1', 1, '2026-01-20 17:00:45'),
	(387, 'reception_staff', '::1', 1, '2026-01-21 03:51:34'),
	(388, 'bj', '::1', 1, '2026-01-21 04:45:34'),
	(389, 'reception_staff', '::1', 1, '2026-01-21 05:45:03'),
	(390, 'reception_staff', '::1', 1, '2026-01-21 06:16:16'),
	(391, 'bj', '192.168.0.106', 1, '2026-01-21 10:07:50'),
	(392, 'waiter', '::1', 1, '2026-01-21 10:12:03'),
	(393, 'kitchen', '::1', 1, '2026-01-21 10:15:14'),
	(394, 'waiter', '::1', 1, '2026-01-21 10:34:41'),
	(395, 'kitchen', '::1', 1, '2026-01-21 11:15:50');
INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `success`, `attempted_at`) VALUES
	(396, 'cashier', '::1', 1, '2026-01-21 11:26:14'),
	(397, 'bj', '192.168.0.106', 1, '2026-01-21 11:27:51'),
	(398, 'waiter', '::1', 1, '2026-01-21 11:45:53'),
	(399, 'kitchen', '::1', 1, '2026-01-21 11:46:27'),
	(400, 'cashier', '::1', 1, '2026-01-21 11:46:44'),
	(401, 'kitchen', '::1', 1, '2026-01-21 14:48:33'),
	(402, 'cashier', '::1', 1, '2026-01-21 15:04:24'),
	(403, 'waiter', '::1', 1, '2026-01-21 15:04:51'),
	(404, 'admin', '::1', 1, '2026-01-22 01:27:45'),
	(405, 'reception_staff', '::1', 1, '2026-01-22 02:16:38'),
	(406, 'admin', '::1', 1, '2026-01-22 02:20:37'),
	(407, 'tirdy', '::1', 1, '2026-01-22 02:37:12'),
	(408, 'bj', '::1', 0, '2026-01-22 02:38:14'),
	(409, 'admin', '::1', 0, '2026-01-22 02:38:27'),
	(410, 'yo', '::1', 0, '2026-01-22 03:17:10'),
	(411, 'yo', '::1', 1, '2026-01-22 03:17:21'),
	(412, 'yo', '::1', 1, '2026-01-22 03:17:34'),
	(413, 'yo', '::1', 0, '2026-01-22 03:17:56'),
	(414, 'tirdy', '::1', 0, '2026-01-22 03:30:54'),
	(415, 'cashier', '::1', 1, '2026-01-22 03:31:05'),
	(416, 'admin', '::1', 1, '2026-01-22 03:33:00'),
	(417, 'tirdy', '::1', 0, '2026-01-22 03:47:55'),
	(418, 'kitchen', '::1', 1, '2026-01-22 03:48:05'),
	(419, 'admin', '::1', 1, '2026-01-22 03:57:34'),
	(420, 'admin', '::1', 1, '2026-01-22 03:59:07'),
	(421, 'reception_staff', '::1', 1, '2026-01-22 04:01:00'),
	(422, 'admin', '::1', 1, '2026-01-22 04:01:54'),
	(423, 'admin', '::1', 1, '2026-01-22 05:08:35'),
	(424, 'reception_staff', '::1', 1, '2026-01-22 05:11:24'),
	(425, 'kitchen', '192.168.0.117', 1, '2026-01-22 05:12:40'),
	(426, 'waiter', '192.168.0.115', 0, '2026-01-22 05:12:45'),
	(427, 'waiter', '192.168.0.115', 1, '2026-01-22 05:12:51');
INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `success`, `attempted_at`) VALUES
	(428, 'kitchen', '::1', 1, '2026-01-22 05:15:09'),
	(429, 'cashier', '::1', 1, '2026-01-22 05:21:32'),
	(430, 'bj', '::1', 1, '2026-01-22 05:24:00'),
	(431, 'reception_staff', '192.168.0.118', 1, '2026-01-22 05:28:28'),
	(432, 'kitchen', '192.168.0.117', 1, '2026-01-22 06:15:40'),
	(433, 'kitchen', '::1', 1, '2026-01-22 06:17:42'),
	(434, 'cashier', '::1', 1, '2026-01-22 06:24:40'),
	(435, 'cashier', '192.168.0.115', 1, '2026-01-22 06:52:45'),
	(436, 'waiter', '192.168.0.107', 1, '2026-01-22 07:22:35'),
	(437, 'waiter', '192.168.0.117', 1, '2026-01-22 07:25:20'),
	(438, 'cashier', '192.168.0.118', 0, '2026-01-22 07:26:33'),
	(439, 'kitchen', '192.168.0.107', 1, '2026-01-22 07:27:01'),
	(440, 'cashier', '192.168.0.118', 0, '2026-01-22 07:28:28'),
	(441, 'cashier', '192.168.0.118', 1, '2026-01-22 07:28:56'),
	(442, 'joyalrain', '192.168.0.105', 1, '2026-01-22 07:57:02'),
	(443, 'reception_staff', '::1', 1, '2026-01-22 08:01:43'),
	(444, 'admin', '::1', 1, '2026-01-22 08:13:26'),
	(445, 'bj', '::1', 1, '2026-01-22 08:27:32'),
	(446, 'Lyka', '192.168.0.107', 1, '2026-01-22 08:31:27'),
	(447, 'kdd', '::1', 1, '2026-01-22 08:32:10'),
	(448, 'waiter', '192.168.0.103', 1, '2026-01-22 08:37:40'),
	(449, 'waiter', '::1', 1, '2026-01-23 06:35:38'),
	(450, 'waiter', '192.168.0.100', 1, '2026-01-23 06:58:06'),
	(451, 'waiter', '::1', 1, '2026-01-23 07:03:21'),
	(452, 'waiter', '::1', 1, '2026-01-23 07:35:33'),
	(453, 'waiter', '192.168.0.100', 1, '2026-01-23 09:04:24'),
	(454, 'kitchen', '::1', 1, '2026-01-23 11:04:14'),
	(455, 'cashier', '::1', 0, '2026-01-23 12:34:35'),
	(456, 'cashier', '::1', 1, '2026-01-23 12:34:39'),
	(457, 'kitchen', '::1', 1, '2026-01-23 14:00:36');
INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `success`, `attempted_at`) VALUES
	(458, 'cashier', '::1', 1, '2026-01-23 15:49:38'),
	(459, 'cashier', '::1', 1, '2026-01-24 01:54:16'),
	(460, 'waiter', '192.168.0.100', 1, '2026-01-24 01:58:16'),
	(461, 'cashier', '::1', 1, '2026-01-24 01:59:04'),
	(462, 'kitchen', '::1', 1, '2026-01-24 04:14:49'),
	(463, 'bj', '::1', 1, '2026-01-24 04:57:00'),
	(464, 'reception_staff', '::1', 1, '2026-01-24 05:30:57'),
	(465, 'waiter', '::1', 1, '2026-01-24 05:33:45'),
	(466, 'kitchen', '::1', 1, '2026-01-24 05:36:09'),
	(467, 'cashier', '::1', 1, '2026-01-24 05:36:37'),
	(468, 'waiter', '::1', 1, '2026-01-24 05:43:01'),
	(469, 'cashier', '192.168.0.100', 1, '2026-01-24 05:51:51'),
	(470, 'reception_staff', '::1', 1, '2026-01-24 05:57:16'),
	(471, 'rl', '::1', 1, '2026-01-24 06:02:59'),
	(472, 'bj', '::1', 1, '2026-01-24 06:17:16'),
	(473, 'reception_staff', '::1', 1, '2026-01-24 06:27:36'),
	(474, 'cashier', '::1', 1, '2026-01-24 06:46:26'),
	(475, 'cashier', '::1', 1, '2026-01-24 06:55:33'),
	(476, 'cashier', '::1', 1, '2026-01-24 08:47:05'),
	(477, 'reception_staff', '::1', 1, '2026-01-24 08:55:58'),
	(478, 'cashier', '::1', 1, '2026-01-24 10:32:35'),
	(479, 'waiter', '::1', 1, '2026-01-24 10:33:27'),
	(480, 'kitchen', '::1', 1, '2026-01-24 10:34:23'),
	(481, 'cashier', '::1', 1, '2026-01-24 10:35:07'),
	(482, 'admin', '::1', 1, '2026-01-24 12:39:52'),
	(483, 'cashier', '192.168.0.136', 1, '2026-01-26 01:36:54'),
	(484, 'kitchen', '::1', 1, '2026-01-26 01:58:49'),
	(485, 'waiter', '192.168.0.122', 1, '2026-01-26 01:59:30'),
	(486, 'bj', '::1', 1, '2026-01-26 02:15:39'),
	(487, 'waiter', '::1', 1, '2026-01-26 05:21:48'),
	(488, 'kitchen', '::1', 1, '2026-01-26 05:34:17'),
	(489, 'waiter', '192.168.0.104', 1, '2026-01-26 05:39:43');
INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `success`, `attempted_at`) VALUES
	(490, 'kitchen', '::1', 1, '2026-01-26 05:52:23'),
	(491, 'kitchen', '::1', 1, '2026-01-26 06:04:41'),
	(492, 'kitchen', '::1', 1, '2026-01-26 06:06:34'),
	(493, 'bj', '::1', 1, '2026-01-26 07:30:07'),
	(494, 'cashier', '::1', 1, '2026-01-26 11:22:37'),
	(495, 'waiter', '192.168.0.104', 1, '2026-01-26 12:48:46'),
	(496, 'admin', '::1', 1, '2026-01-26 12:50:54'),
	(497, 'bj', '::1', 1, '2026-01-26 12:54:46'),
	(498, 'cashier', '::1', 1, '2026-01-26 13:06:41'),
	(499, 'cashier', '192.168.0.104', 0, '2026-01-26 14:42:01'),
	(500, 'cashier', '192.168.0.104', 1, '2026-01-26 14:42:07'),
	(501, 'cashier', '::1', 1, '2026-01-26 14:43:16'),
	(502, 'waiter', '192.168.0.104', 1, '2026-01-26 15:45:53'),
	(503, 'cashier', '::1', 1, '2026-01-26 16:06:32'),
	(504, 'waiter', '::1', 1, '2026-01-26 16:07:46'),
	(505, 'waiter', '::1', 1, '2026-01-26 16:21:12'),
	(506, 'cashier', '::1', 1, '2026-01-26 16:23:17'),
	(507, 'waiter', '::1', 1, '2026-01-26 16:28:10'),
	(508, 'admin', '::1', 1, '2026-01-27 02:30:56'),
	(509, 'waiter', '10.115.71.61', 1, '2026-01-27 02:37:48'),
	(510, 'cashier', '10.115.71.146', 1, '2026-01-27 02:39:13'),
	(511, 'lika', '::1', 0, '2026-01-27 02:40:47'),
	(512, 'lika', '::1', 1, '2026-01-27 02:41:00'),
	(513, 'kitchen', '::1', 1, '2026-01-27 02:52:43'),
	(514, 'cashier', '10.115.71.146', 1, '2026-01-27 02:56:40'),
	(515, 'admin', '::1', 1, '2026-01-29 02:03:26'),
	(516, 'waiter', '192.168.213.87', 1, '2026-01-29 02:05:28'),
	(517, 'waiter', '192.168.213.3', 1, '2026-01-29 02:05:44'),
	(518, 'kitchen', '::1', 1, '2026-01-29 02:07:41'),
	(519, 'waiter', '192.168.213.3', 1, '2026-01-29 03:43:08'),
	(520, 'kitchen', '::1', 1, '2026-01-29 03:44:36');
INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `success`, `attempted_at`) VALUES
	(521, 'waiter', '10.21.247.3', 1, '2026-02-11 06:47:23'),
	(522, 'waiter', '10.21.247.155', 1, '2026-02-11 06:47:31'),
	(523, 'cashier', '10.21.247.3', 1, '2026-02-11 06:47:52'),
	(524, 'admin', '::1', 1, '2026-02-11 06:48:39'),
	(525, 'waiter', '10.21.247.3', 1, '2026-02-11 06:49:10'),
	(526, 'cashier', '10.21.247.218', 1, '2026-02-11 06:50:34'),
	(527, 'kitchen', '::1', 1, '2026-02-11 07:00:20'),
	(528, 'cashier', '::1', 1, '2026-02-11 07:03:17'),
	(529, 'kitchen', '::1', 1, '2026-02-11 07:05:56'),
	(530, 'admin', '::1', 1, '2026-02-11 07:07:10'),
	(531, 'yeye', '::1', 1, '2026-02-11 07:17:40'),
	(532, 'reception', '192.168.1.179', 0, '2026-02-11 07:21:19'),
	(533, 'reception_staff', '192.168.1.179', 1, '2026-02-11 07:21:47'),
	(534, 'reception_staff', '::1', 1, '2026-02-11 07:28:53'),
	(535, 'waiter', '192.168.1.179', 1, '2026-02-11 07:37:02'),
	(536, 'reception_staff', '::1', 1, '2026-02-11 07:38:53'),
	(537, 'cashier', '::1', 0, '2026-02-11 07:56:29'),
	(538, 'cashier', '::1', 1, '2026-02-11 07:56:32'),
	(539, 'waiter', '::1', 1, '2026-02-11 10:48:48'),
	(540, 'cashier', '::1', 1, '2026-02-22 12:10:06'),
	(541, 'admin', '::1', 1, '2026-02-22 13:29:08'),
	(542, 'reception_staff', '::1', 1, '2026-02-28 09:30:43'),
	(543, 'waiter', '::1', 1, '2026-02-28 09:40:22'),
	(544, 'cashier', '::1', 1, '2026-02-28 09:49:59'),
	(545, 'waiter', '::1', 1, '2026-03-10 01:34:52'),
	(546, 'admin', '::1', 1, '2026-03-10 05:42:56'),
	(547, 'admin', '::1', 1, '2026-03-12 07:46:51'),
	(548, 'reception_staff', '::1', 1, '2026-03-12 07:53:09'),
	(549, 'kitchen', '::1', 1, '2026-03-12 08:10:34'),
	(550, 'cashier', '::1', 1, '2026-03-12 08:27:25'),
	(551, 'reception_staff', '::1', 1, '2026-03-12 11:07:50');
INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `success`, `attempted_at`) VALUES
	(552, 'admin', '::1', 1, '2026-03-12 12:44:01'),
	(553, 'waiter', '::1', 1, '2026-03-12 12:45:14'),
	(554, 'kitchen', '::1', 1, '2026-03-12 12:45:56'),
	(555, 'cashier', '::1', 1, '2026-03-12 12:46:43'),
	(556, 'waiter', '::1', 1, '2026-03-12 12:48:31'),
	(557, 'kitchen', '::1', 1, '2026-03-12 12:48:52'),
	(558, 'AJ', '::1', 0, '2026-03-12 13:27:35'),
	(559, 'Aj', '::1', 0, '2026-03-12 13:27:42'),
	(560, 'bj', '::1', 1, '2026-03-12 13:28:13'),
	(561, 'reception_staff', '::1', 1, '2026-03-12 13:29:25'),
	(562, 'waiter', '::1', 1, '2026-03-12 13:30:30'),
	(563, 'kitchen', '::1', 1, '2026-03-12 13:31:12'),
	(564, 'reception_staff', '::1', 1, '2026-03-13 07:17:59'),
	(565, 'waiter', '192.168.0.117', 1, '2026-03-13 07:19:35'),
	(566, 'reception_staff', '::1', 1, '2026-03-13 07:39:09'),
	(567, 'waiter', '192.168.0.117', 1, '2026-03-13 07:41:49'),
	(568, 'waiter', '::1', 1, '2026-03-13 09:09:34'),
	(569, 'kitchen', '::1', 1, '2026-03-13 09:09:58'),
	(570, 'cashier', '::1', 1, '2026-03-13 09:10:29'),
	(571, 'waiter', '192.168.0.117', 1, '2026-03-13 09:20:40'),
	(572, 'kitchen', '::1', 1, '2026-03-13 09:42:50'),
	(573, 'bj', '::1', 1, '2026-03-13 09:47:49'),
	(574, 'reception_staff', '::1', 1, '2026-03-13 10:11:49'),
	(575, 'kitchen', '::1', 1, '2026-03-14 02:48:08'),
	(576, 'waiter', '::1', 1, '2026-03-14 03:25:02'),
	(577, 'kitchen', '::1', 1, '2026-03-14 04:03:48');

-- Dumping structure for table hotelia_db.menu_categories
CREATE TABLE IF NOT EXISTS `menu_categories` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) NOT NULL,
  `category_slug` varchar(50) NOT NULL,
  `icon_name` varchar(50) DEFAULT 'utensils',
  `display_order` int DEFAULT '0',
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.menu_categories: ~6 rows (approximately)
DELETE FROM `menu_categories`;
INSERT INTO `menu_categories` (`category_id`, `category_name`, `category_slug`, `icon_name`, `display_order`) VALUES
	(1, 'Appetizer', 'appetizer', 'utensils-crossed', 1),
	(2, 'Soup', 'soup', 'soup', 2),
	(3, 'Salad', 'salad', 'leaf', 3),
	(4, 'Main Course', 'main_course', 'beef', 4),
	(5, 'Dessert', 'dessert', 'ice-cream', 5),
	(6, 'Beverages', 'beverages', 'cup-soda', 6);

-- Dumping structure for table hotelia_db.menu_items
CREATE TABLE IF NOT EXISTS `menu_items` (
  `item_id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `item_description` text,
  `price` decimal(10,2) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('available','unavailable') DEFAULT 'available',
  `is_special` tinyint(1) DEFAULT '0',
  `is_available` tinyint(1) DEFAULT '1',
  `inventory_item_id` int DEFAULT NULL,
  `stock_quantity` int DEFAULT '0',
  PRIMARY KEY (`item_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `menu_categories` (`category_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.menu_items: ~31 rows (approximately)
DELETE FROM `menu_items`;
INSERT INTO `menu_items` (`item_id`, `category_id`, `item_name`, `item_description`, `price`, `image_path`, `status`, `is_special`, `is_available`, `inventory_item_id`, `stock_quantity`) VALUES
	(1, 1, 'Lumpiang Shanghai', NULL, 185.00, 'lumpia_shanghai.jpg', 'unavailable', 0, 1, NULL, 0),
	(2, 1, 'Pork Sisig (Sizzling)', NULL, 220.00, 'pork_sisig.jpg', 'unavailable', 0, 1, NULL, 0),
	(3, 1, 'Chicharon Bulaklak', NULL, 195.00, 'chicharon-bulaklak.jpg', 'unavailable', 0, 1, NULL, 0),
	(4, 1, 'Tokwa’t Baboy', NULL, 165.00, 'tokwa_baboy.webp', 'unavailable', 0, 1, NULL, 0),
	(5, 1, 'Calamares', NULL, 240.00, 'calamares.jpg', 'available', 0, 1, NULL, 20),
	(6, 2, 'Sinigang na Baboy', NULL, 350.00, 'sinigang_baboy.webp', 'unavailable', 0, 1, NULL, 0),
	(7, 2, 'Bulalo (Beef Shank Soup)', NULL, 480.00, 'bulalo.jpg', 'unavailable', 0, 1, NULL, 0),
	(8, 2, 'Chicken Binakol', NULL, 320.00, 'chicken_binakol.jpg', 'unavailable', 0, 1, NULL, 0),
	(9, 2, 'Pancit Molo', NULL, 180.00, 'pancit_molo.jpg', 'unavailable', 0, 1, NULL, 0),
	(10, 2, 'Tinola na Manok', NULL, 290.00, 'chicken_tinola.jpg', 'unavailable', 0, 1, NULL, 0),
	(11, 3, 'Ensaladang Talong (Eggplant)', NULL, 120.00, 'ensalada_talong.jpg', 'unavailable', 0, 1, NULL, 0),
	(12, 3, 'Atcharang Papaya', NULL, 85.00, 'atchara.jpg', 'unavailable', 0, 1, NULL, 0),
	(13, 3, 'Ensaladang Mangga at Bagoong', NULL, 110.00, 'ensalada_mangga.jpg', 'unavailable', 0, 1, NULL, 0),
	(14, 3, 'Pako (Fiddlehead Fern) Salad', NULL, 155.00, 'pako_salad.jpg', 'unavailable', 0, 1, NULL, 0),
	(15, 3, 'Ensaladang Kamatis at Sibuyas', NULL, 95.00, 'tomato_salad.webp', 'unavailable', 0, 1, NULL, 0),
	(16, 4, 'Beef Caldereta', NULL, 380.00, 'beef_caldereta.jpeg', 'unavailable', 0, 1, NULL, 0);
INSERT INTO `menu_items` (`item_id`, `category_id`, `item_name`, `item_description`, `price`, `image_path`, `status`, `is_special`, `is_available`, `inventory_item_id`, `stock_quantity`) VALUES
	(17, 4, 'Chicken/Pork Adobo', NULL, 310.00, 'adobo.jpg', 'unavailable', 0, 1, NULL, 0),
	(18, 4, 'Kare-Kare (Oxtail/Beef)', NULL, 450.00, 'kare_kare.jpg', 'unavailable', 0, 1, NULL, 0),
	(19, 4, 'Crispy Pata (Deep Fried Trotter)', NULL, 650.00, 'crispy_pata.jpg', 'unavailable', 0, 1, NULL, 0),
	(20, 4, 'Lechon Kawali', NULL, 340.00, 'lechon_kawali.jpg', 'unavailable', 0, 1, NULL, 0),
	(21, 5, 'Halo-Halo Especial', NULL, 145.00, 'halo_halo.jpg', 'unavailable', 0, 1, NULL, 0),
	(22, 5, 'Leche Flan', NULL, 120.00, 'leche_flan.jpg', 'unavailable', 0, 1, NULL, 0),
	(23, 5, 'Buko Pandan', NULL, 85.00, 'buko_pandan.jpg', 'unavailable', 0, 1, NULL, 0),
	(24, 5, 'Turon with Ice Cream', NULL, 110.00, 'turon.jpg', 'unavailable', 0, 1, NULL, 0),
	(25, 5, 'Bibingka (Rice Cake)', NULL, 130.00, 'bibingka.jpg', 'unavailable', 0, 1, NULL, 0),
	(26, 6, 'Sago’t Gulaman', NULL, 65.00, 'sago_gulaman.jpg', 'unavailable', 0, 1, NULL, 0),
	(27, 6, 'Fresh Buko Juice', NULL, 95.00, 'buko_juice.jpg', 'unavailable', 0, 1, NULL, 0),
	(28, 6, 'Calamansi Juice (Hot/Cold)', NULL, 55.00, 'calamansi.webp', 'unavailable', 0, 1, NULL, 0),
	(29, 6, 'San Miguel Beer (Pilsen)', NULL, 80.00, 'sm_pilsen.webp', 'unavailable', 0, 1, NULL, 0),
	(30, 6, 'Brewed Barako Coffee', NULL, 75.00, 'barako_coffee.webp', 'unavailable', 0, 1, NULL, 0),
	(31, 4, 'Rice', NULL, 75.00, 'rice.jpg', 'unavailable', 0, 1, NULL, 0);

-- Dumping structure for table hotelia_db.menu_item_ingredients
CREATE TABLE IF NOT EXISTS `menu_item_ingredients` (
  `recipe_id` int NOT NULL AUTO_INCREMENT,
  `menu_item_id` int DEFAULT NULL,
  `inventory_item_id` int DEFAULT NULL,
  `quantity_needed` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`recipe_id`),
  KEY `menu_item_id` (`menu_item_id`),
  KEY `inventory_item_id` (`inventory_item_id`),
  CONSTRAINT `menu_item_ingredients_ibfk_1` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`item_id`),
  CONSTRAINT `menu_item_ingredients_ibfk_2` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.menu_item_ingredients: ~6 rows (approximately)
DELETE FROM `menu_item_ingredients`;
INSERT INTO `menu_item_ingredients` (`recipe_id`, `menu_item_id`, `inventory_item_id`, `quantity_needed`) VALUES
	(1, 5, 5, 0.30),
	(2, 5, 10, 0.05),
	(3, 28, 6, 5.00),
	(4, 28, 13, 0.02),
	(5, 6, 1, 0.40),
	(6, 31, 7, 0.15);

-- Dumping structure for table hotelia_db.orders
CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` int NOT NULL AUTO_INCREMENT,
  `booking_id` int DEFAULT NULL,
  `waiter_id` int DEFAULT NULL,
  `cashier_id` int DEFAULT NULL,
  `table_number` varchar(10) DEFAULT NULL,
  `room_number` varchar(10) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('pending','preparing','ready','delivered','cancelled') DEFAULT 'pending',
  `billing_method` enum('room','instant') DEFAULT 'instant',
  `billing_status` enum('paid','unpaid') DEFAULT 'unpaid',
  `order_source` varchar(20) DEFAULT 'waiter',
  `is_billed` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `amount_received` decimal(10,2) DEFAULT '0.00',
  `amount_change` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`order_id`),
  KEY `booking_id` (`booking_id`),
  KEY `fk_cashier` (`cashier_id`),
  CONSTRAINT `fk_cashier` FOREIGN KEY (`cashier_id`) REFERENCES `staff` (`staff_id`),
  CONSTRAINT `fk_order_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_orders_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.orders: ~4 rows (approximately)
DELETE FROM `orders`;
INSERT INTO `orders` (`order_id`, `booking_id`, `waiter_id`, `cashier_id`, `table_number`, `room_number`, `total_amount`, `status`, `billing_method`, `billing_status`, `order_source`, `is_billed`, `created_at`, `updated_at`, `amount_received`, `amount_change`) VALUES
	(1, NULL, 5, 7, '1', NULL, 2475.20, 'delivered', 'instant', 'paid', 'waiter', 1, '2026-03-13 09:16:14', '2026-03-13 09:35:50', 2500.00, 24.80),
	(2, NULL, 5, 5, '3', NULL, 352.80, 'delivered', 'instant', 'unpaid', 'waiter', 0, '2026-03-13 09:21:18', '2026-03-13 09:25:02', 0.00, 0.00),
	(3, NULL, 5, 5, '2', NULL, 1069.60, 'delivered', 'instant', 'unpaid', 'waiter', 0, '2026-03-13 09:21:22', '2026-03-13 09:43:50', 0.00, 0.00),
	(4, NULL, 5, 7, '4', NULL, 61.60, 'delivered', 'instant', 'paid', 'waiter', 1, '2026-03-13 09:27:43', '2026-03-13 09:43:57', 100.00, 38.40),
	(5, 8, 5, 5, '5', 'G101', 123.20, 'pending', 'room', 'unpaid', 'waiter', 0, '2026-03-13 10:23:07', '2026-03-13 10:23:07', 0.00, 0.00);

-- Dumping structure for table hotelia_db.order_items
CREATE TABLE IF NOT EXISTS `order_items` (
  `order_item_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `menu_item_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `price_at_order` decimal(10,2) NOT NULL,
  `notes` text,
  `item_status` enum('pending','preparing','served','picked_up') DEFAULT 'pending',
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.order_items: ~16 rows (approximately)
DELETE FROM `order_items`;
INSERT INTO `order_items` (`order_item_id`, `order_id`, `menu_item_id`, `quantity`, `price_at_order`, `notes`, `item_status`) VALUES
	(1, 1, 3, 1, 195.00, '', 'picked_up'),
	(2, 1, 5, 1, 240.00, '', 'picked_up'),
	(3, 1, 21, 1, 145.00, '', 'picked_up'),
	(4, 1, 6, 1, 350.00, 'no sinigang mix only sampaloc', 'picked_up'),
	(5, 1, 12, 1, 85.00, '', 'picked_up'),
	(6, 1, 28, 1, 55.00, 'Cold', 'picked_up'),
	(7, 1, 20, 1, 340.00, '', 'picked_up'),
	(8, 1, 19, 1, 650.00, '', 'picked_up'),
	(9, 1, 31, 2, 75.00, '', 'picked_up'),
	(10, 2, 3, 1, 195.00, '', 'picked_up'),
	(11, 2, 22, 1, 120.00, '', 'picked_up'),
	(12, 3, 4, 1, 165.00, '', 'picked_up'),
	(13, 3, 11, 1, 120.00, '', 'picked_up'),
	(14, 3, 22, 1, 120.00, '', 'picked_up'),
	(15, 4, 28, 1, 55.00, 'hot\n', 'picked_up'),
	(16, 3, 28, 10, 55.00, '', 'picked_up'),
	(17, 5, 28, 2, 55.00, '', 'pending');

-- Dumping structure for table hotelia_db.payments
CREATE TABLE IF NOT EXISTS `payments` (
  `payment_id` int NOT NULL AUTO_INCREMENT,
  `booking_id` int DEFAULT NULL,
  `guest_id` int DEFAULT NULL,
  `payment_method_id` int NOT NULL,
  `paid_amount` decimal(10,2) NOT NULL,
  `payment_reference` varchar(100) NOT NULL,
  `user_reference` varchar(100) DEFAULT NULL,
  `verification_attempts` int DEFAULT '0',
  `status` enum('pending','pending_verification','completed','failed','refunded','declined','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'pending',
  `payment_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_by_staff` int DEFAULT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `verification_notes` text,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  KEY `booking_id` (`booking_id`),
  KEY `payment_method_id` (`payment_method_id`),
  KEY `guest_id` (`guest_id`),
  KEY `idx_payment_verification` (`status`,`guest_id`),
  KEY `fk_payments_staff` (`processed_by_staff`),
  KEY `idx_payments_created` (`created_at`),
  KEY `idx_payments_updated` (`updated_at`),
  CONSTRAINT `fk_payments_bookings` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_payment_methods` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`method_id`),
  CONSTRAINT `fk_payments_staff` FOREIGN KEY (`processed_by_staff`) REFERENCES `staff` (`staff_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.payments: ~4 rows (approximately)
DELETE FROM `payments`;
INSERT INTO `payments` (`payment_id`, `booking_id`, `guest_id`, `payment_method_id`, `paid_amount`, `payment_reference`, `user_reference`, `verification_attempts`, `status`, `payment_date`, `processed_by_staff`, `receipt_number`, `verification_notes`, `verified_at`, `created_at`, `updated_at`) VALUES
	(4, 4, 20, 6, 13440.00, 'Cash (walk-in)', 'Cash (walk-in)', 0, 'completed', '2026-03-13 07:58:59', 2, NULL, NULL, '2026-03-13 15:58:59', '2026-03-13 07:58:59', '2026-03-13 07:58:59'),
	(5, 5, 21, 6, 5600.00, 'Cash (walk-in)', 'Cash (walk-in)', 0, 'completed', '2026-03-13 08:06:18', 2, NULL, NULL, '2026-03-13 16:06:18', '2026-03-13 08:06:18', '2026-03-13 08:06:18'),
	(6, 6, 22, 6, 0.56, 'Cash (walk-in)', 'Cash (walk-in)', 0, 'completed', '2026-03-13 08:19:41', 2, NULL, NULL, '2026-03-13 16:19:41', '2026-03-13 08:19:41', '2026-03-13 08:19:41'),
	(7, 7, 8, 3, 1345.68, 'GC-260313-92187', 'GC-260313-92187', 0, 'completed', '2026-03-13 10:10:03', 2, 'PMY-130326-4E77F', '', '2026-03-13 18:12:06', '2026-03-13 10:10:03', '2026-03-13 10:12:06'),
	(8, 8, 8, 3, 840.00, 'GC-260313-073C0', 'GC-260313-073C0', 0, 'completed', '2026-03-13 10:20:45', 2, 'PMY-130326-ADB41', '', '2026-03-13 18:21:06', '2026-03-13 10:20:45', '2026-03-13 10:21:06');

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
DELETE FROM `payment_methods`;
INSERT INTO `payment_methods` (`method_id`, `method_code`, `method_name`, `icon`, `description`, `is_active`, `created_at`, `requires_gateway`, `gateway_type`, `instructions`) VALUES
	(1, 'credit_card', 'Credit Card', 'credit-card', 'Pay with Visa, MasterCard, or American Express', 1, '2025-10-16 13:16:50', 1, 'card', NULL),
	(2, 'debit_card', 'Debit Card', 'credit-card', 'Pay with your debit card', 1, '2025-10-16 13:16:50', 1, 'card', NULL),
	(3, 'gcash', 'GCash', 'smartphone', 'Pay using GCash mobile wallet', 1, '2025-10-16 13:16:50', 1, 'ewallet', NULL),
	(4, 'paymaya', 'PayMaya', 'smartphone', 'Pay using PayMaya mobile wallet', 1, '2025-10-16 13:16:50', 1, 'ewallet', NULL),
	(5, 'bank_transfer', 'Bank Transfer', 'landmark', 'Direct bank transfer', 1, '2025-10-16 13:16:50', 0, 'bank', NULL),
	(6, 'cash', 'Cash', 'dollar-sign', 'Pay in cash at the hotel', 1, '2025-10-16 13:16:50', 0, 'cash', NULL);

-- Dumping structure for table hotelia_db.payment_method_details
CREATE TABLE IF NOT EXISTS `payment_method_details` (
  `detail_id` int NOT NULL AUTO_INCREMENT,
  `payment_id` int NOT NULL,
  `method_type` enum('gcash','paymaya','credit_card','debit_card','bank_transfer','cash') NOT NULL,
  `account_reference` varchar(100) NOT NULL COMMENT 'GCash number, card last4, bank account, etc.',
  `authorization_code` varchar(50) DEFAULT NULL COMMENT 'Auth code from gateway',
  `gateway_response` json NOT NULL COMMENT 'Full gateway response for audit',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`detail_id`),
  UNIQUE KEY `uq_payment_detail` (`payment_id`),
  KEY `idx_payment` (`payment_id`),
  CONSTRAINT `fk_payment_method_detail` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.payment_method_details: ~0 rows (approximately)
DELETE FROM `payment_method_details`;
INSERT INTO `payment_method_details` (`detail_id`, `payment_id`, `method_type`, `account_reference`, `authorization_code`, `gateway_response`, `created_at`) VALUES
	(1, 7, 'gcash', '09999999999', NULL, '{}', '2026-03-13 10:10:03'),
	(2, 8, 'gcash', '09999999999', NULL, '{}', '2026-03-13 10:20:45');

-- Dumping structure for view hotelia_db.payment_summary
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `payment_summary` (
	`payment_id` INT NOT NULL,
	`booking_id` INT NULL,
	`guest_id` INT NULL,
	`paid_amount` DECIMAL(10,2) NOT NULL,
	`method_name` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`status` ENUM('pending','pending_verification','completed','failed','refunded','declined','cancelled') NULL COLLATE 'utf8mb4_0900_ai_ci',
	`payment_date` TIMESTAMP NULL,
	`processed_by_staff` INT NULL,
	`user_reference` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`verification_attempts` INT NULL,
	`hotel_reference` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`booking_ref` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`full_name` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`email` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci'
) ENGINE=MyISAM;

-- Dumping structure for table hotelia_db.pos_transactions
CREATE TABLE IF NOT EXISTS `pos_transactions` (
  `transaction_id` int NOT NULL AUTO_INCREMENT,
  `booking_id` int NOT NULL,
  `reference_no` varchar(30) NOT NULL,
  `transaction_amount` decimal(10,2) NOT NULL,
  `payment_method_id` int NOT NULL,
  `staff_id` int DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('completed','void','refunded') DEFAULT 'completed',
  `transaction_type` enum('booking_deposit','booking_full','balance_payment','checkin_full','checkout_balance','incidental','refund') DEFAULT 'booking_deposit',
  `auth_code` varchar(50) DEFAULT NULL COMMENT 'Authorization code from payment gateway/processor',
  `receipt_number` varchar(50) DEFAULT NULL COMMENT 'Official receipt number for cash payments',
  `payment_reference` varchar(100) DEFAULT NULL COMMENT 'GCash/PayMaya reference, bank reference, or cheque number',
  PRIMARY KEY (`transaction_id`),
  KEY `idx_booking` (`booking_id`),
  KEY `idx_method` (`payment_method_id`),
  KEY `idx_ref` (`reference_no`),
  KEY `idx_date` (`processed_at`),
  KEY `idx_staff` (`staff_id`),
  CONSTRAINT `fk_pos_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pos_payment_method` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`method_id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pos_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.pos_transactions: ~5 rows (approximately)
DELETE FROM `pos_transactions`;
INSERT INTO `pos_transactions` (`transaction_id`, `booking_id`, `reference_no`, `transaction_amount`, `payment_method_id`, `staff_id`, `processed_at`, `status`, `transaction_type`, `auth_code`, `receipt_number`, `payment_reference`) VALUES
	(4, 4, 'POS-20260313-M46B', 13440.00, 6, 2, '2026-03-13 07:58:59', 'completed', 'booking_full', NULL, NULL, 'Cash (walk-in)'),
	(5, 5, 'POS-20260313-7YTG', 5600.00, 6, 2, '2026-03-13 08:06:18', 'completed', 'booking_full', NULL, NULL, 'Cash (walk-in)'),
	(6, 5, 'POS260313080859-6373', 5600.00, 6, NULL, '2026-03-13 08:08:59', 'completed', 'checkout_balance', NULL, NULL, NULL),
	(7, 6, 'POS-20260313-6MNV', 0.56, 6, 2, '2026-03-13 08:19:41', 'completed', 'booking_full', NULL, NULL, 'Cash (walk-in)'),
	(8, 6, 'POS260313085625-8950', 0.56, 6, NULL, '2026-03-13 08:56:25', 'completed', 'checkout_balance', NULL, NULL, NULL),
	(9, 4, 'POS260313085648-3628', 13440.00, 6, NULL, '2026-03-13 08:56:48', 'completed', 'checkout_balance', NULL, NULL, NULL),
	(10, 8, 'POS260313102641-4978', 963.20, 6, NULL, '2026-03-13 10:26:41', 'completed', 'checkout_balance', NULL, NULL, NULL);

-- Dumping structure for table hotelia_db.refund_requests
CREATE TABLE IF NOT EXISTS `refund_requests` (
  `refund_id` int NOT NULL AUTO_INCREMENT,
  `payment_id` int NOT NULL,
  `booking_id` int NOT NULL,
  `requested_amount` decimal(10,2) NOT NULL,
  `reason` enum('cancellation','overpayment','service_issue','other') NOT NULL,
  `status` enum('pending','approved','rejected','processed') DEFAULT 'pending',
  `processed_by_staff` int DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`refund_id`),
  KEY `idx_payment` (`payment_id`),
  KEY `idx_booking` (`booking_id`),
  CONSTRAINT `fk_refund_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_refund_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.refund_requests: ~0 rows (approximately)
DELETE FROM `refund_requests`;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.request_updates: ~0 rows (approximately)
DELETE FROM `request_updates`;

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
  `status` enum('available','occupied','maintenance','reserved') DEFAULT 'available',
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `maintenance_status` enum('good','cleaning','under_repair') DEFAULT 'good',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`room_id`),
  UNIQUE KEY `room_number` (`room_number`),
  KEY `room_type_id` (`room_type_id`),
  KEY `bed_type_id` (`bed_type_id`),
  KEY `view_type_id` (`view_type_id`),
  KEY `idx_room_capacity` (`room_type_id`,`capacity`,`max_extra`),
  CONSTRAINT `fk_rooms_bed_types` FOREIGN KEY (`bed_type_id`) REFERENCES `bed_types` (`bed_type_id`),
  CONSTRAINT `fk_rooms_room_types` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`room_type_id`),
  CONSTRAINT `fk_rooms_view_types` FOREIGN KEY (`view_type_id`) REFERENCES `view_types` (`view_type_id`),
  CONSTRAINT `chk_valid_capacity` CHECK (((`capacity` >= 1) and (`max_extra` >= 0)))
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.rooms: ~22 rows (approximately)
DELETE FROM `rooms`;
INSERT INTO `rooms` (`room_id`, `room_number`, `floor`, `room_type_id`, `bed_type_id`, `view_type_id`, `capacity`, `max_extra`, `status`, `description`, `created_at`, `maintenance_status`, `updated_at`) VALUES
	(7, 'G101', 1, 4, 8, 7, 2, 1, 'reserved', 'Cozy standard room with garden view', '2025-10-06 05:32:56', 'good', '2026-03-13 10:21:53'),
	(8, 'G102', 1, 4, 8, 11, 2, 1, 'reserved', 'Standard room with courtyard view', '2025-10-06 05:32:56', 'good', '2026-01-22 08:46:52'),
	(9, 'G103', 1, 4, 8, 6, 2, 1, 'available', 'Comfortable standard room with pool view', '2025-10-06 05:32:56', 'good', '2026-01-22 08:24:03'),
	(10, 'G104', 1, 14, 10, 11, 6, 0, 'reserved', 'Budget-friendly dormitory with 6 bunk beds', '2025-10-06 05:32:56', 'good', '2026-02-11 07:23:35'),
	(11, 'G105', 1, 14, 10, 11, 6, 0, 'reserved', 'Shared dormitory room, perfect for backpackers', '2025-10-06 05:32:56', 'good', '2026-03-12 13:29:44'),
	(12, '201', 2, 5, 8, 5, 2, 1, 'available', 'Spacious deluxe room with stunning sea view', '2025-10-06 05:32:56', 'good', '2026-03-10 02:37:43'),
	(13, '202', 2, 5, 8, 6, 2, 1, 'maintenance', 'Deluxe room overlooking the pool', '2025-10-06 05:32:56', 'good', '2026-02-11 07:15:32'),
	(14, '203', 2, 5, 8, 10, 2, 1, 'available', 'Deluxe room with beautiful sunset view', '2025-10-06 05:32:56', 'good', '2026-01-22 08:24:09'),
	(15, '204', 2, 6, 9, 5, 2, 1, 'available', 'Executive room with premium sea view', '2025-10-06 05:32:56', 'good', '2026-01-04 08:22:46'),
	(16, '205', 2, 6, 9, 7, 2, 1, 'available', 'Executive room with garden view', '2025-10-06 05:32:56', 'good', '2025-12-09 07:27:55'),
	(17, '206', 2, 7, 7, 7, 4, 2, 'available', 'Large family room with garden view, sleeps up to 6', '2025-10-06 05:32:56', 'good', '2025-12-10 08:47:15');
INSERT INTO `rooms` (`room_id`, `room_number`, `floor`, `room_type_id`, `bed_type_id`, `view_type_id`, `capacity`, `max_extra`, `status`, `description`, `created_at`, `maintenance_status`, `updated_at`) VALUES
	(18, '207', 2, 7, 7, 6, 4, 2, 'available', 'Spacious family room with pool view', '2025-10-06 05:32:56', 'good', '2025-12-10 08:47:15'),
	(19, '301', 3, 8, 9, 5, 2, 1, 'available', 'Elegant suite with panoramic sea view', '2025-10-06 05:32:56', 'good', '2026-01-22 08:24:13'),
	(20, '302', 3, 8, 9, 10, 2, 1, 'available', 'Luxurious suite with stunning sunset view', '2025-10-06 05:32:56', 'good', '2025-12-09 07:27:55'),
	(21, '303', 3, 9, 8, 5, 2, 1, 'available', 'Modern junior suite with sea view', '2025-10-06 05:32:56', 'good', '2025-12-10 08:47:15'),
	(22, '304', 3, 9, 8, 7, 2, 1, 'available', 'Junior suite with peaceful garden view', '2025-10-06 05:32:56', 'good', '2025-12-10 08:47:15'),
	(23, '305', 3, 10, 9, 5, 2, 1, 'available', 'Premium executive suite with sea view and work area', '2025-10-06 05:32:56', 'good', '2025-12-09 07:27:55'),
	(24, '306', 3, 10, 9, 10, 2, 1, 'available', 'Executive suite with magnificent sunset view', '2025-10-06 05:32:56', 'good', '2026-01-07 14:32:06'),
	(25, '307', 3, 11, 9, 5, 4, 2, 'available', 'Ultimate luxury presidential suite with panoramic sea view and private terrace', '2025-10-06 05:32:56', 'good', '2025-12-09 07:27:55'),
	(26, '308', 3, 12, 9, 10, 3, 2, 'available', 'Prestigious governor suite with sunset terrace and ocean breeze', '2025-10-06 05:32:56', 'good', '2025-12-09 07:27:55'),
	(27, '309', 3, 13, 9, 5, 3, 2, 'available', 'Exclusive mayor suite with sweeping sea and garden views', '2025-10-06 05:32:56', 'good', '2025-12-09 07:27:55'),
	(31, 'G104-S', 1, 4, 8, 11, 3, 0, 'available', NULL, '2026-02-22 14:42:14', 'good', '2026-02-22 14:42:14');
INSERT INTO `rooms` (`room_id`, `room_number`, `floor`, `room_type_id`, `bed_type_id`, `view_type_id`, `capacity`, `max_extra`, `status`, `description`, `created_at`, `maintenance_status`, `updated_at`) VALUES
	(35, 'ban1', 3, 21, 10, 8, 2, 0, 'available', NULL, '2026-03-12 10:10:55', 'good', '2026-03-12 10:10:55');

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
DELETE FROM `room_amenities`;

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
	`availability_status` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci'
) ENGINE=MyISAM;

-- Dumping structure for view hotelia_db.room_availability_with_conflicts
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `room_availability_with_conflicts` (
	`room_id` INT NULL,
	`room_number` VARCHAR(1) NULL COLLATE 'utf8mb4_0900_ai_ci',
	`room_status` ENUM('available','occupied','maintenance','reserved') NULL COLLATE 'utf8mb4_0900_ai_ci',
	`is_booked_today` INT NOT NULL,
	`current_bookings` TEXT NULL COLLATE 'utf8mb4_0900_ai_ci'
) ENGINE=MyISAM;

-- Dumping structure for table hotelia_db.room_rate_calendar
CREATE TABLE IF NOT EXISTS `room_rate_calendar` (
  `date` date NOT NULL,
  `room_type_id` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `reason` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`date`,`room_type_id`),
  KEY `room_type_id` (`room_type_id`),
  CONSTRAINT `room_rate_calendar_ibfk_1` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`room_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.room_rate_calendar: ~432 rows (approximately)
DELETE FROM `room_rate_calendar`;
INSERT INTO `room_rate_calendar` (`date`, `room_type_id`, `price`, `reason`, `updated_at`) VALUES
	('2025-02-14', 8, 2000.00, 'valentines', '2025-12-10 14:30:03'),
	('2025-06-01', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-01', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-02', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-02', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-03', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-03', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-04', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-04', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-05', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-05', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-06', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-06', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-07', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-07', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-08', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-08', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-09', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-09', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-10', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-10', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-11', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-11', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-12', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-12', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-13', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-13', 5, 2800.00, 'summer', '2025-12-10 14:30:03');
INSERT INTO `room_rate_calendar` (`date`, `room_type_id`, `price`, `reason`, `updated_at`) VALUES
	('2025-06-14', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-14', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-15', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-15', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-16', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-16', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-17', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-17', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-18', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-18', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-19', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-19', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-20', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-20', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-21', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-21', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-22', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-22', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-23', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-23', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-24', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-24', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-25', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-25', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-26', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-26', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-27', 4, 1500.00, 'summer', '2025-12-10 14:30:03');
INSERT INTO `room_rate_calendar` (`date`, `room_type_id`, `price`, `reason`, `updated_at`) VALUES
	('2025-06-27', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-28', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-28', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-29', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-29', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-30', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-06-30', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-01', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-01', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-02', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-02', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-03', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-03', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-04', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-04', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-05', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-05', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-06', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-06', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-07', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-07', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-08', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-08', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-09', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-09', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-10', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-10', 5, 2800.00, 'summer', '2025-12-10 14:30:03');
INSERT INTO `room_rate_calendar` (`date`, `room_type_id`, `price`, `reason`, `updated_at`) VALUES
	('2025-07-11', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-11', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-12', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-12', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-13', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-13', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-14', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-14', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-15', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-15', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-16', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-16', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-17', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-17', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-18', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-18', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-19', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-19', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-20', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-20', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-21', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-21', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-22', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-22', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-23', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-23', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-24', 4, 1500.00, 'summer', '2025-12-10 14:30:03');
INSERT INTO `room_rate_calendar` (`date`, `room_type_id`, `price`, `reason`, `updated_at`) VALUES
	('2025-07-24', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-25', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-25', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-26', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-26', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-27', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-27', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-28', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-28', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-29', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-29', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-30', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-30', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-31', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-07-31', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-01', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-01', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-02', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-02', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-03', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-03', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-04', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-04', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-05', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-05', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-06', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-06', 5, 2800.00, 'summer', '2025-12-10 14:30:03');
INSERT INTO `room_rate_calendar` (`date`, `room_type_id`, `price`, `reason`, `updated_at`) VALUES
	('2025-08-07', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-07', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-08', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-08', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-09', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-09', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-10', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-10', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-11', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-11', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-12', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-12', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-13', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-13', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-14', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-14', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-15', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-15', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-16', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-16', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-17', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-17', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-18', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-18', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-19', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-19', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-20', 4, 1500.00, 'summer', '2025-12-10 14:30:03');
INSERT INTO `room_rate_calendar` (`date`, `room_type_id`, `price`, `reason`, `updated_at`) VALUES
	('2025-08-20', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-21', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-21', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-22', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-22', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-23', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-23', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-24', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-24', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-25', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-25', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-26', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-26', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-27', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-27', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-28', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-28', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-29', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-29', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-30', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2025-08-30', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2025-12-24', 4, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-24', 5, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-24', 6, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-24', 7, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-24', 8, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-24', 9, 1800.00, 'christmas', '2025-12-10 14:30:03');
INSERT INTO `room_rate_calendar` (`date`, `room_type_id`, `price`, `reason`, `updated_at`) VALUES
	('2025-12-24', 10, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-24', 11, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-24', 12, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-24', 13, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-24', 14, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-25', 4, 1800.00, 'christmas', '2026-01-20 12:55:12'),
	('2025-12-25', 5, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-25', 6, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-25', 7, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-25', 8, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-25', 9, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-25', 10, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-25', 11, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-25', 12, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-25', 13, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-25', 14, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-26', 4, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-26', 5, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-26', 6, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-26', 7, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-26', 8, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-26', 9, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-26', 10, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-26', 11, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-26', 12, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2025-12-26', 13, 1800.00, 'christmas', '2025-12-10 14:30:03');
INSERT INTO `room_rate_calendar` (`date`, `room_type_id`, `price`, `reason`, `updated_at`) VALUES
	('2025-12-26', 14, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-02-14', 8, 2000.00, 'valentines', '2026-01-20 12:55:12'),
	('2026-06-01', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-01', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-02', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-02', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-03', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-03', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-04', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-04', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-05', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-05', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-06', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-06', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-07', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-07', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-08', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-08', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-09', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-09', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-10', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-10', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-11', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-11', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-12', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-12', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-13', 4, 1500.00, 'summer', '2025-12-10 14:30:03');
INSERT INTO `room_rate_calendar` (`date`, `room_type_id`, `price`, `reason`, `updated_at`) VALUES
	('2026-06-13', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-14', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-14', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-15', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-15', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-16', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-16', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-17', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-17', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-18', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-18', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-19', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-19', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-20', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-20', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-21', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-21', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-22', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-22', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-23', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-23', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-24', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-24', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-25', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-25', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-26', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-26', 5, 2800.00, 'summer', '2025-12-10 14:30:03');
INSERT INTO `room_rate_calendar` (`date`, `room_type_id`, `price`, `reason`, `updated_at`) VALUES
	('2026-06-27', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-27', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-28', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-28', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-29', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-29', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-30', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-06-30', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-01', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-01', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-02', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-02', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-03', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-03', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-04', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-04', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-05', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-05', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-06', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-06', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-07', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-07', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-08', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-08', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-09', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-09', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-10', 4, 1500.00, 'summer', '2025-12-10 14:30:03');
INSERT INTO `room_rate_calendar` (`date`, `room_type_id`, `price`, `reason`, `updated_at`) VALUES
	('2026-07-10', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-11', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-11', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-12', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-12', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-13', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-13', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-14', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-14', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-15', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-15', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-16', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-16', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-17', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-17', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-18', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-18', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-19', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-19', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-20', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-20', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-21', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-21', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-22', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-22', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-23', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-23', 5, 2800.00, 'summer', '2025-12-10 14:30:03');
INSERT INTO `room_rate_calendar` (`date`, `room_type_id`, `price`, `reason`, `updated_at`) VALUES
	('2026-07-24', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-24', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-25', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-25', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-26', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-26', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-27', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-27', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-28', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-28', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-29', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-29', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-30', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-30', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-31', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-07-31', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-01', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-01', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-02', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-02', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-03', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-03', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-04', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-04', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-05', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-05', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-06', 4, 1500.00, 'summer', '2025-12-10 14:30:03');
INSERT INTO `room_rate_calendar` (`date`, `room_type_id`, `price`, `reason`, `updated_at`) VALUES
	('2026-08-06', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-07', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-07', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-08', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-08', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-09', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-09', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-10', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-10', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-11', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-11', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-12', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-12', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-13', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-13', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-14', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-14', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-15', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-15', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-16', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-16', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-17', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-17', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-18', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-18', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-19', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-19', 5, 2800.00, 'summer', '2025-12-10 14:30:03');
INSERT INTO `room_rate_calendar` (`date`, `room_type_id`, `price`, `reason`, `updated_at`) VALUES
	('2026-08-20', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-20', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-21', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-21', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-22', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-22', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-23', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-23', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-24', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-24', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-25', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-25', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-26', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-26', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-27', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-27', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-28', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-28', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-29', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-29', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-30', 4, 1500.00, 'summer', '2025-12-10 14:30:03'),
	('2026-08-30', 5, 2800.00, 'summer', '2025-12-10 14:30:03'),
	('2026-12-24', 4, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-24', 5, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-24', 6, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-24', 7, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-24', 8, 1800.00, 'christmas', '2025-12-10 14:30:03');
INSERT INTO `room_rate_calendar` (`date`, `room_type_id`, `price`, `reason`, `updated_at`) VALUES
	('2026-12-24', 9, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-24', 10, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-24', 11, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-24', 12, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-24', 13, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-24', 14, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-25', 4, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-25', 5, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-25', 6, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-25', 7, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-25', 8, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-25', 9, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-25', 10, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-25', 11, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-25', 12, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-25', 13, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-25', 14, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-26', 4, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-26', 5, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-26', 6, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-26', 7, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-26', 8, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-26', 9, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-26', 10, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-26', 11, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-26', 12, 1800.00, 'christmas', '2025-12-10 14:30:03');
INSERT INTO `room_rate_calendar` (`date`, `room_type_id`, `price`, `reason`, `updated_at`) VALUES
	('2026-12-26', 13, 1800.00, 'christmas', '2025-12-10 14:30:03'),
	('2026-12-26', 14, 1800.00, 'christmas', '2025-12-10 14:30:03');

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
  `check_in_date` datetime NOT NULL,
  `check_out_date` datetime NOT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Stores guest room selections before booking confirmation';

-- Dumping data for table hotelia_db.room_selections: ~0 rows (approximately)
DELETE FROM `room_selections`;

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.room_selection_instances: ~0 rows (approximately)
DELETE FROM `room_selection_instances`;

-- Dumping structure for table hotelia_db.room_types
CREATE TABLE IF NOT EXISTS `room_types` (
  `room_type_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `official_bed_type_id` int NOT NULL,
  `base_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `extra_person_rate` decimal(10,2) NOT NULL DEFAULT '0.00',
  `description` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`room_type_id`),
  UNIQUE KEY `name` (`name`),
  KEY `fk_room_types_official_bed` (`official_bed_type_id`),
  CONSTRAINT `fk_room_types_official_bed` FOREIGN KEY (`official_bed_type_id`) REFERENCES `bed_types` (`bed_type_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.room_types: ~12 rows (approximately)
DELETE FROM `room_types`;
INSERT INTO `room_types` (`room_type_id`, `name`, `official_bed_type_id`, `base_price`, `extra_person_rate`, `description`, `updated_at`) VALUES
	(4, 'Standard Room', 8, 1500.00, 500.00, 'Comfortable and affordable rooms with essential amenities', '2025-12-12 12:22:37'),
	(5, 'Deluxe Room', 8, 2500.00, 0.00, 'Spacious rooms with premium amenities and beautiful views', '2025-12-10 08:46:59'),
	(6, 'Executive Room', 9, 3200.00, 0.00, 'Professional accommodations with work-friendly features', '2025-12-10 08:46:59'),
	(7, 'Family Room', 7, 3500.00, 0.00, 'Large rooms designed for families with extra space and comfort', '2025-12-10 08:46:59'),
	(8, 'Suite', 9, 4500.00, 0.00, 'Elegant suites with separate living areas and luxury amenities', '2025-12-10 15:48:43'),
	(9, 'Junior Suite', 8, 4000.00, 0.00, 'Compact suites offering luxury in a cozy space', '2025-12-10 08:46:59'),
	(10, 'Executive Suite', 9, 5500.00, 0.00, 'Premium suites with executive services and panoramic views', '2025-12-10 08:46:59'),
	(11, 'Presidential Suite', 9, 12000.00, 0.00, 'The ultimate in luxury with exclusive amenities and services', '2025-12-10 08:46:59'),
	(12, 'Governor Suite', 9, 10000.00, 0.00, 'Prestigious accommodations fit for dignitaries', '2025-12-10 08:46:59'),
	(13, 'Mayor Suite', 9, 9500.00, 0.00, 'Exclusive suites with VIP treatment and amenities', '2025-12-10 08:46:59'),
	(14, 'Dormitory Room', 10, 800.00, 0.00, 'Budget-friendly shared accommodations perfect for backpackers', '2025-12-10 08:46:59'),
	(21, 'room ni banjong', 10, 1.00, 0.00, 'aa', '2026-03-12 09:59:37');

-- Dumping structure for view hotelia_db.room_types_with_capacity
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `room_types_with_capacity` (
	`room_type_id` INT NOT NULL,
	`name` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`base_price` DECIMAL(10,2) NOT NULL,
	`description` TEXT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`calculated_max_capacity` INT NOT NULL
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
DELETE FROM `room_type_amenities`;
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
	(21, 21),
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
	`total_rooms` BIGINT NULL,
	`physically_available` BIGINT NULL,
	`prebooked_count` BIGINT NOT NULL,
	`occupied_rooms` BIGINT NULL,
	`maintenance_rooms` BIGINT NULL,
	`available_for_booking` BIGINT NULL,
	`availability_status` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_0900_ai_ci'
) ENGINE=MyISAM;

-- Dumping structure for table hotelia_db.room_type_images
CREATE TABLE IF NOT EXISTS `room_type_images` (
  `image_id` int NOT NULL AUTO_INCREMENT,
  `room_type_id` int NOT NULL,
  `image_filename` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  `display_order` tinyint DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`image_id`),
  KEY `room_type_id` (`room_type_id`),
  CONSTRAINT `fk_room_type_images` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`room_type_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=142 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.room_type_images: ~110 rows (approximately)
DELETE FROM `room_type_images`;
INSERT INTO `room_type_images` (`image_id`, `room_type_id`, `image_filename`, `is_primary`, `display_order`, `created_at`, `updated_at`) VALUES
	(31, 4, 'standard_room_1.jpg', 1, 1, '2025-10-06 05:32:56', '2025-12-10 18:17:27'),
	(32, 4, 'standard_room_2.jpg', 0, 2, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(33, 4, 'standard_room_3.jpg', 0, 3, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(34, 4, 'standard_room_4.jpg', 0, 4, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(35, 4, 'standard_room_5.jpg', 0, 5, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(36, 4, 'standard_room_6.jpg', 0, 6, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(37, 4, 'standard_room_7.jpg', 0, 7, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(38, 4, 'standard_room_8.jpg', 0, 8, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(39, 4, 'standard_room_9.jpg', 0, 9, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(40, 4, 'standard_room_10.jpg', 0, 10, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(41, 5, 'deluxe_room_1.jpg', 1, 1, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(42, 5, 'deluxe_room_2.jpg', 0, 2, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(43, 5, 'deluxe_room_3.jpg', 0, 3, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(44, 5, 'deluxe_room_4.jpg', 0, 4, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(45, 5, 'deluxe_room_5.jpg', 0, 5, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(46, 5, 'deluxe_room_6.jpg', 0, 6, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(47, 5, 'deluxe_room_7.jpg', 0, 7, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(48, 5, 'deluxe_room_8.jpg', 0, 8, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(49, 5, 'deluxe_room_9.jpg', 0, 9, '2025-10-06 05:32:56', '2025-12-09 07:27:55');
INSERT INTO `room_type_images` (`image_id`, `room_type_id`, `image_filename`, `is_primary`, `display_order`, `created_at`, `updated_at`) VALUES
	(50, 5, 'deluxe_room_10.jpg', 0, 10, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(51, 6, 'executive_room_1.jpg', 1, 1, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(52, 6, 'executive_room_2.jpg', 0, 2, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(53, 6, 'executive_room_3.jpg', 0, 3, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(54, 6, 'executive_room_4.jpg', 0, 4, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(55, 6, 'executive_room_5.jpg', 0, 5, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(56, 6, 'executive_room_6.jpg', 0, 6, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(57, 6, 'executive_room_7.jpg', 0, 7, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(58, 6, 'executive_room_8.jpg', 0, 8, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(59, 6, 'executive_room_9.jpg', 0, 9, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(60, 6, 'executive_room_10.jpg', 0, 10, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(61, 7, 'family_room_1.jpg', 1, 1, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(62, 7, 'family_room_2.jpg', 0, 2, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(63, 7, 'family_room_3.jpg', 0, 3, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(64, 7, 'family_room_4.jpg', 0, 4, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(65, 7, 'family_room_5.jpg', 0, 5, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(66, 7, 'family_room_6.jpg', 0, 6, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(67, 7, 'family_room_7.jpg', 0, 7, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(68, 7, 'family_room_8.jpg', 0, 8, '2025-10-06 05:32:56', '2025-12-09 07:27:55');
INSERT INTO `room_type_images` (`image_id`, `room_type_id`, `image_filename`, `is_primary`, `display_order`, `created_at`, `updated_at`) VALUES
	(69, 7, 'family_room_9.jpg', 0, 9, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(70, 7, 'family_room_10.jpg', 0, 10, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(71, 8, 'suite_1.jpg', 1, 1, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(72, 8, 'suite_2.jpg', 0, 2, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(73, 8, 'suite_3.jpg', 0, 3, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(74, 8, 'suite_4.jpg', 0, 4, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(75, 8, 'suite_5.jpg', 0, 5, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(76, 8, 'suite_6.jpg', 0, 6, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(77, 8, 'suite_7.jpg', 0, 7, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(78, 8, 'suite_8.jpg', 0, 8, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(79, 8, 'suite_9.jpg', 0, 9, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(80, 8, 'suite_10.jpg', 0, 10, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(81, 9, 'junior_suite_1.jpg', 1, 1, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(82, 9, 'junior_suite_2.jpg', 0, 2, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(83, 9, 'junior_suite_3.jpg', 0, 3, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(84, 9, 'junior_suite_4.jpg', 0, 4, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(85, 9, 'junior_suite_5.jpg', 0, 5, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(86, 9, 'junior_suite_6.jpg', 0, 6, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(87, 9, 'junior_suite_7.jpg', 0, 7, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(88, 9, 'junior_suite_8.jpg', 0, 8, '2025-10-06 05:32:56', '2025-12-09 07:27:55');
INSERT INTO `room_type_images` (`image_id`, `room_type_id`, `image_filename`, `is_primary`, `display_order`, `created_at`, `updated_at`) VALUES
	(89, 9, 'junior_suite_9.jpg', 0, 9, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(90, 9, 'junior_suite_10.jpg', 0, 10, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(91, 10, 'executive_suite_1.jpg', 1, 1, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(92, 10, 'executive_suite_2.jpg', 0, 2, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(93, 10, 'executive_suite_3.jpg', 0, 3, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(94, 10, 'executive_suite_4.jpg', 0, 4, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(95, 10, 'executive_suite_5.jpg', 0, 5, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(96, 10, 'executive_suite_6.jpg', 0, 6, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(97, 10, 'executive_suite_7.jpg', 0, 7, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(98, 10, 'executive_suite_8.jpg', 0, 8, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(99, 10, 'executive_suite_9.jpg', 0, 9, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(100, 10, 'executive_suite_10.jpg', 0, 10, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(101, 11, 'presidential_suite_1.jpg', 1, 1, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(102, 11, 'presidential_suite_2.jpg', 0, 2, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(103, 11, 'presidential_suite_3.jpg', 0, 3, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(104, 11, 'presidential_suite_4.jpg', 0, 4, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(105, 11, 'presidential_suite_5.jpg', 0, 5, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(106, 11, 'presidential_suite_6.jpg', 0, 6, '2025-10-06 05:32:56', '2025-12-09 07:27:55');
INSERT INTO `room_type_images` (`image_id`, `room_type_id`, `image_filename`, `is_primary`, `display_order`, `created_at`, `updated_at`) VALUES
	(107, 11, 'presidential_suite_7.jpg', 0, 7, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(108, 11, 'presidential_suite_8.jpg', 0, 8, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(109, 11, 'presidential_suite_9.jpg', 0, 9, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(110, 11, 'presidential_suite_10.jpg', 0, 10, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(111, 12, 'governor_suite_1.jpg', 1, 1, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(112, 12, 'governor_suite_2.jpg', 0, 2, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(113, 12, 'governor_suite_3.jpg', 0, 3, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(114, 12, 'governor_suite_4.jpg', 0, 4, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(115, 12, 'governor_suite_5.jpg', 0, 5, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(116, 12, 'governor_suite_6.jpg', 0, 6, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(117, 12, 'governor_suite_7.jpg', 0, 7, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(118, 12, 'governor_suite_8.jpg', 0, 8, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(119, 12, 'governor_suite_9.jpg', 0, 9, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(120, 12, 'governor_suite_10.jpg', 0, 10, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(121, 13, 'mayor_suite_1.jpg', 1, 1, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(122, 13, 'mayor_suite_2.jpg', 0, 2, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(123, 13, 'mayor_suite_3.jpg', 0, 3, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(124, 13, 'mayor_suite_4.jpg', 0, 4, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(125, 13, 'mayor_suite_5.jpg', 0, 5, '2025-10-06 05:32:56', '2025-12-09 07:27:55');
INSERT INTO `room_type_images` (`image_id`, `room_type_id`, `image_filename`, `is_primary`, `display_order`, `created_at`, `updated_at`) VALUES
	(126, 13, 'mayor_suite_6.jpg', 0, 6, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(127, 13, 'mayor_suite_7.jpg', 0, 7, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(128, 13, 'mayor_suite_8.jpg', 0, 8, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(129, 13, 'mayor_suite_9.jpg', 0, 9, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(130, 13, 'mayor_suite_10.jpg', 0, 10, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(131, 14, 'dormitory_room_1.jpg', 1, 1, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(132, 14, 'dormitory_room_2.jpg', 0, 2, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(133, 14, 'dormitory_room_3.jpg', 0, 3, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(134, 14, 'dormitory_room_4.jpg', 0, 4, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(135, 14, 'dormitory_room_5.jpg', 0, 5, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(136, 14, 'dormitory_room_6.jpg', 0, 6, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(137, 14, 'dormitory_room_7.jpg', 0, 7, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(138, 14, 'dormitory_room_8.jpg', 0, 8, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(139, 14, 'dormitory_room_9.jpg', 0, 9, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(140, 14, 'dormitory_room_10.jpg', 0, 10, '2025-10-06 05:32:56', '2025-12-09 07:27:55'),
	(141, 21, 'default.jpg', 0, 0, '2026-03-12 09:59:37', '2026-03-12 10:13:08');

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
DELETE FROM `service_categories`;
INSERT INTO `service_categories` (`category_id`, `name`, `icon`, `description`, `is_active`, `created_at`) VALUES
	(1, 'Room Amenities', 'shirt', 'Extra towels, pillows, toiletries and room items', 1, '2025-10-30 12:47:22'),
	(2, 'Housekeeping', 'sparkles', 'Cleaning services and room maintenance', 1, '2025-10-30 12:47:22'),
	(3, 'Maintenance', 'wrench', 'Repair requests and technical issues', 1, '2025-10-30 12:47:22'),
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
DELETE FROM `service_forms`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.service_requests: ~0 rows (approximately)
DELETE FROM `service_requests`;

-- Dumping structure for table hotelia_db.staff
CREATE TABLE IF NOT EXISTS `staff` (
  `staff_id` int NOT NULL AUTO_INCREMENT,
  `account_id` int NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','reception','fnb_cashier','fnb_waiter','fnb_kitchen') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'reception',
  `status` enum('active','inactive') DEFAULT 'active',
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`staff_id`),
  UNIQUE KEY `account_id` (`account_id`),
  CONSTRAINT `fk_staff_accounts` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.staff: ~9 rows (approximately)
DELETE FROM `staff`;
INSERT INTO `staff` (`staff_id`, `account_id`, `full_name`, `role`, `status`, `email`, `phone`, `updated_at`) VALUES
	(2, 4, 'Reception Staff', 'reception', 'active', 'reception@hotelia.com', '123-456-7890', '2025-12-28 02:40:48'),
	(5, 10, 'F&B Waiter', 'fnb_waiter', 'active', 'waiter@hotelia.com', '123-456-7891', '2026-01-20 12:55:12'),
	(6, 12, 'Kitchen Master', 'fnb_kitchen', 'active', NULL, NULL, '2026-01-20 13:21:48'),
	(7, 13, 'Main Cashier', 'fnb_cashier', 'active', NULL, NULL, '2026-01-20 13:29:15'),
	(9, 16, 'System Administrator', 'admin', 'active', NULL, NULL, '2026-01-20 13:29:15'),
	(10, 17, 'tirdy', 'fnb_waiter', 'inactive', NULL, NULL, '2026-02-11 07:14:10'),
	(11, 18, 'yo', 'fnb_kitchen', 'inactive', NULL, NULL, '2026-02-11 07:14:10'),
	(12, 20, 'kdick', 'fnb_cashier', 'inactive', NULL, NULL, '2026-03-12 07:50:29'),
	(13, 25, 'lika reymaundo', 'fnb_waiter', 'inactive', NULL, NULL, '2026-03-12 07:50:29');

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
) ENGINE=InnoDB AUTO_INCREMENT=419 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.user_sessions: ~46 rows (approximately)
DELETE FROM `user_sessions`;
INSERT INTO `user_sessions` (`session_id`, `account_id`, `session_token`, `ip_address`, `user_agent`, `created_at`, `expires_at`) VALUES
	(230, 1, 'muj71pph1svmqdirm8q2qerjdq', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-07 11:42:24', '2036-01-06 18:57:40'),
	(231, 1, '3t7mfl16mgrcekoudm58nubgp2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 02:37:20', '2036-01-08 03:29:01'),
	(232, 4, 'npf858suq4mnh8eohb1bl0om3o', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 02:39:32', '2036-01-08 16:30:37'),
	(233, 1, '7ka163ujfgppcho2h2b1vrjdmo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 03:29:43', '2036-01-08 16:41:21'),
	(239, 13, 'ii87i9ffuvuhdjfpapi2e21vt1', '192.168.0.106', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-01-20 13:31:35', '2036-01-18 13:31:35'),
	(240, 4, '396n07kpm2hgi7eg63eftmo5va', '192.168.0.102', 'Mozilla/5.0 (Linux; Android 12; M2003J15SC) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Mobile Safari/537.36', '2026-01-20 13:33:25', '2036-01-18 13:52:50'),
	(248, 14, '7ktn65hoh1jn43findn4pd6iuv', '192.168.0.106', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-01-21 10:07:50', '2036-01-19 10:13:19');
INSERT INTO `user_sessions` (`session_id`, `account_id`, `session_token`, `ip_address`, `user_agent`, `created_at`, `expires_at`) VALUES
	(254, 14, 'bpltojaufhmat3svb3oakeo27b', '192.168.0.106', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-01-21 11:27:51', '2036-01-19 12:37:54'),
	(285, 13, 'rcsco0mde49oe1p8uu1ogmpcl6', '192.168.0.115', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-01-22 06:52:45', '2036-01-20 06:52:45'),
	(287, 10, 'jci8m6ntak2ida5jec4freupf0', '192.168.0.117', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-22 07:25:20', '2036-01-20 07:25:20'),
	(289, 13, 'gj8qiiipt2fmg64k50tems7hh2', '192.168.0.118', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/143.0.7499.151 Mobile/15E148 Safari/604.1', '2026-01-22 07:28:56', '2036-01-20 07:28:56'),
	(290, 21, 'arkmbcab0m0vkg0ercejn0ofpd', '192.168.0.105', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36', '2026-01-22 07:57:02', '2036-01-20 08:53:38'),
	(294, 22, '305lc68bv94v6i7vlb4pbcqbhc', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 08:31:27', '2036-01-20 08:32:56'),
	(298, 10, '4itcb0rk0nmn86vahguao9rpmu', '192.168.0.100', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-01-23 06:58:06', '2036-01-21 06:58:06');
INSERT INTO `user_sessions` (`session_id`, `account_id`, `session_token`, `ip_address`, `user_agent`, `created_at`, `expires_at`) VALUES
	(301, 10, '2f8e9antu3oe68cnqcs4o7p81r', '192.168.0.100', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-01-23 09:04:24', '2036-01-21 09:04:24'),
	(307, 10, 'ekp0eg26qk82oiu0nqc1i3gftu', '192.168.0.100', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-01-24 01:58:16', '2036-01-22 01:58:16'),
	(316, 13, 'qthmkuj424kt1sgfkrhbf270mb', '192.168.0.100', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-01-24 05:51:51', '2036-01-22 05:51:51'),
	(330, 13, 'g0lj27itgmtqrk6g5acteh13l4', '192.168.0.136', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/143.0.7499.151 Mobile/15E148 Safari/604.1', '2026-01-26 01:36:54', '2036-01-24 01:36:54'),
	(332, 10, 'hdjmoktkdqscitd4p1nj6fkksl', '192.168.0.122', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36', '2026-01-26 01:59:30', '2036-01-24 01:59:30'),
	(336, 10, 'h9l1c2k5taac6l5b383ddr7a5m', '192.168.0.104', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-01-26 05:39:43', '2036-01-24 05:39:43');
INSERT INTO `user_sessions` (`session_id`, `account_id`, `session_token`, `ip_address`, `user_agent`, `created_at`, `expires_at`) VALUES
	(342, 10, '8v90o5qrrj7pc15ftddcucloqu', '192.168.0.104', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-01-26 12:48:46', '2036-01-24 12:48:46'),
	(346, 13, 'vsq50uspl00dd7iu5vo9c4hufh', '192.168.0.104', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-01-26 14:42:07', '2036-01-24 14:42:07'),
	(348, 10, 'ec1koqrtft8jaclusrmsm9fjh1', '192.168.0.104', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-01-26 15:45:53', '2036-01-24 15:45:53'),
	(353, 10, 'trvuqj9cf029i9uksnbt53krfs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 16:28:10', '2036-01-24 16:28:10'),
	(355, 10, 'q2k81h0bkme7or90le4bcmojtg', '10.115.71.61', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36', '2026-01-27 02:37:48', '2036-01-25 02:37:48'),
	(356, 13, 'o7mc390svmjtcjso6lktig24ac', '10.115.71.146', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36', '2026-01-27 02:39:13', '2036-01-25 02:39:13'),
	(358, 12, '60nibnjigdp89plj0pg8931cbu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 02:52:43', '2036-01-25 02:52:43');
INSERT INTO `user_sessions` (`session_id`, `account_id`, `session_token`, `ip_address`, `user_agent`, `created_at`, `expires_at`) VALUES
	(359, 13, 'u5esr4jsav9upg6hucru2e9o8k', '10.115.71.146', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36', '2026-01-27 02:56:40', '2036-01-25 02:56:40'),
	(361, 10, 'mj8sa2ug0rtn8m10vhbhps3t4v', '192.168.213.87', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36', '2026-01-29 02:05:28', '2036-01-27 03:18:32'),
	(362, 10, '6tlskttlbmdb4s16pkgtnk24b9', '192.168.213.3', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36', '2026-01-29 02:05:44', '2036-01-27 02:05:44'),
	(364, 10, 'l4ri0ct0eorse5cngjb7o0vj40', '192.168.213.3', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36', '2026-01-29 03:43:08', '2036-01-27 03:43:08'),
	(367, 10, '4495i49btp1c1kgdlk2ko0c2ek', '10.21.247.155', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36', '2026-02-11 06:47:31', '2036-02-09 06:47:31'),
	(370, 10, 'dpsp8np23n54nukfjrdum47om6', '10.21.247.3', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36', '2026-02-11 06:49:10', '2036-02-09 06:49:10'),
	(371, 13, 'ksjug1s45k0603gbg7uhv607be', '10.21.247.218', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/143.0.7499.151 Mobile/15E148 Safari/604.1', '2026-02-11 06:50:34', '2036-02-09 06:50:34');
INSERT INTO `user_sessions` (`session_id`, `account_id`, `session_token`, `ip_address`, `user_agent`, `created_at`, `expires_at`) VALUES
	(379, 10, 'hknsc2cdpamu2tb0l7t1tj1k4f', '192.168.1.179', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36', '2026-02-11 07:37:02', '2036-02-09 07:37:02'),
	(380, 4, 'h498i7n8apljcbktoa1s6bslu7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-11 07:38:53', '2036-02-09 07:48:20'),
	(382, 10, 'b0pipmuvbb7pkqf1fn44geacmd', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 10:48:48', '2036-02-09 10:48:48'),
	(386, 10, '3cocvf1dm8irrra1rfmf3dmrbi', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-28 09:40:22', '2036-02-26 09:40:22'),
	(387, 13, 'jgn1jdbqnr25dae1pgkngbl6bo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-28 09:49:59', '2036-02-26 09:49:59'),
	(388, 10, 'fu113ro9qi153f52ntnvkd36u0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 01:34:52', '2036-03-07 01:34:52'),
	(389, 16, 'vfrcnpvui6i2hq7le0a63badd4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-10 05:42:56', '2036-03-07 08:51:29');
INSERT INTO `user_sessions` (`session_id`, `account_id`, `session_token`, `ip_address`, `user_agent`, `created_at`, `expires_at`) VALUES
	(417, 10, 'is85rpp6b96vfvgulbhb1a0ihg', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-14 03:25:02', '2026-03-14 11:25:02'),
	(418, 12, 'ldglau8uot96s9065ptggbp1qr', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-14 04:03:48', '2026-03-14 12:03:48');

-- Dumping structure for table hotelia_db.view_types
CREATE TABLE IF NOT EXISTS `view_types` (
  `view_type_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `premium_rate` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`view_type_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.view_types: ~7 rows (approximately)
DELETE FROM `view_types`;
INSERT INTO `view_types` (`view_type_id`, `name`, `premium_rate`) VALUES
	(5, 'Sea View', 0.00),
	(6, 'Pool View', 0.00),
	(7, 'Garden View', 0.00),
	(8, 'City View', 0.00),
	(9, 'Mountain View', 0.00),
	(10, 'Sunset View', 0.00),
	(11, 'Courtyard View', 0.00);

-- Dumping structure for trigger hotelia_db.check_room_availability_before_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `check_room_availability_before_insert` BEFORE INSERT ON `booking_items` FOR EACH ROW BEGIN
    DECLARE check_in_date_val DATE;
    DECLARE check_out_date_val DATE;
    
    -- Get dates from parent booking
    SELECT check_in_date, check_out_date 
    INTO check_in_date_val, check_out_date_val
    FROM bookings 
    WHERE booking_id = NEW.booking_id;
    
    -- Only check if a room is being assigned
    IF NEW.room_id IS NOT NULL THEN
        -- Check for overlapping bookings
        IF EXISTS (
            SELECT 1 FROM booking_items bi
            JOIN bookings b ON bi.booking_id = b.booking_id
            WHERE bi.room_id = NEW.room_id
            AND b.status IN ('confirmed', 'assigned', 'checked_in')
            AND check_in_date_val < b.check_out_date
            AND check_out_date_val > b.check_in_date
        ) THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Room is already booked for these dates';
        END IF;
    END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

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

-- Dumping structure for trigger hotelia_db.trg_rooms_before_insert_fix
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `trg_rooms_before_insert_fix` BEFORE INSERT ON `rooms` FOR EACH ROW BEGIN
  DECLARE expected INT;
  SELECT official_bed_type_id INTO expected FROM room_types WHERE room_type_id = NEW.room_type_id;
  IF expected IS NOT NULL THEN
    SET NEW.bed_type_id = expected;
  END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger hotelia_db.trg_rooms_before_update_fix
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `trg_rooms_before_update_fix` BEFORE UPDATE ON `rooms` FOR EACH ROW BEGIN
  DECLARE expected INT;
  SELECT official_bed_type_id INTO expected FROM room_types WHERE room_type_id = NEW.room_type_id;
  IF expected IS NOT NULL THEN
    SET NEW.bed_type_id = expected;
  END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger hotelia_db.trg_sync_room_type_beds
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `trg_sync_room_type_beds` AFTER UPDATE ON `room_types` FOR EACH ROW BEGIN
    -- Check if the official bed type has changed
    IF NEW.official_bed_type_id != OLD.official_bed_type_id THEN
        -- Update all rooms belonging to this room type
        UPDATE `rooms` 
        SET `bed_type_id` = NEW.official_bed_type_id
        WHERE `room_type_id` = NEW.room_type_id;
    END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `global_update_signal`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `global_update_signal` AS select max(`combined_signals`.`updated_at`) AS `last_change` from (select `bookings`.`updated_at` AS `updated_at` from `bookings` union all select `payments`.`updated_at` AS `updated_at` from `payments` union all select `rooms`.`updated_at` AS `updated_at` from `rooms` union all select `booking_items`.`updated_at` AS `updated_at` from `booking_items`) `combined_signals`;

-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `payment_summary`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `payment_summary` AS select `p`.`payment_id` AS `payment_id`,`p`.`booking_id` AS `booking_id`,`p`.`guest_id` AS `guest_id`,`p`.`paid_amount` AS `paid_amount`,`pm`.`method_name` AS `method_name`,`p`.`status` AS `status`,`p`.`payment_date` AS `payment_date`,`p`.`processed_by_staff` AS `processed_by_staff`,`p`.`user_reference` AS `user_reference`,`p`.`verification_attempts` AS `verification_attempts`,`p`.`payment_reference` AS `hotel_reference`,`b`.`reference_no` AS `booking_ref`,`g`.`full_name` AS `full_name`,`g`.`email` AS `email` from (((`payments` `p` join `payment_methods` `pm` on((`p`.`payment_method_id` = `pm`.`method_id`))) join `bookings` `b` on((`p`.`booking_id` = `b`.`booking_id`))) join `guests` `g` on((`p`.`guest_id` = `g`.`guest_id`)));

-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `room_availability`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `room_availability` AS select `r`.`room_id` AS `room_id`,`r`.`room_number` AS `room_number`,`r`.`floor` AS `floor`,`rt`.`name` AS `room_type`,`bt`.`name` AS `bed_type`,`vt`.`name` AS `view_type`,`r`.`capacity` AS `capacity`,`r`.`max_extra` AS `max_extra`,`rt`.`base_price` AS `base_price`,`r`.`status` AS `status`,`r`.`maintenance_status` AS `maintenance_status`,(case when ((`r`.`status` = 'available') and (`r`.`maintenance_status` = 'good')) then 'Available' when (`r`.`status` = 'occupied') then 'Occupied' when (`r`.`status` = 'maintenance') then 'Under Maintenance' when (`r`.`status` = 'reserved') then 'Reserved' when (`r`.`maintenance_status` = 'cleaning') then 'Cleaning' when (`r`.`maintenance_status` = 'under_repair') then 'Under Repair' else 'Not Available' end) AS `availability_status` from (((`rooms` `r` join `room_types` `rt` on((`r`.`room_type_id` = `rt`.`room_type_id`))) join `bed_types` `bt` on((`r`.`bed_type_id` = `bt`.`bed_type_id`))) join `view_types` `vt` on((`r`.`view_type_id` = `vt`.`view_type_id`))) order by `r`.`floor`,`r`.`room_number`;

-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `room_availability_with_conflicts`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `room_availability_with_conflicts` AS select `r`.`room_id` AS `room_id`,`r`.`room_number` AS `room_number`,`r`.`status` AS `room_status`,exists(select 1 from (`booking_items` `bi` join `bookings` `b` on((`bi`.`booking_id` = `b`.`booking_id`))) where ((`bi`.`room_id` = `r`.`room_id`) and (`b`.`status` in ('confirmed','assigned','checked_in')) and (curdate() between `b`.`check_in_date` and `b`.`check_out_date`))) AS `is_booked_today`,(select group_concat(concat(`b`.`reference_no`,' (',`b`.`check_in_date`,' to ',`b`.`check_out_date`,')') separator ', ') from (`booking_items` `bi` join `bookings` `b` on((`bi`.`booking_id` = `b`.`booking_id`))) where ((`bi`.`room_id` = `r`.`room_id`) and (`b`.`status` in ('confirmed','assigned','checked_in')) and (curdate() between `b`.`check_in_date` and `b`.`check_out_date`))) AS `current_bookings` from `rooms` `r` order by `r`.`room_number`;

-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `room_types_with_capacity`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `room_types_with_capacity` AS select `rt`.`room_type_id` AS `room_type_id`,`rt`.`name` AS `name`,`rt`.`base_price` AS `base_price`,`rt`.`description` AS `description`,coalesce(max((`r`.`capacity` + `r`.`max_extra`)),0) AS `calculated_max_capacity` from (`room_types` `rt` left join `rooms` `r` on((`rt`.`room_type_id` = `r`.`room_type_id`))) group by `rt`.`room_type_id`,`rt`.`name`,`rt`.`base_price`,`rt`.`description`;

-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `room_type_availability`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `room_type_availability` AS with `prebooked_counts` as (select `bi`.`room_type_id` AS `room_type_id`,count(distinct `bi`.`booking_item_id`) AS `prebooked_count` from ((`booking_items` `bi` join `bookings` `b` on((`bi`.`booking_id` = `b`.`booking_id`))) join `payments` `p` on((`b`.`booking_id` = `p`.`booking_id`))) where ((`bi`.`room_id` is null) and (`b`.`status` in ('confirmed','pending_verification')) and (`p`.`status` in ('completed','pending_verification')) and (`b`.`check_out_date` >= curdate())) group by `bi`.`room_type_id`), `room_counts` as (select `rooms`.`room_type_id` AS `room_type_id`,count(0) AS `total_rooms`,count((case when ((`rooms`.`status` = 'available') and (`rooms`.`maintenance_status` = 'good')) then 1 end)) AS `available_rooms`,count((case when (`rooms`.`status` = 'occupied') then 1 end)) AS `occupied_rooms`,count((case when ((`rooms`.`status` = 'maintenance') or (`rooms`.`maintenance_status` <> 'good')) then 1 end)) AS `maintenance_rooms` from `rooms` group by `rooms`.`room_type_id`) select `rt`.`room_type_id` AS `room_type_id`,`rt`.`name` AS `room_type_name`,`rt`.`base_price` AS `base_price`,`rc`.`total_rooms` AS `total_rooms`,`rc`.`available_rooms` AS `physically_available`,coalesce(`pc`.`prebooked_count`,0) AS `prebooked_count`,`rc`.`occupied_rooms` AS `occupied_rooms`,`rc`.`maintenance_rooms` AS `maintenance_rooms`,greatest((`rc`.`available_rooms` - coalesce(`pc`.`prebooked_count`,0)),0) AS `available_for_booking`,(case when (greatest((`rc`.`available_rooms` - coalesce(`pc`.`prebooked_count`,0)),0) > 0) then 'Available' else 'Fully Booked' end) AS `availability_status` from ((`room_types` `rt` left join `room_counts` `rc` on((`rt`.`room_type_id` = `rc`.`room_type_id`))) left join `prebooked_counts` `pc` on((`rt`.`room_type_id` = `pc`.`room_type_id`))) order by `rt`.`room_type_id`;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
