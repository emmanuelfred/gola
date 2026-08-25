<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('staff');
require_once 'includes/staff_helper.php';
$page_title = "Manage Staff";
$success = '';

// Handle delete
if (isset($_GET['delete']) && hasPermission('admin')) {
    $del_id = intval($_GET['delete']);
    // If this staff has a linked login account, remove it too
    $row = $conn->query("SELECT admin_user_id FROM staff WHERE id=$del_id")->fetch_assoc();
    if ($row && $row['admin_user_id']) {
        $conn->query("DELETE FROM admin_users WHERE id=" . intval($row['admin_user_id']));
    }
    $stmt = $conn->prepare("DELETE FROM staff WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        logActivity('delete_staff', 'Deleted staff ID: ' . $del_id);
        $success = "Staff record deleted successfully.";
    }
}

// Filters
$role_filter   = isset($_GET['role']) ? intval($_GET['role']) : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search        = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = "1=1";
$params = [];
$types = '';

if ($role_filter) { $where .= " AND st.role_id = ?"; $params[] = $role_filter; $types .= 'i'; }
if ($status_filter) { $where .= " AND st.status = ?"; $params[] = $status_filter; $types .= 's'; }
if ($search) { $where .= " AND (st.first_name LIKE ? OR st.last_name LIKE ? OR st.staff_id LIKE ?)"; $s = "%$search%"; $params[] = $s; $params[] = $s; $params[] = $s; $types .= 'sss'; }

$sql = "SELECT st.*, r.role_name, r.requires_login FROM staff st JOIN staff_roles r ON st.role_id = r.id WHERE $where ORDER BY st.created_at DESC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$staff_list = $stmt->get_result();

$roles = getAllRoles($conn);
$total_staff  = $conn->query("SELECT COUNT(*) as c FROM staff")->fetch_assoc()['c'];
$active_staff = $conn->query("SELECT COUNT(*) as c FROM staff WHERE status='Active'")->fetch_assoc()['c'];
$with_login   = $conn->query("SELECT COUNT(*) as c FROM staff WHERE admin_user_id IS NOT NULL")->fetch_assoc()['c'];
$total_payroll = $conn->query("SELECT COALESCE(SUM(salary),0) as s FROM staff WHERE status='Active'")->fetch_assoc()['s'];
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
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: "#0A2E4D", gold: "#C5A059" }, fontFamily: { sans: ["Inter", "sans-serif"] } } } };
    </script>
    <style>.sidebar-link.active{background:linear-gradient(90deg,rgba(197,160,89,0.1) 0%,transparent 100%);border-left:3px solid #C5A059;color:#C5A059;}</style>
</head>
<body class="bg-slate-50 font-sans">
<div class="flex h-screen overflow-hidden">
    <?php include 'admin_sidebar.php'; ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?php include 'admin_topbar.php'; ?>
        <main class="flex-1 overflow-y-auto p-8">

            <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">Staff Records</h1>
                    <p class="text-slate-600">Manage all staff, their roles, and salary information.</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="manage_staff_roles.php" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 rounded-xl font-semibold text-sm text-slate-700 hover:bg-slate-50">
                        <span class="material-symbols-outlined text-sm">tune</span>Manage Roles
                    </a>
                    <a href="add_staff.php" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gold text-primary rounded-xl font-bold text-sm hover:bg-gold/90 shadow-sm">
                        <span class="material-symbols-outlined text-sm">person_add</span>Add Staff
                    </a>
                </div>
            </div>

            <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-xs font-semibold text-slate-500 uppercase">Total Staff</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1"><?php echo $total_staff; ?></p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-xs font-semibold text-slate-500 uppercase">Active</p>
                    <p class="text-2xl font-bold text-green-600 mt-1"><?php echo $active_staff; ?></p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-xs font-semibold text-slate-500 uppercase">With Login</p>
                    <p class="text-2xl font-bold text-primary mt-1"><?php echo $with_login; ?></p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-xs font-semibold text-slate-500 uppercase">Active Monthly Payroll</p>
                    <p class="text-2xl font-bold text-gold mt-1">&#8358;<?php echo number_format($total_payroll, 2); ?></p>
                </div>
            </div>

            <!-- Filters -->
            <form method="GET" class="bg-white rounded-xl border border-slate-200 p-4 mb-6 flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="text-xs font-semibold text-slate-600 mb-1 block">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name or Staff ID..." class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
                </div>
                <div class="w-48">
                    <label class="text-xs font-semibold text-slate-600 mb-1 block">Role</label>
                    <select name="role" class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
                        <option value="">All Roles</option>
                        <?php foreach ($roles as $r): ?>
                        <option value="<?php echo $r['id']; ?>" <?php echo $role_filter==$r['id']?'selected':''; ?>><?php echo htmlspecialchars($r['role_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="w-44">
                    <label class="text-xs font-semibold text-slate-600 mb-1 block">Status</label>
                    <select name="status" class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
                        <option value="">All Status</option>
                        <?php foreach (['Active','On Leave','Suspended','Terminated'] as $st): ?>
                        <option <?php echo $status_filter==$st?'selected':''; ?>><?php echo $st; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="px-5 py-2.5 bg-primary text-white rounded-lg text-sm font-semibold hover:bg-primary/90">Filter</button>
                <?php if ($role_filter || $status_filter || $search): ?>
                <a href="manage_staff.php" class="px-4 py-2.5 bg-slate-100 text-slate-600 rounded-lg text-sm font-semibold hover:bg-slate-200">Clear</a>
                <?php endif; ?>
            </form>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="px-5 py-3">Staff</th>
                            <th class="px-5 py-3">Role</th>
                            <th class="px-5 py-3">Contact</th>
                            <th class="px-5 py-3">Salary</th>
                            <th class="px-5 py-3">Login</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if ($staff_list->num_rows === 0): ?>
                        <tr><td colspan="7" class="px-5 py-12 text-center text-slate-400">No staff found. <a href="add_staff.php" class="text-primary font-semibold hover:underline">Add the first one</a>.</td></tr>
                        <?php endif; ?>
                        <?php while ($s = $staff_list->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <?php if ($s['passport_photo']): ?>
                                    <img src="../<?php echo htmlspecialchars($s['passport_photo']); ?>" class="w-9 h-9 rounded-full object-cover border">
                                    <?php else: ?>
                                    <div class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center text-slate-500 text-xs font-bold"><?php echo strtoupper(substr($s['first_name'],0,1).substr($s['last_name'],0,1)); ?></div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="font-semibold text-slate-800"><?php echo htmlspecialchars($s['first_name'].' '.$s['last_name']); ?></p>
                                        <p class="text-xs text-slate-400"><?php echo htmlspecialchars($s['staff_id']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-slate-600"><?php echo htmlspecialchars($s['role_name']); ?></td>
                            <td class="px-5 py-3 text-slate-500 text-xs">
                                <?php echo htmlspecialchars($s['phone'] ?: '—'); ?><br>
                                <?php echo htmlspecialchars($s['email'] ?: ''); ?>
                            </td>
                            <td class="px-5 py-3 text-slate-700 font-medium">&#8358;<?php echo number_format($s['salary'], 2); ?></td>
                            <td class="px-5 py-3">
                                <?php if ($s['admin_user_id']): ?>
                                <span class="px-2 py-0.5 bg-primary/10 text-primary text-xs font-semibold rounded-full">Has Login</span>
                                <?php elseif ($s['requires_login']): ?>
                                <span class="px-2 py-0.5 bg-amber-100 text-amber-700 text-xs font-semibold rounded-full">Missing Login</span>
                                <?php else: ?>
                                <span class="px-2 py-0.5 bg-slate-100 text-slate-500 text-xs font-semibold rounded-full">Not Needed</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3">
                                <?php
                                $badge = ['Active'=>'bg-green-100 text-green-700','On Leave'=>'bg-amber-100 text-amber-700','Suspended'=>'bg-orange-100 text-orange-700','Terminated'=>'bg-red-100 text-red-600'];
                                ?>
                                <span class="px-2 py-0.5 <?php echo $badge[$s['status']] ?? 'bg-slate-100 text-slate-500'; ?> text-xs font-semibold rounded-full"><?php echo $s['status']; ?></span>
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <a href="view_staff.php?id=<?php echo $s['id']; ?>" class="text-slate-500 hover:text-primary text-xs font-semibold mr-3">View</a>
                                <a href="edit_staff.php?id=<?php echo $s['id']; ?>" class="text-primary hover:underline text-xs font-semibold mr-3">Edit</a>
                                <?php if (hasPermission('admin')): ?>
                                <a href="manage_staff.php?delete=<?php echo $s['id']; ?>" onclick="return confirm('Delete this staff record? This also removes their login account if any.')" class="text-red-500 hover:underline text-xs font-semibold">Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>
</div>
</body>
</html>
