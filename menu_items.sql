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
  `available_servings` int DEFAULT '0',
  PRIMARY KEY (`item_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `menu_categories` (`category_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hotelia_db.menu_items: ~31 rows (approximately)
DELETE FROM `menu_items`;
INSERT INTO `menu_items` (`item_id`, `category_id`, `item_name`, `item_description`, `price`, `image_path`, `status`, `is_special`, `is_available`, `inventory_item_id`, `stock_quantity`, `available_servings`) VALUES
	(1, 1, 'Lumpiang Shanghai', NULL, 185.00, 'lumpia_shanghai.jpg', 'unavailable', 0, 1, NULL, 0, 0),
	(2, 1, 'Pork Sisig (Sizzling)', NULL, 220.00, 'pork_sisig.jpg', 'unavailable', 0, 1, NULL, 0, 0),
	(3, 1, 'Chicharon Bulaklak', NULL, 195.00, 'chicharon-bulaklak.jpg', 'unavailable', 0, 1, NULL, 0, 0),
	(4, 1, 'Tokwa’t Baboy', NULL, 165.00, 'tokwa_baboy.webp', 'unavailable', 0, 1, NULL, 0, 0),
	(5, 1, 'Calamares', NULL, 240.00, 'calamares.jpg', 'unavailable', 0, 1, NULL, 0, 0),
	(6, 2, 'Sinigang na Baboy', NULL, 350.00, 'sinigang_baboy.webp', 'unavailable', 0, 1, NULL, 0, 0),
	(7, 2, 'Bulalo (Beef Shank Soup)', NULL, 480.00, 'bulalo.jpg', 'unavailable', 0, 1, NULL, 0, 0),
	(8, 2, 'Chicken Binakol', NULL, 320.00, 'chicken_binakol.jpg', 'unavailable', 0, 1, NULL, 0, 0),
	(9, 2, 'Pancit Molo', NULL, 180.00, 'pancit_molo.jpg', 'unavailable', 0, 1, NULL, 0, 0),
	(10, 2, 'Tinola na Manok', NULL, 290.00, 'chicken_tinola.jpg', 'unavailable', 0, 1, NULL, 0, 0),
	(11, 3, 'Ensaladang Talong (Eggplant)', NULL, 120.00, 'ensalada_talong.jpg', 'unavailable', 0, 1, NULL, 0, 0),
	(12, 3, 'Atcharang Papaya', NULL, 85.00, 'atchara.jpg', 'unavailable', 0, 1, NULL, 0, 0),
	(13, 3, 'Ensaladang Mangga at Bagoong', NULL, 110.00, 'ensalada_mangga.jpg', 'unavailable', 0, 1, NULL, 0, 0),
	(14, 3, 'Pako (Fiddlehead Fern) Salad', NULL, 155.00, 'pako_salad.jpg', 'unavailable', 0, 1, NULL, 0, 0),
	(15, 3, 'Ensaladang Kamatis at Sibuyas', NULL, 95.00, 'tomato_salad.webp', 'unavailable', 0, 1, NULL, 0, 0),
	(16, 4, 'Beef Caldereta', NULL, 380.00, 'beef_caldereta.jpeg', 'unavailable', 0, 1, NULL, 0, 0);
INSERT INTO `menu_items` (`item_id`, `category_id`, `item_name`, `item_description`, `price`, `image_path`, `status`, `is_special`, `is_available`, `inventory_item_id`, `stock_quantity`, `available_servings`) VALUES
	(17, 4, 'Chicken/Pork Adobo', NULL, 310.00, 'adobo.jpg', 'unavailable', 0, 1, NULL, 0, 0),
	(18, 4, 'Kare-Kare (Oxtail/Beef)', NULL, 450.00, 'kare_kare.jpg', 'unavailable', 0, 1, NULL, 0, 0),
	(19, 4, 'Crispy Pata (Deep Fried Trotter)', NULL, 650.00, 'crispy_pata.jpg', 'unavailable', 0, 1, NULL, 0, 0),
	(20, 4, 'Lechon Kawali', NULL, 340.00, 'lechon_kawali.jpg', 'unavailable', 0, 1, NULL, 0, 0),
	(21, 5, 'Halo-Halo Especial', NULL, 145.00, 'halo_halo.jpg', 'unavailable', 0, 1, NULL, 0, 0),
	(22, 5, 'Leche Flan', NULL, 120.00, 'leche_flan.jpg', 'unavailable', 0, 1, NULL, 0, 0),
	(23, 5, 'Buko Pandan', NULL, 85.00, 'buko_pandan.jpg', 'unavailable', 0, 1, NULL, 0, 0),
	(24, 5, 'Turon with Ice Cream', NULL, 110.00, 'turon.jpg', 'unavailable', 0, 1, NULL, 0, 0),
	(25, 5, 'Bibingka (Rice Cake)', NULL, 130.00, 'bibingka.jpg', 'unavailable', 0, 1, NULL, 0, 0),
	(26, 6, 'Sago’t Gulaman', NULL, 65.00, 'sago_gulaman.jpg', 'unavailable', 0, 1, NULL, 0, 0),
	(27, 6, 'Fresh Buko Juice', NULL, 95.00, 'buko_juice.jpg', 'unavailable', 0, 1, NULL, 0, 0),
	(28, 6, 'Calamansi Juice (Hot/Cold)', NULL, 55.00, 'calamansi.webp', 'unavailable', 0, 1, NULL, 0, 0),
	(29, 6, 'San Miguel Beer (Pilsen)', NULL, 80.00, 'sm_pilsen.webp', 'unavailable', 0, 1, NULL, 0, 0),
	(30, 6, 'Brewed Barako Coffee', NULL, 75.00, 'barako_coffee.webp', 'unavailable', 0, 1, NULL, 0, 0),
	(31, 4, 'Rice', NULL, 75.00, 'rice.jpg', 'unavailable', 0, 1, NULL, 0, 0);

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
