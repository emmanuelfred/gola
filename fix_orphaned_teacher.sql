-- Run this BEFORE the classes_teacher_fk ALTER if it failed.
-- Clears any class_teacher_id left over from the old admin_users-based
-- system that doesn't correspond to a real staff record. You'll need to
-- reassign the form teacher for these classes afterward via Manage Classes.
UPDATE classes
SET class_teacher_id = NULL
WHERE class_teacher_id IS NOT NULL
  AND class_teacher_id NOT IN (SELECT id FROM staff);

-- Then retry:
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_teacher_fk` FOREIGN KEY (`class_teacher_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;
