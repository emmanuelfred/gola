<?php
require_once 'auth_check.php';
$page_title = "Manage Class Subjects";

$success = '';
$error   = '';

// ── Handle actions ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // Assign a subject to a class
    if ($_POST['action'] === 'assign') {
        $class_id   = intval($_POST['class_id'] ?? 0);
        $subject_id = intval($_POST['subject_id'] ?? 0);
        if ($class_id && $subject_id) {
            $stmt = $conn->prepare("INSERT IGNORE INTO class_subjects (class_id, subject_id) VALUES (?,?)");
            $stmt->bind_param("ii", $class_id, $subject_id);
            $stmt->execute();
            $success = "Subject assigned to class.";
        }
    }

    // Remove a subject from a class
    if ($_POST['action'] === 'remove') {
        $class_id   = intval($_POST['class_id'] ?? 0);
        $subject_id = intval($_POST['subject_id'] ?? 0);
        // Safety: check if results exist for this combo
        $check = $conn->query("SELECT COUNT(*) as c FROM results
            WHERE class_id=$class_id AND subject_id=$subject_id")->fetch_assoc();
        if ($check['c'] > 0) {
            $error = "Cannot remove — {$check['c']} result record(s) exist for this subject in this class.";
        } else {
            $conn->query("DELETE FROM class_subjects WHERE class_id=$class_id AND subject_id=$subject_id");
            $success = "Subject removed from class.";
        }
    }

    // Add a brand-new subject to the subjects master list
    if ($_POST['action'] === 'add_subject') {
        $code     = strtoupper(trim($_POST['subject_code'] ?? ''));
        $name     = trim($_POST['subject_name'] ?? '');
        $category = $_POST['category'] ?? 'Core';
        if (!$code || !$name) {
            $error = 'Subject code and name are required.';
        } else {
            $stmt = $conn->prepare("INSERT INTO subjects (subject_code, subject_name, category, is_active) VALUES (?,?,?,1)");
            $stmt->bind_param("sss", $code, $name, $category);
            if ($stmt->execute()) {
                logActivity('add_subject', "Added subject: $name ($code)");
                $success = "Subject <strong>$name ($code)</strong> added to master list. Now assign it to classes below.";
            } else {
                $error = 'Error: '.$conn->error.' (Code may already exist.)';
            }
        }
    }

    // Bulk-assign: copy all subjects from one class to another
    if ($_POST['action'] === 'bulk_copy') {
        $from_class = intval($_POST['from_class'] ?? 0);
        $to_class   = intval($_POST['to_class']   ?? 0);
        if ($from_class && $to_class && $from_class !== $to_class) {
            $conn->query("INSERT IGNORE INTO class_subjects (class_id, subject_id)
                SELECT $to_class, subject_id FROM class_subjects WHERE class_id=$from_class");
            $affected = $conn->affected_rows;
            $success = "Copied $affected subject(s) from source class.";
        }
    }
}

// ── Data ───────────────────────────────────────────────────────────────────────
$selected_class = intval($_GET['class_id'] ?? 0);

$classes = $conn->query("SELECT id, class_name, arm, class_level FROM classes ORDER BY class_level DESC, class_name, arm")->fetch_all(MYSQLI_ASSOC);

// Subjects already assigned to selected class
$assigned = [];
if ($selected_class) {
    $q = $conn->query("
        SELECT s.id, s.subject_code, s.subject_name, s.category, s.is_active,
               (SELECT COUNT(*) FROM results r WHERE r.class_id=$selected_class AND r.subject_id=s.id) as result_count
        FROM class_subjects cs
        JOIN subjects s ON s.id = cs.subject_id
        WHERE cs.class_id = $selected_class
        ORDER BY s.subject_name
    ");
    while ($r = $q->fetch_assoc()) $assigned[] = $r;
}

// All subjects NOT yet assigned to the selected class
$available = [];
if ($selected_class) {
    $q = $conn->query("
        SELECT id, subject_code, subject_name, category
        FROM subjects
        WHERE is_active=1
          AND id NOT IN (SELECT subject_id FROM class_subjects WHERE class_id=$selected_class)
        ORDER BY subject_name
    ");
    while ($r = $q->fetch_assoc()) $available[] = $r;
}

// All subjects (for master list tab)
$all_subjects = $conn->query("
    SELECT s.*,
        (SELECT COUNT(*) FROM class_subjects cs WHERE cs.subject_id=s.id) as class_count,
        (SELECT COUNT(*) FROM results r WHERE r.subject_id=s.id) as result_count
    FROM subjects s
    ORDER BY s.subject_name
")->fetch_all(MYSQLI_ASSOC);

// Class subject counts
$class_counts = [];
$ccq = $conn->query("SELECT class_id, COUNT(*) as c FROM class_subjects GROUP BY class_id");
while ($r = $ccq->fetch_assoc()) $class_counts[$r['class_id']] = $r['c'];

$active_tab = $_GET['tab'] ?? 'classes';
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
<style>
.sidebar-link.active{background:linear-gradient(90deg,rgba(197,160,89,.1) 0%,transparent 100%);border-left:3px solid #C5A059;color:#C5A059;}
</style>
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
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-2">
            <a href="manage_results.php" class="hover:text-gold">Results</a>
            <span class="material-symbols-outlined text-xs">chevron_right</span>
            <span class="text-slate-800">Class Subjects</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Class Subject Management</h1>
        <p class="text-slate-500 text-sm mt-1">Assign which subjects each class offers. These control what appears in result entry.</p>
    </div>
    <button onclick="document.getElementById('addSubjectModal').classList.remove('hidden')"
        class="inline-flex items-center gap-2 bg-gold text-primary px-5 py-3 rounded-xl font-bold hover:bg-gold/90 shadow-sm">
        <span class="material-symbols-outlined">add_circle</span>New Subject
    </button>
</div>

<!-- Alerts -->
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

<!-- Tabs -->
<div class="flex gap-1 mb-6 bg-slate-100 p-1 rounded-xl w-fit">
    <a href="?tab=classes<?php echo $selected_class ? '&class_id='.$selected_class : ''; ?>"
       class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-all
              <?php echo $active_tab==='classes' ? 'bg-white text-primary shadow-sm' : 'text-slate-600 hover:text-primary'; ?>">
        <span class="material-symbols-outlined text-sm align-middle mr-1">school</span>By Class
    </a>
    <a href="?tab=subjects"
       class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-all
              <?php echo $active_tab==='subjects' ? 'bg-white text-primary shadow-sm' : 'text-slate-600 hover:text-primary'; ?>">
        <span class="material-symbols-outlined text-sm align-middle mr-1">menu_book</span>All Subjects
    </a>
</div>

<?php if ($active_tab === 'classes'): ?>
<!-- ── BY CLASS TAB ─────────────────────────────────────────────── -->

<div class="grid lg:grid-cols-3 gap-6">

    <!-- Left: Class List -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
                <h3 class="font-bold text-slate-800 text-sm">Select a Class</h3>
            </div>
            <div class="divide-y divide-slate-100 max-h-[60vh] overflow-y-auto">
                <?php
                $current_level = '';
                foreach ($classes as $cls):
                    $level_label = $cls['class_level'];
                    if ($level_label !== $current_level):
                        $current_level = $level_label;
                ?>
                <div class="px-4 py-2 bg-slate-50">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider"><?php echo htmlspecialchars($level_label); ?></p>
                </div>
                <?php endif; ?>
                <a href="?tab=classes&class_id=<?php echo $cls['id']; ?>"
                   class="flex items-center justify-between px-4 py-3 hover:bg-gold/5 transition-colors
                          <?php echo $selected_class===$cls['id'] ? 'bg-gold/10 border-l-4 border-gold' : ''; ?>">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-base <?php echo $selected_class===$cls['id'] ? 'text-gold' : 'text-slate-400'; ?>">school</span>
                        <span class="font-semibold text-sm <?php echo $selected_class===$cls['id'] ? 'text-primary' : 'text-slate-700'; ?>">
                            <?php echo htmlspecialchars($cls['class_name'].' '.$cls['arm']); ?>
                        </span>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full font-semibold
                                 <?php echo isset($class_counts[$cls['id']]) ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-slate-400'; ?>">
                        <?php echo $class_counts[$cls['id']] ?? '0'; ?> subj
                    </span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Right: Assigned subjects + add form -->
    <div class="lg:col-span-2">
        <?php if (!$selected_class): ?>
        <div class="bg-white rounded-xl border border-slate-200 p-16 text-center h-full flex flex-col items-center justify-center">
            <span class="material-symbols-outlined text-6xl text-slate-200 mb-3">school</span>
            <p class="font-bold text-slate-700 mb-1">Select a class from the left</p>
            <p class="text-sm text-slate-400">You'll see which subjects are assigned and can add or remove them.</p>
        </div>
        <?php else:
            $sel_class = array_values(array_filter($classes, fn($c)=>$c['id']==$selected_class))[0] ?? null;
        ?>

        <!-- Class header -->
        <div class="bg-primary rounded-xl p-5 mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-white font-bold text-lg">
                    <?php echo htmlspecialchars($sel_class['class_name'].' '.$sel_class['arm']); ?>
                </h3>
                <p class="text-slate-300 text-sm">
                    <?php echo count($assigned); ?> subject(s) assigned
                    <?php if (count($available) > 0): ?>
                    · <?php echo count($available); ?> more available to add
                    <?php endif; ?>
                </p>
            </div>
            <!-- Bulk copy -->
            <form method="POST" class="flex items-center gap-2">
                <input type="hidden" name="action"   value="bulk_copy">
                <input type="hidden" name="to_class" value="<?php echo $selected_class; ?>">
                <select name="from_class" class="text-xs border-0 bg-white/10 text-white rounded-lg px-2 py-1.5 focus:ring-gold">
                    <option value="">Copy from class…</option>
                    <?php foreach ($classes as $cls):
                        if ($cls['id'] === $selected_class) continue; ?>
                    <option value="<?php echo $cls['id']; ?>">
                        <?php echo htmlspecialchars($cls['class_name'].' '.$cls['arm']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="px-3 py-1.5 bg-gold text-primary text-xs font-bold rounded-lg hover:bg-gold/90">
                    Copy
                </button>
            </form>
        </div>

        <!-- Assigned subjects table -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden mb-4">
            <div class="px-5 py-3 border-b border-slate-100 bg-slate-50">
                <h4 class="font-semibold text-slate-800 text-sm">Assigned Subjects</h4>
            </div>
            <?php if (empty($assigned)): ?>
            <div class="px-5 py-10 text-center text-slate-400 text-sm">
                <span class="material-symbols-outlined text-3xl block mb-2">playlist_add</span>
                No subjects assigned yet. Use the form below to add some.
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b text-xs">
                        <tr>
                            <th class="px-4 py-2.5 text-left font-semibold text-slate-500 uppercase">Code</th>
                            <th class="px-4 py-2.5 text-left font-semibold text-slate-500 uppercase">Subject</th>
                            <th class="px-4 py-2.5 text-center font-semibold text-slate-500 uppercase">Type</th>
                            <th class="px-4 py-2.5 text-center font-semibold text-slate-500 uppercase">Results</th>
                            <th class="px-4 py-2.5 text-center font-semibold text-slate-500 uppercase">Remove</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($assigned as $sub):
                            $cat_colors = ['Core'=>'bg-blue-100 text-blue-700','Elective'=>'bg-amber-100 text-amber-700','Vocational'=>'bg-green-100 text-green-700'];
                            $cc = $cat_colors[$sub['category']] ?? 'bg-slate-100 text-slate-600';
                        ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-2.5 font-mono text-xs font-bold text-primary"><?php echo htmlspecialchars($sub['subject_code']); ?></td>
                            <td class="px-4 py-2.5 font-semibold text-slate-800"><?php echo htmlspecialchars($sub['subject_name']); ?></td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?php echo $cc; ?>"><?php echo $sub['category']; ?></span>
                            </td>
                            <td class="px-4 py-2.5 text-center text-xs <?php echo $sub['result_count']>0 ? 'font-bold text-primary' : 'text-slate-300'; ?>">
                                <?php echo $sub['result_count'] > 0 ? $sub['result_count'] : '—'; ?>
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <form method="POST">
                                    <input type="hidden" name="action"     value="remove">
                                    <input type="hidden" name="class_id"   value="<?php echo $selected_class; ?>">
                                    <input type="hidden" name="subject_id" value="<?php echo $sub['id']; ?>">
                                    <button type="submit"
                                        onclick="return confirm('Remove <?php echo htmlspecialchars($sub['subject_name']); ?> from this class?')"
                                        class="p-1 hover:bg-red-50 text-red-400 hover:text-red-600 rounded-lg transition-all"
                                        title="Remove from this class"
                                        <?php echo $sub['result_count']>0 ? 'disabled title="Has results — cannot remove"' : ''; ?>>
                                        <span class="material-symbols-outlined text-sm">remove_circle</span>
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

        <!-- Add subject to this class -->
        <?php if (!empty($available)): ?>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h4 class="font-semibold text-slate-800 text-sm mb-3">
                <span class="material-symbols-outlined text-sm align-middle text-gold mr-1">add_circle</span>
                Add Subject to This Class
            </h4>
            <form method="POST" class="flex gap-3 items-end flex-wrap">
                <input type="hidden" name="action"   value="assign">
                <input type="hidden" name="class_id" value="<?php echo $selected_class; ?>">
                <div class="flex-1 min-w-48">
                    <label class="text-xs font-semibold text-slate-500 mb-1 block">Choose Subject</label>
                    <select name="subject_id" required
                        class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
                        <option value="">Select a subject…</option>
                        <?php
                        $current_cat = '';
                        foreach ($available as $av):
                            if ($av['category'] !== $current_cat) {
                                if ($current_cat) echo '</optgroup>';
                                echo '<optgroup label="'.$av['category'].'">';
                                $current_cat = $av['category'];
                            }
                        ?>
                        <option value="<?php echo $av['id']; ?>">
                            <?php echo htmlspecialchars($av['subject_name'].' ('.$av['subject_code'].')'); ?>
                        </option>
                        <?php endforeach; if ($current_cat) echo '</optgroup>'; ?>
                    </select>
                </div>
                <button type="submit"
                    class="px-5 py-2.5 bg-gold text-primary text-sm font-bold rounded-lg hover:bg-gold/90 transition-all">
                    Assign
                </button>
            </form>
        </div>
        <?php else: ?>
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-center text-sm text-slate-500">
            All available subjects are already assigned to this class.
        </div>
        <?php endif; ?>

        <?php endif; // end if selected_class ?>
    </div>
</div>

<?php else: ?>
<!-- ── ALL SUBJECTS TAB ──────────────────────────────────────────── -->

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b text-xs">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-500 uppercase">Code</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-500 uppercase">Subject Name</th>
                    <th class="px-4 py-3 text-center font-semibold text-slate-500 uppercase">Category</th>
                    <th class="px-4 py-3 text-center font-semibold text-slate-500 uppercase">Used in Classes</th>
                    <th class="px-4 py-3 text-center font-semibold text-slate-500 uppercase">Results</th>
                    <th class="px-4 py-3 text-center font-semibold text-slate-500 uppercase">Active</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($all_subjects as $sub):
                    $cat_colors = ['Core'=>'bg-blue-100 text-blue-700','Elective'=>'bg-amber-100 text-amber-700','Vocational'=>'bg-green-100 text-green-700'];
                    $cc = $cat_colors[$sub['category']] ?? 'bg-slate-100 text-slate-600';
                ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-mono text-xs font-bold text-primary"><?php echo htmlspecialchars($sub['subject_code']); ?></td>
                    <td class="px-4 py-3 font-semibold text-slate-800"><?php echo htmlspecialchars($sub['subject_name']); ?></td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?php echo $cc; ?>"><?php echo $sub['category']; ?></span>
                    </td>
                    <td class="px-4 py-3 text-center font-semibold <?php echo $sub['class_count']>0 ? 'text-primary' : 'text-slate-300'; ?>">
                        <?php echo $sub['class_count'] ?: '—'; ?>
                    </td>
                    <td class="px-4 py-3 text-center text-xs <?php echo $sub['result_count']>0 ? 'font-bold text-primary' : 'text-slate-300'; ?>">
                        <?php echo $sub['result_count'] ?: '—'; ?>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <?php if ($sub['is_active']): ?>
                        <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-semibold rounded-full">Active</span>
                        <?php else: ?>
                        <span class="px-2 py-0.5 bg-slate-100 text-slate-500 text-xs font-semibold rounded-full">Hidden</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

</main>
</div>
</div>

<!-- Add Subject Modal -->
<div id="addSubjectModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl p-8 max-w-md w-full shadow-2xl">
    <h2 class="text-xl font-bold text-slate-900 mb-5 flex items-center gap-2">
        <span class="material-symbols-outlined text-gold">add_circle</span>
        Add New Subject
    </h2>
    <p class="text-xs text-slate-500 mb-4">This adds the subject to the master list. After adding, go to a class and assign it there.</p>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="add_subject">
        <div>
            <label class="text-xs font-semibold text-slate-600 mb-1 block">
                Subject Code <span class="text-red-500">*</span>
            </label>
            <input type="text" name="subject_code" required maxlength="10" placeholder="e.g. MTH, ENG, PHY"
                class="w-full border-slate-200 rounded-xl text-sm uppercase font-mono focus:ring-gold focus:border-gold"
                style="text-transform:uppercase">
            <p class="text-xs text-slate-400 mt-1">Short unique code — max 10 chars.</p>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 mb-1 block">
                Subject Name <span class="text-red-500">*</span>
            </label>
            <input type="text" name="subject_name" required placeholder="e.g. Mathematics"
                class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 mb-1 block">Category</label>
            <select name="category" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold">
                <option value="Core">Core (required for all)</option>
                <option value="Elective">Elective (optional)</option>
                <option value="Vocational">Vocational (practical)</option>
            </select>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 bg-gold text-primary py-3 rounded-xl font-bold hover:bg-gold/90">Add Subject</button>
            <button type="button" onclick="document.getElementById('addSubjectModal').classList.add('hidden')"
                class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl font-semibold hover:bg-slate-200">Cancel</button>
        </div>
    </form>
</div>
</div>

<script>
document.getElementById('addSubjectModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
});
</script>
</body>
</html>
