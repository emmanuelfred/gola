<?php
/**
 * expense_helper.php
 * Requires $conn (mysqli connection) to already be available.
 */

function getExpenseCategories(mysqli $conn): array {
    $result = $conn->query("SELECT * FROM expense_categories WHERE is_active=1 ORDER BY name ASC");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getExpenses(mysqli $conn, string $from, string $to, int $category_id = 0): array {
    $sql = "SELECT e.*, ec.name as category_name, au.full_name as recorded_by_name
            FROM expenses e
            JOIN expense_categories ec ON ec.id = e.category_id
            LEFT JOIN admin_users au ON au.id = e.recorded_by
            WHERE e.expense_date BETWEEN ? AND ?";
    $types = "ss";
    $params = [$from, $to];
    if ($category_id) { $sql .= " AND e.category_id = ?"; $types .= "i"; $params[] = $category_id; }
    $sql .= " ORDER BY e.expense_date DESC, e.id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getExpensesTotal(mysqli $conn, string $from, string $to): float {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) as t FROM expenses WHERE expense_date BETWEEN ? AND ?");
    $stmt->bind_param("ss", $from, $to);
    $stmt->execute();
    return (float) $stmt->get_result()->fetch_assoc()['t'];
}

function getExpensesByCategory(mysqli $conn, string $from, string $to): array {
    $stmt = $conn->prepare("
        SELECT ec.name, COALESCE(SUM(e.amount),0) as total, COUNT(e.id) as count
        FROM expense_categories ec
        LEFT JOIN expenses e ON e.category_id = ec.id AND e.expense_date BETWEEN ? AND ?
        WHERE ec.is_active=1
        GROUP BY ec.id, ec.name
        HAVING total > 0
        ORDER BY total DESC
    ");
    $stmt->bind_param("ss", $from, $to);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function recordExpense(mysqli $conn, int $category_id, string $description, float $amount, string $expense_date, string $method, ?string $receipt_ref, ?string $notes, ?int $recorded_by): array {
    if ($amount <= 0) return ['ok' => false, 'error' => 'Amount must be greater than zero.'];
    $stmt = $conn->prepare("INSERT INTO expenses (category_id, description, amount, expense_date, payment_method, receipt_reference, notes, recorded_by) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->bind_param("isdssssi", $category_id, $description, $amount, $expense_date, $method, $receipt_ref, $notes, $recorded_by);
    $ok = $stmt->execute();
    return ['ok' => $ok, 'error' => $ok ? null : $conn->error];
}
