<?php
require_once '../config/database.php';

// ── Validate POST data ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$student_id_raw = trim($_POST['student_id'] ?? '');
$pin_raw        = strtoupper(trim($_POST['pin_code'] ?? ''));
$session_id     = intval($_POST['session_id'] ?? 0);
$term_id        = intval($_POST['term_id'] ?? 0);

// Missing fields check
if (!$student_id_raw || !$pin_raw || !$session_id || !$term_id) {
    header('Location: index.php?error=missing_fields&'.http_build_query([
        'student_id' => $student_id_raw,
        'session_id' => $session_id,
        'term_id'    => $term_id,
    ]));
    exit;
}

// ── Grade helper ──────────────────────────────────────────────────────────────
function calculateGrade($score) {
    global $conn;
    $stmt = $conn->prepare("SELECT grade, remark FROM grading_system WHERE ? BETWEEN min_score AND max_score LIMIT 1");
    $stmt->bind_param("d", $score);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    return $r ?: ['grade'=>'F9','remark'=>'Fail'];
}

// ── 1. Validate scratch card PIN ──────────────────────────────────────────────
$pin_stmt = $conn->prepare("SELECT * FROM scratch_cards WHERE pin_code = ?");
$pin_stmt->bind_param("s", $pin_raw);
$pin_stmt->execute();
$card = $pin_stmt->get_result()->fetch_assoc();

if (!$card) {
    header('Location: index.php?error=invalid_pin&student_id='.urlencode($student_id_raw).'&session_id='.$session_id.'&term_id='.$term_id);
    exit;
}
if (!$card['is_activated']) {
    header('Location: index.php?error=inactive_card&student_id='.urlencode($student_id_raw).'&session_id='.$session_id.'&term_id='.$term_id);
    exit;
}
if ($card['times_used'] >= $card['max_uses']) {
    header('Location: index.php?error=used_up&student_id='.urlencode($student_id_raw).'&session_id='.$session_id.'&term_id='.$term_id);
    exit;
}

// ── 2. Fetch student ───────────────────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT s.*, c.class_name, c.arm
    FROM students s
    JOIN classes c ON s.class_id = c.id
    WHERE s.student_id = ? AND s.status = 'Active'
");
$stmt->bind_param("s", $student_id_raw);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    header('Location: index.php?error=no_student&session_id='.$session_id.'&term_id='.$term_id);
    exit;
}

// ── 3. Check summary is PUBLISHED before doing anything else ─────────────────
// Students must not see results until admin has published them.
$stmt = $conn->prepare("SELECT * FROM result_summary WHERE student_id=? AND session_id=? AND term_id=? AND published=1");
$stmt->bind_param("iii", $student['id'], $session_id, $term_id);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();

if (!$summary) {
    // Results not yet published by admin — do NOT deduct a PIN use
    header('Location: index.php?error=not_published&student_id='.urlencode($student_id_raw).'&session_id='.$session_id.'&term_id='.$term_id);
    exit;
}

// ── 4. Fetch individual subject scores ────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT r.*, sub.subject_name, sub.subject_code
    FROM results r
    JOIN subjects sub ON r.subject_id = sub.id
    WHERE r.student_id = ? AND r.session_id = ? AND r.term_id = ?
    ORDER BY sub.subject_name
");
$stmt->bind_param("iii", $student['id'], $session_id, $term_id);
$stmt->execute();
$result_data = $stmt->get_result();

if ($result_data->num_rows === 0) {
    header('Location: index.php?error=no_results&student_id='.urlencode($student_id_raw).'&session_id='.$session_id.'&term_id='.$term_id);
    exit;
}

$results = [];
while ($row = $result_data->fetch_assoc()) {
    $results[] = $row;
}

// ── 5. Deduct PIN use (only now — published results confirmed) ─────────────────
$conn->query("UPDATE scratch_cards SET times_used = times_used + 1 WHERE id = {$card['id']}");

// ── 6. Session & term labels ───────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT session_name FROM academic_sessions WHERE id=?");
$stmt->bind_param("i", $session_id);
$stmt->execute();
$session_info = $stmt->get_result()->fetch_assoc();

$term_names = [1=>'First Term', 2=>'Second Term', 3=>'Third Term'];
$term_label = $term_names[$term_id] ?? 'Term '.$term_id;

// ── 7. Compute class-wide stats for position display ──────────────────────────
$stmt = $conn->prepare("
    SELECT student_id, AVG(total_score) as avg_score
    FROM results
    WHERE session_id=? AND term_id=? AND class_id=?
    GROUP BY student_id
    ORDER BY avg_score DESC
");
$stmt->bind_param("iii", $session_id, $term_id, $student['class_id']);
$stmt->execute();
$class_stats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$class_size  = count($class_stats);

// ── Render page ────────────────────────────────────────────────────────────────
$page_title = "Result — " . $student['first_name'] . " " . $student['last_name'];
include '../includes/header.php';
?>

<!-- Result Print Sheet -->
<section class="py-8 bg-slate-100 dark:bg-slate-900 print:bg-white print:py-0">
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

    <!-- Action bar (hidden when printing) -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 print:hidden">
        <a href="index.php" class="inline-flex items-center gap-2 text-primary dark:text-gold hover:underline text-sm font-semibold">
            <span class="material-symbols-outlined text-sm">arrow_back</span>Check Another Result
        </a>
        <div class="flex gap-3">
            <button onclick="window.print()"
                class="inline-flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-semibold hover:bg-primary/90 transition-all text-sm shadow">
                <span class="material-symbols-outlined text-sm">print</span>Print Result
            </button>
        </div>
    </div>

    <!-- Result Card -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden print:shadow-none print:rounded-none" id="resultCard">

        <!-- School Header -->
        <div class="bg-primary text-white p-6 print:p-4">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-gold rounded-xl flex items-center justify-center flex-shrink-0 print:w-12 print:h-12">
                    <span class="material-symbols-outlined text-primary text-3xl print:text-2xl">school</span>
                </div>
                <div>
                    <h1 class="text-2xl font-black tracking-wide print:text-xl">GOODNESS OMOGO LEADERSHIP ACADEMY</h1>
                    <p class="text-gold text-xs font-semibold tracking-[0.2em] uppercase mt-1">To Learn • To Grow • To Lead</p>
                    <p class="text-slate-300 text-xs mt-1">Lagos, Nigeria &nbsp;|&nbsp; www.goodnessomogo.edu.ng</p>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-white/20 text-center">
                <h2 class="text-lg font-bold text-gold tracking-wider">STUDENT ACADEMIC REPORT CARD</h2>
                <p class="text-slate-300 text-sm">
                    <?php echo htmlspecialchars($session_info['session_name']); ?> Academic Session
                    &nbsp;—&nbsp; <?php echo htmlspecialchars($term_label); ?>
                </p>
            </div>
        </div>

        <!-- Student Bio Data -->
        <div class="p-6 bg-slate-50 border-b border-slate-200 print:p-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <p class="text-slate-500 text-xs font-semibold uppercase tracking-wide">Full Name</p>
                    <p class="font-bold text-primary mt-1">
                        <?php echo htmlspecialchars(
                            $student['last_name'].', '.
                            $student['first_name'].
                            (!empty($student['middle_name']) ? ' '.$student['middle_name'] : '')
                        ); ?>
                    </p>
                </div>
                <div>
                    <p class="text-slate-500 text-xs font-semibold uppercase tracking-wide">Reg. Number</p>
                    <p class="font-bold text-primary font-mono mt-1"><?php echo htmlspecialchars($student['student_id']); ?></p>
                </div>
                <div>
                    <p class="text-slate-500 text-xs font-semibold uppercase tracking-wide">Class</p>
                    <p class="font-bold text-primary mt-1"><?php echo htmlspecialchars($student['class_name'].' '.$student['arm']); ?></p>
                </div>
                <div>
                    <p class="text-slate-500 text-xs font-semibold uppercase tracking-wide">Gender</p>
                    <p class="font-bold text-primary mt-1"><?php echo htmlspecialchars($student['gender']); ?></p>
                </div>
            </div>
        </div>

        <!-- Performance Summary Banner -->
        <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-slate-200 border-b border-slate-200 print:grid-cols-4">
            <div class="p-4 text-center">
                <p class="text-3xl font-black text-primary print:text-2xl"><?php echo number_format($summary['total_score'], 1); ?></p>
                <p class="text-xs text-slate-500 font-semibold uppercase mt-1">Total Score</p>
            </div>
            <div class="p-4 text-center">
                <p class="text-3xl font-black text-primary print:text-2xl"><?php echo number_format($summary['average_score'], 1); ?>%</p>
                <p class="text-xs text-slate-500 font-semibold uppercase mt-1">Average</p>
            </div>
            <div class="p-4 text-center">
                <?php
                $grade_info  = calculateGrade($summary['average_score']);
                $grade_color = 'text-primary';
                if ($grade_info['grade'] === 'A1') $grade_color = 'text-green-600';
                elseif ($grade_info['grade'][0] === 'B') $grade_color = 'text-blue-600';
                elseif ($grade_info['grade'][0] === 'C') $grade_color = 'text-yellow-600';
                elseif (in_array($grade_info['grade'], ['D7','E8','F9'])) $grade_color = 'text-red-600';
                ?>
                <p class="text-3xl font-black <?php echo $grade_color; ?> print:text-2xl"><?php echo htmlspecialchars($grade_info['grade']); ?></p>
                <p class="text-xs text-slate-500 font-semibold uppercase mt-1">Overall Grade</p>
            </div>
            <div class="p-4 text-center">
                <?php if ($summary['overall_position']): ?>
                <p class="text-3xl font-black text-primary print:text-2xl"><?php echo htmlspecialchars($summary['overall_position']); ?></p>
                <p class="text-xs text-slate-500 font-semibold uppercase mt-1">
                    Position
                    <?php if ($summary['class_size'] ?? $class_size): ?>
                    of <?php echo $summary['class_size'] ?: $class_size; ?>
                    <?php endif; ?>
                </p>
                <?php else: ?>
                <p class="text-3xl font-black text-slate-300 print:text-2xl">—</p>
                <p class="text-xs text-slate-500 font-semibold uppercase mt-1">Position</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Attendance (if available) -->
        <?php if (!empty($summary['attendance_total']) && $summary['attendance_total'] > 0): ?>
        <div class="px-6 py-3 bg-blue-50 border-b border-blue-100 flex items-center gap-6 text-sm print:px-4">
            <span class="material-symbols-outlined text-blue-600">event_available</span>
            <span class="font-semibold text-blue-900">Attendance:</span>
            <span class="text-blue-800">
                <?php echo $summary['attendance_present']; ?> of <?php echo $summary['attendance_total']; ?> days
                (<?php echo number_format(($summary['attendance_present']/$summary['attendance_total'])*100, 0); ?>%)
            </span>
        </div>
        <?php endif; ?>

        <!-- Scores Table -->
        <div class="p-6 print:p-4">
            <h3 class="text-base font-bold text-primary mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined print:hidden">assignment</span>
                Subject Results
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-primary text-white text-xs">
                            <th class="px-3 py-3 text-left border border-slate-300">S/N</th>
                            <th class="px-3 py-3 text-left border border-slate-300">Subject</th>
                            <th class="px-3 py-3 text-center border border-slate-300">CA1<br>/20</th>
                            <th class="px-3 py-3 text-center border border-slate-300">CA2<br>/20</th>
                            <th class="px-3 py-3 text-center border border-slate-300">Exam<br>/60</th>
                            <th class="px-3 py-3 text-center border border-slate-300">Total<br>/100</th>
                            <th class="px-3 py-3 text-center border border-slate-300">Grade</th>
                            <th class="px-3 py-3 text-center border border-slate-300">Remark</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $sn => $r):
                            $gi = calculateGrade($r['total_score']);
                            $gc = match(true) {
                                $gi['grade'] === 'A1'                    => 'text-green-700 font-bold',
                                in_array($gi['grade'], ['B2','B3'])      => 'text-blue-700 font-bold',
                                in_array($gi['grade'], ['C4','C5','C6']) => 'text-yellow-700 font-bold',
                                $gi['grade'] === 'D7'                   => 'text-orange-700 font-bold',
                                default                                  => 'text-red-700 font-bold',
                            };
                            $row_bg = ($sn % 2 === 0) ? '' : 'bg-slate-50';
                        ?>
                        <tr class="<?php echo $row_bg; ?> hover:bg-gold/5 transition-colors">
                            <td class="px-3 py-2 text-center border border-slate-200 text-slate-400 text-xs"><?php echo $sn+1; ?></td>
                            <td class="px-3 py-2 border border-slate-200 font-semibold text-slate-800"><?php echo htmlspecialchars($r['subject_name']); ?></td>
                            <td class="px-3 py-2 text-center border border-slate-200"><?php echo number_format($r['ca1'], 1); ?></td>
                            <td class="px-3 py-2 text-center border border-slate-200"><?php echo number_format($r['ca2'], 1); ?></td>
                            <td class="px-3 py-2 text-center border border-slate-200"><?php echo number_format($r['exam_score'], 1); ?></td>
                            <td class="px-3 py-2 text-center border border-slate-200 font-bold text-base text-primary"><?php echo number_format($r['total_score'], 1); ?></td>
                            <td class="px-3 py-2 text-center border border-slate-200 <?php echo $gc; ?>"><?php echo htmlspecialchars($gi['grade']); ?></td>
                            <td class="px-3 py-2 text-center border border-slate-200 text-xs text-slate-600"><?php echo htmlspecialchars($gi['remark']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-gold/20 font-bold">
                            <td colspan="5" class="px-3 py-2 text-right border border-slate-300 text-sm">TOTAL SCORE:</td>
                            <td class="px-3 py-2 text-center border border-slate-300 text-lg text-primary"><?php echo number_format($summary['total_score'], 1); ?></td>
                            <td colspan="2" class="border border-slate-300"></td>
                        </tr>
                        <tr class="bg-primary text-white font-bold">
                            <td colspan="5" class="px-3 py-2 text-right border border-slate-300 text-sm">AVERAGE / PERCENTAGE:</td>
                            <td class="px-3 py-2 text-center border border-slate-300 text-lg"><?php echo number_format($summary['average_score'], 2); ?>%</td>
                            <td class="px-3 py-2 text-center border border-slate-300"><?php echo htmlspecialchars($grade_info['grade']); ?></td>
                            <td class="px-3 py-2 text-center border border-slate-300 text-xs"><?php echo htmlspecialchars($grade_info['remark']); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Grading Key -->
        <div class="px-6 pb-4 print:px-4 print:pb-2">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Grading Key:</p>
            <div class="flex flex-wrap gap-2">
                <?php
                $gk = $conn->query("SELECT * FROM grading_system ORDER BY min_score DESC");
                while ($gkr = $gk->fetch_assoc()):
                ?>
                <span class="px-2 py-1 bg-slate-100 rounded text-xs font-semibold text-slate-700">
                    <?php echo htmlspecialchars($gkr['grade']); ?>:
                    <?php echo $gkr['min_score'].'-'.$gkr['max_score']; ?>
                    (<?php echo htmlspecialchars($gkr['remark']); ?>)
                </span>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Comments -->
        <?php if (!empty($summary['class_teacher_comment']) || !empty($summary['principal_comment'])): ?>
        <div class="px-6 pb-6 grid md:grid-cols-2 gap-4 border-t border-slate-100 pt-5 print:px-4 print:pb-4">
            <?php if (!empty($summary['class_teacher_comment'])): ?>
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">
                    Class Teacher's Comment
                    <?php if (!empty($summary['class_teacher_name'])): ?>
                    — <span class="text-primary"><?php echo htmlspecialchars($summary['class_teacher_name']); ?></span>
                    <?php endif; ?>
                </p>
                <p class="text-slate-800 text-sm italic">"<?php echo htmlspecialchars($summary['class_teacher_comment']); ?>"</p>
                <div class="mt-3 pt-2 border-t border-slate-200">
                    <p class="text-xs text-slate-400">Signature: ___________________</p>
                </div>
            </div>
            <?php endif; ?>
            <?php if (!empty($summary['principal_comment'])): ?>
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">
                    Principal's Comment
                    — <span class="text-primary"><?php echo htmlspecialchars($summary['principal_name'] ?? 'DR. SAMUEL OMOGO'); ?></span>
                </p>
                <p class="text-slate-800 text-sm italic">"<?php echo htmlspecialchars($summary['principal_comment']); ?>"</p>
                <div class="mt-3 pt-2 border-t border-slate-200">
                    <p class="text-xs text-slate-400">Signature: ___________________</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Next Term -->
        <?php if (!empty($summary['next_term_begins'])): ?>
        <div class="px-6 pb-4 print:px-4">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-gold/10 border border-gold/30 rounded-lg text-sm">
                <span class="material-symbols-outlined text-gold text-sm">event</span>
                <span class="font-semibold text-primary">Next Term Begins:</span>
                <span class="text-slate-700"><?php echo date('l, F j, Y', strtotime($summary['next_term_begins'])); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="bg-primary text-white px-6 py-4 text-center print:px-4 print:py-3">
            <p class="text-xs text-slate-300">This is a computer-generated result slip. Valid without manual signature.</p>
            <p class="text-xs text-slate-400 mt-1">
                Generated: <?php echo date('D, d M Y \a\t g:i A'); ?>
                <?php if (!empty($summary['result_unique_id'])): ?>
                &nbsp;|&nbsp; Ref: <?php echo htmlspecialchars($summary['result_unique_id']); ?>
                <?php endif; ?>
                &nbsp;|&nbsp; PIN uses remaining: <?php echo max(0, $card['max_uses'] - ($card['times_used'] + 1)); ?>
            </p>
        </div>

    </div><!-- /resultCard -->
</div>
</section>

<style>
@media print {
    header, footer, nav, .print\:hidden { display: none !important; }
    body { background: white !important; }
    #resultCard { box-shadow: none !important; border-radius: 0 !important; }
    @page { margin: 10mm; size: A4; }
}
</style>

<?php include '../includes/footer.php'; ?>
