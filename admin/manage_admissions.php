<?php
require_once 'auth_check.php';
require_once '../includes/email.php';

function getSetting($conn, $key, $default = '') {
    $r = $conn->query("SELECT setting_value FROM school_settings WHERE setting_key='".mysqli_real_escape_string($conn,$key)."' LIMIT 1");
    $row = $r ? $r->fetch_assoc() : null;
    return $row ? $row['setting_value'] : $default;
}

$page_title = "Manage Admissions";

$success = '';
$error   = '';

// ── Handle actions ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // Update status + send email
    if ($_POST['action'] === 'update_status') {
        $app_id  = intval($_POST['app_id']);
        $status  = $_POST['status'] ?? '';
        $notes   = trim($_POST['admin_notes'] ?? '');
        $exam_no = trim($_POST['exam_no'] ?? '');
        $allowed = ['Under Review','Shortlisted','Admitted','Rejected'];
        if (in_array($status, $allowed)) {
            $conn->query("UPDATE admissions_applications SET
                status='".mysqli_real_escape_string($conn, $status)."',
                admin_notes='".mysqli_real_escape_string($conn, $notes)."',
                exam_no='".mysqli_real_escape_string($conn, $exam_no)."',
                reviewed_by=$admin_id,
                reviewed_at=NOW(),
                ".($status==='Admitted' ? "admitted_at=NOW()," : "")."
                updated_at=NOW()
                WHERE id=$app_id");
            logActivity('update_application', "Set application #$app_id to $status");

            $app = $conn->query("SELECT * FROM admissions_applications WHERE id=$app_id")->fetch_assoc();
            if ($app && filter_var($app['parent_email'], FILTER_VALIDATE_EMAIL)) {
                $exam_date  = getSetting($conn, 'entrance_exam_date', '');
                $exam_venue = getSetting($conn, 'entrance_exam_venue', '');
                $mail_data  = [
                    'full_name'      => $app['full_name'],
                    'email'          => $app['parent_email'],
                    'application_no' => $app['application_no'],
                    'exam_no'        => $exam_no ?: $app['exam_no'],
                    'grade_applying' => $app['grade_applying_for'],
                    'session'        => $app['session_applying'],
                    'exam_date'      => $exam_date,
                    'exam_venue'     => $exam_venue,
                    'admin_notes'    => $notes,
                ];
                $mail_result = sendStatusUpdateEmail($status, $mail_data);
                $success = $mail_result['ok']
                    ? "Status updated to <strong>$status</strong> and email sent to {$app['parent_email']}."
                    : "Status updated to <strong>$status</strong>. <span class='text-amber-600'>Email failed: ".$mail_result['error']."</span>";
            } else {
                $success = "Status updated to <strong>$status</strong>. No email sent (no valid email on file).";
            }
        }
    }

    // Verify payment
    if ($_POST['action'] === 'verify_payment') {
        $app_id = intval($_POST['app_id']);
        $conn->query("UPDATE admissions_applications SET payment_verified=1 WHERE id=$app_id");
        $success = "Payment verified.";
    }

    // Enrol applicant into students table — maps ALL application fields
    if ($_POST['action'] === 'enrol' && hasPermission('admin')) {
        $app_id       = intval($_POST['app_id']);
        $class_id     = intval($_POST['class_id']);
        $student_type = in_array($_POST['student_type'] ?? 'Boarding', ['Boarding','Day'])
                        ? $_POST['student_type'] : 'Boarding';

        $app = $conn->query("SELECT * FROM admissions_applications WHERE id=$app_id")->fetch_assoc();
        if (!$app) {
            $error = 'Application not found.';
        } else {
            // Generate Student ID
            $year  = date('Y');
            $count = $conn->query("SELECT COUNT(*) as c FROM students WHERE student_id LIKE 'GOLA/$year/%'")->fetch_assoc()['c'];
            $student_id = 'GOLA/' . $year . '/' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);

            // Get current session_id
            $sess_row   = $conn->query("SELECT id FROM academic_sessions WHERE is_current=1 LIMIT 1")->fetch_assoc();
            $session_id = $sess_row ? intval($sess_row['id']) : null;

            // Parse full name — stored as "SURNAME Firstname Middlename"
            $parts       = explode(' ', trim($app['full_name']), 3);
            $last_name   = $parts[0] ?? '';
            $first_name  = $parts[1] ?? '';
            $middle_name = $parts[2] ?? '';

            // Map application health fields to students table format
            $has_medical = $app['has_medical_condition'] ? 'Yes' : 'No';
            $allergies   = $app['has_allergies'] ? ($app['allergy_details'] ?: 'Yes') : null;

            $stmt = $conn->prepare("
                INSERT INTO students (
                    student_id, first_name, middle_name, last_name,
                    gender, date_of_birth, state_of_origin, lga, nationality,
                    religion, phone, class_id, session_id, admission_date,
                    status, student_type,
                    father_name, father_phone,
                    mother_name, mother_phone,
                    guardian_name, guardian_phone, guardian_relationship,
                    parent_email, home_address,
                    emergency_contact_name, emergency_contact_phone,
                    has_medical_condition, medical_condition_desc,
                    allergies, doctor_name, doctor_phone,
                    previous_school_name, previous_class_completed, reason_for_leaving
                ) VALUES (
                    ?,?,?,?,  ?,?,?,?,?,  ?,?,?,?,CURDATE(),  'Active',?,
                    ?,?,  ?,?,  ?,?,?,  ?,?,
                    ?,?,  ?,?,  ?,?,?,  ?,?,?
                )
            ");

            $stmt->bind_param(
                "ssss" . "sssss" . "ssiis" .
                "ss" . "ss" . "sss" . "ss" .
                "ss" . "ss" . "sss" . "sss",
                $student_id, $first_name, $middle_name, $last_name,
                $app['gender'], $app['date_of_birth'],
                $app['state_of_origin'], $app['lga'], $app['nationality'],
                $app['religion'], $app['phone_number'],
                $class_id, $session_id,
                $student_type,
                $app['father_name'],  $app['father_phone'],
                $app['mother_name'],  $app['mother_phone'],
                $app['guardian_name'], $app['guardian_phone'], $app['guardian_relationship'],
                $app['parent_email'], $app['home_address'],
                $app['emergency_contact_name'], $app['emergency_contact_phone'],
                $has_medical, $app['medical_condition_details'],
                $allergies, $app['family_doctor'], $app['family_doctor_phone'],
                $app['last_school'], $app['class_completed'], $app['reason_for_leaving']
            );

            if ($stmt->execute()) {
                $new_db_id = $conn->insert_id;
                $conn->query("UPDATE admissions_applications SET
                    status='Enrolled', enrolled_at=NOW(),
                    enrolled_as_student_id=$new_db_id WHERE id=$app_id");
                logActivity('enrol_student', "Enrolled $student_id from application #$app_id");

                // Send enrolment email
                if (filter_var($app['parent_email'], FILTER_VALIDATE_EMAIL)) {
                    sendStatusUpdateEmail('Enrolled', [
                        'full_name'      => $app['full_name'],
                        'email'          => $app['parent_email'],
                        'application_no' => $app['application_no'],
                        'exam_no'        => $app['exam_no'],
                        'grade_applying' => $app['grade_applying_for'],
                        'session'        => $app['session_applying'],
                        'student_id'     => $student_id,
                        'exam_date'      => '', 'exam_venue' => '', 'admin_notes' => '',
                    ]);
                }
                $success = "Student <strong>$student_id</strong> enrolled. All application data copied to student record.";
            } else {
                $error = 'Enrolment failed: ' . $conn->error;
            }
        }
    }
}

// ── Filters ────────────────────────────────────────────────────────────────────
$status_filter  = $_GET['status'] ?? '';
$session_filter = $_GET['session'] ?? '';
$search         = trim($_GET['search'] ?? '');

$where = "1=1";
if ($status_filter)  $where .= " AND status='".mysqli_real_escape_string($conn, $status_filter)."'";
if ($session_filter) $where .= " AND session_applying='".mysqli_real_escape_string($conn, $session_filter)."'";
if ($search)         $where .= " AND (full_name LIKE '%".mysqli_real_escape_string($conn, $search)."%' OR application_no LIKE '%".mysqli_real_escape_string($conn, $search)."%' OR phone_number LIKE '%".mysqli_real_escape_string($conn, $search)."%')";

$applications = $conn->query("SELECT * FROM admissions_applications WHERE $where ORDER BY created_at DESC");
$classes      = $conn->query("SELECT id, class_name, arm FROM classes ORDER BY class_name, arm");

// Status counts
$counts = [];
$cq = $conn->query("SELECT status, COUNT(*) as c FROM admissions_applications GROUP BY status");
while ($r = $cq->fetch_assoc()) $counts[$r['status']] = $r['c'];
$total = array_sum($counts);

// Single application view
$view_app = null;
if (isset($_GET['view'])) {
    $view_id = intval($_GET['view']);
    $view_app = $conn->query("SELECT * FROM admissions_applications WHERE id=$view_id")->fetch_assoc();
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

<?php if ($view_app): ?>
<!-- ══════════════════════════════════════════════
     SINGLE APPLICATION VIEW
══════════════════════════════════════════════ -->
<div class="mb-6 flex items-center justify-between">
    <div>
        <a href="manage_admissions.php" class="text-sm text-slate-500 hover:text-gold flex items-center gap-1 mb-2">
            <span class="material-symbols-outlined text-sm">arrow_back</span>Back to all applications
        </a>
        <h1 class="text-2xl font-bold text-slate-900"><?php echo htmlspecialchars($view_app['full_name']); ?></h1>
        <p class="text-slate-500 font-mono text-sm"><?php echo htmlspecialchars($view_app['application_no']); ?></p>
    </div>
    <?php
    $sc = ['Pending'=>'bg-slate-100 text-slate-700','Under Review'=>'bg-blue-100 text-blue-700','Shortlisted'=>'bg-amber-100 text-amber-700','Admitted'=>'bg-green-100 text-green-700','Rejected'=>'bg-red-100 text-red-700','Enrolled'=>'bg-primary/10 text-primary'];
    $cc = $sc[$view_app['status']] ?? 'bg-slate-100 text-slate-700';
    ?>
    <span class="px-4 py-2 rounded-full font-bold text-sm <?php echo $cc; ?>"><?php echo $view_app['status']; ?></span>
</div>

<?php if ($success): ?>
<div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl text-green-800 text-sm flex gap-2">
    <span class="material-symbols-outlined text-green-600">check_circle</span><?php echo $success; ?>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-red-800 text-sm flex gap-2">
    <span class="material-symbols-outlined text-red-600">error</span><?php echo $error; ?>
</div>
<?php endif; ?>

<div class="grid lg:grid-cols-3 gap-6">

    <!-- Application Details -->
    <div class="lg:col-span-2 space-y-5">
        <?php
        $sections = [
            'Personal Info' => [
                'Full Name'=>$view_app['full_name'],'Gender'=>$view_app['gender'],
                'Date of Birth'=>$view_app['date_of_birth'],'Age'=>$view_app['age'],
                'State of Origin'=>$view_app['state_of_origin'],'LGA'=>$view_app['lga'],
                'Nationality'=>$view_app['nationality'],'Religion'=>$view_app['religion'],
                'Address'=>$view_app['home_address'],'Phone'=>$view_app['phone_number'],
            ],
            'Parent / Guardian' => [
                'Father'=>$view_app['father_name'].' '.$view_app['father_phone'],
                'Mother'=>$view_app['mother_name'].' '.$view_app['mother_phone'],
                'Guardian'=>$view_app['guardian_name'],
                'Email'=>$view_app['parent_email'],
            ],
            'Academic' => [
                'Grade Applying'=>$view_app['grade_applying_for'],
                'Session'=>$view_app['session_applying'],
                'Last School'=>$view_app['last_school'],
                'Class Completed'=>$view_app['class_completed'],
                'Year'=>$view_app['year_completed'],
            ],
            'Health' => [
                'Medical Condition'=>$view_app['has_medical_condition'] ? 'Yes — '.$view_app['medical_condition_details'] : 'No',
                'Allergies'=>$view_app['has_allergies'] ? 'Yes — '.$view_app['allergy_details'] : 'No',
                'Special Diet'=>$view_app['special_diet'] ? 'Yes — '.$view_app['special_diet_details'] : 'No',
                'Emergency Contact'=>$view_app['emergency_contact_name'].' '.$view_app['emergency_contact_phone'],
            ],
        ];
        foreach($sections as $title=>$fields): ?>
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-3 bg-slate-50 border-b border-slate-200">
                <h3 class="font-bold text-slate-800 text-sm"><?php echo $title; ?></h3>
            </div>
            <div class="p-5 grid md:grid-cols-2 gap-3 text-sm">
                <?php foreach($fields as $label=>$value): if(!trim($value)) continue; ?>
                <div>
                    <p class="text-xs text-slate-400 font-semibold uppercase"><?php echo $label; ?></p>
                    <p class="text-slate-800 font-medium mt-0.5"><?php echo htmlspecialchars($value); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Payment -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 text-sm">Payment</h3>
                <?php if (!$view_app['payment_verified'] && hasPermission('admin')): ?>
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="verify_payment">
                    <input type="hidden" name="app_id" value="<?php echo $view_app['id']; ?>">
                    <button type="submit" class="px-3 py-1 bg-primary text-white text-xs font-bold rounded-lg">✓ Verify Payment</button>
                </form>
                <?php elseif ($view_app['payment_verified']): ?>
                <span class="px-3 py-1 bg-gold/20 text-primary text-xs font-bold rounded-full">✓ Verified</span>
                <?php endif; ?>
            </div>
            <div class="p-5 grid md:grid-cols-3 gap-3 text-sm">
                <div><p class="text-xs text-slate-400 font-semibold uppercase">Amount</p><p class="font-bold text-primary">₦<?php echo number_format($view_app['payment_amount']); ?></p></div>
                <div><p class="text-xs text-slate-400 font-semibold uppercase">Bank</p><p class="font-medium text-slate-800"><?php echo htmlspecialchars($view_app['payment_bank']); ?></p></div>
                <div><p class="text-xs text-slate-400 font-semibold uppercase">Date</p><p class="font-medium text-slate-800"><?php echo $view_app['payment_date']; ?></p></div>
                <?php if ($view_app['payment_proof_file']): ?>
                <div class="md:col-span-3">
                    <a href="../uploads/applications/<?php echo htmlspecialchars($view_app['payment_proof_file']); ?>" target="_blank"
                        class="inline-flex items-center gap-2 text-primary hover:underline text-sm font-semibold">
                        <span class="material-symbols-outlined text-sm">receipt</span>View Payment Proof
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right: Actions Panel -->
    <div class="space-y-5">

        <!-- Update Status -->
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-bold text-slate-800 mb-4">Update Status</h3>
            <form method="POST" class="space-y-3">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="app_id" value="<?php echo $view_app['id']; ?>">
                <div>
                    <label class="text-xs font-semibold text-slate-600 mb-1 block">Status</label>
                    <select name="status" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold">
                        <?php foreach(['Under Review','Shortlisted','Admitted','Rejected'] as $st): ?>
                        <option <?php echo $view_app['status']===$st?'selected':''; ?>><?php echo $st; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 mb-1 block">Exam Number</label>
                    <input type="text" name="exam_no" value="<?php echo htmlspecialchars($view_app['exam_no'] ?? ''); ?>"
                        class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold" placeholder="e.g. ENT-001">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 mb-1 block">Admin Notes</label>
                    <textarea name="admin_notes" rows="3"
                        class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"
                        placeholder="Internal notes..."><?php echo htmlspecialchars($view_app['admin_notes'] ?? ''); ?></textarea>
                </div>
                <button type="submit" class="w-full bg-primary text-white py-2.5 rounded-xl font-bold text-sm hover:bg-primary/90">
                    Save Status
                </button>
            </form>
        </div>

        <!-- Enrol -->
        <?php if (hasPermission('admin')): ?>
        <div class="bg-gold/5 border border-gold/30 rounded-xl p-5">
            <h3 class="font-bold text-primary mb-1">Enrol This Student</h3>
            <p class="text-xs text-slate-600 mb-4">This will create a student record and assign them to a class.</p>
            <form method="POST" class="space-y-3">
                <input type="hidden" name="action" value="enrol">
                <input type="hidden" name="app_id" value="<?php echo $view_app['id']; ?>">
                <div>
                    <label class="text-xs font-semibold text-slate-600 mb-1 block">Assign to Class</label>
                    <select name="class_id" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold">
                        <option value="">Select Class</option>
                        <?php $classes->data_seek(0); while($cls=$classes->fetch_assoc()): ?>
                        <option value="<?php echo $cls['id']; ?>"><?php echo htmlspecialchars($cls['class_name'].' '.$cls['arm']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 mb-1 block">Student Type</label>
                    <select name="student_type" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold px-3 py-2">
                        <option value="Boarding" selected>Boarding</option>
                        <option value="Day">Day</option>
                    </select>
                </div>
                <button type="submit" onclick="return confirm('Enrol this applicant? Their full application data will be copied to the student record.')"
                    class="w-full bg-gold text-primary py-2.5 rounded-xl font-bold text-sm hover:bg-gold/90 transition-all">
                    <span class="material-symbols-outlined text-sm align-middle mr-1">person_add</span>Enrol Student
                </button>
            </form>
        </div>
        <?php elseif ($view_app['enrolled_as_student_id']): ?>
        <div class="bg-primary/5 border border-primary/20 rounded-xl p-5 text-center">
            <span class="material-symbols-outlined text-primary text-3xl block mb-2">verified</span>
            <p class="font-bold text-primary text-sm">Enrolled</p>
            <a href="view_student.php?id=<?php echo $view_app['enrolled_as_student_id']; ?>"
                class="text-xs text-gold hover:underline mt-1 block">View Student Record →</a>
        </div>
        <?php endif; ?>

        <!-- Application Dates -->
        <div class="bg-white rounded-xl border border-slate-200 p-5 text-xs text-slate-500 space-y-1.5">
            <p><strong>Applied:</strong> <?php echo date('d M Y g:ia', strtotime($view_app['created_at'])); ?></p>
            <?php if ($view_app['reviewed_at']): ?><p><strong>Reviewed:</strong> <?php echo date('d M Y', strtotime($view_app['reviewed_at'])); ?></p><?php endif; ?>
            <?php if ($view_app['admitted_at']): ?><p><strong>Admitted:</strong> <?php echo date('d M Y', strtotime($view_app['admitted_at'])); ?></p><?php endif; ?>
            <?php if ($view_app['enrolled_at']): ?><p><strong>Enrolled:</strong> <?php echo date('d M Y', strtotime($view_app['enrolled_at'])); ?></p><?php endif; ?>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ══════════════════════════════════════════════
     APPLICATION LIST
══════════════════════════════════════════════ -->
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Admissions</h1>
        <p class="text-slate-500 text-sm mt-1"><?php echo $total; ?> total applications</p>
    </div>
    <a href="../admissions.php" target="_blank"
        class="inline-flex items-center gap-2 px-4 py-2 bg-gold text-primary text-sm font-bold rounded-xl hover:bg-gold/90">
        <span class="material-symbols-outlined text-sm">open_in_new</span>View Public Form
    </a>
</div>

<?php if ($success): ?>
<div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl text-green-800 text-sm"><?php echo $success; ?></div>
<?php endif; ?>

<!-- Status Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 mb-6">
    <?php
    $statuses = ['Pending','Under Review','Shortlisted','Admitted','Rejected','Enrolled'];
    $sc = ['Pending'=>'border-slate-200','Under Review'=>'border-blue-200','Shortlisted'=>'border-amber-200','Admitted'=>'border-green-200','Rejected'=>'border-red-200','Enrolled'=>'border-primary/30'];
    foreach($statuses as $st):
        $cnt = $counts[$st] ?? 0;
        $active = $status_filter === $st;
    ?>
    <a href="?status=<?php echo urlencode($st); ?>"
        class="bg-white rounded-xl border-2 <?php echo $active ? 'border-gold bg-gold/5' : $sc[$st]; ?> p-3 text-center hover:border-gold transition-all">
        <p class="text-2xl font-black text-primary"><?php echo $cnt; ?></p>
        <p class="text-xs text-slate-500 font-semibold mt-0.5"><?php echo $st; ?></p>
    </a>
    <?php endforeach; ?>
</div>

<!-- Search & Filter -->
<form method="GET" class="bg-white rounded-xl border border-slate-200 p-4 mb-5 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-48">
        <label class="text-xs font-semibold text-slate-600 mb-1 block">Search</label>
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
            placeholder="Name, application no, phone…"
            class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold px-3 py-2">
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-600 mb-1 block">Status</label>
        <select name="status" class="border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold px-3 py-2">
            <option value="">All Statuses</option>
            <?php foreach($statuses as $st): ?>
            <option <?php echo $status_filter===$st?'selected':''; ?>><?php echo $st; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="px-5 py-2 bg-primary text-white text-sm font-bold rounded-xl hover:bg-primary/90">Filter</button>
    <a href="manage_admissions.php" class="px-4 py-2 text-sm text-slate-500 hover:text-gold">Reset</a>
</form>

<!-- Table -->
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200 text-xs">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-500 uppercase">Applicant</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-500 uppercase">App No</th>
                    <th class="px-4 py-3 text-center font-semibold text-slate-500 uppercase">Grade</th>
                    <th class="px-4 py-3 text-center font-semibold text-slate-500 uppercase">Payment</th>
                    <th class="px-4 py-3 text-center font-semibold text-slate-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-center font-semibold text-slate-500 uppercase">Date</th>
                    <th class="px-4 py-3 text-center font-semibold text-slate-500 uppercase">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if ($applications->num_rows === 0): ?>
                <tr><td colspan="7" class="px-4 py-12 text-center text-slate-400">
                    <span class="material-symbols-outlined text-4xl block mb-2">inbox</span>No applications found.
                </td></tr>
                <?php endif; ?>
                <?php while($app = $applications->fetch_assoc()):
                    $sc2 = ['Pending'=>'bg-slate-100 text-slate-600','Under Review'=>'bg-blue-100 text-blue-700','Shortlisted'=>'bg-amber-100 text-amber-700','Admitted'=>'bg-green-100 text-green-700','Rejected'=>'bg-red-100 text-red-700','Enrolled'=>'bg-primary/10 text-primary'];
                    $cc2 = $sc2[$app['status']] ?? 'bg-slate-100 text-slate-600';
                ?>
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center text-primary font-bold text-xs">
                                <?php echo strtoupper(substr($app['full_name'],0,2)); ?>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-900"><?php echo htmlspecialchars($app['full_name']); ?></p>
                                <p class="text-xs text-slate-400"><?php echo htmlspecialchars($app['phone_number']); ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-slate-600"><?php echo htmlspecialchars($app['application_no']); ?></td>
                    <td class="px-4 py-3 text-center text-xs font-semibold text-slate-700"><?php echo htmlspecialchars($app['grade_applying_for']); ?></td>
                    <td class="px-4 py-3 text-center">
                        <?php if ($app['payment_verified']): ?>
                        <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-bold rounded-full">✓ Verified</span>
                        <?php elseif ($app['payment_proof_file']): ?>
                        <span class="px-2 py-0.5 bg-amber-100 text-amber-700 text-xs font-bold rounded-full">Uploaded</span>
                        <?php else: ?>
                        <span class="px-2 py-0.5 bg-red-100 text-red-600 text-xs font-bold rounded-full">No Proof</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $cc2; ?>"><?php echo $app['status']; ?></span>
                    </td>
                    <td class="px-4 py-3 text-center text-xs text-slate-400"><?php echo date('d M Y', strtotime($app['created_at'])); ?></td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="?view=<?php echo $app['id']; ?>"
                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-primary text-white text-xs font-semibold rounded-lg hover:bg-primary/90">
                                <span class="material-symbols-outlined text-xs">visibility</span>Review
                            </a>
                            <button onclick="openEnrolModal(<?php echo $app['id']; ?>, '<?php echo htmlspecialchars(addslashes($app['full_name'])); ?>', '<?php echo htmlspecialchars(addslashes($app['application_no'])); ?>')"
                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-gold text-primary text-xs font-bold rounded-lg hover:bg-gold/90 transition-all">
                                <span class="material-symbols-outlined text-xs">person_add</span>Enrol
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

</main>
</div>
</div>

<!-- ══ QUICK ENROL MODAL ══ -->
<div id="enrolModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
    <div class="bg-primary rounded-t-2xl px-6 py-5 flex items-center justify-between">
        <div>
            <h2 class="text-white font-bold text-lg">Enrol Student</h2>
            <p class="text-slate-300 text-xs mt-0.5" id="enrolModalName">—</p>
        </div>
        <button onclick="document.getElementById('enrolModal').classList.add('hidden')" class="text-white/60 hover:text-white">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>
    <form method="POST" class="p-6 space-y-4">
        <input type="hidden" name="action" value="enrol">
        <input type="hidden" name="app_id" id="enrolAppId">

        <!-- App no display -->
        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-center">
            <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider mb-1">Application Number</p>
            <p class="font-black font-mono text-primary text-lg" id="enrolAppNo">—</p>
        </div>

        <div>
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1 block">Assign to Class <span class="text-red-500">*</span></label>
            <select name="class_id" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold px-3 py-2.5">
                <option value="">Select Class</option>
                <?php $classes->data_seek(0); while($cls=$classes->fetch_assoc()): ?>
                <option value="<?php echo $cls['id']; ?>"><?php echo htmlspecialchars($cls['class_name'].' '.$cls['arm']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div>
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1 block">Student Type</label>
            <div class="flex gap-3">
                <label class="flex-1 flex items-center justify-center gap-2 border-2 border-slate-200 rounded-xl py-3 cursor-pointer hover:border-gold transition-all has-[:checked]:border-gold has-[:checked]:bg-gold/5">
                    <input type="radio" name="student_type" value="Boarding" checked class="text-gold">
                    <span class="font-semibold text-sm text-slate-700">Boarding</span>
                </label>
                <label class="flex-1 flex items-center justify-center gap-2 border-2 border-slate-200 rounded-xl py-3 cursor-pointer hover:border-gold transition-all has-[:checked]:border-gold has-[:checked]:bg-gold/5">
                    <input type="radio" name="student_type" value="Day" class="text-gold">
                    <span class="font-semibold text-sm text-slate-700">Day</span>
                </label>
            </div>
        </div>

        <p class="text-xs text-slate-400 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-sm text-gold">info</span>
            All application data (personal info, health, parents) will be copied to the student record automatically.
        </p>

        <div class="flex gap-3 pt-2">
            <button type="submit"
                onclick="return confirm('Enrol this applicant as a student? This cannot be undone.')"
                class="flex-1 bg-gold text-primary py-3 rounded-xl font-bold text-sm hover:bg-gold/90 transition-all flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-sm">person_add</span>Enrol Student
            </button>
            <button type="button" onclick="document.getElementById('enrolModal').classList.add('hidden')"
                class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl font-bold text-sm hover:bg-slate-200 transition-all">Cancel</button>
        </div>
    </form>
</div>
</div>

<script>
function openEnrolModal(appId, name, appNo) {
    document.getElementById('enrolAppId').value  = appId;
    document.getElementById('enrolModalName').textContent = name;
    document.getElementById('enrolAppNo').textContent     = appNo;
    document.getElementById('enrolModal').classList.remove('hidden');
}
document.getElementById('enrolModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
});
</script>
</body>
</html>