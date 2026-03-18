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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.payments: ~1 rows (approximately)
DELETE FROM `payments`;
INSERT INTO `payments` (`payment_id`, `booking_id`, `guest_id`, `payment_method_id`, `paid_amount`, `payment_reference`, `user_reference`, `verification_attempts`, `status`, `payment_date`, `processed_by_staff`, `receipt_number`, `verification_notes`, `verified_at`, `created_at`, `updated_at`) VALUES
	(1, 1, 14, 6, 1400.00, 'Cash (walk-in)', 'Cash (walk-in)', 0, 'completed', '2026-03-10 10:17:24', 2, NULL, NULL, '2026-03-10 18:17:24', '2026-03-10 10:17:24', '2026-03-10 10:17:24');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
