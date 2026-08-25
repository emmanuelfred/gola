<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('payroll');
require_once 'includes/payroll_helper.php';
$page_title = "Payroll Run";

$run_id = intval($_GET['id'] ?? 0);
$run = getPayrollRun($conn, $run_id);
if (!$run) { header('Location: manage_payroll.php'); exit; }

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'update_item') {
        $item_id   = intval($_POST['item_id'] ?? 0);
        $housing   = floatval($_POST['housing_allowance'] ?? 0);
        $transport = floatval($_POST['transport_allowance'] ?? 0);
        $other_a   = floatval($_POST['other_allowance'] ?? 0);
        $tax       = floatval($_POST['tax_deduction'] ?? 0);
        $pension   = floatval($_POST['pension_deduction'] ?? 0);
        $other_d   = floatval($_POST['other_deduction'] ?? 0);
        updatePayrollItem($conn, $item_id, $housing, $transport, $other_a, $tax, $pension, $other_d);
        $success = "Payslip updated.";
    }

    if ($_POST['action'] === 'mark_paid') {
        $item_id = intval($_POST['item_id'] ?? 0);
        $method  = $_POST['payment_method'] ?? 'Cash';
        $date    = $_POST['paid_date'] ?? date('Y-m-d');
        markPayrollItemPaid($conn, $item_id, $method, $date);
        logActivity('payroll_paid', "Marked payroll item $item_id as paid via $method");
        $success = "Marked as paid.";
    }

    if ($_POST['action'] === 'mark_all_paid' && hasPermission('admin')) {
        $method = $_POST['bulk_payment_method'] ?? 'Transfer';
        $date   = $_POST['bulk_paid_date'] ?? date('Y-m-d');
        $items = getPayrollItems($conn, $run_id);
        foreach ($items as $it) {
            if ($it['payment_status'] !== 'Paid') markPayrollItemPaid($conn, $it['id'], $method, $date);
        }
        logActivity('payroll_bulk_paid', "Marked all items in run $run_id as paid via $method");
        $success = "All unpaid staff marked as paid.";
    }

    if ($_POST['action'] === 'finalize' && hasPermission('admin')) {
        $conn->query("UPDATE payroll_runs SET status='Finalized' WHERE id=$run_id");
        logActivity('finalize_payroll', "Finalized payroll run $run_id");
        $success = "Payroll run finalized.";
    }
}

$items = getPayrollItems($conn, $run_id);
$summary = getPayrollRunSummary($conn, $run_id);
$run = getPayrollRun($conn, $run_id); // refresh status after possible finalize
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $page_title; ?> | G.O.L.A Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<script>tailwind.config={theme:{extend:{colors:{primary:"#0A2E4D",gold:"#C5A059"},fontFamily:{sans:["Inter","sans-serif"]}}}};</script>
<style>.sidebar-link.active{background:linear-gradient(90deg,rgba(197,160,89,.1) 0%,transparent 100%);border-left:3px solid #C5A059;color:#C5A059;}</style>
</head>
<body class="bg-slate-50 font-sans">
<div class="flex h-screen overflow-hidden">
<?php include 'admin_sidebar.php'; ?>
<div class="flex-1 flex flex-col overflow-hidden">
<?php include 'admin_topbar.php'; ?>
<main class="flex-1 overflow-y-auto p-6 lg:p-8">

<div class="flex items-center gap-4 mb-6">
    <a href="manage_payroll.php" class="p-2 hover:bg-slate-100 rounded-lg"><span class="material-symbols-outlined">arrow_back</span></a>
    <div class="flex-1">
        <h1 class="text-2xl font-bold text-slate-900"><?php echo htmlspecialchars($run['label']); ?></h1>
        <p class="text-slate-500 text-sm">
            <span class="px-2 py-0.5 <?php echo $run['status']=='Finalized' ? 'bg-primary/10 text-primary' : 'bg-amber-100 text-amber-700'; ?> text-xs font-semibold rounded-full"><?php echo $run['status']; ?></span>
        </p>
    </div>
    <?php if ($run['status']==='Draft' && hasPermission('admin')): ?>
    <form method="POST" onsubmit="return confirm('Finalize this run? Basic salary figures will be locked in.')">
        <input type="hidden" name="action" value="finalize">
        <button type="submit" class="px-4 py-2.5 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary/90">Finalize Run</button>
    </form>
    <?php endif; ?>
</div>

<?php if ($success): ?>
<div class="mb-5 p-4 bg-green-50 border border-green-200 rounded-xl flex gap-3 items-start">
    <span class="material-symbols-outlined text-green-600 flex-shrink-0">check_circle</span>
    <p class="text-green-800 text-sm"><?php echo htmlspecialchars($success); ?></p>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl flex gap-3 items-start">
    <span class="material-symbols-outlined text-red-600 flex-shrink-0">error</span>
    <p class="text-red-800 text-sm"><?php echo htmlspecialchars($error); ?></p>
</div>
<?php endif; ?>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs font-semibold text-slate-500 uppercase">Staff</p><p class="text-2xl font-bold text-slate-900 mt-1"><?php echo $summary['staff_count']; ?></p></div>
    <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs font-semibold text-slate-500 uppercase">Gross</p><p class="text-2xl font-bold text-slate-700 mt-1">&#8358;<?php echo number_format($summary['total_gross'],2); ?></p></div>
    <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs font-semibold text-slate-500 uppercase">Deductions</p><p class="text-2xl font-bold text-amber-600 mt-1">&#8358;<?php echo number_format($summary['total_deductions'],2); ?></p></div>
    <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs font-semibold text-slate-500 uppercase">Net Payout</p><p class="text-2xl font-bold text-primary mt-1">&#8358;<?php echo number_format($summary['total_net'],2); ?></p></div>
</div>

<?php if ($summary['paid_count'] < $summary['staff_count'] && hasPermission('admin')): ?>
<div class="bg-white rounded-xl border border-slate-200 p-4 mb-6 flex flex-wrap gap-3 items-end">
    <form method="POST" class="flex flex-wrap gap-3 items-end" onsubmit="return confirm('Mark ALL unpaid staff in this run as paid?')">
        <input type="hidden" name="action" value="mark_all_paid">
        <div><label class="text-xs font-semibold text-slate-500 mb-1 block">Method</label>
            <select name="bulk_payment_method" class="border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"><option>Transfer</option><option>Cash</option></select></div>
        <div><label class="text-xs font-semibold text-slate-500 mb-1 block">Date</label>
            <input type="date" name="bulk_paid_date" value="<?php echo date('Y-m-d'); ?>" class="border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
        <button type="submit" class="px-5 py-2.5 bg-gold text-primary text-sm font-bold rounded-lg hover:bg-gold/90">Mark All Unpaid as Paid</button>
    </form>
</div>
<?php endif; ?>

<div class="space-y-3">
    <?php foreach ($items as $it): ?>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <p class="font-bold text-slate-800"><?php echo htmlspecialchars($it['first_name'].' '.$it['last_name']); ?></p>
                <p class="text-xs text-slate-400"><?php echo htmlspecialchars($it['role_name']); ?> · <?php echo htmlspecialchars($it['staff_reg_no']); ?></p>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right">
                    <p class="text-xs text-slate-400">Net Pay</p>
                    <p class="font-bold text-lg text-primary">&#8358;<?php echo number_format($it['net_pay'],2); ?></p>
                </div>
                <?php if ($it['payment_status'] === 'Paid'): ?>
                <span class="px-3 py-1.5 bg-green-100 text-green-700 text-xs font-semibold rounded-full">Paid <?php echo $it['paid_date'] ? 'on '.date('d M', strtotime($it['paid_date'])) : ''; ?></span>
                <a href="payslip.php?item_id=<?php echo $it['id']; ?>" target="_blank" class="text-primary hover:underline text-xs font-semibold">Payslip</a>
                <?php else: ?>
                <form method="POST" class="flex items-center gap-1.5">
                    <input type="hidden" name="action" value="mark_paid">
                    <input type="hidden" name="item_id" value="<?php echo $it['id']; ?>">
                    <select name="payment_method" class="text-xs border-slate-200 rounded-lg focus:ring-gold focus:border-gold py-1.5"><option>Cash</option><option>Transfer</option></select>
                    <input type="date" name="paid_date" value="<?php echo date('Y-m-d'); ?>" class="text-xs border-slate-200 rounded-lg focus:ring-gold focus:border-gold py-1.5">
                    <button type="submit" class="px-3 py-1.5 bg-primary text-white text-xs font-semibold rounded-lg hover:bg-primary/90">Mark Paid</button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <form method="POST" class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <input type="hidden" name="action" value="update_item">
            <input type="hidden" name="item_id" value="<?php echo $it['id']; ?>">
            <div><label class="text-xs text-slate-500 mb-1 block">Basic</label>
                <div class="px-2 py-1.5 bg-slate-50 rounded-lg text-sm text-slate-500">&#8358;<?php echo number_format($it['basic_salary'],2); ?></div></div>
            <div><label class="text-xs text-slate-500 mb-1 block">Housing Allowance</label>
                <input type="number" step="0.01" min="0" name="housing_allowance" value="<?php echo number_format($it['housing_allowance'],2,'.',''); ?>" class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
            <div><label class="text-xs text-slate-500 mb-1 block">Transport Allowance</label>
                <input type="number" step="0.01" min="0" name="transport_allowance" value="<?php echo number_format($it['transport_allowance'],2,'.',''); ?>" class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
            <div><label class="text-xs text-slate-500 mb-1 block">Other Allowance</label>
                <input type="number" step="0.01" min="0" name="other_allowance" value="<?php echo number_format($it['other_allowance'],2,'.',''); ?>" class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
            <div><label class="text-xs text-slate-500 mb-1 block">Tax (PAYE)</label>
                <input type="number" step="0.01" min="0" name="tax_deduction" value="<?php echo number_format($it['tax_deduction'],2,'.',''); ?>" class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
            <div><label class="text-xs text-slate-500 mb-1 block">Pension</label>
                <input type="number" step="0.01" min="0" name="pension_deduction" value="<?php echo number_format($it['pension_deduction'],2,'.',''); ?>" class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
            <div><label class="text-xs text-slate-500 mb-1 block">Other Deduction</label>
                <input type="number" step="0.01" min="0" name="other_deduction" value="<?php echo number_format($it['other_deduction'],2,'.',''); ?>" class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
            <div class="flex items-end"><button type="submit" class="w-full px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg">Update</button></div>
        </form>
    </div>
    <?php endforeach; ?>
    <?php if (empty($items)): ?>
    <div class="bg-white rounded-xl border border-slate-200 p-12 text-center text-slate-400">No staff in this run.</div>
    <?php endif; ?>
</div>

</main>
</div>
</div>
</body>
</html>
