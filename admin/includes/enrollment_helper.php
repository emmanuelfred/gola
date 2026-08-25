<?php
/**
 * enrollment_helper.php
 * ─────────────────────────────────────────────────────────────────────────
 * Session-scoped class rosters. A student's "class" for result entry and
 * roster purposes is whatever `class_enrollments` says for the CURRENT
 * session — not the permanent `students.class_id` field (which is just a
 * convenience "home class" used at admission time).
 *
 * Registration happens in: admin/class_roster.php
 *
 * Requires $conn (mysqli connection) to already be available — include this
 * AFTER config/database.php.
 * ─────────────────────────────────────────────────────────────────────────
 */

/**
 * Get the full roster (enrolled students) for a class in a given session.
 * Only returns students whose own record is still 'Active'.
 */
function getClassRoster(mysqli $conn, int $class_id, int $session_id): array {
    $stmt = $conn->prepare("
        SELECT s.id, s.student_id, s.first_name, COALESCE(s.middle_name,'') as middle_name, s.last_name,
               s.gender, s.phone as student_phone, s.email as student_email,
               s.father_name, s.father_phone, s.mother_name, s.mother_phone,
               s.guardian_name, s.guardian_phone, s.guardian_relationship, s.parent_email,
               ce.id as enrollment_id, ce.enrolled_at, ce.enrolled_by
        FROM class_enrollments ce
        JOIN students s ON s.id = ce.student_id
        WHERE ce.class_id = ? AND ce.session_id = ? AND s.status = 'Active'
        ORDER BY s.last_name, s.first_name
    ");
    $stmt->bind_param("ii", $class_id, $session_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * How many students are enrolled in a class for a given session.
 */
function getClassRosterCount(mysqli $conn, int $class_id, int $session_id): int {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as c FROM class_enrollments ce
        JOIN students s ON s.id = ce.student_id
        WHERE ce.class_id = ? AND ce.session_id = ? AND s.status = 'Active'
    ");
    $stmt->bind_param("ii", $class_id, $session_id);
    $stmt->execute();
    return (int) $stmt->get_result()->fetch_assoc()['c'];
}

/**
 * Find an Active student by their reg no (students.student_id), for the
 * "register by reg no" flow. Returns null if not found or not Active.
 */
function findStudentByRegNo(mysqli $conn, string $reg_no): ?array {
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ? AND status = 'Active'");
    $stmt->bind_param("s", trim($reg_no));
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

/**
 * Get a student's current enrollment (class + session) for a given session,
 * or null if they aren't enrolled anywhere that session yet.
 */
function getStudentEnrollment(mysqli $conn, int $student_id, int $session_id): ?array {
    $stmt = $conn->prepare("
        SELECT ce.*, c.class_name, c.arm
        FROM class_enrollments ce
        JOIN classes c ON c.id = ce.class_id
        WHERE ce.student_id = ? AND ce.session_id = ?
    ");
    $stmt->bind_param("ii", $student_id, $session_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

/**
 * Enroll (or move) a student into a class for a session. Because of the
 * UNIQUE(student_id, session_id) constraint, this is an upsert: if the
 * student is already enrolled elsewhere this session, they're moved to the
 * new class rather than creating a duplicate row.
 *
 * Returns ['moved' => bool, 'previous_class_id' => int|null]
 */
function enrollStudentInClass(mysqli $conn, int $student_id, int $class_id, int $session_id, ?int $enrolled_by): array {
    $existing = getStudentEnrollment($conn, $student_id, $session_id);
    $moved = $existing && $existing['class_id'] != $class_id;
    $previous_class_id = $existing ? $existing['class_id'] : null;

    $stmt = $conn->prepare("
        INSERT INTO class_enrollments (student_id, class_id, session_id, enrolled_by, enrolled_at)
        VALUES (?,?,?,?,NOW())
        ON DUPLICATE KEY UPDATE class_id = VALUES(class_id), enrolled_by = VALUES(enrolled_by), enrolled_at = NOW()
    ");
    $stmt->bind_param("iiii", $student_id, $class_id, $session_id, $enrolled_by);
    $stmt->execute();

    return ['moved' => $moved, 'previous_class_id' => $previous_class_id];
}

/**
 * Remove a student from a class roster for a session (un-enroll). This does
 * NOT delete or change the student record itself — only their enrollment.
 */
function unenrollStudent(mysqli $conn, int $student_id, int $session_id): bool {
    $stmt = $conn->prepare("DELETE FROM class_enrollments WHERE student_id = ? AND session_id = ?");
    $stmt->bind_param("ii", $student_id, $session_id);
    return $stmt->execute();
}

/**
 * Find which class (if any) a staff member is the Form/Class Teacher of.
 * Used by the Form Class Manager to scope a teacher to only their own class.
 */
function getFormTeacherClass(mysqli $conn, int $staff_id): ?array {
    $stmt = $conn->prepare("SELECT * FROM classes WHERE class_teacher_id = ? LIMIT 1");
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}
