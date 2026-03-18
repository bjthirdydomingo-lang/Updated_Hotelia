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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.bookings: ~1 rows (approximately)
DELETE FROM `bookings`;
INSERT INTO `bookings` (`booking_id`, `reference_no`, `guest_id`, `check_in_date`, `check_out_date`, `nights`, `room_subtotal`, `extra_person_total`, `discount_amount`, `grand_total`, `status`, `created_at`, `tax_amount`, `updated_at`) VALUES
	(1, 'HTL260310-6686', 14, '2026-03-10 00:00:00', '2026-03-11 00:00:00', 1, 2500.00, 0.00, 0.00, 4799.20, 'checked_in', '2026-03-10 03:26:06', 300.00, '2026-03-10 05:22:15');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
