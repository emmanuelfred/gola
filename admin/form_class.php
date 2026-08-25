<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('my_class');
require_once 'includes/session_helper.php';
require_once 'includes/enrollment_helper.php';
require_once 'includes/staff_helper.php';
require_once 'includes/timetable_helper.php';
$page_title = "My Class";

$success = '';
$error   = '';

$current    = getCurrentSessionTerm($conn);
$session_id = intval($current['session_id'] ?? 0);

// ── Who is this, and which class are they the form teacher of? ─────────────────
$my_staff = getStaffByAdminUserId($conn, $admin_id);
$my_class = $my_staff ? getFormTeacherClass($conn, $my_staff['id']) : null;
$is_admin_tier = hasPermission('admin');

// Admins/super admins can preview any class (oversight). Anyone else is locked
// to whichever class they're the form teacher of — that's the whole point.
$preview_class_id = ($is_admin_tier && isset($_GET['class_id'])) ? intval($_GET['class_id']) : 0;
$class_id = $preview_class_id ?: ($my_class['id'] ?? 0);
$is_previewing = $is_admin_tier && $preview_class_id && $preview_class_id != ($my_class['id'] ?? 0);

// ── Handle actions ─────────────────────────────────────────────────────────────
// Server-side guard: whatever the form posts, the class being acted on must be
// the class this person is actually authorized for (their own, or an
// admin-tier preview) — never trust a submitted class_id blindly.
function authorizedForClass(int $submitted_class_id, int $class_id, bool $is_admin_tier): bool {
    return $submitted_class_id === $class_id && ($class_id > 0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $session_id) {
    $posted_class_id = intval($_POST['class_id'] ?? 0);

    if (!authorizedForClass($posted_class_id, $class_id, $is_admin_tier)) {
        $error = 'You are not authorized to manage that class.';
    } else {
        if ($_POST['action'] === 'register') {
            $reg_no = trim($_POST['reg_no'] ?? '');
            if (!$reg_no) {
                $error = 'Enter a reg number.';
            } else {
                $student = findStudentByRegNo($conn, $reg_no);
                if (!$student) {
                    $error = "No active student found with reg number \"" . htmlspecialchars($reg_no) . "\". Check the number and try again.";
                } else {
                    $result = enrollStudentInClass($conn, $student['id'], $class_id, $session_id, $admin_id);
                    $full_name = htmlspecialchars($student['first_name'].' '.$student['last_name']);
                    if ($result['moved']) {
                        logActivity('move_student_class', "Moved {$student['student_id']} to class $class_id for session $session_id (via My Class)");
                        $success = "<strong>$full_name</strong> was registered in another class this session — moved here instead.";
                    } else {
                        logActivity('register_student_class', "Registered {$student['student_id']} into class $class_id for session $session_id (via My Class)");
                        $success = "<strong>$full_name</strong> ({$student['student_id']}) registered to your class.";
                    }
                }
            }
        }

        if ($_POST['action'] === 'unenroll') {
            $student_id = intval($_POST['student_id'] ?? 0);
            if ($student_id) {
                unenrollStudent($conn, $student_id, $session_id);
                logActivity('unenroll_student_class', "Removed student ID $student_id from class roster (via My Class)");
                $success = "Student removed from your class roster.";
            }
        }
    }
}

// ── Data ───────────────────────────────────────────────────────────────────────
$class_info = null;
$roster = [];
$class_subjects_info = [];
if ($class_id && $session_id) {
    $class_info = $conn->query("
        SELECT c.*, st.first_name as t_first, st.last_name as t_last
        FROM classes c LEFT JOIN staff st ON st.id = c.class_teacher_id
        WHERE c.id = $class_id
    ")->fetch_assoc();
    $roster = getClassRoster($conn, $class_id, $session_id);

    $class_subjects_info = $conn->query("
        SELECT s.subject_name, st.first_name as t_first, st.last_name as t_last
        FROM class_subjects cs
        JOIN subjects s ON s.id = cs.subject_id
        LEFT JOIN staff st ON st.id = cs.teacher_id
        WHERE cs.class_id = $class_id
        ORDER BY s.subject_name
    ")->fetch_all(MYSQLI_ASSOC);
}

// For admin-tier preview dropdown
$all_classes = $is_admin_tier ? $conn->query("SELECT id, class_name, arm FROM classes ORDER BY class_level DESC, class_name, arm")->fetch_all(MYSQLI_ASSOC) : [];

// This person's own teaching schedule (subjects they teach, in any class) —
// independent of whether they're a form teacher. Pulled straight from
// class_subjects.teacher_id, so it's always in sync with Manage Class Subjects.
$my_teaching = $my_staff ? getTeacherTimetable($conn, $my_staff['id']) : [];
$my_periods  = !empty($my_teaching) ? getTimetablePeriods($conn) : [];
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
        <h1 class="text-2xl font-bold text-slate-900">My Class</h1>
        <p class="text-slate-500 text-sm mt-1">
            <?php if ($class_info): ?>
                <?php echo htmlspecialchars($class_info['class_name'].' '.$class_info['arm']); ?> — <?php echo htmlspecialchars($current['session_name'] ?? ''); ?>
            <?php else: ?>
                Manage your form class roster and student contact info.
            <?php endif; ?>
        </p>
    </div>
    <?php if ($is_admin_tier): ?>
    <form method="GET" class="flex items-center gap-2">
        <label class="text-xs font-semibold text-slate-500">Preview class:</label>
        <select name="class_id" onchange="this.form.submit()" class="text-sm border-slate-200 rounded-lg focus:ring-gold focus:border-gold">
            <option value="">— My own class —</option>
            <?php foreach ($all_classes as $c): ?>
            <option value="<?php echo $c['id']; ?>" <?php echo $preview_class_id==$c['id']?'selected':''; ?>>
                <?php echo htmlspecialchars($c['class_name'].' '.$c['arm']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </form>
    <?php endif; ?>
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

<?php if ($is_previewing): ?>
<div class="mb-5 p-3 bg-blue-50 border border-blue-200 rounded-xl text-blue-700 text-sm flex items-center gap-2">
    <span class="material-symbols-outlined text-sm">visibility</span>
    You're previewing this class as an admin — you're not its form teacher.
</div>
<?php endif; ?>

<?php if (!$class_id): ?>
<!-- Not assigned to any class -->
<div class="bg-white rounded-2xl border border-slate-200 p-16 text-center">
    <span class="material-symbols-outlined text-6xl text-slate-200 block mb-3">school</span>
    <h3 class="font-bold text-slate-800 mb-2">You're not currently a form teacher</h3>
    <p class="text-sm text-slate-500 max-w-md mx-auto">
        You haven't been assigned as the form teacher of any class yet.
        <?php if ($is_admin_tier): ?>Assign yourself or someone else from <a href="manage_classes.php" class="text-primary font-semibold hover:underline">Manage Classes</a>, or use the preview dropdown above to look at any class.<?php else: ?>Ask an administrator to assign you to a class from Manage Classes.<?php endif; ?>
    </p>
</div>

<?php elseif (!$session_id): ?>
<div class="bg-amber-50 border border-amber-200 rounded-2xl p-8 text-center text-amber-700">
    No academic session is currently marked as active. Ask an administrator to set one in School Settings.
</div>

<?php else: ?>

<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">

        <!-- Register by reg no -->
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h4 class="font-semibold text-slate-800 text-sm mb-3">
                <span class="material-symbols-outlined text-sm align-middle text-gold mr-1">person_add</span>
                Register a Student by Reg No
            </h4>
            <form method="POST" class="flex gap-3 items-end flex-wrap">
                <input type="hidden" name="action"   value="register">
                <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                <div class="flex-1 min-w-56">
                    <label class="text-xs font-semibold text-slate-500 mb-1 block">Reg No</label>
                    <input type="text" name="reg_no" required placeholder="e.g. GOLA/2026/JSS1A/003"
                        class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
                </div>
                <button type="submit" class="px-5 py-2.5 bg-gold text-primary text-sm font-bold rounded-lg hover:bg-gold/90 transition-all">
                    Register
                </button>
            </form>
        </div>

        <!-- Roster -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                <h4 class="font-semibold text-slate-800 text-sm">Your Students (<?php echo count($roster); ?>)</h4>
                <?php if ($roster): ?>
                <a href="student_results.php?session_id=<?php echo $session_id; ?>&term_id=<?php echo $current['term_id']; ?>&class_id=<?php echo $class_id; ?>"
                   class="text-xs font-semibold text-primary hover:underline flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">assignment</span>Enter Results
                </a>
                <?php endif; ?>
            </div>
            <?php if (empty($roster)): ?>
            <div class="px-5 py-12 text-center text-slate-400 text-sm">
                <span class="material-symbols-outlined text-4xl block mb-2">person_off</span>
                No students registered yet this session. Use the box above to add them by reg no.
            </div>
            <?php else: ?>
            <div class="divide-y divide-slate-100">
                <?php foreach ($roster as $st):
                    $parent_name  = $st['father_name'] ?: $st['mother_name'] ?: $st['guardian_name'] ?: null;
                    $parent_phone = $st['father_phone'] ?: $st['mother_phone'] ?: $st['guardian_phone'] ?: null;
                    $row_id = 'contact-'.$st['id'];
                ?>
                <div class="px-5 py-3">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center text-slate-500 text-xs font-bold flex-shrink-0">
                                <?php echo strtoupper(substr($st['first_name'],0,1).substr($st['last_name'],0,1)); ?>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-800 text-sm"><?php echo htmlspecialchars($st['first_name'].' '.$st['last_name']); ?></p>
                                <p class="text-xs text-slate-400 font-mono"><?php echo htmlspecialchars($st['student_id']); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button type="button" onclick="document.getElementById('<?php echo $row_id; ?>').classList.toggle('hidden')"
                                class="p-1.5 hover:bg-slate-100 text-slate-500 rounded-lg transition-all" title="Parent contact">
                                <span class="material-symbols-outlined text-sm">call</span>
                            </button>
                            <form method="POST" onsubmit="return confirm('Remove <?php echo htmlspecialchars($st['first_name']); ?> from your class roster?')">
                                <input type="hidden" name="action"     value="unenroll">
                                <input type="hidden" name="class_id"   value="<?php echo $class_id; ?>">
                                <input type="hidden" name="student_id" value="<?php echo $st['id']; ?>">
                                <button type="submit" class="p-1.5 hover:bg-red-50 text-red-400 hover:text-red-600 rounded-lg transition-all" title="Remove from roster">
                                    <span class="material-symbols-outlined text-sm">person_remove</span>
                                </button>
                            </form>
                        </div>
                    </div>
                    <div id="<?php echo $row_id; ?>" class="hidden mt-3 ml-12 p-3 bg-slate-50 rounded-lg text-xs text-slate-600 space-y-1">
                        <?php if ($st['father_name']): ?><p><span class="font-semibold text-slate-500">Father:</span> <?php echo htmlspecialchars($st['father_name']); ?><?php echo $st['father_phone'] ? ' — '.htmlspecialchars($st['father_phone']) : ''; ?></p><?php endif; ?>
                        <?php if ($st['mother_name']): ?><p><span class="font-semibold text-slate-500">Mother:</span> <?php echo htmlspecialchars($st['mother_name']); ?><?php echo $st['mother_phone'] ? ' — '.htmlspecialchars($st['mother_phone']) : ''; ?></p><?php endif; ?>
                        <?php if ($st['guardian_name']): ?><p><span class="font-semibold text-slate-500">Guardian<?php echo $st['guardian_relationship'] ? ' ('.htmlspecialchars($st['guardian_relationship']).')' : ''; ?>:</span> <?php echo htmlspecialchars($st['guardian_name']); ?><?php echo $st['guardian_phone'] ? ' — '.htmlspecialchars($st['guardian_phone']) : ''; ?></p><?php endif; ?>
                        <?php if ($st['parent_email']): ?><p><span class="font-semibold text-slate-500">Email:</span> <?php echo htmlspecialchars($st['parent_email']); ?></p><?php endif; ?>
                        <?php if (!$parent_name): ?><p class="text-slate-400">No parent/guardian contact on file.</p><?php endif; ?>
                        <p class="pt-1"><span class="font-semibold text-slate-500">Student phone/email:</span> <?php echo htmlspecialchars($st['student_phone'] ?: '—'); ?> <?php echo $st['student_email'] ? '/ '.htmlspecialchars($st['student_email']) : ''; ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right: class info -->
    <div class="space-y-6">
        <div class="bg-primary rounded-xl p-5 text-white">
            <p class="text-slate-300 text-xs uppercase font-bold tracking-wider mb-1">Form Teacher</p>
            <p class="font-bold text-lg"><?php echo $class_info['t_first'] ? htmlspecialchars($class_info['t_first'].' '.$class_info['t_last']) : '— None assigned —'; ?></p>
            <div class="mt-4 pt-4 border-t border-white/10 flex justify-between text-sm">
                <span class="text-slate-300">Students</span>
                <span class="font-bold"><?php echo count($roster); ?></span>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h4 class="font-semibold text-slate-800 text-sm mb-3">Subjects & Teachers</h4>
            <?php if (empty($class_subjects_info)): ?>
            <p class="text-xs text-slate-400">No subjects assigned to this class yet.</p>
            <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($class_subjects_info as $cs): ?>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-700 font-medium"><?php echo htmlspecialchars($cs['subject_name']); ?></span>
                    <span class="<?php echo $cs['t_first'] ? 'text-slate-500' : 'text-amber-500'; ?>">
                        <?php echo $cs['t_first'] ? htmlspecialchars($cs['t_first'].' '.$cs['t_last']) : 'No teacher'; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php endif; ?>

<?php if (!empty($my_teaching)): ?>
<div class="mt-6 bg-white rounded-xl border border-slate-200 p-5 max-w-2xl">
    <h4 class="font-semibold text-slate-800 text-sm mb-1">
        <span class="material-symbols-outlined text-sm align-middle text-gold mr-1">calendar_view_week</span>
        Your Teaching Schedule
    </h4>
    <p class="text-xs text-slate-400 mb-3">Every subject you teach, across all classes — pulled from your subject-teacher assignments.</p>
    <div class="grid sm:grid-cols-2 gap-x-6 gap-y-2">
        <?php foreach ($my_periods as $p):
            if ($p['is_break']) continue;
            $day_hits = [];
            foreach (TIMETABLE_DAYS as $day) {
                if (!empty($my_teaching[$p['id']][$day])) $day_hits[$day] = $my_teaching[$p['id']][$day];
            }
            if (empty($day_hits)) continue;
        ?>
        <div class="text-xs border-b border-slate-50 pb-2">
            <p class="font-semibold text-slate-600 mb-1"><?php echo htmlspecialchars($p['period_name']); ?> <span class="text-slate-400 font-normal"><?php echo date('g:i A', strtotime($p['start_time'])); ?></span></p>
            <?php foreach ($day_hits as $day => $hit): ?>
            <p class="text-slate-500 pl-2">
                <span class="font-medium text-primary"><?php echo substr($day,0,3); ?></span> —
                <?php echo htmlspecialchars($hit['subject_name']); ?> ·
                <?php echo htmlspecialchars($hit['class_name'].' '.$hit['arm']); ?>
            </p>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

</main>
</div>
</div>
</body>
</html>
