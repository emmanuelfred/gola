<?php
require_once 'auth_check.php';

$page_title = "Manage Sessions";
$success = '';
$error   = '';

// ── Handle actions ─────────────────────────────────────────────────────────────

// Add new session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'add_session') {
        $name  = trim($_POST['session_name'] ?? '');
        $start = trim($_POST['start_date'] ?? '');
        $end   = trim($_POST['end_date'] ?? '');
        $curr  = isset($_POST['is_current']) ? 1 : 0;
        
        if (!$name || !$start || !$end) {
            $error = 'Please fill in all required session fields.';
        } else {
            if ($curr) {
                $conn->query("UPDATE academic_sessions SET is_current=0");
            }
            $stmt = $conn->prepare("INSERT INTO academic_sessions (session_name, start_date, end_date, is_current) VALUES (?,?,?,?)");
            $stmt->bind_param("sssi", $name, $start, $end, $curr);
            if ($stmt->execute()) {
                $new_sess_id = $conn->insert_id;
                // Auto-create three terms for this session
                $term_defs = [
                    ['First Term',  '09-01', '12-15'],
                    ['Second Term', '01-06', '04-15'],
                    ['Third Term',  '04-28', '08-15'],
                ];
                $year = substr($name, 0, 4);
                $next = intval($year) + 1;
                foreach ($term_defs as $td) {
                    $t_start = ($td[0]==='First Term' ? $year : $next).'-'.$td[1];
                    $t_end   = ($td[0]==='Third Term'  ? $next : ($td[0]==='Second Term' ? $next : $year)).'-'.$td[2];
                    $tstmt = $conn->prepare("INSERT IGNORE INTO terms (term_name, session_id, start_date, end_date, is_current) VALUES (?,?,?,?,0)");
                    $tstmt->bind_param("siss", $td[0], $new_sess_id, $t_start, $t_end);
                    $tstmt->execute();
                }
                logActivity('add_session', "Added session: $name");
                $success = "Session <strong>$name</strong> added with three terms auto-created.";
            } else {
                $error = 'Failed to add session: '.$conn->error;
            }
        }
    }
    
    if ($_POST['action'] === 'set_current_session') {
        $id = intval($_POST['session_id'] ?? 0);
        $conn->query("UPDATE academic_sessions SET is_current=0");
        $conn->query("UPDATE academic_sessions SET is_current=1 WHERE id=$id");
        logActivity('set_current_session', "Set session id=$id as current");
        $success = 'Current session updated.';
    }
    
    if ($_POST['action'] === 'add_term') {
        $sess_id   = intval($_POST['sess_id'] ?? 0);
        $term_name = trim($_POST['term_name'] ?? '');
        $t_start   = trim($_POST['term_start'] ?? '');
        $t_end     = trim($_POST['term_end']   ?? '');
        $t_curr    = isset($_POST['term_is_current']) ? 1 : 0;
        
        if (!$sess_id || !$term_name || !$t_start || !$t_end) {
            $error = 'All term fields are required.';
        } else {
            if ($t_curr) {
                $conn->query("UPDATE terms SET is_current=0 WHERE session_id=$sess_id");
            }
            $stmt = $conn->prepare("INSERT INTO terms (term_name, session_id, start_date, end_date, is_current) VALUES (?,?,?,?,?)");
            $stmt->bind_param("sissi", $term_name, $sess_id, $t_start, $t_end, $t_curr);
            if ($stmt->execute()) {
                $success = "Term added successfully.";
            } else {
                $error = 'Failed: '.$conn->error;
            }
        }
    }
    
    if ($_POST['action'] === 'set_current_term') {
        $tid = intval($_POST['term_id'] ?? 0);
        $sid = intval($_POST['session_id'] ?? 0);
        $conn->query("UPDATE terms SET is_current=0");
        $conn->query("UPDATE terms SET is_current=1 WHERE id=$tid");
        // Also set session current
        $conn->query("UPDATE academic_sessions SET is_current=0");
        $conn->query("UPDATE academic_sessions SET is_current=1 WHERE id=$sid");
        logActivity('set_current_term', "Set term id=$tid as current");
        $success = 'Current term updated.';
    }
    
    if ($_POST['action'] === 'delete_session' && hasPermission('super_admin')) {
        $id = intval($_POST['session_id'] ?? 0);
        $check = $conn->query("SELECT COUNT(*) as c FROM results WHERE session_id=$id")->fetch_assoc();
        if ($check['c'] > 0) {
            $error = 'Cannot delete: this session has '.$check['c'].' result records.';
        } else {
            $conn->query("DELETE FROM academic_sessions WHERE id=$id");
            $success = 'Session deleted.';
        }
    }
}

// ── Fetch all sessions with their terms ────────────────────────────────────────
$sessions = $conn->query("SELECT * FROM academic_sessions ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$terms_all = $conn->query("SELECT * FROM terms ORDER BY session_id DESC, id ASC")->fetch_all(MYSQLI_ASSOC);
// Group terms by session
$terms_by_session = [];
foreach ($terms_all as $t) {
    $terms_by_session[$t['session_id']][] = $t;
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

<div class="mb-8 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Academic Sessions & Terms</h1>
        <p class="text-slate-500 text-sm mt-1">Manage school years and terms. The current session/term controls what appears on result forms.</p>
    </div>
    <button onclick="document.getElementById('addSessionModal').classList.remove('hidden')"
        class="inline-flex items-center gap-2 bg-gold text-primary px-5 py-3 rounded-xl font-bold hover:bg-gold/90 transition-all shadow-sm">
        <span class="material-symbols-outlined">add_circle</span>New Session
    </button>
</div>

<!-- Alerts -->
<?php if ($success): ?>
<div class="mb-5 p-4 bg-green-50 border border-green-200 rounded-xl flex items-start gap-3">
    <span class="material-symbols-outlined text-green-600">check_circle</span>
    <p class="text-green-800 text-sm"><?php echo $success; ?></p>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl flex items-start gap-3">
    <span class="material-symbols-outlined text-red-600">error</span>
    <p class="text-red-800 text-sm"><?php echo $error; ?></p>
</div>
<?php endif; ?>

<!-- Sessions List -->
<?php if (empty($sessions)): ?>
<div class="bg-white rounded-xl border border-slate-200 p-16 text-center">
    <span class="material-symbols-outlined text-6xl text-slate-200 block mb-3">calendar_today</span>
    <h3 class="font-bold text-slate-800 mb-2">No sessions yet</h3>
    <p class="text-slate-500 text-sm mb-4">Add your first academic session to get started.</p>
    <button onclick="document.getElementById('addSessionModal').classList.remove('hidden')"
        class="inline-flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-primary/90">
        <span class="material-symbols-outlined text-sm">add</span>Add Session
    </button>
</div>
<?php else: ?>
<div class="space-y-6">
    <?php foreach ($sessions as $sess): 
        $sess_terms = $terms_by_session[$sess['id']] ?? [];
    ?>
    <div class="bg-white rounded-xl border <?php echo $sess['is_current'] ? 'border-gold ring-2 ring-gold/20' : 'border-slate-200'; ?> overflow-hidden">
        <!-- Session Header -->
        <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 <?php echo $sess['is_current'] ? 'bg-gold/5' : 'bg-slate-50'; ?> border-b border-slate-200">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 <?php echo $sess['is_current'] ? 'bg-gold text-primary' : 'bg-slate-200 text-slate-600'; ?> rounded-xl flex items-center justify-center">
                    <span class="material-symbols-outlined text-lg">calendar_month</span>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-bold text-slate-900 text-lg"><?php echo htmlspecialchars($sess['session_name']); ?> Session</h2>
                        <?php if ($sess['is_current']): ?>
                        <span class="px-2 py-0.5 bg-gold text-primary text-xs font-bold rounded-full">CURRENT</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-slate-500">
                        <?php echo date('M j, Y', strtotime($sess['start_date'])); ?> —
                        <?php echo date('M j, Y', strtotime($sess['end_date'])); ?>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <?php if (!$sess['is_current']): ?>
                <form method="POST" class="inline">
                    <input type="hidden" name="action"     value="set_current_session">
                    <input type="hidden" name="session_id" value="<?php echo $sess['id']; ?>">
                    <button type="submit" class="px-3 py-1.5 bg-primary text-white text-xs font-semibold rounded-lg hover:bg-primary/90 transition-all">
                        Set as Current
                    </button>
                </form>
                <?php endif; ?>
                <button onclick="openAddTermModal(<?php echo $sess['id']; ?>, '<?php echo htmlspecialchars($sess['session_name']); ?>')"
                    class="px-3 py-1.5 bg-slate-100 text-slate-700 text-xs font-semibold rounded-lg hover:bg-slate-200 transition-all inline-flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">add</span>Add Term
                </button>
                <?php if (hasPermission('super_admin') && !$sess['is_current']): ?>
                <form method="POST" class="inline" onsubmit="return confirm('Delete this session? This cannot be undone.')">
                    <input type="hidden" name="action"     value="delete_session">
                    <input type="hidden" name="session_id" value="<?php echo $sess['id']; ?>">
                    <button type="submit" class="px-3 py-1.5 bg-red-50 text-red-600 text-xs font-semibold rounded-lg hover:bg-red-100 transition-all">
                        Delete
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Terms for this session -->
        <div class="p-4">
            <?php if (empty($sess_terms)): ?>
            <p class="text-center text-sm text-slate-400 py-4">No terms — click "Add Term" above.</p>
            <?php else: ?>
            <div class="grid md:grid-cols-3 gap-3">
                <?php foreach ($sess_terms as $term):
                    $t_is_current = $term['is_current'];
                    $t_today = (strtotime($term['start_date']) <= time() && time() <= strtotime($term['end_date']));
                ?>
                <div class="rounded-xl border <?php echo $t_is_current ? 'border-primary bg-primary/5' : 'border-slate-200 bg-slate-50'; ?> p-4">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <span class="text-sm font-bold <?php echo $t_is_current ? 'text-primary' : 'text-slate-700'; ?>">
                                <?php echo htmlspecialchars($term['term_name']); ?>
                            </span>
                            <?php if ($t_is_current): ?>
                            <span class="ml-2 px-1.5 py-0.5 bg-primary text-white text-xs font-bold rounded">CURRENT</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!$t_is_current): ?>
                        <form method="POST">
                            <input type="hidden" name="action"     value="set_current_term">
                            <input type="hidden" name="term_id"    value="<?php echo $term['id']; ?>">
                            <input type="hidden" name="session_id" value="<?php echo $sess['id']; ?>">
                            <button type="submit" class="text-xs text-primary hover:underline font-semibold">Set Current</button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-slate-500">
                        <?php echo date('d M Y', strtotime($term['start_date'])); ?> —
                        <?php echo date('d M Y', strtotime($term['end_date'])); ?>
                    </p>
                    <?php if ($t_today): ?>
                    <span class="mt-2 inline-block px-2 py-0.5 bg-green-100 text-green-700 text-xs font-semibold rounded-full">In Progress</span>
                    <?php elseif (time() > strtotime($term['end_date'])): ?>
                    <span class="mt-2 inline-block px-2 py-0.5 bg-slate-100 text-slate-500 text-xs font-semibold rounded-full">Completed</span>
                    <?php else: ?>
                    <span class="mt-2 inline-block px-2 py-0.5 bg-blue-100 text-blue-600 text-xs font-semibold rounded-full">Upcoming</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

</main>
</div>
</div>

<!-- Add Session Modal -->
<div id="addSessionModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl p-8 max-w-md w-full shadow-2xl">
    <h2 class="text-xl font-bold text-slate-900 mb-5 flex items-center gap-2">
        <span class="material-symbols-outlined text-gold">calendar_add_on</span>
        Add New Academic Session
    </h2>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="add_session">
        <div>
            <label class="text-xs font-semibold text-slate-600 mb-1 block">Session Name <span class="text-red-500">*</span></label>
            <input type="text" name="session_name" required placeholder="e.g. 2025/2026"
                class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"
                pattern="\d{4}/\d{4}" title="Format: YYYY/YYYY">
            <p class="text-xs text-slate-400 mt-1">Format: 2025/2026</p>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-xs font-semibold text-slate-600 mb-1 block">Start Date <span class="text-red-500">*</span></label>
                <input type="date" name="start_date" required
                    class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold">
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 mb-1 block">End Date <span class="text-red-500">*</span></label>
                <input type="date" name="end_date" required
                    class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold">
            </div>
        </div>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="is_current" class="rounded text-gold focus:ring-gold">
            <span class="text-sm font-semibold text-slate-700">Set as Current Session</span>
        </label>
        <p class="text-xs text-blue-600 bg-blue-50 rounded-lg p-3">
            <span class="material-symbols-outlined text-xs align-middle">info</span>
            Three terms will be auto-created based on the session year. You can edit their dates after.
        </p>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 bg-gold text-primary py-3 rounded-xl font-bold hover:bg-gold/90">Add Session</button>
            <button type="button" onclick="document.getElementById('addSessionModal').classList.add('hidden')"
                class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl font-semibold hover:bg-slate-200">Cancel</button>
        </div>
    </form>
</div>
</div>

<!-- Add Term Modal -->
<div id="addTermModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl p-8 max-w-md w-full shadow-2xl">
    <h2 class="text-xl font-bold text-slate-900 mb-1 flex items-center gap-2">
        <span class="material-symbols-outlined text-gold">event_note</span>
        Add Term
    </h2>
    <p id="termModalSessionLabel" class="text-sm text-slate-500 mb-5"></p>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="action"   value="add_term">
        <input type="hidden" name="sess_id"  id="termModalSessId">
        <div>
            <label class="text-xs font-semibold text-slate-600 mb-1 block">Term <span class="text-red-500">*</span></label>
            <select name="term_name" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold">
                <option value="First Term">First Term</option>
                <option value="Second Term">Second Term</option>
                <option value="Third Term">Third Term</option>
            </select>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-xs font-semibold text-slate-600 mb-1 block">Start Date <span class="text-red-500">*</span></label>
                <input type="date" name="term_start" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold">
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 mb-1 block">End Date <span class="text-red-500">*</span></label>
                <input type="date" name="term_end" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold">
            </div>
        </div>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="term_is_current" class="rounded text-gold focus:ring-gold">
            <span class="text-sm font-semibold text-slate-700">Set as Current Term</span>
        </label>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 bg-gold text-primary py-3 rounded-xl font-bold hover:bg-gold/90">Add Term</button>
            <button type="button" onclick="document.getElementById('addTermModal').classList.add('hidden')"
                class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl font-semibold hover:bg-slate-200">Cancel</button>
        </div>
    </form>
</div>
</div>

<script>
function openAddTermModal(sessId, sessName) {
    document.getElementById('termModalSessId').value = sessId;
    document.getElementById('termModalSessionLabel').textContent = 'For session: ' + sessName;
    document.getElementById('addTermModal').classList.remove('hidden');
}
// Close modals on backdrop click
['addSessionModal','addTermModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) this.classList.add('hidden');
    });
});
</script>
</body>
</html>
