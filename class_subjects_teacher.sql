-- ============================================================================
-- CLASS SUBJECT TEACHER ASSIGNMENT
-- Run this after staff_module.sql (needs the staff table to exist)
-- ============================================================================

-- Adds the missing piece: which staff member teaches this subject in this
-- specific class. This is what lets the same person be, say, the English
-- teacher for JSS 1 and the Literature teacher for SS 1 — the assignment is
-- per (class, subject), not a single global "subject teacher."
ALTER TABLE `class_subjects`
  ADD COLUMN `teacher_id` int(11) DEFAULT NULL AFTER `subject_id`,
  ADD KEY `teacher_id` (`teacher_id`),
  ADD CONSTRAINT `class_subjects_teacher_fk` FOREIGN KEY (`teacher_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;
