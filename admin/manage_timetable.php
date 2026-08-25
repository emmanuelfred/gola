<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('timetable');
require_once 'includes/timetable_helper.php';
$page_title = "Class Timetable";

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_slot') {
    $class_id  = intval($_POST['class_id'] ?? 0);
    $period_id = intval($_POST['period_id'] ?? 0);
    $day       = $_POST['day'] ?? '';
    $subject_id = intval($_POST['subject_id'] ?? 0) ?: null;

    if ($class_id && $period_id && in_array($day, TIMETABLE_DAYS, true)) {
        setTimetableSlot($conn, $class_id, $period_id, $day, $subject_id);
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Invalid request.']);
    }
    exit;
}

$class_id = intval($_GET['class_id'] ?? 0);
$classes  = $conn->query("SELECT id, class_name, arm, class_level FROM classes ORDER BY class_level DESC, class_name, arm")->fetch_all(MYSQLI_ASSOC);
$periods  = getTimetablePeriods($conn);

$sel_class = null;
$grid = [];
$class_subjects = [];
if ($class_id) {
    foreach ($classes as $c) { if ($c['id'] == $class_id) { $sel_class = $c; break; } }
    $grid = getClassTimetable($conn, $class_id);
    $class_subjects = $conn->query("
        SELECT s.id, s.subject_name, s.subject_code, cs.teacher_id, st.first_name as t_first, st.last_name as t_last
        FROM class_subjects cs
        JOIN subjects s ON s.id = cs.subject_id
        LEFT JOIN staff st ON st.id = cs.teacher_id
        WHERE cs.class_id = $class_id
        ORDER BY s.subject_name
    ")->fetch_all(MYSQLI_ASSOC);
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
        <h1 class="text-2xl font-bold text-slate-900">Class Timetable</h1>
        <p class="text-slate-500 text-sm mt-1">Click any cell to assign a subject. The teacher shown comes from <a href="manage_class_subjects.php" class="text-primary font-semibold hover:underline">Class Subjects</a> — assign teachers there.</p>
    </div>
    <form method="GET" class="flex items-center gap-2">
        <select name="class_id" onchange="this.form.submit()" class="text-sm border-slate-200 rounded-lg focus:ring-gold focus:border-gold">
            <option value="">Select a class…</option>
            <?php foreach ($classes as $c): ?>
            <option value="<?php echo $c['id']; ?>" <?php echo $class_id==$c['id']?'selected':''; ?>><?php echo htmlspecialchars($c['class_name'].' '.$c['arm']); ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div id="toast" class="hidden mb-5 p-3 bg-green-50 border border-green-200 rounded-xl text-green-800 text-sm flex items-center gap-2">
    <span class="material-symbols-outlined text-green-600 text-sm">check_circle</span><span id="toastMsg">Saved.</span>
</div>

<?php if (!$class_id): ?>
<div class="bg-white rounded-2xl border border-slate-200 p-16 text-center">
    <span class="material-symbols-outlined text-6xl text-slate-200 block mb-3">calendar_view_week</span>
    <p class="font-bold text-slate-700 mb-1">Select a class above</p>
    <p class="text-sm text-slate-400">You'll see and edit its weekly timetable grid.</p>
</div>
<?php elseif (empty($periods)): ?>
<div class="bg-amber-50 border border-amber-200 rounded-2xl p-8 text-center text-amber-700">
    No periods set up yet. Go to <a href="manage_timetable_periods.php" class="underline font-semibold">Timetable Periods</a> first.
</div>
<?php else: ?>

<div class="mb-4 bg-primary rounded-xl p-5">
    <h3 class="text-white font-bold text-lg"><?php echo htmlspecialchars($sel_class['class_name'].' '.$sel_class['arm']); ?></h3>
    <p class="text-slate-300 text-sm"><?php echo count($class_subjects); ?> subject(s) available to schedule</p>
</div>

<?php if (empty($class_subjects)): ?>
<div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-700 text-sm">
    This class has no subjects assigned yet — <a href="manage_class_subjects.php?tab=classes&class_id=<?php echo $class_id; ?>" class="underline font-semibold">assign some first</a> so you have something to put on the timetable.
</div>
<?php endif; ?>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden overflow-x-auto">
    <table class="w-full text-sm border-collapse">
        <thead>
            <tr class="bg-slate-50 border-b border-slate-200">
                <th class="px-3 py-3 text-left font-semibold text-slate-500 uppercase text-xs w-40">Period</th>
                <?php foreach (TIMETABLE_DAYS as $day): ?>
                <th class="px-3 py-3 text-center font-semibold text-slate-500 uppercase text-xs"><?php echo $day; ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach ($periods as $p): ?>
            <tr class="<?php echo $p['is_break'] ? 'bg-amber-50/50' : ''; ?>">
                <td class="px-3 py-2 align-top">
                    <p class="font-semibold text-slate-700 text-xs"><?php echo htmlspecialchars($p['period_name']); ?></p>
                    <p class="text-xs text-slate-400"><?php echo date('g:i A', strtotime($p['start_time'])); ?>–<?php echo date('g:i A', strtotime($p['end_time'])); ?></p>
                </td>
                <?php foreach (TIMETABLE_DAYS as $day):
                    $cell = $grid[$p['id']][$day] ?? null;
                ?>
                <td class="px-2 py-2 align-top border-l border-slate-100">
                    <?php if ($p['is_break']): ?>
                    <div class="text-center text-xs text-amber-600 font-semibold py-2"><?php echo htmlspecialchars($p['period_name']); ?></div>
                    <?php else: ?>
                    <select
                        class="slot-select w-full text-xs border-slate-200 rounded-lg focus:ring-gold focus:border-gold py-1.5"
                        data-class="<?php echo $class_id; ?>" data-period="<?php echo $p['id']; ?>" data-day="<?php echo $day; ?>">
                        <option value="">— Free —</option>
                        <?php foreach ($class_subjects as $cs): ?>
                        <option value="<?php echo $cs['id']; ?>" <?php echo ($cell && $cell['subject_id']==$cs['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cs['subject_code']); ?><?php echo $cs['t_first'] ? ' — '.htmlspecialchars($cs['t_first']) : ''; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($cell && $cell['subject_id'] && !$cell['teacher_id']): ?>
                    <p class="text-xs text-amber-500 mt-0.5">No teacher assigned</p>
                    <?php endif; ?>
                    <?php endif; ?>
                </td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

</main>
</div>
</div>

<script>
document.querySelectorAll('.slot-select').forEach(sel => {
    sel.addEventListener('change', function() {
        const toast = document.getElementById('toast');
        const toastMsg = document.getElementById('toastMsg');
        fetch('manage_timetable.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: 'save_slot',
                class_id: this.dataset.class,
                period_id: this.dataset.period,
                day: this.dataset.day,
                subject_id: this.value
            })
        })
        .then(r => r.json())
        .then(data => {
            toastMsg.textContent = data.ok ? 'Saved.' : (data.error || 'Something went wrong.');
            toast.classList.remove('hidden');
            toast.classList.toggle('bg-red-50', !data.ok);
            toast.classList.toggle('border-red-200', !data.ok);
            toast.classList.toggle('text-red-800', !data.ok);
            setTimeout(() => toast.classList.add('hidden'), 2000);
            if (data.ok) location.reload();
        })
        .catch(() => { toastMsg.textContent = 'Network error — try again.'; toast.classList.remove('hidden'); });
    });
});
</script>
</body>
</html>
