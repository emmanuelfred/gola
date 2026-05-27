<?php
require_once '../config/database.php';
require_once 'auth_check.php'; // This already has session_start()

$success_message = '';
$error_message = '';
$user = null;

// Get current admin info from session
$admin_name = $_SESSION['full_name'] ?? $_SESSION['admin_name'] ?? 'Admin';
$admin_email = $_SESSION['email'] ?? $_SESSION['admin_email'] ?? '';
$admin_role = $_SESSION['role'] ?? $_SESSION['admin_role'] ?? 'teacher';
$current_admin_id = $_SESSION['id'] ?? $_SESSION['admin_id'] ?? 0;

// Get user ID
if (!isset($_GET['id'])) {
    header('Location: manage-admin-users.php');
    exit();
}

$user_id = intval($_GET['id']);

// Fetch user data
$query = "SELECT * FROM admin_users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: manage-admin-users.php');
    exit();
}

$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'teacher';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $change_password = isset($_POST['change_password']);
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    // Validation
    if (empty($full_name) || empty($email)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif ($change_password && strlen($new_password) < 6) {
        $error_message = 'New password must be at least 6 characters long.';
    } elseif ($change_password && $new_password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } else {
        // Check if email already exists for another user
        $check_email = "SELECT id FROM admin_users WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($check_email);
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error_message = 'Email already exists for another user.';
        } else {
            // Update user
            if ($change_password) {
                $hashed_password = $new_password; // In production: password_hash($new_password, PASSWORD_DEFAULT);
                $update_query = "UPDATE admin_users SET full_name = ?, email = ?, role = ?, is_active = ?, password = ? WHERE id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("sssisi", $full_name, $email, $role, $is_active, $hashed_password, $user_id);
            } else {
                $update_query = "UPDATE admin_users SET full_name = ?, email = ?, role = ?, is_active = ? WHERE id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("sssii", $full_name, $email, $role, $is_active, $user_id);
            }
            
            if ($stmt->execute()) {
                $success_message = 'Admin user updated successfully!';
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM admin_users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
            } else {
                $error_message = 'Failed to update admin user: ' . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Admin User - Goodness Omogo Leadership Academy</title>
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
                        <h1 class="text-3xl font-bold text-gray-900">Edit Admin User</h1>
                        <p class="text-gray-600 mt-2">Update administrator account details</p>
                    </div>
                    <a href="manage-admin-users.php" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">arrow_back</span>
                        Back to Users
                    </a>
                </div>
            </div>
            
            <!-- Success Message -->
            <?php if ($success_message): ?>
            <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-lg">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-green-500">check_circle</span>
                    <p><?php echo $success_message; ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Error Message -->
            <?php if ($error_message): ?>
            <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-red-500">error</span>
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Edit User Form -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                
                <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-blue-600 to-blue-700">
                    <div class="flex items-center gap-3 text-white">
                        <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center text-2xl font-bold">
                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                            <p class="text-blue-100 text-sm">@<?php echo htmlspecialchars($user['username']); ?></p>
                        </div>
                    </div>
                </div>
                
                <form method="POST" class="p-6">
                    
                    <!-- Account Information -->
                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <span class="material-symbols-outlined text-blue-600">account_circle</span>
                            Account Information
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Username (Read-only) -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Username</label>
                                <input 
                                    type="text" 
                                    value="<?php echo htmlspecialchars($user['username']); ?>"
                                    disabled
                                    class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-500"
                                >
                                <p class="mt-1 text-xs text-gray-500">Username cannot be changed</p>
                            </div>
                            
                            <!-- Email -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Email Address <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="email" 
                                    name="email" 
                                    required
                                    value="<?php echo htmlspecialchars($user['email']); ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                >
                            </div>
                        </div>
                    </div>
                    
                    <!-- Personal Information -->
                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <span class="material-symbols-outlined text-blue-600">badge</span>
                            Personal Information
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Full Name -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Full Name <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    name="full_name" 
                                    required
                                    value="<?php echo htmlspecialchars($user['full_name']); ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                >
                            </div>
                            
                            <!-- Role -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    User Role <span class="text-red-500">*</span>
                                </label>
                                <select 
                                    name="role" 
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                >
                                    <option value="teacher" <?php echo $user['role'] === 'teacher' ? 'selected' : ''; ?>>Teacher</option>
                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    <option value="super_admin" <?php echo $user['role'] === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                                </select>
                            </div>
                            
                            <!-- Account Status -->
                            <div class="flex items-start pt-8">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input 
                                        type="checkbox" 
                                        name="is_active" 
                                        value="1"
                                        <?php echo $user['is_active'] ? 'checked' : ''; ?>
                                        <?php echo $user['id'] == $current_admin_id ? 'disabled' : ''; ?>
                                        class="w-5 h-5 text-blue-600 rounded border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    >
                                    <div>
                                        <span class="text-sm font-semibold text-gray-700">Active Account</span>
                                        <p class="text-xs text-gray-500">User can login to the system</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Change Password Section -->
                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <span class="material-symbols-outlined text-blue-600">lock_reset</span>
                            Change Password
                        </h3>
                        
                        <div class="mb-4">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    name="change_password" 
                                    id="change_password_checkbox"
                                    class="w-5 h-5 text-blue-600 rounded border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    onchange="togglePasswordFields()"
                                >
                                <span class="text-sm font-semibold text-gray-700">I want to change the password</span>
                            </label>
                        </div>
                        
                        <div id="password_fields" style="display: none;" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- New Password -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">New Password</label>
                                <input 
                                    type="password" 
                                    name="new_password" 
                                    minlength="6"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                    placeholder="••••••••"
                                >
                                <p class="mt-1 text-xs text-gray-500">Minimum 6 characters</p>
                            </div>
                            
                            <!-- Confirm Password -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Confirm New Password</label>
                                <input 
                                    type="password" 
                                    name="confirm_password" 
                                    minlength="6"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                    placeholder="••••••••"
                                >
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200">
                        <a href="manage-admin-users.php" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                            Cancel
                        </a>
                        <button 
                            type="submit" 
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2 shadow-lg hover:shadow-xl"
                        >
                            <span class="material-symbols-outlined">save</span>
                            Update User
                        </button>
                    </div>
                    
                </form>
                
            </div>
            
            <!-- Account Info -->
            <div class="mt-6 grid grid-cols-2 gap-6">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-blue-600">schedule</span>
                        <div class="text-sm">
                            <p class="text-blue-800 font-semibold">Account Created</p>
                            <p class="text-blue-600"><?php echo date('F d, Y h:i A', strtotime($user['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-green-600">login</span>
                        <div class="text-sm">
                            <p class="text-green-800 font-semibold">Last Login</p>
                            <p class="text-green-600"><?php echo $user['last_login'] ? date('F d, Y h:i A', strtotime($user['last_login'])) : 'Never logged in'; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
        </main>
    </div>
</div>

<script>
function togglePasswordFields() {
    const checkbox = document.getElementById('change_password_checkbox');
    const fields = document.getElementById('password_fields');
    
    if (checkbox.checked) {
        fields.style.display = 'grid';
    } else {
        fields.style.display = 'none';
    }
}
</script>

</body>
</html>