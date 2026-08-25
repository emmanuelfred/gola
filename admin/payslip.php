<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('payroll');
require_once 'includes/payroll_helper.php';

$item_id = intval($_GET['item_id'] ?? 0);
$p = getPayrollItem($conn, $item_id);
if (!$p) { die('Payslip not found.'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payslip — <?php echo htmlspecialchars($p['first_name'].' '.$p['last_name']); ?> — <?php echo htmlspecialchars($p['pay_period']); ?></title>
<style>
    * { box-sizing: border-box; }
    body { font-family: Arial, sans-serif; max-width: 480px; margin: 20px auto; color: #1a1a1a; font-size: 13px; }
    .center { text-align: center; }
    .school-name { font-weight: bold; font-size: 18px; color: #0A2E4D; }
    hr { border: none; border-top: 1px solid #ddd; margin: 14px 0; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 4px 0; vertical-align: top; }
    .right { text-align: right; }
    .section-title { font-weight: bold; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #C5A059; margin-bottom: 6px; }
    .total-row td { font-weight: bold; font-size: 16px; padding-top: 10px; border-top: 2px solid #0A2E4D; color: #0A2E4D; }
    .no-print { margin-top: 24px; text-align: center; }
    .no-print button { padding: 10px 24px; background: #0A2E4D; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; }
    @media print { .no-print { display: none; } body { margin: 0; } }
</style>
</head>
<body>
    <div class="center">
        <p class="school-name">G.O.L.A</p>
        <p>Goodness Omogo Leadership Academy</p>
        <p>Payslip — <?php echo htmlspecialchars($p['run_label']); ?></p>
    </div>
    <hr>
    <table>
        <tr><td>Staff Name:</td><td class="right"><?php echo htmlspecialchars($p['first_name'].' '.$p['last_name']); ?></td></tr>
        <tr><td>Staff ID:</td><td class="right"><?php echo htmlspecialchars($p['staff_reg_no']); ?></td></tr>
        <tr><td>Role:</td><td class="right"><?php echo htmlspecialchars($p['role_name']); ?></td></tr>
        <tr><td>Pay Period:</td><td class="right"><?php echo htmlspecialchars($p['run_label']); ?></td></tr>
        <tr><td>Status:</td><td class="right"><?php echo $p['payment_status']; ?><?php echo $p['paid_date'] ? ' on '.date('d M Y', strtotime($p['paid_date'])) : ''; ?></td></tr>
    </table>
    <hr>
    <p class="section-title">Earnings</p>
    <table>
        <tr><td>Basic Salary</td><td class="right">&#8358;<?php echo number_format($p['basic_salary'],2); ?></td></tr>
        <tr><td>Housing Allowance</td><td class="right">&#8358;<?php echo number_format($p['housing_allowance'],2); ?></td></tr>
        <tr><td>Transport Allowance</td><td class="right">&#8358;<?php echo number_format($p['transport_allowance'],2); ?></td></tr>
        <tr><td>Other Allowance</td><td class="right">&#8358;<?php echo number_format($p['other_allowance'],2); ?></td></tr>
        <tr><td><strong>Gross Pay</strong></td><td class="right"><strong>&#8358;<?php echo number_format($p['gross_pay'],2); ?></strong></td></tr>
    </table>
    <hr>
    <p class="section-title">Deductions</p>
    <table>
        <tr><td>Tax (PAYE)</td><td class="right">&#8358;<?php echo number_format($p['tax_deduction'],2); ?></td></tr>
        <tr><td>Pension</td><td class="right">&#8358;<?php echo number_format($p['pension_deduction'],2); ?></td></tr>
        <tr><td>Other</td><td class="right">&#8358;<?php echo number_format($p['other_deduction'],2); ?></td></tr>
    </table>
    <table>
        <tr class="total-row"><td>Net Pay</td><td class="right">&#8358;<?php echo number_format($p['net_pay'],2); ?></td></tr>
    </table>
    <?php if ($p['payment_method']): ?>
    <p style="margin-top:10px; color:#777;">Paid via <?php echo htmlspecialchars($p['payment_method']); ?></p>
    <?php endif; ?>

    <div class="no-print">
        <button onclick="window.print()">Print Payslip</button>
    </div>
</body>
</html>
