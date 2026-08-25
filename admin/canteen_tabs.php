<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('canteen');
require_once 'includes/canteen_helper.php';
$page_title = "Student Tabs";

$authorized = isCanteenOperator($conn, $admin_id) || hasPermission('admin');
$success = '';
$error   = '';

if ($authorized && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'pay') {
    $student_id = intval($_POST['student_id'] ?? 0);
    $amount     = floatval($_POST['amount'] ?? 0);
    $method     = $_POST['payment_method'] ?? '';

    $balance = getStudentTabBalance($conn, $student_id);
    if ($amount > $balance + 0.01) {
        $error = "Payment (₦" . number_format($amount,2) . ") is more than the outstanding balance (₦" . number_format($balance,2) . ").";
    } else {
        $result = recordTabPayment($conn, $student_id, $amount, $method, $admin_id);
        if ($result['ok']) {
            logActivity('canteen_payment', "Recorded $method payment of $amount for student ID $student_id");
            $success = "Payment of ₦" . number_format($amount,2) . " recorded.";
        } else {
            $error = $result['error'];
        }
    }
}

$open_tabs = $authorized ? getStudentsWithOpenTabs($conn) : [];
$view_student_id = intval($_GET['student_id'] ?? 0);
$view_ledger = [];
$view_student = null;
if ($view_student_id) {
    $view_student = $conn->query("SELECT s.*, c.class_name, c.arm FROM students s LEFT JOIN classes c ON c.id=s.class_id WHERE s.id=$view_student_id")->fetch_assoc();
    $view_ledger  = getStudentLedger($conn, $view_student_id);
}
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

<?php if (!$authorized): ?>
<div class="bg-white rounded-2xl border border-slate-200 p-16 text-center max-w-lg mx-auto mt-10">
    <span class="material-symbols-outlined text-6xl text-slate-200 block mb-3">lock</span>
    <h3 class="font-bold text-slate-800 mb-2">Canteen Operator access required</h3>
    <p class="text-sm text-slate-500">This area is restricted to staff with the Canteen Operator role.</p>
</div>
<?php else: ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Student Tabs</h1>
    <p class="text-slate-500 text-sm mt-1">
        <a href="canteen_pos.php" class="text-primary font-semibold hover:underline">Sell</a> ·
        <a href="manage_canteen_items.php" class="text-primary font-semibold hover:underline">Manage Items</a>
    </p>
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

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Left: list of open tabs -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
                <h3 class="font-bold text-slate-800 text-sm">Open Tabs (<?php echo count($open_tabs); ?>)</h3>
            </div>
            <div class="divide-y divide-slate-100 max-h-[65vh] overflow-y-auto">
                <?php if (empty($open_tabs)): ?>
                <p class="px-4 py-8 text-center text-sm text-slate-400">No outstanding tabs.</p>
                <?php endif; ?>
                <?php foreach ($open_tabs as $t): ?>
                <a href="?student_id=<?php echo $t['id']; ?>"
                   class="flex items-center justify-between px-4 py-3 hover:bg-gold/5 transition-colors <?php echo $view_student_id===$t['id'] ? 'bg-gold/10 border-l-4 border-gold' : ''; ?>">
                    <div>
                        <p class="font-semibold text-sm text-slate-700"><?php echo htmlspecialchars($t['first_name'].' '.$t['last_name']); ?></p>
                        <p class="text-xs text-slate-400"><?php echo htmlspecialchars(($t['class_name']??'').' '.($t['arm']??'')); ?></p>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full font-bold bg-red-100 text-red-600">&#8358;<?php echo number_format($t['balance'],2); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Right: selected student's ledger -->
    <div class="lg:col-span-2">
        <?php if (!$view_student): ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-16 text-center h-full flex flex-col items-center justify-center">
            <span class="material-symbols-outlined text-6xl text-slate-200 mb-3">receipt_long</span>
            <p class="font-bold text-slate-700 mb-1">Select a student's tab from the left</p>
            <p class="text-sm text-slate-400">Or use Sell to search any student by reg no directly.</p>
        </div>
        <?php else:
            $balance = getStudentTabBalance($conn, $view_student_id);
        ?>
        <div class="bg-primary rounded-xl p-5 mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-white font-bold text-lg"><?php echo htmlspecialchars($view_student['first_name'].' '.$view_student['last_name']); ?></h3>
                <p class="text-slate-300 text-sm"><?php echo htmlspecialchars($view_student['student_id']); ?> · <?php echo htmlspecialchars(($view_student['class_name']??'').' '.($view_student['arm']??'')); ?></p>
            </div>
            <div class="text-right">
                <p class="text-slate-300 text-xs uppercase font-bold">Balance</p>
                <p class="text-white font-bold text-2xl">&#8358;<?php echo number_format($balance, 2); ?></p>
            </div>
        </div>

        <?php if ($balance > 0): ?>
        <div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
            <h4 class="font-semibold text-slate-800 text-sm mb-3">Record Payment</h4>
            <form method="POST" class="flex flex-wrap gap-3 items-end">
                <input type="hidden" name="action" value="pay">
                <input type="hidden" name="student_id" value="<?php echo $view_student_id; ?>">
                <div>
                    <label class="text-xs font-semibold text-slate-500 mb-1 block">Amount (&#8358;)</label>
                    <input type="number" step="0.01" min="0.01" max="<?php echo $balance; ?>" name="amount" value="<?php echo number_format($balance,2,'.',''); ?>" required class="w-36 border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500 mb-1 block">Method</label>
                    <select name="payment_method" required class="border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
                        <option value="Cash">Cash</option>
                        <option value="Transfer">Transfer</option>
                    </select>
                </div>
                <button type="submit" class="px-5 py-2.5 bg-gold text-primary text-sm font-bold rounded-lg hover:bg-gold/90 transition-all">
                    Record Payment
                </button>
                <a href="canteen_bill.php?student_id=<?php echo $view_student_id; ?>" target="_blank"
                    class="px-5 py-2.5 bg-primary text-white text-sm font-bold rounded-lg hover:bg-primary/90 transition-all inline-flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">print</span>Print Bill
                </a>
            </form>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100 bg-slate-50">
                <h4 class="font-semibold text-slate-800 text-sm">Transaction History</h4>
            </div>
            <div class="divide-y divide-slate-100">
                <?php foreach ($view_ledger as $entry): ?>
                <div class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-700"><?php echo htmlspecialchars($entry['description'] ?: ($entry['entry_type']=='Payment' ? $entry['payment_method'].' payment' : 'Charge')); ?></p>
                        <p class="text-xs text-slate-400"><?php echo date('d M Y, g:i a', strtotime($entry['created_at'])); ?><?php echo $entry['receipt_no'] ? ' · '.htmlspecialchars($entry['receipt_no']) : ''; ?></p>
                    </div>
                    <span class="font-bold text-sm <?php echo $entry['entry_type']=='Charge' ? 'text-red-600' : 'text-green-600'; ?>">
                        <?php echo $entry['entry_type']=='Charge' ? '+' : '−'; ?>&#8358;<?php echo number_format($entry['amount'],2); ?>
                    </span>
                </div>
                <?php endforeach; ?>
                <?php if (empty($view_ledger)): ?>
                <p class="px-5 py-8 text-center text-sm text-slate-400">No transactions yet.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>
</main>
</div>
</div>
</body>
</html>
