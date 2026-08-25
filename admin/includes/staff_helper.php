<?php
/**
 * staff_helper.php
 * ─────────────────────────────────────────────────────────────────────────
 * Shared helpers for the Staff & Role module.
 *
 * Roles are managed from: admin/manage_staff_roles.php
 * Staff are managed from: admin/manage_staff.php
 *
 * Requires $conn (mysqli connection) to already be available — include this
 * AFTER config/database.php.
 * ─────────────────────────────────────────────────────────────────────────
 */

/**
 * Get all roles (active + inactive), ordered for display.
 */
function getAllRoles(mysqli $conn): array {
    $result = $conn->query("SELECT * FROM staff_roles ORDER BY display_order ASC, role_name ASC");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Get only roles that can currently be assigned to new staff.
 */
function getActiveRoles(mysqli $conn): array {
    $result = $conn->query("SELECT * FROM staff_roles WHERE is_active = 1 ORDER BY display_order ASC, role_name ASC");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Get a single role by id.
 */
function getRole(mysqli $conn, int $role_id): ?array {
    $stmt = $conn->prepare("SELECT * FROM staff_roles WHERE id = ?");
    $stmt->bind_param("i", $role_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

/**
 * Auto-generate a unique staff ID, e.g. GOLA/STAFF/2026/0001
 */
function generateStaffId(mysqli $conn): string {
    $year  = date('Y');
    $count = $conn->query("SELECT COUNT(*) as c FROM staff WHERE staff_id LIKE 'GOLA/STAFF/$year/%'")->fetch_assoc()['c'];
    $staff_id = 'GOLA/STAFF/' . $year . '/' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

    // Guard against a rare collision (e.g. a staff record deleted then re-counted)
    $check = $conn->prepare("SELECT id FROM staff WHERE staff_id = ?");
    $check->bind_param("s", $staff_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $staff_id .= '-' . rand(10, 99);
    }
    return $staff_id;
}

/**
 * Get active staff, for use in "assign teacher" dropdowns (e.g. class subject
 * teacher, form/class teacher). Teacher-type roles are listed first, but every
 * active staff member is included — some schools have a Principal or VP who
 * also teaches a subject.
 */
function getAssignableStaff(mysqli $conn): array {
    $result = $conn->query("
        SELECT st.id, st.first_name, st.last_name, st.staff_id, r.role_name,
               CASE WHEN r.role_name LIKE '%Teacher%' OR r.role_name LIKE '%Head of Department%' THEN 0 ELSE 1 END as sort_group
        FROM staff st
        JOIN staff_roles r ON st.role_id = r.id
        WHERE st.status = 'Active'
        ORDER BY sort_group ASC, st.first_name ASC, st.last_name ASC
    ");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Get a single staff member's display name by id, or null.
 */
function getStaffName(mysqli $conn, ?int $staff_id): ?string {
    if (!$staff_id) return null;
    $stmt = $conn->prepare("SELECT first_name, last_name FROM staff WHERE id = ?");
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? trim($row['first_name'] . ' ' . $row['last_name']) : null;
}
/**
 * Reverse-lookup: find the staff record linked to a given login (admin_users.id).
 * Used to figure out "who is this logged-in person, as staff" — e.g. for the
 * Form Class Manager to know which class they're the form teacher of.
 */
function getStaffByAdminUserId(mysqli $conn, int $admin_user_id): ?array {
    $stmt = $conn->prepare("SELECT * FROM staff WHERE admin_user_id = ? LIMIT 1");
    $stmt->bind_param("i", $admin_user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

/**
 * Generate a simple, human-friendly username from a staff member's name,
 * guaranteed unique against admin_users. e.g. "John Adeyemi" -> "jadeyemi",
 * "jadeyemi2" if taken.
 */
function generateUsername(mysqli $conn, string $first_name, string $last_name): string {
    $base = strtolower(preg_replace('/[^a-z]/i', '', substr($first_name, 0, 1) . $last_name));
    if ($base === '') $base = 'staff';
    $username = $base;
    $i = 1;
    while (true) {
        $stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) break;
        $i++;
        $username = $base . $i;
    }
    return $username;
}
