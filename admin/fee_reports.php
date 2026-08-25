<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('fees');
require_once 'includes/session_helper.php';
require_once 'includes/fee_helper.php';
$page_title = "Fee Reports";

$preset = $_GET['preset'] ?? 'month';
$today = date('Y-m-d');
switch ($preset) {
    case 'today': $from = $today; $to = $today; break;
    case 'week':  $from = date('Y-m-d', strtotime('monday this week')); $to = $today; break;
    case '30d':   $from = date('Y-m-d', strtotime('-29 days')); $to = $today; break;
    case 'month':
    default:      $from = date('Y-m-01'); $to = $today; $preset = 'month'; break;
}

$current    = getCurrentSessionTerm($conn);
$session_id = intval($_GET['session_id'] ?? $current['session_id'] ?? 0);
$term_id    = intval($_GET['term_id'] ?? $current['term_id'] ?? 0);

$collected = getFeesCollected($conn, $from, $to);
$outstanding_list = getStudentsWithOutstandingFees($conn, $session_id, $term_id);
$total_outstanding = array_sum(array_column($outstanding_list, 'balance'));

// Outstanding grouped by class
$by_class = [];
foreach ($outstanding_list as $s) {
    $key = $s['class_name'].' '.$s['arm'];
    if (!isset($by_class[$key])) $by_class[$key] = ['count'=>0, 'total'=>0];
    $by_class[$key]['count']++;
    $by_class[$key]['total'] += $s['balance'];
}
arsort($by_class);

$sessions = getAllSessions($conn);
$terms_by_session = [];
foreach (getAllTerms($conn) as $t) $terms_by_session[$t['session_id']][] = $t;
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
        <h1 class="text-2xl font-bold text-slate-900">Fee Reports</h1>
        <p class="text-slate-500 text-sm mt-1"><a href="fee_collection.php" class="text-primary font-semibold hover:underline">Collect Fees</a> · <a href="manage_fee_structure.php" class="text-primary font-semibold hover:underline">Fee Structure</a></p>
    </div>
    <div class="flex items-center gap-2">
        <?php foreach (['today'=>'Today','week'=>'This Week','month'=>'This Month','30d'=>'Last 30 Days'] as $p=>$label): ?>
        <a href="?preset=<?php echo $p; ?>&session_id=<?php echo $session_id; ?>&term_id=<?php echo $term_id; ?>" class="px-3 py-1.5 rounded-lg text-xs font-semibold <?php echo $preset==$p ? 'bg-gold text-primary' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'; ?>"><?php echo $label; ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-semibold text-slate-500 uppercase">Collected (<?php echo date('d M', strtotime($from)); ?>–<?php echo date('d M', strtotime($to)); ?>)</p>
        <p class="text-2xl font-bold text-primary mt-1">&#8358;<?php echo number_format($collected,2); ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-semibold text-slate-500 uppercase">Outstanding (current term)</p>
        <p class="text-2xl font-bold text-red-600 mt-1">&#8358;<?php echo number_format($total_outstanding,2); ?></p>
        <p class="text-xs text-slate-400 mt-1"><?php echo count($outstanding_list); ?> student(s)</p>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="px-5 py-3 border-b border-slate-100 bg-slate-50">
        <h4 class="font-semibold text-slate-800 text-sm">Outstanding by Class — This Term</h4>
    </div>
    <?php if (empty($by_class)): ?>
    <p class="px-5 py-8 text-center text-sm text-slate-400">No outstanding fees this term.</p>
    <?php else: ?>
    <div class="divide-y divide-slate-100">
        <?php foreach ($by_class as $class_label => $data): ?>
        <div class="px-5 py-3 flex items-center justify-between">
            <span class="text-sm font-medium text-slate-700"><?php echo htmlspecialchars($class_label); ?></span>
            <div class="text-right">
                <span class="font-bold text-sm text-red-600">&#8358;<?php echo number_format($data['total'],2); ?></span>
                <span class="text-xs text-slate-400 ml-2"><?php echo $data['count']; ?> student(s)</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

</main>
</div>
</div>
</body>
</html>
