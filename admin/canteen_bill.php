<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('canteen');
require_once 'includes/canteen_helper.php';

if (!(isCanteenOperator($conn, $admin_id) || hasPermission('admin'))) {
    die('Access restricted to Canteen Operators.');
}

$student_id = intval($_GET['student_id'] ?? 0);
$student = $conn->query("SELECT s.*, c.class_name, c.arm FROM students s LEFT JOIN classes c ON c.id=s.class_id WHERE s.id=$student_id")->fetch_assoc();
if (!$student) { die('Student not found.'); }

$ledger  = getStudentLedger($conn, $student_id);
$balance = getStudentTabBalance($conn, $student_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Canteen Bill — <?php echo htmlspecialchars($student['first_name'].' '.$student['last_name']); ?></title>
<style>
    * { box-sizing: border-box; }
    body { font-family: 'Courier New', monospace; max-width: 380px; margin: 20px auto; color: #1a1a1a; font-size: 13px; }
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
        <p>Canteen Tab Statement</p>
    </div>
    <hr>
    <table>
        <tr><td>Student:</td><td class="right"><?php echo htmlspecialchars($student['first_name'].' '.$student['last_name']); ?></td></tr>
        <tr><td>Reg No:</td><td class="right"><?php echo htmlspecialchars($student['student_id']); ?></td></tr>
        <tr><td>Class:</td><td class="right"><?php echo htmlspecialchars(($student['class_name']??'').' '.($student['arm']??'')); ?></td></tr>
        <tr><td>Statement Date:</td><td class="right"><?php echo date('d M Y, g:i a'); ?></td></tr>
    </table>
    <hr>
    <table>
        <?php foreach ($ledger as $entry): ?>
        <tr>
            <td>
                <?php echo date('d M', strtotime($entry['created_at'])); ?> —
                <?php echo htmlspecialchars($entry['description'] ?: ($entry['entry_type']=='Payment' ? $entry['payment_method'].' payment' : 'Charge')); ?>
            </td>
            <td class="right"><?php echo $entry['entry_type']=='Charge' ? '+' : '−'; ?>&#8358;<?php echo number_format($entry['amount'],2); ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($ledger)): ?>
        <tr><td colspan="2" class="center" style="color:#777; padding:12px 0;">No transactions on record.</td></tr>
        <?php endif; ?>
    </table>
    <table>
        <tr class="total-row"><td>Balance Due</td><td class="right">&#8358;<?php echo number_format($balance,2); ?></td></tr>
    </table>
    <hr>
    <p class="center" style="color:#777;">Please settle at the canteen.</p>

    <div class="no-print">
        <button onclick="window.print()">Print Bill</button>
    </div>
</body>
</html>
