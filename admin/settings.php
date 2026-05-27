<?php
require_once '../config/database.php';
require_once 'auth_check.php'; // This already has session_start()

$success_message = '';
$error_message = '';
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';

// Get current admin info from session
$admin_name = $_SESSION['full_name'] ?? $_SESSION['admin_name'] ?? 'Admin';
$admin_email = $_SESSION['email'] ?? $_SESSION['admin_email'] ?? '';
$admin_role = $_SESSION['role'] ?? $_SESSION['admin_role'] ?? 'teacher';

// Get current user data
$user_id = $_SESSION['id'] ?? $_SESSION['admin_id'] ?? 0;
$query = "SELECT * FROM admin_users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($full_name) || empty($email)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        // Check if email already exists for another user
        $check_email = "SELECT id FROM admin_users WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($check_email);
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error_message = 'Email already exists for another user.';
        } else {
            // Update profile
            $update_query = "UPDATE admin_users SET full_name = ?, email = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssi", $full_name, $email, $user_id);
            
            if ($stmt->execute()) {
                $success_message = 'Profile updated successfully!';
                $_SESSION['full_name'] = $full_name;
                $_SESSION['admin_name'] = $full_name;
                $_SESSION['email'] = $email;
                $_SESSION['admin_email'] = $email;
                $admin_name = $full_name;
                $admin_email = $email;
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM admin_users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
            } else {
                $error_message = 'Failed to update profile.';
            }
        }
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = trim($_POST['current_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = 'Please fill in all password fields.';
        $active_tab = 'password';
    } elseif ($current_password !== $user['password']) { // In production: password_verify()
        $error_message = 'Current password is incorrect.';
        $active_tab = 'password';
    } elseif (strlen($new_password) < 6) {
        $error_message = 'New password must be at least 6 characters long.';
        $active_tab = 'password';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'New passwords do not match.';
        $active_tab = 'password';
    } elseif ($current_password === $new_password) {
        $error_message = 'New password must be different from current password.';
        $active_tab = 'password';
    } else {
        // Update password
        $hashed_password = $new_password; // In production: password_hash($new_password, PASSWORD_DEFAULT);
        $update_query = "UPDATE admin_users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            $success_message = 'Password changed successfully!';
            $active_tab = 'password';
            // Update user data
            $user['password'] = $hashed_password;
        } else {
            $error_message = 'Failed to change password.';
            $active_tab = 'password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Goodness Omogo Leadership Academy</title>
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
                <h1 class="text-3xl font-bold text-gray-900">Account Settings</h1>
                <p class="text-gray-600 mt-2">Manage your profile and account preferences</p>
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
            
            <!-- Settings Container -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                
                <!-- Sidebar Navigation -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow overflow-hidden">
                        <div class="p-6 bg-gradient-to-r from-blue-600 to-blue-700 text-white">
                            <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3 text-3xl font-bold">
                                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                            </div>
                            <h3 class="text-center font-bold text-lg"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                            <p class="text-center text-blue-100 text-sm">@<?php echo htmlspecialchars($user['username']); ?></p>
                            <p class="text-center text-blue-200 text-xs mt-2">
                                <?php 
                                $role_display = str_replace('_', ' ', ucwords($user['role'], '_'));
                                echo $role_display;
                                ?>
                            </p>
                        </div>
                        
                        <nav class="p-4">
                            <a href="?tab=profile" class="flex items-center gap-3 px-4 py-3 rounded-lg mb-2 transition <?php echo $active_tab === 'profile' ? 'bg-blue-50 text-blue-600 font-semibold' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                <span class="material-symbols-outlined">person</span>
                                Profile
                            </a>
                            <a href="?tab=password" class="flex items-center gap-3 px-4 py-3 rounded-lg mb-2 transition <?php echo $active_tab === 'password' ? 'bg-blue-50 text-blue-600 font-semibold' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                <span class="material-symbols-outlined">lock</span>
                                Password
                            </a>
                            <a href="?tab=account" class="flex items-center gap-3 px-4 py-3 rounded-lg transition <?php echo $active_tab === 'account' ? 'bg-blue-50 text-blue-600 font-semibold' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                <span class="material-symbols-outlined">settings</span>
                                Account
                            </a>
                        </nav>
                    </div>
                </div>
                
                <!-- Main Content Area -->
                <div class="lg:col-span-3">
                    
                    <?php if ($active_tab === 'profile'): ?>
                    <!-- Profile Settings -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                                <span class="material-symbols-outlined text-blue-600">person</span>
                                Profile Information
                            </h2>
                            <p class="text-sm text-gray-600 mt-1">Update your personal details</p>
                        </div>
                        
                        <form method="POST" class="p-6">
                            <div class="space-y-6">
                                
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
                                
                                <!-- Full Name -->
                                <div>
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
                                
                                <!-- Role (Read-only) -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Role</label>
                                    <input 
                                        type="text" 
                                        value="<?php echo str_replace('_', ' ', ucwords($user['role'], '_')); ?>"
                                        disabled
                                        class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-500"
                                    >
                                    <p class="mt-1 text-xs text-gray-500">Role is assigned by administrators</p>
                                </div>
                                
                            </div>
                            
                            <div class="mt-8 flex justify-end">
                                <button 
                                    type="submit" 
                                    name="update_profile"
                                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2 shadow-lg hover:shadow-xl"
                                >
                                    <span class="material-symbols-outlined">save</span>
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <?php elseif ($active_tab === 'password'): ?>
                    <!-- Password Settings -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                                <span class="material-symbols-outlined text-blue-600">lock</span>
                                Change Password
                            </h2>
                            <p class="text-sm text-gray-600 mt-1">Update your password regularly for security</p>
                        </div>
                        
                        <form method="POST" class="p-6">
                            <div class="space-y-6">
                                
                                <!-- Current Password -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Current Password <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="password" 
                                        name="current_password" 
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                        placeholder="••••••••"
                                    >
                                </div>
                                
                                <!-- New Password -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        New Password <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="password" 
                                        name="new_password" 
                                        required
                                        minlength="6"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                        placeholder="••••••••"
                                    >
                                    <p class="mt-1 text-xs text-gray-500">Minimum 6 characters</p>
                                </div>
                                
                                <!-- Confirm New Password -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Confirm New Password <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="password" 
                                        name="confirm_password" 
                                        required
                                        minlength="6"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                        placeholder="••••••••"
                                    >
                                </div>
                                
                            </div>
                            
                            <!-- Security Tips -->
                            <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <div class="flex gap-3">
                                    <span class="material-symbols-outlined text-yellow-600">tips_and_updates</span>
                                    <div class="text-sm text-yellow-800">
                                        <p class="font-semibold mb-2">Password Security Tips:</p>
                                        <ul class="list-disc list-inside space-y-1">
                                            <li>Use at least 8 characters</li>
                                            <li>Include uppercase and lowercase letters</li>
                                            <li>Add numbers and special characters</li>
                                            <li>Don't use common words or personal info</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-8 flex justify-end">
                                <button 
                                    type="submit" 
                                    name="change_password"
                                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2 shadow-lg hover:shadow-xl"
                                >
                                    <span class="material-symbols-outlined">lock_reset</span>
                                    Change Password
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <?php elseif ($active_tab === 'account'): ?>
                    <!-- Account Settings -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                                <span class="material-symbols-outlined text-blue-600">settings</span>
                                Account Information
                            </h2>
                            <p class="text-sm text-gray-600 mt-1">View your account details</p>
                        </div>
                        
                        <div class="p-6 space-y-6">
                            
                            <!-- Account Created -->
                            <div class="flex items-start justify-between p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-blue-100 rounded-lg">
                                        <span class="material-symbols-outlined text-blue-600">schedule</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">Account Created</p>
                                        <p class="text-sm text-gray-600"><?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Last Login -->
                            <div class="flex items-start justify-between p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-green-100 rounded-lg">
                                        <span class="material-symbols-outlined text-green-600">login</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">Last Login</p>
                                        <p class="text-sm text-gray-600"><?php echo $user['last_login'] ? date('F d, Y h:i A', strtotime($user['last_login'])) : 'Never'; ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Account Status -->
                            <div class="flex items-start justify-between p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-<?php echo $user['is_active'] ? 'green' : 'red'; ?>-100 rounded-lg">
                                        <span class="material-symbols-outlined text-<?php echo $user['is_active'] ? 'green' : 'red'; ?>-600">
                                            <?php echo $user['is_active'] ? 'check_circle' : 'cancel'; ?>
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">Account Status</p>
                                        <p class="text-sm text-gray-600"><?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- User ID -->
                            <div class="flex items-start justify-between p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-purple-100 rounded-lg">
                                        <span class="material-symbols-outlined text-purple-600">fingerprint</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">User ID</p>
                                        <p class="text-sm text-gray-600 font-mono">#<?php echo str_pad($user['id'], 4, '0', STR_PAD_LEFT); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                    <?php endif; ?>
                    
                </div>
                
            </div>
            
        </main>
    </div>
</div>

</body>
</html>