<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('staff');
require_once 'includes/staff_helper.php';
$page_title = "Manage Staff Roles";

$success = '';
$error   = '';

// ── Handle actions ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add_role') {
        $name    = trim($_POST['role_name'] ?? '');
        $salary  = floatval($_POST['default_salary'] ?? 0);
        $login   = isset($_POST['requires_login']) ? 1 : 0;
        $access  = in_array($_POST['system_access'] ?? '', ['super_admin','admin','teacher']) ? $_POST['system_access'] : 'teacher';

        if (!$name) {
            $error = 'Role name is required.';
        } else {
            $next_order = ($conn->query("SELECT COALESCE(MAX(display_order),0)+1 as n FROM staff_roles")->fetch_assoc()['n']);
            $stmt = $conn->prepare("INSERT INTO staff_roles (role_name, default_salary, requires_login, system_access, display_order) VALUES (?,?,?,?,?)");
            $stmt->bind_param("sdisi", $name, $salary, $login, $access, $next_order);
            if ($stmt->execute()) {
                logActivity('add_role', "Added staff role: $name");
                $success = "Role <strong>" . htmlspecialchars($name) . "</strong> added.";
            } else {
                $error = 'Failed to add role: ' . ($conn->error ?: 'That role name may already exist.');
            }
        }
    }

    if ($_POST['action'] === 'edit_role') {
        $id     = intval($_POST['role_id'] ?? 0);
        $name   = trim($_POST['role_name'] ?? '');
        $salary = floatval($_POST['default_salary'] ?? 0);
        $login  = isset($_POST['requires_login']) ? 1 : 0;
        $access = in_array($_POST['system_access'] ?? '', ['super_admin','admin','teacher']) ? $_POST['system_access'] : 'teacher';

        if (!$id || !$name) {
            $error = 'Role name is required.';
        } else {
            $stmt = $conn->prepare("UPDATE staff_roles SET role_name=?, default_salary=?, requires_login=?, system_access=? WHERE id=?");
            $stmt->bind_param("sdisi", $name, $salary, $login, $access, $id);
            if ($stmt->execute()) {
                logActivity('edit_role', "Updated staff role: $name");
                $success = "Role updated.";
            } else {
                $error = 'Failed to update role: ' . $conn->error;
            }
        }
    }

    if ($_POST['action'] === 'toggle_active') {
        $id = intval($_POST['role_id'] ?? 0);
        $conn->query("UPDATE staff_roles SET is_active = 1 - is_active WHERE id=$id");
        $success = 'Role status updated.';
    }
}

$roles = getAllRoles($conn);

// Staff count per role (so we know if a role is in use)
$counts = [];
$cq = $conn->query("SELECT role_id, COUNT(*) as c FROM staff GROUP BY role_id");
while ($cq && $row = $cq->fetch_assoc()) $counts[$row['role_id']] = $row['c'];
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

<div class="mb-8 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Staff Roles</h1>
        <p class="text-slate-500 text-sm mt-1 max-w-2xl">Predefined job roles for staff. Each role carries a default salary (for payroll) and whether staff in that role need a system login. Every role can currently access the whole system — later, access can be fine-tuned per role right here.</p>
    </div>
    <button onclick="document.getElementById('addRoleModal').classList.remove('hidden')"
        class="inline-flex items-center gap-2 bg-gold text-primary px-5 py-3 rounded-xl font-bold hover:bg-gold/90 transition-all shadow-sm flex-shrink-0">
        <span class="material-symbols-outlined">add_circle</span>New Role
    </button>
</div>

<?php if ($success): ?>
<div class="mb-5 p-4 bg-green-50 border border-green-200 rounded-xl flex items-start gap-3">
    <span class="material-symbols-outlined text-green-600">check_circle</span>
    <p class="text-green-800 text-sm"><?php echo $success; ?></p>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl flex items-start gap-3">
    <span class="material-symbols-outlined text-red-600">error</span>
    <p class="text-red-800 text-sm"><?php echo htmlspecialchars($error); ?></p>
</div>
<?php endif; ?>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                <th class="px-5 py-3">Role</th>
                <th class="px-5 py-3">Default Salary</th>
                <th class="px-5 py-3">Needs Login?</th>
                <th class="px-5 py-3">Access Tier</th>
                <th class="px-5 py-3">Staff</th>
                <th class="px-5 py-3">Status</th>
                <th class="px-5 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach ($roles as $r): ?>
            <tr class="hover:bg-slate-50 <?php echo !$r['is_active'] ? 'opacity-50' : ''; ?>">
                <td class="px-5 py-3 font-semibold text-slate-800"><?php echo htmlspecialchars($r['role_name']); ?></td>
                <td class="px-5 py-3 text-slate-600">&#8358;<?php echo number_format($r['default_salary'], 2); ?></td>
                <td class="px-5 py-3">
                    <?php if ($r['requires_login']): ?>
                    <span class="px-2 py-0.5 bg-primary/10 text-primary text-xs font-semibold rounded-full inline-flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">login</span>Yes — login required
                    </span>
                    <?php else: ?>
                    <span class="px-2 py-0.5 bg-slate-100 text-slate-500 text-xs font-semibold rounded-full">No login needed</span>
                    <?php endif; ?>
                </td>
                <td class="px-5 py-3">
                    <span class="px-2 py-0.5 bg-gold/10 text-gold text-xs font-semibold rounded-full capitalize"><?php echo str_replace('_',' ',$r['system_access']); ?></span>
                </td>
                <td class="px-5 py-3 text-slate-600"><?php echo $counts[$r['id']] ?? 0; ?></td>
                <td class="px-5 py-3">
                    <?php if ($r['is_active']): ?>
                    <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-semibold rounded-full">Active</span>
                    <?php else: ?>
                    <span class="px-2 py-0.5 bg-red-100 text-red-600 text-xs font-semibold rounded-full">Disabled</span>
                    <?php endif; ?>
                </td>
                <td class="px-5 py-3 text-right">
                    <button onclick='openEditModal(<?php echo json_encode($r); ?>)' class="text-primary hover:underline text-xs font-semibold mr-3">Edit</button>
                    <a href="manage_role_permissions.php?role_id=<?php echo $r['id']; ?>" class="text-gold hover:underline text-xs font-semibold mr-3">Permissions</a>
                    <?php if (hasPermission('admin')): ?>
                    <form method="POST" class="inline" onsubmit="return confirm('<?php echo $r['is_active'] ? 'Disable' : 'Re-enable'; ?> this role?')">
                        <input type="hidden" name="action" value="toggle_active">
                        <input type="hidden" name="role_id" value="<?php echo $r['id']; ?>">
                        <button type="submit" class="text-xs font-semibold <?php echo $r['is_active'] ? 'text-red-500 hover:underline' : 'text-green-600 hover:underline'; ?>">
                            <?php echo $r['is_active'] ? 'Disable' : 'Enable'; ?>
                        </button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="mt-5 p-4 bg-slate-100 rounded-xl flex items-center gap-3 text-sm text-slate-600">
    <span class="material-symbols-outlined text-slate-400">info</span>
    Need to add staff members? Head over to
    <a href="add_staff.php" class="text-primary font-semibold hover:underline">Add Staff</a> —
    the role list here is exactly what populates that form.
</div>

</main>
</div>
</div>

<!-- Add Role Modal -->
<div id="addRoleModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl p-8 max-w-md w-full shadow-2xl">
    <h2 class="text-xl font-bold text-slate-900 mb-5 flex items-center gap-2">
        <span class="material-symbols-outlined text-gold">badge</span>Add New Role
    </h2>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="add_role">
        <div>
            <label class="text-xs font-semibold text-slate-600 mb-1 block">Role Name <span class="text-red-500">*</span></label>
            <input type="text" name="role_name" required placeholder="e.g. Sports Master"
                class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 mb-1 block">Default Salary (&#8358;)</label>
            <input type="number" step="0.01" min="0" name="default_salary" value="0.00"
                class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold">
            <p class="text-xs text-slate-400 mt-1">Can be overridden per staff member when adding them.</p>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 mb-1 block">System Access Tier</label>
            <select name="system_access" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold">
                <option value="teacher">Teacher-level</option>
                <option value="admin">Admin-level</option>
                <option value="super_admin">Super Admin-level</option>
            </select>
            <p class="text-xs text-slate-400 mt-1">Only matters if this role needs a login. Every tier can see everything for now.</p>
        </div>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="requires_login" class="rounded text-gold focus:ring-gold" checked>
            <span class="text-sm font-semibold text-slate-700">Staff in this role need a system login</span>
        </label>
        <p class="text-xs text-blue-600 bg-blue-50 rounded-lg p-3">
            <span class="material-symbols-outlined text-xs align-middle">info</span>
            Login is also how future attendance/payroll tracking will know a staff member is active.
        </p>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 bg-gold text-primary py-3 rounded-xl font-bold hover:bg-gold/90">Add Role</button>
            <button type="button" onclick="document.getElementById('addRoleModal').classList.add('hidden')"
                class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl font-semibold hover:bg-slate-200">Cancel</button>
        </div>
    </form>
</div>
</div>

<!-- Edit Role Modal -->
<div id="editRoleModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl p-8 max-w-md w-full shadow-2xl">
    <h2 class="text-xl font-bold text-slate-900 mb-5 flex items-center gap-2">
        <span class="material-symbols-outlined text-gold">edit</span>Edit Role
    </h2>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="edit_role">
        <input type="hidden" name="role_id" id="editRoleId">
        <div>
            <label class="text-xs font-semibold text-slate-600 mb-1 block">Role Name <span class="text-red-500">*</span></label>
            <input type="text" name="role_name" id="editRoleName" required
                class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 mb-1 block">Default Salary (&#8358;)</label>
            <input type="number" step="0.01" min="0" name="default_salary" id="editRoleSalary"
                class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 mb-1 block">System Access Tier</label>
            <select name="system_access" id="editRoleAccess" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold">
                <option value="teacher">Teacher-level</option>
                <option value="admin">Admin-level</option>
                <option value="super_admin">Super Admin-level</option>
            </select>
        </div>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="requires_login" id="editRoleLogin" class="rounded text-gold focus:ring-gold">
            <span class="text-sm font-semibold text-slate-700">Staff in this role need a system login</span>
        </label>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 bg-gold text-primary py-3 rounded-xl font-bold hover:bg-gold/90">Save Changes</button>
            <button type="button" onclick="document.getElementById('editRoleModal').classList.add('hidden')"
                class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl font-semibold hover:bg-slate-200">Cancel</button>
        </div>
    </form>
</div>
</div>

<script>
function openEditModal(role) {
    document.getElementById('editRoleId').value = role.id;
    document.getElementById('editRoleName').value = role.role_name;
    document.getElementById('editRoleSalary').value = role.default_salary;
    document.getElementById('editRoleAccess').value = role.system_access;
    document.getElementById('editRoleLogin').checked = role.requires_login == 1;
    document.getElementById('editRoleModal').classList.remove('hidden');
}
['addRoleModal','editRoleModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) this.classList.add('hidden');
    });
});
</script>
</body>
</html>
