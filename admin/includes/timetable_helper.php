<?php
/**
 * timetable_helper.php
 * ─────────────────────────────────────────────────────────────────────────
 * The period structure (Period 1, Break, ...) is school-wide and set once
 * from admin/manage_timetable_periods.php. Each class's actual weekly grid
 * is set from admin/manage_timetable.php.
 *
 * A slot's teacher is never stored on the timetable — it's looked up from
 * class_subjects.teacher_id (see manage_class_subjects.php), so "who teaches
 * this subject in this class" always has exactly one source of truth.
 *
 * Requires $conn (mysqli connection) to already be available — include this
 * AFTER config/database.php.
 * ─────────────────────────────────────────────────────────────────────────
 */

const TIMETABLE_DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

/**
 * Get all periods in display order.
 */
function getTimetablePeriods(mysqli $conn): array {
    $result = $conn->query("SELECT * FROM timetable_periods ORDER BY display_order ASC, start_time ASC");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Get a class's full weekly timetable as a lookup grid:
 * [period_id][day_of_week] => ['subject_id'=>, 'subject_name'=>, 'subject_code'=>,
 *                               'teacher_id'=>, 'teacher_name'=>] or null if empty.
 */
function getClassTimetable(mysqli $conn, int $class_id): array {
    $stmt = $conn->prepare("
        SELECT ts.period_id, ts.day_of_week, ts.subject_id,
               s.subject_name, s.subject_code,
               cs.teacher_id, st.first_name as t_first, st.last_name as t_last
        FROM timetable_slots ts
        LEFT JOIN subjects s ON s.id = ts.subject_id
        LEFT JOIN class_subjects cs ON cs.class_id = ts.class_id AND cs.subject_id = ts.subject_id
        LEFT JOIN staff st ON st.id = cs.teacher_id
        WHERE ts.class_id = ?
    ");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $grid = [];
    while ($row = $result->fetch_assoc()) {
        $grid[$row['period_id']][$row['day_of_week']] = $row;
    }
    return $grid;
}

/**
 * Set (or clear) a single timetable slot. Pass subject_id = null to clear it.
 */
function setTimetableSlot(mysqli $conn, int $class_id, int $period_id, string $day, ?int $subject_id): bool {
    $stmt = $conn->prepare("
        INSERT INTO timetable_slots (class_id, period_id, day_of_week, subject_id)
        VALUES (?,?,?,?)
        ON DUPLICATE KEY UPDATE subject_id = VALUES(subject_id)
    ");
    $stmt->bind_param("iisi", $class_id, $period_id, $day, $subject_id);
    return $stmt->execute();
}

/**
 * A teacher's own personal timetable across every class they teach —
 * built from class_subjects.teacher_id, so it's automatically accurate
 * whenever subject-teacher assignments change.
 */
function getTeacherTimetable(mysqli $conn, int $staff_id): array {
    $stmt = $conn->prepare("
        SELECT ts.period_id, ts.day_of_week, ts.subject_id,
               s.subject_name, s.subject_code,
               c.id as class_id, c.class_name, c.arm
        FROM timetable_slots ts
        JOIN class_subjects cs ON cs.class_id = ts.class_id AND cs.subject_id = ts.subject_id
        JOIN subjects s ON s.id = ts.subject_id
        JOIN classes c ON c.id = ts.class_id
        WHERE cs.teacher_id = ?
    ");
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $grid = [];
    while ($row = $result->fetch_assoc()) {
        $grid[$row['period_id']][$row['day_of_week']] = $row;
    }
    return $grid;
}
