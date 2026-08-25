<?php
/**
 * session_helper.php
 * ─────────────────────────────────────────────────────────────────────────
 * Single source of truth for "which academic session/term are we in?"
 *
 * The CURRENT session and term are controlled from one place:
 *   admin/manage_settings.php?tab=session   (School Settings → Session & Term)
 *
 * Every page that needs to know / default to the current session or term
 * should require this file and call the functions below instead of writing
 * its own "SELECT ... WHERE is_current=1" query.
 *
 * Requires $conn (mysqli connection) to already be available — include this
 * AFTER config/database.php.
 * ─────────────────────────────────────────────────────────────────────────
 */

/**
 * Get the current academic session row (id, session_name, start_date, end_date, is_current).
 * Returns null if no session is marked current (e.g. fresh install).
 */
function getCurrentSession(mysqli $conn): ?array {
    $result = $conn->query("SELECT * FROM academic_sessions WHERE is_current = 1 LIMIT 1");
    return $result && $result->num_rows ? $result->fetch_assoc() : null;
}

/**
 * Get the current term row (id, term_name, session_id, start_date, end_date, is_current).
 * Returns null if no term is marked current.
 */
function getCurrentTerm(mysqli $conn): ?array {
    $result = $conn->query("SELECT * FROM terms WHERE is_current = 1 LIMIT 1");
    return $result && $result->num_rows ? $result->fetch_assoc() : null;
}

/**
 * Convenience: get both current session_id and term_id in one call.
 * Returns ['session_id' => int|null, 'term_id' => int|null,
 *          'session_name' => string|null, 'term_name' => string|null]
 */
function getCurrentSessionTerm(mysqli $conn): array {
    $session = getCurrentSession($conn);
    $term    = getCurrentTerm($conn);
    return [
        'session_id'   => $session['id'] ?? null,
        'session_name' => $session['session_name'] ?? null,
        'term_id'      => $term['id'] ?? null,
        'term_name'    => $term['term_name'] ?? null,
    ];
}

/**
 * Get all academic sessions, most recent first.
 */
function getAllSessions(mysqli $conn): array {
    $result = $conn->query("SELECT * FROM academic_sessions ORDER BY id DESC");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Get all terms for a given session (or all terms if $session_id is null).
 */
function getAllTerms(mysqli $conn, ?int $session_id = null): array {
    if ($session_id) {
        $stmt = $conn->prepare("SELECT * FROM terms WHERE session_id = ? ORDER BY id ASC");
        $stmt->bind_param("i", $session_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    $result = $conn->query("SELECT * FROM terms ORDER BY session_id DESC, id ASC");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Render a <select> of sessions, pre-selecting the current one (or $selected_id if given).
 * $name is the form field name. Echoes directly.
 */
function renderSessionDropdown(mysqli $conn, string $name = 'session_id', ?int $selected_id = null, bool $required = true): void {
    $sessions = getAllSessions($conn);
    if ($selected_id === null) {
        $current = getCurrentSession($conn);
        $selected_id = $current['id'] ?? null;
    }
    $req = $required ? 'required' : '';
    echo "<select name=\"$name\" $req class=\"w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold\">";
    echo "<option value=\"\">Select Session</option>";
    foreach ($sessions as $s) {
        $sel = ($selected_id !== null && (int)$selected_id === (int)$s['id']) ? 'selected' : '';
        $label = htmlspecialchars($s['session_name']) . ($s['is_current'] ? ' (Current)' : '');
        echo "<option value=\"{$s['id']}\" $sel>$label</option>";
    }
    echo "</select>";
}

/**
 * Render a <select> of terms for a given session, pre-selecting the current one (or $selected_id).
 * If $session_id is null, lists all terms.
 */
function renderTermDropdown(mysqli $conn, string $name = 'term_id', ?int $session_id = null, ?int $selected_id = null, bool $required = true): void {
    $terms = getAllTerms($conn, $session_id);
    if ($selected_id === null) {
        $current = getCurrentTerm($conn);
        $selected_id = $current['id'] ?? null;
    }
    $req = $required ? 'required' : '';
    echo "<select name=\"$name\" $req class=\"w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold\">";
    echo "<option value=\"\">Select Term</option>";
    foreach ($terms as $t) {
        $sel = ($selected_id !== null && (int)$selected_id === (int)$t['id']) ? 'selected' : '';
        $label = htmlspecialchars($t['term_name']) . ($t['is_current'] ? ' (Current)' : '');
        echo "<option value=\"{$t['id']}\" $sel>$label</option>";
    }
    echo "</select>";
}
