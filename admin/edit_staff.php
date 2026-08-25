<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('staff');
require_once 'includes/staff_helper.php';
$page_title = "Edit Staff";
$errors = [];
$success = '';

$id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$id) { header('Location: manage_staff.php'); exit; }

$stmt = $conn->prepare("SELECT * FROM staff WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$st = $stmt->get_result()->fetch_assoc();
if (!$st) { header('Location: manage_staff.php'); exit; }

$roles = getAllRoles($conn);

// Handle update
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
    $date_employed = $_POST['date_employed'] ?: null;
    $salary        = floatval($_POST['salary'] ?? 0);
    $status        = in_array($_POST['status'] ?? '', ['Active','On Leave','Suspended','Terminated']) ? $_POST['status'] : 'Active';
    $notes         = trim($_POST['notes'] ?? '');

    $role = $role_id ? getRole($conn, $role_id) : null;

    if (!$first_name) $errors[] = 'First name is required.';
    if (!$last_name) $errors[] = 'Last name is required.';
    if (!$gender) $errors[] = 'Gender is required.';
    if (!$role) $errors[] = 'Please select a valid role.';

    // Login account handling
    $want_login = isset($_POST['create_login']); // checkbox: "give/keep a login account"
    $new_username = trim($_POST['username'] ?? '');
    $new_password = trim($_POST['password'] ?? ''); // only set if they want to reset it

    if ($want_login && !$st['admin_user_id']) {
        // Creating a brand new login account for this staff member
        if (!$email) $errors[] = 'Email is required to create a login account.';
        if (!$new_username) $errors[] = 'Username is required to create a login account.';
        elseif (strlen($new_username) < 4) $errors[] = 'Username must be at least 4 characters.';
        if (!$new_password) $errors[] = 'Password is required to create a login account.';
        elseif (strlen($new_password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($new_username) {
            $check = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
            $check->bind_param("s", $new_username);
            $check->execute();
            if ($check->get_result()->num_rows > 0) $errors[] = 'That username is already taken.';
        }
    }

    // Handle photo upload
    $passport_path = $st['passport_photo'];
    if (isset($_FILES['passport_photo']) && $_FILES['passport_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/passports/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['passport_photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp']) && $_FILES['passport_photo']['size'] <= 2*1024*1024) {
            $filename = time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['passport_photo']['tmp_name'], $upload_dir . $filename);
            $passport_path = 'uploads/passports/' . $filename;
        }
    }

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            $admin_user_id = $st['admin_user_id'];

            if ($want_login && !$admin_user_id) {
                // Create a new login account and link it
                $hashed_password = $new_password; // matches existing plaintext scheme, see admin/login.php
                $full_name = trim("$first_name $last_name");
                $stmt = $conn->prepare("INSERT INTO admin_users (username, password, full_name, email, role, is_active) VALUES (?,?,?,?,?,1)");
                $stmt->bind_param("sssss", $new_username, $hashed_password, $full_name, $email, $role['system_access']);
                if (!$stmt->execute()) throw new Exception('Failed to create login account: ' . $conn->error);
                $admin_user_id = $conn->insert_id;
            } elseif (!$want_login && $admin_user_id) {
                // Login account removed for this staff member
                $conn->query("DELETE FROM admin_users WHERE id=" . intval($admin_user_id));
                $admin_user_id = null;
            } elseif ($want_login && $admin_user_id) {
                // Keep the existing account, but sync name/email and optionally reset password
                $full_name = trim("$first_name $last_name");
                if ($new_password) {
                    $stmt = $conn->prepare("UPDATE admin_users SET full_name=?, email=?, role=?, password=? WHERE id=?");
                    $stmt->bind_param("ssssi", $full_name, $email, $role['system_access'], $new_password, $admin_user_id);
                } else {
                    $stmt = $conn->prepare("UPDATE admin_users SET full_name=?, email=?, role=? WHERE id=?");
                    $stmt->bind_param("sssi", $full_name, $email, $role['system_access'], $admin_user_id);
                }
                $stmt->execute();
            }

            $stmt = $conn->prepare("UPDATE staff SET
                role_id=?, first_name=?, middle_name=?, last_name=?, gender=?, date_of_birth=?,
                phone=?, email=?, address=?, passport_photo=?, date_employed=?, salary=?, status=?, admin_user_id=?, notes=?
                WHERE id=?");
            $stmt->bind_param(
                "issssssssssdsisi",
                $role_id, $first_name, $middle_name, $last_name, $gender, $date_of_birth,
                $phone, $email, $address, $passport_path, $date_employed, $salary, $status, $admin_user_id, $notes, $id
            );
            if (!$stmt->execute()) throw new Exception('Failed to update staff record: ' . $conn->error);

            $conn->commit();
            logActivity('edit_staff', "Updated staff: $first_name $last_name ({$st['staff_id']})");
            $success = "Staff record updated successfully.";

            // Refresh $st for the form
            $stmt = $conn->prepare("SELECT * FROM staff WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $st = $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}

// Current linked login (if any)
$login = null;
if ($st['admin_user_id']) {
    $login = $conn->query("SELECT * FROM admin_users WHERE id=" . intval($st['admin_user_id']))->fetch_assoc();
}
$current_role = getRole($conn, $st['role_id']);
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
                    <h1 class="text-3xl font-bold text-slate-900">Edit Staff</h1>
                    <p class="text-slate-600"><?php echo htmlspecialchars($st['staff_id']); ?></p>
                </div>
            </div>

            <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($errors): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
                <?php foreach ($errors as $e) echo '<p class="text-sm">&bull; ' . htmlspecialchars($e) . '</p>'; ?>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-8">
                <input type="hidden" name="id" value="<?php echo $st['id']; ?>">

                <!-- 1. ROLE -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="section-title flex items-center gap-2"><span class="material-symbols-outlined text-sm">badge</span> 1. Role</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <label class="text-xs font-semibold text-slate-600">Role <span class="text-red-500">*</span></label>
                            <select name="role_id" id="roleSelect" required class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold" onchange="onRoleChange()">
                                <?php foreach ($roles as $r): ?>
                                <option value="<?php echo $r['id']; ?>"
                                    data-login="<?php echo $r['requires_login']; ?>"
                                    <?php echo ($st['role_id'] == $r['id']) ? 'selected' : ''; ?>
                                    <?php echo (!$r['is_active'] && $st['role_id'] != $r['id']) ? 'disabled' : ''; ?>>
                                    <?php echo htmlspecialchars($r['role_name']); ?><?php echo !$r['is_active'] ? ' (disabled)' : ''; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Salary (&#8358;) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" min="0" name="salary" required class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold" value="<?php echo htmlspecialchars($st['salary']); ?>">
                        </div>
                    </div>
                </div>

                <!-- 2. PERSONAL INFORMATION -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="section-title flex items-center gap-2"><span class="material-symbols-outlined text-sm">person</span> 2. Personal Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div><label class="text-xs font-semibold text-slate-600">First Name *</label><input type="text" name="first_name" required class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold" value="<?php echo htmlspecialchars($st['first_name']); ?>"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Middle Name</label><input type="text" name="middle_name" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold" value="<?php echo htmlspecialchars($st['middle_name']); ?>"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Last Name *</label><input type="text" name="last_name" required class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold" value="<?php echo htmlspecialchars($st['last_name']); ?>"></div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Passport Photo</label>
                            <input type="file" name="passport_photo" accept="image/*" class="mt-1 w-full text-sm file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-gold/10 file:text-gold file:font-semibold hover:file:bg-gold/20" onchange="previewPhoto(this)">
                            <img id="photo-preview" class="mt-2 h-24 w-24 object-cover rounded-lg border <?php echo $st['passport_photo'] ? '' : 'hidden'; ?>" src="../<?php echo htmlspecialchars($st['passport_photo'] ?? ''); ?>">
                        </div>
                        <div><label class="text-xs font-semibold text-slate-600">Gender *</label><select name="gender" required class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"><option value="">Select</option><option <?php echo $st['gender']=='Male'?'selected':''; ?>>Male</option><option <?php echo $st['gender']=='Female'?'selected':''; ?>>Female</option></select></div>
                        <div><label class="text-xs font-semibold text-slate-600">Date of Birth</label><input type="date" name="date_of_birth" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold" value="<?php echo htmlspecialchars($st['date_of_birth']); ?>"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Phone Number</label><input type="tel" name="phone" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold" value="<?php echo htmlspecialchars($st['phone']); ?>"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Email Address</label><input type="email" name="email" id="emailInput" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold" value="<?php echo htmlspecialchars($st['email']); ?>"></div>
                        <div class="md:col-span-2"><label class="text-xs font-semibold text-slate-600">Address</label><input type="text" name="address" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold" value="<?php echo htmlspecialchars($st['address']); ?>"></div>
                    </div>
                </div>

                <!-- 3. EMPLOYMENT -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="section-title flex items-center gap-2"><span class="material-symbols-outlined text-sm">work</span> 3. Employment</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div><label class="text-xs font-semibold text-slate-600">Date Employed</label><input type="date" name="date_employed" value="<?php echo htmlspecialchars($st['date_employed']); ?>" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Status</label>
                            <select name="status" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
                                <?php foreach (['Active','On Leave','Suspended','Terminated'] as $stt): ?>
                                <option <?php echo $st['status']==$stt?'selected':''; ?>><?php echo $stt; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="md:col-span-3"><label class="text-xs font-semibold text-slate-600">Notes</label><textarea name="notes" rows="2" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"><?php echo htmlspecialchars($st['notes']); ?></textarea></div>
                    </div>
                </div>

                <!-- 4. LOGIN ACCOUNT -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="section-title flex items-center gap-2"><span class="material-symbols-outlined text-sm">login</span> 4. Login Account</h2>

                    <label class="flex items-center gap-2 cursor-pointer mb-4">
                        <input type="checkbox" name="create_login" id="createLoginCheck" class="rounded text-gold focus:ring-gold" <?php echo $login ? 'checked' : ''; ?>>
                        <span class="text-sm font-semibold text-slate-700">This staff member has a system login</span>
                    </label>

                    <div id="loginFields" class="grid grid-cols-1 md:grid-cols-2 gap-4 <?php echo $login ? '' : 'hidden'; ?>">
                        <?php if ($login): ?>
                        <div class="md:col-span-2 p-3 bg-blue-50 rounded-lg text-sm text-blue-700">
                            <span class="material-symbols-outlined text-sm align-middle">info</span>
                            Existing login username: <strong><?php echo htmlspecialchars($login['username']); ?></strong> — leave password blank to keep it unchanged.
                        </div>
                        <div><label class="text-xs font-semibold text-slate-600">Password (leave blank to keep)</label><input type="password" name="password" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold" placeholder="New password (optional)"></div>
                        <?php else: ?>
                        <div><label class="text-xs font-semibold text-slate-600">Username</label><input type="text" name="username" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold"></div>
                        <div><label class="text-xs font-semibold text-slate-600">Password</label><input type="password" name="password" class="mt-1 w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold" placeholder="At least 6 characters"></div>
                        <?php endif; ?>
                    </div>
                    <p id="removeLoginNote" class="text-xs text-red-500 mt-2 <?php echo $login ? 'hidden' : ''; ?>">Unchecking this and saving will permanently delete the linked login account.</p>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="view_staff.php?id=<?php echo $st['id']; ?>" class="px-6 py-3 bg-slate-100 text-slate-700 rounded-xl font-semibold hover:bg-slate-200">Cancel</a>
                    <button type="submit" class="px-8 py-3 bg-gold text-primary rounded-xl font-bold hover:bg-gold/90 shadow-sm inline-flex items-center gap-2">
                        <span class="material-symbols-outlined">save</span>Save Changes
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
const hadLoginOriginally = <?php echo $login ? 'true' : 'false'; ?>;
document.getElementById('createLoginCheck').addEventListener('change', function() {
    document.getElementById('loginFields').classList.toggle('hidden', !this.checked);
    document.getElementById('removeLoginNote').classList.toggle('hidden', this.checked || !hadLoginOriginally);
});
</script>
</body>
</html>
