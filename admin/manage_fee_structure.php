<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('fees');
require_once 'includes/session_helper.php';
require_once 'includes/fee_helper.php';
$page_title = "Fee Structure";

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'save_rates') {
        $class_id   = intval($_POST['class_id'] ?? 0);
        $session_id = intval($_POST['session_id'] ?? 0);
        $term_id    = intval($_POST['term_id'] ?? 0);
        $amounts    = $_POST['amount'] ?? [];

        if (!$class_id || !$session_id || !$term_id) {
            $error = 'Class, session, and term are all required.';
        } else {
            foreach ($amounts as $category_id => $amount) {
                setFeeStructureAmount($conn, $class_id, $session_id, $term_id, intval($category_id), floatval($amount));
            }
            logActivity('save_fee_structure', "Updated fee structure for class $class_id, term $term_id");
            $success = "Fee structure saved.";
        }
    }

    if ($_POST['action'] === 'add_category') {
        $name = trim($_POST['name'] ?? '');
        if ($name) {
            $next_order = $conn->query("SELECT COALESCE(MAX(display_order),0)+1 as n FROM fee_categories")->fetch_assoc()['n'];
            $stmt = $conn->prepare("INSERT INTO fee_categories (name, display_order) VALUES (?,?)");
            $stmt->bind_param("si", $name, $next_order);
            if ($stmt->execute()) {
                $success = "Category \"" . htmlspecialchars($name) . "\" added.";
            } else {
                $error = 'That category may already exist.';
            }
        }
    }
}

$current    = getCurrentSessionTerm($conn);
$session_id = intval($_GET['session_id'] ?? $current['session_id'] ?? 0);
$term_id    = intval($_GET['term_id'] ?? $current['term_id'] ?? 0);
$class_id   = intval($_GET['class_id'] ?? 0);

$sessions = getAllSessions($conn);
$terms_by_session = [];
foreach (getAllTerms($conn) as $t) $terms_by_session[$t['session_id']][] = $t;
$classes  = $conn->query("SELECT id, class_name, arm, class_level FROM classes ORDER BY class_level DESC, class_name, arm")->fetch_all(MYSQLI_ASSOC);
$categories = getFeeCategories($conn);

$rate_card = [];
$class_total = 0;
if ($class_id && $session_id && $term_id) {
    $rate_card = getFeeStructureForClass($conn, $class_id, $session_id, $term_id);
    $class_total = array_sum(array_column($rate_card, 'amount'));
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
        <h1 class="text-2xl font-bold text-slate-900">Fee Structure</h1>
        <p class="text-slate-500 text-sm mt-1 max-w-2xl">Set each category's amount per class, per term. A student's total fee for the term is these added together, plus any personal adjustment.</p>
    </div>
    <button onclick="document.getElementById('addCatModal').classList.remove('hidden')"
        class="inline-flex items-center gap-2 bg-white border border-slate-200 px-4 py-2.5 rounded-xl font-semibold text-sm text-slate-700 hover:bg-slate-50">
        <span class="material-symbols-outlined text-sm">add</span>New Category
    </button>
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

<!-- Selectors -->
<form method="GET" class="bg-white rounded-xl border border-slate-200 p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div>
        <label class="text-xs font-semibold text-slate-500 mb-1 block">Session</label>
        <select name="session_id" onchange="this.form.submit()" class="border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
            <?php foreach ($sessions as $s): ?>
            <option value="<?php echo $s['id']; ?>" <?php echo $session_id==$s['id']?'selected':''; ?>><?php echo htmlspecialchars($s['session_name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500 mb-1 block">Term</label>
        <select name="term_id" onchange="this.form.submit()" class="border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
            <?php foreach (($terms_by_session[$session_id] ?? []) as $t): ?>
            <option value="<?php echo $t['id']; ?>" <?php echo $term_id==$t['id']?'selected':''; ?>><?php echo htmlspecialchars($t['term_name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500 mb-1 block">Class</label>
        <select name="class_id" onchange="this.form.submit()" class="border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
            <option value="">Select a class…</option>
            <?php foreach ($classes as $c): ?>
            <option value="<?php echo $c['id']; ?>" <?php echo $class_id==$c['id']?'selected':''; ?>><?php echo htmlspecialchars($c['class_name'].' '.$c['arm']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<?php if ($class_id && $session_id && $term_id): ?>
<div class="bg-white rounded-xl border border-slate-200 p-6">
    <form method="POST">
        <input type="hidden" name="action" value="save_rates">
        <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
        <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
        <input type="hidden" name="term_id" value="<?php echo $term_id; ?>">

        <div class="space-y-3 mb-5">
            <?php foreach ($rate_card as $row): ?>
            <div class="flex items-center gap-3">
                <label class="w-40 text-sm font-semibold text-slate-700"><?php echo htmlspecialchars($row['name']); ?></label>
                <span class="text-slate-400">&#8358;</span>
                <input type="number" step="0.01" min="0" name="amount[<?php echo $row['category_id']; ?>]" value="<?php echo number_format($row['amount'],2,'.',''); ?>"
                    class="w-48 border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
            </div>
            <?php endforeach; ?>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-slate-100">
            <p class="font-bold text-slate-800">Total: &#8358;<span id="liveTotal"><?php echo number_format($class_total,2); ?></span></p>
            <button type="submit" class="px-6 py-2.5 bg-gold text-primary text-sm font-bold rounded-lg hover:bg-gold/90">Save Fee Structure</button>
        </div>
    </form>
</div>
<script>
document.querySelectorAll('input[name^="amount"]').forEach(inp => {
    inp.addEventListener('input', () => {
        let total = 0;
        document.querySelectorAll('input[name^="amount"]').forEach(i => total += parseFloat(i.value || 0));
        document.getElementById('liveTotal').textContent = total.toLocaleString('en-NG', {minimumFractionDigits:2, maximumFractionDigits:2});
    });
});
</script>
<?php else: ?>
<div class="bg-white rounded-2xl border border-slate-200 p-16 text-center">
    <span class="material-symbols-outlined text-6xl text-slate-200 block mb-3">receipt_long</span>
    <p class="font-bold text-slate-700 mb-1">Select session, term, and class above</p>
    <p class="text-sm text-slate-400">You'll see and edit that class's fee categories and amounts for the term.</p>
</div>
<?php endif; ?>

</main>
</div>
</div>

<!-- Add Category Modal -->
<div id="addCatModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl p-8 max-w-sm w-full shadow-2xl">
    <h2 class="text-xl font-bold text-slate-900 mb-5 flex items-center gap-2"><span class="material-symbols-outlined text-gold">add_circle</span>New Fee Category</h2>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="add_category">
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Category Name</label>
            <input type="text" name="name" required placeholder="e.g. Excursion, Lab Fee" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 bg-gold text-primary py-3 rounded-xl font-bold hover:bg-gold/90">Add</button>
            <button type="button" onclick="document.getElementById('addCatModal').classList.add('hidden')" class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl font-semibold hover:bg-slate-200">Cancel</button>
        </div>
    </form>
</div>
</div>
<script>
document.getElementById('addCatModal').addEventListener('click', function(e) { if (e.target === this) this.classList.add('hidden'); });
</script>
</body>
</html>
