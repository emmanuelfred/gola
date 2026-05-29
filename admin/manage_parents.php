<?php
require_once 'auth_check.php';
require_once '../includes/email.php';

$page_title = "Parent & Guardian Communications";
$success = '';
$error   = '';

// ── Termii SMS helper ─────────────────────────────────────────────────────────
// Replace with your real Termii API key from https://termii.com
define('TERMII_API_KEY', 'TLAwTQJDLxaMPXlEbykMvJteAzrqOFfDtTOoLJOLbyMfIrIOMjSSGkQxBhAHFH');
define('TERMII_SENDER',  'idealforge');  // Must be approved sender ID on Termii

function sendTermiiSMS(string $to, string $message): bool {
    // Strip non-numeric chars, ensure Nigerian format (234...)
    $phone = preg_replace('/\D/', '', $to);
    if (strlen($phone) === 11 && $phone[0] === '0') {
        $phone = '234' . substr($phone, 1);
    }
    if (strlen($phone) < 10) return false;

    $payload = json_encode([
        'to'        => $phone,
        'from'      => TERMII_SENDER,
        'sms'       => $message,
        'type'      => 'plain',
        'channel'   => 'generic',
        'api_key'   => TERMII_API_KEY,
    ]);

    $ch = curl_init('https://api.ng.termii.com/api/sms/send');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err) { error_log("Termii curl error: $err"); return false; }
    $data = json_decode($response, true);
    return isset($data['message_id']) || (isset($data['code']) && $data['code'] === 'ok');
}

// ── Handle bulk / single send ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_communication'])) {
    $channel        = $_POST['channel'] ?? 'email';        // email | sms
    $subject        = trim($_POST['subject'] ?? '');
    $message        = trim($_POST['message'] ?? '');
    $student_ids    = array_map('intval', $_POST['student_ids'] ?? []);

    if (empty($student_ids)) {
        $error = 'Please select at least one student.';
    } elseif (!$message) {
        $error = 'Message cannot be empty.';
    } elseif ($channel === 'email' && !$subject) {
        $error = 'Email subject is required.';
    } else {
        $ids_str  = implode(',', $student_ids);
        $students = $conn->query("
            SELECT s.id, s.first_name, s.last_name, s.student_id,
                   s.parent_email, s.father_name, s.father_phone,
                   s.mother_name, s.mother_phone,
                   s.guardian_name, s.guardian_phone, s.guardian_relationship,
                   c.class_name, c.arm
            FROM students s
            JOIN classes c ON s.class_id = c.id
            WHERE s.id IN ($ids_str)
        ")->fetch_all(MYSQLI_ASSOC);

        $sent   = 0;
        $failed = 0;

        foreach ($students as $st) {
            $parent_name  = $st['father_name'] ?: $st['mother_name'] ?: $st['guardian_name'] ?: 'Parent/Guardian';
            $parent_phone = $st['father_phone'] ?: $st['mother_phone'] ?: $st['guardian_phone'] ?: '';
            $parent_email = $st['parent_email'] ?: '';

            // Personalise message — replace placeholders
            $personalised = str_replace(
                ['{student_name}', '{class}', '{parent_name}', '{student_id}'],
                [$st['first_name'].' '.$st['last_name'], $st['class_name'].' '.$st['arm'], $parent_name, $st['student_id']],
                $message
            );

            if ($channel === 'email') {
                if (!filter_var($parent_email, FILTER_VALIDATE_EMAIL)) { $failed++; continue; }
                try {
                    $mail = createMailer();
                    $mail->addAddress($parent_email, $parent_name);
                    $mail->Subject = $subject;
                    $mail->isHTML(true);
                    // Wrap in GOLA branded template
                    $html_body = nl2br(htmlspecialchars($personalised));
                    $mail->Body = emailWrap("
                        <h2>Dear $parent_name,</h2>
                        <p>".nl2br(htmlspecialchars($personalised))."</p>
                        <div class='divider'></div>
                        <p style='font-size:13px;color:#64748b;'>This message was sent from Goodness Omogo Leadership Academy regarding <strong>{$st['first_name']} {$st['last_name']}</strong> ({$st['student_id']}).</p>
                        <p style='font-size:13px;color:#64748b;'>📞 09125128213 | ✉️ golaedu2026@gmail.com</p>
                    ", $subject);
                    $mail->AltBody = $personalised;
                    $mail->send();
                    $sent++;
                } catch (Exception $e) {
                    error_log("Parent email failed for {$st['id']}: ".$e->getMessage());
                    $failed++;
                }
            } else { // sms
                if (!$parent_phone) { $failed++; continue; }
                // SMS: keep short — 160 chars
                $sms_text = mb_substr("GOLA: ".$personalised, 0, 160);
                if (sendTermiiSMS($parent_phone, $sms_text)) $sent++;
                else $failed++;
            }
        }

        // Log it
        $class_id_log = intval($_POST['class_filter'] ?? 0) ?: 'NULL';
        $ids_json     = mysqli_real_escape_string($conn, json_encode($student_ids));
        $subj_esc     = mysqli_real_escape_string($conn, $subject);
        $msg_esc      = mysqli_real_escape_string($conn, $message);
        $chan_esc      = mysqli_real_escape_string($conn, $channel);
        $rtype        = count($student_ids) === 1 ? 'single' : (isset($_POST['class_filter']) && $_POST['class_filter'] ? 'class' : 'bulk');
        $conn->query("INSERT INTO communication_logs
            (sent_by, channel, recipient_type, class_id, student_ids, subject, message, recipient_count, failed_count, status)
            VALUES ($admin_id, '$chan_esc', '$rtype', $class_id_log, '$ids_json', '$subj_esc', '$msg_esc', $sent, $failed,
                    '".($failed===0?'sent':($sent>0?'partial':'failed'))."')");

        logActivity('send_communication', "Sent $channel to $sent parents ($failed failed)");
        $success = "✓ $channel sent to <strong>$sent parent(s)</strong>." . ($failed > 0 ? " <span class='text-amber-600'>$failed failed (no valid contact).</span>" : '');
    }
}

// ── Handle status change (expire/suspend/graduate) ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status'])) {
    $student_id_db = intval($_POST['student_db_id']);
    $new_status    = $_POST['new_status'] ?? '';
    $reason        = trim($_POST['status_reason'] ?? '');
    $allowed       = ['Active','Graduated','Withdrawn','Suspended'];

    if (!in_array($new_status, $allowed)) {
        $error = 'Invalid status.';
    } else {
        $ns = mysqli_real_escape_string($conn, $new_status);
        $conn->query("UPDATE students SET status='$ns', updated_at=NOW() WHERE id=$student_id_db");
        logActivity('change_student_status', "Changed student #$student_id_db to $new_status. Reason: $reason");

        // Fetch student for notification email
        $st = $conn->query("SELECT s.*, c.class_name, c.arm FROM students s JOIN classes c ON s.class_id=c.id WHERE s.id=$student_id_db")->fetch_assoc();
        if ($st && filter_var($st['parent_email'], FILTER_VALIDATE_EMAIL)) {
            $parent_name = $st['father_name'] ?: $st['mother_name'] ?: $st['guardian_name'] ?: 'Parent/Guardian';
            $student_name = $st['first_name'].' '.$st['last_name'];
            $class_label  = $st['class_name'].' '.$st['arm'];

            $status_messages = [
                'Graduated'  => "We are pleased to inform you that <strong>$student_name</strong> has successfully <strong>graduated</strong> from Goodness Omogo Leadership Academy. We wish them every success in their future endeavours.",
                'Withdrawn'  => "This is to inform you that <strong>$student_name</strong> has been <strong>withdrawn</strong> from Goodness Omogo Leadership Academy." . ($reason ? " Reason: $reason." : ''),
                'Suspended'  => "This is to inform you that <strong>$student_name</strong> has been placed on <strong>suspension</strong> from Goodness Omogo Leadership Academy." . ($reason ? " Reason: $reason." : '') . " Please contact the school office immediately.",
                'Active'     => "<strong>$student_name</strong>'s student status has been restored to <strong>Active</strong>." . ($reason ? " Note: $reason." : ''),
            ];

            $status_subjects = [
                'Graduated' => "Congratulations — Graduation Notice for $student_name",
                'Withdrawn' => "Important: Withdrawal Notice — $student_name",
                'Suspended' => "URGENT: Suspension Notice — $student_name",
                'Active'    => "Student Status Update — $student_name",
            ];

            try {
                $mail = createMailer();
                $mail->addAddress($st['parent_email'], $parent_name);
                $mail->Subject = $status_subjects[$new_status] ?? "Student Status Update — $student_name";
                $mail->isHTML(true);
                $mail->Body = emailWrap("
                    <h2>Dear $parent_name,</h2>
                    <p>{$status_messages[$new_status]}</p>
                    <table class='detail-table'>
                        <tr><td>Student Name</td><td>$student_name</td></tr>
                        <tr><td>Student ID</td><td>{$st['student_id']}</td></tr>
                        <tr><td>Class</td><td>$class_label</td></tr>
                        <tr><td>New Status</td><td><strong>$new_status</strong></td></tr>
                    </table>
                    <div class='divider'></div>
                    <p style='font-size:13px;color:#64748b;'>If you have any questions, please contact us immediately.<br>
                    📞 <strong>09125128213</strong> | ✉️ <strong>golaedu2026@gmail.com</strong></p>
                ", $status_subjects[$new_status] ?? '');
                $mail->AltBody = strip_tags($status_messages[$new_status]);
                $mail->send();
                $success = "Student status changed to <strong>$new_status</strong> and notification email sent to guardian.";
            } catch (Exception $e) {
                $success = "Student status changed to <strong>$new_status</strong>. Email failed: ".$e->getMessage();
            }
        } else {
            $success = "Student status changed to <strong>$new_status</strong>. No guardian email on file.";
        }
    }
}

// ── Filters ───────────────────────────────────────────────────────────────────
$class_filter  = intval($_GET['class_id'] ?? 0);
$status_filter = $_GET['status'] ?? 'Active';
$search        = trim($_GET['search'] ?? '');

$where = "s.id > 0";
if ($class_filter)  $where .= " AND s.class_id = $class_filter";
if ($status_filter) $where .= " AND s.status = '".mysqli_real_escape_string($conn,$status_filter)."'";
if ($search)        $where .= " AND (s.first_name LIKE '%".mysqli_real_escape_string($conn,$search)."%'
                                  OR s.last_name  LIKE '%".mysqli_real_escape_string($conn,$search)."%'
                                  OR s.student_id LIKE '%".mysqli_real_escape_string($conn,$search)."%'
                                  OR s.parent_email LIKE '%".mysqli_real_escape_string($conn,$search)."%'
                                  OR s.father_name LIKE '%".mysqli_real_escape_string($conn,$search)."%'
                                  OR s.mother_name LIKE '%".mysqli_real_escape_string($conn,$search)."%')";

$students = $conn->query("
    SELECT s.id, s.student_id, s.first_name, s.last_name, s.gender, s.status,
           s.parent_email, s.father_name, s.father_phone,
           s.mother_name, s.mother_phone,
           s.guardian_name, s.guardian_phone, s.guardian_relationship,
           c.id as class_id, c.class_name, c.arm
    FROM students s
    JOIN classes c ON s.class_id = c.id
    WHERE $where
    ORDER BY c.class_name, c.arm, s.last_name, s.first_name
")->fetch_all(MYSQLI_ASSOC);

$classes = $conn->query("SELECT id, class_name, arm FROM classes ORDER BY class_name, arm")->fetch_all(MYSQLI_ASSOC);

// Counts per status
$counts = [];
$cq = $conn->query("SELECT status, COUNT(*) as c FROM students GROUP BY status");
while ($r = $cq->fetch_assoc()) $counts[$r['status']] = $r['c'];
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
.student-row.selected { background: rgba(197,160,89,.07); }
.student-row.selected td:first-child { border-left: 3px solid #C5A059; }
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
        <h1 class="text-2xl font-bold text-slate-900">Parent & Guardian Communications</h1>
        <p class="text-slate-500 text-sm mt-1">Filter students by class, select recipients, then send email or SMS.</p>
    </div>
    <div class="flex gap-3">
        <a href="manage_classes.php"
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 text-slate-700 text-sm font-semibold rounded-xl hover:bg-slate-50 transition-all">
            <span class="material-symbols-outlined text-sm">school</span>Manage Classes
        </a>
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

<!-- Status stat pills -->
<div class="flex flex-wrap gap-3 mb-5">
    <?php foreach(['Active'=>'green','Graduated'=>'blue','Suspended'=>'amber','Withdrawn'=>'red'] as $st=>$col): ?>
    <a href="?status=<?php echo $st; ?><?php echo $class_filter?"&class_id=$class_filter":''; ?>"
       class="px-4 py-2 rounded-full text-xs font-bold border-2 transition-all
              <?php echo $status_filter===$st
                ? "bg-$col-600 text-white border-$col-600"
                : "bg-white text-$col-700 border-$col-200 hover:border-$col-400"; ?>">
        <?php echo $st; ?> (<?php echo $counts[$st]??0; ?>)
    </a>
    <?php endforeach; ?>
    <a href="?status=" class="px-4 py-2 rounded-full text-xs font-bold border-2 bg-white text-slate-600 border-slate-200 hover:border-slate-400 transition-all">
        All (<?php echo array_sum($counts); ?>)
    </a>
</div>

<div class="grid xl:grid-cols-3 gap-6">

<!-- ── LEFT: Filters + Student Table ── -->
<div class="xl:col-span-2">

    <!-- Filters bar -->
    <form method="GET" class="bg-white rounded-xl border border-slate-200 p-4 mb-4 flex flex-wrap gap-3 items-end">
        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
        <div class="flex-1 min-w-40">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1 block">Search</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                placeholder="Name, ID, parent name or email…"
                class="w-full border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:ring-gold focus:border-gold">
        </div>
        <div>
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1 block">Class</label>
            <select name="class_id" class="border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:ring-gold focus:border-gold">
                <option value="">All Classes</option>
                <?php foreach($classes as $cl): ?>
                <option value="<?php echo $cl['id']; ?>" <?php echo $class_filter==$cl['id']?'selected':''; ?>>
                    <?php echo htmlspecialchars($cl['class_name'].' '.$cl['arm']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="px-5 py-2.5 bg-primary text-white text-sm font-bold rounded-xl hover:bg-primary/90">Filter</button>
        <a href="manage_parents.php" class="px-4 py-2.5 text-sm text-slate-400 hover:text-gold font-semibold">Reset</a>
    </form>

    <!-- Selection toolbar (shows when rows are checked) -->
    <div id="selectionBar" class="hidden bg-primary text-white rounded-xl px-5 py-3 mb-3 flex items-center gap-4 flex-wrap">
        <span class="font-bold text-sm"><span id="selCount">0</span> selected</span>
        <button onclick="selectAll()" class="text-xs bg-white/20 hover:bg-white/30 px-3 py-1.5 rounded-lg font-semibold transition-all">Select All <?php echo count($students); ?></button>
        <button onclick="clearSelection()" class="text-xs bg-white/20 hover:bg-white/30 px-3 py-1.5 rounded-lg font-semibold transition-all">Clear</button>
        <div class="ml-auto flex gap-2">
            <button onclick="openCompose('email')"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-gold text-primary text-sm font-bold rounded-lg hover:bg-gold/90 transition-all">
                <span class="material-symbols-outlined text-sm">email</span>Send Email
            </button>
            <button onclick="openCompose('sms')"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-green-500 text-white text-sm font-bold rounded-lg hover:bg-green-600 transition-all">
                <span class="material-symbols-outlined text-sm">sms</span>Send SMS
            </button>
        </div>
    </div>

    <!-- Student Table -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 text-sm"><?php echo count($students); ?> student(s)</h3>
            <div class="flex gap-2">
                <button onclick="selectAll()" class="text-xs text-slate-400 hover:text-primary font-semibold">Select All</button>
                <span class="text-slate-200">|</span>
                <button onclick="clearSelection()" class="text-xs text-slate-400 hover:text-primary font-semibold">Clear</button>
            </div>
        </div>
        <?php if (empty($students)): ?>
        <div class="p-12 text-center text-slate-400">
            <span class="material-symbols-outlined text-5xl block mb-2">group</span>
            No students found matching these filters.
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b text-xs">
                    <tr>
                        <th class="px-4 py-3 w-8"><input type="checkbox" id="selectAllCheck" onchange="toggleAll(this)" class="rounded text-gold focus:ring-gold"></th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-500 uppercase">Student</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-500 uppercase">Class</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-500 uppercase">Parent / Guardian</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-500 uppercase">Contact</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100" id="studentTableBody">
                    <?php foreach($students as $st):
                        $parent_name  = $st['father_name'] ?: $st['mother_name'] ?: $st['guardian_name'] ?: '—';
                        $parent_phone = $st['father_phone'] ?: $st['mother_phone'] ?: $st['guardian_phone'] ?: '—';
                        $has_email    = filter_var($st['parent_email'], FILTER_VALIDATE_EMAIL);
                        $has_phone    = $parent_phone !== '—';
                        $sc = ['Active'=>'bg-green-100 text-green-700','Graduated'=>'bg-blue-100 text-blue-700','Suspended'=>'bg-amber-100 text-amber-700','Withdrawn'=>'bg-red-100 text-red-700'];
                        $cc = $sc[$st['status']] ?? 'bg-slate-100 text-slate-600';
                    ?>
                    <tr class="student-row hover:bg-slate-50 transition-colors"
                        data-id="<?php echo $st['id']; ?>"
                        data-name="<?php echo htmlspecialchars($st['first_name'].' '.$st['last_name']); ?>">
                        <td class="px-4 py-3">
                            <input type="checkbox" class="student-check rounded text-gold focus:ring-gold"
                                value="<?php echo $st['id']; ?>"
                                onchange="updateSelection()">
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center text-primary font-bold text-xs flex-shrink-0">
                                    <?php echo strtoupper(substr($st['first_name'],0,1).substr($st['last_name'],0,1)); ?>
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-900"><?php echo htmlspecialchars($st['first_name'].' '.$st['last_name']); ?></p>
                                    <p class="text-xs font-mono text-slate-400"><?php echo htmlspecialchars($st['student_id']); ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-xs font-semibold text-slate-700">
                            <?php echo htmlspecialchars($st['class_name'].' '.$st['arm']); ?>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-sm text-slate-800 font-medium"><?php echo htmlspecialchars($parent_name); ?></p>
                            <?php if ($st['guardian_relationship']): ?>
                            <p class="text-xs text-slate-400"><?php echo htmlspecialchars($st['guardian_relationship']); ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($has_email): ?>
                            <p class="text-xs text-slate-600 flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs text-gold">email</span>
                                <?php echo htmlspecialchars($st['parent_email']); ?>
                            </p>
                            <?php endif; ?>
                            <?php if ($has_phone): ?>
                            <p class="text-xs text-slate-600 flex items-center gap-1 mt-0.5">
                                <span class="material-symbols-outlined text-xs text-green-500">phone</span>
                                <?php echo htmlspecialchars($parent_phone); ?>
                            </p>
                            <?php endif; ?>
                            <?php if (!$has_email && !$has_phone): ?>
                            <span class="text-xs text-red-400">No contact info</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?php echo $cc; ?>"><?php echo $st['status']; ?></span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-1">
                                <!-- Quick single email -->
                                <?php if ($has_email): ?>
                                <button onclick="quickSend(<?php echo $st['id']; ?>, '<?php echo htmlspecialchars(addslashes($st['first_name'].' '.$st['last_name'])); ?>', 'email')"
                                    title="Send Email"
                                    class="p-1.5 hover:bg-gold/10 text-gold rounded-lg transition-all">
                                    <span class="material-symbols-outlined text-base">email</span>
                                </button>
                                <?php endif; ?>
                                <!-- Quick single SMS -->
                                <?php if ($has_phone): ?>
                                <button onclick="quickSend(<?php echo $st['id']; ?>, '<?php echo htmlspecialchars(addslashes($st['first_name'].' '.$st['last_name'])); ?>', 'sms')"
                                    title="Send SMS"
                                    class="p-1.5 hover:bg-green-50 text-green-600 rounded-lg transition-all">
                                    <span class="material-symbols-outlined text-base">sms</span>
                                </button>
                                <?php endif; ?>
                                <!-- Change status -->
                                <button onclick="openStatusModal(<?php echo $st['id']; ?>, '<?php echo htmlspecialchars(addslashes($st['first_name'].' '.$st['last_name'])); ?>', '<?php echo $st['status']; ?>')"
                                    title="Change Status"
                                    class="p-1.5 hover:bg-slate-100 text-slate-500 rounded-lg transition-all">
                                    <span class="material-symbols-outlined text-base">manage_accounts</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── RIGHT: Communication Log ── -->
<div class="xl:col-span-1">
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden sticky top-4">
        <div class="px-5 py-4 bg-primary">
            <h3 class="font-bold text-white">Recent Communications</h3>
            <p class="text-slate-300 text-xs mt-0.5">Last 20 messages sent</p>
        </div>
        <?php
        $logs = $conn->query("
            SELECT l.*, a.full_name as sent_by_name
            FROM communication_logs l
            LEFT JOIN admin_users a ON a.id = l.sent_by
            ORDER BY l.sent_at DESC LIMIT 20
        ")->fetch_all(MYSQLI_ASSOC);
        ?>
        <?php if (empty($logs)): ?>
        <div class="p-8 text-center text-slate-400 text-sm">No communications sent yet.</div>
        <?php else: ?>
        <div class="divide-y divide-slate-100 max-h-[70vh] overflow-y-auto">
            <?php foreach($logs as $log):
                $ch_color = $log['channel']==='email' ? 'text-gold bg-gold/10' : 'text-green-600 bg-green-100';
                $ch_icon  = $log['channel']==='email' ? 'email' : 'sms';
                $st_color = ['sent'=>'text-green-600','partial'=>'text-amber-500','failed'=>'text-red-500'][$log['status']] ?? '';
            ?>
            <div class="px-4 py-3 hover:bg-slate-50 transition-colors">
                <div class="flex items-start gap-2.5">
                    <div class="w-8 h-8 rounded-xl <?php echo $ch_color; ?> flex items-center justify-center flex-shrink-0 mt-0.5">
                        <span class="material-symbols-outlined text-sm"><?php echo $ch_icon; ?></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-slate-800 truncate">
                            <?php echo htmlspecialchars($log['subject'] ?: mb_substr($log['message'],0,40).'…'); ?>
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            <?php echo $log['recipient_count']; ?> sent
                            <?php if ($log['failed_count']>0): ?>· <span class="text-amber-500"><?php echo $log['failed_count']; ?> failed</span><?php endif; ?>
                            · <span class="<?php echo $st_color; ?> font-semibold"><?php echo ucfirst($log['status']); ?></span>
                        </p>
                        <p class="text-xs text-slate-300 mt-0.5"><?php echo date('d M Y g:ia', strtotime($log['sent_at'])); ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>

</main>
</div>
</div>

<!-- ══ COMPOSE MODAL ══ -->
<div id="composeModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
    <div class="bg-primary rounded-t-2xl px-6 py-5 flex items-center justify-between">
        <div>
            <h2 class="text-white font-bold text-lg" id="composeTitle">Compose Message</h2>
            <p class="text-slate-300 text-xs mt-0.5" id="composeSubtitle">Sending to selected parents</p>
        </div>
        <button onclick="closeCompose()" class="text-white/60 hover:text-white transition-colors">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>
    <form method="POST" id="composeForm" class="p-6 space-y-4">
        <input type="hidden" name="send_communication" value="1">
        <input type="hidden" name="channel" id="channelInput" value="email">
        <input type="hidden" name="class_filter" value="<?php echo $class_filter; ?>">
        <div id="selectedStudentsContainer"></div>

        <!-- Recipient summary -->
        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Recipients</p>
            <p class="text-sm font-semibold text-slate-800" id="recipientSummary">—</p>
        </div>

        <!-- Subject (email only) -->
        <div id="subjectField">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1 block">Subject *</label>
            <input type="text" name="subject" id="subjectInput" placeholder="e.g. End of Term Notice"
                class="w-full border-slate-200 rounded-xl text-sm px-4 py-3 focus:ring-gold focus:border-gold">
        </div>

        <!-- Message -->
        <div>
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1 block">Message *</label>
            <textarea name="message" id="messageInput" rows="6"
                class="w-full border-slate-200 rounded-xl text-sm px-4 py-3 focus:ring-gold focus:border-gold resize-none"
                placeholder="Type your message…"></textarea>
            <p class="text-xs text-slate-400 mt-1.5">
                Use <code class="bg-slate-100 px-1 rounded">{student_name}</code>,
                <code class="bg-slate-100 px-1 rounded">{class}</code>,
                <code class="bg-slate-100 px-1 rounded">{parent_name}</code> for personalisation.
                <span id="smsCount" class="hidden font-semibold text-amber-600 ml-2">0/160 chars</span>
            </p>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" id="sendBtn"
                class="flex-1 py-3 rounded-xl font-bold text-sm transition-all bg-primary text-white hover:bg-primary/90 flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-sm" id="sendIcon">send</span>
                <span id="sendLabel">Send Email</span>
            </button>
            <button type="button" onclick="closeCompose()"
                class="flex-1 py-3 rounded-xl font-bold text-sm bg-slate-100 text-slate-700 hover:bg-slate-200 transition-all">
                Cancel
            </button>
        </div>
    </form>
</div>
</div>

<!-- ══ STATUS CHANGE MODAL ══ -->
<div id="statusModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
    <div class="bg-slate-700 rounded-t-2xl px-6 py-5 flex items-center justify-between">
        <div>
            <h2 class="text-white font-bold text-lg">Change Student Status</h2>
            <p class="text-slate-300 text-xs mt-0.5" id="statusStudentName">—</p>
        </div>
        <button onclick="closeStatusModal()" class="text-white/60 hover:text-white">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>
    <form method="POST" class="p-6 space-y-4">
        <input type="hidden" name="change_status" value="1">
        <input type="hidden" name="student_db_id" id="statusStudentId">
        <div>
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1 block">New Status</label>
            <div class="grid grid-cols-2 gap-3">
                <?php foreach(['Active'=>['green','check_circle'],'Graduated'=>['blue','school'],'Suspended'=>['amber','block'],'Withdrawn'=>['red','logout']] as $st=>[$col,$icon]): ?>
                <label class="flex items-center gap-2 p-3 border-2 border-slate-200 rounded-xl cursor-pointer hover:border-<?php echo $col; ?>-400 has-[:checked]:border-<?php echo $col; ?>-500 has-[:checked]:bg-<?php echo $col; ?>-50 transition-all">
                    <input type="radio" name="new_status" value="<?php echo $st; ?>" class="text-<?php echo $col; ?>-600">
                    <span class="material-symbols-outlined text-<?php echo $col; ?>-600 text-lg"><?php echo $icon; ?></span>
                    <span class="text-sm font-semibold text-slate-700"><?php echo $st; ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div>
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1 block">Reason / Notes</label>
            <textarea name="status_reason" rows="3"
                class="w-full border-slate-200 rounded-xl text-sm px-4 py-3 focus:ring-gold focus:border-gold resize-none"
                placeholder="Optional — will be included in the guardian notification email…"></textarea>
        </div>
        <p class="text-xs text-slate-400 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-sm text-gold">info</span>
            An email notification will be sent to the guardian automatically.
        </p>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 bg-slate-700 text-white py-3 rounded-xl font-bold text-sm hover:bg-slate-800 transition-all">
                Update Status & Notify
            </button>
            <button type="button" onclick="closeStatusModal()" class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl font-bold text-sm hover:bg-slate-200 transition-all">Cancel</button>
        </div>
    </form>
</div>
</div>

<script>
// ── Selection ─────────────────────────────────────────────────────────
let selected = new Set();

function updateSelection() {
    selected.clear();
    document.querySelectorAll('.student-check:checked').forEach(cb => {
        selected.add(parseInt(cb.value));
        cb.closest('tr').classList.add('selected');
    });
    document.querySelectorAll('.student-check:not(:checked)').forEach(cb => {
        cb.closest('tr').classList.remove('selected');
    });

    const bar = document.getElementById('selectionBar');
    bar.classList.toggle('hidden', selected.size === 0);
    document.getElementById('selCount').textContent = selected.size;
    document.getElementById('selectAllCheck').indeterminate =
        selected.size > 0 && selected.size < document.querySelectorAll('.student-check').length;
    document.getElementById('selectAllCheck').checked =
        selected.size === document.querySelectorAll('.student-check').length;
}

function toggleAll(master) {
    document.querySelectorAll('.student-check').forEach(cb => {
        cb.checked = master.checked;
        cb.closest('tr').classList.toggle('selected', master.checked);
        if (master.checked) selected.add(parseInt(cb.value));
    });
    if (!master.checked) selected.clear();
    const bar = document.getElementById('selectionBar');
    bar.classList.toggle('hidden', selected.size === 0);
    document.getElementById('selCount').textContent = selected.size;
}

function selectAll() {
    document.querySelectorAll('.student-check').forEach(cb => {
        cb.checked = true;
        cb.closest('tr').classList.add('selected');
        selected.add(parseInt(cb.value));
    });
    document.getElementById('selectAllCheck').checked = true;
    document.getElementById('selectionBar').classList.remove('hidden');
    document.getElementById('selCount').textContent = selected.size;
}

function clearSelection() {
    selected.clear();
    document.querySelectorAll('.student-check').forEach(cb => {
        cb.checked = false;
        cb.closest('tr').classList.remove('selected');
    });
    document.getElementById('selectAllCheck').checked = false;
    document.getElementById('selectionBar').classList.add('hidden');
}

// ── Compose modal ─────────────────────────────────────────────────────
function openCompose(channel) {
    if (selected.size === 0) { alert('Please select at least one student first.'); return; }
    document.getElementById('channelInput').value = channel;

    const isEmail = channel === 'email';
    document.getElementById('composeTitle').textContent   = isEmail ? 'Send Email to Parents' : 'Send SMS to Parents';
    document.getElementById('composeSubtitle').textContent = `Sending to ${selected.size} parent(s)`;
    document.getElementById('subjectField').classList.toggle('hidden', !isEmail);
    document.getElementById('sendIcon').textContent  = isEmail ? 'email' : 'sms';
    document.getElementById('sendLabel').textContent = isEmail ? 'Send Email' : 'Send SMS';
    document.getElementById('smsCount').classList.toggle('hidden', isEmail);
    document.getElementById('sendBtn').className = 'flex-1 py-3 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2 ' +
        (isEmail ? 'bg-primary text-white hover:bg-primary/90' : 'bg-green-600 text-white hover:bg-green-700');

    // Build summary
    const rows = document.querySelectorAll('.student-row.selected');
    const names = Array.from(rows).slice(0,3).map(r => r.dataset.name).join(', ');
    document.getElementById('recipientSummary').textContent =
        names + (selected.size > 3 ? ` and ${selected.size - 3} more…` : '');

    // Build hidden inputs for student_ids[]
    const container = document.getElementById('selectedStudentsContainer');
    container.innerHTML = '';
    selected.forEach(id => {
        const inp = document.createElement('input');
        inp.type  = 'hidden';
        inp.name  = 'student_ids[]';
        inp.value = id;
        container.appendChild(inp);
    });

    document.getElementById('composeModal').classList.remove('hidden');
}

function closeCompose() {
    document.getElementById('composeModal').classList.add('hidden');
    document.getElementById('messageInput').value = '';
    document.getElementById('subjectInput').value = '';
}

// SMS char counter
document.getElementById('messageInput').addEventListener('input', function() {
    const cnt = document.getElementById('smsCount');
    if (!cnt.classList.contains('hidden')) {
        const len = this.value.length;
        cnt.textContent = len + '/160 chars';
        cnt.className = len > 160
            ? 'font-semibold text-red-600 ml-2'
            : 'font-semibold text-amber-600 ml-2';
    }
});

// Quick send single
function quickSend(id, name, channel) {
    selected.clear();
    document.querySelectorAll('.student-check').forEach(cb => {
        cb.checked = cb.value == id;
        cb.closest('tr').classList.toggle('selected', cb.value == id);
        if (cb.value == id) selected.add(parseInt(cb.value));
    });
    document.getElementById('selCount').textContent = 1;
    document.getElementById('selectionBar').classList.remove('hidden');
    openCompose(channel);
}

// Close on backdrop
['composeModal','statusModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) this.classList.add('hidden');
    });
});

// ── Status modal ──────────────────────────────────────────────────────
function openStatusModal(id, name, currentStatus) {
    document.getElementById('statusStudentId').value = id;
    document.getElementById('statusStudentName').textContent = name + ' — Currently: ' + currentStatus;
    // Pre-select current status
    const radios = document.querySelectorAll('[name=new_status]');
    radios.forEach(r => { r.checked = r.value === currentStatus; });
    document.getElementById('statusModal').classList.remove('hidden');
}
function closeStatusModal() {
    document.getElementById('statusModal').classList.add('hidden');
}
</script>
</body>
</html>
