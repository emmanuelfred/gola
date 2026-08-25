<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('fees');
require_once 'includes/session_helper.php';
require_once 'includes/enrollment_helper.php';
require_once 'includes/fee_helper.php';
$page_title = "Fee Collection";

$success = '';
$error   = '';

$current    = getCurrentSessionTerm($conn);
$session_id = intval($_GET['session_id'] ?? $current['session_id'] ?? 0);
$term_id    = intval($_GET['term_id'] ?? $current['term_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'pay') {
    $student_id = intval($_POST['student_id'] ?? 0);
    $amount     = floatval($_POST['amount'] ?? 0);
    $method     = $_POST['payment_method'] ?? '';
    $notes      = trim($_POST['notes'] ?? '') ?: null;
    $pay_session = intval($_POST['session_id'] ?? $session_id);
    $pay_term    = intval($_POST['term_id'] ?? $term_id);

    $result = recordFeePayment($conn, $student_id, $pay_session, $pay_term, $amount, $method, $notes, $admin_id);
    if ($result['ok']) {
        logActivity('fee_payment', "Recorded {$result['receipt_no']} — ₦$amount via $method for student ID $student_id");
        $success = "Payment recorded — receipt <strong>{$result['receipt_no']}</strong>.";
        $_GET['student_id'] = $student_id; // keep viewing this student after posting
    } else {
        $error = $result['error'];
    }
}

$sessions = getAllSessions($conn);
$terms_by_session = [];
foreach (getAllTerms($conn) as $t) $terms_by_session[$t['session_id']][] = $t;

$outstanding = getStudentsWithOutstandingFees($conn, $session_id, $term_id);

$view_student_id = intval($_GET['student_id'] ?? 0);
$view_student = null;
$view_status  = null;
$view_rate_card = [];
$view_payments = [];
if ($view_student_id) {
    $view_student = $conn->query("SELECT s.*, c.id as class_id, c.class_name, c.arm FROM students s LEFT JOIN classes c ON c.id=s.class_id WHERE s.id=$view_student_id")->fetch_assoc();
    // Use their actual enrollment for THIS session/term if it exists, else fall back to home class
    $enroll = getStudentEnrollment($conn, $view_student_id, $session_id);
    $eff_class_id = $enroll ? $enroll['class_id'] : ($view_student['class_id'] ?? 0);
    if ($view_student && $eff_class_id) {
        $view_status = getStudentFeeStatus($conn, $view_student_id, $eff_class_id, $session_id, $term_id);
        $view_rate_card = getFeeStructureForClass($conn, $eff_class_id, $session_id, $term_id);
        $stmt = $conn->prepare("SELECT * FROM fee_payments WHERE student_id=? AND session_id=? AND term_id=? ORDER BY created_at DESC");
        $stmt->bind_param("iii", $view_student_id, $session_id, $term_id);
        $stmt->execute();
        $view_payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

$search_result = null;
$search_error = '';
if (!empty($_GET['reg_no'])) {
    $reg_no = trim($_GET['reg_no']);
    $stmt = $conn->prepare("SELECT id FROM students WHERE student_id = ? AND status='Active'");
    $stmt->bind_param("s", $reg_no);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        header("Location: fee_collection.php?session_id=$session_id&term_id=$term_id&student_id={$row['id']}");
        exit;
    } else {
        $search_error = "No active student found with reg number \"" . htmlspecialchars($reg_no) . "\".";
    }
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

<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Fee Collection</h1>
        <p class="text-slate-500 text-sm mt-1">
            <a href="manage_fee_structure.php" class="text-primary font-semibold hover:underline">Fee Structure</a> ·
            <a href="fee_reports.php" class="text-primary font-semibold hover:underline">Reports</a>
        </p>
    </div>
    <form method="GET" class="flex items-center gap-2">
        <?php if ($view_student_id): ?><input type="hidden" name="student_id" value="<?php echo $view_student_id; ?>"><?php endif; ?>
        <select name="session_id" onchange="this.form.submit()" class="text-sm border-slate-200 rounded-lg focus:ring-gold focus:border-gold">
            <?php foreach ($sessions as $s): ?>
            <option value="<?php echo $s['id']; ?>" <?php echo $session_id==$s['id']?'selected':''; ?>><?php echo htmlspecialchars($s['session_name']); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="term_id" onchange="this.form.submit()" class="text-sm border-slate-200 rounded-lg focus:ring-gold focus:border-gold">
            <?php foreach (($terms_by_session[$session_id] ?? []) as $t): ?>
            <option value="<?php echo $t['id']; ?>" <?php echo $term_id==$t['id']?'selected':''; ?>><?php echo htmlspecialchars($t['term_name']); ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if ($success): ?>
<div class="mb-5 p-4 bg-green-50 border border-green-200 rounded-xl flex gap-3 items-start">
    <span class="material-symbols-outlined text-green-600 flex-shrink-0">check_circle</span>
    <p class="text-green-800 text-sm"><?php echo $success; ?></p>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl flex gap-3 items-start">
    <span class="material-symbols-outlined text-red-600 flex-shrink-0">error</span>
    <p class="text-red-800 text-sm"><?php echo htmlspecialchars($error); ?></p>
</div>
<?php endif; ?>

<!-- Search by reg no -->
<form method="GET" class="bg-white rounded-xl border border-slate-200 p-4 mb-6 flex gap-3 items-end">
    <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
    <input type="hidden" name="term_id" value="<?php echo $term_id; ?>">
    <div class="flex-1 max-w-md">
        <label class="text-xs font-semibold text-slate-500 mb-1 block">Find a Student by Reg No</label>
        <input type="text" name="reg_no" placeholder="e.g. GOLA/2026/JSS1A/003" class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
    </div>
    <button type="submit" class="px-5 py-2.5 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary/90">Search</button>
</form>
<?php if ($search_error): ?>
<div class="mb-5 p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm"><?php echo $search_error; ?></div>
<?php endif; ?>

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Left: outstanding list -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
                <h3 class="font-bold text-slate-800 text-sm">Outstanding This Term (<?php echo count($outstanding); ?>)</h3>
            </div>
            <div class="divide-y divide-slate-100 max-h-[65vh] overflow-y-auto">
                <?php if (empty($outstanding)): ?>
                <p class="px-4 py-8 text-center text-sm text-slate-400">Everyone is fully paid up.</p>
                <?php endif; ?>
                <?php foreach ($outstanding as $t): ?>
                <a href="?session_id=<?php echo $session_id; ?>&term_id=<?php echo $term_id; ?>&student_id=<?php echo $t['id']; ?>"
                   class="flex items-center justify-between px-4 py-3 hover:bg-gold/5 transition-colors <?php echo $view_student_id===$t['id'] ? 'bg-gold/10 border-l-4 border-gold' : ''; ?>">
                    <div>
                        <p class="font-semibold text-sm text-slate-700"><?php echo htmlspecialchars($t['first_name'].' '.$t['last_name']); ?></p>
                        <p class="text-xs text-slate-400"><?php echo htmlspecialchars($t['class_name'].' '.$t['arm']); ?></p>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full font-bold bg-red-100 text-red-600">&#8358;<?php echo number_format($t['balance'],2); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Right: selected student -->
    <div class="lg:col-span-2">
        <?php if (!$view_student): ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-16 text-center h-full flex flex-col items-center justify-center">
            <span class="material-symbols-outlined text-6xl text-slate-200 mb-3">payments</span>
            <p class="font-bold text-slate-700 mb-1">Search a student or pick one from the outstanding list</p>
        </div>
        <?php else: ?>

        <div class="bg-primary rounded-xl p-5 mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-white font-bold text-lg"><?php echo htmlspecialchars($view_student['first_name'].' '.$view_student['last_name']); ?></h3>
                <p class="text-slate-300 text-sm"><?php echo htmlspecialchars($view_student['student_id']); ?> · <?php echo htmlspecialchars($view_student['class_name'].' '.$view_student['arm']); ?></p>
            </div>
            <div class="text-right">
                <p class="text-slate-300 text-xs uppercase font-bold">Balance</p>
                <p class="text-white font-bold text-2xl">&#8358;<?php echo number_format($view_status['balance'] ?? 0, 2); ?></p>
            </div>
        </div>

        <!-- Fee breakdown -->
        <div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
            <h4 class="font-semibold text-slate-800 text-sm mb-3">Fee Breakdown — This Term</h4>
            <?php if (empty($view_rate_card)): ?>
            <p class="text-sm text-amber-600">No fee structure set for this class/term yet. <a href="manage_fee_structure.php" class="underline">Set it up</a>.</p>
            <?php else: ?>
            <div class="space-y-1.5">
                <?php foreach ($view_rate_card as $row): ?>
                <div class="flex justify-between text-sm"><span class="text-slate-600"><?php echo htmlspecialchars($row['name']); ?></span><span class="text-slate-700">&#8358;<?php echo number_format($row['amount'],2); ?></span></div>
                <?php endforeach; ?>
                <div class="flex justify-between text-sm font-bold pt-2 border-t border-slate-100"><span>Total Owed</span><span>&#8358;<?php echo number_format($view_status['total'],2); ?></span></div>
                <div class="flex justify-between text-sm text-green-600"><span>Paid</span><span>&#8358;<?php echo number_format($view_status['paid'],2); ?></span></div>
                <div class="flex justify-between text-sm font-bold <?php echo $view_status['balance']>0 ? 'text-red-600' : 'text-green-600'; ?>"><span>Balance</span><span>&#8358;<?php echo number_format($view_status['balance'],2); ?></span></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Record payment -->
        <div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
            <h4 class="font-semibold text-slate-800 text-sm mb-3">Record Payment</h4>
            <form method="POST" class="flex flex-wrap gap-3 items-end">
                <input type="hidden" name="action" value="pay">
                <input type="hidden" name="student_id" value="<?php echo $view_student_id; ?>">
                <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
                <input type="hidden" name="term_id" value="<?php echo $term_id; ?>">
                <div>
                    <label class="text-xs font-semibold text-slate-500 mb-1 block">Amount (&#8358;)</label>
                    <input type="number" step="0.01" min="0.01" name="amount" value="<?php echo number_format(max(0,$view_status['balance'] ?? 0),2,'.',''); ?>" required class="w-36 border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500 mb-1 block">Method</label>
                    <select name="payment_method" required class="border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
                        <option value="Cash">Cash</option>
                        <option value="Transfer">Transfer</option>
                    </select>
                </div>
                <div class="flex-1 min-w-40">
                    <label class="text-xs font-semibold text-slate-500 mb-1 block">Note (optional)</label>
                    <input type="text" name="notes" class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
                </div>
                <button type="submit" class="px-5 py-2.5 bg-gold text-primary text-sm font-bold rounded-lg hover:bg-gold/90 transition-all">Record Payment</button>
            </form>
        </div>

        <!-- Payment history -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100 bg-slate-50">
                <h4 class="font-semibold text-slate-800 text-sm">Payment History — This Term</h4>
            </div>
            <div class="divide-y divide-slate-100">
                <?php foreach ($view_payments as $p): ?>
                <div class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-700"><?php echo htmlspecialchars($p['receipt_no']); ?> — <?php echo htmlspecialchars($p['payment_method']); ?></p>
                        <p class="text-xs text-slate-400"><?php echo date('d M Y, g:i a', strtotime($p['created_at'])); ?><?php echo $p['notes'] ? ' · '.htmlspecialchars($p['notes']) : ''; ?></p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="font-bold text-sm text-green-600">&#8358;<?php echo number_format($p['amount'],2); ?></span>
                        <a href="fee_receipt.php?payment_id=<?php echo $p['id']; ?>" target="_blank" class="text-primary hover:underline text-xs font-semibold">Print</a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($view_payments)): ?>
                <p class="px-5 py-8 text-center text-sm text-slate-400">No payments recorded yet this term.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php endif; ?>
    </div>
</div>

</main>
</div>
</div>
</body>
</html>
