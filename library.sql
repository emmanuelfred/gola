-- ============================================================================
-- LIBRARY SYSTEM
-- Run this after staff_module.sql (loans can be borrowed by staff, needs that table)
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Table: library_books
-- Tracked at title level (not individual barcoded copies) — total_copies and
-- available_copies. available_copies goes down when lent, up when returned.
-- ----------------------------------------------------------------------------
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

-- ----------------------------------------------------------------------------
-- Table: library_loans
-- One row per borrow. A loan is "active" while returned_date IS NULL.
-- Overdue is NOT a stored status — it's computed as
-- (returned_date IS NULL AND due_date < CURDATE()), so it's always accurate
-- without a cron job or manual update.
-- ----------------------------------------------------------------------------
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
