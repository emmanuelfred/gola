-- ============================================================================
-- CANTEEN SYSTEM
-- Run this after staff_module.sql
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Table: canteen_items
-- quantity_in_stock goes down automatically on every sale. low_stock_threshold
-- is just a display warning line, not a hard block.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `canteen_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `quantity_in_stock` int(11) NOT NULL DEFAULT 0,
  `low_stock_threshold` int(11) NOT NULL DEFAULT 5,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: canteen_sales
-- One row per transaction (= one receipt). student_id is nullable — an
-- anonymous over-the-counter cash sale doesn't need a student attached, but
-- payment_type='Tab' always requires one (enforced in code, not SQL, since
-- MySQL can't easily conditionally-require a column).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `canteen_sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `receipt_no` varchar(50) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_type` enum('Cash','Transfer','Tab') NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sold_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_no` (`receipt_no`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `sale_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sale_sold_by_fk` FOREIGN KEY (`sold_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: canteen_sale_items
-- Line items. Name and price are snapshotted at sale time so a receipt never
-- changes retroactively if the item is later renamed or repriced.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `canteen_sale_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `item_name` varchar(150) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `line_total` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  CONSTRAINT `sale_item_sale_fk` FOREIGN KEY (`sale_id`) REFERENCES `canteen_sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sale_item_item_fk` FOREIGN KEY (`item_id`) REFERENCES `canteen_items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: canteen_ledger
-- The running tab itself — a simple ledger, not a stateful "open/close tab"
-- object. A student's balance = SUM(Charge entries) - SUM(Payment entries).
-- This is what "running balance across visits" naturally falls out of.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `canteen_ledger` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `entry_type` enum('Charge','Payment') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `payment_method` enum('Cash','Transfer') DEFAULT NULL,
  `sale_id` int(11) DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `sale_id` (`sale_id`),
  CONSTRAINT `ledger_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ledger_sale_fk` FOREIGN KEY (`sale_id`) REFERENCES `canteen_sales` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ledger_recorded_by_fk` FOREIGN KEY (`recorded_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Credit limits: student override > class default > school-wide default.
-- NULL on a student or class means "use the next level up."
-- ----------------------------------------------------------------------------
ALTER TABLE `classes`  ADD COLUMN `canteen_credit_limit`  decimal(10,2) DEFAULT NULL AFTER `class_teacher_id`;
ALTER TABLE `students` ADD COLUMN `canteen_credit_limit`  decimal(10,2) DEFAULT NULL AFTER `status`;

CREATE TABLE IF NOT EXISTS `canteen_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `canteen_settings` (`setting_key`, `setting_value`) VALUES
('default_credit_limit', '2000.00');

-- ----------------------------------------------------------------------------
-- New role: Canteen Operator. Only people in this role (plus admins/super
-- admins, for oversight) can access the canteen pages — the first place in
-- the system with real page-level role restriction rather than just
-- action-level gating.
-- ----------------------------------------------------------------------------
INSERT INTO `staff_roles` (`role_name`, `default_salary`, `requires_login`, `system_access`, `display_order`)
SELECT 'Canteen Operator', 0.00, 1, 'admin', COALESCE((SELECT MAX(display_order)+1 FROM staff_roles s2), 1)
WHERE NOT EXISTS (SELECT 1 FROM staff_roles WHERE role_name = 'Canteen Operator');
