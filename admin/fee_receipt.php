<?php
require_once 'auth_check.php';

require_once 'includes/permission_helper.php';
requirePermission('fees');
$payment_id = intval($_GET['payment_id'] ?? 0);
$stmt = $conn->prepare("
    SELECT fp.*, s.first_name, s.last_name, s.student_id as reg_no, c.class_name, c.arm,
           sess.session_name, t.term_name, au.full_name as recorded_by_name
    FROM fee_payments fp
    JOIN students s ON s.id = fp.student_id
    LEFT JOIN classes c ON c.id = s.class_id
    JOIN academic_sessions sess ON sess.id = fp.session_id
    JOIN terms t ON t.id = fp.term_id
    LEFT JOIN admin_users au ON au.id = fp.recorded_by
    WHERE fp.id = ?
");
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
if (!$p) { die('Receipt not found.'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Receipt <?php echo htmlspecialchars($p['receipt_no']); ?></title>
<style>
    * { box-sizing: border-box; }
    body { font-family: 'Courier New', monospace; max-width: 340px; margin: 20px auto; color: #1a1a1a; font-size: 13px; }
    .center { text-align: center; }
    .school-name { font-weight: bold; font-size: 16px; }
    hr { border: none; border-top: 1px dashed #999; margin: 10px 0; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 3px 0; vertical-align: top; }
    .right { text-align: right; }
    .total-row td { font-weight: bold; font-size: 15px; padding-top: 8px; border-top: 2px solid #1a1a1a; }
    .no-print { margin-top: 20px; text-align: center; }
    .no-print button { padding: 10px 24px; background: #0A2E4D; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; }
    @media print { .no-print { display: none; } body { margin: 0; } }
</style>
</head>
<body>
    <div class="center">
        <p class="school-name">G.O.L.A</p>
        <p>Goodness Omogo Leadership Academy</p>
        <p>School Fees Receipt</p>
    </div>
    <hr>
    <table>
        <tr><td>Receipt No:</td><td class="right"><?php echo htmlspecialchars($p['receipt_no']); ?></td></tr>
        <tr><td>Date:</td><td class="right"><?php echo date('d M Y, g:i a', strtotime($p['created_at'])); ?></td></tr>
        <tr><td>Student:</td><td class="right"><?php echo htmlspecialchars($p['first_name'].' '.$p['last_name']); ?></td></tr>
        <tr><td>Reg No:</td><td class="right"><?php echo htmlspecialchars($p['reg_no']); ?></td></tr>
        <tr><td>Class:</td><td class="right"><?php echo htmlspecialchars(($p['class_name']??'').' '.($p['arm']??'')); ?></td></tr>
        <tr><td>Session/Term:</td><td class="right"><?php echo htmlspecialchars($p['session_name']); ?>, <?php echo htmlspecialchars($p['term_name']); ?></td></tr>
        <tr><td>Received by:</td><td class="right"><?php echo htmlspecialchars($p['recorded_by_name'] ?: '—'); ?></td></tr>
    </table>
    <hr>
    <table>
        <tr class="total-row"><td>Amount Paid</td><td class="right">&#8358;<?php echo number_format($p['amount'],2); ?></td></tr>
        <tr><td>Method</td><td class="right"><?php echo htmlspecialchars($p['payment_method']); ?></td></tr>
        <?php if ($p['notes']): ?>
        <tr><td colspan="2" style="padding-top:6px; color:#777;">Note: <?php echo htmlspecialchars($p['notes']); ?></td></tr>
        <?php endif; ?>
    </table>
    <hr>
    <p class="center" style="color:#777;">Thank you!</p>

    <div class="no-print">
        <button onclick="window.print()">Print Receipt</button>
    </div>
</body>
</html>
