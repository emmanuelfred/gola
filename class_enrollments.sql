-- ============================================================================
-- SESSION-SCOPED CLASS ENROLLMENT
-- Run this after class_subjects_teacher.sql
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Table: class_enrollments
--
-- This is the real answer to "which students are actually sitting in this
-- class, this session." `students.class_id` is kept as a convenience "home /
-- default class" field (used when admitting a new student and for display),
-- but it is NOT what result entry or class rosters read from anymore.
--
-- Because enrollment is keyed by session_id, a brand-new session automatically
-- starts with zero enrollment rows for every class — no cleanup script needed,
-- classes are simply empty until the form teacher registers each student by
-- their reg no (students.student_id) through Class Roster.
--
-- UNIQUE(student_id, session_id) — a student can only be enrolled in ONE
-- class per session. Re-registering them into a different class the same
-- session moves them (see enrollStudentInClass() in enrollment_helper.php),
-- it doesn't create a second row.
-- ----------------------------------------------------------------------------
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

-- ----------------------------------------------------------------------------
-- Backfill: enroll every currently-Active student into their existing
-- students.class_id for whichever session is currently marked current.
-- This is a ONE-TIME catch-up so the session that's already in progress
-- doesn't suddenly look empty. It does NOT run for future sessions — those
-- genuinely start empty, which is the whole point.
-- ----------------------------------------------------------------------------
INSERT IGNORE INTO class_enrollments (student_id, class_id, session_id, enrolled_by, enrolled_at)
SELECT s.id, s.class_id, sess.id, NULL, NOW()
FROM students s
JOIN academic_sessions sess ON sess.is_current = 1
WHERE s.status = 'Active' AND s.class_id IS NOT NULL;

-- ----------------------------------------------------------------------------
-- Form Teacher now points at staff, not raw admin_users
-- (no existing FK to drop — classes.class_teacher_id had none)
-- ----------------------------------------------------------------------------
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_teacher_fk` FOREIGN KEY (`class_teacher_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;
