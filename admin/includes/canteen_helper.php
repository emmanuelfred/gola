<?php
/**
 * canteen_helper.php
 * ─────────────────────────────────────────────────────────────────────────
 * Stock lives in canteen_items. Every transaction is a canteen_sales row
 * with canteen_sale_items lines. A student's running tab is NOT a separate
 * "open tab" object — it's a ledger (canteen_ledger): balance = sum of
 * Charge entries minus sum of Payment entries. This is what makes "keep
 * adding to the tab across visits until they pay" fall out naturally.
 *
 * Credit limit resolves in this order: student override → class default →
 * school-wide default (canteen_settings.default_credit_limit).
 *
 * Requires $conn (mysqli connection) to already be available — include this
 * AFTER config/database.php.
 * ─────────────────────────────────────────────────────────────────────────
 */

/**
 * Is this logged-in admin_user allowed to operate the canteen? True for
 * admin-tier logins (oversight) or anyone whose staff role is literally
 * "Canteen Operator".
 */
function isCanteenOperator(mysqli $conn, int $admin_id): bool {
    $stmt = $conn->prepare("
        SELECT r.role_name FROM staff s
        JOIN staff_roles r ON r.id = s.role_id
        WHERE s.admin_user_id = ? AND s.status = 'Active'
    ");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row && $row['role_name'] === 'Canteen Operator';
}

/**
 * Get active items, optionally filtered by a search term.
 */
function getActiveItems(mysqli $conn, string $search = ''): array {
    if ($search) {
        $stmt = $conn->prepare("SELECT * FROM canteen_items WHERE is_active=1 AND name LIKE ? ORDER BY name ASC");
        $s = "%$search%";
        $stmt->bind_param("s", $s);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    $result = $conn->query("SELECT * FROM canteen_items WHERE is_active=1 ORDER BY name ASC");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getItem(mysqli $conn, int $item_id): ?array {
    $stmt = $conn->prepare("SELECT * FROM canteen_items WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

/**
 * Resolve the credit limit that applies to a given student right now.
 */
function getStudentCreditLimit(mysqli $conn, int $student_id): float {
    $stmt = $conn->prepare("
        SELECT s.canteen_credit_limit as student_limit, c.canteen_credit_limit as class_limit
        FROM students s LEFT JOIN classes c ON c.id = s.class_id
        WHERE s.id = ?
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row && $row['student_limit'] !== null) return (float) $row['student_limit'];
    if ($row && $row['class_limit'] !== null) return (float) $row['class_limit'];

    $default = $conn->query("SELECT setting_value FROM canteen_settings WHERE setting_key='default_credit_limit'")->fetch_assoc();
    return $default ? (float) $default['setting_value'] : 0.0;
}

/**
 * Current running tab balance for a student (what they currently owe).
 */
function getStudentTabBalance(mysqli $conn, int $student_id): float {
    $stmt = $conn->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN entry_type='Charge' THEN amount ELSE 0 END),0) -
            COALESCE(SUM(CASE WHEN entry_type='Payment' THEN amount ELSE 0 END),0) as balance
        FROM canteen_ledger WHERE student_id = ?
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    return (float) $stmt->get_result()->fetch_assoc()['balance'];
}

/**
 * Full ledger history for a student, most recent first — used to print a bill.
 */
function getStudentLedger(mysqli $conn, int $student_id): array {
    $stmt = $conn->prepare("
        SELECT cl.*, cs.receipt_no
        FROM canteen_ledger cl LEFT JOIN canteen_sales cs ON cs.id = cl.sale_id
        WHERE cl.student_id = ? ORDER BY cl.created_at DESC
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Every student who currently has an outstanding tab balance > 0.
 */
function getStudentsWithOpenTabs(mysqli $conn): array {
    $result = $conn->query("
        SELECT s.id, s.student_id, s.first_name, s.last_name, c.class_name, c.arm,
            COALESCE(SUM(CASE WHEN cl.entry_type='Charge' THEN cl.amount ELSE 0 END),0) -
            COALESCE(SUM(CASE WHEN cl.entry_type='Payment' THEN cl.amount ELSE 0 END),0) as balance
        FROM canteen_ledger cl
        JOIN students s ON s.id = cl.student_id
        LEFT JOIN classes c ON c.id = s.class_id
        GROUP BY s.id
        HAVING balance > 0
        ORDER BY balance DESC
    ");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function generateReceiptNo(mysqli $conn): string {
    $year  = date('Y');
    $count = $conn->query("SELECT COUNT(*) as c FROM canteen_sales WHERE receipt_no LIKE 'GOLA/CANTEEN/$year/%'")->fetch_assoc()['c'];
    return 'GOLA/CANTEEN/' . $year . '/' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
}

/**
 * Record a full sale: validates stock, validates the credit limit if this
 * is going on a tab, decrements stock, writes the sale + line items, and
 * (for Tab sales) writes the matching ledger Charge entry.
 *
 * $items = [['item_id'=>int, 'quantity'=>int], ...]
 * Returns ['ok'=>bool, 'error'=>string|null, 'sale_id'=>int|null, 'receipt_no'=>string|null]
 */
function recordSale(mysqli $conn, ?int $student_id, array $items, string $payment_type, float $amount_paid, ?int $sold_by, bool $override_limit = false): array {
    if (empty($items)) return ['ok' => false, 'error' => 'No items in the cart.', 'sale_id' => null, 'receipt_no' => null];
    if ($payment_type === 'Tab' && !$student_id) return ['ok' => false, 'error' => 'A tab sale must be tied to a student.', 'sale_id' => null, 'receipt_no' => null];

    // Validate stock and compute subtotal before touching the database
    $subtotal = 0.0;
    $resolved = [];
    foreach ($items as $line) {
        $item = getItem($conn, intval($line['item_id']));
        $qty  = intval($line['quantity']);
        if (!$item || $qty < 1) return ['ok' => false, 'error' => 'Invalid item in cart.', 'sale_id' => null, 'receipt_no' => null];
        if ($item['quantity_in_stock'] < $qty) {
            return ['ok' => false, 'error' => "Not enough stock for \"{$item['name']}\" — only {$item['quantity_in_stock']} left.", 'sale_id' => null, 'receipt_no' => null];
        }
        $line_total = $item['price'] * $qty;
        $subtotal += $line_total;
        $resolved[] = ['item' => $item, 'qty' => $qty, 'line_total' => $line_total];
    }

    // Credit limit check for tab sales
    if ($payment_type === 'Tab' && !$override_limit) {
        $balance = getStudentTabBalance($conn, $student_id);
        $limit   = getStudentCreditLimit($conn, $student_id);
        if ($balance + $subtotal > $limit) {
            $remaining = max(0, $limit - $balance);
            return [
                'ok' => false,
                'error' => "This would exceed the student's credit limit (₦" . number_format($limit,2) . "). Current balance: ₦" . number_format($balance,2) . ". Remaining allowance: ₦" . number_format($remaining,2) . ".",
                'sale_id' => null, 'receipt_no' => null,
            ];
        }
    }

    $conn->begin_transaction();
    try {
        $receipt_no = generateReceiptNo($conn);
        $stmt = $conn->prepare("INSERT INTO canteen_sales (receipt_no, student_id, subtotal, payment_type, amount_paid, sold_by) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("sidsdi", $receipt_no, $student_id, $subtotal, $payment_type, $amount_paid, $sold_by);
        $stmt->execute();
        $sale_id = $conn->insert_id;

        foreach ($resolved as $line) {
            $stmt = $conn->prepare("INSERT INTO canteen_sale_items (sale_id, item_id, item_name, unit_price, quantity, line_total) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("iisdid", $sale_id, $line['item']['id'], $line['item']['name'], $line['item']['price'], $line['qty'], $line['line_total']);
            $stmt->execute();

            $conn->query("UPDATE canteen_items SET quantity_in_stock = quantity_in_stock - {$line['qty']} WHERE id = {$line['item']['id']}");
        }

        if ($payment_type === 'Tab') {
            $desc = implode(', ', array_map(fn($l) => "{$l['qty']}x {$l['item']['name']}", $resolved));
            $stmt = $conn->prepare("INSERT INTO canteen_ledger (student_id, entry_type, amount, description, sale_id, recorded_by) VALUES (?,'Charge',?,?,?,?)");
            $stmt->bind_param("idsii", $student_id, $subtotal, $desc, $sale_id, $sold_by);
            $stmt->execute();
        }

        $conn->commit();
        return ['ok' => true, 'error' => null, 'sale_id' => $sale_id, 'receipt_no' => $receipt_no];
    } catch (Exception $e) {
        $conn->rollback();
        return ['ok' => false, 'error' => $e->getMessage(), 'sale_id' => null, 'receipt_no' => null];
    }
}

/**
 * Record a payment against a student's tab (full or partial).
 */
function recordTabPayment(mysqli $conn, int $student_id, float $amount, string $payment_method, ?int $recorded_by): array {
    if ($amount <= 0) return ['ok' => false, 'error' => 'Payment amount must be greater than zero.'];
    if (!in_array($payment_method, ['Cash', 'Transfer'], true)) return ['ok' => false, 'error' => 'Invalid payment method.'];

    $stmt = $conn->prepare("INSERT INTO canteen_ledger (student_id, entry_type, amount, description, payment_method, recorded_by) VALUES (?,'Payment',?,?,?,?)");
    $desc = "$payment_method payment";
    $stmt->bind_param("idssi", $student_id, $amount, $desc, $payment_method, $recorded_by);
    if ($stmt->execute()) {
        return ['ok' => true, 'error' => null];
    }
    return ['ok' => false, 'error' => $conn->error];
}

/**
 * Full receipt data for printing — sale header + line items.
 */
function getSaleReceipt(mysqli $conn, int $sale_id): ?array {
    $stmt = $conn->prepare("
        SELECT cs.*, s.first_name, s.last_name, s.student_id as reg_no, au.full_name as sold_by_name
        FROM canteen_sales cs
        LEFT JOIN students s ON s.id = cs.student_id
        LEFT JOIN admin_users au ON au.id = cs.sold_by
        WHERE cs.id = ?
    ");
    $stmt->bind_param("i", $sale_id);
    $stmt->execute();
    $sale = $stmt->get_result()->fetch_assoc();
    if (!$sale) return null;

    $stmt = $conn->prepare("SELECT * FROM canteen_sale_items WHERE sale_id = ?");
    $stmt->bind_param("i", $sale_id);
    $stmt->execute();
    $sale['items'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    return $sale;
}

// ─────────────────────────────────────────────────────────────────────────
// REPORTS
// ─────────────────────────────────────────────────────────────────────────

/**
 * Revenue, cost, profit, and volume summary for a date range (inclusive).
 * Cost/profit are only meaningful for items that have a cost_price set —
 * items_missing_cost tells the caller how many sold items had no cost on
 * record, so the UI can flag that profit may be understated.
 */
function getCanteenSummary(mysqli $conn, string $from, string $to): array {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as transaction_count, COALESCE(SUM(subtotal),0) as revenue
        FROM canteen_sales WHERE DATE(created_at) BETWEEN ? AND ?
    ");
    $stmt->bind_param("ss", $from, $to);
    $stmt->execute();
    $base = $stmt->get_result()->fetch_assoc();

    $stmt = $conn->prepare("
        SELECT
            COALESCE(SUM(csi.quantity),0) as items_sold,
            COALESCE(SUM(csi.quantity * ci.cost_price),0) as cost,
            COALESCE(SUM(CASE WHEN ci.cost_price IS NULL THEN csi.quantity ELSE 0 END),0) as items_missing_cost
        FROM canteen_sale_items csi
        JOIN canteen_sales cs ON cs.id = csi.sale_id
        LEFT JOIN canteen_items ci ON ci.id = csi.item_id
        WHERE DATE(cs.created_at) BETWEEN ? AND ?
    ");
    $stmt->bind_param("ss", $from, $to);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_assoc();

    return [
        'revenue'             => (float) $base['revenue'],
        'transaction_count'   => (int) $base['transaction_count'],
        'items_sold'          => (int) $items['items_sold'],
        'cost'                => (float) $items['cost'],
        'profit'              => (float) $base['revenue'] - (float) $items['cost'],
        'items_missing_cost'  => (int) $items['items_missing_cost'],
    ];
}

/**
 * Revenue split by how it was collected (Cash / Transfer / Tab) for a range.
 */
function getPaymentBreakdown(mysqli $conn, string $from, string $to): array {
    $stmt = $conn->prepare("
        SELECT payment_type, COUNT(*) as count, COALESCE(SUM(subtotal),0) as total
        FROM canteen_sales WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY payment_type
    ");
    $stmt->bind_param("ss", $from, $to);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $out = ['Cash' => 0.0, 'Transfer' => 0.0, 'Tab' => 0.0];
    foreach ($rows as $r) $out[$r['payment_type']] = (float) $r['total'];
    return $out;
}

/**
 * Top-selling items by quantity, for a date range.
 */
function getBestSellers(mysqli $conn, string $from, string $to, int $limit = 10): array {
    $stmt = $conn->prepare("
        SELECT csi.item_name, SUM(csi.quantity) as qty_sold, SUM(csi.line_total) as revenue
        FROM canteen_sale_items csi JOIN canteen_sales cs ON cs.id = csi.sale_id
        WHERE DATE(cs.created_at) BETWEEN ? AND ?
        GROUP BY csi.item_name ORDER BY qty_sold DESC LIMIT ?
    ");
    $stmt->bind_param("ssi", $from, $to, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Daily revenue for a date range — for the trend chart. Every day in the
 * range is present (zero-filled), not just days with sales, so the chart
 * doesn't misleadingly skip slow days.
 */
function getDailyRevenue(mysqli $conn, string $from, string $to): array {
    $stmt = $conn->prepare("
        SELECT DATE(created_at) as d, SUM(subtotal) as total
        FROM canteen_sales WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
    ");
    $stmt->bind_param("ss", $from, $to);
    $stmt->execute();
    $result = $stmt->get_result();
    $byDate = [];
    while ($r = $result->fetch_assoc()) $byDate[$r['d']] = (float) $r['total'];

    $out = [];
    $cursor = strtotime($from);
    $end = strtotime($to);
    while ($cursor <= $end) {
        $d = date('Y-m-d', $cursor);
        $out[] = ['date' => $d, 'total' => $byDate[$d] ?? 0.0];
        $cursor = strtotime('+1 day', $cursor);
    }
    return $out;
}

/**
 * Total money currently owed to the canteen across every student tab right
 * now — not date-range scoped, this is a live snapshot.
 */
function getTotalOutstandingTabs(mysqli $conn): float {
    $result = $conn->query("
        SELECT COALESCE(SUM(CASE WHEN entry_type='Charge' THEN amount ELSE -amount END),0) as total
        FROM canteen_ledger
    ");
    return (float) $result->fetch_assoc()['total'];
}

/**
 * Items that are out of stock or below their low-stock threshold right now.
 */
function getStockAlerts(mysqli $conn): array {
    $items = getActiveItems($conn);
    return [
        'out_of_stock' => array_values(array_filter($items, fn($i) => $i['quantity_in_stock'] == 0)),
        'low_stock'    => array_values(array_filter($items, fn($i) => $i['quantity_in_stock'] > 0 && $i['quantity_in_stock'] <= $i['low_stock_threshold'])),
    ];
}
