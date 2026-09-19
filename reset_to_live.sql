-- ============================================================================
-- GOLA — RESET TO LIVE USE
-- Clears all student/staff/financial test data. Keeps: admin accounts,
-- roles & permissions, everything the public website displays (news,
-- gallery, academic calendar/departments/curriculum, events), and the
-- school's setup/structure (classes, subjects, fee categories & structure,
-- grading scale, terms & sessions, canteen/inventory categories, and the
-- canteen/inventory/library item catalogs).
--
-- ⚠️  THIS CANNOT BE UNDONE. Take a full database backup/export first
--     (phpMyAdmin → Export, or `mysqldump goodness_omogo_db > backup.sql`)
--     and only run this once you're sure you have that backup saved
--     somewhere safe.
--
-- This version deletes in child-before-parent order instead of disabling
-- FOREIGN_KEY_CHECKS, because phpMyAdmin's importer runs large SQL files in
-- separate chunks and doesn't reliably carry that setting from one chunk to
-- the next — which is exactly what caused the earlier "#1701 Cannot
-- truncate a table referenced in a foreign key constraint" error.
--
-- Run this once, in full, e.g. via phpMyAdmin → SQL tab, or:
--     mysql -u <user> -p goodness_omogo_db < reset_to_live.sql
-- ============================================================================

-- ── Fix references into `staff` before it's wiped ──────────────────────────
-- `classes` and `class_subjects` are being KEPT, but each has a column
-- pointing at a staff member as the assigned teacher. Since `staff` is about
-- to be cleared, point those at "no teacher assigned yet" instead of leaving
-- them referencing a row that's about to disappear.
UPDATE `classes` SET `class_teacher_id` = NULL;
UPDATE `class_subjects` SET `teacher_id` = NULL;

-- ── Tables that point AT students/staff — must go first ─────────────────────
DELETE FROM `admissions_applications`;
DELETE FROM `canteen_sale_items`;
DELETE FROM `canteen_ledger`;
DELETE FROM `canteen_sales`;
DELETE FROM `class_enrollments`;
DELETE FROM `fee_payments`;
DELETE FROM `student_fee_adjustments`;
DELETE FROM `library_loans`;
DELETE FROM `payroll_items`;

-- ── Tables with no foreign keys into them — any order is fine ──────────────
DELETE FROM `scratch_card_usage`;
DELETE FROM `results`;
DELETE FROM `result_summary`;
DELETE FROM `communication_logs`;
DELETE FROM `prospectus_requests`;
DELETE FROM `activity_logs`;
DELETE FROM `expenses`;

-- ── Now safe to clear: everything that pointed at these has been removed ───
DELETE FROM `payroll_runs`;
DELETE FROM `scratch_cards`;
DELETE FROM `staff`;
DELETE FROM `students`;

-- ── Reset ID counters back to 1, so the next real record starts clean ──────
ALTER TABLE `admissions_applications` AUTO_INCREMENT = 1;
ALTER TABLE `canteen_sale_items` AUTO_INCREMENT = 1;
ALTER TABLE `canteen_ledger` AUTO_INCREMENT = 1;
ALTER TABLE `canteen_sales` AUTO_INCREMENT = 1;
ALTER TABLE `class_enrollments` AUTO_INCREMENT = 1;
ALTER TABLE `fee_payments` AUTO_INCREMENT = 1;
ALTER TABLE `student_fee_adjustments` AUTO_INCREMENT = 1;
ALTER TABLE `library_loans` AUTO_INCREMENT = 1;
ALTER TABLE `payroll_items` AUTO_INCREMENT = 1;
ALTER TABLE `scratch_card_usage` AUTO_INCREMENT = 1;
ALTER TABLE `results` AUTO_INCREMENT = 1;
ALTER TABLE `result_summary` AUTO_INCREMENT = 1;
ALTER TABLE `communication_logs` AUTO_INCREMENT = 1;
ALTER TABLE `prospectus_requests` AUTO_INCREMENT = 1;
ALTER TABLE `activity_logs` AUTO_INCREMENT = 1;
ALTER TABLE `expenses` AUTO_INCREMENT = 1;
ALTER TABLE `payroll_runs` AUTO_INCREMENT = 1;
ALTER TABLE `scratch_cards` AUTO_INCREMENT = 1;
ALTER TABLE `staff` AUTO_INCREMENT = 1;
ALTER TABLE `students` AUTO_INCREMENT = 1;

-- ============================================================================
-- NOT touched by this script (verify this list matches what you expect):
--   admin_users, staff_roles, permissions, role_permissions        (logins & roles)
--   news_articles, gallery_images                                  (blog & gallery)
--   academic_departments, academic_calendar, curriculum_subjects,
--   academic_events                                                (public website content)
--   school_settings                                                (site contact/config)
--   classes, subjects, class_subjects, terms, academic_sessions,
--   fee_categories, fee_structure, grading_system,
--   expense_categories, inventory_categories, canteen_settings,
--   canteen_items, inventory_items, library_books,
--   timetable_periods, timetable_slots                             (school setup/structure)
-- ============================================================================
