<?php
$page_title = "Apply Now - Goodness Omogo Leadership Academy";
require_once 'config/database.php';
require_once 'includes/email.php';   // ← PHPMailer helper

function getSetting($conn, $key, $default = '') {
    $r = $conn->query("SELECT setting_value FROM school_settings WHERE setting_key='".mysqli_real_escape_string($conn,$key)."' LIMIT 1");
    $row = $r ? $r->fetch_assoc() : null;
    return $row ? $row['setting_value'] : $default;
}

$admissions_open      = getSetting($conn, 'admissions_open', '1');
$current_session      = getSetting($conn, 'admissions_session', '2025/2026');
$app_fee_amount       = getSetting($conn, 'application_fee_amount', '5000');
$app_fee_bank         = getSetting($conn, 'application_fee_bank', '');
$app_fee_account_no   = getSetting($conn, 'application_fee_account_no', '');
$app_fee_account_name = getSetting($conn, 'application_fee_account_name', '');
$exam_date            = getSetting($conn, 'entrance_exam_date', '');
$exam_venue           = getSetting($conn, 'entrance_exam_venue', '');

$success = '';
$error   = '';
$app_no  = '';

// ── Handle final submission ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application'])) {
    $payment_proof_file = '';

    if (!empty($_FILES['payment_proof']['name'])) {
        $ext = strtolower(pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','pdf'])) {
            $error = 'Payment proof must be JPG, PNG or PDF.';
        } elseif ($_FILES['payment_proof']['size'] > 5 * 1024 * 1024) {
            $error = 'Payment proof must be under 5MB.';
        } else {
            $year  = date('Y');
            $count = $conn->query("SELECT COUNT(*) as c FROM admissions_applications WHERE session_applying='".mysqli_real_escape_string($conn,$current_session)."'")->fetch_assoc()['c'];
            $app_no = 'GOLA-APP-'.$year.'-'.str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            $upload_dir = __DIR__.'/uploads/applications/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);
            $payment_proof_file = $app_no.'-proof.'.$ext;
            move_uploaded_file($_FILES['payment_proof']['tmp_name'], $upload_dir.$payment_proof_file);
        }
    } else {
        $error = 'Please upload proof of payment.';
    }

    if (!$error) {
        $s = $_POST;
        if (empty($app_no)) {
            $year  = date('Y');
            $count = $conn->query("SELECT COUNT(*) as c FROM admissions_applications WHERE session_applying='".mysqli_real_escape_string($conn,$current_session)."'")->fetch_assoc()['c'];
            $app_no = 'GOLA-APP-'.$year.'-'.str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        }
        $gender  = ($s['gender'] ?? '') === 'Male' ? 'Male' : 'Female';
        $dob_fmt = date('Y-m-d', strtotime(($s['dob_year']??date('Y')).'-'.($s['dob_month']??1).'-'.($s['dob_day']??1)));

        $f = function($v) use ($conn) { return mysqli_real_escape_string($conn, $v ?? ''); };

        $sql = "INSERT INTO admissions_applications (
            application_no,full_name,gender,date_of_birth,age,state_of_origin,lga,nationality,religion,home_address,phone_number,
            father_name,father_phone,mother_name,mother_phone,guardian_name,guardian_relationship,guardian_phone,parent_email,
            last_school,class_completed,year_completed,reason_for_leaving,has_last_result,has_birth_cert,has_passport_photos,
            emergency_contact_name,emergency_contact_phone,has_medical_condition,medical_condition_details,
            has_allergies,allergy_details,special_diet,special_diet_details,special_care_needs,family_doctor,family_doctor_phone,
            sports_football,sports_volleyball,sports_basketball,sports_badminton,sports_long_jump,sports_triple_jump,
            sports_shot_put,sports_discus,sports_javelin,sports_table_tennis,sports_handball,
            sports_participated_before,sports_level,sports_awards,
            interested_in_leadership,held_leadership_before,leadership_details,
            interest_public_speaking,interest_business,interest_agriculture,interest_ict,interest_tailoring,interest_arts_music,interest_debate,
            interest_other,future_career,ever_suspended,suspension_details,special_talents,
            grade_applying_for,session_applying,how_heard,payment_amount,payment_bank,payment_date,payment_proof_file
        ) VALUES (
            '{$f($app_no)}','{$f($s['full_name'])}','$gender','$dob_fmt',".intval($s['age']??0).",
            '{$f($s['state_of_origin'])}','{$f($s['lga'])}','{$f($s['nationality']??'Nigerian')}','{$f($s['religion'])}',
            '{$f($s['home_address'])}','{$f($s['phone_number'])}',
            '{$f($s['father_name'])}','{$f($s['father_phone'])}','{$f($s['mother_name'])}','{$f($s['mother_phone'])}',
            '{$f($s['guardian_name'])}','{$f($s['guardian_relationship'])}','{$f($s['guardian_phone'])}','{$f($s['parent_email'])}',
            '{$f($s['last_school'])}','{$f($s['class_completed'])}',".intval($s['year_completed']??date('Y')).",'{$f($s['reason_for_leaving'])}',
            ".(isset($s['has_last_result'])?1:0).",".(isset($s['has_birth_cert'])?1:0).",".(isset($s['has_passport_photos'])?1:0).",
            '{$f($s['emergency_contact_name'])}','{$f($s['emergency_contact_phone'])}',
            ".(isset($s['has_medical'])?1:0).",'{$f($s['medical_details'])}',
            ".(isset($s['has_allergies'])?1:0).",'{$f($s['allergy_details'])}',
            ".(isset($s['special_diet'])?1:0).",'{$f($s['special_diet_details'])}',
            '{$f($s['special_care_needs'])}','{$f($s['family_doctor'])}','{$f($s['family_doctor_phone'])}',
            ".(isset($s['sp_football'])?1:0).",".(isset($s['sp_volleyball'])?1:0).",".(isset($s['sp_basketball'])?1:0).",
            ".(isset($s['sp_badminton'])?1:0).",".(isset($s['sp_long_jump'])?1:0).",".(isset($s['sp_triple_jump'])?1:0).",
            ".(isset($s['sp_shot_put'])?1:0).",".(isset($s['sp_discus'])?1:0).",".(isset($s['sp_javelin'])?1:0).",
            ".(isset($s['sp_table_tennis'])?1:0).",".(isset($s['sp_handball'])?1:0).",
            ".(isset($s['sports_participated'])?1:0).",'{$f($s['sports_level'])}','{$f($s['sports_awards'])}',
            ".(isset($s['lead_interested'])?1:0).",".(isset($s['lead_held_before'])?1:0).",'{$f($s['lead_details'])}',
            ".(isset($s['int_speaking'])?1:0).",".(isset($s['int_business'])?1:0).",".(isset($s['int_agriculture'])?1:0).",
            ".(isset($s['int_ict'])?1:0).",".(isset($s['int_tailoring'])?1:0).",".(isset($s['int_arts'])?1:0).",
            ".(isset($s['int_debate'])?1:0).",'{$f($s['int_other'])}','{$f($s['future_career'])}',
            ".(isset($s['ever_suspended'])?1:0).",'{$f($s['suspension_details'])}','{$f($s['special_talents'])}',
            '{$f($s['grade_applying'])}','{$f($current_session)}','{$f($s['how_heard'])}',
            ".floatval($s['payment_amount']??$app_fee_amount).",'{$f($s['payment_bank'])}',
            ".(!empty($s['payment_date']) ? "'".$f($s['payment_date'])."'" : 'NULL').",
            '{$f($payment_proof_file)}'
        )";

        if ($conn->query($sql)) {
            $success = $app_no;

            // Send confirmation email to applicant
            // Get their email from POST (parent_email field)
            $applicant_email = trim($s['parent_email'] ?? '');
            if (filter_var($applicant_email, FILTER_VALIDATE_EMAIL)) {
                sendApplicationConfirmation([
                    'full_name'      => $s['full_name']    ?? '',
                    'email'          => $applicant_email,
                    'application_no' => $app_no,
                    'grade_applying' => $s['grade_applying'] ?? '',
                    'session'        => $current_session,
                    'exam_date'      => $exam_date,
                    'exam_venue'     => $exam_venue,
                ]);
            }
        } else {
            $error = 'Submission failed. Please try again. ('.$conn->error.')';
        }
    }
}

include 'includes/header.php';
?>

<style>
/* ── Step system ── */
.form-step { display: none; }
.form-step.active { display: block; }

/* ── Stepper ── */
.step-item { flex: 1; }
.step-item .step-dot {
    width: 2.25rem; height: 2.25rem;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: .8rem;
    border: 2.5px solid #e2e8f0;
    background: white; color: #94a3b8;
    transition: all .3s; position: relative; z-index: 1;
}
.step-item.done .step-dot   { background: #C5A059; border-color: #C5A059; color: white; }
.step-item.active .step-dot { background: #0A2E4D; border-color: #0A2E4D; color: white; box-shadow: 0 0 0 4px rgba(10,46,77,.15); }
.step-item .step-label { font-size: .65rem; font-weight: 600; color: #94a3b8; margin-top: .35rem; text-align: center; }
.step-item.active .step-label, .step-item.done .step-label { color: #0A2E4D; }
.step-connector { flex: 1; height: 2px; background: #e2e8f0; margin-top: 1.1rem; transition: background .3s; }
.step-connector.done { background: #C5A059; }

/* ── Card inputs ── */
.card-check { display: none; }
.card-check-label {
    display: flex; align-items: center; gap: .6rem;
    padding: .75rem 1rem; border-radius: .75rem;
    border: 2px solid #e2e8f0; background: #f8fafc;
    cursor: pointer; transition: all .2s; font-size: .85rem; font-weight: 600; color: #475569;
}
.card-check:checked + .card-check-label { border-color: #C5A059; background: rgba(197,160,89,.06); color: #0A2E4D; }
.card-radio-label {
    display: flex; align-items: center; justify-content: center; gap: .5rem;
    padding: .8rem 1rem; border-radius: .75rem;
    border: 2px solid #e2e8f0; background: white;
    cursor: pointer; transition: all .2s; font-size: .9rem; font-weight: 700; color: #475569; flex: 1;
}
.card-radio:checked + .card-radio-label { border-color: #C5A059; background: rgba(197,160,89,.07); color: #0A2E4D; }

/* ── Field ── */
.field-input {
    width: 100%; padding: .75rem 1rem;
    border: 2px solid #e2e8f0; border-radius: .75rem;
    background: white; font-size: .9rem; color: #0f172a;
    transition: border-color .2s, box-shadow .2s; outline: none;
}
.field-input:focus { border-color: #C5A059; box-shadow: 0 0 0 3px rgba(197,160,89,.12); }
.field-label { display: block; font-size: .8rem; font-weight: 700; color: #475569; margin-bottom: .4rem; text-transform: uppercase; letter-spacing: .03em; }
.field-req { color: #ef4444; }

@media (prefers-color-scheme: dark) {
    .field-input { background: #1e293b; border-color: #334155; color: white; }
    .card-check-label { background: #1e293b; border-color: #334155; color: #cbd5e1; }
    .card-radio-label { background: #1e293b; border-color: #334155; color: #cbd5e1; }
}
</style>

<!-- Hero -->
<section class="py-12 bg-gradient-to-br from-primary via-[#0A3556] to-slate-800 text-white">
    <div class="max-w-3xl mx-auto px-4 text-center">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-gold/20 border border-gold/30 rounded-full text-gold font-semibold text-xs uppercase tracking-widest mb-5">
            <span class="w-2 h-2 rounded-full bg-gold animate-pulse"></span>
            Admissions <?php echo htmlspecialchars($current_session); ?>
        </div>
        <h1 class="text-4xl lg:text-5xl font-display font-black mb-3">Boarding Entrance Application</h1>
        <p class="text-slate-300 text-base max-w-xl mx-auto">Complete all 8 sections carefully. You can navigate back and forth between sections before final submission.</p>
    </div>
</section>

<?php if (!$admissions_open): ?>
<section class="py-24 bg-slate-50 dark:bg-slate-900 text-center">
    <div class="max-w-lg mx-auto px-4">
        <span class="material-symbols-outlined text-7xl text-slate-200 block mb-4">lock</span>
        <h2 class="text-2xl font-bold text-slate-800 dark:text-white mb-3">Admissions Currently Closed</h2>
        <p class="text-slate-500 mb-6">Applications are not being accepted right now. Please contact the school office for more details.</p>
        <a href="contact.php" class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-xl font-semibold hover:bg-primary/90">
            <span class="material-symbols-outlined text-sm">call</span>Contact Us
        </a>
    </div>
</section>

<?php elseif ($success): ?>
<!-- ══ SUCCESS ══ -->
<section class="py-20 bg-slate-50 dark:bg-slate-900">
    <div class="max-w-2xl mx-auto px-4 text-center">
        <div class="w-24 h-24 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-6 shadow-xl">
            <span class="material-symbols-outlined text-6xl text-green-600">task_alt</span>
        </div>
        <h2 class="text-4xl font-display font-black text-slate-900 dark:text-white mb-3">Application Submitted!</h2>
        <p class="text-slate-500 dark:text-slate-400 mb-6 text-base">Your application has been received successfully. Please save your application number.</p>
        <div class="bg-white dark:bg-slate-800 rounded-2xl border-2 border-gold shadow-xl p-8 mb-6">
            <p class="text-xs text-slate-400 uppercase tracking-widest font-bold mb-2">Application Number</p>
            <p class="text-4xl font-black font-mono text-primary dark:text-gold tracking-wider"><?php echo htmlspecialchars($success); ?></p>
        </div>
        <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">Our admissions team will review and contact you within <strong>5 working days</strong>.</p>
        <?php if ($exam_date): ?>
        <div class="inline-flex items-center gap-3 mt-5 px-6 py-4 bg-gold/10 border border-gold/30 rounded-2xl">
            <span class="material-symbols-outlined text-gold">event</span>
            <div class="text-left">
                <p class="font-bold text-primary dark:text-gold text-sm">Next Entrance Exam</p>
                <p class="text-xs text-slate-500"><?php echo date('l, F j, Y', strtotime($exam_date)); ?><?php if ($exam_venue): ?> — <?php echo htmlspecialchars($exam_venue); ?><?php endif; ?></p>
            </div>
        </div>
        <?php endif; ?>
        <div class="mt-8 flex gap-3 justify-center">
            <a href="admissions.php" class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-xl font-semibold hover:bg-primary/90">
                <span class="material-symbols-outlined text-sm">arrow_back</span>Back to Admissions
            </a>
        </div>
    </div>
</section>

<?php else: ?>
<!-- ══ MULTI-STEP FORM ══ -->

<!-- Sticky stepper + payment info -->
<div class="sticky top-0 z-40 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700 shadow-sm">
    <!-- Payment banner -->
    <div class="bg-primary/95 text-white py-2 px-4">
        <div class="max-w-4xl mx-auto flex flex-wrap items-center gap-x-5 gap-y-1 text-xs">
            <span class="font-bold text-gold flex items-center gap-1.5"><span class="material-symbols-outlined text-sm">payments</span>Fee: ₦<?php echo number_format(floatval($app_fee_amount)); ?></span>
            <?php if ($app_fee_bank): ?><span><span class="text-slate-300">Bank:</span> <strong><?php echo htmlspecialchars($app_fee_bank); ?></strong></span><?php endif; ?>
            <?php if ($app_fee_account_no): ?><span><span class="text-slate-300">Account:</span> <strong><?php echo htmlspecialchars($app_fee_account_no); ?></strong></span><?php endif; ?>
            <?php if ($app_fee_account_name): ?><span><span class="text-slate-300">Name:</span> <strong><?php echo htmlspecialchars($app_fee_account_name); ?></strong></span><?php endif; ?>
        </div>
    </div>
    <!-- Step indicators -->
    <div class="max-w-4xl mx-auto px-4 py-3">
        <div class="flex items-start" id="stepperBar">
            <?php
            $steps = ['Personal','Parents','Academic','Health','Sports','Leadership','Character','Payment'];
            foreach ($steps as $i => $label):
                $isLast = ($i === count($steps)-1);
            ?>
            <div class="step-item <?php echo $i===0?'active':''; ?>" data-step="<?php echo $i; ?>">
                <div class="flex flex-col items-center">
                    <div class="step-dot"><?php echo $i+1; ?></div>
                    <span class="step-label hidden sm:block"><?php echo $label; ?></span>
                </div>
            </div>
            <?php if (!$isLast): ?>
            <div class="step-connector" data-connector="<?php echo $i; ?>"></div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if ($error): ?>
<div class="max-w-4xl mx-auto px-4 pt-5">
    <div class="p-4 bg-red-50 border border-red-200 rounded-2xl flex gap-3 items-start">
        <span class="material-symbols-outlined text-red-500 flex-shrink-0">error</span>
        <p class="text-red-700 text-sm"><?php echo htmlspecialchars($error); ?></p>
    </div>
</div>
<?php endif; ?>

<section class="py-8 bg-slate-50 dark:bg-slate-900 min-h-screen">
<div class="max-w-4xl mx-auto px-4">

<form method="POST" enctype="multipart/form-data" id="appForm" novalidate>
<input type="hidden" name="submit_application" value="1">

<?php
$ic = 'class="field-input dark:bg-slate-800 dark:border-slate-600 dark:text-white"';
$sl = 'class="field-input dark:bg-slate-800 dark:border-slate-600 dark:text-white"';

function fld($n,$l,$t='text',$req=false,$ph='',$extra='') {
    $r  = $req ? '<span class="field-req">*</span>' : '';
    $rq = $req ? 'required' : '';
    return "<div>
        <label class='field-label'>$l $r</label>
        <input type='$t' name='$n' $rq placeholder='$ph' $extra
            class='field-input dark:bg-slate-800 dark:border-slate-600 dark:text-white'>
    </div>";
}

function shead($step, $icon, $color, $title, $sub) {
    return "<div class='flex items-center gap-4 p-5 rounded-2xl mb-5 text-white' style='background:linear-gradient(135deg,$color)'>
        <div class='w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0'>
            <span class='material-symbols-outlined text-2xl'>$icon</span>
        </div>
        <div class='flex-1'>
            <h2 class='font-black text-xl'>$title</h2>
            <p class='text-white/70 text-xs mt-0.5'>$sub</p>
        </div>
        <span class='text-white/40 font-mono text-sm hidden sm:block'>Step ".($step+1)."/8</span>
    </div>";
}
?>

<!-- ══════ STEP 0 — Personal Info ══════ -->
<div class="form-step active" data-step="0">
    <?php echo shead(0,'person','#0A2E4D, #1e3a5f','Section A — Personal Information','Student details as on official documents'); ?>
    <div class="grid md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
            <label class="field-label">Full Name (Surname First) <span class="field-req">*</span></label>
            <input type="text" name="full_name" required placeholder="e.g. OMOGO Emmanuel Chukwuemeka" class="field-input dark:bg-slate-800 dark:border-slate-600 dark:text-white">
        </div>
        <div class="md:col-span-2">
            <label class="field-label">Gender <span class="field-req">*</span></label>
            <div class="flex gap-3">
                <?php foreach(['Male','Female'] as $g): ?>
                <input type="radio" name="gender" value="<?php echo $g; ?>" id="gender_<?php echo $g; ?>" class="card-radio hidden" required>
                <label for="gender_<?php echo $g; ?>" class="card-radio-label"><?php echo $g; ?></label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="md:col-span-2">
            <label class="field-label">Date of Birth <span class="field-req">*</span></label>
            <div class="grid grid-cols-3 gap-3">
                <select name="dob_day" required class="field-input dark:bg-slate-800 dark:border-slate-600 dark:text-white">
                    <option value="">Day</option><?php for($d=1;$d<=31;$d++) echo "<option>$d</option>"; ?>
                </select>
                <select name="dob_month" required class="field-input dark:bg-slate-800 dark:border-slate-600 dark:text-white">
                    <option value="">Month</option>
                    <?php foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $i=>$m) echo '<option value="'.($i+1).'">'.$m.'</option>'; ?>
                </select>
                <select name="dob_year" required class="field-input dark:bg-slate-800 dark:border-slate-600 dark:text-white">
                    <option value="">Year</option><?php for($y=date('Y')-5;$y>=date('Y')-25;$y--) echo "<option>$y</option>"; ?>
                </select>
            </div>
        </div>
        <?php echo fld('age','Age','number',false,'e.g. 14'); ?>
        <?php echo fld('state_of_origin','State of Origin','text',true,'e.g. Ebonyi'); ?>
        <?php echo fld('lga','Local Government Area','text',false,'LGA'); ?>
        <?php echo fld('nationality','Nationality','text',false,'Nigerian','value="Nigerian"'); ?>
        <?php echo fld('religion','Religion','text',false,'e.g. Christianity'); ?>
        <div class="md:col-span-2"><?php echo fld('home_address','Home Address','text',false,'Full residential address'); ?></div>
        <?php echo fld('phone_number','Phone Number','tel',false,'e.g. 08012345678'); ?>
        <div>
            <label class="field-label">Grade Applying For <span class="field-req">*</span></label>
            <select name="grade_applying" required class="field-input dark:bg-slate-800 dark:border-slate-600 dark:text-white">
                <option value="">Select Class</option>
                <?php foreach(['JSS 1','JSS 2','JSS 3','SS 1','SS 2','SS 3'] as $g): ?><option><?php echo $g; ?></option><?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<!-- ══════ STEP 1 — Parents ══════ -->
<div class="form-step" data-step="1">
    <?php echo shead(1,'family_restroom','#1e293b, #334155','Section B — Parent / Guardian Information','Emergency and guardian contact details'); ?>
    <div class="grid md:grid-cols-2 gap-4">
        <?php echo fld('father_name',"Father's Name"); ?>
        <?php echo fld('father_phone',"Father's Phone",'tel'); ?>
        <?php echo fld('mother_name',"Mother's Name"); ?>
        <?php echo fld('mother_phone',"Mother's Phone",'tel'); ?>
        <?php echo fld('guardian_name',"Guardian's Name (if applicable)"); ?>
        <?php echo fld('guardian_relationship','Relationship to Student'); ?>
        <?php echo fld('guardian_phone',"Guardian's Phone",'tel'); ?>
        <?php echo fld('parent_email','Parent / Guardian Email','email',false,'parent@example.com'); ?>
    </div>
</div>

<!-- ══════ STEP 2 — Academic ══════ -->
<div class="form-step" data-step="2">
    <?php echo shead(2,'school','#1d4ed8, #1e40af','Section C — Academic Background','Previous educational history'); ?>
    <div class="grid md:grid-cols-2 gap-4">
        <div class="md:col-span-2"><?php echo fld('last_school','Last School Attended'); ?></div>
        <?php echo fld('class_completed','Class Completed','text',false,'e.g. Primary 6 / JSS 3'); ?>
        <div>
            <label class="field-label">Year Completed</label>
            <select name="year_completed" class="field-input dark:bg-slate-800 dark:border-slate-600 dark:text-white">
                <?php for($y=date('Y');$y>=2010;$y--) echo "<option>$y</option>"; ?>
            </select>
        </div>
        <div class="md:col-span-2"><?php echo fld('reason_for_leaving','Reason for Leaving'); ?></div>
        <div class="md:col-span-2">
            <label class="field-label">Documents you are submitting</label>
            <div class="grid sm:grid-cols-3 gap-3 mt-1">
                <?php foreach([['has_last_result','description','Last School Result'],['has_birth_cert','badge','Birth Certificate'],['has_passport_photos','photo_camera','2 Passport Photos']] as [$n,$icon,$l]): ?>
                <input type="checkbox" name="<?php echo $n; ?>" id="<?php echo $n; ?>" class="card-check">
                <label for="<?php echo $n; ?>" class="card-check-label">
                    <span class="material-symbols-outlined text-gold text-lg"><?php echo $icon; ?></span><?php echo $l; ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ══════ STEP 3 — Health ══════ -->
<div class="form-step" data-step="3">
    <?php echo shead(3,'health_and_safety','#15803d, #166534','Section D — Boarding & Health','Health and emergency information for boarding'); ?>
    <div class="grid md:grid-cols-2 gap-4">
        <?php echo fld('emergency_contact_name','Emergency Contact Name (not parent)'); ?>
        <?php echo fld('emergency_contact_phone','Emergency Contact Phone','tel'); ?>
        <?php foreach([
            ['has_medical','medical_row','Any medical condition?','medical_details','Specify the condition'],
            ['has_allergies','allergy_row','Any allergies?','allergy_details','Specify allergies'],
            ['special_diet','diet_row','Special diet required?','special_diet_details','Explain dietary needs'],
        ] as [$cb,$row,$lbl,$fn,$ph]): ?>
        <div class="md:col-span-2">
            <input type="checkbox" name="<?php echo $cb; ?>" id="<?php echo $cb; ?>" class="card-check toggle-detail" data-target="<?php echo $row; ?>">
            <label for="<?php echo $cb; ?>" class="card-check-label w-full"><?php echo $lbl; ?></label>
            <div id="<?php echo $row; ?>" class="hidden mt-2">
                <input type="text" name="<?php echo $fn; ?>" placeholder="<?php echo $ph; ?>" class="field-input dark:bg-slate-800 dark:border-slate-600 dark:text-white">
            </div>
        </div>
        <?php endforeach; ?>
        <div class="md:col-span-2"><?php echo fld('special_care_needs','Special Care Needs'); ?></div>
        <?php echo fld('family_doctor','Family Doctor (if any)'); ?>
        <?php echo fld('family_doctor_phone',"Doctor's Phone",'tel'); ?>
    </div>
</div>

<!-- ══════ STEP 4 — Sports ══════ -->
<div class="form-step" data-step="4">
    <?php echo shead(4,'sports_soccer','#c2410c, #9a3412','Section E — Sports & Physical Development','GOLA promotes fitness, teamwork and discipline through sport'); ?>
    <div class="space-y-5">
        <div>
            <label class="field-label">Sports of Interest</label>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-1">
                <?php foreach(['sp_football'=>'⚽ Football','sp_volleyball'=>'🏐 Volleyball','sp_basketball'=>'🏀 Basketball','sp_badminton'=>'🏸 Badminton','sp_long_jump'=>'🏃 Long Jump','sp_triple_jump'=>'🏃 Triple Jump','sp_shot_put'=>'🏋 Shot Put','sp_discus'=>'🥏 Discus','sp_javelin'=>'🎯 Javelin','sp_table_tennis'=>'🏓 Table Tennis','sp_handball'=>'🤾 Handball'] as $k=>$v): ?>
                <input type="checkbox" name="<?php echo $k; ?>" id="<?php echo $k; ?>" class="card-check">
                <label for="<?php echo $k; ?>" class="card-check-label"><?php echo $v; ?></label>
                <?php endforeach; ?>
            </div>
        </div>
        <div>
            <input type="checkbox" name="sports_participated" id="sports_participated" class="card-check toggle-detail" data-target="sports_detail">
            <label for="sports_participated" class="card-check-label w-full">Participated in competitive sports before?</label>
            <div id="sports_detail" class="hidden mt-3 grid md:grid-cols-2 gap-4">
                <div>
                    <label class="field-label">Level</label>
                    <select name="sports_level" class="field-input dark:bg-slate-800 dark:border-slate-600 dark:text-white">
                        <option value="">Select level</option>
                        <?php foreach(['School Level','Inter-School','State Level','National Level'] as $l): ?><option><?php echo $l; ?></option><?php endforeach; ?>
                    </select>
                </div>
                <?php echo fld('sports_awards','Awards / Achievements'); ?>
            </div>
        </div>
    </div>
</div>

<!-- ══════ STEP 5 — Leadership ══════ -->
<div class="form-step" data-step="5">
    <?php echo shead(5,'emoji_events','#7c3aed, #6d28d9','Section F — Leadership & Entrepreneurship','Interests, ambitions and leadership experience'); ?>
    <div class="space-y-5">
        <div class="grid md:grid-cols-2 gap-3">
            <input type="checkbox" name="lead_interested" id="lead_interested" class="card-check">
            <label for="lead_interested" class="card-check-label">Interested in leadership roles?</label>
            <input type="checkbox" name="lead_held_before" id="lead_held_before" class="card-check toggle-detail" data-target="lead_detail">
            <label for="lead_held_before" class="card-check-label">Held a leadership position before?</label>
        </div>
        <div id="lead_detail" class="hidden">
            <?php echo fld('lead_details','Specify role','text',false,'e.g. Class Captain, School Prefect'); ?>
        </div>
        <div>
            <label class="field-label">Areas of Interest</label>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-1">
                <?php foreach(['int_speaking'=>'🎤 Public Speaking','int_business'=>'💼 Business & Entrepreneurship','int_agriculture'=>'🌾 Agriculture','int_ict'=>'💻 ICT / Technology','int_tailoring'=>'✂️ Tailoring / Fashion','int_arts'=>'🎨 Arts / Music','int_debate'=>'🗣️ Debate'] as $k=>$v): ?>
                <input type="checkbox" name="<?php echo $k; ?>" id="<?php echo $k; ?>" class="card-check">
                <label for="<?php echo $k; ?>" class="card-check-label"><?php echo $v; ?></label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php echo fld('int_other','Other Interest'); ?>
        <?php echo fld('future_career','Future Career Ambition','text',false,'e.g. Doctor, Engineer, Lawyer'); ?>
    </div>
</div>

<!-- ══════ STEP 6 — Character ══════ -->
<div class="form-step" data-step="6">
    <?php echo shead(6,'verified_user','#475569, #334155','Section G — Character & Discipline','Be honest — this helps us support you better'); ?>
    <div class="space-y-4">
        <input type="checkbox" name="ever_suspended" id="ever_suspended" class="card-check toggle-detail" data-target="suspension_detail">
        <label for="ever_suspended" class="card-check-label w-full">Ever been suspended or expelled from school?</label>
        <div id="suspension_detail" class="hidden">
            <?php echo fld('suspension_details','Please explain','text',false,'Details of the suspension…'); ?>
        </div>
        <?php echo fld('special_talents','Special Talents / Skills','text',false,'e.g. Singing, Drawing, Coding, Football'); ?>
        <div>
            <label class="field-label">How did you hear about GOLA?</label>
            <textarea name="how_heard" rows="3" class="field-input dark:bg-slate-800 dark:border-slate-600 dark:text-white" style="height:auto;resize:none;"></textarea>
        </div>
    </div>
</div>

<!-- ══════ STEP 7 — Payment & Declaration ══════ -->
<div class="form-step" data-step="7">
    <?php echo shead(7,'payments','#b45309, #92400e','Section H — Payment Proof & Declaration','Upload your bank teller — then read and accept the declaration'); ?>
    <div class="space-y-6">

        <!-- Account details -->
        <div class="bg-primary/5 dark:bg-primary/10 border border-primary/20 rounded-2xl p-5">
            <p class="font-bold text-primary dark:text-gold text-sm mb-4">Make payment to this account first:</p>
            <div class="grid sm:grid-cols-2 gap-4">
                <?php foreach([['account_balance','Bank',$app_fee_bank],['tag','Account Number',$app_fee_account_no],['person','Account Name',$app_fee_account_name]] as [$icon,$lbl,$val]): if (!$val) continue; ?>
                <div class="flex gap-3 items-start">
                    <span class="material-symbols-outlined text-gold mt-0.5"><?php echo $icon; ?></span>
                    <div>
                        <p class="text-xs text-slate-400"><?php echo $lbl; ?></p>
                        <p class="font-bold text-slate-900 dark:text-white <?php echo $icon==='tag'?'font-mono text-xl tracking-widest':''; ?>"><?php echo htmlspecialchars($val); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
                <div class="flex gap-3 items-start">
                    <span class="material-symbols-outlined text-gold mt-0.5">payments</span>
                    <div>
                        <p class="text-xs text-slate-400">Amount to Pay</p>
                        <p class="font-black text-3xl text-primary dark:text-gold">₦<?php echo number_format(floatval($app_fee_amount)); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment fields -->
        <div class="grid md:grid-cols-3 gap-4">
            <?php echo fld('payment_amount','Amount Paid (₦)','number',false,'',  'value="'.$app_fee_amount.'"'); ?>
            <?php echo fld('payment_bank','Bank Paid Into','text',false,'', 'value="'.htmlspecialchars($app_fee_bank).'"'); ?>
            <div>
                <label class="field-label">Date of Payment</label>
                <input type="date" name="payment_date" class="field-input dark:bg-slate-800 dark:border-slate-600 dark:text-white">
            </div>
        </div>

        <!-- Upload -->
        <div>
            <label class="field-label">Upload Proof of Payment <span class="field-req">*</span></label>
            <label id="uploadZone" class="flex flex-col items-center gap-3 border-2 border-dashed border-slate-300 dark:border-slate-600 hover:border-gold rounded-2xl p-10 cursor-pointer transition-all bg-slate-50 dark:bg-slate-800 group">
                <span class="material-symbols-outlined text-5xl text-slate-300 group-hover:text-gold transition-colors">upload_file</span>
                <div class="text-center">
                    <p class="font-semibold text-slate-600 dark:text-slate-400 text-sm">Click to upload bank teller or screenshot</p>
                    <p class="text-xs text-slate-400 mt-1">JPG, PNG or PDF — max 5MB</p>
                </div>
                <input type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf" required id="paymentFile" class="hidden">
            </label>
            <div id="filePreview" class="hidden mt-3 flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800">
                <span class="material-symbols-outlined text-green-600">check_circle</span>
                <span id="fileName" class="text-sm font-semibold text-green-700 dark:text-green-400"></span>
                <button type="button" onclick="clearFile()" class="ml-auto text-xs text-slate-400 hover:text-red-500">Remove</button>
            </div>
        </div>

        <!-- Declaration -->
        <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
            <h3 class="font-bold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                <span class="material-symbols-outlined text-gold">gavel</span>Declaration
            </h3>
            <blockquote class="border-l-4 border-gold bg-white dark:bg-slate-700/50 rounded-r-xl px-4 py-3 text-slate-600 dark:text-slate-300 text-sm italic leading-relaxed mb-4">
                "I declare that the information provided is true and correct. I understand that Goodness Omogo Leadership Academy, Ntezi is a full boarding institution, and I agree to abide by all boarding rules, discipline, and leadership standards of the school."
            </blockquote>
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" id="declaration" required class="mt-0.5 w-5 h-5 text-gold focus:ring-gold rounded border-2 border-slate-300">
                <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">
                    I confirm all information is true, complete and accurate. I agree to the above declaration.
                </span>
            </label>
        </div>
    </div>
</div>

<!-- ══ NAV BUTTONS ══ -->
<div class="flex items-center justify-between mt-8 pt-6 border-t border-slate-200 dark:border-slate-700 gap-4">
    <button type="button" id="prevBtn" onclick="changeStep(-1)"
        class="hidden inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-bold rounded-xl hover:border-primary hover:text-primary transition-all">
        <span class="material-symbols-outlined">arrow_back</span>Back
    </button>
    <div class="flex-1 flex justify-center">
        <div id="stepLabel" class="text-xs text-slate-400 font-semibold">Step 1 of 8 — Personal Information</div>
    </div>
    <button type="button" id="nextBtn" onclick="changeStep(1)"
        class="inline-flex items-center gap-2 px-8 py-3 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition-all shadow-md">
        Next <span class="material-symbols-outlined">arrow_forward</span>
    </button>
    <button type="submit" id="submitBtn"
        class="hidden inline-flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-primary to-[#0A3556] text-white font-bold rounded-xl shadow-xl hover:-translate-y-0.5 transition-all">
        <span class="material-symbols-outlined">send</span>Submit Application
    </button>
</div>

</form>
</div>
</section>

<script>
const TOTAL   = 8;
let current   = 0;

const stepLabels = [
    'Personal Information','Parent / Guardian','Academic Background',
    'Boarding & Health','Sports & Physical','Leadership & Interests',
    'Character & Discipline','Payment & Declaration'
];
const steps       = document.querySelectorAll('.form-step');
const stepItems   = document.querySelectorAll('.step-item');
const connectors  = document.querySelectorAll('.step-connector');
const prevBtn     = document.getElementById('prevBtn');
const nextBtn     = document.getElementById('nextBtn');
const submitBtn   = document.getElementById('submitBtn');
const stepLabel   = document.getElementById('stepLabel');

function updateUI() {
    steps.forEach((s,i)     => s.classList.toggle('active', i===current));
    stepItems.forEach((s,i) => {
        s.classList.toggle('active', i===current);
        s.classList.toggle('done',   i<current);
    });
    connectors.forEach((c,i) => c.classList.toggle('done', i<current));
    prevBtn.classList.toggle('hidden', current===0);
    nextBtn.classList.toggle('hidden', current===TOTAL-1);
    submitBtn.classList.toggle('hidden', current!==TOTAL-1);
    stepLabel.textContent = 'Step '+(current+1)+' of '+TOTAL+' — '+stepLabels[current];
    window.scrollTo({top:0, behavior:'smooth'});
}

function validateStep(n) {
    const step = steps[n];
    const required = step.querySelectorAll('[required]');
    let ok = true;
    required.forEach(el => {
        el.classList.remove('border-red-400');
        if (!el.value.trim()) {
            el.classList.add('border-red-400');
            ok = false;
        }
    });
    // Radio group check
    const radios = step.querySelectorAll('input[type=radio][required]');
    if (radios.length) {
        const name = radios[0].name;
        const checked = step.querySelector('input[type=radio][name="'+name+'"]:checked');
        if (!checked) {
            radios.forEach(r => r.closest('label')?.classList.add('!border-red-400'));
            ok = false;
        }
    }
    if (!ok) {
        const first = step.querySelector('.border-red-400, .\\!border-red-400');
        if (first) first.scrollIntoView({behavior:'smooth', block:'center'});
    }
    return ok;
}

function changeStep(dir) {
    if (dir === 1 && !validateStep(current)) return;
    current = Math.max(0, Math.min(TOTAL-1, current+dir));
    updateUI();
}

// Remove red border on input
document.addEventListener('input', e => e.target.classList.remove('border-red-400'));
document.addEventListener('change', e => {
    e.target.classList.remove('border-red-400','!border-red-400');
    e.target.closest('label')?.classList.remove('!border-red-400');
});

// Toggle detail fields
document.querySelectorAll('.toggle-detail').forEach(cb => {
    const t = document.getElementById(cb.dataset.target);
    if (t) cb.addEventListener('change', () => t.classList.toggle('hidden', !cb.checked));
});

// File upload
const paymentFile = document.getElementById('paymentFile');
paymentFile.addEventListener('change', function() {
    const preview = document.getElementById('filePreview');
    const name    = document.getElementById('fileName');
    if (this.files[0]) {
        name.textContent = this.files[0].name;
        preview.classList.remove('hidden');
        document.getElementById('uploadZone').classList.add('border-gold');
    }
});
function clearFile() {
    paymentFile.value = '';
    document.getElementById('filePreview').classList.add('hidden');
    document.getElementById('uploadZone').classList.remove('border-gold');
}

// Final submit validation
document.getElementById('appForm').addEventListener('submit', function(e) {
    if (!document.getElementById('declaration').checked) {
        e.preventDefault();
        alert('Please accept the declaration before submitting.');
        return;
    }
    if (!paymentFile.files[0]) {
        e.preventDefault();
        alert('Please upload proof of payment before submitting.');
        paymentFile.closest('label').scrollIntoView({behavior:'smooth',block:'center'});
    }
});

updateUI();
</script>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
