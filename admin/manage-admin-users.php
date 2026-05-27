<?php
require_once '../config/database.php';
require_once 'auth_check.php'; // This already has session_start()

$success_message = '';
$error_message = '';

// Get current admin info from session
$admin_name = $_SESSION['full_name'] ?? $_SESSION['admin_name'] ?? 'Admin';
$admin_email = $_SESSION['email'] ?? $_SESSION['admin_email'] ?? '';
$admin_role = $_SESSION['role'] ?? $_SESSION['admin_role'] ?? 'teacher';
$current_admin_id = $_SESSION['id'] ?? $_SESSION['admin_id'] ?? 0;

// Handle delete action
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Prevent deleting self
    if ($id == $current_admin_id) {
        $error_message = 'You cannot delete your own account!';
    } else {
        $delete_query = "DELETE FROM admin_users WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success_message = 'Admin user deleted successfully!';
        } else {
            $error_message = 'Failed to delete admin user.';
        }
    }
}

// Handle toggle active status
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Prevent deactivating self
    if ($id == $current_admin_id) {
        $error_message = 'You cannot deactivate your own account!';
    } else {
        $toggle_query = "UPDATE admin_users SET is_active = NOT is_active WHERE id = ?";
        $stmt = $conn->prepare($toggle_query);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success_message = 'Account status updated successfully!';
        } else {
            $error_message = 'Failed to update account status.';
        }
    }
}

// Fetch all admin users
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$query = "SELECT * FROM admin_users WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $query .= " AND (username LIKE ? OR full_name LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if (!empty($role_filter)) {
    $query .= " AND role = ?";
    $params[] = $role_filter;
    $types .= 's';
}

if ($status_filter !== '') {
    $query .= " AND is_active = ?";
    $params[] = $status_filter;
    $types .= 'i';
}

$query .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users_result = $stmt->get_result();

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN role = 'super_admin' THEN 1 ELSE 0 END) as super_admins,
    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
    SUM(CASE WHEN role = 'teacher' THEN 1 ELSE 0 END) as teachers,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users
    FROM admin_users";
$stats = $conn->query($stats_query)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admin Users - Goodness Omogo Leadership Academy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0A2E4D',
                        gold: '#C5A059'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">

<div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <?php include 'admin_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Bar -->
        <?php include 'admin_topbar.php'; ?>
        
        <!-- Page Content -->
        <main class="flex-1 overflow-y-auto p-8">
            
            <!-- Page Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Admin Users</h1>
                        <p class="text-gray-600 mt-2">Manage system administrators and teachers</p>
                    </div>
                    <a href="add-admin-user.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2 shadow-lg hover:shadow-xl transition">
                        <span class="material-symbols-outlined">person_add</span>
                        Add New User
                    </a>
                </div>
            </div>
            
            <!-- Success Message -->
            <?php if ($success_message): ?>
            <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-lg">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined">check_circle</span>
                    <p><?php echo $success_message; ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Error Message -->
            <?php if ($error_message): ?>
            <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined">error</span>
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Users</p>
                            <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($stats['total']); ?></p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-lg">
                            <span class="material-symbols-outlined text-blue-600 text-3xl">group</span>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Super Admins</p>
                            <p class="text-3xl font-bold text-purple-600 mt-1"><?php echo number_format($stats['super_admins']); ?></p>
                        </div>
                        <div class="p-3 bg-purple-100 rounded-lg">
                            <span class="material-symbols-outlined text-purple-600 text-3xl">shield_person</span>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Admins</p>
                            <p class="text-3xl font-bold text-green-600 mt-1"><?php echo number_format($stats['admins']); ?></p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-lg">
                            <span class="material-symbols-outlined text-green-600 text-3xl">admin_panel_settings</span>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Teachers</p>
                            <p class="text-3xl font-bold text-orange-600 mt-1"><?php echo number_format($stats['teachers']); ?></p>
                        </div>
                        <div class="p-3 bg-orange-100 rounded-lg">
                            <span class="material-symbols-outlined text-orange-600 text-3xl">school</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters and Search -->
            <div class="bg-white rounded-xl shadow mb-6 p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Search</label>
                        <input 
                            type="text" 
                            name="search" 
                            value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Search by username, name, or email..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                    </div>
                    
                    <!-- Role Filter -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Role</label>
                        <select name="role" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">All Roles</option>
                            <option value="super_admin" <?php echo $role_filter === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                            <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="teacher" <?php echo $role_filter === 'teacher' ? 'selected' : ''; ?>>Teacher</option>
                        </select>
                    </div>
                    
                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">All Status</option>
                            <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Active</option>
                            <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="md:col-span-4 flex gap-2">
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">search</span>
                            Search
                        </button>
                        <a href="manage-admin-users.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                            Clear Filters
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Users Table -->
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">User</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Last Login</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if ($users_result->num_rows > 0): ?>
                                <?php while ($user = $users_result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white font-bold">
                                                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($user['full_name']); ?></p>
                                                <p class="text-sm text-gray-500">@<?php echo htmlspecialchars($user['username']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $role_colors = [
                                            'super_admin' => 'bg-purple-100 text-purple-800',
                                            'admin' => 'bg-green-100 text-green-800',
                                            'teacher' => 'bg-blue-100 text-blue-800'
                                        ];
                                        $role_display = str_replace('_', ' ', ucwords($user['role'], '_'));
                                        ?>
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full <?php echo $role_colors[$user['role']]; ?>">
                                            <?php echo $role_display; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <?php if ($user['is_active']): ?>
                                            <span class="px-3 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded-full">Active</span>
                                        <?php else: ?>
                                            <span class="px-3 py-1 text-xs font-semibold bg-red-100 text-red-800 rounded-full">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?php echo $user['last_login'] ? date('M d, Y h:i A', strtotime($user['last_login'])) : 'Never'; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center gap-2">
                                            <!-- Edit -->
                                            <a href="edit-admin-user.php?id=<?php echo $user['id']; ?>" 
                                               class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" 
                                               title="Edit">
                                                <span class="material-symbols-outlined text-sm">edit</span>
                                            </a>
                                            
                                            <!-- Toggle Status -->
                                            <?php if ($user['id'] != $current_admin_id): ?>
                                            <a href="?toggle_status=1&id=<?php echo $user['id']; ?>" 
                                               class="p-2 text-orange-600 hover:bg-orange-50 rounded-lg transition"
                                               onclick="return confirm('Are you sure you want to change this user\'s status?')"
                                               title="<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                <span class="material-symbols-outlined text-sm">
                                                    <?php echo $user['is_active'] ? 'block' : 'check_circle'; ?>
                                                </span>
                                            </a>
                                            
                                            <!-- Delete -->
                                            <a href="?delete=1&id=<?php echo $user['id']; ?>" 
                                               class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition"
                                               onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone!')"
                                               title="Delete">
                                                <span class="material-symbols-outlined text-sm">delete</span>
                                            </a>
                                            <?php else: ?>
                                            <span class="text-xs text-gray-400 italic">(You)</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <span class="material-symbols-outlined text-gray-300 text-6xl mb-4">person_off</span>
                                            <p class="text-gray-500 font-semibold">No users found</p>
                                            <p class="text-sm text-gray-400 mt-1">Try adjusting your search or filters</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </main>
    </div>
</div>

</body>
</html>