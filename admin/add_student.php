<?php
require_once 'auth_check.php';
$page_title = "Register Student";
$errors = [];
$success = '';

$classes = $conn->query("SELECT id, class_name, arm FROM classes ORDER BY class_name, arm");
$sessions = $conn->query("SELECT id, session_name FROM academic_sessions ORDER BY id DESC");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $class_id = intval($_POST['class_id'] ?? 0);

    if (!$first_name) $errors[] = 'First name is required.';
    if (!$last_name) $errors[] = 'Last name is required.';
    if (!$gender) $errors[] = 'Gender is required.';
    if (!$class_id) $errors[] = 'Class is required.';

    // Handle passport photo upload
    $passport_path = null;
    if (isset($_FILES['passport_photo']) && $_FILES['passport_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/passports/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['passport_photo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
            $errors[] = 'Passport photo must be JPG, PNG, or WEBP.';
        } elseif ($_FILES['passport_photo']['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Passport photo must be under 2MB.';
        } else {
            $filename = time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['passport_photo']['tmp_name'], $upload_dir . $filename);
            $passport_path = 'uploads/passports/' . $filename;
        }
    }

    if (empty($errors)) {
        // Auto-generate student ID
        $class_row = $conn->query("SELECT class_name, arm FROM classes WHERE id = $class_id")->fetch_assoc();
        $class_code = str_replace(' ', '', $class_row['class_name']);
        $year = date('Y');
        $count_q = $conn->query("SELECT COUNT(*) as c FROM students WHERE class_id = $class_id");
        $count = $count_q->fetch_assoc()['c'] + 1;
        $student_id = 'GOLA/' . $year . '/' . $class_code . '/' . str_pad($count, 3, '0', STR_PAD_LEFT);

        // Ensure unique
        $check = $conn->prepare("SELECT id FROM students WHERE student_id = ?");
        $check->bind_param("s", $student_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $student_id .= '-' . rand(10, 99);
        }

        $session_id = intval($_POST['session_id'] ?? 0) ?: null;
        
        $stmt = $conn->prepare("INSERT INTO students (
            student_id, first_name, middle_name, last_name, passport_photo, gender, date_of_birth,
            state_of_origin, lga, nationality, religion, blood_group, genotype, phone, email,
            admission_date, class_id, session_id, status, student_type,
            father_name, father_phone, father_occupation, mother_name, mother_phone, mother_occupation,
            guardian_name, guardian_phone, guardian_relationship, parent_email, parent_address,
            home_address, city, state, emergency_contact_name, emergency_contact_phone, emergency_contact_relationship,
            has_medical_condition, medical_condition_desc, allergies, physical_disability,
            doctor_name, doctor_phone, hospital_name, special_medical_instructions,
            previous_school_name, previous_school_address, previous_class_completed,
            reason_for_leaving, transfer_cert_number, previous_performance, date_left_previous
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

        $dob = $_POST['date_of_birth'] ?: null;
        $adm_date = $_POST['admission_date'] ?: null;
        $date_left = $_POST['date_left_previous'] ?: null;
        $med = $_POST['has_medical_condition'] ?? 'No';
        $bg = $_POST['blood_group'] ?? '';
        $gt = $_POST['genotype'] ?? '';

        $stmt->bind_param("ssssssssssssssssississssssssssssssssssssssssssssssss",
            $student_id, $first_name, $middle_name, $last_name, $passport_path, $gender, $dob,
            $_POST['state_of_origin'], $_POST['lga'], $_POST['nationality'], $_POST['religion'], $bg, $gt, $_POST['phone'], $_POST['email'],
            $adm_date, $class_id, $session_id, $_POST['status'], $_POST['student_type'],
            $_POST['father_name'], $_POST['father_phone'], $_POST['father_occupation'], $_POST['mother_name'], $_POST['mother_phone'], $_POST['mother_occupation'],
            $_POST['guardian_name'], $_POST['guardian_phone'], $_POST['guardian_relationship'], $_POST['parent_email'], $_POST['parent_address'],
            $_POST['home_address'], $_POST['city'], $_POST['state_addr'], $_POST['emergency_contact_name'], $_POST['emergency_contact_phone'], $_POST['emergency_contact_relationship'],
            $med, $_POST['medical_condition_desc'], $_POST['allergies'], $_POST['physical_disability'],
            $_POST['doctor_name'], $_POST['doctor_phone'], $_POST['hospital_name'], $_POST['special_medical_instructions'],
            $_POST['previous_school_name'], $_POST['previous_school_address'], $_POST['previous_class_completed'],
            $_POST['reason_for_leaving'], $_POST['transfer_cert_number'], $_POST['previous_performance'], $date_left
        );

        if ($stmt->execute()) {
            logActivity('register_student', 'Registered student: ' . $first_name . ' ' . $last_name . ' (' . $student_id . ')');
            $success = "Student registered successfully! Registration Number: <strong>" . $student_id . "</strong>";
        } else {
            $errors[] = 'Database error: ' . $conn->error;
        }
    }
}

$nigerian_states = ['Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno','Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara'];
?>
<!DOCTYPE html>
<html class="scroll-smooth" lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | G.O.L.A</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>tailwind.config={theme:{extend:{colors:{primary:"#0A2E4D",gold:"#C5A059"},fontFamily:{sans:["Inter","sans-serif"]}}}};</script>
    <style>.sidebar-link.active{background:linear-gradient(90deg,rgba(197,160,89,0.1) 0%,transparent 100%);border-left:3px solid #C5A059;color:#C5A059;}.section-title{font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#C5A059;margin-bottom:1rem;padding-bottom:0.5rem;border-bottom:2px solid #C5A05930;}</style>
</head>
<body class="bg-slate-50 font-sans">
<div class="flex h-screen overflow-hidden">
    <?php include 'admin_sidebar.php'; ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?php include 'admin_topbar.php'; ?>
        <main class="flex-1 overflow-y-auto p-8">
            
            <div class="flex items-center gap-4 mb-8">
                <a href="manage_students.php" class="p-2 hover:bg-slate-100 rounded-lg"><span class="material-symbols-outlined">arrow_back</span></a>
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">Register New Student</h1>
                    <p class="text-slate-600">Fill in all required (*) fields. Registration number is auto-generated.</p>
                </div>
            </div>

            <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($errors): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
                <?php foreach ($errors as $e) echo '<p class="text-sm">&bull; ' . htmlspecialchars($e) . '</p>'; ?>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-8">

                <!-- 1. BASIC STUDENT INFORMATION -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="section-title flex items-center gap-2"><span class="material-symbols-outlined text-sm">person</span> 1. Basic Student Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div><label class="text-xs font-semibold text-slate-600">First Name *</label><input type="text" name="first_name" required class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Middle Name</label><input type="text" name="middle_name" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold" value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Last Name *</label><input type="text" name="last_name" required class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"></div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Passport Photo</label>
                            <input type="file" name="passport_photo" accept="image/*" class="mt-1 w-full text-sm file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-gold/10 file:text-gold file:font-semibold hover:file:bg-gold/20" onchange="previewPhoto(this)">
                            <img id="photo-preview" class="mt-2 h-24 w-24 object-cover rounded-lg border hidden">
                        </div>
                        <div><label class="text-xs font-semibold text-slate-600">Gender *</label><select name="gender" required class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"><option value="">Select</option><option value="Male">Male</option><option value="Female">Female</option></select></div>
                        <div><label class="text-xs font-semibold text-slate-600">Date of Birth</label><input type="date" name="date_of_birth" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">State of Origin</label>
                            <select name="state_of_origin" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
                                <option value="">Select</option>
                                <?php foreach ($nigerian_states as $s): ?><option value="<?php echo $s; ?>"><?php echo $s; ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div><label class="text-xs font-semibold text-slate-600">LGA</label><input type="text" name="lga" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Nationality</label><input type="text" name="nationality" value="Nigerian" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Religion</label><select name="religion" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"><option value="">Select</option><option>Christianity</option><option>Islam</option><option>Traditional</option><option>Other</option></select></div>
                        <div><label class="text-xs font-semibold text-slate-600">Blood Group</label><select name="blood_group" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"><option value="">Select</option><option>A+</option><option>A-</option><option>B+</option><option>B-</option><option>AB+</option><option>AB-</option><option>O+</option><option>O-</option></select></div>
                        <div><label class="text-xs font-semibold text-slate-600">Genotype</label><select name="genotype" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"><option value="">Select</option><option>AA</option><option>AS</option><option>AC</option><option>SS</option><option>SC</option><option>CC</option></select></div>
                        <div><label class="text-xs font-semibold text-slate-600">Phone Number</label><input type="tel" name="phone" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Email Address</label><input type="email" name="email" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                    </div>
                </div>

                <!-- 2. ADMISSION INFORMATION -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="section-title flex items-center gap-2"><span class="material-symbols-outlined text-sm">school</span> 2. Admission Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-3 p-3 bg-blue-50 rounded-lg text-sm text-blue-700"><span class="material-symbols-outlined text-sm align-middle">info</span> Registration Number will be auto-generated upon submission.</div>
                        <div><label class="text-xs font-semibold text-slate-600">Admission Date</label><input type="date" name="admission_date" value="<?php echo date('Y-m-d'); ?>" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Class *</label><select name="class_id" required class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"><option value="">Select Class</option><?php $classes->data_seek(0); while($c=$classes->fetch_assoc()): ?><option value="<?php echo $c['id']; ?>"><?php echo $c['class_name'].' '.$c['arm']; ?></option><?php endwhile; ?></select></div>
                        <div><label class="text-xs font-semibold text-slate-600">Session</label><select name="session_id" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"><option value="">Select</option><?php while($s=$sessions->fetch_assoc()): ?><option value="<?php echo $s['id']; ?>"><?php echo $s['session_name']; ?></option><?php endwhile; ?></select></div>
                        <div><label class="text-xs font-semibold text-slate-600">Status</label><select name="status" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"><option value="Active">Active</option><option value="Graduated">Graduated</option><option value="Withdrawn">Withdrawn</option><option value="Suspended">Suspended</option></select></div>
                        <div><label class="text-xs font-semibold text-slate-600">Boarding/Day</label><select name="student_type" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"><option value="Day">Day Student</option><option value="Boarding">Boarding Student</option></select></div>
                    </div>
                </div>

                <!-- 3. PARENT/GUARDIAN INFORMATION -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="section-title flex items-center gap-2"><span class="material-symbols-outlined text-sm">family_restroom</span> 3. Parent / Guardian Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div><label class="text-xs font-semibold text-slate-600">Father's Full Name</label><input type="text" name="father_name" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Father's Phone</label><input type="tel" name="father_phone" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Father's Occupation</label><input type="text" name="father_occupation" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Mother's Full Name</label><input type="text" name="mother_name" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Mother's Phone</label><input type="tel" name="mother_phone" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Mother's Occupation</label><input type="text" name="mother_occupation" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Guardian Name</label><input type="text" name="guardian_name" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Guardian Phone</label><input type="tel" name="guardian_phone" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Guardian Relationship</label><input type="text" name="guardian_relationship" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Parent/Guardian Email</label><input type="email" name="parent_email" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div class="md:col-span-2"><label class="text-xs font-semibold text-slate-600">Residential Address</label><textarea name="parent_address" rows="2" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></textarea></div>
                    </div>
                </div>

                <!-- 4. STUDENT CONTACT -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="section-title flex items-center gap-2"><span class="material-symbols-outlined text-sm">home</span> 4. Student Contact Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-3"><label class="text-xs font-semibold text-slate-600">Home Address</label><textarea name="home_address" rows="2" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></textarea></div>
                        <div><label class="text-xs font-semibold text-slate-600">City</label><input type="text" name="city" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div><label class="text-xs font-semibold text-slate-600">State</label><input type="text" name="state_addr" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Emergency Contact Name</label><input type="text" name="emergency_contact_name" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Emergency Contact Phone</label><input type="tel" name="emergency_contact_phone" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Emergency Contact Relationship</label><input type="text" name="emergency_contact_relationship" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                    </div>
                </div>

                <!-- 5. MEDICAL INFORMATION -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="section-title flex items-center gap-2"><span class="material-symbols-outlined text-sm">medical_information</span> 5. Medical Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div><label class="text-xs font-semibold text-slate-600">Any Medical Condition?</label><select name="has_medical_condition" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"><option value="No">No</option><option value="Yes">Yes</option></select></div>
                        <div class="md:col-span-2"><label class="text-xs font-semibold text-slate-600">Condition Description</label><input type="text" name="medical_condition_desc" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Allergies</label><input type="text" name="allergies" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Physical Disability</label><input type="text" name="physical_disability" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Doctor's Name</label><input type="text" name="doctor_name" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Doctor's Phone</label><input type="tel" name="doctor_phone" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Hospital Name</label><input type="text" name="hospital_name" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div class="md:col-span-3"><label class="text-xs font-semibold text-slate-600">Special Medical Instructions</label><textarea name="special_medical_instructions" rows="2" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></textarea></div>
                    </div>
                </div>

                <!-- 6. PREVIOUS SCHOOL -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="section-title flex items-center gap-2"><span class="material-symbols-outlined text-sm">history_edu</span> 6. Previous School Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div><label class="text-xs font-semibold text-slate-600">Previous School Name</label><input type="text" name="previous_school_name" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div class="md:col-span-2"><label class="text-xs font-semibold text-slate-600">Previous School Address</label><input type="text" name="previous_school_address" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Class Completed</label><input type="text" name="previous_class_completed" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Reason for Leaving</label><input type="text" name="reason_for_leaving" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Transfer Certificate No.</label><input type="text" name="transfer_cert_number" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Previous Academic Performance</label><input type="text" name="previous_performance" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Date Left Previous School</label><input type="date" name="date_left_previous" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex gap-4">
                    <button type="submit" class="bg-gold text-primary px-8 py-3 rounded-lg font-bold hover:bg-gold/90 transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined">save</span>
                        Register Student
                    </button>
                    <a href="manage_students.php" class="bg-slate-200 text-slate-700 px-6 py-3 rounded-lg font-semibold hover:bg-slate-300 transition-all">Cancel</a>
                </div>
            </form>

        </main>
    </div>
</div>

<script>
function previewPhoto(input) {
    const preview = document.getElementById('photo-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.classList.remove('hidden'); };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>
