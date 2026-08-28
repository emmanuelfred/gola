-- ============================================================================
-- GOLA — COMPLETE MIGRATION, SAFE TO RE-RUN
--
-- This file replaces running the individual .sql files one by one. It is
-- fully idempotent: every CREATE TABLE, ALTER TABLE, ADD COLUMN, ADD
-- CONSTRAINT, and seed INSERT in here checks first whether it's already
-- been applied, and skips itself if so. Nothing in this file ever runs
-- DROP, DELETE, or TRUNCATE on your data — it only adds what's missing.
-- Existing data is never touched.
--
-- Safe to run on:
--   - a database that's never had any of this applied
--   - a database that has SOME of it applied (exactly your situation)
--   - a database that already has ALL of it applied (running this again
--     later, e.g. after a future `git pull`, will just do nothing)
--
-- Usage:
--   mysql -u root -p goodness_omogo_db < gola_full_migration.sql
-- ============================================================================

USE goodness_omogo_db;

-- ----------------------------------------------------------------------------
-- Helper procedures (used throughout this file, dropped again at the end)
-- ----------------------------------------------------------------------------
DELIMITER $$

DROP PROCEDURE IF EXISTS gola_add_column_if_missing$$
CREATE PROCEDURE gola_add_column_if_missing(
    IN p_table VARCHAR(64), IN p_column VARCHAR(64), IN p_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_column
    ) THEN
        SET @gola_sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN ', p_definition);
        PREPARE gola_stmt FROM @gola_sql;
        EXECUTE gola_stmt;
        DEALLOCATE PREPARE gola_stmt;
    END IF;
END$$

DROP PROCEDURE IF EXISTS gola_add_index_if_missing$$
CREATE PROCEDURE gola_add_index_if_missing(
    IN p_table VARCHAR(64), IN p_index_name VARCHAR(64), IN p_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND INDEX_NAME = p_index_name
    ) THEN
        SET @gola_sql = CONCAT('ALTER TABLE `', p_table, '` ADD KEY `', p_index_name, '` ', p_definition);
        PREPARE gola_stmt FROM @gola_sql;
        EXECUTE gola_stmt;
        DEALLOCATE PREPARE gola_stmt;
    END IF;
END$$

DROP PROCEDURE IF EXISTS gola_add_fk_if_missing$$
CREATE PROCEDURE gola_add_fk_if_missing(
    IN p_table VARCHAR(64), IN p_constraint_name VARCHAR(64), IN p_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND CONSTRAINT_NAME = p_constraint_name
    ) THEN
        SET @gola_sql = CONCAT('ALTER TABLE `', p_table, '` ADD CONSTRAINT `', p_constraint_name, '` ', p_definition);
        PREPARE gola_stmt FROM @gola_sql;
        EXECUTE gola_stmt;
        DEALLOCATE PREPARE gola_stmt;
    END IF;
END$$

DELIMITER ;


-- ============================================================================
-- 1. COMMUNICATION LOG  (already CREATE TABLE IF NOT EXISTS — safe as-is)
-- ============================================================================
CREATE TABLE IF NOT EXISTS communication_logs (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    sent_by      INT NOT NULL,
    channel      ENUM('email','sms') NOT NULL,
    recipient_type ENUM('single','bulk','class') NOT NULL,
    class_id     INT NULL,
    student_ids  TEXT NULL,
    subject      VARCHAR(255),
    message      TEXT NOT NULL,
    recipient_count INT DEFAULT 0,
    failed_count    INT DEFAULT 0,
    status       ENUM('sent','partial','failed') DEFAULT 'sent',
    sent_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sent_by) REFERENCES admin_users(id) ON DELETE CASCADE,
    INDEX idx_sent_at (sent_at),
    INDEX idx_channel (channel)
);


-- ============================================================================
-- 2. STAFF MODULE
-- ============================================================================
CREATE TABLE IF NOT EXISTS `staff_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(100) NOT NULL,
  `default_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `requires_login` tinyint(1) NOT NULL DEFAULT 0,
  `system_access` enum('super_admin','admin','teacher') NOT NULL DEFAULT 'teacher',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `staff_roles` (`role_name`, `default_salary`, `requires_login`, `system_access`, `display_order`) VALUES
('Super Admin',            0.00, 1, 'super_admin', 1),
('School Administrator',   0.00, 1, 'admin',       2),
('Principal',              0.00, 1, 'admin',       3),
('Vice Principal',         0.00, 1, 'admin',       4),
('Bursar / Accountant',    0.00, 1, 'admin',       5),
('Registrar',              0.00, 1, 'admin',       6),
('Teacher',                0.00, 1, 'teacher',     7),
('Form/Class Teacher',     0.00, 1, 'teacher',     8),
('Head of Department',     0.00, 1, 'teacher',     9),
('Hostel Master/Mistress', 0.00, 0, 'teacher',    10),
('Matron',                 0.00, 0, 'teacher',    11),
('Librarian',              0.00, 1, 'teacher',    12),
('School Nurse',           0.00, 0, 'teacher',    13),
('Transport Officer',      0.00, 0, 'teacher',    14),
('Storekeeper',            0.00, 0, 'teacher',    15),
('HR Officer',             0.00, 1, 'admin',      16);

CREATE TABLE IF NOT EXISTS `staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` varchar(50) NOT NULL,
  `role_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `passport_photo` varchar(255) DEFAULT NULL,
  `date_employed` date DEFAULT NULL,
  `salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('Active','On Leave','Suspended','Terminated') NOT NULL DEFAULT 'Active',
  `admin_user_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_id` (`staff_id`),
  KEY `role_id` (`role_id`),
  KEY `admin_user_id` (`admin_user_id`),
  CONSTRAINT `staff_role_fk` FOREIGN KEY (`role_id`) REFERENCES `staff_roles` (`id`),
  CONSTRAINT `staff_admin_user_fk` FOREIGN KEY (`admin_user_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 3. CLASS SUBJECT TEACHER ASSIGNMENT
-- ============================================================================
CALL gola_add_column_if_missing('class_subjects', 'teacher_id', '`teacher_id` int(11) DEFAULT NULL AFTER `subject_id`');
CALL gola_add_index_if_missing('class_subjects', 'teacher_id', '(`teacher_id`)');
CALL gola_add_fk_if_missing('class_subjects', 'class_subjects_teacher_fk', 'FOREIGN KEY (`teacher_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL');


-- ============================================================================
-- 4. SESSION-SCOPED CLASS ENROLLMENT
-- ============================================================================
CREATE TABLE IF NOT EXISTS `class_enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `enrolled_by` int(11) DEFAULT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_session` (`student_id`,`session_id`),
  KEY `class_id` (`class_id`),
  KEY `session_id` (`session_id`),
  KEY `enrolled_by` (`enrolled_by`),
  CONSTRAINT `enroll_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enroll_class_fk` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  CONSTRAINT `enroll_session_fk` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`),
  CONSTRAINT `enroll_admin_fk` FOREIGN KEY (`enrolled_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO class_enrollments (student_id, class_id, session_id, enrolled_by, enrolled_at)
SELECT s.id, s.class_id, sess.id, NULL, NOW()
FROM students s
JOIN academic_sessions sess ON sess.is_current = 1
WHERE s.status = 'Active' AND s.class_id IS NOT NULL;

-- Only add this FK if class_teacher_id has no leftover values that don't
-- correspond to a real staff.id. If it does, this step is skipped rather
-- than erroring — run fix_orphaned_teacher.sql, then re-run this file.
SET @orphaned_teacher_refs = (
    SELECT COUNT(*) FROM classes
    WHERE class_teacher_id IS NOT NULL
      AND class_teacher_id NOT IN (SELECT id FROM staff)
);
SET @gola_skip_fk = IF(@orphaned_teacher_refs > 0, 1, 0);

SET @fk_already_exists = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'classes' AND CONSTRAINT_NAME = 'classes_teacher_fk'
);
SET @gola_sql = IF(@fk_already_exists = 0 AND @orphaned_teacher_refs = 0,
    'ALTER TABLE `classes` ADD CONSTRAINT `classes_teacher_fk` FOREIGN KEY (`class_teacher_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE gola_stmt FROM @gola_sql;
EXECUTE gola_stmt;
DEALLOCATE PREPARE gola_stmt;


-- ============================================================================
-- 5. TIMETABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS `timetable_periods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `period_name` varchar(50) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_break` tinyint(1) NOT NULL DEFAULT 0,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- timetable_periods has no unique key on period_name, so a plain re-run
-- would create duplicates — each row is guarded individually instead.
INSERT INTO timetable_periods (period_name, start_time, end_time, is_break, display_order)
SELECT * FROM (SELECT 'Period 1' AS period_name, '08:00:00' AS start_time, '08:40:00' AS end_time, 0 AS is_break, 1 AS display_order) t
WHERE NOT EXISTS (SELECT 1 FROM timetable_periods WHERE period_name = 'Period 1');

INSERT INTO timetable_periods (period_name, start_time, end_time, is_break, display_order)
SELECT * FROM (SELECT 'Period 2', '08:40:00', '09:20:00', 0, 2) t
WHERE NOT EXISTS (SELECT 1 FROM timetable_periods WHERE period_name = 'Period 2');

INSERT INTO timetable_periods (period_name, start_time, end_time, is_break, display_order)
SELECT * FROM (SELECT 'Period 3', '09:20:00', '10:00:00', 0, 3) t
WHERE NOT EXISTS (SELECT 1 FROM timetable_periods WHERE period_name = 'Period 3');

INSERT INTO timetable_periods (period_name, start_time, end_time, is_break, display_order)
SELECT * FROM (SELECT 'Short Break', '10:00:00', '10:20:00', 1, 4) t
WHERE NOT EXISTS (SELECT 1 FROM timetable_periods WHERE period_name = 'Short Break');

INSERT INTO timetable_periods (period_name, start_time, end_time, is_break, display_order)
SELECT * FROM (SELECT 'Period 4', '10:20:00', '11:00:00', 0, 5) t
WHERE NOT EXISTS (SELECT 1 FROM timetable_periods WHERE period_name = 'Period 4');

INSERT INTO timetable_periods (period_name, start_time, end_time, is_break, display_order)
SELECT * FROM (SELECT 'Period 5', '11:00:00', '11:40:00', 0, 6) t
WHERE NOT EXISTS (SELECT 1 FROM timetable_periods WHERE period_name = 'Period 5');

INSERT INTO timetable_periods (period_name, start_time, end_time, is_break, display_order)
SELECT * FROM (SELECT 'Period 6', '11:40:00', '12:20:00', 0, 7) t
WHERE NOT EXISTS (SELECT 1 FROM timetable_periods WHERE period_name = 'Period 6');

INSERT INTO timetable_periods (period_name, start_time, end_time, is_break, display_order)
SELECT * FROM (SELECT 'Lunch Break', '12:20:00', '13:00:00', 1, 8) t
WHERE NOT EXISTS (SELECT 1 FROM timetable_periods WHERE period_name = 'Lunch Break');

INSERT INTO timetable_periods (period_name, start_time, end_time, is_break, display_order)
SELECT * FROM (SELECT 'Period 7', '13:00:00', '13:40:00', 0, 9) t
WHERE NOT EXISTS (SELECT 1 FROM timetable_periods WHERE period_name = 'Period 7');

INSERT INTO timetable_periods (period_name, start_time, end_time, is_break, display_order)
SELECT * FROM (SELECT 'Period 8', '13:40:00', '14:20:00', 0, 10) t
WHERE NOT EXISTS (SELECT 1 FROM timetable_periods WHERE period_name = 'Period 8');

CREATE TABLE IF NOT EXISTS `timetable_slots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL,
  `period_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday') NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_slot` (`class_id`,`period_id`,`day_of_week`),
  KEY `subject_id` (`subject_id`),
  CONSTRAINT `slot_class_fk` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `slot_period_fk` FOREIGN KEY (`period_id`) REFERENCES `timetable_periods` (`id`) ON DELETE CASCADE,
  CONSTRAINT `slot_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 6. LIBRARY
-- ============================================================================
CREATE TABLE IF NOT EXISTS `library_books` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `author` varchar(150) DEFAULT NULL,
  `isbn` varchar(50) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `shelf_location` varchar(50) DEFAULT NULL,
  `total_copies` int(11) NOT NULL DEFAULT 1,
  `available_copies` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `library_loans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `book_id` int(11) NOT NULL,
  `borrower_type` enum('Student','Staff') NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `borrowed_date` date NOT NULL,
  `due_date` date NOT NULL,
  `returned_date` date DEFAULT NULL,
  `status` enum('Borrowed','Returned','Lost') NOT NULL DEFAULT 'Borrowed',
  `issued_by` int(11) DEFAULT NULL,
  `returned_to` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `book_id` (`book_id`),
  KEY `student_id` (`student_id`),
  KEY `staff_id` (`staff_id`),
  CONSTRAINT `loan_book_fk` FOREIGN KEY (`book_id`) REFERENCES `library_books` (`id`),
  CONSTRAINT `loan_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loan_staff_fk` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loan_issued_by_fk` FOREIGN KEY (`issued_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loan_returned_to_fk` FOREIGN KEY (`returned_to`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 7. CANTEEN
-- ============================================================================
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

CALL gola_add_column_if_missing('classes', 'canteen_credit_limit', '`canteen_credit_limit` decimal(10,2) DEFAULT NULL AFTER `class_teacher_id`');
CALL gola_add_column_if_missing('students', 'canteen_credit_limit', '`canteen_credit_limit` decimal(10,2) DEFAULT NULL AFTER `status`');

CREATE TABLE IF NOT EXISTS `canteen_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `canteen_settings` (`setting_key`, `setting_value`) VALUES
('default_credit_limit', '2000.00');

INSERT INTO `staff_roles` (`role_name`, `default_salary`, `requires_login`, `system_access`, `display_order`)
SELECT 'Canteen Operator', 0.00, 1, 'admin', COALESCE((SELECT MAX(display_order)+1 FROM staff_roles s2), 1)
WHERE NOT EXISTS (SELECT 1 FROM staff_roles WHERE role_name = 'Canteen Operator');

CALL gola_add_column_if_missing('canteen_items', 'cost_price', '`cost_price` decimal(10,2) DEFAULT NULL AFTER `price`');


-- ============================================================================
-- 8. SCHOOL FEES
-- ============================================================================
CREATE TABLE IF NOT EXISTS `fee_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `fee_categories` (`name`, `display_order`) VALUES
('Tuition', 1), ('PTA', 2), ('Sports', 3), ('Development Levy', 4);

CREATE TABLE IF NOT EXISTS `fee_structure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `term_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_rate` (`class_id`,`term_id`,`category_id`),
  KEY `session_id` (`session_id`),
  CONSTRAINT `fs_class_fk` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fs_session_fk` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`),
  CONSTRAINT `fs_term_fk` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fs_category_fk` FOREIGN KEY (`category_id`) REFERENCES `fee_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `student_fee_adjustments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `term_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `sfa_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sfa_term_fk` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sfa_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `fee_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `receipt_no` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `term_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('Cash','Transfer') NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_no` (`receipt_no`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `fp_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fp_session_fk` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`),
  CONSTRAINT `fp_term_fk` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fp_recorded_by_fk` FOREIGN KEY (`recorded_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 9. PAYROLL
-- ============================================================================
CREATE TABLE IF NOT EXISTS `payroll_runs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pay_period` varchar(20) NOT NULL,
  `label` varchar(50) NOT NULL,
  `status` enum('Draft','Finalized') NOT NULL DEFAULT 'Draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `pay_period` (`pay_period`),
  CONSTRAINT `pr_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payroll_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payroll_run_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `basic_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `housing_allowance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `transport_allowance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `other_allowance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_deduction` decimal(12,2) NOT NULL DEFAULT 0.00,
  `pension_deduction` decimal(12,2) NOT NULL DEFAULT 0.00,
  `other_deduction` decimal(12,2) NOT NULL DEFAULT 0.00,
  `gross_pay` decimal(12,2) NOT NULL DEFAULT 0.00,
  `net_pay` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('Pending','Paid') NOT NULL DEFAULT 'Pending',
  `payment_method` enum('Cash','Transfer') DEFAULT NULL,
  `paid_date` date DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_run_staff` (`payroll_run_id`,`staff_id`),
  KEY `staff_id` (`staff_id`),
  CONSTRAINT `pi_run_fk` FOREIGN KEY (`payroll_run_id`) REFERENCES `payroll_runs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pi_staff_fk` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 10. EXPENSES
-- ============================================================================
CREATE TABLE IF NOT EXISTS `expense_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `expense_categories` (`name`) VALUES
('Utilities'), ('Maintenance & Repairs'), ('Supplies & Stationery'),
('Transport & Fuel'), ('Furniture & Equipment'), ('Events & Activities'), ('Other');

CREATE TABLE IF NOT EXISTS `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `expense_date` date NOT NULL,
  `payment_method` enum('Cash','Transfer') NOT NULL,
  `receipt_reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `exp_category_fk` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`),
  CONSTRAINT `exp_recorded_by_fk` FOREIGN KEY (`recorded_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 11. INVENTORY
-- ============================================================================
CREATE TABLE IF NOT EXISTS `inventory_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `inventory_categories` (`name`) VALUES
('Furniture'), ('Electronics'), ('Lab Equipment'), ('Sports Equipment'), ('Vehicles'), ('Other');

CREATE TABLE IF NOT EXISTS `inventory_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `category_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_value` decimal(12,2) DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `condition_status` enum('New','Good','Fair','Poor','Damaged') NOT NULL DEFAULT 'Good',
  `serial_number` varchar(100) DEFAULT NULL,
  `date_acquired` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `inv_category_fk` FOREIGN KEY (`category_id`) REFERENCES `inventory_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 12. ROLE-BASED PAGE PERMISSIONS  (run last — needs staff_roles populated)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `permissions` (
  `permission_key` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`permission_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `permissions` (`permission_key`, `label`, `category`, `display_order`) VALUES
('my_class',           'My Class',              'General',      1),
('students',           'Students & Parents',    'Management',   2),
('classes',            'Classes & Roster',      'Management',   3),
('timetable',          'Timetable',             'Management',   4),
('staff',              'Staff Records',         'Management',   5),
('results',            'Results & Class Subjects','Academics',  6),
('scratch_cards',      'Scratch Cards',         'Academics',    7),
('library',            'Library',               'Academics',    8),
('canteen',            'Canteen',               'Academics',    9),
('admissions',         'Admissions',            'Academics',   10),
('content',            'News & Gallery',        'Content',     11),
('events_departments', 'Events, Subjects & Departments','Content',12),
('fees',               'School Fees',           'Finance',     13),
('payroll',            'Payroll',               'Finance',     14),
('expenses',           'Expenses',              'Finance',     15),
('inventory',          'Inventory',             'Finance',     16),
('settings',           'School Settings',       'System',      17);

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_key` varchar(50) NOT NULL,
  PRIMARY KEY (`role_id`,`permission_key`),
  CONSTRAINT `rp_role_fk` FOREIGN KEY (`role_id`) REFERENCES `staff_roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rp_perm_fk` FOREIGN KEY (`permission_key`) REFERENCES `permissions` (`permission_key`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO role_permissions (role_id, permission_key)
SELECT sr.id, p.permission_key FROM staff_roles sr, permissions p
WHERE sr.role_name IN ('Super Admin','School Administrator');

INSERT IGNORE INTO role_permissions (role_id, permission_key)
SELECT sr.id, p.permission_key FROM staff_roles sr, permissions p
WHERE sr.role_name IN ('Principal','Vice Principal');

INSERT IGNORE INTO role_permissions (role_id, permission_key)
SELECT sr.id, p.permission_key FROM staff_roles sr, permissions p
WHERE sr.role_name = 'Bursar / Accountant'
  AND p.permission_key IN ('students','classes','fees','payroll','expenses','inventory');

INSERT IGNORE INTO role_permissions (role_id, permission_key)
SELECT sr.id, p.permission_key FROM staff_roles sr, permissions p
WHERE sr.role_name = 'Registrar'
  AND p.permission_key IN ('students','classes','admissions','timetable');

INSERT IGNORE INTO role_permissions (role_id, permission_key)
SELECT sr.id, p.permission_key FROM staff_roles sr, permissions p
WHERE sr.role_name = 'Teacher'
  AND p.permission_key IN ('my_class','results','timetable','library');

INSERT IGNORE INTO role_permissions (role_id, permission_key)
SELECT sr.id, p.permission_key FROM staff_roles sr, permissions p
WHERE sr.role_name = 'Form/Class Teacher'
  AND p.permission_key IN ('my_class','results','timetable','library');

INSERT IGNORE INTO role_permissions (role_id, permission_key)
SELECT sr.id, p.permission_key FROM staff_roles sr, permissions p
WHERE sr.role_name = 'Head of Department'
  AND p.permission_key IN ('my_class','results','timetable','library','classes');

INSERT IGNORE INTO role_permissions (role_id, permission_key)
SELECT sr.id, p.permission_key FROM staff_roles sr, permissions p
WHERE sr.role_name IN ('Hostel Master/Mistress','Matron','School Nurse','Transport Officer')
  AND p.permission_key = 'students';

INSERT IGNORE INTO role_permissions (role_id, permission_key)
SELECT sr.id, p.permission_key FROM staff_roles sr, permissions p
WHERE sr.role_name = 'Librarian' AND p.permission_key = 'library';

INSERT IGNORE INTO role_permissions (role_id, permission_key)
SELECT sr.id, p.permission_key FROM staff_roles sr, permissions p
WHERE sr.role_name = 'Storekeeper' AND p.permission_key = 'inventory';

INSERT IGNORE INTO role_permissions (role_id, permission_key)
SELECT sr.id, p.permission_key FROM staff_roles sr, permissions p
WHERE sr.role_name = 'HR Officer' AND p.permission_key = 'staff';

INSERT IGNORE INTO role_permissions (role_id, permission_key)
SELECT sr.id, p.permission_key FROM staff_roles sr, permissions p
WHERE sr.role_name = 'Canteen Operator' AND p.permission_key = 'canteen';


-- ----------------------------------------------------------------------------
-- Cleanup — drop the helper procedures, they're not needed after this runs
-- ----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS gola_add_column_if_missing;
DROP PROCEDURE IF EXISTS gola_add_index_if_missing;
DROP PROCEDURE IF EXISTS gola_add_fk_if_missing;

SELECT 'Migration complete.' AS result;
