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
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.rooms: ~21 rows (approximately)
DELETE FROM `rooms`;
INSERT INTO `rooms` (`room_id`, `room_number`, `floor`, `room_type_id`, `bed_type_id`, `view_type_id`, `capacity`, `max_extra`, `status`, `description`, `created_at`, `maintenance_status`, `updated_at`) VALUES
	(7, 'G101', 1, 4, 8, 7, 2, 1, 'available', 'Cozy standard room with garden view', '2025-10-06 05:32:56', 'good', '2026-01-07 14:32:02'),
	(8, 'G102', 1, 4, 8, 11, 2, 1, 'reserved', 'Standard room with courtyard view', '2025-10-06 05:32:56', 'good', '2026-01-22 08:46:52'),
	(9, 'G103', 1, 4, 8, 6, 2, 1, 'available', 'Comfortable standard room with pool view', '2025-10-06 05:32:56', 'good', '2026-01-22 08:24:03'),
	(10, 'G104', 1, 14, 10, 11, 6, 0, 'reserved', 'Budget-friendly dormitory with 6 bunk beds', '2025-10-06 05:32:56', 'good', '2026-02-11 07:23:35'),
	(11, 'G105', 1, 14, 10, 11, 6, 0, 'available', 'Shared dormitory room, perfect for backpackers', '2025-10-06 05:32:56', 'good', '2026-01-26 01:45:28'),
	(12, '201', 2, 5, 8, 5, 2, 1, 'reserved', 'Spacious deluxe room with stunning sea view', '2025-10-06 05:32:56', 'good', '2026-02-11 07:35:45'),
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
	(27, '309', 3, 13, 9, 5, 3, 2, 'available', 'Exclusive mayor suite with sweeping sea and garden views', '2025-10-06 05:32:56', 'good', '2025-12-09 07:27:55');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
