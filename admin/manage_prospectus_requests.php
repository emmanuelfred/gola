<?php
require_once 'auth_check.php';
$page_title = "Prospectus Requests";

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_sent'])) {
    $id = intval($_POST['id']);
    $notes = mysqli_real_escape_string($conn, trim($_POST['notes'] ?? ''));
    $conn->query("UPDATE prospectus_requests SET status='Sent', admin_notes='$notes', sent_at=NOW() WHERE id=$id");
    logActivity('mark_prospectus_sent', "Marked prospectus request #$id as sent");
    $success = 'Marked as sent.';
}
if (isset($_GET['delete']) && hasPermission('admin')) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM prospectus_requests WHERE id=$id");
    $success = 'Request deleted.';
}

$filter = $_GET['status'] ?? '';
$where  = $filter ? "WHERE status='".mysqli_real_escape_string($conn,$filter)."'" : '';
$requests = $conn->query("SELECT * FROM prospectus_requests $where ORDER BY requested_at DESC");
$counts = [];
$cq = $conn->query("SELECT status, COUNT(*) as c FROM prospectus_requests GROUP BY status");
while ($r = $cq->fetch_assoc()) $counts[$r['status']] = $r['c'];
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
        <h1 class="text-2xl font-bold text-slate-900">Prospectus Requests</h1>
        <p class="text-slate-500 text-sm mt-1">Parents who requested the school prospectus. Send it manually then mark as sent.</p>
    </div>
</div>

<?php if ($success): ?>
<div class="mb-5 p-4 bg-green-50 border border-green-200 rounded-xl text-green-800 text-sm flex gap-2 items-center">
    <span class="material-symbols-outlined text-green-600">check_circle</span><?php echo $success; ?>
</div>
<?php endif; ?>

<!-- Status stats -->
<div class="grid grid-cols-3 gap-4 mb-6">
    <?php foreach(['Pending'=>'bg-amber-50 text-amber-700 border-amber-200','Sent'=>'bg-green-50 text-green-700 border-green-200','Failed'=>'bg-red-50 text-red-700 border-red-200'] as $st=>$cls): ?>
    <a href="?status=<?php echo $st; ?>" class="rounded-xl border p-4 text-center hover:shadow-md transition-all <?php echo $cls; ?> <?php echo $filter===$st?'ring-2 ring-offset-1 ring-current':''; ?>">
        <p class="text-2xl font-black"><?php echo $counts[$st]??0; ?></p>
        <p class="text-xs font-bold uppercase mt-1"><?php echo $st; ?></p>
    </a>
    <?php endforeach; ?>
</div>

<?php if ($filter): ?>
<a href="manage_prospectus_requests.php" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-gold mb-4">
    <span class="material-symbols-outlined text-sm">close</span>Clear filter — show all
</a>
<?php endif; ?>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b text-xs">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-500 uppercase">Parent / Student</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-500 uppercase">Contact</th>
                    <th class="px-4 py-3 text-center font-semibold text-slate-500 uppercase">Grade</th>
                    <th class="px-4 py-3 text-center font-semibold text-slate-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-center font-semibold text-slate-500 uppercase">Date</th>
                    <th class="px-4 py-3 text-center font-semibold text-slate-500 uppercase">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (!$requests || $requests->num_rows === 0): ?>
                <tr><td colspan="6" class="px-4 py-12 text-center text-slate-400">
                    <span class="material-symbols-outlined text-4xl block mb-2">description</span>No requests found.
                </td></tr>
                <?php else: while($req = $requests->fetch_assoc()):
                    $sc = ['Pending'=>'bg-amber-100 text-amber-700','Sent'=>'bg-green-100 text-green-700','Failed'=>'bg-red-100 text-red-700'];
                    $cc = $sc[$req['status']] ?? 'bg-slate-100 text-slate-600';
                ?>
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3">
                        <p class="font-semibold text-slate-900"><?php echo htmlspecialchars($req['parent_name']); ?></p>
                        <p class="text-xs text-slate-400">Student: <?php echo htmlspecialchars($req['student_name']); ?></p>
                    </td>
                    <td class="px-4 py-3">
                        <a href="mailto:<?php echo htmlspecialchars($req['email']); ?>" class="text-gold hover:underline text-xs font-semibold block"><?php echo htmlspecialchars($req['email']); ?></a>
                        <?php if ($req['phone']): ?><p class="text-xs text-slate-400"><?php echo htmlspecialchars($req['phone']); ?></p><?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-center text-xs font-bold text-slate-700"><?php echo htmlspecialchars($req['grade_level']); ?></td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-bold <?php echo $cc; ?>"><?php echo $req['status']; ?></span>
                        <?php if ($req['sent_at']): ?><p class="text-xs text-slate-400 mt-0.5"><?php echo date('d M', strtotime($req['sent_at'])); ?></p><?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-center text-xs text-slate-400"><?php echo date('d M Y', strtotime($req['requested_at'])); ?></td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <?php if ($req['status'] === 'Pending'): ?>
                            <button onclick="openMarkSent(<?php echo $req['id']; ?>, '<?php echo htmlspecialchars(addslashes($req['email'])); ?>')"
                                class="px-3 py-1.5 bg-green-600 text-white text-xs font-bold rounded-lg hover:bg-green-700 transition-all">
                                Mark Sent
                            </button>
                            <?php endif; ?>
                            <a href="mailto:<?php echo htmlspecialchars($req['email']); ?>?subject=GOLA+School+Prospectus&body=Dear+<?php echo urlencode($req['parent_name']); ?>,%0A%0AThank+you+for+your+interest+in+Goodness+Omogo+Leadership+Academy.+Please+find+attached+our+school+prospectus.%0A%0AKind+regards,%0AGOLA+Admissions+Team"
                                class="px-3 py-1.5 bg-primary/10 text-primary text-xs font-bold rounded-lg hover:bg-primary/20 transition-all">
                                Open Email
                            </a>
                            <a href="?delete=<?php echo $req['id']; ?>" onclick="return confirm('Delete this request?')"
                                class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all">
                                <span class="material-symbols-outlined text-sm">delete</span>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

</main>
</div>
</div>

<!-- Mark Sent Modal -->
<div id="markSentModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl p-7 max-w-md w-full shadow-2xl">
    <h2 class="font-bold text-slate-900 text-lg mb-1">Mark Prospectus as Sent</h2>
    <p id="markSentEmail" class="text-sm text-slate-500 mb-4"></p>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="mark_sent" value="1">
        <input type="hidden" name="id" id="markSentId">
        <div>
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1 block">Admin Notes (optional)</label>
            <textarea name="notes" rows="2" placeholder="e.g. Sent via Gmail on 28 May 2026"
                class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold px-4 py-2.5"></textarea>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="flex-1 bg-green-600 text-white py-3 rounded-xl font-bold hover:bg-green-700">Confirm Sent</button>
            <button type="button" onclick="document.getElementById('markSentModal').classList.add('hidden')"
                class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl font-semibold hover:bg-slate-200">Cancel</button>
        </div>
    </form>
</div>
</div>
<script>
function openMarkSent(id, email) {
    document.getElementById('markSentId').value = id;
    document.getElementById('markSentEmail').textContent = 'Email: ' + email;
    document.getElementById('markSentModal').classList.remove('hidden');
}
document.getElementById('markSentModal').addEventListener('click', function(e) {
    if (e.target===this) this.classList.add('hidden');
});
</script>
</body>
</html>
