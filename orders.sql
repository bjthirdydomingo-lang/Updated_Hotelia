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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.orders: ~1 rows (approximately)
DELETE FROM `orders`;
INSERT INTO `orders` (`order_id`, `booking_id`, `waiter_id`, `cashier_id`, `table_number`, `room_number`, `total_amount`, `status`, `billing_method`, `billing_status`, `order_source`, `is_billed`, `created_at`, `updated_at`, `amount_received`, `amount_change`) VALUES
	(1, 1, 5, 5, '1', 'G101', 1086.40, 'pending', 'room', 'unpaid', 'waiter', 0, '2026-02-28 09:59:42', '2026-02-28 10:00:37', 0.00, 0.00);

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
