<?php
/**
 * permission_helper.php
 * ─────────────────────────────────────────────────────────────────────────
 * Real page-level access control. A role has a set of permission keys
 * (role_permissions table); the sidebar only shows sections the current
 * user's role is granted, and every page enforces the same check with
 * requirePermission() — so hiding a link and blocking direct URL access
 * are never out of sync with each other.
 *
 * Super Admin and the legacy admin_users 'admin'/'super_admin' tier always
 * bypass these checks — this is deliberate: it's the safety net that stops
 * an admin from locking themselves out while editing permissions.
 *
 * No session caching is used here on purpose — permissions are checked
 * fresh on every request, so a permission change takes effect immediately
 * instead of only after the affected user logs out and back in.
 *
 * Requires $conn (mysqli), $admin_id, $admin_role to already be available —
 * include this AFTER auth_check.php.
 * ─────────────────────────────────────────────────────────────────────────
 */

/**
 * Resolve the CURRENT logged-in user's set of granted permission keys.
 * Returns null if this user bypasses checks entirely (super admin tier).
 */
function getCurrentUserPermissionKeys(mysqli $conn, int $admin_id, string $admin_role): ?array {
    if (in_array($admin_role, ['super_admin', 'admin'], true)) {
        return null; // null = unrestricted, checked explicitly by userCan()
    }

    $stmt = $conn->prepare("
        SELECT rp.permission_key
        FROM staff s
        JOIN role_permissions rp ON rp.role_id = s.role_id
        WHERE s.admin_user_id = ? AND s.status = 'Active'
    ");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $keys = [];
    while ($row = $result->fetch_assoc()) $keys[] = $row['permission_key'];
    return $keys;
}

/**
 * Does the current user have this permission? Call this for conditional
 * UI (hiding sidebar links, buttons, etc.) — for actually blocking a page,
 * use requirePermission() instead.
 */
function userCan(string $permission_key): bool {
    global $conn, $admin_id, $admin_role;
    static $keys = null;
    static $resolved = false;
    if (!$resolved) {
        $keys = getCurrentUserPermissionKeys($conn, $admin_id, $admin_role);
        $resolved = true;
    }
    if ($keys === null) return true; // unrestricted tier
    return in_array($permission_key, $keys, true);
}

/**
 * Block the page entirely if the current user lacks this permission.
 * Call right after auth_check.php's require, before any output.
 */
function requirePermission(string $permission_key): void {
    if (!userCan($permission_key)) {
        header('Location: access_denied.php?feature=' . urlencode($permission_key));
        exit;
    }
}

/**
 * All permissions, grouped by category, for the role-permission editor UI.
 */
function getAllPermissionsGrouped(mysqli $conn): array {
    $result = $conn->query("SELECT * FROM permissions ORDER BY display_order ASC");
    $grouped = [];
    while ($row = $result->fetch_assoc()) $grouped[$row['category']][] = $row;
    return $grouped;
}

function getRolePermissionKeys(mysqli $conn, int $role_id): array {
    $stmt = $conn->prepare("SELECT permission_key FROM role_permissions WHERE role_id = ?");
    $stmt->bind_param("i", $role_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $keys = [];
    while ($row = $result->fetch_assoc()) $keys[] = $row['permission_key'];
    return $keys;
}

/**
 * Replace a role's entire permission set with the given list of keys.
 */
function setRolePermissions(mysqli $conn, int $role_id, array $permission_keys): void {
    $conn->query("DELETE FROM role_permissions WHERE role_id = " . intval($role_id));
    if (empty($permission_keys)) return;
    $stmt = $conn->prepare("INSERT INTO role_permissions (role_id, permission_key) VALUES (?,?)");
    foreach ($permission_keys as $key) {
        $stmt->bind_param("is", $role_id, $key);
        $stmt->execute();
    }
}
