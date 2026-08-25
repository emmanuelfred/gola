<?php
/**
 * fee_helper.php
 * ─────────────────────────────────────────────────────────────────────────
 * A student's total fee for a term is NEVER stored directly — it's computed
 * live as: SUM(fee_structure for their class/session/term) + SUM(their
 * student_fee_adjustments for that term). Balance = that total minus
 * SUM(fee_payments). This means fixing a rate-card mistake instantly
 * corrects every affected student's balance, and installments are simply
 * multiple payment rows against the same running total.
 *
 * Requires $conn (mysqli connection) to already be available — include this
 * AFTER config/database.php.
 * ─────────────────────────────────────────────────────────────────────────
 */

function getFeeCategories(mysqli $conn): array {
    $result = $conn->query("SELECT * FROM fee_categories WHERE is_active=1 ORDER BY display_order ASC, name ASC");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * The rate card for a class in a given session/term — one row per category.
 */
function getFeeStructureForClass(mysqli $conn, int $class_id, int $session_id, int $term_id): array {
    $stmt = $conn->prepare("
        SELECT fc.id as category_id, fc.name, COALESCE(fs.amount,0) as amount, fs.id as structure_id
        FROM fee_categories fc
        LEFT JOIN fee_structure fs ON fs.category_id = fc.id AND fs.class_id=? AND fs.session_id=? AND fs.term_id=?
        WHERE fc.is_active=1 ORDER BY fc.display_order ASC, fc.name ASC
    ");
    $stmt->bind_param("iii", $class_id, $session_id, $term_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Set (or update) one rate-card amount for a class/term/category.
 */
function setFeeStructureAmount(mysqli $conn, int $class_id, int $session_id, int $term_id, int $category_id, float $amount): bool {
    $stmt = $conn->prepare("
        INSERT INTO fee_structure (class_id, session_id, term_id, category_id, amount) VALUES (?,?,?,?,?)
        ON DUPLICATE KEY UPDATE amount = VALUES(amount), session_id = VALUES(session_id)
    ");
    $stmt->bind_param("iiiid", $class_id, $session_id, $term_id, $category_id, $amount);
    return $stmt->execute();
}

/**
 * Total rate-card fee (sum of all categories) for a class in a term.
 */
function getClassFeeTotal(mysqli $conn, int $class_id, int $session_id, int $term_id): float {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) as t FROM fee_structure WHERE class_id=? AND session_id=? AND term_id=?");
    $stmt->bind_param("iii", $class_id, $session_id, $term_id);
    $stmt->execute();
    return (float) $stmt->get_result()->fetch_assoc()['t'];
}

/**
 * A student's total fee owed for a term = their class's rate-card total,
 * plus any personal adjustments for that term.
 */
function getStudentFeeTotal(mysqli $conn, int $student_id, int $class_id, int $session_id, int $term_id): float {
    $base = getClassFeeTotal($conn, $class_id, $session_id, $term_id);

    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) as t FROM student_fee_adjustments WHERE student_id=? AND session_id=? AND term_id=?");
    $stmt->bind_param("iii", $student_id, $session_id, $term_id);
    $stmt->execute();
    $adjustments = (float) $stmt->get_result()->fetch_assoc()['t'];

    return $base + $adjustments;
}

function getStudentFeePaid(mysqli $conn, int $student_id, int $session_id, int $term_id): float {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) as t FROM fee_payments WHERE student_id=? AND session_id=? AND term_id=?");
    $stmt->bind_param("iii", $student_id, $session_id, $term_id);
    $stmt->execute();
    return (float) $stmt->get_result()->fetch_assoc()['t'];
}

/**
 * Full breakdown for one student's term fee status: total owed, paid, balance.
 */
function getStudentFeeStatus(mysqli $conn, int $student_id, int $class_id, int $session_id, int $term_id): array {
    $total = getStudentFeeTotal($conn, $student_id, $class_id, $session_id, $term_id);
    $paid  = getStudentFeePaid($conn, $student_id, $session_id, $term_id);
    return ['total' => $total, 'paid' => $paid, 'balance' => $total - $paid];
}

function generateFeeReceiptNo(mysqli $conn): string {
    $year  = date('Y');
    $count = $conn->query("SELECT COUNT(*) as c FROM fee_payments WHERE receipt_no LIKE 'GOLA/FEES/$year/%'")->fetch_assoc()['c'];
    return 'GOLA/FEES/' . $year . '/' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
}

/**
 * Record a fee payment. Does not block overpayment (a student may
 * legitimately pay ahead), but the UI defaults the amount field to the
 * current balance.
 */
function recordFeePayment(mysqli $conn, int $student_id, int $session_id, int $term_id, float $amount, string $method, ?string $notes, ?int $recorded_by): array {
    if ($amount <= 0) return ['ok' => false, 'error' => 'Payment amount must be greater than zero.', 'receipt_no' => null];
    if (!in_array($method, ['Cash','Transfer'], true)) return ['ok' => false, 'error' => 'Invalid payment method.', 'receipt_no' => null];

    $receipt_no = generateFeeReceiptNo($conn);
    $stmt = $conn->prepare("INSERT INTO fee_payments (receipt_no, student_id, session_id, term_id, amount, payment_method, notes, recorded_by) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->bind_param("siiidssi", $receipt_no, $student_id, $session_id, $term_id, $amount, $method, $notes, $recorded_by);
    if ($stmt->execute()) {
        return ['ok' => true, 'error' => null, 'receipt_no' => $receipt_no, 'payment_id' => $conn->insert_id];
    }
    return ['ok' => false, 'error' => $conn->error, 'receipt_no' => null];
}

/**
 * Every student enrolled in a session/term with an outstanding balance > 0,
 * scanning across all classes. Used for the "who hasn't paid" report.
 */
function getStudentsWithOutstandingFees(mysqli $conn, int $session_id, int $term_id): array {
    $stmt = $conn->prepare("
        SELECT s.id, s.student_id, s.first_name, s.last_name, c.id as class_id, c.class_name, c.arm
        FROM class_enrollments ce
        JOIN students s ON s.id = ce.student_id
        JOIN classes c ON c.id = ce.class_id
        WHERE ce.session_id = ? AND s.status='Active'
        ORDER BY c.class_level DESC, c.class_name, s.last_name
    ");
    $stmt->bind_param("i", $session_id);
    $stmt->execute();
    $roster = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $out = [];
    foreach ($roster as $st) {
        $status = getStudentFeeStatus($conn, $st['id'], $st['class_id'], $session_id, $term_id);
        if ($status['balance'] > 0.009) {
            $out[] = array_merge($st, $status);
        }
    }
    usort($out, fn($a, $b) => $b['balance'] <=> $a['balance']);
    return $out;
}

/**
 * Total fees actually collected in a date range (for the financial overview).
 */
function getFeesCollected(mysqli $conn, string $from, string $to): float {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) as t FROM fee_payments WHERE DATE(created_at) BETWEEN ? AND ?");
    $stmt->bind_param("ss", $from, $to);
    $stmt->execute();
    return (float) $stmt->get_result()->fetch_assoc()['t'];
}
