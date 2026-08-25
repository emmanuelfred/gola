<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('staff');
require_once 'includes/staff_helper.php';
$page_title = "Add Staff";
$errors = [];
$success = '';

$roles = getActiveRoles($conn);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name    = trim($_POST['first_name'] ?? '');
    $middle_name   = trim($_POST['middle_name'] ?? '');
    $last_name     = trim($_POST['last_name'] ?? '');
    $gender        = $_POST['gender'] ?? '';
    $role_id       = intval($_POST['role_id'] ?? 0);
    $date_of_birth = $_POST['date_of_birth'] ?: null;
    $phone         = trim($_POST['phone'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $address       = trim($_POST['address'] ?? '');
    $date_employed = $_POST['date_employed'] ?: date('Y-m-d');
    $salary        = floatval($_POST['salary'] ?? 0);
    $status        = in_array($_POST['status'] ?? '', ['Active','On Leave','Suspended','Terminated']) ? $_POST['status'] : 'Active';
    $notes         = trim($_POST['notes'] ?? '');

    $role = $role_id ? getRole($conn, $role_id) : null;

    if (!$first_name) $errors[] = 'First name is required.';
    if (!$last_name) $errors[] = 'Last name is required.';
    if (!$gender) $errors[] = 'Gender is required.';
    if (!$role) $errors[] = 'Please select a valid role.';

    // Login account fields — only relevant/required when the chosen role needs a login
    $create_login = ($role && $role['requires_login']) ? 1 : 0;
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if ($create_login) {
        if (!$email) $errors[] = 'Email is required to create a login account for this role.';
        if (!$username) $errors[] = 'Username is required for this role\'s login account.';
        elseif (strlen($username) < 4) $errors[] = 'Username must be at least 4 characters.';
        if (!$password) $errors[] = 'Password is required for this role\'s login account.';
        elseif (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';

        if ($username) {
            $check = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
            $check->bind_param("s", $username);
            $check->execute();
            if ($check->get_result()->num_rows > 0) $errors[] = 'That username is already taken.';
        }
    }

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
        $conn->begin_transaction();
        try {
            $admin_user_id = null;

            // Create the login account first, if this role requires one
            if ($create_login) {
                // NOTE: matches this project's existing plaintext password storage
                // (see admin/login.php) — change to password_hash() in both places
                // together if you decide to harden this.
                $hashed_password = $password;
                $stmt = $conn->prepare("INSERT INTO admin_users (username, password, full_name, email, role, is_active) VALUES (?,?,?,?,?,1)");
                $full_name = trim("$first_name $last_name");
                $stmt->bind_param("sssss", $username, $hashed_password, $full_name, $email, $role['system_access']);
                if (!$stmt->execute()) throw new Exception('Failed to create login account: ' . $conn->error);
                $admin_user_id = $conn->insert_id;
            }

            $staff_id = generateStaffId($conn);

            $stmt = $conn->prepare("INSERT INTO staff (
                staff_id, role_id, first_name, middle_name, last_name, gender, date_of_birth,
                phone, email, address, passport_photo, date_employed, salary, status, admin_user_id, notes
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param(
                "sissssssssssdsis",
                $staff_id, $role_id, $first_name, $middle_name, $last_name, $gender, $date_of_birth,
                $phone, $email, $address, $passport_path, $date_employed, $salary, $status, $admin_user_id, $notes
            );
            if (!$stmt->execute()) throw new Exception('Failed to save staff record: ' . $conn->error);

            $conn->commit();
            logActivity('add_staff', "Added staff: $first_name $last_name ($staff_id) as {$role['role_name']}");
            $success = "Staff registered successfully! Staff ID: <strong>$staff_id</strong>"
                . ($create_login ? " — login username: <strong>" . htmlspecialchars($username) . "</strong>" : "");
            $_POST = [];
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html class="scroll-smooth" lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | G.O.L.A</title>
    <link rel="icon" href="../asset/favicon.png" type="image/png">
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
                <a href="manage_staff.php" class="p-2 hover:bg-slate-100 rounded-lg"><span class="material-symbols-outlined">arrow_back</span></a>
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">Add Staff</h1>
                    <p class="text-slate-600">Fill in the required (*) fields. Staff ID is auto-generated.</p>
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

                <!-- 1. ROLE -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="section-title flex items-center gap-2"><span class="material-symbols-outlined text-sm">badge</span> 1. Role</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <label class="text-xs font-semibold text-slate-600">Role <span class="text-red-500">*</span></label>
                            <select name="role_id" id="roleSelect" required class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold" onchange="onRoleChange()">
                                <option value="">Select Role</option>
                                <?php foreach ($roles as $r): ?>
                                <option value="<?php echo $r['id']; ?>"
                                    data-salary="<?php echo $r['default_salary']; ?>"
                                    data-login="<?php echo $r['requires_login']; ?>"
                                    <?php echo (($_POST['role_id'] ?? '') == $r['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($r['role_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($roles)): ?>
                            <p class="text-xs text-red-500 mt-1">No active roles found — <a href="manage_staff_roles.php" class="underline">add one first</a>.</p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Salary (&#8358;) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" min="0" name="salary" id="salaryInput" required class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold" value="<?php echo htmlspecialchars($_POST['salary'] ?? '0.00'); ?>">
                            <p class="text-xs text-slate-400 mt-1">Pre-fills from the role's default — adjust if needed.</p>
                        </div>
                        <div id="loginNotice" class="md:col-span-3 hidden p-3 bg-blue-50 rounded-lg text-sm text-blue-700">
                            <span class="material-symbols-outlined text-sm align-middle">login</span>
                            This role needs a system login — fill in the login details in section 4 below.
                        </div>
                    </div>
                </div>

                <!-- 2. PERSONAL INFORMATION -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="section-title flex items-center gap-2"><span class="material-symbols-outlined text-sm">person</span> 2. Personal Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div><label class="text-xs font-semibold text-slate-600">First Name *</label><input type="text" name="first_name" required class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Middle Name</label><input type="text" name="middle_name" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold" value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Last Name *</label><input type="text" name="last_name" required class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"></div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Passport Photo</label>
                            <input type="file" name="passport_photo" accept="image/*" class="mt-1 w-full text-sm file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-gold/10 file:text-gold file:font-semibold hover:file:bg-gold/20" onchange="previewPhoto(this)">
                            <img id="photo-preview" class="mt-2 h-24 w-24 object-cover rounded-lg border hidden">
                        </div>
                        <div><label class="text-xs font-semibold text-slate-600">Gender *</label><select name="gender" required class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"><option value="">Select</option><option <?php echo (($_POST['gender'] ?? '')=='Male')?'selected':''; ?>>Male</option><option <?php echo (($_POST['gender'] ?? '')=='Female')?'selected':''; ?>>Female</option></select></div>
                        <div><label class="text-xs font-semibold text-slate-600">Date of Birth</label><input type="date" name="date_of_birth" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold" value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Phone Number</label><input type="tel" name="phone" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Email Address</label><input type="email" name="email" id="emailInput" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"></div>
                        <div class="md:col-span-2"><label class="text-xs font-semibold text-slate-600">Address</label><input type="text" name="address" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>"></div>
                    </div>
                </div>

                <!-- 3. EMPLOYMENT -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="section-title flex items-center gap-2"><span class="material-symbols-outlined text-sm">work</span> 3. Employment</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-3 p-3 bg-blue-50 rounded-lg text-sm text-blue-700"><span class="material-symbols-outlined text-sm align-middle">info</span> Staff ID will be auto-generated upon submission.</div>
                        <div><label class="text-xs font-semibold text-slate-600">Date Employed</label><input type="date" name="date_employed" value="<?php echo htmlspecialchars($_POST['date_employed'] ?? date('Y-m-d')); ?>" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Status</label>
                            <select name="status" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
                                <?php foreach (['Active','On Leave','Suspended','Terminated'] as $st): ?>
                                <option <?php echo (($_POST['status'] ?? 'Active')==$st)?'selected':''; ?>><?php echo $st; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="md:col-span-3"><label class="text-xs font-semibold text-slate-600">Notes</label><textarea name="notes" rows="2" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea></div>
                    </div>
                </div>

                <!-- 4. LOGIN ACCOUNT (shown only when the selected role needs one) -->
                <div class="bg-white rounded-xl border border-slate-200 p-6" id="loginSection">
                    <h2 class="section-title flex items-center gap-2"><span class="material-symbols-outlined text-sm">login</span> 4. Login Account</h2>
                    <p class="text-xs text-slate-500 mb-4">Select a role above to see whether a login is needed. Login activity will also be used to feed attendance/payroll later.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="text-xs font-semibold text-slate-600">Username</label><input type="text" name="username" id="usernameInput" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Password</label><input type="password" name="password" id="passwordInput" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold" placeholder="At least 6 characters"></div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="manage_staff.php" class="px-6 py-3 bg-slate-100 text-slate-700 rounded-xl font-semibold hover:bg-slate-200">Cancel</a>
                    <button type="submit" class="px-8 py-3 bg-gold text-primary rounded-xl font-bold hover:bg-gold/90 shadow-sm inline-flex items-center gap-2">
                        <span class="material-symbols-outlined">save</span>Save Staff
                    </button>
                </div>
            </form>

        </main>
    </div>
</div>

<script>
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const preview = document.getElementById('photo-preview');
        preview.src = URL.createObjectURL(input.files[0]);
        preview.classList.remove('hidden');
    }
}

function onRoleChange() {
    const sel = document.getElementById('roleSelect');
    const opt = sel.options[sel.selectedIndex];
    const salaryInput = document.getElementById('salaryInput');
    const loginNotice = document.getElementById('loginNotice');
    const loginSection = document.getElementById('loginSection');

    if (!opt || !opt.value) {
        loginNotice.classList.add('hidden');
        loginSection.style.opacity = '0.4';
        return;
    }

    // Pre-fill salary from role default (only if the field is still untouched/zero)
    const defaultSalary = opt.dataset.salary || '0.00';
    if (!salaryInput.dataset.userEdited) salaryInput.value = defaultSalary;

    const needsLogin = opt.dataset.login === '1';
    loginNotice.classList.toggle('hidden', !needsLogin);
    loginSection.style.opacity = needsLogin ? '1' : '0.4';
    document.getElementById('usernameInput').required = needsLogin;
    document.getElementById('passwordInput').required = needsLogin;
}
document.getElementById('salaryInput').addEventListener('input', function() { this.dataset.userEdited = '1'; });
document.addEventListener('DOMContentLoaded', onRoleChange);
</script>
</body>
</html>
