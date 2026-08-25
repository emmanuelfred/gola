<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('expenses');
require_once 'includes/expense_helper.php';
$page_title = "Expenses";

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add_expense') {
        $category_id = intval($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $amount      = floatval($_POST['amount'] ?? 0);
        $date        = $_POST['expense_date'] ?? date('Y-m-d');
        $method      = $_POST['payment_method'] ?? 'Cash';
        $receipt_ref = trim($_POST['receipt_reference'] ?? '') ?: null;
        $notes       = trim($_POST['notes'] ?? '') ?: null;

        if (!$category_id || !$description) {
            $error = 'Category and description are required.';
        } else {
            $result = recordExpense($conn, $category_id, $description, $amount, $date, $method, $receipt_ref, $notes, $admin_id);
            if ($result['ok']) {
                logActivity('add_expense', "Recorded expense: $description (₦$amount)");
                $success = "Expense recorded.";
            } else {
                $error = $result['error'];
            }
        }
    }

    if ($_POST['action'] === 'delete_expense' && hasPermission('admin')) {
        $id = intval($_POST['expense_id'] ?? 0);
        $conn->query("DELETE FROM expenses WHERE id=$id");
        $success = "Expense deleted.";
    }

    if ($_POST['action'] === 'add_category') {
        $name = trim($_POST['name'] ?? '');
        if ($name) {
            $stmt = $conn->prepare("INSERT INTO expense_categories (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            if ($stmt->execute()) { $success = "Category added."; } else { $error = "That category may already exist."; }
        }
    }
}

$preset = $_GET['preset'] ?? 'month';
$today = date('Y-m-d');
switch ($preset) {
    case 'today': $from = $today; $to = $today; break;
    case 'week':  $from = date('Y-m-d', strtotime('monday this week')); $to = $today; break;
    case '30d':   $from = date('Y-m-d', strtotime('-29 days')); $to = $today; break;
    case 'month':
    default:      $from = date('Y-m-01'); $to = $today; $preset = 'month'; break;
}
$category_filter = intval($_GET['category_id'] ?? 0);

$categories = getExpenseCategories($conn);
$expenses   = getExpenses($conn, $from, $to, $category_filter);
$total      = getExpensesTotal($conn, $from, $to);
$by_category = getExpensesByCategory($conn, $from, $to);
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
        <h1 class="text-2xl font-bold text-slate-900">Expenses</h1>
        <p class="text-slate-500 text-sm mt-1">Track school spending by category.</p>
    </div>
    <div class="flex items-center gap-2">
        <button onclick="document.getElementById('addCatModal').classList.remove('hidden')"
            class="inline-flex items-center gap-2 bg-white border border-slate-200 px-4 py-2.5 rounded-xl font-semibold text-sm text-slate-700 hover:bg-slate-50">
            <span class="material-symbols-outlined text-sm">category</span>New Category
        </button>
        <button onclick="document.getElementById('addExpModal').classList.remove('hidden')"
            class="inline-flex items-center gap-2 bg-gold text-primary px-5 py-2.5 rounded-xl font-bold hover:bg-gold/90 shadow-sm">
            <span class="material-symbols-outlined text-sm">add_circle</span>Record Expense
        </button>
    </div>
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

<div class="flex flex-wrap items-center gap-2 mb-4">
    <?php foreach (['today'=>'Today','week'=>'This Week','month'=>'This Month','30d'=>'Last 30 Days'] as $p=>$label): ?>
    <a href="?preset=<?php echo $p; ?>" class="px-3 py-1.5 rounded-lg text-xs font-semibold <?php echo $preset==$p ? 'bg-gold text-primary' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'; ?>"><?php echo $label; ?></a>
    <?php endforeach; ?>
    <form method="GET" class="inline-flex items-center gap-2 ml-2">
        <input type="hidden" name="preset" value="<?php echo $preset; ?>">
        <select name="category_id" onchange="this.form.submit()" class="text-xs border-slate-200 rounded-lg focus:ring-gold focus:border-gold">
            <option value="0">All Categories</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?php echo $c['id']; ?>" <?php echo $category_filter==$c['id']?'selected':''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="grid lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-semibold text-slate-500 uppercase">Total (<?php echo date('d M', strtotime($from)); ?>–<?php echo date('d M', strtotime($to)); ?>)</p>
        <p class="text-2xl font-bold text-red-600 mt-1">&#8358;<?php echo number_format($total,2); ?></p>
        <p class="text-xs text-slate-400 mt-1"><?php echo count($expenses); ?> record(s)</p>
    </div>
    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-semibold text-slate-500 uppercase mb-2">By Category</p>
        <?php if (empty($by_category)): ?>
        <p class="text-sm text-slate-400">No expenses in this period.</p>
        <?php else: ?>
        <div class="space-y-1.5">
            <?php foreach ($by_category as $c):
                $pct = $total > 0 ? round(($c['total']/$total)*100) : 0;
            ?>
            <div class="flex items-center gap-2 text-xs">
                <span class="w-32 text-slate-600 truncate"><?php echo htmlspecialchars($c['name']); ?></span>
                <div class="flex-1 bg-slate-100 rounded-full h-2"><div class="bg-gold h-2 rounded-full" style="width:<?php echo $pct; ?>%"></div></div>
                <span class="w-24 text-right text-slate-700 font-medium">&#8358;<?php echo number_format($c['total'],2); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                <th class="px-5 py-3">Date</th>
                <th class="px-5 py-3">Description</th>
                <th class="px-5 py-3">Category</th>
                <th class="px-5 py-3">Method</th>
                <th class="px-5 py-3 text-right">Amount</th>
                <th class="px-5 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if (empty($expenses)): ?>
            <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400">No expenses recorded in this period.</td></tr>
            <?php endif; ?>
            <?php foreach ($expenses as $e): ?>
            <tr class="hover:bg-slate-50">
                <td class="px-5 py-3 text-slate-500 text-xs"><?php echo date('d M Y', strtotime($e['expense_date'])); ?></td>
                <td class="px-5 py-3 font-medium text-slate-800"><?php echo htmlspecialchars($e['description']); ?><?php echo $e['receipt_reference'] ? '<span class="text-xs text-slate-400"> · ref: '.htmlspecialchars($e['receipt_reference']).'</span>' : ''; ?></td>
                <td class="px-5 py-3 text-slate-500"><?php echo htmlspecialchars($e['category_name']); ?></td>
                <td class="px-5 py-3 text-slate-500"><?php echo htmlspecialchars($e['payment_method']); ?></td>
                <td class="px-5 py-3 text-right font-bold text-red-600">&#8358;<?php echo number_format($e['amount'],2); ?></td>
                <td class="px-5 py-3 text-right">
                    <?php if (hasPermission('admin')): ?>
                    <form method="POST" class="inline" onsubmit="return confirm('Delete this expense record?')">
                        <input type="hidden" name="action" value="delete_expense">
                        <input type="hidden" name="expense_id" value="<?php echo $e['id']; ?>">
                        <button type="submit" class="text-red-500 hover:underline text-xs font-semibold">Delete</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</main>
</div>
</div>

<!-- Add Expense Modal -->
<div id="addExpModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl p-8 max-w-md w-full shadow-2xl">
    <h2 class="text-xl font-bold text-slate-900 mb-5 flex items-center gap-2"><span class="material-symbols-outlined text-gold">add_circle</span>Record Expense</h2>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="add_expense">
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Category <span class="text-red-500">*</span></label>
            <select name="category_id" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold">
                <option value="">Select…</option>
                <?php foreach ($categories as $c): ?>
                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                <?php endforeach; ?>
            </select></div>
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Description <span class="text-red-500">*</span></label>
            <input type="text" name="description" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Amount (&#8358;)</label>
                <input type="number" step="0.01" min="0.01" name="amount" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
            <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Date</label>
                <input type="date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        </div>
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Payment Method</label>
            <select name="payment_method" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"><option>Cash</option><option>Transfer</option></select></div>
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Receipt/Invoice Ref (optional)</label>
            <input type="text" name="receipt_reference" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Notes (optional)</label>
            <textarea name="notes" rows="2" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></textarea></div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 bg-gold text-primary py-3 rounded-xl font-bold hover:bg-gold/90">Save Expense</button>
            <button type="button" onclick="document.getElementById('addExpModal').classList.add('hidden')" class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl font-semibold hover:bg-slate-200">Cancel</button>
        </div>
    </form>
</div>
</div>

<!-- Add Category Modal -->
<div id="addCatModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl p-8 max-w-sm w-full shadow-2xl">
    <h2 class="text-xl font-bold text-slate-900 mb-5 flex items-center gap-2"><span class="material-symbols-outlined text-gold">category</span>New Expense Category</h2>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="add_category">
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Category Name</label>
            <input type="text" name="name" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 bg-gold text-primary py-3 rounded-xl font-bold hover:bg-gold/90">Add</button>
            <button type="button" onclick="document.getElementById('addCatModal').classList.add('hidden')" class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl font-semibold hover:bg-slate-200">Cancel</button>
        </div>
    </form>
</div>
</div>
<script>
['addExpModal','addCatModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) { if (e.target === this) this.classList.add('hidden'); });
});
</script>
</body>
</html>
