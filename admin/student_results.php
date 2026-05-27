<?php
require_once 'auth_check.php';
$page_title = "Class Results";

$session_id = intval($_GET['session_id'] ?? 0);
$term_id    = intval($_GET['term_id']    ?? 0);
$class_id   = intval($_GET['class_id']   ?? 0);

if (!$session_id || !$term_id || !$class_id) {
    header('Location: manage_results.php');
    exit;
}

$class_info   = $conn->query("SELECT class_name, arm FROM classes WHERE id=$class_id")->fetch_assoc();
$session_info = $conn->query("SELECT session_name FROM academic_sessions WHERE id=$session_id")->fetch_assoc();
$term_names   = [1=>'First Term', 2=>'Second Term', 3=>'Third Term'];

if (!$class_info || !$session_info) { header('Location: manage_results.php'); exit; }

// Count total subjects for this class
$total_subjects_row = $conn->query("SELECT COUNT(*) as c FROM class_subjects WHERE class_id=$class_id")->fetch_assoc();
$total_subjects = intval($total_subjects_row['c']);

// Fetch all active students in this class
$students = $conn->query("
    SELECT id, student_id, first_name, COALESCE(middle_name,'') AS middle_name, last_name, gender
    FROM students
    WHERE class_id=$class_id AND status='Active'
    ORDER BY last_name, first_name
")->fetch_all(MYSQLI_ASSOC);

// For each student — how many subjects have scores entered this term
$progress = [];
if ($students) {
    $ids = implode(',', array_column($students, 'id'));
    $pq = $conn->query("
        SELECT student_id, COUNT(*) as entered
        FROM results
        WHERE student_id IN ($ids) AND session_id=$session_id AND term_id=$term_id
        GROUP BY student_id
    ");
    while ($p = $pq->fetch_assoc()) $progress[$p['student_id']] = intval($p['entered']);
}

// Published summaries
$published = [];
$pubq = $conn->query("
    SELECT student_id, published FROM result_summary
    WHERE class_id=$class_id AND session_id=$session_id AND term_id=$term_id
");
while ($pub = $pubq->fetch_assoc()) $published[$pub['student_id']] = $pub['published'];

$total_students = count($students);
$fully_done  = 0;
$partially   = 0;
$not_started = 0;
foreach ($students as $st) {
    $entered = $progress[$st['id']] ?? 0;
    if ($total_subjects > 0 && $entered >= $total_subjects) $fully_done++;
    elseif ($entered > 0) $partially++;
    else $not_started++;
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

<!-- Breadcrumb -->
<div class="flex items-center gap-2 text-xs text-slate-500 mb-4">
    <a href="dashboard.php" class="hover:text-gold">Dashboard</a>
    <span class="material-symbols-outlined text-xs">chevron_right</span>
    <a href="manage_results.php" class="hover:text-gold">Results</a>
    <span class="material-symbols-outlined text-xs">chevron_right</span>
    <span class="text-slate-800">
        <?php echo htmlspecialchars($class_info['class_name'].' '.$class_info['arm']); ?>
        — <?php echo htmlspecialchars($session_info['session_name']); ?>
        <?php echo htmlspecialchars($term_names[$term_id] ?? ''); ?>
    </span>
</div>

<!-- Header -->
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">
            <?php echo htmlspecialchars($class_info['class_name'].' '.$class_info['arm']); ?>
            <span class="text-slate-400 font-normal text-lg">— Student Results</span>
        </h1>
        <div class="flex flex-wrap gap-2 mt-2">
            <span class="px-3 py-1 bg-gold/10 text-gold text-xs font-semibold rounded-full">
                <?php echo htmlspecialchars($session_info['session_name']); ?>
            </span>
            <span class="px-3 py-1 bg-slate-100 text-slate-600 text-xs font-semibold rounded-full">
                <?php echo htmlspecialchars($term_names[$term_id] ?? 'Term '.$term_id); ?>
            </span>
            <span class="px-3 py-1 bg-primary/10 text-primary text-xs font-semibold rounded-full">
                <?php echo $total_subjects; ?> subjects per student
            </span>
        </div>
    </div>
    <a href="manage_results.php" class="inline-flex items-center gap-1 px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50">
        <span class="material-symbols-outlined text-sm">arrow_back</span>Back
    </a>
</div>

<!-- Progress Stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
        <p class="text-2xl font-black text-slate-900"><?php echo $total_students; ?></p>
        <p class="text-xs text-slate-500 font-semibold uppercase mt-1">Total Students</p>
    </div>
    <div class="bg-white rounded-xl border border-green-200 p-4 text-center">
        <p class="text-2xl font-black text-green-600"><?php echo $fully_done; ?></p>
        <p class="text-xs text-slate-500 font-semibold uppercase mt-1">Fully Entered</p>
    </div>
    <div class="bg-white rounded-xl border border-amber-200 p-4 text-center">
        <p class="text-2xl font-black text-amber-500"><?php echo $partially; ?></p>
        <p class="text-xs text-slate-500 font-semibold uppercase mt-1">Partial</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
        <p class="text-2xl font-black text-slate-400"><?php echo $not_started; ?></p>
        <p class="text-xs text-slate-500 font-semibold uppercase mt-1">Not Started</p>
    </div>
</div>

<!-- Search/Filter bar -->
<div class="flex gap-3 mb-5">
    <div class="relative flex-1 max-w-xs">
        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-base">search</span>
        <input type="text" id="studentSearch" placeholder="Search student name…"
            class="w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold bg-white">
    </div>
    <select id="statusFilter" class="border border-slate-200 rounded-xl text-sm px-3 py-2.5 bg-white focus:ring-gold focus:border-gold">
        <option value="">All Students</option>
        <option value="complete">Fully Entered</option>
        <option value="partial">Partial</option>
        <option value="empty">Not Started</option>
        <option value="published">Published</option>
    </select>
</div>

<!-- Student Cards Grid -->
<?php if (empty($students)): ?>
<div class="bg-white rounded-xl border border-slate-200 p-16 text-center">
    <span class="material-symbols-outlined text-6xl text-slate-200 block mb-3">person_off</span>
    <p class="font-bold text-slate-700">No active students in this class.</p>
    <p class="text-sm text-slate-400 mt-1">Register students first via Student Management.</p>
</div>
<?php else: ?>
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4" id="studentGrid">
    <?php foreach ($students as $sn => $st):
        $entered   = $progress[$st['id']] ?? 0;
        $is_pub    = $published[$st['id']] ?? false;
        $pct       = $total_subjects > 0 ? round(($entered / $total_subjects) * 100) : 0;

        if ($total_subjects > 0 && $entered >= $total_subjects) {
            $status = 'complete';
            $status_label = 'Complete';
            $status_color = 'bg-green-100 text-green-700';
            $bar_color    = 'bg-green-500';
            $border       = 'border-green-200';
        } elseif ($entered > 0) {
            $status = 'partial';
            $status_label = 'Partial';
            $status_color = 'bg-amber-100 text-amber-700';
            $bar_color    = 'bg-amber-400';
            $border       = 'border-amber-200';
        } else {
            $status = 'empty';
            $status_label = 'Not Started';
            $status_color = 'bg-slate-100 text-slate-500';
            $bar_color    = 'bg-slate-200';
            $border       = 'border-slate-200';
        }

        $entry_url = 'enter_student_results.php?session_id='.$session_id.'&term_id='.$term_id.'&class_id='.$class_id.'&student_id='.$st['id'];
        $initials  = strtoupper(substr($st['first_name'],0,1).substr($st['last_name'],0,1));
    ?>
    <a href="<?php echo $entry_url; ?>"
       class="student-card block bg-white rounded-xl border-2 <?php echo $border; ?> p-5 hover:shadow-md hover:-translate-y-0.5 transition-all group"
       data-name="<?php echo strtolower($st['first_name'].' '.$st['last_name']); ?>"
       data-status="<?php echo $status; ?>"
       data-published="<?php echo $is_pub ? 'published' : ''; ?>">

        <div class="flex items-start gap-3 mb-3">
            <!-- Avatar -->
            <div class="w-11 h-11 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0 font-bold text-primary text-sm group-hover:bg-gold/20 group-hover:text-gold transition-all">
                <?php echo $initials; ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-bold text-slate-900 truncate">
                    <?php echo htmlspecialchars($st['last_name'].', '.$st['first_name'].($st['middle_name']?' '.$st['middle_name']:'')); ?>
                </p>
                <p class="text-xs text-slate-500 font-mono"><?php echo htmlspecialchars($st['student_id']); ?></p>
            </div>
            <div class="flex flex-col items-end gap-1">
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?php echo $status_color; ?>">
                    <?php echo $status_label; ?>
                </span>
                <?php if ($is_pub): ?>
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-primary/10 text-primary">Published</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Progress bar -->
        <div class="mb-2">
            <div class="flex justify-between text-xs text-slate-500 mb-1">
                <span><?php echo $entered; ?> of <?php echo $total_subjects; ?> subjects entered</span>
                <span class="font-semibold"><?php echo $pct; ?>%</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-2">
                <div class="<?php echo $bar_color; ?> h-2 rounded-full transition-all" style="width:<?php echo $pct; ?>%"></div>
            </div>
        </div>

        <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-100">
            <span class="text-xs text-slate-400"><?php echo $st['gender']; ?></span>
            <span class="text-xs text-primary font-semibold group-hover:text-gold transition-all flex items-center gap-1">
                <?php echo $entered > 0 ? 'Update Results' : 'Enter Results'; ?>
                <span class="material-symbols-outlined text-xs">arrow_forward</span>
            </span>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

</main>
</div>
</div>

<script>
const searchInput  = document.getElementById('studentSearch');
const statusFilter = document.getElementById('statusFilter');
const cards        = document.querySelectorAll('.student-card');

function filterCards() {
    const q   = searchInput.value.toLowerCase().trim();
    const st  = statusFilter.value;
    cards.forEach(card => {
        const nameMatch   = !q  || card.dataset.name.includes(q);
        const statusMatch = !st || card.dataset.status === st || (st === 'published' && card.dataset.published === 'published');
        card.closest('a').style.display = (nameMatch && statusMatch) ? '' : 'none';
    });
}
searchInput.addEventListener('input', filterCards);
statusFilter.addEventListener('change', filterCards);
</script>
</body>
</html>
