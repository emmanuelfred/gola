-- ============================================================================
-- PAYROLL SYSTEM
-- Run this after staff_module.sql
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Table: payroll_runs
-- One row per pay period (e.g. "2026-08" for August 2026). Creating a run
-- auto-populates payroll_items for every Active staff member using their
-- CURRENT staff.salary as the basic salary snapshot for that run — later
-- salary changes don't retroactively alter past runs.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payroll_runs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pay_period` varchar(20) NOT NULL COMMENT 'e.g. 2026-08',
  `label` varchar(50) NOT NULL COMMENT 'e.g. August 2026',
  `status` enum('Draft','Finalized') NOT NULL DEFAULT 'Draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `pay_period` (`pay_period`),
  CONSTRAINT `pr_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: payroll_items
-- One row per staff member per run — a payslip. basic_salary is a snapshot
-- (copied from staff.salary at the moment the run was created), so editing
-- a staff member's salary later never rewrites payroll history.
-- gross_pay = basic + all allowances. net_pay = gross - all deductions.
-- Both are stored (not computed on the fly) so a finalized payslip is a
-- permanent, unambiguous record even if the calculation logic changes later.
-- ----------------------------------------------------------------------------
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
