-- ============================================================================
-- CLEAR ALL STUDENTS  (DESTRUCTIVE - cannot be undone without a backup)
--
-- STEP 1 - take a backup first, from the server shell:
--     mysqldump -u root -p goodness_omogo_db > /root/gola_backup_before_clear.sql
--
-- STEP 2 - run this file:
--     mysql -u root -p goodness_omogo_db < clear_all_students.sql
--
-- What happens: every row in `students` is deleted. Tables linked to a student
-- follow their own foreign-key rule:
--   * ON DELETE CASCADE  -> that student's rows are deleted too
--       (class enrollments, canteen ledger, and any fee/result/library rows
--        defined with CASCADE)
--   * ON DELETE SET NULL -> the row stays, but its student becomes empty
--       (e.g. canteen_sales receipts)
--   * no rule (RESTRICT) -> MySQL stops with an error and NOTHING is deleted;
--       tell me the table name it prints and I'll adjust the script.
-- Staff, classes, sessions, terms, fee structure and settings are NOT touched.
--
-- Afterwards the next student added gets GOLA/<year>/001, because the number is
-- worked out from the highest existing one (0 when the table is empty).
-- ============================================================================

SELECT COUNT(*) AS students_before FROM students;

START TRANSACTION;
DELETE FROM students;
COMMIT;

ALTER TABLE students AUTO_INCREMENT = 1;

SELECT COUNT(*) AS students_after FROM students;
