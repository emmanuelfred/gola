-- ============================================================================
-- ROLE-BASED PAGE PERMISSIONS
-- Run this after staff_module.sql
--
-- This replaces "everyone can see everything" with real per-role control.
-- Super Admin (and the legacy admin_users 'admin' tier) always bypasses
-- these checks — a safety net so nobody can lock themselves out while
-- editing permissions. Admin Users management stays super_admin-only
-- regardless of role_permissions, since granting it is effectively granting
-- the ability to create more admins.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `permissions` (
  `permission_key` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`permission_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`permission_key`, `label`, `category`, `display_order`) VALUES
('my_class',           'My Class',              'General',      1),
('students',           'Students & Parents',    'Management',   2),
('classes',            'Classes & Roster',      'Management',   3),
('timetable',          'Timetable',             'Management',   4),
('staff',              'Staff Records',         'Management',   5),
('results',            'Results & Class Subjects','Academics',  6),
('scratch_cards',      'Scratch Cards',         'Academics',    7),
('library',            'Library',               'Academics',    8),
('canteen',            'Canteen',               'Academics',    9),
('admissions',         'Admissions',            'Academics',   10),
('content',            'News & Gallery',        'Content',     11),
('events_departments', 'Events, Subjects & Departments','Content',12),
('fees',               'School Fees',           'Finance',     13),
('payroll',            'Payroll',               'Finance',     14),
('expenses',           'Expenses',              'Finance',     15),
('inventory',          'Inventory',             'Finance',     16),
('settings',           'School Settings',       'System',      17);

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_key` varchar(50) NOT NULL,
  PRIMARY KEY (`role_id`,`permission_key`),
  CONSTRAINT `rp_role_fk` FOREIGN KEY (`role_id`) REFERENCES `staff_roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rp_perm_fk` FOREIGN KEY (`permission_key`) REFERENCES `permissions` (`permission_key`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Sensible defaults for the predefined roles, so the system keeps working
-- out of the box. Adjust anytime from Manage Staff → Roles → Permissions.
-- ----------------------------------------------------------------------------

-- Super Admin & School Administrator: everything
INSERT INTO role_permissions (role_id, permission_key)
SELECT sr.id, p.permission_key FROM staff_roles sr, permissions p
WHERE sr.role_name IN ('Super Admin','School Administrator');

-- Principal & Vice Principal: broad oversight, everything except nothing withheld
INSERT INTO role_permissions (role_id, permission_key)
SELECT sr.id, p.permission_key FROM staff_roles sr, permissions p
WHERE sr.role_name IN ('Principal','Vice Principal');

-- Bursar / Accountant: finance-focused, plus enough student/class context to work
INSERT INTO role_permissions (role_id, permission_key)
SELECT sr.id, p.permission_key FROM staff_roles sr, permissions p
WHERE sr.role_name = 'Bursar / Accountant'
  AND p.permission_key IN ('students','classes','fees','payroll','expenses','inventory');

-- Registrar: admissions + student/class records
INSERT INTO role_permissions (role_id, permission_key)
SELECT sr.id, p.permission_key FROM staff_roles sr, permissions p
WHERE sr.role_name = 'Registrar'
  AND p.permission_key IN ('students','classes','admissions','timetable');

-- Teacher: their own teaching tools
INSERT INTO role_permissions (role_id, permission_key)
SELECT sr.id, p.permission_key FROM staff_roles sr, permissions p
WHERE sr.role_name = 'Teacher'
  AND p.permission_key IN ('my_class','results','timetable','library');

-- Form/Class Teacher: same as Teacher — My Class is their main tool
INSERT INTO role_permissions (role_id, permission_key)
SELECT sr.id, p.permission_key FROM staff_roles sr, permissions p
WHERE sr.role_name = 'Form/Class Teacher'
  AND p.permission_key IN ('my_class','results','timetable','library');

-- Head of Department: teaching tools + class/subject oversight
INSERT INTO role_permissions (role_id, permission_key)
SELECT sr.id, p.permission_key FROM staff_roles sr, permissions p
WHERE sr.role_name = 'Head of Department'
  AND p.permission_key IN ('my_class','results','timetable','library','classes');

-- Hostel Master/Mistress, Matron, School Nurse, Transport Officer: student lookup only
INSERT INTO role_permissions (role_id, permission_key)
SELECT sr.id, p.permission_key FROM staff_roles sr, permissions p
WHERE sr.role_name IN ('Hostel Master/Mistress','Matron','School Nurse','Transport Officer')
  AND p.permission_key = 'students';

-- Librarian: library
INSERT INTO role_permissions (role_id, permission_key)
SELECT sr.id, p.permission_key FROM staff_roles sr, permissions p
WHERE sr.role_name = 'Librarian' AND p.permission_key = 'library';

-- Storekeeper: inventory
INSERT INTO role_permissions (role_id, permission_key)
SELECT sr.id, p.permission_key FROM staff_roles sr, permissions p
WHERE sr.role_name = 'Storekeeper' AND p.permission_key = 'inventory';

-- HR Officer: staff records
INSERT INTO role_permissions (role_id, permission_key)
SELECT sr.id, p.permission_key FROM staff_roles sr, permissions p
WHERE sr.role_name = 'HR Officer' AND p.permission_key = 'staff';

-- Canteen Operator: canteen (this replaces the old hardcoded role-name check)
INSERT INTO role_permissions (role_id, permission_key)
SELECT sr.id, p.permission_key FROM staff_roles sr, permissions p
WHERE sr.role_name = 'Canteen Operator' AND p.permission_key = 'canteen';
