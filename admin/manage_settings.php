<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('settings');
require_once '../config/database.php';
require_once 'includes/session_helper.php';
$page_title = "School Settings";

$success = '';
$error   = '';
$tab     = $_GET['tab'] ?? 'session';

// ── Session & Term actions ───────────────────────────────────────────────────
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
        $tab = 'session';
    }

    if ($_POST['action'] === 'set_current_session') {
        $id = intval($_POST['session_id'] ?? 0);
        $conn->query("UPDATE academic_sessions SET is_current=0");
        $conn->query("UPDATE academic_sessions SET is_current=1 WHERE id=$id");
        logActivity('set_current_session', "Set session id=$id as current");
        $success = 'Current session updated.';
        $tab = 'session';
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
        $tab = 'session';
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
        $tab = 'session';
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
        $tab = 'session';
    }
}

// ── Save settings ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $keys = $_POST['keys'] ?? [];
    $vals = $_POST['vals'] ?? [];
    foreach($keys as $i => $key) {
        $key = mysqli_real_escape_string($conn, $key);
        $val = mysqli_real_escape_string($conn, $vals[$i] ?? '');
        $conn->query("UPDATE school_settings SET setting_value='$val' WHERE setting_key='$key'");
    }
    logActivity('update_settings', 'Updated school settings: '.implode(', ', $keys));
    $success = 'Settings saved.';
}

// ── Upload prospectus ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_prospectus'])) {
    if (!empty($_FILES['prospectus_pdf']['name'])) {
        $ext = strtolower(pathinfo($_FILES['prospectus_pdf']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            $error = 'Prospectus must be a PDF file.';
        } elseif ($_FILES['prospectus_pdf']['size'] > 20 * 1024 * 1024) {
            $error = 'File too large — max 20MB.';
        } else {
            $upload_dir = dirname(__DIR__).'/uploads/prospectus/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);
            $filename = 'GOLA-Prospectus-'.date('Y').'.pdf';
            if (move_uploaded_file($_FILES['prospectus_pdf']['tmp_name'], $upload_dir.$filename)) {
                $fn = mysqli_real_escape_string($conn, $filename);
                $now = date('Y-m-d H:i:s');
                $conn->query("UPDATE school_settings SET setting_value='$fn' WHERE setting_key='prospectus_file'");
                $conn->query("UPDATE school_settings SET setting_value='$now' WHERE setting_key='prospectus_updated_at'");
                logActivity('upload_prospectus', "Uploaded new prospectus: $filename");
                $success = "Prospectus uploaded — students can now download it.";
            } else {
                $error = 'Upload failed. Check folder permissions.';
            }
        }
    }
}

// ── Add calendar event ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_calendar'])) {
    $sess_id    = intval($_POST['cal_session_id'] ?? 1);
    $title      = mysqli_real_escape_string($conn, trim($_POST['cal_title'] ?? ''));
    $event_date = $_POST['cal_date'] ?? '';
    $end_date   = $_POST['cal_end_date'] ?? null;
    $category   = mysqli_real_escape_string($conn, $_POST['cal_category'] ?? 'Event');
    $desc       = mysqli_real_escape_string($conn, trim($_POST['cal_description'] ?? ''));

    if ($title && $event_date) {
        $end_q = $end_date ? "'$end_date'" : 'NULL';
        $conn->query("INSERT INTO academic_calendar (session_id, title, event_date, end_date, category, description)
            VALUES ($sess_id, '$title', '$event_date', $end_q, '$category', '$desc')");
        $success = "Calendar event added.";
        $tab = 'calendar';
    } else {
        $error = 'Title and date are required.';
    }
}

// ── Delete calendar event ──────────────────────────────────────────────────────
if (isset($_GET['delete_cal'])) {
    $id = intval($_GET['delete_cal']);
    $conn->query("DELETE FROM academic_calendar WHERE id=$id");
    $success = 'Event deleted.';
    $tab = 'calendar';
}

// ── Fetch all settings grouped ─────────────────────────────────────────────────
$settings = [];
$sq = $conn->query("SELECT * FROM school_settings ORDER BY setting_group, id");
while ($r = $sq->fetch_assoc()) $settings[$r['setting_group']][$r['setting_key']] = $r;

// ── Fetch calendar ─────────────────────────────────────────────────────────────
$sessions  = $conn->query("SELECT id, session_name FROM academic_sessions ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$cal_sess  = intval($_GET['cal_sess'] ?? ($sessions[0]['id'] ?? 1));
$calendar  = $conn->query("SELECT * FROM academic_calendar WHERE session_id=$cal_sess ORDER BY event_date ASC")->fetch_all(MYSQLI_ASSOC);

// ── Fetch sessions & terms for the Session & Term tab ───────────────────────────
$all_sessions = getAllSessions($conn);
$terms_all    = $conn->query("SELECT * FROM terms ORDER BY session_id DESC, id ASC")->fetch_all(MYSQLI_ASSOC);
$terms_by_session = [];
foreach ($terms_all as $t) {
    $terms_by_session[$t['session_id']][] = $t;
}

$cat_colors = [
    'Term Start'=>'bg-green-100 text-green-700',
    'Term End'  =>'bg-red-100 text-red-700',
    'Holiday'   =>'bg-blue-100 text-blue-700',
    'Exam'      =>'bg-amber-100 text-amber-700',
    'Event'     =>'bg-purple-100 text-purple-700',
    'Deadline'  =>'bg-orange-100 text-orange-700',
    'Other'     =>'bg-slate-100 text-slate-600',
];
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

<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">School Settings</h1>
    <p class="text-slate-500 text-sm mt-1">Manage payment info, prospectus, academic calendar and admissions settings.</p>
</div>

<?php if ($success): ?>
<div class="mb-5 p-4 bg-green-50 border border-green-200 rounded-xl flex gap-3 items-start">
    <span class="material-symbols-outlined text-green-600">check_circle</span>
    <p class="text-green-800 text-sm"><?php echo $success; ?></p>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl flex gap-3 items-start">
    <span class="material-symbols-outlined text-red-600">error</span>
    <p class="text-red-800 text-sm"><?php echo $error; ?></p>
</div>
<?php endif; ?>

<!-- Tabs -->
<div class="flex gap-1 mb-6 bg-slate-100 p-1 rounded-xl w-fit flex-wrap">
    <?php foreach([
        'session'    => ['event','Session & Term'],
        'payment'    => ['payments','Payment Details'],
        'admissions' => ['school','Admissions'],
        'prospectus' => ['description','Prospectus'],
        'calendar'   => ['calendar_month','Academic Calendar'],
        'contact'    => ['call','Contact Info'],
    ] as $t=>[$icon,$label]): ?>
    <a href="?tab=<?php echo $t; ?>"
       class="px-4 py-2.5 rounded-lg text-sm font-semibold transition-all <?php echo $tab===$t?'bg-white text-primary shadow-sm':'text-slate-600 hover:text-primary'; ?>">
        <span class="material-symbols-outlined text-sm align-middle mr-1"><?php echo $icon; ?></span><?php echo $label; ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- ── SESSION & TERM TAB ────────────────────────────────────────────── -->
<?php if ($tab === 'session'): ?>
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <p class="text-slate-500 text-sm max-w-xl">This is the single place that controls the current session and term. Every other page in the system — results, classes, fees — reads from whatever is marked <strong>Current</strong> here.</p>
    <button onclick="document.getElementById('addSessionModal').classList.remove('hidden')"
        class="inline-flex items-center gap-2 bg-gold text-primary px-5 py-3 rounded-xl font-bold hover:bg-gold/90 transition-all shadow-sm flex-shrink-0">
        <span class="material-symbols-outlined">add_circle</span>New Session
    </button>
</div>

<?php if (empty($all_sessions)): ?>
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
    <?php foreach ($all_sessions as $sess):
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

<!-- ── PAYMENT TAB ────────────────────────────────────────────── -->
<?php elseif ($tab === 'payment'): ?>
<form method="POST" class="bg-white rounded-xl border border-slate-200 p-6 max-w-2xl">
    <input type="hidden" name="save_settings" value="1">
    <h2 class="font-bold text-slate-900 mb-5">Payment Details</h2>
    <p class="text-xs text-slate-500 mb-5">These values appear on the public admissions page and application form.</p>
    <div class="space-y-4">
        <?php foreach($settings['payment'] ?? [] as $key => $row): ?>
        <div>
            <label class="text-xs font-semibold text-slate-600 mb-1 block"><?php echo htmlspecialchars($row['setting_label']); ?></label>
            <input type="hidden" name="keys[]" value="<?php echo htmlspecialchars($key); ?>">
            <input type="text" name="vals[]" value="<?php echo htmlspecialchars($row['setting_value']); ?>"
                class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold px-4 py-3"
                placeholder="<?php echo htmlspecialchars($row['setting_label']); ?>">
        </div>
        <?php endforeach; ?>
    </div>
    <button type="submit" class="mt-6 bg-gold text-primary px-6 py-3 rounded-xl font-bold hover:bg-gold/90">Save Payment Details</button>
</form>

<!-- ── ADMISSIONS TAB ────────────────────────────────────────── -->
<?php elseif ($tab === 'admissions'): ?>
<form method="POST" class="bg-white rounded-xl border border-slate-200 p-6 max-w-2xl">
    <input type="hidden" name="save_settings" value="1">
    <h2 class="font-bold text-slate-900 mb-5">Admissions Settings</h2>
    <div class="space-y-4">
        <?php foreach($settings['admissions'] ?? [] as $key => $row): ?>
        <div>
            <label class="text-xs font-semibold text-slate-600 mb-1 block"><?php echo htmlspecialchars($row['setting_label']); ?></label>
            <input type="hidden" name="keys[]" value="<?php echo htmlspecialchars($key); ?>">
            <?php if ($key === 'admissions_open'): ?>
            <select name="vals[]" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold px-4 py-3">
                <option value="1" <?php echo $row['setting_value']==='1'?'selected':''; ?>>Open — accepting applications</option>
                <option value="0" <?php echo $row['setting_value']==='0'?'selected':''; ?>>Closed — not accepting</option>
            </select>
            <?php elseif (str_contains($key, 'date')): ?>
            <input type="date" name="vals[]" value="<?php echo htmlspecialchars($row['setting_value']); ?>"
                class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold px-4 py-3">
            <?php else: ?>
            <input type="text" name="vals[]" value="<?php echo htmlspecialchars($row['setting_value']); ?>"
                class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold px-4 py-3">
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <button type="submit" class="mt-6 bg-gold text-primary px-6 py-3 rounded-xl font-bold hover:bg-gold/90">Save Admissions Settings</button>
</form>

<!-- ── PROSPECTUS TAB ────────────────────────────────────────── -->
<?php elseif ($tab === 'prospectus'): ?>
<div class="grid md:grid-cols-2 gap-6 max-w-3xl">
    <!-- Upload new -->
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="font-bold text-slate-900 mb-1">Upload Prospectus PDF</h2>
        <p class="text-xs text-slate-500 mb-5">This replaces the current prospectus. Students will see a download button on the admissions page.</p>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="upload_prospectus" value="1">
            <div class="border-2 border-dashed border-slate-200 rounded-xl p-8 text-center hover:border-gold transition-all">
                <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">upload_file</span>
                <p class="text-sm text-slate-500 mb-3">Select your prospectus PDF</p>
                <input type="file" name="prospectus_pdf" accept=".pdf" required class="text-sm">
            </div>
            <button type="submit" class="w-full bg-primary text-white py-3 rounded-xl font-bold hover:bg-primary/90">Upload Prospectus</button>
        </form>
    </div>
    <!-- Current -->
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="font-bold text-slate-900 mb-4">Current Prospectus</h2>
        <?php
        $pf = $settings['prospectus']['prospectus_file']['setting_value'] ?? '';
        $pu = $settings['prospectus']['prospectus_updated_at']['setting_value'] ?? '';
        ?>
        <?php if ($pf): ?>
        <div class="flex items-center gap-3 p-4 bg-slate-50 rounded-xl mb-4">
            <span class="material-symbols-outlined text-red-500 text-3xl">picture_as_pdf</span>
            <div>
                <p class="font-semibold text-slate-800 text-sm"><?php echo htmlspecialchars($pf); ?></p>
                <?php if ($pu): ?><p class="text-xs text-slate-400">Updated: <?php echo date('d M Y', strtotime($pu)); ?></p><?php endif; ?>
            </div>
        </div>
        <a href="../uploads/prospectus/<?php echo htmlspecialchars($pf); ?>" target="_blank"
            class="inline-flex items-center gap-2 text-primary hover:underline text-sm font-semibold">
            <span class="material-symbols-outlined text-sm">open_in_new</span>Preview / Download
        </a>
        <?php else: ?>
        <div class="text-center py-8 text-slate-400">
            <span class="material-symbols-outlined text-4xl block mb-2">description</span>
            <p class="text-sm">No prospectus uploaded yet.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── CALENDAR TAB ──────────────────────────────────────────── -->
<?php elseif ($tab === 'calendar'): ?>
<div class="grid lg:grid-cols-3 gap-6">

    <!-- Add Event Form -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-bold text-slate-900 mb-4">Add Calendar Event</h3>
            <form method="POST" class="space-y-3">
                <input type="hidden" name="add_calendar" value="1">
                <div>
                    <label class="text-xs font-semibold text-slate-600 mb-1 block">Session</label>
                    <select name="cal_session_id" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold px-3 py-2">
                        <?php foreach($sessions as $sess): ?>
                        <option value="<?php echo $sess['id']; ?>" <?php echo $cal_sess==$sess['id']?'selected':''; ?>>
                            <?php echo htmlspecialchars($sess['session_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 mb-1 block">Event Title *</label>
                    <input type="text" name="cal_title" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold px-3 py-2" placeholder="e.g. First Term Begins">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 mb-1 block">Category</label>
                    <select name="cal_category" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold px-3 py-2">
                        <?php foreach(array_keys($cat_colors) as $cat): ?>
                        <option><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs font-semibold text-slate-600 mb-1 block">Start Date *</label>
                        <input type="date" name="cal_date" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold px-3 py-2">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600 mb-1 block">End Date</label>
                        <input type="date" name="cal_end_date" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold px-3 py-2">
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 mb-1 block">Description</label>
                    <textarea name="cal_description" rows="2" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold px-3 py-2"></textarea>
                </div>
                <button type="submit" class="w-full bg-gold text-primary py-2.5 rounded-xl font-bold text-sm hover:bg-gold/90">Add Event</button>
            </form>
        </div>
    </div>

    <!-- Calendar Events List -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-900">
                    Academic Calendar
                    <?php foreach($sessions as $sess) if($sess['id']==$cal_sess) echo '— '.htmlspecialchars($sess['session_name']); ?>
                </h3>
                <!-- Session switcher -->
                <div class="flex gap-2">
                    <?php foreach($sessions as $sess): ?>
                    <a href="?tab=calendar&cal_sess=<?php echo $sess['id']; ?>"
                        class="px-3 py-1 text-xs font-semibold rounded-lg <?php echo $cal_sess==$sess['id']?'bg-primary text-white':'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
                        <?php echo htmlspecialchars($sess['session_name']); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php if (empty($calendar)): ?>
            <div class="p-12 text-center text-slate-400">
                <span class="material-symbols-outlined text-4xl block mb-2">calendar_month</span>
                No events for this session yet.
            </div>
            <?php else: ?>
            <div class="divide-y divide-slate-100">
                <?php foreach($calendar as $event):
                    $cc = $cat_colors[$event['category']] ?? 'bg-slate-100 text-slate-600';
                ?>
                <div class="px-5 py-4 flex items-start justify-between gap-4 hover:bg-slate-50">
                    <div class="flex items-start gap-3">
                        <div class="text-center bg-primary text-white rounded-xl px-3 py-2 flex-shrink-0">
                            <p class="text-lg font-black leading-none"><?php echo date('d', strtotime($event['event_date'])); ?></p>
                            <p class="text-xs font-semibold"><?php echo date('M', strtotime($event['event_date'])); ?></p>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900 text-sm"><?php echo htmlspecialchars($event['title']); ?></p>
                            <?php if ($event['end_date'] && $event['end_date'] !== $event['event_date']): ?>
                            <p class="text-xs text-slate-400">Until <?php echo date('d M Y', strtotime($event['end_date'])); ?></p>
                            <?php endif; ?>
                            <?php if ($event['description']): ?>
                            <p class="text-xs text-slate-500 mt-0.5"><?php echo htmlspecialchars($event['description']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?php echo $cc; ?>"><?php echo $event['category']; ?></span>
                        <a href="?tab=calendar&cal_sess=<?php echo $cal_sess; ?>&delete_cal=<?php echo $event['id']; ?>"
                            onclick="return confirm('Delete this event?')"
                            class="p-1 hover:bg-red-50 text-red-400 hover:text-red-600 rounded-lg transition-all">
                            <span class="material-symbols-outlined text-sm">delete</span>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ── CONTACT TAB ────────────────────────────────────────────── -->
<?php elseif ($tab === 'contact'): ?>
<form method="POST" class="bg-white rounded-xl border border-slate-200 p-6 max-w-2xl">
    <input type="hidden" name="save_settings" value="1">
    <h2 class="font-bold text-slate-900 mb-5">Contact Information</h2>
    <div class="space-y-4">
        <?php foreach($settings['contact'] ?? [] as $key => $row): ?>
        <div>
            <label class="text-xs font-semibold text-slate-600 mb-1 block"><?php echo htmlspecialchars($row['setting_label']); ?></label>
            <input type="hidden" name="keys[]" value="<?php echo htmlspecialchars($key); ?>">
            <input type="text" name="vals[]" value="<?php echo htmlspecialchars($row['setting_value']); ?>"
                class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold px-4 py-3">
        </div>
        <?php endforeach; ?>
    </div>
    <button type="submit" class="mt-6 bg-gold text-primary px-6 py-3 rounded-xl font-bold hover:bg-gold/90">Save Contact Info</button>
</form>
<?php endif; ?>

</main>
</div>
</div>
</body>
</html>
