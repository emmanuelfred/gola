<?php
require_once 'auth_check.php';
$page_title = "Manage Classes";
$success = '';
$error   = '';

// ── Handle actions ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Add class
    if (isset($_POST['add_class'])) {
        $class_name  = trim($_POST['class_name'] ?? '');
        $class_level = trim($_POST['class_level'] ?? '');
        $arm         = trim($_POST['arm'] ?? '');
        $teacher_id  = intval($_POST['class_teacher_id'] ?? 0) ?: null;

        if (!$class_name || !$class_level) {
            $error = 'Class name and level are required.';
        } else {
            $cn = mysqli_real_escape_string($conn, $class_name);
            $cl = mysqli_real_escape_string($conn, $class_level);
            $ar = mysqli_real_escape_string($conn, $arm);
            $ti = $teacher_id ? $teacher_id : 'NULL';
            $res = $conn->query("INSERT INTO classes (class_name, class_level, arm, class_teacher_id) VALUES ('$cn','$cl','$ar',$ti)");
            if ($res) {
                logActivity('add_class', "Added class: $class_name $arm");
                $success = "Class <strong>$class_name $arm</strong> added.";
            } else {
                $error = 'Failed: '.$conn->error.' (Class + arm combination may already exist.)';
            }
        }
    }

    // Edit class
    if (isset($_POST['edit_class'])) {
        $id          = intval($_POST['class_id']);
        $class_name  = trim($_POST['class_name'] ?? '');
        $class_level = trim($_POST['class_level'] ?? '');
        $arm         = trim($_POST['arm'] ?? '');
        $teacher_id  = intval($_POST['class_teacher_id'] ?? 0) ?: null;

        if (!$class_name || !$class_level) {
            $error = 'Class name and level are required.';
        } else {
            $cn = mysqli_real_escape_string($conn, $class_name);
            $cl = mysqli_real_escape_string($conn, $class_level);
            $ar = mysqli_real_escape_string($conn, $arm);
            $ti = $teacher_id ? $teacher_id : 'NULL';
            $conn->query("UPDATE classes SET class_name='$cn', class_level='$cl', arm='$ar', class_teacher_id=$ti WHERE id=$id");
            logActivity('edit_class', "Edited class ID $id: $class_name $arm");
            $success = "Class updated.";
        }
    }

    // Delete class
    if (isset($_POST['delete_class']) && hasPermission('admin')) {
        $id = intval($_POST['class_id']);
        // Check if students are assigned
        $count = $conn->query("SELECT COUNT(*) as c FROM students WHERE class_id=$id")->fetch_assoc()['c'];
        if ($count > 0) {
            $error = "Cannot delete — $count student(s) are assigned to this class. Reassign them first.";
        } else {
            $conn->query("DELETE FROM class_subjects WHERE class_id=$id");
            $conn->query("DELETE FROM classes WHERE id=$id");
            logActivity('delete_class', "Deleted class ID $id");
            $success = "Class deleted.";
        }
    }

    // Promote all students in a class to a new class
    if (isset($_POST['promote_class']) && hasPermission('admin')) {
        $from_class = intval($_POST['from_class_id']);
        $to_class   = intval($_POST['to_class_id']);
        if ($from_class && $to_class && $from_class !== $to_class) {
            $count = $conn->query("SELECT COUNT(*) as c FROM students WHERE class_id=$from_class AND status='Active'")->fetch_assoc()['c'];
            $conn->query("UPDATE students SET class_id=$to_class WHERE class_id=$from_class AND status='Active'");
            logActivity('promote_class', "Promoted $count students from class $from_class to $to_class");
            $success = "<strong>$count student(s)</strong> promoted to new class.";
        }
    }
}

// ── Fetch data ─────────────────────────────────────────────────────────────────
$classes_raw = $conn->query("
    SELECT c.*,
        (SELECT COUNT(*) FROM students s WHERE s.class_id=c.id AND s.status='Active') as student_count,
        (SELECT COUNT(*) FROM class_subjects cs WHERE cs.class_id=c.id) as subject_count,
        a.full_name as teacher_name
    FROM classes c
    LEFT JOIN admin_users a ON a.id = c.class_teacher_id
    ORDER BY c.class_level DESC, c.class_name, c.arm
")->fetch_all(MYSQLI_ASSOC);

// Group by level
$by_level = [];
foreach ($classes_raw as $cl) {
    $by_level[$cl['class_level']][] = $cl;
}

$teachers = $conn->query("SELECT id, full_name FROM admin_users WHERE is_active=1 ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);

// Edit mode
$edit_class = null;
if (isset($_GET['edit'])) {
    $eid = intval($_GET['edit']);
    foreach ($classes_raw as $cl) { if ($cl['id']==$eid) { $edit_class=$cl; break; } }
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

<!-- Header -->
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Manage Classes</h1>
        <p class="text-slate-500 text-sm mt-1">Add, edit, and manage school classes. Promote whole classes at end of year.</p>
    </div>
    <div class="flex gap-3">
        <a href="manage_parents.php" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 text-slate-700 text-sm font-semibold rounded-xl hover:bg-slate-50">
            <span class="material-symbols-outlined text-sm">family_restroom</span>Parents
        </a>
        <button onclick="document.getElementById('addModal').classList.remove('hidden')"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-gold text-primary font-bold text-sm rounded-xl hover:bg-gold/90 shadow-sm">
            <span class="material-symbols-outlined text-sm">add_circle</span>Add Class
        </button>
    </div>
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

<!-- Stats row -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <?php
    $total_classes  = count($classes_raw);
    $total_students = array_sum(array_column($classes_raw, 'student_count'));
    $jss_count      = count($by_level['Junior'] ?? []);
    $sss_count      = count($by_level['Senior'] ?? []);
    foreach([['school','Total Classes',$total_classes,'text-primary'],['group','Active Students',$total_students,'text-green-600'],['looks_one','JSS Classes',$jss_count,'text-blue-600'],['looks_two','SSS Classes',$sss_count,'text-purple-600']] as [$icon,$lbl,$val,$col]): ?>
    <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3">
        <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center flex-shrink-0">
            <span class="material-symbols-outlined <?php echo $col; ?>"><?php echo $icon; ?></span>
        </div>
        <div>
            <p class="text-2xl font-black <?php echo $col; ?>"><?php echo $val; ?></p>
            <p class="text-xs text-slate-500 font-semibold uppercase"><?php echo $lbl; ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Classes by level -->
<?php foreach ($by_level as $level => $level_classes): ?>
<div class="mb-8">
    <h2 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3 flex items-center gap-3">
        <span class="flex-1 h-px bg-slate-200"></span>
        <?php echo htmlspecialchars($level); ?> Secondary
        <span class="flex-1 h-px bg-slate-200"></span>
    </h2>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <?php foreach ($level_classes as $cl): ?>
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden hover:shadow-md transition-all group">
            <div class="bg-gradient-to-br <?php echo $level==='Senior'?'from-primary to-slate-700':'from-blue-700 to-blue-900'; ?> p-5">
                <h3 class="text-white font-black text-xl"><?php echo htmlspecialchars($cl['class_name']); ?></h3>
                <p class="text-white/70 text-sm"><?php echo htmlspecialchars($cl['arm']); ?></p>
            </div>
            <div class="p-4 space-y-2.5">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-500">Students</span>
                    <span class="font-bold text-primary"><?php echo $cl['student_count']; ?></span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-500">Subjects</span>
                    <span class="font-bold text-primary"><?php echo $cl['subject_count']; ?></span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-500">Class Teacher</span>
                    <span class="font-semibold text-slate-700 text-xs"><?php echo $cl['teacher_name'] ? htmlspecialchars(explode(' ',$cl['teacher_name'])[0]) : '—'; ?></span>
                </div>
                <div class="pt-2 flex gap-2 border-t border-slate-100">
                    <a href="?edit=<?php echo $cl['id']; ?>"
                        class="flex-1 py-1.5 bg-primary/5 hover:bg-primary/10 text-primary text-xs font-bold rounded-lg transition-all text-center flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-xs">edit</span>Edit
                    </a>
                    <a href="manage_parents.php?class_id=<?php echo $cl['id']; ?>"
                        class="flex-1 py-1.5 bg-gold/10 hover:bg-gold/20 text-gold text-xs font-bold rounded-lg transition-all text-center flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-xs">family_restroom</span>Parents
                    </a>
                    <?php if ($cl['student_count'] == 0 && hasPermission('admin')): ?>
                    <form method="POST" class="inline">
                        <input type="hidden" name="delete_class" value="1">
                        <input type="hidden" name="class_id" value="<?php echo $cl['id']; ?>">
                        <button type="submit" onclick="return confirm('Delete this class?')"
                            class="px-2 py-1.5 hover:bg-red-50 text-red-400 hover:text-red-600 rounded-lg transition-all" title="Delete">
                            <span class="material-symbols-outlined text-sm">delete</span>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<?php if (empty($classes_raw)): ?>
<div class="bg-white rounded-2xl border border-slate-200 p-16 text-center">
    <span class="material-symbols-outlined text-6xl text-slate-200 block mb-3">school</span>
    <p class="font-bold text-slate-700 mb-1">No classes yet</p>
    <button onclick="document.getElementById('addModal').classList.remove('hidden')"
        class="mt-3 inline-flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-primary/90">
        <span class="material-symbols-outlined text-sm">add</span>Add First Class
    </button>
</div>
<?php endif; ?>

<!-- Promote section -->
<?php if (count($classes_raw) >= 2 && hasPermission('admin')): ?>
<div class="mt-8 bg-white rounded-2xl border border-slate-200 p-6">
    <h2 class="font-bold text-slate-900 mb-1 flex items-center gap-2">
        <span class="material-symbols-outlined text-gold">upgrade</span>
        Promote Entire Class
    </h2>
    <p class="text-sm text-slate-500 mb-4">Move all active students from one class to another at end of academic year.</p>
    <form method="POST" class="flex flex-wrap gap-4 items-end" onsubmit="return confirm('This will move ALL active students. Are you sure?')">
        <input type="hidden" name="promote_class" value="1">
        <div>
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1 block">From Class</label>
            <select name="from_class_id" required class="border-slate-200 rounded-xl text-sm px-4 py-2.5 focus:ring-gold focus:border-gold">
                <option value="">Select current class…</option>
                <?php foreach($classes_raw as $cl): ?>
                <option value="<?php echo $cl['id']; ?>">
                    <?php echo htmlspecialchars($cl['class_name'].' '.$cl['arm']); ?>
                    (<?php echo $cl['student_count']; ?> students)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex items-center self-end pb-2.5">
            <span class="material-symbols-outlined text-gold text-2xl">arrow_forward</span>
        </div>
        <div>
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1 block">To Class</label>
            <select name="to_class_id" required class="border-slate-200 rounded-xl text-sm px-4 py-2.5 focus:ring-gold focus:border-gold">
                <option value="">Select destination class…</option>
                <?php foreach($classes_raw as $cl): ?>
                <option value="<?php echo $cl['id']; ?>">
                    <?php echo htmlspecialchars($cl['class_name'].' '.$cl['arm']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit"
            class="px-6 py-2.5 bg-amber-500 text-white font-bold text-sm rounded-xl hover:bg-amber-600 transition-all self-end">
            <span class="material-symbols-outlined text-sm align-middle mr-1">upgrade</span>Promote
        </button>
    </form>
</div>
<?php endif; ?>

</main>
</div>
</div>

<!-- Add Class Modal -->
<div id="addModal" class="<?php echo $edit_class?'hidden':'hidden'; ?> fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl p-7 max-w-md w-full shadow-2xl">
    <h2 class="text-xl font-bold text-slate-900 mb-5 flex items-center gap-2">
        <span class="material-symbols-outlined text-gold">add_circle</span>Add New Class
    </h2>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="add_class" value="1">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1 block">Class Name *</label>
                <select name="class_name" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold px-3 py-2.5">
                    <option value="">Select</option>
                    <?php foreach(['JSS 1','JSS 2','JSS 3','SS 1','SS 2','SS 3'] as $cn): ?>
                    <option><?php echo $cn; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1 block">Level *</label>
                <select name="class_level" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold px-3 py-2.5">
                    <option value="Junior">Junior Secondary</option>
                    <option value="Senior">Senior Secondary</option>
                </select>
            </div>
        </div>
        <div>
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1 block">Arm / Stream</label>
            <input type="text" name="arm" placeholder="e.g. A, B, Science, Arts, Commercial"
                class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold px-4 py-2.5">
            <p class="text-xs text-slate-400 mt-1">Leave blank for single-stream classes</p>
        </div>
        <div>
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1 block">Class Teacher</label>
            <select name="class_teacher_id" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold px-3 py-2.5">
                <option value="">None assigned</option>
                <?php foreach($teachers as $t): ?>
                <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['full_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 bg-gold text-primary py-3 rounded-xl font-bold hover:bg-gold/90">Add Class</button>
            <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')"
                class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl font-semibold hover:bg-slate-200">Cancel</button>
        </div>
    </form>
</div>
</div>

<!-- Edit Class Modal (auto-opens if ?edit= is set) -->
<?php if ($edit_class): ?>
<div id="editModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl p-7 max-w-md w-full shadow-2xl">
    <h2 class="text-xl font-bold text-slate-900 mb-5 flex items-center gap-2">
        <span class="material-symbols-outlined text-gold">edit</span>
        Edit Class — <?php echo htmlspecialchars($edit_class['class_name'].' '.$edit_class['arm']); ?>
    </h2>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="edit_class" value="1">
        <input type="hidden" name="class_id" value="<?php echo $edit_class['id']; ?>">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1 block">Class Name *</label>
                <select name="class_name" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold px-3 py-2.5">
                    <?php foreach(['JSS 1','JSS 2','JSS 3','SS 1','SS 2','SS 3'] as $cn): ?>
                    <option <?php echo $edit_class['class_name']===$cn?'selected':''; ?>><?php echo $cn; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1 block">Level *</label>
                <select name="class_level" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold px-3 py-2.5">
                    <option value="Junior" <?php echo $edit_class['class_level']==='Junior'?'selected':''; ?>>Junior</option>
                    <option value="Senior" <?php echo $edit_class['class_level']==='Senior'?'selected':''; ?>>Senior</option>
                </select>
            </div>
        </div>
        <div>
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1 block">Arm / Stream</label>
            <input type="text" name="arm" value="<?php echo htmlspecialchars($edit_class['arm']); ?>"
                class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold px-4 py-2.5">
        </div>
        <div>
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1 block">Class Teacher</label>
            <select name="class_teacher_id" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold px-3 py-2.5">
                <option value="">None assigned</option>
                <?php foreach($teachers as $t): ?>
                <option value="<?php echo $t['id']; ?>" <?php echo $edit_class['class_teacher_id']==$t['id']?'selected':''; ?>>
                    <?php echo htmlspecialchars($t['full_name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 bg-primary text-white py-3 rounded-xl font-bold hover:bg-primary/90">Save Changes</button>
            <a href="manage_classes.php" class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl font-bold hover:bg-slate-200 text-center">Cancel</a>
        </div>
    </form>
</div>
</div>
<?php endif; ?>

<script>
document.getElementById('addModal').addEventListener('click', function(e) {
    if (e.target===this) this.classList.add('hidden');
});
</script>
</body>
</html>
