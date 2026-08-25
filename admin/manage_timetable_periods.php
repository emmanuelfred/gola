<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('timetable');
require_once 'includes/timetable_helper.php';
$page_title = "Timetable Periods";

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add_period') {
        $name  = trim($_POST['period_name'] ?? '');
        $start = $_POST['start_time'] ?? '';
        $end   = $_POST['end_time'] ?? '';
        $is_break = isset($_POST['is_break']) ? 1 : 0;

        if (!$name || !$start || !$end) {
            $error = 'Period name, start time, and end time are all required.';
        } else {
            $next_order = ($conn->query("SELECT COALESCE(MAX(display_order),0)+1 as n FROM timetable_periods")->fetch_assoc()['n']);
            $stmt = $conn->prepare("INSERT INTO timetable_periods (period_name, start_time, end_time, is_break, display_order) VALUES (?,?,?,?,?)");
            $stmt->bind_param("sssii", $name, $start, $end, $is_break, $next_order);
            if ($stmt->execute()) {
                $success = "Period <strong>" . htmlspecialchars($name) . "</strong> added.";
            } else {
                $error = 'Failed to add period: ' . $conn->error;
            }
        }
    }

    if ($_POST['action'] === 'edit_period') {
        $id    = intval($_POST['period_id'] ?? 0);
        $name  = trim($_POST['period_name'] ?? '');
        $start = $_POST['start_time'] ?? '';
        $end   = $_POST['end_time'] ?? '';
        $is_break = isset($_POST['is_break']) ? 1 : 0;

        if (!$id || !$name || !$start || !$end) {
            $error = 'Period name, start time, and end time are all required.';
        } else {
            $stmt = $conn->prepare("UPDATE timetable_periods SET period_name=?, start_time=?, end_time=?, is_break=? WHERE id=?");
            $stmt->bind_param("sssii", $name, $start, $end, $is_break, $id);
            $stmt->execute();
            $success = "Period updated.";
        }
    }

    if ($_POST['action'] === 'delete_period' && hasPermission('admin')) {
        $id = intval($_POST['period_id'] ?? 0);
        $used = $conn->query("SELECT COUNT(*) as c FROM timetable_slots WHERE period_id=$id")->fetch_assoc()['c'];
        if ($used > 0) {
            $error = "Cannot delete — $used class timetable slot(s) use this period.";
        } else {
            $conn->query("DELETE FROM timetable_periods WHERE id=$id");
            $success = "Period deleted.";
        }
    }
}

$periods = getTimetablePeriods($conn);
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
        <h1 class="text-2xl font-bold text-slate-900">Timetable Periods</h1>
        <p class="text-slate-500 text-sm mt-1 max-w-2xl">The daily period structure — shared by every class's timetable. Set this up once here, then build each class's actual weekly grid in <a href="manage_timetable.php" class="text-primary font-semibold hover:underline">Class Timetables</a>.</p>
    </div>
    <button onclick="document.getElementById('addModal').classList.remove('hidden')"
        class="inline-flex items-center gap-2 bg-gold text-primary px-5 py-3 rounded-xl font-bold hover:bg-gold/90 shadow-sm flex-shrink-0">
        <span class="material-symbols-outlined">add_circle</span>New Period
    </button>
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

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                <th class="px-5 py-3">Period</th>
                <th class="px-5 py-3">Time</th>
                <th class="px-5 py-3">Type</th>
                <th class="px-5 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach ($periods as $p): ?>
            <tr class="hover:bg-slate-50">
                <td class="px-5 py-3 font-semibold text-slate-800"><?php echo htmlspecialchars($p['period_name']); ?></td>
                <td class="px-5 py-3 text-slate-600"><?php echo date('g:i A', strtotime($p['start_time'])); ?> – <?php echo date('g:i A', strtotime($p['end_time'])); ?></td>
                <td class="px-5 py-3">
                    <?php if ($p['is_break']): ?>
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 text-xs font-semibold rounded-full">Break</span>
                    <?php else: ?>
                    <span class="px-2 py-0.5 bg-primary/10 text-primary text-xs font-semibold rounded-full">Class Period</span>
                    <?php endif; ?>
                </td>
                <td class="px-5 py-3 text-right">
                    <button onclick='openEditModal(<?php echo json_encode($p); ?>)' class="text-primary hover:underline text-xs font-semibold mr-3">Edit</button>
                    <?php if (hasPermission('admin')): ?>
                    <form method="POST" class="inline" onsubmit="return confirm('Delete this period?')">
                        <input type="hidden" name="action" value="delete_period">
                        <input type="hidden" name="period_id" value="<?php echo $p['id']; ?>">
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

<!-- Add Period Modal -->
<div id="addModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl p-8 max-w-md w-full shadow-2xl">
    <h2 class="text-xl font-bold text-slate-900 mb-5 flex items-center gap-2"><span class="material-symbols-outlined text-gold">add_circle</span>Add Period</h2>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="add_period">
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Period Name <span class="text-red-500">*</span></label>
            <input type="text" name="period_name" required placeholder="e.g. Period 9, Assembly" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Start Time <span class="text-red-500">*</span></label>
                <input type="time" name="start_time" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
            <div><label class="text-xs font-semibold text-slate-600 mb-1 block">End Time <span class="text-red-500">*</span></label>
                <input type="time" name="end_time" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        </div>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="is_break" class="rounded text-gold focus:ring-gold">
            <span class="text-sm font-semibold text-slate-700">This is a break / non-class period</span>
        </label>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 bg-gold text-primary py-3 rounded-xl font-bold hover:bg-gold/90">Add</button>
            <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl font-semibold hover:bg-slate-200">Cancel</button>
        </div>
    </form>
</div>
</div>

<!-- Edit Period Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl p-8 max-w-md w-full shadow-2xl">
    <h2 class="text-xl font-bold text-slate-900 mb-5 flex items-center gap-2"><span class="material-symbols-outlined text-gold">edit</span>Edit Period</h2>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="edit_period">
        <input type="hidden" name="period_id" id="editId">
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Period Name <span class="text-red-500">*</span></label>
            <input type="text" name="period_name" id="editName" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Start Time <span class="text-red-500">*</span></label>
                <input type="time" name="start_time" id="editStart" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
            <div><label class="text-xs font-semibold text-slate-600 mb-1 block">End Time <span class="text-red-500">*</span></label>
                <input type="time" name="end_time" id="editEnd" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        </div>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="is_break" id="editIsBreak" class="rounded text-gold focus:ring-gold">
            <span class="text-sm font-semibold text-slate-700">This is a break / non-class period</span>
        </label>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 bg-gold text-primary py-3 rounded-xl font-bold hover:bg-gold/90">Save</button>
            <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl font-semibold hover:bg-slate-200">Cancel</button>
        </div>
    </form>
</div>
</div>

<script>
function openEditModal(p) {
    document.getElementById('editId').value = p.id;
    document.getElementById('editName').value = p.period_name;
    document.getElementById('editStart').value = p.start_time.substring(0,5);
    document.getElementById('editEnd').value = p.end_time.substring(0,5);
    document.getElementById('editIsBreak').checked = p.is_break == 1;
    document.getElementById('editModal').classList.remove('hidden');
}
['addModal','editModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) { if (e.target === this) this.classList.add('hidden'); });
});
</script>
</body>
</html>
