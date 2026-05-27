<?php
require_once '../config/database.php';
require_once 'auth_check.php'; // This already has session_start()

$success_message = '';
$error_message = '';

// Get current admin info from session
$admin_name = $_SESSION['full_name'] ?? $_SESSION['admin_name'] ?? 'Admin';
$admin_email = $_SESSION['email'] ?? $_SESSION['admin_email'] ?? '';
$admin_role = $_SESSION['role'] ?? $_SESSION['admin_role'] ?? 'teacher';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'teacher';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    if (empty($username) || empty($password) || empty($full_name) || empty($email)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (strlen($username) < 4) {
        $error_message = 'Username must be at least 4 characters long.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        // Check if username already exists
        $check_username = "SELECT id FROM admin_users WHERE username = ?";
        $stmt = $conn->prepare($check_username);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error_message = 'Username already exists. Please choose a different username.';
        } else {
            // Check if email already exists
            $check_email = "SELECT id FROM admin_users WHERE email = ?";
            $stmt = $conn->prepare($check_email);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                $error_message = 'Email already exists. Please use a different email.';
            } else {
                // Hash password (matching your existing format)
                $hashed_password = $password; // Change to: password_hash($password, PASSWORD_DEFAULT) for production
                
                // Insert new admin user
                $insert_query = "INSERT INTO admin_users (username, password, full_name, email, role, is_active) 
                                VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("sssssi", $username, $hashed_password, $full_name, $email, $role, $is_active);
                
                if ($stmt->execute()) {
                    $success_message = "Admin user created successfully! Username: <strong>$username</strong>";
                    // Clear form
                    $_POST = [];
                } else {
                    $error_message = 'Failed to create admin user: ' . $conn->error;
                }
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
    <title>Add Admin User - Goodness Omogo Leadership Academy</title>
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
                        <h1 class="text-3xl font-bold text-gray-900">Add Admin User</h1>
                        <p class="text-gray-600 mt-2">Create a new administrator account</p>
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
                    <div>
                        <p class="font-semibold">Success!</p>
                        <p><?php echo $success_message; ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Error Message -->
            <?php if ($error_message): ?>
            <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-red-500">error</span>
                    <div>
                        <p class="font-semibold">Error</p>
                        <p><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Add User Form -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                
                <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-blue-600 to-blue-700">
                    <div class="flex items-center gap-3 text-white">
                        <div class="p-3 bg-white/20 rounded-lg">
                            <span class="material-symbols-outlined text-3xl">person_add</span>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold">User Information</h2>
                            <p class="text-blue-100 text-sm">Fill in the details below</p>
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
                            <!-- Username -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Username <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    name="username" 
                                    required 
                                    minlength="4"
                                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                    placeholder="e.g., john.doe"
                                >
                                <p class="mt-1 text-xs text-gray-500">Minimum 4 characters, no spaces</p>
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
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                    placeholder="user@goodnessomogo.edu.ng"
                                >
                            </div>
                            
                            <!-- Password -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Password <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="password" 
                                    name="password" 
                                    required
                                    minlength="6"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                    placeholder="••••••••"
                                >
                                <p class="mt-1 text-xs text-gray-500">Minimum 6 characters</p>
                            </div>
                            
                            <!-- Confirm Password -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Confirm Password <span class="text-red-500">*</span>
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
                                    value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                    placeholder="e.g., John Doe Smith"
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
                                    <option value="teacher" <?php echo (isset($_POST['role']) && $_POST['role'] === 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                                    <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    <option value="super_admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'super_admin') ? 'selected' : ''; ?>>Super Admin</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">
                                    <strong>Teacher:</strong> Can enter results<br>
                                    <strong>Admin:</strong> Full access except system settings<br>
                                    <strong>Super Admin:</strong> Complete system access
                                </p>
                            </div>
                            
                            <!-- Account Status -->
                            <div class="flex items-start pt-8">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input 
                                        type="checkbox" 
                                        name="is_active" 
                                        value="1"
                                        checked
                                        class="w-5 h-5 text-blue-600 rounded border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    >
                                    <div>
                                        <span class="text-sm font-semibold text-gray-700">Active Account</span>
                                        <p class="text-xs text-gray-500">User can login immediately</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200">
                        <a href="manage-admin-users.php" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                            Cancel
                        </a>
                        <button 
                            type="reset" 
                            class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition"
                        >
                            Reset Form
                        </button>
                        <button 
                            type="submit" 
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2 shadow-lg hover:shadow-xl"
                        >
                            <span class="material-symbols-outlined">person_add</span>
                            Create Admin User
                        </button>
                    </div>
                    
                </form>
                
            </div>
            
            <!-- Info Card -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex gap-3">
                    <span class="material-symbols-outlined text-blue-600">info</span>
                    <div class="text-sm text-blue-800">
                        <p class="font-semibold mb-1">Important Notes:</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Username must be unique and cannot be changed later</li>
                            <li>Password should be at least 6 characters for security</li>
                            <li>Super Admin role should be assigned carefully</li>
                            <li>Inactive accounts cannot login to the system</li>
                        </ul>
                    </div>
                </div>
            </div>
            
        </main>
    </div>
</div>

</body>
</html>