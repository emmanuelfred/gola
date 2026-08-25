-- ============================================================================
-- INVENTORY (school property — furniture, equipment, etc. Not for sale,
-- distinct from canteen_items which tracks sellable stock)
-- Run this after staff_module.sql
-- ============================================================================

CREATE TABLE IF NOT EXISTS `inventory_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `inventory_categories` (`name`) VALUES
('Furniture'), ('Electronics'), ('Lab Equipment'), ('Sports Equipment'), ('Vehicles'), ('Other');

CREATE TABLE IF NOT EXISTS `inventory_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `category_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_value` decimal(12,2) DEFAULT NULL COMMENT 'optional — cost/value per unit, for asset value reporting',
  `location` varchar(150) DEFAULT NULL COMMENT 'e.g. JSS1-A classroom, ICT Lab, Store',
  `condition_status` enum('New','Good','Fair','Poor','Damaged') NOT NULL DEFAULT 'Good',
  `serial_number` varchar(100) DEFAULT NULL,
  `date_acquired` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0 = disposed/decommissioned',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `inv_category_fk` FOREIGN KEY (`category_id`) REFERENCES `inventory_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
