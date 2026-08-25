<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('payroll');
require_once 'includes/payroll_helper.php';
$page_title = "Payroll";

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_run' && hasPermission('admin')) {
    $pay_period = trim($_POST['pay_period'] ?? ''); // e.g. 2026-08 from <input type="month">
    if (!$pay_period || !preg_match('/^\d{4}-\d{2}$/', $pay_period)) {
        $error = 'Select a valid month.';
    } else {
        $label = date('F Y', strtotime($pay_period.'-01'));
        $result = createPayrollRun($conn, $pay_period, $label, $admin_id);
        if ($result['ok']) {
            logActivity('create_payroll_run', "Created payroll run: $label");
            header("Location: payroll_run.php?id={$result['run_id']}");
            exit;
        } else {
            $error = $result['error'];
        }
    }
}

$runs = getAllPayrollRuns($conn);
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
        <h1 class="text-2xl font-bold text-slate-900">Payroll</h1>
        <p class="text-slate-500 text-sm mt-1">Monthly staff pay runs — salary plus allowances, minus deductions.</p>
    </div>
    <?php if (hasPermission('admin')): ?>
    <button onclick="document.getElementById('newRunModal').classList.remove('hidden')"
        class="inline-flex items-center gap-2 bg-gold text-primary px-5 py-3 rounded-xl font-bold hover:bg-gold/90 shadow-sm">
        <span class="material-symbols-outlined">add_circle</span>New Payroll Run
    </button>
    <?php endif; ?>
</div>

<?php if ($error): ?>
<div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl flex gap-3 items-start">
    <span class="material-symbols-outlined text-red-600 flex-shrink-0">error</span>
    <p class="text-red-800 text-sm"><?php echo htmlspecialchars($error); ?></p>
</div>
<?php endif; ?>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                <th class="px-5 py-3">Period</th>
                <th class="px-5 py-3">Status</th>
                <th class="px-5 py-3 text-center">Staff</th>
                <th class="px-5 py-3 text-right">Total Net</th>
                <th class="px-5 py-3 text-center">Paid</th>
                <th class="px-5 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if (empty($runs)): ?>
            <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400">No payroll runs yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($runs as $r):
                $summary = getPayrollRunSummary($conn, $r['id']);
            ?>
            <tr class="hover:bg-slate-50">
                <td class="px-5 py-3 font-semibold text-slate-800"><?php echo htmlspecialchars($r['label']); ?></td>
                <td class="px-5 py-3">
                    <span class="px-2 py-0.5 <?php echo $r['status']=='Finalized' ? 'bg-primary/10 text-primary' : 'bg-amber-100 text-amber-700'; ?> text-xs font-semibold rounded-full"><?php echo $r['status']; ?></span>
                </td>
                <td class="px-5 py-3 text-center text-slate-600"><?php echo $summary['staff_count']; ?></td>
                <td class="px-5 py-3 text-right font-medium text-slate-700">&#8358;<?php echo number_format($summary['total_net'],2); ?></td>
                <td class="px-5 py-3 text-center text-slate-500"><?php echo $summary['paid_count']; ?>/<?php echo $summary['staff_count']; ?></td>
                <td class="px-5 py-3 text-right">
                    <a href="payroll_run.php?id=<?php echo $r['id']; ?>" class="text-primary hover:underline text-xs font-semibold">Open →</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</main>
</div>
</div>

<!-- New Run Modal -->
<div id="newRunModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl p-8 max-w-sm w-full shadow-2xl">
    <h2 class="text-xl font-bold text-slate-900 mb-5 flex items-center gap-2"><span class="material-symbols-outlined text-gold">add_circle</span>New Payroll Run</h2>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="create_run">
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Month</label>
            <input type="month" name="pay_period" required value="<?php echo date('Y-m'); ?>" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        <p class="text-xs text-slate-400">This will create one payslip for every currently Active staff member, using their current salary.</p>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 bg-gold text-primary py-3 rounded-xl font-bold hover:bg-gold/90">Create Run</button>
            <button type="button" onclick="document.getElementById('newRunModal').classList.add('hidden')" class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl font-semibold hover:bg-slate-200">Cancel</button>
        </div>
    </form>
</div>
</div>
<script>
document.getElementById('newRunModal').addEventListener('click', function(e) { if (e.target === this) this.classList.add('hidden'); });
</script>
</body>
</html>
