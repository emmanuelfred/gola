<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('canteen');
require_once 'includes/canteen_helper.php';

if (!(isCanteenOperator($conn, $admin_id) || hasPermission('admin'))) {
    die('Access restricted to Canteen Operators.');
}

$sale_id = intval($_GET['sale_id'] ?? 0);
$sale = getSaleReceipt($conn, $sale_id);
if (!$sale) { die('Receipt not found.'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Receipt <?php echo htmlspecialchars($sale['receipt_no']); ?></title>
<style>
    * { box-sizing: border-box; }
    body { font-family: 'Courier New', monospace; max-width: 320px; margin: 20px auto; color: #1a1a1a; font-size: 13px; }
    .center { text-align: center; }
    .school-name { font-weight: bold; font-size: 16px; }
    hr { border: none; border-top: 1px dashed #999; margin: 10px 0; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 2px 0; vertical-align: top; }
    .right { text-align: right; }
    .total-row td { font-weight: bold; font-size: 14px; padding-top: 6px; }
    .no-print { margin-top: 20px; text-align: center; }
    .no-print button { padding: 10px 24px; background: #0A2E4D; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; }
    @media print { .no-print { display: none; } body { margin: 0; } }
</style>
</head>
<body>
    <div class="center">
        <p class="school-name">G.O.L.A</p>
        <p>Goodness Omogo Leadership Academy</p>
        <p>Canteen Receipt</p>
    </div>
    <hr>
    <table>
        <tr><td>Receipt No:</td><td class="right"><?php echo htmlspecialchars($sale['receipt_no']); ?></td></tr>
        <tr><td>Date:</td><td class="right"><?php echo date('d M Y, g:i a', strtotime($sale['created_at'])); ?></td></tr>
        <?php if ($sale['first_name']): ?>
        <tr><td>Student:</td><td class="right"><?php echo htmlspecialchars($sale['first_name'].' '.$sale['last_name']); ?></td></tr>
        <tr><td>Reg No:</td><td class="right"><?php echo htmlspecialchars($sale['reg_no']); ?></td></tr>
        <?php endif; ?>
        <tr><td>Served by:</td><td class="right"><?php echo htmlspecialchars($sale['sold_by_name'] ?: '—'); ?></td></tr>
    </table>
    <hr>
    <table>
        <?php foreach ($sale['items'] as $item): ?>
        <tr>
            <td><?php echo htmlspecialchars($item['item_name']); ?><br><span style="color:#777">&#8358;<?php echo number_format($item['unit_price'],2); ?> × <?php echo $item['quantity']; ?></span></td>
            <td class="right">&#8358;<?php echo number_format($item['line_total'],2); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <hr>
    <table>
        <tr class="total-row"><td>Total</td><td class="right">&#8358;<?php echo number_format($sale['subtotal'],2); ?></td></tr>
        <tr><td>Payment</td><td class="right"><?php echo htmlspecialchars($sale['payment_type']); ?></td></tr>
        <?php if ($sale['payment_type'] !== 'Tab'): ?>
        <tr><td>Amount Paid</td><td class="right">&#8358;<?php echo number_format($sale['amount_paid'],2); ?></td></tr>
        <?php else: ?>
        <tr><td colspan="2" style="padding-top:6px; font-style:italic; color:#777;">Added to student tab — not paid now.</td></tr>
        <?php endif; ?>
    </table>
    <hr>
    <p class="center" style="color:#777;">Thank you!</p>

    <div class="no-print">
        <button onclick="window.print()">Print Receipt</button>
    </div>
</body>
</html>
