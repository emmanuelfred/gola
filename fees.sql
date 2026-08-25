-- ============================================================================
-- SCHOOL FEES SYSTEM
-- Run this after class_enrollments.sql
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Table: fee_categories
-- e.g. Tuition, PTA, Sports, Development Levy — the categories that get
-- summed together to make up a term's total fee.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fee_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `fee_categories` (`name`, `display_order`) VALUES
('Tuition', 1), ('PTA', 2), ('Sports', 3), ('Development Levy', 4);

-- ----------------------------------------------------------------------------
-- Table: fee_structure
-- The rate card: how much each category costs for a given class, in a given
-- session + term. A student's total fee for a term = SUM of these rows for
-- their class, plus any personal adjustment (see student_fee_adjustments).
-- This is computed live, not stored as a snapshot — so if you fix a rate
-- card mistake, it corrects everyone's balance immediately rather than
-- needing a re-generation step.
-- ----------------------------------------------------------------------------
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

-- ----------------------------------------------------------------------------
-- Table: student_fee_adjustments
-- A per-student, per-term adjustment on top of the class rate card — a
-- scholarship/discount (negative amount) or an extra charge (positive
-- amount), with a reason on record. Optional; most students have none.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_fee_adjustments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `term_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL COMMENT 'negative = discount, positive = extra charge',
  `reason` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `sfa_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sfa_term_fk` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sfa_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: fee_payments
-- Every payment received, scoped to a term. Installments are simply
-- multiple rows — balance = (rate card total + adjustments) - SUM(payments).
-- ----------------------------------------------------------------------------
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
