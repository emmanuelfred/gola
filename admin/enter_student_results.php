<?php
require_once 'auth_check.php';
$page_title = "Enter Student Results";

$session_id = intval($_GET['session_id'] ?? $_POST['session_id'] ?? 0);
$term_id    = intval($_GET['term_id']    ?? $_POST['term_id']    ?? 0);
$class_id   = intval($_GET['class_id']   ?? $_POST['class_id']   ?? 0);
$student_db_id = intval($_GET['student_id'] ?? $_POST['student_id'] ?? 0);

if (!$session_id || !$term_id || !$class_id || !$student_db_id) {
    header('Location: manage_results.php');
    exit;
}

// ── Info lookups ──────────────────────────────────────────────────────────────
$student_info = $conn->query("
    SELECT s.*, c.class_name, c.arm
    FROM students s JOIN classes c ON s.class_id=c.id
    WHERE s.id=$student_db_id AND s.class_id=$class_id AND s.status='Active'
")->fetch_assoc();

$session_info = $conn->query("SELECT session_name FROM academic_sessions WHERE id=$session_id")->fetch_assoc();
$class_info   = $conn->query("SELECT class_name, arm FROM classes WHERE id=$class_id")->fetch_assoc();
$term_names   = [1=>'First Term', 2=>'Second Term', 3=>'Third Term'];

if (!$student_info || !$session_info) { header('Location: manage_results.php'); exit; }

// Subjects for this class
$subjects_q = $conn->query("
    SELECT s.id, s.subject_code, s.subject_name, s.category
    FROM class_subjects cs
    JOIN subjects s ON s.id=cs.subject_id
    WHERE cs.class_id=$class_id AND s.is_active=1
    ORDER BY s.subject_name
");
$subjects = $subjects_q->fetch_all(MYSQLI_ASSOC);

// Grade helper
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
$tab     = $_GET['tab'] ?? $_POST['tab'] ?? 'scores';

// ── SAVE SCORES ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_results'])) {
    $subject_ids = $_POST['subject_ids'] ?? [];
    $ca1_scores  = $_POST['ca1']         ?? [];
    $ca2_scores  = $_POST['ca2']         ?? [];
    $exam_scores = $_POST['exam']        ?? [];
    $remarks     = $_POST['remarks']     ?? [];

    $saved  = 0;
    $errors = [];

    foreach ($subject_ids as $i => $subj_id) {
        $subj_id = intval($subj_id);
        $ca1     = floatval($ca1_scores[$i] ?? 0);
        $ca2     = floatval($ca2_scores[$i] ?? 0);
        $exam    = floatval($exam_scores[$i] ?? 0);

        // Skip fully blank rows
        if ($ca1 == 0 && $ca2 == 0 && $exam == 0) continue;

        if ($ca1  > 20) { $errors[] = "Row ".($i+1).": CA1 cannot exceed 20"; continue; }
        if ($ca2  > 20) { $errors[] = "Row ".($i+1).": CA2 cannot exceed 20"; continue; }
        if ($exam > 60) { $errors[] = "Row ".($i+1).": Exam cannot exceed 60"; continue; }

        $total      = $ca1 + $ca2 + $exam;
        $grade_info = calcGrade($total);
        $remark     = trim($remarks[$i] ?? '') ?: $grade_info['remark'];

        $stmt = $conn->prepare("
            INSERT INTO results
                (student_id, subject_id, class_id, session_id, term_id,
                 ca1, ca2, exam_score, total_score, grade, remark, entered_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                ca1=VALUES(ca1), ca2=VALUES(ca2),
                exam_score=VALUES(exam_score),
                total_score=VALUES(total_score),
                grade=VALUES(grade), remark=VALUES(remark),
                entered_by=VALUES(entered_by)
        ");
        $stmt->bind_param("iiiiiddddssi",
            $student_db_id, $subj_id, $class_id, $session_id, $term_id,
            $ca1, $ca2, $exam, $total, $grade_info['grade'], $remark, $admin_id);
        if ($stmt->execute()) $saved++;
    }

    if ($saved > 0) {
        logActivity('enter_student_results',
            "Saved $saved subject scores for {$student_info['first_name']} {$student_info['last_name']}");
        $success = "Scores saved for <strong>$saved subject(s)</strong> successfully!";
    }
    if ($errors) $error = implode('<br>', $errors);
    $tab = 'scores';
}

// ── SAVE SUMMARY ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_summary'])) {
    $res = $conn->query("SELECT COUNT(*) as cnt, SUM(total_score) as total
                         FROM results WHERE student_id=$student_db_id AND session_id=$session_id AND term_id=$term_id");
    $rdata = $res->fetch_assoc();

    if (!$rdata || $rdata['cnt'] == 0) {
        $error = 'No scores found for this student this term. Enter scores first.';
        $tab   = 'summary';
    } else {
        $total_subj  = intval($rdata['cnt']);
        $total_score = floatval($rdata['total']);
        $avg         = round($total_score / $total_subj, 2);
        $grade_info  = calcGrade($avg);

        $pos        = trim($_POST['overall_position'] ?? '');
        $cs         = intval($_POST['class_size'] ?? 0);
        $ap         = intval($_POST['att_present'] ?? 0);
        $at         = intval($_POST['att_total'] ?? 0);
        $tc         = trim($_POST['teacher_comment'] ?? '');
        $tn         = trim($_POST['teacher_name'] ?? '');
        $pc         = trim($_POST['principal_comment'] ?? '');
        $pn         = trim($_POST['principal_name'] ?? 'DR. SAMUEL OMOGO');
        $next_term  = trim($_POST['next_term_begins'] ?? '') ?: null;
        $result_uid = 'GOLA-'.date('Y').'-'.str_pad($student_db_id, 5, '0', STR_PAD_LEFT);

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
        // types: i i i i  i  d  d  s   s   i  i  i   s   s   s   s   s    s
        //        sid cid ses tid tsub tsc avg grd pos cs  ap  at  tc  tn  pc  pn  nxt uid
        $stmt->bind_param("iiiiiddssiiissssss",
            $student_db_id, $class_id, $session_id, $term_id,
            $total_subj, $total_score, $avg, $grade_info['grade'],
            $pos, $cs, $ap, $at,
            $tc, $tn, $pc, $pn, $next_term, $result_uid);

        if ($stmt->execute()) {
            logActivity('publish_student_result',
                "Published result for {$student_info['first_name']} {$student_info['last_name']}");
            $success = "Result published successfully for this student!";
        } else {
            $error = 'Error saving summary: '.$conn->error;
        }
        $tab = 'summary';
    }
}

// ── Existing scores for this student this term ────────────────────────────────
$existing = [];
$ex_q = $conn->query("
    SELECT * FROM results
    WHERE student_id=$student_db_id AND session_id=$session_id AND term_id=$term_id
");
while ($e = $ex_q->fetch_assoc()) $existing[$e['subject_id']] = $e;

// ── Existing summary ──────────────────────────────────────────────────────────
$summary = $conn->query("
    SELECT * FROM result_summary
    WHERE student_id=$student_db_id AND session_id=$session_id AND term_id=$term_id
")->fetch_assoc();

// Running totals
$entered_count = count($existing);
$grand_total   = array_sum(array_column($existing, 'total_score'));
$average       = $entered_count > 0 ? round($grand_total / $entered_count, 2) : 0;
$overall_grade = $entered_count > 0 ? calcGrade($average) : null;

// Back URL
$back_url = 'student_results.php?session_id='.$session_id.'&term_id='.$term_id.'&class_id='.$class_id;
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

<!-- Breadcrumb -->
<div class="flex items-center gap-2 text-xs text-slate-500 mb-4 flex-wrap">
    <a href="manage_results.php" class="hover:text-gold">Results</a>
    <span class="material-symbols-outlined text-xs">chevron_right</span>
    <a href="<?php echo $back_url; ?>" class="hover:text-gold">
        <?php echo htmlspecialchars($class_info['class_name'].' '.$class_info['arm']); ?>
    </a>
    <span class="material-symbols-outlined text-xs">chevron_right</span>
    <span class="text-slate-800 font-semibold">
        <?php echo htmlspecialchars($student_info['first_name'].' '.$student_info['last_name']); ?>
    </span>
</div>

<!-- Student Header Card -->
<div class="bg-primary rounded-2xl p-6 mb-6 flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-4">
        <div class="w-14 h-14 bg-gold rounded-xl flex items-center justify-center font-black text-primary text-xl flex-shrink-0">
            <?php echo strtoupper(substr($student_info['first_name'],0,1).substr($student_info['last_name'],0,1)); ?>
        </div>
        <div>
            <h1 class="text-xl font-black text-white">
                <?php echo htmlspecialchars($student_info['last_name'].', '.$student_info['first_name'].
                    (!empty($student_info['middle_name'])?' '.$student_info['middle_name']:'')); ?>
            </h1>
            <div class="flex flex-wrap gap-2 mt-1.5">
                <span class="px-2 py-0.5 bg-white/10 text-white text-xs font-semibold rounded-full">
                    <?php echo htmlspecialchars($student_info['student_id']); ?>
                </span>
                <span class="px-2 py-0.5 bg-gold/20 text-gold text-xs font-semibold rounded-full">
                    <?php echo htmlspecialchars($class_info['class_name'].' '.$class_info['arm']); ?>
                </span>
                <span class="px-2 py-0.5 bg-white/10 text-white text-xs font-semibold rounded-full">
                    <?php echo htmlspecialchars($session_info['session_name']); ?>
                    · <?php echo htmlspecialchars($term_names[$term_id] ?? ''); ?>
                </span>
            </div>
        </div>
    </div>
    <!-- Live running totals -->
    <div class="flex gap-4 text-center">
        <div>
            <p class="text-2xl font-black text-white" id="liveEntered"><?php echo $entered_count; ?></p>
            <p class="text-xs text-slate-300">of <?php echo count($subjects); ?> entered</p>
        </div>
        <div class="w-px bg-white/20"></div>
        <div>
            <p class="text-2xl font-black text-gold" id="liveTotal"><?php echo number_format($grand_total,1); ?></p>
            <p class="text-xs text-slate-300">Total Score</p>
        </div>
        <div class="w-px bg-white/20"></div>
        <div>
            <p class="text-2xl font-black text-gold" id="liveAvg"><?php echo number_format($average,1); ?>%</p>
            <p class="text-xs text-slate-300">Average</p>
        </div>
        <div class="w-px bg-white/20"></div>
        <div>
            <p class="text-2xl font-black <?php echo $overall_grade ? 'text-green-400' : 'text-slate-400'; ?>" id="liveGrade">
                <?php echo $overall_grade ? $overall_grade['grade'] : '—'; ?>
            </p>
            <p class="text-xs text-slate-300">Grade</p>
        </div>
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

<!-- Tabs -->
<div class="flex gap-1 mb-6 bg-slate-100 p-1 rounded-xl w-fit">
    <a href="?session_id=<?php echo $session_id; ?>&term_id=<?php echo $term_id; ?>&class_id=<?php echo $class_id; ?>&student_id=<?php echo $student_db_id; ?>&tab=scores"
       class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-all <?php echo $tab==='scores'?'bg-white text-primary shadow-sm':'text-slate-600 hover:text-primary'; ?>">
        <span class="material-symbols-outlined text-sm align-middle mr-1">edit_note</span>Enter Scores
    </a>
    <a href="?session_id=<?php echo $session_id; ?>&term_id=<?php echo $term_id; ?>&class_id=<?php echo $class_id; ?>&student_id=<?php echo $student_db_id; ?>&tab=summary"
       class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-all <?php echo $tab==='summary'?'bg-white text-primary shadow-sm':'text-slate-600 hover:text-primary'; ?>">
        <span class="material-symbols-outlined text-sm align-middle mr-1">summarize</span>Summary & Publish
        <?php if ($summary && $summary['published']): ?>
        <span class="ml-1 px-1.5 py-0.5 bg-green-100 text-green-700 text-xs rounded-full">Published</span>
        <?php endif; ?>
    </a>
</div>

<?php if ($tab === 'scores'): ?>
<!-- ── SCORES TAB ─────────────────────────────────────────────── -->
<form method="POST">
    <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
    <input type="hidden" name="term_id"    value="<?php echo $term_id; ?>">
    <input type="hidden" name="class_id"   value="<?php echo $class_id; ?>">
    <input type="hidden" name="student_id" value="<?php echo $student_db_id; ?>">
    <input type="hidden" name="tab"        value="scores">

    <?php if (empty($subjects)): ?>
    <div class="bg-white rounded-xl border border-slate-200 p-12 text-center">
        <span class="material-symbols-outlined text-5xl text-slate-200 block mb-3">menu_book</span>
        <p class="font-bold text-slate-700">No subjects assigned to this class.</p>
        <a href="manage_class_subjects.php?tab=classes&class_id=<?php echo $class_id; ?>"
           class="inline-flex items-center gap-1 mt-3 text-gold font-semibold text-sm hover:underline">
            <span class="material-symbols-outlined text-sm">settings</span>Go to Class Subjects
        </a>
    </div>
    <?php else: ?>

    <!-- Scoring guide -->
    <div class="mb-4 p-3 bg-blue-50 border border-blue-100 rounded-xl flex flex-wrap gap-4 text-xs text-blue-800">
        <span><strong>CA1</strong> — max 20 marks</span>
        <span><strong>CA2</strong> — max 20 marks</span>
        <span><strong>Exam</strong> — max 60 marks</span>
        <span class="font-bold">Total = 100</span>
        <span class="ml-auto text-blue-600">Leave all three blank to skip a subject</span>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-bold text-slate-900">
                All Subjects
                <span class="text-sm font-normal text-slate-400 ml-2">(<?php echo count($subjects); ?> subjects)</span>
            </h2>
            <span class="text-xs text-slate-400 flex items-center gap-1">
                <span class="material-symbols-outlined text-xs">keyboard</span>Tab to navigate
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-500 uppercase w-8">#</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-500 uppercase">Subject</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-500 uppercase w-10">Code</th>
                        <th class="px-4 py-3 text-center font-semibold text-blue-600 uppercase w-20">CA1<br><span class="text-slate-400 normal-case font-normal">/20</span></th>
                        <th class="px-4 py-3 text-center font-semibold text-blue-700 uppercase w-20">CA2<br><span class="text-slate-400 normal-case font-normal">/20</span></th>
                        <th class="px-4 py-3 text-center font-semibold text-primary uppercase w-20">Exam<br><span class="text-slate-400 normal-case font-normal">/60</span></th>
                        <th class="px-4 py-3 text-center font-semibold text-gold uppercase w-20">Total<br><span class="text-slate-400 normal-case font-normal">/100</span></th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-500 uppercase w-16">Grade</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-500 uppercase">Remark</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100" id="subjectTableBody">
                    <?php foreach ($subjects as $i => $subj):
                        $ex         = $existing[$subj['id']] ?? null;
                        $grade_info = $ex ? calcGrade($ex['total_score']) : null;
                        $gc = '';
                        if ($grade_info) {
                            $g = $grade_info['grade'];
                            if ($g==='A1') $gc='text-green-600 font-bold';
                            elseif (in_array($g,['B2','B3'])) $gc='text-blue-600 font-bold';
                            elseif (in_array($g,['C4','C5','C6'])) $gc='text-yellow-600 font-bold';
                            elseif ($g==='D7') $gc='text-orange-600 font-bold';
                            else $gc='text-red-600 font-bold';
                        }
                        $cat_dot = ['Core'=>'bg-blue-400','Elective'=>'bg-amber-400','Vocational'=>'bg-green-400'];
                        $dot = $cat_dot[$subj['category']] ?? 'bg-slate-400';
                    ?>
                    <tr class="hover:bg-slate-50 transition-colors subject-row" data-has-score="<?php echo $ex ? '1' : '0'; ?>">
                        <td class="px-4 py-2.5 text-slate-400 text-xs"><?php echo $i+1; ?></td>
                        <td class="px-4 py-2.5">
                            <div class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full <?php echo $dot; ?> flex-shrink-0"></span>
                                <span class="font-semibold text-slate-800"><?php echo htmlspecialchars($subj['subject_name']); ?></span>
                            </div>
                            <input type="hidden" name="subject_ids[]" value="<?php echo $subj['id']; ?>">
                        </td>
                        <td class="px-4 py-2.5 text-center font-mono text-xs text-primary font-bold"><?php echo htmlspecialchars($subj['subject_code']); ?></td>
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
                            <?php echo $ex ? number_format($ex['total_score'],1) : '—'; ?>
                        </td>
                        <td class="px-4 py-2.5 text-center grade-cell <?php echo $gc; ?>">
                            <?php echo $ex ? ($grade_info['grade'] ?? '—') : '—'; ?>
                        </td>
                        <td class="px-2 py-2">
                            <input type="text" name="remarks[]"
                                value="<?php echo $ex ? htmlspecialchars($ex['remark']) : ''; ?>"
                                class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:border-gold focus:ring-1 focus:ring-gold/30"
                                placeholder="Auto if blank">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 bg-slate-50 border-t border-slate-100 flex flex-wrap items-center gap-3">
            <button type="submit" name="save_results" value="1"
                class="inline-flex items-center gap-2 bg-gold text-primary px-6 py-3 rounded-xl font-bold hover:bg-gold/90 transition-all shadow-sm">
                <span class="material-symbols-outlined">save</span>Save All Scores
            </button>
            <a href="<?php echo $back_url; ?>"
               class="inline-flex items-center gap-2 bg-white text-slate-700 border border-slate-200 px-5 py-3 rounded-xl font-semibold hover:bg-slate-50 transition-all text-sm">
                <span class="material-symbols-outlined text-sm">arrow_back</span>Back to Class
            </a>
            <span class="ml-auto text-xs text-slate-400">
                <span class="material-symbols-outlined text-xs align-middle">info</span>
                After saving, go to <strong>Summary & Publish</strong> tab.
            </span>
        </div>
    </div>
    <?php endif; ?>
</form>

<?php elseif ($tab === 'summary'): ?>
<!-- ── SUMMARY TAB ────────────────────────────────────────────── -->
<form method="POST">
    <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
    <input type="hidden" name="term_id"    value="<?php echo $term_id; ?>">
    <input type="hidden" name="class_id"   value="<?php echo $class_id; ?>">
    <input type="hidden" name="student_id" value="<?php echo $student_db_id; ?>">
    <input type="hidden" name="tab"        value="summary">

    <!-- Calculated stats banner -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
        <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
            <p class="text-2xl font-black text-primary"><?php echo $entered_count; ?></p>
            <p class="text-xs text-slate-500 font-semibold uppercase mt-1">Subjects</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
            <p class="text-2xl font-black text-primary"><?php echo number_format($grand_total,1); ?></p>
            <p class="text-xs text-slate-500 font-semibold uppercase mt-1">Total Score</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
            <p class="text-2xl font-black text-primary"><?php echo number_format($average,2); ?>%</p>
            <p class="text-xs text-slate-500 font-semibold uppercase mt-1">Average</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
            <p class="text-2xl font-black <?php echo $overall_grade ? 'text-green-600' : 'text-slate-300'; ?>">
                <?php echo $overall_grade ? $overall_grade['grade'] : '—'; ?>
            </p>
            <p class="text-xs text-slate-500 font-semibold uppercase mt-1">Overall Grade</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-6 space-y-5">
        <h3 class="font-bold text-slate-900 text-base">Report Card Details</h3>

        <div class="grid md:grid-cols-3 gap-4">
            <div>
                <label class="text-xs font-semibold text-slate-600 mb-1 block">Position in Class</label>
                <input type="text" name="overall_position"
                    value="<?php echo htmlspecialchars($summary['overall_position'] ?? ''); ?>"
                    class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold" placeholder="e.g. 3rd">
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 mb-1 block">Class Size</label>
                <input type="number" name="class_size"
                    value="<?php echo $summary['class_size'] ?? ''; ?>"
                    class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold" placeholder="e.g. 35">
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 mb-1 block">Next Term Begins</label>
                <input type="date" name="next_term_begins"
                    value="<?php echo $summary['next_term_begins'] ?? ''; ?>"
                    class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold">
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-semibold text-slate-600 mb-1 block">Times Present</label>
                <input type="number" name="att_present"
                    value="<?php echo $summary['attendance_present'] ?? ''; ?>"
                    class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold" placeholder="e.g. 60">
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 mb-1 block">Total School Days</label>
                <input type="number" name="att_total"
                    value="<?php echo $summary['attendance_total'] ?? ''; ?>"
                    class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold" placeholder="e.g. 65">
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-semibold text-slate-600 mb-1 block">Class Teacher's Name</label>
                <input type="text" name="teacher_name"
                    value="<?php echo htmlspecialchars($summary['class_teacher_name'] ?? ''); ?>"
                    class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold" placeholder="e.g. Mrs. Sarah Okon">
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 mb-1 block">Principal's Name</label>
                <input type="text" name="principal_name"
                    value="<?php echo htmlspecialchars($summary['principal_name'] ?? 'DR. SAMUEL OMOGO'); ?>"
                    class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold">
            </div>
        </div>

        <div>
            <label class="text-xs font-semibold text-slate-600 mb-1 block">Class Teacher's Comment</label>
            <textarea name="teacher_comment" rows="3"
                class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"
                placeholder="e.g. Chukwuemeka is a diligent student who demonstrates great potential..."
                ><?php echo htmlspecialchars($summary['class_teacher_comment'] ?? ''); ?></textarea>
        </div>

        <div>
            <label class="text-xs font-semibold text-slate-600 mb-1 block">Principal's Comment</label>
            <textarea name="principal_comment" rows="3"
                class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"
                placeholder="e.g. A very impressive result. Maintain this standard of excellence..."
                ><?php echo htmlspecialchars($summary['principal_comment'] ?? ''); ?></textarea>
        </div>

        <div class="flex flex-wrap gap-3 pt-2 border-t border-slate-100">
            <button type="submit" name="save_summary" value="1"
                class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-xl font-bold hover:bg-primary/90 transition-all shadow-sm">
                <span class="material-symbols-outlined">publish</span>
                <?php echo ($summary && $summary['published']) ? 'Update & Re-Publish' : 'Save & Publish Result'; ?>
            </button>
            <a href="?session_id=<?php echo $session_id; ?>&term_id=<?php echo $term_id; ?>&class_id=<?php echo $class_id; ?>&student_id=<?php echo $student_db_id; ?>&tab=scores"
               class="inline-flex items-center gap-2 bg-white border border-slate-200 text-slate-700 px-5 py-3 rounded-xl font-semibold hover:bg-slate-50 transition-all text-sm">
                <span class="material-symbols-outlined text-sm">arrow_back</span>Back to Scores
            </a>
            <a href="<?php echo $back_url; ?>"
               class="inline-flex items-center gap-2 bg-white border border-slate-200 text-slate-700 px-5 py-3 rounded-xl font-semibold hover:bg-slate-50 transition-all text-sm">
                <span class="material-symbols-outlined text-sm">groups</span>Back to Class List
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
const GRADES=[
    [75,100,'A1'],[70,74,'B2'],[65,69,'B3'],[60,64,'C4'],
    [55,59,'C5'],[50,54,'C6'],[45,49,'D7'],[40,44,'E8'],[0,39,'F9']
];
function getGrade(t){for(const[min,max,g]of GRADES)if(t>=min&&t<=max)return g;return 'F9';}
function gradeClass(g){
    if(g==='A1')return'text-green-600 font-bold';
    if(g[0]==='B')return'text-blue-600 font-bold';
    if(g[0]==='C')return'text-yellow-600 font-bold';
    if(g==='D7')return'text-orange-600 font-bold';
    return'text-red-600 font-bold';
}

// Running totals for the header banner
function updateRunningTotals(){
    let entered=0, grandTotal=0;
    document.querySelectorAll('.subject-row').forEach(row=>{
        const inputs=row.querySelectorAll('.score-input');
        let rowTotal=0;
        inputs.forEach(i=>rowTotal+=parseFloat(i.value)||0);
        if(rowTotal>0){entered++;grandTotal+=rowTotal;}
    });
    document.getElementById('liveEntered').textContent=entered;
    document.getElementById('liveTotal').textContent=grandTotal.toFixed(1);
    const avg=entered>0?grandTotal/entered:0;
    document.getElementById('liveAvg').textContent=avg.toFixed(1)+'%';
    const grade=entered>0?getGrade(avg):'—';
    document.getElementById('liveGrade').textContent=grade;
    document.getElementById('liveGrade').className='text-2xl font-black '+(entered>0?'text-green-400':'text-slate-400');
}

document.querySelectorAll('.score-input').forEach(input=>{
    input.addEventListener('input',function(){
        const max=parseFloat(this.dataset.max);
        if(parseFloat(this.value)>max){this.value=max;this.classList.add('border-red-400');}
        else this.classList.remove('border-red-400');
        const row=this.closest('tr');
        const inputs=row.querySelectorAll('.score-input');
        let total=0;inputs.forEach(i=>total+=parseFloat(i.value)||0);
        const grade=getGrade(total);
        row.querySelector('.total-cell').textContent=total.toFixed(1);
        const gc=row.querySelector('.grade-cell');
        gc.textContent=grade;
        gc.className='px-4 py-2.5 text-center grade-cell '+gradeClass(grade);
        row.dataset.hasScore=total>0?'1':'0';
        updateRunningTotals();
    });
});

// Row focus highlight
document.querySelectorAll('.subject-row').forEach(row=>{
    row.querySelectorAll('input').forEach(inp=>{
        inp.addEventListener('focus',()=>row.classList.add('bg-gold/5'));
        inp.addEventListener('blur',()=>row.classList.remove('bg-gold/5'));
    });
});
</script>
</body>
</html>