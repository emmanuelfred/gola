<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('canteen');
require_once 'includes/canteen_helper.php';
$page_title = "Canteen Reports";

$authorized = isCanteenOperator($conn, $admin_id) || hasPermission('admin');

// ── Date range ───────────────────────────────────────────────────────────────
$preset = $_GET['preset'] ?? 'month';
$today = date('Y-m-d');
switch ($preset) {
    case 'today': $from = $today; $to = $today; break;
    case 'week':  $from = date('Y-m-d', strtotime('monday this week')); $to = $today; break;
    case '30d':   $from = date('Y-m-d', strtotime('-29 days')); $to = $today; break;
    case 'custom':
        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to'] ?? $today;
        break;
    case 'month':
    default:      $from = date('Y-m-01'); $to = $today; $preset = 'month'; break;
}
// Basic sanity: don't let 'to' be before 'from'
if (strtotime($to) < strtotime($from)) { $to = $from; }

$summary   = $authorized ? getCanteenSummary($conn, $from, $to) : null;
$breakdown = $authorized ? getPaymentBreakdown($conn, $from, $to) : null;
$sellers   = $authorized ? getBestSellers($conn, $from, $to) : [];
$daily     = $authorized ? getDailyRevenue($conn, $from, $to) : [];
$outstanding = $authorized ? getTotalOutstandingTabs($conn) : 0;
$stock_alerts = $authorized ? getStockAlerts($conn) : ['out_of_stock'=>[],'low_stock'=>[]];

$max_daily = $daily ? max(array_column($daily, 'total')) : 0;
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

<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Reports</h1>
        <p class="text-slate-500 text-sm mt-1">
            <a href="canteen_pos.php" class="text-primary font-semibold hover:underline">Sell</a> ·
            <a href="canteen_tabs.php" class="text-primary font-semibold hover:underline">Student Tabs</a> ·
            <a href="manage_canteen_items.php" class="text-primary font-semibold hover:underline">Items & Stock</a>
        </p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <?php foreach (['today'=>'Today','week'=>'This Week','month'=>'This Month','30d'=>'Last 30 Days'] as $p=>$label): ?>
        <a href="?preset=<?php echo $p; ?>" class="px-3 py-1.5 rounded-lg text-xs font-semibold <?php echo $preset==$p ? 'bg-gold text-primary' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'; ?>"><?php echo $label; ?></a>
        <?php endforeach; ?>
        <form method="GET" class="flex items-center gap-1">
            <input type="hidden" name="preset" value="custom">
            <input type="date" name="from" value="<?php echo htmlspecialchars($from); ?>" class="text-xs border-slate-200 rounded-lg focus:ring-gold focus:border-gold py-1.5">
            <span class="text-xs text-slate-400">to</span>
            <input type="date" name="to" value="<?php echo htmlspecialchars($to); ?>" class="text-xs border-slate-200 rounded-lg focus:ring-gold focus:border-gold py-1.5">
            <button type="submit" class="px-3 py-1.5 bg-primary text-white text-xs font-semibold rounded-lg">Go</button>
        </form>
    </div>
</div>

<p class="text-xs text-slate-400 mb-4"><?php echo date('d M Y', strtotime($from)); ?> — <?php echo date('d M Y', strtotime($to)); ?></p>

<!-- Summary cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-semibold text-slate-500 uppercase">Revenue</p>
        <p class="text-2xl font-bold text-primary mt-1">&#8358;<?php echo number_format($summary['revenue'],2); ?></p>
        <p class="text-xs text-slate-400 mt-1"><?php echo $summary['transaction_count']; ?> sale(s) · <?php echo $summary['items_sold']; ?> item(s)</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-semibold text-slate-500 uppercase">Cost</p>
        <p class="text-2xl font-bold text-slate-700 mt-1">&#8358;<?php echo number_format($summary['cost'],2); ?></p>
        <?php if ($summary['items_missing_cost'] > 0): ?>
        <p class="text-xs text-amber-500 mt-1"><?php echo $summary['items_missing_cost']; ?> item(s) sold have no cost price set</p>
        <?php else: ?>
        <p class="text-xs text-slate-400 mt-1">All sold items have a cost on record</p>
        <?php endif; ?>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-semibold text-slate-500 uppercase">Gross Profit</p>
        <p class="text-2xl font-bold <?php echo $summary['profit']>=0 ? 'text-green-600' : 'text-red-600'; ?> mt-1">&#8358;<?php echo number_format($summary['profit'],2); ?></p>
        <p class="text-xs text-slate-400 mt-1"><?php echo $summary['items_missing_cost']>0 ? 'May be understated — see Cost' : 'Revenue minus cost'; ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-semibold text-slate-500 uppercase">Outstanding (all tabs, now)</p>
        <p class="text-2xl font-bold text-amber-600 mt-1">&#8358;<?php echo number_format($outstanding,2); ?></p>
        <p class="text-xs text-slate-400 mt-1"><a href="canteen_tabs.php" class="hover:underline">View tabs →</a></p>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6 mb-6">
    <!-- Revenue trend -->
    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-5">
        <h4 class="font-semibold text-slate-800 text-sm mb-4">Revenue Over Time</h4>
        <?php if (empty($daily) || $max_daily == 0): ?>
        <p class="text-sm text-slate-400 text-center py-10">No sales in this period.</p>
        <?php else: ?>
        <div class="flex items-end gap-1 h-40">
            <?php foreach ($daily as $d):
                $pct = $max_daily > 0 ? max(2, round(($d['total'] / $max_daily) * 100)) : 2;
            ?>
            <div class="flex-1 flex flex-col items-center justify-end h-full group relative">
                <div class="absolute -top-6 hidden group-hover:block bg-primary text-white text-xs px-2 py-1 rounded whitespace-nowrap z-10">
                    &#8358;<?php echo number_format($d['total'],2); ?>
                </div>
                <div class="w-full <?php echo $d['total']>0 ? 'bg-gold' : 'bg-slate-100'; ?> rounded-t transition-all" style="height: <?php echo $pct; ?>%"></div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="flex justify-between mt-2 text-xs text-slate-400">
            <span><?php echo date('d M', strtotime($daily[0]['date'])); ?></span>
            <?php if (count($daily) > 1): ?>
            <span><?php echo date('d M', strtotime(end($daily)['date'])); ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Payment breakdown -->
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h4 class="font-semibold text-slate-800 text-sm mb-4">By Payment Method</h4>
        <div class="space-y-3">
            <?php foreach (['Cash'=>'bg-green-500','Transfer'=>'bg-blue-500','Tab'=>'bg-amber-500'] as $method=>$color):
                $amt = $breakdown[$method] ?? 0;
                $pct = $summary['revenue'] > 0 ? round(($amt / $summary['revenue']) * 100) : 0;
            ?>
            <div>
                <div class="flex justify-between text-xs mb-1">
                    <span class="font-semibold text-slate-600"><?php echo $method; ?></span>
                    <span class="text-slate-500">&#8358;<?php echo number_format($amt,2); ?> (<?php echo $pct; ?>%)</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-2">
                    <div class="<?php echo $color; ?> h-2 rounded-full" style="width: <?php echo $pct; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    <!-- Best sellers -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-100 bg-slate-50">
            <h4 class="font-semibold text-slate-800 text-sm">Best Sellers</h4>
        </div>
        <?php if (empty($sellers)): ?>
        <p class="px-5 py-8 text-center text-sm text-slate-400">No sales in this period.</p>
        <?php else: ?>
        <div class="divide-y divide-slate-100">
            <?php foreach ($sellers as $i => $s): ?>
            <div class="px-5 py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="w-6 h-6 flex items-center justify-center rounded-full <?php echo $i<3 ? 'bg-gold text-primary' : 'bg-slate-100 text-slate-500'; ?> text-xs font-bold"><?php echo $i+1; ?></span>
                    <span class="text-sm font-medium text-slate-700"><?php echo htmlspecialchars($s['item_name']); ?></span>
                </div>
                <div class="text-right">
                    <p class="text-sm font-bold text-slate-800"><?php echo $s['qty_sold']; ?> sold</p>
                    <p class="text-xs text-slate-400">&#8358;<?php echo number_format($s['revenue'],2); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Stock alerts -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
            <h4 class="font-semibold text-slate-800 text-sm">Stock Alerts</h4>
            <a href="manage_canteen_items.php" class="text-xs text-primary font-semibold hover:underline">Manage Items →</a>
        </div>
        <?php if (empty($stock_alerts['out_of_stock']) && empty($stock_alerts['low_stock'])): ?>
        <p class="px-5 py-8 text-center text-sm text-slate-400">All items are well-stocked.</p>
        <?php else: ?>
        <div class="divide-y divide-slate-100">
            <?php foreach ($stock_alerts['out_of_stock'] as $it): ?>
            <div class="px-5 py-2.5 flex items-center justify-between">
                <span class="text-sm text-slate-700"><?php echo htmlspecialchars($it['name']); ?></span>
                <span class="px-2 py-0.5 bg-red-100 text-red-600 text-xs font-semibold rounded-full">Out of stock</span>
            </div>
            <?php endforeach; ?>
            <?php foreach ($stock_alerts['low_stock'] as $it): ?>
            <div class="px-5 py-2.5 flex items-center justify-between">
                <span class="text-sm text-slate-700"><?php echo htmlspecialchars($it['name']); ?></span>
                <span class="px-2 py-0.5 bg-amber-100 text-amber-700 text-xs font-semibold rounded-full"><?php echo $it['quantity_in_stock']; ?> left</span>
            </div>
            <?php endforeach; ?>
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
