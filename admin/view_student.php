<?php
require_once 'auth_check.php';
$page_title = "View Student";

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: manage_students.php'); exit; }

$stmt = $conn->prepare("SELECT s.*, c.class_name, c.arm, acs.session_name FROM students s JOIN classes c ON s.class_id = c.id LEFT JOIN academic_sessions acs ON s.session_id = acs.id WHERE s.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$st = $stmt->get_result()->fetch_assoc();
if (!$st) { header('Location: manage_students.php'); exit; }

$status_colors = ['Active'=>'bg-green-100 text-green-700','Graduated'=>'bg-blue-100 text-blue-700','Withdrawn'=>'bg-yellow-100 text-yellow-700','Suspended'=>'bg-red-100 text-red-700'];
$sc = $status_colors[$st['status']] ?? 'bg-slate-100 text-slate-700';
?>
<!DOCTYPE html>
<html class="scroll-smooth" lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Student | G.O.L.A</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>tailwind.config={theme:{extend:{colors:{primary:"#0A2E4D",gold:"#C5A059"},fontFamily:{sans:["Inter","sans-serif"]}}}};</script>
    <style>.sidebar-link.active{background:linear-gradient(90deg,rgba(197,160,89,0.1) 0%,transparent 100%);border-left:3px solid #C5A059;color:#C5A059;}.info-label{font-size:0.65rem;text-transform:uppercase;letter-spacing:0.05em;font-weight:600;color:#94a3b8;}.info-val{font-size:0.875rem;font-weight:500;color:#1e293b;margin-top:2px;}</style>
</head>
<body class="bg-slate-50 font-sans">
<div class="flex h-screen overflow-hidden">
    <?php include 'admin_sidebar.php'; ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?php include 'admin_topbar.php'; ?>
        <main class="flex-1 overflow-y-auto p-8">
            
            <div class="flex items-center gap-4 mb-8">
                <a href="manage_students.php" class="p-2 hover:bg-slate-100 rounded-lg"><span class="material-symbols-outlined">arrow_back</span></a>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-slate-900"><?php echo htmlspecialchars($st['first_name'].' '.($st['middle_name']??'').' '.$st['last_name']); ?></h1>
                    <p class="text-slate-500 text-sm font-mono"><?php echo htmlspecialchars($st['student_id']); ?></p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold <?php echo $sc; ?>"><?php echo $st['status']; ?></span>
                <a href="edit_student.php?id=<?php echo $id; ?>" class="inline-flex items-center gap-2 bg-gold text-primary px-4 py-2 rounded-lg font-semibold text-sm hover:bg-gold/90"><span class="material-symbols-outlined text-lg">edit</span>Edit</a>
            </div>

            <?php
            // Helper to render info
            function infoField($label, $value) {
                $v = $value ?: '—';
                echo '<div><p class="info-label">'.$label.'</p><p class="info-val">'.htmlspecialchars($v).'</p></div>';
            }
            ?>

            <div class="space-y-6">
                <!-- Basic Info -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gold mb-4 pb-2 border-b border-gold/20 flex items-center gap-2"><span class="material-symbols-outlined text-sm">person</span>Basic Information</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <?php
                        infoField('First Name', $st['first_name']);
                        infoField('Middle Name', $st['middle_name']);
                        infoField('Last Name', $st['last_name']);
                        infoField('Gender', $st['gender']);
                        infoField('Date of Birth', $st['date_of_birth'] ? date('M j, Y', strtotime($st['date_of_birth'])) : '');
                        infoField('State of Origin', $st['state_of_origin']);
                        infoField('LGA', $st['lga']);
                        infoField('Nationality', $st['nationality']);
                        infoField('Religion', $st['religion']);
                        infoField('Blood Group', $st['blood_group']);
                        infoField('Genotype', $st['genotype']);
                        infoField('Phone', $st['phone']);
                        infoField('Email', $st['email']);
                        ?>
                    </div>
                </div>

                <!-- Admission -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gold mb-4 pb-2 border-b border-gold/20 flex items-center gap-2"><span class="material-symbols-outlined text-sm">school</span>Admission Information</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <?php
                        infoField('Registration Number', $st['student_id']);
                        infoField('Admission Date', $st['admission_date'] ? date('M j, Y', strtotime($st['admission_date'])) : '');
                        infoField('Class', $st['class_name'].' '.$st['arm']);
                        infoField('Session', $st['session_name']);
                        infoField('Status', $st['status']);
                        infoField('Student Type', $st['student_type']);
                        ?>
                    </div>
                </div>

                <!-- Parent/Guardian -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gold mb-4 pb-2 border-b border-gold/20 flex items-center gap-2"><span class="material-symbols-outlined text-sm">family_restroom</span>Parent / Guardian</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <?php
                        infoField("Father's Name", $st['father_name']);
                        infoField("Father's Phone", $st['father_phone']);
                        infoField("Father's Occupation", $st['father_occupation']);
                        infoField("Mother's Name", $st['mother_name']);
                        infoField("Mother's Phone", $st['mother_phone']);
                        infoField("Mother's Occupation", $st['mother_occupation']);
                        infoField("Guardian Name", $st['guardian_name']);
                        infoField("Guardian Phone", $st['guardian_phone']);
                        infoField("Guardian Relationship", $st['guardian_relationship']);
                        infoField("Parent Email", $st['parent_email']);
                        ?>
                    </div>
                    <?php if ($st['parent_address']): ?>
                    <div class="mt-4"><?php infoField("Parent Address", $st['parent_address']); ?></div>
                    <?php endif; ?>
                </div>

                <!-- Contact -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gold mb-4 pb-2 border-b border-gold/20 flex items-center gap-2"><span class="material-symbols-outlined text-sm">home</span>Contact Information</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <?php
                        infoField("Home Address", $st['home_address']);
                        infoField("City", $st['city']);
                        infoField("State", $st['state']);
                        infoField("Emergency Contact", $st['emergency_contact_name']);
                        infoField("Emergency Phone", $st['emergency_contact_phone']);
                        infoField("Emergency Relationship", $st['emergency_contact_relationship']);
                        ?>
                    </div>
                </div>

                <!-- Medical -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gold mb-4 pb-2 border-b border-gold/20 flex items-center gap-2"><span class="material-symbols-outlined text-sm">medical_information</span>Medical Information</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <?php
                        infoField("Medical Condition", $st['has_medical_condition']);
                        infoField("Condition Description", $st['medical_condition_desc']);
                        infoField("Allergies", $st['allergies']);
                        infoField("Physical Disability", $st['physical_disability']);
                        infoField("Doctor's Name", $st['doctor_name']);
                        infoField("Doctor's Phone", $st['doctor_phone']);
                        infoField("Hospital", $st['hospital_name']);
                        infoField("Special Instructions", $st['special_medical_instructions']);
                        ?>
                    </div>
                </div>

                <!-- Previous School -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gold mb-4 pb-2 border-b border-gold/20 flex items-center gap-2"><span class="material-symbols-outlined text-sm">history_edu</span>Previous School</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <?php
                        infoField("School Name", $st['previous_school_name']);
                        infoField("School Address", $st['previous_school_address']);
                        infoField("Class Completed", $st['previous_class_completed']);
                        infoField("Reason for Leaving", $st['reason_for_leaving']);
                        infoField("Transfer Cert. No", $st['transfer_cert_number']);
                        infoField("Performance", $st['previous_performance']);
                        infoField("Date Left", $st['date_left_previous'] ? date('M j, Y', strtotime($st['date_left_previous'])) : '');
                        ?>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>
</body>
</html>
