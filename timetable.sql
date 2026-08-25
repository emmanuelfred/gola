-- ============================================================================
-- TIMETABLE
-- Run this after class_enrollments.sql
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Table: timetable_periods
-- The school-wide period structure (Period 1, Break, Period 2, ...), defined
-- ONCE and shared by every class's timetable — not redefined per class.
-- `is_break` periods (break, assembly, lunch) can't be assigned a subject.
-- ----------------------------------------------------------------------------
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

-- A typical Nigerian secondary school day, 8am–3pm. Edit freely from
-- Admin → Timetable Periods afterward — this is just a sensible starting point.
INSERT INTO `timetable_periods` (`period_name`, `start_time`, `end_time`, `is_break`, `display_order`) VALUES
('Period 1', '08:00:00', '08:40:00', 0, 1),
('Period 2', '08:40:00', '09:20:00', 0, 2),
('Period 3', '09:20:00', '10:00:00', 0, 3),
('Short Break', '10:00:00', '10:20:00', 1, 4),
('Period 4', '10:20:00', '11:00:00', 0, 5),
('Period 5', '11:00:00', '11:40:00', 0, 6),
('Period 6', '11:40:00', '12:20:00', 0, 7),
('Lunch Break', '12:20:00', '13:00:00', 1, 8),
('Period 7', '13:00:00', '13:40:00', 0, 9),
('Period 8', '13:40:00', '14:20:00', 0, 10);

-- ----------------------------------------------------------------------------
-- Table: timetable_slots
-- One row per (class, day, period). `subject_id` must already be assigned to
-- the class via class_subjects — the teacher for this slot is NOT stored here,
-- it's looked up from class_subjects.teacher_id, so there's exactly one place
-- that says "who teaches what" (set in Manage Class Subjects). A slot with a
-- NULL subject_id on a non-break period just means nothing is scheduled yet.
-- ----------------------------------------------------------------------------
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
