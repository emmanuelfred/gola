-- ============================================================================
-- STAFF & ROLE MODULE
-- Run this after database_schema.sql (adds 2 new tables, touches nothing else)
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Table: staff_roles
-- Predefined job roles. Each role carries the info needed for payroll later:
--   - default_salary   : starting salary figure for that role (editable per staff)
--   - requires_login    : does someone in this role need a system login?
--                         (a role that requires_login=1 gets an admin_users
--                          account when the staff member is added — their
--                          login activity is what payroll will later use as
--                          an attendance/presence signal. Roles that don't
--                          need to log in (e.g. Storekeeper) are still full
--                          staff/payroll records, just without a login.)
--   - system_access     : which of the existing app access tiers
--                         (super_admin / admin / teacher) this role maps to.
--                         Only relevant when requires_login=1. For now every
--                         tier can already see every page in the system —
--                         this field exists so that later, when granular
--                         per-role permissions are introduced, each role can
--                         be redefined directly from this table without
--                         touching any code.
--   - is_active         : soft-disable a role without deleting staff history
-- ----------------------------------------------------------------------------
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

-- Seed the predefined roles (salaries left at 0.00 — set real figures from
-- Admin → Manage Roles once decided; nothing here is guessed).
INSERT INTO `staff_roles` (`role_name`, `default_salary`, `requires_login`, `system_access`, `display_order`) VALUES
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

-- ----------------------------------------------------------------------------
-- Table: staff
-- One record per staff member. `role_id` drives salary defaults and whether
-- a login is expected. `admin_user_id` is only set when this staff member
-- actually has a login account (linked to the existing admin_users table),
-- so the app's current login/permission system keeps working untouched.
-- ----------------------------------------------------------------------------
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
