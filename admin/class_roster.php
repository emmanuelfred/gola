<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('classes');
require_once 'includes/session_helper.php';
require_once 'includes/enrollment_helper.php';
require_once 'includes/staff_helper.php';
$page_title = "Class Roster";

$success = '';
$error   = '';

$current = getCurrentSessionTerm($conn);
$session_id = intval($_GET['session_id'] ?? $current['session_id'] ?? 0);
$class_id   = intval($_GET['class_id'] ?? 0);

// ── Handle actions ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'register') {
        $reg_no     = trim($_POST['reg_no'] ?? '');
        $class_id   = intval($_POST['class_id'] ?? 0);
        $session_id = intval($_POST['session_id'] ?? 0);

        if (!$reg_no || !$class_id || !$session_id) {
            $error = 'Reg number, class, and session are all required.';
        } else {
            $student = findStudentByRegNo($conn, $reg_no);
            if (!$student) {
                $error = "No active student found with reg number \"" . htmlspecialchars($reg_no) . "\". Check the number and try again.";
            } else {
                $result = enrollStudentInClass($conn, $student['id'], $class_id, $session_id, $_SESSION['admin_id'] ?? null);
                $full_name = htmlspecialchars($student['first_name'].' '.$student['last_name']);
                if ($result['moved']) {
                    logActivity('move_student_class', "Moved {$student['student_id']} to class $class_id for session $session_id");
                    $success = "<strong>$full_name</strong> was already registered in another class this session — moved here instead.";
                } else {
                    logActivity('register_student_class', "Registered {$student['student_id']} into class $class_id for session $session_id");
                    $success = "<strong>$full_name</strong> ({$student['student_id']}) registered to this class.";
                }
            }
        }
    }

    if ($_POST['action'] === 'unenroll') {
        $student_id = intval($_POST['student_id'] ?? 0);
        $session_id = intval($_POST['session_id'] ?? 0);
        $class_id   = intval($_POST['class_id'] ?? 0);
        if ($student_id && $session_id) {
            unenrollStudent($conn, $student_id, $session_id);
            logActivity('unenroll_student_class', "Removed student ID $student_id from class roster, session $session_id");
            $success = "Student removed from this class roster.";
        }
    }
}

// ── Data ───────────────────────────────────────────────────────────────────────
$sessions = getAllSessions($conn);
$classes  = $conn->query("
    SELECT c.*, st.first_name as t_first, st.last_name as t_last
    FROM classes c
    LEFT JOIN staff st ON st.id = c.class_teacher_id
    ORDER BY c.class_level DESC, c.class_name, c.arm
")->fetch_all(MYSQLI_ASSOC);

$roster = [];
$sel_class = null;
$sel_session = null;
if ($class_id && $session_id) {
    $roster = getClassRoster($conn, $class_id, $session_id);
    foreach ($classes as $c) { if ($c['id'] == $class_id) { $sel_class = $c; break; } }
    foreach ($sessions as $s) { if ($s['id'] == $session_id) { $sel_session = $s; break; } }
}

// Roster count per class for the selected session (for the left list)
$roster_counts = [];
if ($session_id) {
    $rq = $conn->query("
        SELECT ce.class_id, COUNT(*) as c FROM class_enrollments ce
        JOIN students s ON s.id = ce.student_id
        WHERE ce.session_id = $session_id AND s.status='Active'
        GROUP BY ce.class_id
    ");
    while ($r = $rq->fetch_assoc()) $roster_counts[$r['class_id']] = $r['c'];
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
        <h1 class="text-2xl font-bold text-slate-900">Class Roster</h1>
        <p class="text-slate-500 text-sm mt-1 max-w-2xl">Register students into a class for a session using their reg no. A new session starts every class empty — this is how students get placed back in.</p>
    </div>
    <form method="GET" class="flex items-center gap-2">
        <?php if ($class_id): ?><input type="hidden" name="class_id" value="<?php echo $class_id; ?>"><?php endif; ?>
        <label class="text-xs font-semibold text-slate-500">Session:</label>
        <select name="session_id" onchange="this.form.submit()" class="text-sm border-slate-200 rounded-lg focus:ring-gold focus:border-gold">
            <?php foreach ($sessions as $s): ?>
            <option value="<?php echo $s['id']; ?>" <?php echo $session_id==$s['id']?'selected':''; ?>>
                <?php echo htmlspecialchars($s['session_name']); ?><?php echo $s['is_current'] ? ' (Current)' : ''; ?>
            </option>
            <?php endforeach; ?>
        </select>
    </form>
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
    <p class="text-red-800 text-sm"><?php echo $error; ?></p>
</div>
<?php endif; ?>

<div class="grid lg:grid-cols-3 gap-6">

    <!-- Left: Class List -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
                <h3 class="font-bold text-slate-800 text-sm">Select a Class</h3>
            </div>
            <div class="divide-y divide-slate-100 max-h-[65vh] overflow-y-auto">
                <?php $current_level = ''; foreach ($classes as $cls):
                    if ($cls['class_level'] !== $current_level): $current_level = $cls['class_level']; ?>
                <div class="px-4 py-2 bg-slate-50"><p class="text-xs font-bold text-slate-400 uppercase tracking-wider"><?php echo htmlspecialchars($current_level); ?></p></div>
                <?php endif; ?>
                <a href="?class_id=<?php echo $cls['id']; ?>&session_id=<?php echo $session_id; ?>"
                   class="flex items-center justify-between px-4 py-3 hover:bg-gold/5 transition-colors <?php echo $class_id===$cls['id'] ? 'bg-gold/10 border-l-4 border-gold' : ''; ?>">
                    <div>
                        <p class="font-semibold text-sm <?php echo $class_id===$cls['id'] ? 'text-primary' : 'text-slate-700'; ?>">
                            <?php echo htmlspecialchars($cls['class_name'].' '.$cls['arm']); ?>
                        </p>
                        <p class="text-xs text-slate-400">
                            <?php echo $cls['t_first'] ? htmlspecialchars($cls['t_first'].' '.$cls['t_last']) : 'No form teacher'; ?>
                        </p>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full font-semibold <?php echo !empty($roster_counts[$cls['id']]) ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-slate-400'; ?>">
                        <?php echo $roster_counts[$cls['id']] ?? 0; ?>
                    </span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Right: Roster + Register box -->
    <div class="lg:col-span-2">
        <?php if (!$class_id): ?>
        <div class="bg-white rounded-xl border border-slate-200 p-16 text-center h-full flex flex-col items-center justify-center">
            <span class="material-symbols-outlined text-6xl text-slate-200 mb-3">groups</span>
            <p class="font-bold text-slate-700 mb-1">Select a class from the left</p>
            <p class="text-sm text-slate-400">Pick a session above, then a class, to see and manage its roster.</p>
        </div>
        <?php else: ?>

        <!-- Class header -->
        <div class="bg-primary rounded-xl p-5 mb-4">
            <h3 class="text-white font-bold text-lg"><?php echo htmlspecialchars($sel_class['class_name'].' '.$sel_class['arm']); ?></h3>
            <p class="text-slate-300 text-sm">
                <?php echo count($roster); ?> student(s) registered for <?php echo htmlspecialchars($sel_session['session_name'] ?? ''); ?>
                <?php if (!$sel_class['class_teacher_id']): ?>
                · <span class="text-amber-300 font-semibold">No form teacher assigned</span>
                <?php endif; ?>
            </p>
        </div>

        <!-- Register by reg no -->
        <div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
            <h4 class="font-semibold text-slate-800 text-sm mb-3">
                <span class="material-symbols-outlined text-sm align-middle text-gold mr-1">person_add</span>
                Register a Student by Reg No
            </h4>
            <form method="POST" class="flex gap-3 items-end flex-wrap">
                <input type="hidden" name="action"     value="register">
                <input type="hidden" name="class_id"   value="<?php echo $class_id; ?>">
                <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
                <div class="flex-1 min-w-56">
                    <label class="text-xs font-semibold text-slate-500 mb-1 block">Reg No</label>
                    <input type="text" name="reg_no" required placeholder="e.g. GOLA/2026/JSS1A/003"
                        class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
                </div>
                <button type="submit" class="px-5 py-2.5 bg-gold text-primary text-sm font-bold rounded-lg hover:bg-gold/90 transition-all">
                    Register
                </button>
            </form>
            <p class="text-xs text-slate-400 mt-2">If the student is already registered in a different class this session, they'll be moved here instead.</p>
        </div>

        <!-- Roster table -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100 bg-slate-50">
                <h4 class="font-semibold text-slate-800 text-sm">Registered Students</h4>
            </div>
            <?php if (empty($roster)): ?>
            <div class="px-5 py-12 text-center text-slate-400 text-sm">
                <span class="material-symbols-outlined text-4xl block mb-2">person_off</span>
                No students registered yet for this class this session.
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b text-xs">
                        <tr>
                            <th class="px-4 py-2.5 text-left font-semibold text-slate-500 uppercase">Reg No</th>
                            <th class="px-4 py-2.5 text-left font-semibold text-slate-500 uppercase">Name</th>
                            <th class="px-4 py-2.5 text-center font-semibold text-slate-500 uppercase">Gender</th>
                            <th class="px-4 py-2.5 text-left font-semibold text-slate-500 uppercase">Parent Contact</th>
                            <th class="px-4 py-2.5 text-center font-semibold text-slate-500 uppercase">Remove</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($roster as $st):
                            $parent_name  = $st['father_name'] ?: $st['mother_name'] ?: $st['guardian_name'] ?: null;
                            $parent_phone = $st['father_phone'] ?: $st['mother_phone'] ?: $st['guardian_phone'] ?: null;
                        ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-2.5 font-mono text-xs font-bold text-primary"><?php echo htmlspecialchars($st['student_id']); ?></td>
                            <td class="px-4 py-2.5 font-semibold text-slate-800"><?php echo htmlspecialchars($st['first_name'].' '.$st['last_name']); ?></td>
                            <td class="px-4 py-2.5 text-center text-slate-500"><?php echo htmlspecialchars($st['gender']); ?></td>
                            <td class="px-4 py-2.5 text-xs text-slate-500">
                                <?php if ($parent_name): ?>
                                <?php echo htmlspecialchars($parent_name); ?><?php echo $parent_phone ? ' — '.htmlspecialchars($parent_phone) : ''; ?>
                                <?php else: ?>
                                <span class="text-slate-300">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <form method="POST" onsubmit="return confirm('Remove <?php echo htmlspecialchars($st['first_name']); ?> from this class roster?')">
                                    <input type="hidden" name="action"     value="unenroll">
                                    <input type="hidden" name="student_id" value="<?php echo $st['id']; ?>">
                                    <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
                                    <input type="hidden" name="class_id"   value="<?php echo $class_id; ?>">
                                    <button type="submit" class="p-1 hover:bg-red-50 text-red-400 hover:text-red-600 rounded-lg transition-all" title="Remove from roster">
                                        <span class="material-symbols-outlined text-sm">person_remove</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <?php endif; ?>
    </div>
</div>

</main>
</div>
</div>
</body>
</html>
