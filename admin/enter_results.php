<?php
require_once 'auth_check.php';
$page_title = "Enter Results";

$session_id = intval($_GET['session_id'] ?? $_POST['session_id'] ?? 0);
$term_id    = intval($_GET['term_id']    ?? $_POST['term_id']    ?? 0);
$class_id   = intval($_GET['class_id']   ?? $_POST['class_id']   ?? 0);
$subject_id = intval($_GET['subject_id'] ?? $_POST['subject_id'] ?? 0);
$tab        = $_GET['tab'] ?? $_POST['tab'] ?? 'scores';

if (!$session_id || !$term_id || !$class_id || !$subject_id) {
    header('Location: manage_results.php');
    exit;
}

// ── Info lookups ──────────────────────────────────────────────────────────────
$class_info   = $conn->query("SELECT class_name, arm FROM classes WHERE id=$class_id")->fetch_assoc();
$subject_info = $conn->query("SELECT subject_name, subject_code FROM subjects WHERE id=$subject_id")->fetch_assoc();
$session_info = $conn->query("SELECT session_name FROM academic_sessions WHERE id=$session_id")->fetch_assoc();
$term_names   = [1=>'First Term', 2=>'Second Term', 3=>'Third Term'];

if (!$class_info || !$subject_info || !$session_info) {
    header('Location: manage_results.php');
    exit;
}

// ── Grade helper ──────────────────────────────────────────────────────────────
function calcGrade($score) {
    global $conn;
    $stmt = $conn->prepare("SELECT grade, remark FROM grading_system WHERE ? BETWEEN min_score AND max_score LIMIT 1");
    $stmt->bind_param("d", $score);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    return $r ?: ['grade'=>'F9','remark'=>'Fail'];
}

$success = '';
$error   = '';

// ── SAVE SCORES ───────────────────────────────────────────────────────────────
// Scoring: CA1 (20) + CA2 (20) + Exam (60) = 100
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_results'])) {
    $student_ids = $_POST['student_ids'] ?? [];
    $ca1_scores  = $_POST['ca1']         ?? [];
    $ca2_scores  = $_POST['ca2']         ?? [];
    $exam_scores = $_POST['exam']        ?? [];
    $remarks     = $_POST['remarks']     ?? [];

    $saved  = 0;
    $errors = [];

    foreach ($student_ids as $i => $sid) {
        $sid  = intval($sid);
        $ca1  = floatval($ca1_scores[$i] ?? 0);
        $ca2  = floatval($ca2_scores[$i] ?? 0);
        $exam = floatval($exam_scores[$i] ?? 0);

        // Skip blank rows
        if ($ca1 == 0 && $ca2 == 0 && $exam == 0) continue;

        // Validate bounds
        if ($ca1  > 20) { $errors[] = "Row ".($i+1).": CA1 cannot exceed 20"; continue; }
        if ($ca2  > 20) { $errors[] = "Row ".($i+1).": CA2 cannot exceed 20"; continue; }
        if ($exam > 60) { $errors[] = "Row ".($i+1).": Exam cannot exceed 60"; continue; }

        $total      = $ca1 + $ca2 + $exam;
        $grade_info = calcGrade($total);
        $remark     = trim($remarks[$i] ?? '') ?: $grade_info['remark'];

        $stmt = $conn->prepare("
            INSERT INTO results
                (student_id, subject_id, class_id, session_id, term_id,
                 ca1, ca2, exam_score, grade, remark, entered_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                ca1=VALUES(ca1), ca2=VALUES(ca2),
                exam_score=VALUES(exam_score),
                grade=VALUES(grade), remark=VALUES(remark),
                entered_by=VALUES(entered_by)
        ");
        $stmt->bind_param("iiiiidddss i",
            $sid, $subject_id, $class_id, $session_id, $term_id,
            $ca1, $ca2, $exam, $grade_info['grade'], $remark, $admin_id);
        if ($stmt->execute()) $saved++;
    }

    if ($saved > 0) {
        logActivity('enter_results',
            "Saved results for $saved students in {$subject_info['subject_name']} — {$class_info['class_name']} {$class_info['arm']}");
        $success = "Results saved for <strong>$saved student(s)</strong> successfully!";
        $tab = 'scores'; // stay on scores tab after save
    }
    if ($errors) $error = implode('<br>', $errors);
}

// ── SAVE SUMMARY / COMMENTS ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_summary'])) {
    $sum_ids          = $_POST['sum_student_ids']  ?? [];
    $positions        = $_POST['position']          ?? [];
    $class_sizes      = $_POST['class_size']        ?? [];
    $att_present      = $_POST['att_present']       ?? [];
    $att_total        = $_POST['att_total']         ?? [];
    $teacher_comments = $_POST['teacher_comment']   ?? [];
    $teacher_name     = trim($_POST['teacher_name'] ?? '');
    $principal_comments = $_POST['principal_comment'] ?? [];
    $principal_name   = trim($_POST['principal_name'] ?? 'DR. SAMUEL OMOGO');
    $next_term_date   = trim($_POST['next_term_begins'] ?? '');

    $saved = 0;
    foreach ($sum_ids as $i => $sid) {
        $sid = intval($sid);

        // Recalculate totals from results table
        $res   = $conn->query("SELECT COUNT(*) as cnt, SUM(total_score) as total
                               FROM results
                               WHERE student_id=$sid AND session_id=$session_id AND term_id=$term_id");
        $rdata = $res->fetch_assoc();
        if (!$rdata || $rdata['cnt'] == 0) continue;

        $total_subj = intval($rdata['cnt']);
        $total_score = floatval($rdata['total']);
        $avg         = $total_subj > 0 ? round($total_score / $total_subj, 2) : 0;
        $grade_info  = calcGrade($avg);

        $pos         = trim($positions[$i] ?? '');
        $cs          = intval($class_sizes[$i] ?? 0);
        $ap          = intval($att_present[$i] ?? 0);
        $at          = intval($att_total[$i]   ?? 0);
        $tc          = trim($teacher_comments[$i] ?? '');
        $pc          = trim($principal_comments[$i] ?? '');
        $result_uid  = 'GOLA-' . date('Y') . '-' . str_pad($sid, 5, '0', STR_PAD_LEFT);
        $next_term   = $next_term_date ?: null;

        $stmt = $conn->prepare("
            INSERT INTO result_summary
                (student_id, class_id, session_id, term_id,
                 total_subjects, total_score, average_score, overall_grade,
                 overall_position, class_size, attendance_present, attendance_total,
                 class_teacher_comment, class_teacher_name,
                 principal_comment, principal_name,
                 next_term_begins, result_unique_id, date_issued, published)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 1)
            ON DUPLICATE KEY UPDATE
                total_subjects=VALUES(total_subjects),
                total_score=VALUES(total_score),
                average_score=VALUES(average_score),
                overall_grade=VALUES(overall_grade),
                overall_position=VALUES(overall_position),
                class_size=VALUES(class_size),
                attendance_present=VALUES(attendance_present),
                attendance_total=VALUES(attendance_total),
                class_teacher_comment=VALUES(class_teacher_comment),
                class_teacher_name=VALUES(class_teacher_name),
                principal_comment=VALUES(principal_comment),
                principal_name=VALUES(principal_name),
                next_term_begins=VALUES(next_term_begins),
                result_unique_id=VALUES(result_unique_id),
                published=1
        ");
        // i  i        i          i       i          d           d    s                  s   i   i   i
        // sid class_id session_id term_id total_subj total_score avg  overall_grade      pos cs  ap  at
        // s                       s             s              s              s       s
        // class_teacher_comment   class_teacher_name principal_comment principal_name next_term result_uid
        $stmt->bind_param("iiiiiiddsiiiissssss",
            $sid, $class_id, $session_id, $term_id,
            $total_subj, $total_score, $avg, $grade_info['grade'],
            $pos, $cs, $ap, $at,
            $tc, $teacher_name, $pc, $principal_name,
            $next_term, $result_uid);
        if ($stmt->execute()) $saved++;
    }

    if ($saved) {
        logActivity('publish_results', "Published summary for $saved students — {$class_info['class_name']} {$class_info['arm']}");
        $success = "Summary published for <strong>$saved student(s)</strong>.";
    }
    $tab = 'summary';
}

// ── Fetch students ────────────────────────────────────────────────────────────
$students = $conn->query("
    SELECT id, student_id, first_name, COALESCE(middle_name,'') AS middle_name, last_name
    FROM students
    WHERE class_id = $class_id AND status = 'Active'
    ORDER BY last_name, first_name
");

// ── Existing scores ───────────────────────────────────────────────────────────
$existing = [];
$ex_q = $conn->query("
    SELECT * FROM results
    WHERE class_id=$class_id AND session_id=$session_id AND term_id=$term_id AND subject_id=$subject_id
");
while ($e = $ex_q->fetch_assoc()) $existing[$e['student_id']] = $e;

// ── Existing summaries ────────────────────────────────────────────────────────
$summaries = [];
$sm_q = $conn->query("
    SELECT * FROM result_summary
    WHERE class_id=$class_id AND session_id=$session_id AND term_id=$term_id
");
while ($s = $sm_q->fetch_assoc()) $summaries[$s['student_id']] = $s;

// ── Compute per-student running totals (for summary tab) ──────────────────────
$student_totals = [];
$tot_q = $conn->query("
    SELECT student_id, COUNT(*) as subj_cnt, SUM(total_score) as grand_total,
           AVG(total_score) as average
    FROM results
    WHERE class_id=$class_id AND session_id=$session_id AND term_id=$term_id
    GROUP BY student_id
");
while ($t = $tot_q->fetch_assoc()) $student_totals[$t['student_id']] = $t;
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
input:focus{outline:none;}
.score-input::-webkit-outer-spin-button,.score-input::-webkit-inner-spin-button{-webkit-appearance:none;}
.score-input[type=number]{-moz-appearance:textfield;}
</style>
</head>
<body class="bg-slate-50 font-sans">
<div class="flex h-screen overflow-hidden">
<?php include 'admin_sidebar.php'; ?>
<div class="flex-1 flex flex-col overflow-hidden">
<?php include 'admin_topbar.php'; ?>
<main class="flex-1 overflow-y-auto p-6 lg:p-8">

<!-- Breadcrumb & Header -->
<div class="mb-6">
    <div class="flex items-center gap-2 text-xs text-slate-500 mb-3">
        <a href="dashboard.php" class="hover:text-gold">Dashboard</a>
        <span class="material-symbols-outlined text-xs">chevron_right</span>
        <a href="manage_results.php" class="hover:text-gold">Results</a>
        <span class="material-symbols-outlined text-xs">chevron_right</span>
        <span class="text-slate-800">Enter Results</span>
    </div>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900"><?php echo htmlspecialchars($subject_info['subject_name']); ?></h1>
            <div class="flex flex-wrap gap-2 mt-2">
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-primary/10 text-primary text-xs font-semibold rounded-full">
                    <span class="material-symbols-outlined text-xs">school</span>
                    <?php echo htmlspecialchars($class_info['class_name'].' '.$class_info['arm']); ?>
                </span>
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-gold/10 text-gold text-xs font-semibold rounded-full">
                    <span class="material-symbols-outlined text-xs">calendar_today</span>
                    <?php echo htmlspecialchars($session_info['session_name']); ?>
                </span>
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-slate-100 text-slate-700 text-xs font-semibold rounded-full">
                    <span class="material-symbols-outlined text-xs">event_note</span>
                    <?php echo htmlspecialchars($term_names[$term_id] ?? 'Term '.$term_id); ?>
                </span>
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-blue-50 text-blue-700 text-xs font-semibold rounded-full">
                    <span class="material-symbols-outlined text-xs">tag</span>
                    <?php echo htmlspecialchars($subject_info['subject_code']); ?>
                </span>
            </div>
        </div>
        <a href="manage_results.php" class="inline-flex items-center gap-1 px-4 py-2 bg-slate-100 text-slate-700 text-sm font-semibold rounded-lg hover:bg-slate-200 transition-all">
            <span class="material-symbols-outlined text-sm">arrow_back</span>Back
        </a>
    </div>
</div>

<!-- Alerts -->
<?php if ($success): ?>
<div class="mb-5 p-4 bg-green-50 border border-green-200 rounded-xl flex items-start gap-3">
    <span class="material-symbols-outlined text-green-600 flex-shrink-0">check_circle</span>
    <p class="text-green-800 text-sm"><?php echo $success; ?></p>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl flex items-start gap-3">
    <span class="material-symbols-outlined text-red-600 flex-shrink-0">error</span>
    <p class="text-red-800 text-sm"><?php echo $error; ?></p>
</div>
<?php endif; ?>

<!-- Scoring Guide -->
<div class="mb-5 p-4 bg-blue-50 border border-blue-200 rounded-xl">
    <div class="flex flex-wrap gap-6 text-sm">
        <div class="flex items-center gap-2">
            <span class="w-8 h-8 bg-blue-600 text-white rounded-lg flex items-center justify-center font-bold text-xs">CA1</span>
            <span class="text-blue-900 font-semibold">Max 20 marks</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="w-8 h-8 bg-blue-700 text-white rounded-lg flex items-center justify-center font-bold text-xs">CA2</span>
            <span class="text-blue-900 font-semibold">Max 20 marks</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="w-8 h-8 bg-primary text-white rounded-lg flex items-center justify-center font-bold text-xs">EXM</span>
            <span class="text-blue-900 font-semibold">Max 60 marks</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="w-8 h-8 bg-gold text-primary rounded-lg flex items-center justify-center font-bold text-xs">=</span>
            <span class="text-blue-900 font-bold">Total 100</span>
        </div>
        <div class="ml-auto text-blue-700 text-xs">
            <span class="material-symbols-outlined align-middle text-sm">info</span>
            Leave all fields blank (0) to skip a student. Totals calculate automatically.
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="flex gap-1 mb-6 bg-slate-100 p-1 rounded-xl w-fit">
    <a href="?session_id=<?php echo $session_id; ?>&term_id=<?php echo $term_id; ?>&class_id=<?php echo $class_id; ?>&subject_id=<?php echo $subject_id; ?>&tab=scores"
       class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-all <?php echo $tab==='scores' ? 'bg-white text-primary shadow-sm' : 'text-slate-600 hover:text-primary'; ?>">
        <span class="material-symbols-outlined text-sm align-middle mr-1">edit_note</span>Enter Scores
    </a>
    <a href="?session_id=<?php echo $session_id; ?>&term_id=<?php echo $term_id; ?>&class_id=<?php echo $class_id; ?>&subject_id=<?php echo $subject_id; ?>&tab=summary"
       class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-all <?php echo $tab==='summary' ? 'bg-white text-primary shadow-sm' : 'text-slate-600 hover:text-primary'; ?>">
        <span class="material-symbols-outlined text-sm align-middle mr-1">summarize</span>Summary & Comments
    </a>
</div>

<?php if ($tab === 'scores'): ?>
<!-- ── SCORES TAB ─────────────────────────────────────────────── -->
<form method="POST">
    <input type="hidden" name="session_id"  value="<?php echo $session_id; ?>">
    <input type="hidden" name="term_id"     value="<?php echo $term_id; ?>">
    <input type="hidden" name="class_id"    value="<?php echo $class_id; ?>">
    <input type="hidden" name="subject_id"  value="<?php echo $subject_id; ?>">
    <input type="hidden" name="tab"         value="scores">

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-bold text-slate-900">
                Student Score Entry
                <span class="ml-2 text-sm font-normal text-slate-500">(<?php echo $students->num_rows; ?> students)</span>
            </h2>
            <span class="text-xs text-slate-400 flex items-center gap-1">
                <span class="material-symbols-outlined text-xs">keyboard</span>Tab to move between fields
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase w-8">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Student</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase w-28">Reg. No</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-blue-600 uppercase w-20">CA1<br><span class="text-slate-400 normal-case font-normal">/20</span></th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-blue-700 uppercase w-20">CA2<br><span class="text-slate-400 normal-case font-normal">/20</span></th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-primary uppercase w-20">Exam<br><span class="text-slate-400 normal-case font-normal">/60</span></th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gold uppercase w-20">Total<br><span class="text-slate-400 normal-case font-normal">/100</span></th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase w-16">Grade</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Remark</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if ($students->num_rows == 0): ?>
                    <tr><td colspan="9" class="px-4 py-12 text-center text-slate-400">
                        <span class="material-symbols-outlined text-4xl block mb-2">person_off</span>
                        No active students found in this class.
                    </td></tr>
                    <?php endif; ?>
                    <?php $sn=1; $students->data_seek(0); while ($st = $students->fetch_assoc()):
                        $ex = $existing[$st['id']] ?? null;
                        $grade_info = $ex ? calcGrade($ex['total_score']) : null;
                        $grade_class = '';
                        if ($grade_info) {
                            $g = $grade_info['grade'];
                            if ($g=='A1') $grade_class='text-green-600 font-bold';
                            elseif (in_array($g,['B2','B3'])) $grade_class='text-blue-600 font-bold';
                            elseif (in_array($g,['C4','C5','C6'])) $grade_class='text-yellow-600 font-bold';
                            elseif ($g=='D7') $grade_class='text-orange-600 font-bold';
                            else $grade_class='text-red-600 font-bold';
                        }
                    ?>
                    <tr class="hover:bg-slate-50 transition-colors" data-student="<?php echo $st['id']; ?>">
                        <td class="px-4 py-2.5 text-slate-400 text-xs"><?php echo $sn++; ?></td>
                        <td class="px-4 py-2.5 font-semibold text-slate-800">
                            <?php echo htmlspecialchars($st['last_name'].', '.$st['first_name'].
                                ($st['middle_name'] ? ' '.$st['middle_name'] : '')); ?>
                            <input type="hidden" name="student_ids[]" value="<?php echo $st['id']; ?>">
                        </td>
                        <td class="px-4 py-2.5 font-mono text-xs text-slate-500"><?php echo htmlspecialchars($st['student_id']); ?></td>
                        <td class="px-2 py-2">
                            <input type="number" name="ca1[]" min="0" max="20" step="0.5"
                                value="<?php echo $ex ? $ex['ca1'] : ''; ?>"
                                class="score-input w-16 text-center border border-slate-200 rounded-lg py-1.5 text-sm focus:border-gold focus:ring-1 focus:ring-gold/30"
                                data-max="20" placeholder="0">
                        </td>
                        <td class="px-2 py-2">
                            <input type="number" name="ca2[]" min="0" max="20" step="0.5"
                                value="<?php echo $ex ? $ex['ca2'] : ''; ?>"
                                class="score-input w-16 text-center border border-slate-200 rounded-lg py-1.5 text-sm focus:border-gold focus:ring-1 focus:ring-gold/30"
                                data-max="20" placeholder="0">
                        </td>
                        <td class="px-2 py-2">
                            <input type="number" name="exam[]" min="0" max="60" step="0.5"
                                value="<?php echo $ex ? $ex['exam_score'] : ''; ?>"
                                class="score-input w-16 text-center border border-slate-200 rounded-lg py-1.5 text-sm focus:border-gold focus:ring-1 focus:ring-gold/30"
                                data-max="60" placeholder="0">
                        </td>
                        <td class="px-4 py-2.5 text-center font-bold text-primary total-cell">
                            <?php echo $ex ? number_format($ex['total_score'], 1) : '—'; ?>
                        </td>
                        <td class="px-4 py-2.5 text-center grade-cell <?php echo $grade_class; ?>">
                            <?php echo $ex ? ($grade_info['grade'] ?? '—') : '—'; ?>
                        </td>
                        <td class="px-2 py-2">
                            <input type="text" name="remarks[]"
                                value="<?php echo $ex ? htmlspecialchars($ex['remark']) : ''; ?>"
                                class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:border-gold focus:ring-1 focus:ring-gold/30"
                                placeholder="Auto if blank">
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex flex-wrap items-center gap-3">
            <button type="submit" name="save_results" value="1"
                class="inline-flex items-center gap-2 bg-gold text-primary px-6 py-3 rounded-lg font-bold hover:bg-gold/90 transition-all shadow-sm">
                <span class="material-symbols-outlined">save</span> Save All Scores
            </button>
            <a href="manage_results.php"
               class="inline-flex items-center gap-2 bg-white text-slate-700 border border-slate-200 px-5 py-3 rounded-lg font-semibold hover:bg-slate-50 transition-all text-sm">
                Cancel
            </a>
            <span class="ml-auto text-xs text-slate-400">
                <span class="material-symbols-outlined text-xs align-middle">info</span>
                After saving scores, go to <strong>Summary & Comments</strong> tab to publish results.
            </span>
        </div>
    </div>
</form>

<?php elseif ($tab === 'summary'): ?>
<!-- ── SUMMARY / COMMENTS TAB ────────────────────────────────── -->
<form method="POST">
    <input type="hidden" name="session_id"  value="<?php echo $session_id; ?>">
    <input type="hidden" name="term_id"     value="<?php echo $term_id; ?>">
    <input type="hidden" name="class_id"    value="<?php echo $class_id; ?>">
    <input type="hidden" name="subject_id"  value="<?php echo $subject_id; ?>">
    <input type="hidden" name="tab"         value="summary">

    <!-- Global fields -->
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-5">
        <h3 class="font-bold text-slate-900 mb-4">Report Card Global Settings</h3>
        <div class="grid md:grid-cols-3 gap-4">
            <div>
                <label class="text-xs font-semibold text-slate-600 mb-1 block">Class Teacher's Name</label>
                <input type="text" name="teacher_name"
                    value="<?php echo htmlspecialchars(array_values($summaries)[0]['class_teacher_name'] ?? ''); ?>"
                    class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"
                    placeholder="e.g. Mrs. Sarah Okon">
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 mb-1 block">Principal's Name</label>
                <input type="text" name="principal_name"
                    value="<?php echo htmlspecialchars(array_values($summaries)[0]['principal_name'] ?? 'DR. SAMUEL OMOGO'); ?>"
                    class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 mb-1 block">Next Term Begins</label>
                <input type="date" name="next_term_begins"
                    value="<?php echo htmlspecialchars(array_values($summaries)[0]['next_term_begins'] ?? ''); ?>"
                    class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
            </div>
        </div>
    </div>

    <!-- Per-student summary -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h3 class="font-bold text-slate-900">Per-Student Summary & Comments</h3>
            <p class="text-xs text-slate-500 mt-1">Totals are calculated automatically from entered scores. Fill position, attendance, and comments.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-50 border-b">
                    <tr>
                        <th class="px-3 py-3 text-left font-semibold text-slate-600">Student</th>
                        <th class="px-3 py-3 text-center font-semibold text-slate-600">Subjects</th>
                        <th class="px-3 py-3 text-center font-semibold text-slate-600">Total</th>
                        <th class="px-3 py-3 text-center font-semibold text-slate-600">Average</th>
                        <th class="px-3 py-3 text-center font-semibold text-slate-600">Grade</th>
                        <th class="px-3 py-3 text-center font-semibold text-slate-600">Position</th>
                        <th class="px-3 py-3 text-center font-semibold text-slate-600">Class Size</th>
                        <th class="px-3 py-3 text-center font-semibold text-slate-600">Att. Present</th>
                        <th class="px-3 py-3 text-center font-semibold text-slate-600">Att. Total</th>
                        <th class="px-3 py-3 text-left font-semibold text-slate-600" style="min-width:200px">Teacher's Comment</th>
                        <th class="px-3 py-3 text-left font-semibold text-slate-600" style="min-width:200px">Principal's Comment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php $students->data_seek(0); while ($st = $students->fetch_assoc()):
                        $sm  = $summaries[$st['id']] ?? null;
                        $tot = $student_totals[$st['id']] ?? null;
                        $avg = $tot ? round(floatval($tot['average']), 2) : 0;
                        $g   = $tot ? calcGrade($avg) : null;
                        $has_scores = $tot && $tot['subj_cnt'] > 0;
                    ?>
                    <tr class="hover:bg-slate-50 <?php echo !$has_scores ? 'opacity-50' : ''; ?>">
                        <td class="px-3 py-2.5 font-semibold text-slate-800 whitespace-nowrap">
                            <?php echo htmlspecialchars($st['first_name'].' '.$st['last_name']); ?>
                            <?php if (!$has_scores): ?><span class="ml-1 text-red-400 font-normal">(no scores)</span><?php endif; ?>
                            <input type="hidden" name="sum_student_ids[]" value="<?php echo $st['id']; ?>">
                        </td>
                        <td class="px-3 py-2.5 text-center font-bold text-primary"><?php echo $tot ? $tot['subj_cnt'] : '0'; ?></td>
                        <td class="px-3 py-2.5 text-center font-bold text-primary"><?php echo $tot ? number_format($tot['grand_total'], 1) : '—'; ?></td>
                        <td class="px-3 py-2.5 text-center font-bold"><?php echo $tot ? number_format($avg, 2).'%' : '—'; ?></td>
                        <td class="px-3 py-2.5 text-center font-bold <?php echo $g ? ($g['grade']=='A1'?'text-green-600':($g['grade'][0]=='B'?'text-blue-600':($g['grade'][0]=='C'?'text-yellow-600':'text-red-600'))) : ''; ?>">
                            <?php echo $g ? $g['grade'] : '—'; ?>
                        </td>
                        <td class="px-2 py-2"><input type="text" name="position[]" value="<?php echo $sm ? htmlspecialchars($sm['overall_position']) : ''; ?>" class="w-16 text-center border-slate-200 rounded text-xs py-1 focus:ring-gold focus:border-gold" placeholder="e.g. 3rd"></td>
                        <td class="px-2 py-2"><input type="number" name="class_size[]" value="<?php echo $sm ? $sm['class_size'] : ''; ?>" class="w-14 text-center border-slate-200 rounded text-xs py-1 focus:ring-gold focus:border-gold" placeholder="45"></td>
                        <td class="px-2 py-2"><input type="number" name="att_present[]" value="<?php echo $sm ? $sm['attendance_present'] : ''; ?>" class="w-14 text-center border-slate-200 rounded text-xs py-1 focus:ring-gold focus:border-gold" placeholder="60"></td>
                        <td class="px-2 py-2"><input type="number" name="att_total[]" value="<?php echo $sm ? $sm['attendance_total'] : ''; ?>" class="w-14 text-center border-slate-200 rounded text-xs py-1 focus:ring-gold focus:border-gold" placeholder="65"></td>
                        <td class="px-2 py-2"><textarea name="teacher_comment[]" rows="2" class="w-full border-slate-200 rounded text-xs py-1 px-2 focus:ring-gold focus:border-gold" placeholder="Class teacher's remark..."><?php echo $sm ? htmlspecialchars($sm['class_teacher_comment']) : ''; ?></textarea></td>
                        <td class="px-2 py-2"><textarea name="principal_comment[]" rows="2" class="w-full border-slate-200 rounded text-xs py-1 px-2 focus:ring-gold focus:border-gold" placeholder="Principal's comment..."><?php echo $sm ? htmlspecialchars($sm['principal_comment']) : ''; ?></textarea></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex gap-3 items-center">
            <button type="submit" name="save_summary" value="1"
                class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-lg font-bold hover:bg-primary/90 transition-all shadow-sm">
                <span class="material-symbols-outlined">publish</span> Save & Publish Results
            </button>
            <a href="?session_id=<?php echo $session_id; ?>&term_id=<?php echo $term_id; ?>&class_id=<?php echo $class_id; ?>&subject_id=<?php echo $subject_id; ?>&tab=scores"
               class="inline-flex items-center gap-2 bg-white text-slate-700 border border-slate-200 px-5 py-3 rounded-lg font-semibold hover:bg-slate-50 transition-all text-sm">
                <span class="material-symbols-outlined text-sm">arrow_back</span>Back to Scores
            </a>
        </div>
    </div>
</form>
<?php endif; ?>

</main>
</div>
</div>

<script>
// ── Live score calculation ──────────────────────────────────────────
const GRADES = [
    [90,100,'A1'],[80,89,'B2'],[70,79,'B3'],[60,69,'C4'],
    [50,59,'C5'],[45,49,'C6'],[40,44,'D7'],[30,39,'E8'],[0,29,'F9']
];
function getGrade(t) {
    for (const [min,max,g] of GRADES) if (t>=min && t<=max) return g;
    return 'F9';
}
function gradeClass(g) {
    if (g==='A1') return 'text-green-600 font-bold';
    if (g[0]==='B') return 'text-blue-600 font-bold';
    if (g[0]==='C') return 'text-yellow-600 font-bold';
    if (g==='D7') return 'text-orange-600 font-bold';
    return 'text-red-600 font-bold';
}

document.querySelectorAll('.score-input').forEach(input => {
    input.addEventListener('input', function() {
        const max = parseFloat(this.dataset.max);
        if (parseFloat(this.value) > max) {
            this.value = max;
            this.classList.add('border-red-400');
        } else {
            this.classList.remove('border-red-400');
        }
        const row = this.closest('tr');
        const inputs = row.querySelectorAll('.score-input');
        let total = 0;
        inputs.forEach(i => total += parseFloat(i.value) || 0);
        const grade = getGrade(total);
        const totalCell = row.querySelector('.total-cell');
        const gradeCell = row.querySelector('.grade-cell');
        totalCell.textContent = total.toFixed(1);
        gradeCell.textContent = grade;
        gradeCell.className = 'px-4 py-2.5 text-center grade-cell ' + gradeClass(grade);
    });
});

// Highlight row on focus
document.querySelectorAll('tbody tr').forEach(row => {
    row.querySelectorAll('input, textarea').forEach(inp => {
        inp.addEventListener('focus', () => row.classList.add('bg-gold/5'));
        inp.addEventListener('blur',  () => row.classList.remove('bg-gold/5'));
    });
});
</script>
</body>
</html>
