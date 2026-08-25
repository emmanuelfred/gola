<?php
/**
 * payroll_helper.php
 * ─────────────────────────────────────────────────────────────────────────
 * A payroll run is created for a pay period (e.g. "2026-08"). Creating it
 * snapshots every Active staff member's CURRENT salary into a payroll_items
 * row — editing staff.salary afterward never rewrites past payroll history.
 * gross_pay/net_pay are recalculated and stored whenever a line item's
 * allowances/deductions are edited, so a payslip is always self-contained.
 *
 * Requires $conn (mysqli connection) to already be available — include this
 * AFTER config/database.php.
 * ─────────────────────────────────────────────────────────────────────────
 */

function getAllPayrollRuns(mysqli $conn): array {
    $result = $conn->query("SELECT * FROM payroll_runs ORDER BY pay_period DESC");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getPayrollRun(mysqli $conn, int $run_id): ?array {
    $stmt = $conn->prepare("SELECT * FROM payroll_runs WHERE id = ?");
    $stmt->bind_param("i", $run_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

/**
 * Create a new payroll run for a pay period and auto-populate one line item
 * per Active staff member, using their current salary as the basic pay.
 * Returns ['ok'=>bool, 'error'=>string|null, 'run_id'=>int|null].
 */
function createPayrollRun(mysqli $conn, string $pay_period, string $label, ?int $created_by): array {
    $existing = $conn->query("SELECT id FROM payroll_runs WHERE pay_period='" . $conn->real_escape_string($pay_period) . "'")->fetch_assoc();
    if ($existing) return ['ok' => false, 'error' => 'A payroll run for this period already exists.', 'run_id' => null];

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO payroll_runs (pay_period, label, created_by) VALUES (?,?,?)");
        $stmt->bind_param("ssi", $pay_period, $label, $created_by);
        $stmt->execute();
        $run_id = $conn->insert_id;

        $staff = $conn->query("SELECT id, salary FROM staff WHERE status='Active'")->fetch_all(MYSQLI_ASSOC);
        foreach ($staff as $s) {
            $basic = (float) $s['salary'];
            $stmt = $conn->prepare("INSERT INTO payroll_items (payroll_run_id, staff_id, basic_salary, gross_pay, net_pay) VALUES (?,?,?,?,?)");
            $stmt->bind_param("iiddd", $run_id, $s['id'], $basic, $basic, $basic);
            $stmt->execute();
        }

        $conn->commit();
        return ['ok' => true, 'error' => null, 'run_id' => $run_id];
    } catch (Exception $e) {
        $conn->rollback();
        return ['ok' => false, 'error' => $e->getMessage(), 'run_id' => null];
    }
}

/**
 * All payslip line items for a run, with staff names/roles joined in.
 */
function getPayrollItems(mysqli $conn, int $run_id): array {
    $stmt = $conn->prepare("
        SELECT pi.*, st.first_name, st.last_name, st.staff_id as staff_reg_no, r.role_name
        FROM payroll_items pi
        JOIN staff st ON st.id = pi.staff_id
        JOIN staff_roles r ON r.id = st.role_id
        WHERE pi.payroll_run_id = ?
        ORDER BY st.first_name, st.last_name
    ");
    $stmt->bind_param("i", $run_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getPayrollItem(mysqli $conn, int $item_id): ?array {
    $stmt = $conn->prepare("
        SELECT pi.*, st.first_name, st.last_name, st.staff_id as staff_reg_no, r.role_name,
               pr.label as run_label, pr.pay_period
        FROM payroll_items pi
        JOIN staff st ON st.id = pi.staff_id
        JOIN staff_roles r ON r.id = st.role_id
        JOIN payroll_runs pr ON pr.id = pi.payroll_run_id
        WHERE pi.id = ?
    ");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

/**
 * Update one payslip's allowances/deductions and recalculate gross/net.
 */
function updatePayrollItem(mysqli $conn, int $item_id, float $housing, float $transport, float $other_allow, float $tax, float $pension, float $other_ded): bool {
    $item = $conn->query("SELECT basic_salary FROM payroll_items WHERE id=" . intval($item_id))->fetch_assoc();
    if (!$item) return false;

    $gross = (float) $item['basic_salary'] + $housing + $transport + $other_allow;
    $net   = $gross - $tax - $pension - $other_ded;

    $stmt = $conn->prepare("
        UPDATE payroll_items SET
            housing_allowance=?, transport_allowance=?, other_allowance=?,
            tax_deduction=?, pension_deduction=?, other_deduction=?,
            gross_pay=?, net_pay=?
        WHERE id=?
    ");
    $stmt->bind_param("ddddddddi", $housing, $transport, $other_allow, $tax, $pension, $other_ded, $gross, $net, $item_id);
    return $stmt->execute();
}

/**
 * Mark one payslip as paid.
 */
function markPayrollItemPaid(mysqli $conn, int $item_id, string $method, string $paid_date): bool {
    $stmt = $conn->prepare("UPDATE payroll_items SET payment_status='Paid', payment_method=?, paid_date=? WHERE id=?");
    $stmt->bind_param("ssi", $method, $paid_date, $item_id);
    return $stmt->execute();
}

/**
 * Totals for a run — used on the run overview and the financial summary.
 */
function getPayrollRunSummary(mysqli $conn, int $run_id): array {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as staff_count,
               COALESCE(SUM(gross_pay),0) as total_gross,
               COALESCE(SUM(net_pay),0) as total_net,
               COALESCE(SUM(tax_deduction+pension_deduction+other_deduction),0) as total_deductions,
               COALESCE(SUM(CASE WHEN payment_status='Paid' THEN net_pay ELSE 0 END),0) as total_paid,
               SUM(CASE WHEN payment_status='Paid' THEN 1 ELSE 0 END) as paid_count
        FROM payroll_items WHERE payroll_run_id = ?
    ");
    $stmt->bind_param("i", $run_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Total net payroll actually paid out in a date range (for the financial overview).
 */
function getPayrollPaidTotal(mysqli $conn, string $from, string $to): float {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(net_pay),0) as t FROM payroll_items WHERE payment_status='Paid' AND paid_date BETWEEN ? AND ?");
    $stmt->bind_param("ss", $from, $to);
    $stmt->execute();
    return (float) $stmt->get_result()->fetch_assoc()['t'];
}
