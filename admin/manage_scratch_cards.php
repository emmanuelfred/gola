<?php
require_once 'auth_check.php';
$page_title = "Scratch Cards";

// Handle Generate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'generate') {
        $count = intval($_POST['count'] ?? 10);
        $max_uses = intval($_POST['max_uses'] ?? 5);
        if ($count < 1 || $count > 500) $count = 10;
        if ($max_uses < 1 || $max_uses > 20) $max_uses = 5;

        $generated = 0;
        for ($i = 0; $i < $count; $i++) {
            $pin = 'GOLA-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4)) . '-'
                 . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4)) . '-'
                 . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
            $serial = 'SC-' . str_pad($conn->query("SELECT COUNT(*) as c FROM scratch_cards")->fetch_assoc()['c'] + $i + 1, 6, '0', STR_PAD_LEFT);
            
            $stmt = $conn->prepare("INSERT INTO scratch_cards (pin_code, serial_number, max_uses, is_activated, created_by) VALUES (?, ?, ?, 0, ?)");
            $stmt->bind_param("ssii", $pin, $serial, $max_uses, $admin_id);
            if ($stmt->execute()) $generated++;
        }
        logActivity('generate_cards', "Generated $generated scratch cards (max $max_uses uses each)");
        $success = "Successfully generated $generated scratch cards.";
    }
    
    if ($_POST['action'] === 'activate' && isset($_POST['card_ids'])) {
        $ids = array_map('intval', $_POST['card_ids']);
        $placeholders = implode(',', $ids);
        $conn->query("UPDATE scratch_cards SET is_activated = 1 WHERE id IN ($placeholders)");
        logActivity('activate_cards', "Activated " . count($ids) . " scratch cards");
        $success = count($ids) . " cards activated successfully.";
    }

    if ($_POST['action'] === 'deactivate' && isset($_POST['card_ids'])) {
        $ids = array_map('intval', $_POST['card_ids']);
        $placeholders = implode(',', $ids);
        $conn->query("UPDATE scratch_cards SET is_activated = 0 WHERE id IN ($placeholders)");
        $success = count($ids) . " cards deactivated.";
    }

    if ($_POST['action'] === 'delete' && isset($_POST['card_ids']) && hasPermission('admin')) {
        $ids = array_map('intval', $_POST['card_ids']);
        $placeholders = implode(',', $ids);
        $conn->query("DELETE FROM scratch_cards WHERE id IN ($placeholders)");
        $success = count($ids) . " cards deleted.";
    }
}

// Filters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where = "1=1";
if ($filter === 'activated') $where = "sc.is_activated = 1";
elseif ($filter === 'inactive') $where = "sc.is_activated = 0";
elseif ($filter === 'used_up') $where = "sc.times_used >= sc.max_uses";
elseif ($filter === 'available') $where = "sc.is_activated = 1 AND sc.times_used < sc.max_uses";

$cards = $conn->query("SELECT sc.*, au.full_name as creator FROM scratch_cards sc LEFT JOIN admin_users au ON sc.created_by = au.id WHERE $where ORDER BY sc.created_at DESC");

// Stats
$total = $conn->query("SELECT COUNT(*) as c FROM scratch_cards")->fetch_assoc()['c'];
$activated = $conn->query("SELECT COUNT(*) as c FROM scratch_cards WHERE is_activated=1")->fetch_assoc()['c'];
$used_up = $conn->query("SELECT COUNT(*) as c FROM scratch_cards WHERE times_used >= max_uses")->fetch_assoc()['c'];
$available = $conn->query("SELECT COUNT(*) as c FROM scratch_cards WHERE is_activated=1 AND times_used < max_uses")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html class="scroll-smooth" lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | G.O.L.A</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>tailwind.config={theme:{extend:{colors:{primary:"#0A2E4D",gold:"#C5A059"},fontFamily:{sans:["Inter","sans-serif"]}}}};</script>
    <style>
        .sidebar-link.active{background:linear-gradient(90deg,rgba(197,160,89,0.1) 0%,transparent 100%);border-left:3px solid #C5A059;color:#C5A059;}
        @media print { .no-print{display:none!important;} .print-card{page-break-inside:avoid;border:2px solid #002C47;padding:20px;margin:10px;} }
    </style>
</head>
<body class="bg-slate-50 font-sans">
<div class="flex h-screen overflow-hidden">
    <?php include 'admin_sidebar.php'; ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?php include 'admin_topbar.php'; ?>
        <main class="flex-1 overflow-y-auto p-8">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">Scratch Card Management</h1>
                    <p class="text-slate-600">Generate, activate, and manage result checker scratch cards</p>
                </div>
                <button onclick="document.getElementById('generateModal').classList.remove('hidden')" class="inline-flex items-center gap-2 bg-gold text-primary px-5 py-3 rounded-lg font-semibold hover:bg-gold/90 transition-all">
                    <span class="material-symbols-outlined">add_circle</span>
                    Generate Cards
                </button>
            </div>

            <?php if (isset($success)): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white rounded-xl p-5 border border-slate-200"><p class="text-2xl font-bold text-primary"><?php echo $total; ?></p><p class="text-xs text-slate-500 font-semibold uppercase">Total Cards</p></div>
                <div class="bg-white rounded-xl p-5 border border-slate-200"><p class="text-2xl font-bold text-green-600"><?php echo $activated; ?></p><p class="text-xs text-slate-500 font-semibold uppercase">Activated</p></div>
                <div class="bg-white rounded-xl p-5 border border-slate-200"><p class="text-2xl font-bold text-blue-600"><?php echo $available; ?></p><p class="text-xs text-slate-500 font-semibold uppercase">Available</p></div>
                <div class="bg-white rounded-xl p-5 border border-slate-200"><p class="text-2xl font-bold text-red-600"><?php echo $used_up; ?></p><p class="text-xs text-slate-500 font-semibold uppercase">Used Up</p></div>
            </div>

            <!-- Filter Tabs -->
            <div class="flex gap-2 mb-6 flex-wrap">
                <?php foreach (['all'=>'All','activated'=>'Activated','inactive'=>'Inactive','available'=>'Available','used_up'=>'Used Up'] as $k=>$v): ?>
                <a href="?filter=<?php echo $k; ?>" class="px-4 py-2 rounded-lg text-sm font-semibold transition-all <?php echo $filter===$k ? 'bg-primary text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'; ?>"><?php echo $v; ?></a>
                <?php endforeach; ?>
            </div>

            <!-- Cards Table -->
            <form method="POST" id="cardsForm">
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <div class="p-4 border-b border-slate-200 flex gap-2 flex-wrap">
                        <button type="submit" name="action" value="activate" class="text-xs bg-green-50 text-green-700 px-3 py-1.5 rounded font-semibold hover:bg-green-100">Activate Selected</button>
                        <button type="submit" name="action" value="deactivate" class="text-xs bg-yellow-50 text-yellow-700 px-3 py-1.5 rounded font-semibold hover:bg-yellow-100">Deactivate Selected</button>
                        <button type="button" onclick="printSelected()" class="text-xs bg-blue-50 text-blue-700 px-3 py-1.5 rounded font-semibold hover:bg-blue-100">Print Selected</button>
                        <?php if (hasPermission('admin')): ?>
                        <button type="submit" name="action" value="delete" class="text-xs bg-red-50 text-red-700 px-3 py-1.5 rounded font-semibold hover:bg-red-100" onclick="return confirm('Delete selected cards?')">Delete Selected</button>
                        <?php endif; ?>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-4 py-3 text-left"><input type="checkbox" id="selectAll" onchange="toggleAll()" class="rounded border-slate-300 text-gold focus:ring-gold"></th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Serial</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">PIN Code</th>
                                    <th class="px-4 py-3 text-center font-semibold text-slate-600">Max Uses</th>
                                    <th class="px-4 py-3 text-center font-semibold text-slate-600">Used</th>
                                    <th class="px-4 py-3 text-center font-semibold text-slate-600">Remaining</th>
                                    <th class="px-4 py-3 text-center font-semibold text-slate-600">Status</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Created</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if ($cards->num_rows == 0): ?>
                                <tr><td colspan="8" class="px-4 py-12 text-center text-slate-500">No scratch cards found.</td></tr>
                                <?php endif; ?>
                                <?php while ($c = $cards->fetch_assoc()):
                                    $remaining = max(0, $c['max_uses'] - $c['times_used']);
                                    $is_used_up = $c['times_used'] >= $c['max_uses'];
                                ?>
                                <tr class="hover:bg-slate-50" data-pin="<?php echo htmlspecialchars($c['pin_code']); ?>" data-serial="<?php echo htmlspecialchars($c['serial_number']); ?>" data-uses="<?php echo $c['max_uses']; ?>">
                                    <td class="px-4 py-3"><input type="checkbox" name="card_ids[]" value="<?php echo $c['id']; ?>" class="card-checkbox rounded border-slate-300 text-gold focus:ring-gold"></td>
                                    <td class="px-4 py-3 font-mono text-xs text-slate-600"><?php echo htmlspecialchars($c['serial_number']); ?></td>
                                    <td class="px-4 py-3 font-mono font-bold text-primary tracking-wider"><?php echo htmlspecialchars($c['pin_code']); ?></td>
                                    <td class="px-4 py-3 text-center"><?php echo $c['max_uses']; ?></td>
                                    <td class="px-4 py-3 text-center"><?php echo $c['times_used']; ?></td>
                                    <td class="px-4 py-3 text-center font-bold <?php echo $is_used_up ? 'text-red-600' : 'text-green-600'; ?>"><?php echo $remaining; ?></td>
                                    <td class="px-4 py-3 text-center">
                                        <?php if ($is_used_up): ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">Used Up</span>
                                        <?php elseif ($c['is_activated']): ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Active</span>
                                        <?php else: ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-500"><?php echo date('M j, Y', strtotime($c['created_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>

        </main>
    </div>
</div>

<!-- Generate Modal -->
<div id="generateModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-8 max-w-md w-full">
        <h2 class="text-xl font-bold text-slate-900 mb-4">Generate Scratch Cards</h2>
        <form method="POST">
            <input type="hidden" name="action" value="generate">
            <div class="space-y-4">
                <div>
                    <label class="text-sm font-semibold text-slate-600">Number of Cards</label>
                    <input type="number" name="count" value="10" min="1" max="500" class="mt-1 w-full border-slate-200 rounded-lg focus:ring-gold focus:border-gold">
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-600">Max Uses per Card</label>
                    <input type="number" name="max_uses" value="5" min="1" max="20" class="mt-1 w-full border-slate-200 rounded-lg focus:ring-gold focus:border-gold">
                    <p class="text-xs text-slate-500 mt-1">Default is 5 uses per card</p>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="submit" class="flex-1 bg-gold text-primary py-3 rounded-lg font-bold hover:bg-gold/90">Generate</button>
                <button type="button" onclick="document.getElementById('generateModal').classList.add('hidden')" class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-lg font-semibold hover:bg-slate-200">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Printable Card Template (hidden) -->
<div id="printArea" class="hidden">
    <style>
        .print-card-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; padding: 20px; }
        .print-card { border: 2px solid #002C47; border-radius: 12px; padding: 20px; text-align: center; page-break-inside: avoid; }
        .print-card h3 { color: #002C47; font-size: 14px; font-weight: 800; margin-bottom: 4px; }
        .print-card .motto { color: #C5A059; font-size: 9px; letter-spacing: 2px; margin-bottom: 12px; }
        .print-card .pin-label { font-size: 10px; color: #666; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
        .print-card .pin { font-family: monospace; font-size: 18px; font-weight: 900; color: #002C47; letter-spacing: 3px; background: #f1f5f9; padding: 8px 12px; border-radius: 8px; margin-bottom: 10px; }
        .print-card .info { font-size: 9px; color: #666; line-height: 1.5; }
        .print-card .serial { font-size: 8px; color: #999; margin-top: 8px; }
    </style>
</div>

<script>
function toggleAll() {
    document.querySelectorAll('.card-checkbox').forEach(cb => cb.checked = document.getElementById('selectAll').checked);
}

function printSelected() {
    const checked = document.querySelectorAll('.card-checkbox:checked');
    if (checked.length === 0) { alert('Please select cards to print.'); return; }
    
    let html = '<html><head><title>Scratch Cards - GOLA</title><style>';
    html += 'body{font-family:Inter,sans-serif;}.grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;padding:20px;}';
    html += '.card{border:2px solid #002C47;border-radius:12px;padding:20px;text-align:center;page-break-inside:avoid;}';
    html += '.card h3{color:#002C47;font-size:14px;font-weight:800;margin:0 0 2px;}.card .motto{color:#C5A059;font-size:9px;letter-spacing:2px;margin-bottom:14px;}';
    html += '.card .pin{font-family:monospace;font-size:18px;font-weight:900;color:#002C47;letter-spacing:3px;background:#f1f5f9;padding:8px 12px;border-radius:8px;margin:8px 0;}';
    html += '.card .lbl{font-size:10px;color:#666;text-transform:uppercase;letter-spacing:1px;}.card .info{font-size:9px;color:#666;line-height:1.6;margin-top:10px;}.card .serial{font-size:8px;color:#999;margin-top:8px;}';
    html += '@media print{@page{margin:10mm;}}</style></head><body><div class="grid">';
    
    checked.forEach(cb => {
        const row = cb.closest('tr');
        const pin = row.dataset.pin;
        const serial = row.dataset.serial;
        const uses = row.dataset.uses;
        html += '<div class="card">';
        html += '<h3>GOODNESS OMOGO LEADERSHIP ACADEMY</h3>';
        html += '<div class="motto">TO LEARN • TO GROW • TO LEAD</div>';
        html += '<div class="lbl">Result Checker Scratch Card</div>';
        html += '<div class="pin">' + pin + '</div>';
        html += '<div class="info">This card allows up to <strong>' + uses + '</strong> result checks.<br>Scratch gently to reveal PIN. Do not share your PIN.</div>';
        html += '<div class="serial">' + serial + '</div>';
        html += '</div>';
    });
    
    html += '</div></body></html>';
    const w = window.open('', '_blank');
    w.document.write(html);
    w.document.close();
    w.focus();
    setTimeout(() => w.print(), 500);
}
</script>
</body>
</html>
