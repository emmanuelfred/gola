<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
$page_title = "Role Permissions";

if (!hasPermission('super_admin')) {
    header('Location: access_denied.php?feature=role_permissions');
    exit;
}

$role_id = intval($_GET['role_id'] ?? 0);
$role = $conn->query("SELECT * FROM staff_roles WHERE id=$role_id")->fetch_assoc();
if (!$role) { header('Location: manage_staff_roles.php'); exit; }

$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_permissions') {
    $keys = $_POST['permissions'] ?? [];
    setRolePermissions($conn, $role_id, $keys);
    logActivity('update_role_permissions', "Updated permissions for role: {$role['role_name']}");
    $success = "Permissions saved for " . htmlspecialchars($role['role_name']) . ".";
}

$grouped = getAllPermissionsGrouped($conn);
$current_keys = getRolePermissionKeys($conn, $role_id);
$is_super_admin_role = ($role['role_name'] === 'Super Admin' || $role['system_access'] === 'super_admin');
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

<div class="flex items-center gap-4 mb-6">
    <a href="manage_staff_roles.php" class="p-2 hover:bg-slate-100 rounded-lg"><span class="material-symbols-outlined">arrow_back</span></a>
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Permissions — <?php echo htmlspecialchars($role['role_name']); ?></h1>
        <p class="text-slate-500 text-sm mt-1">Choose which parts of the system this role can see and use. This applies to every staff member currently assigned this role.</p>
    </div>
</div>

<?php if ($success): ?>
<div class="mb-5 p-4 bg-green-50 border border-green-200 rounded-xl flex gap-3 items-start">
    <span class="material-symbols-outlined text-green-600 flex-shrink-0">check_circle</span>
    <p class="text-green-800 text-sm"><?php echo $success; ?></p>
</div>
<?php endif; ?>

<?php if ($is_super_admin_role): ?>
<div class="mb-5 p-4 bg-blue-50 border border-blue-200 rounded-xl flex gap-3 items-start">
    <span class="material-symbols-outlined text-blue-600 flex-shrink-0">info</span>
    <p class="text-blue-700 text-sm">Super Admin always has full access to every page, regardless of what's checked here — this is a safety net so nobody can accidentally lock the top admin role out of the system. Feel free to leave everything checked for clarity.</p>
</div>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="action" value="save_permissions">
    <div class="space-y-6">
        <?php foreach ($grouped as $category => $perms): ?>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-bold text-slate-800 text-sm mb-3"><?php echo htmlspecialchars($category); ?></h3>
            <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-3">
                <?php foreach ($perms as $p): ?>
                <label class="flex items-center gap-2 cursor-pointer p-2 rounded-lg hover:bg-slate-50">
                    <input type="checkbox" name="permissions[]" value="<?php echo htmlspecialchars($p['permission_key']); ?>"
                        <?php echo in_array($p['permission_key'], $current_keys, true) ? 'checked' : ''; ?>
                        class="rounded text-gold focus:ring-gold">
                    <span class="text-sm text-slate-700"><?php echo htmlspecialchars($p['label']); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-6 flex gap-3">
        <button type="submit" class="px-6 py-3 bg-gold text-primary text-sm font-bold rounded-xl hover:bg-gold/90">Save Permissions</button>
        <a href="manage_staff_roles.php" class="px-6 py-3 bg-slate-100 text-slate-700 text-sm font-semibold rounded-xl hover:bg-slate-200">Cancel</a>
    </div>
</form>

</main>
</div>
</div>
</body>
</html>
