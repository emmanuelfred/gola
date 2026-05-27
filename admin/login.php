<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT id, username, password, full_name, email, role, is_active FROM admin_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if (!$user['is_active']) {
                $error = "Your account has been deactivated. Please contact the administrator.";
            } //elseif (password_verify($password, $user['password'])) {
            elseif ($password == $user['password']) {
                // Login successful
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_name'] = $user['full_name'];
                $_SESSION['admin_role'] = $user['role'];
                
                // Update last login
                $update_stmt = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                $update_stmt->bind_param("i", $user['id']);
                $update_stmt->execute();
                
                // Log activity
                $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, 'login', 'User logged in', ?)");
                $ip = $_SERVER['REMOTE_ADDR'];
                $log_stmt->bind_param("is", $user['id'], $ip);
                $log_stmt->execute();
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Please enter both username and password.";
    }
}

$page_title = "Admin Login";
include '../includes/header.php';
?>

<!-- Login Section -->
<section class="min-h-screen flex items-center justify-center py-12 px-4 bg-gradient-to-br from-primary via-slate-800 to-slate-900">
    <div class="max-w-md w-full">
        <!-- Login Card -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl p-8 lg:p-10">
            <div class="text-center mb-8">
                <div class="inline-block p-4 bg-gold/10 rounded-full mb-4">
                    <span class="material-symbols-outlined text-5xl text-gold">admin_panel_settings</span>
                </div>
                <h1 class="text-3xl font-display font-black text-primary dark:text-gold mb-2">Admin Portal</h1>
                <p class="text-slate-600 dark:text-slate-400">Sign in to access the management system</p>
            </div>
            
            <?php if (!empty($error)): ?>
            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg flex items-start gap-3">
                <span class="material-symbols-outlined text-red-500 flex-shrink-0">error</span>
                <p class="text-red-700 dark:text-red-400 text-sm"><?php echo htmlspecialchars($error); ?></p>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="space-y-6">
                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        Username <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-slate-400">person</span>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            required 
                            autofocus
                            class="w-full pl-12 pr-4 py-3 border-2 border-slate-200 dark:border-slate-700 rounded-xl focus:border-gold focus:ring-2 focus:ring-gold/20 dark:bg-slate-700 dark:text-white transition-all"
                            placeholder="Enter your username"
                        >
                    </div>
                </div>
                
                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        Password <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-slate-400">lock</span>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required 
                            class="w-full pl-12 pr-4 py-3 border-2 border-slate-200 dark:border-slate-700 rounded-xl focus:border-gold focus:ring-2 focus:ring-gold/20 dark:bg-slate-700 dark:text-white transition-all"
                            placeholder="Enter your password"
                        >
                    </div>
                </div>
                
                <!-- Remember Me -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="w-4 h-4 rounded border-slate-300 text-primary focus:ring-gold">
                        <span class="text-sm text-slate-600 dark:text-slate-400">Remember me</span>
                    </label>
                    <a href="#" class="text-sm text-gold hover:underline">Forgot password?</a>
                </div>
                
                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-3 px-6 rounded-xl transition-all flex items-center justify-center gap-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                >
                    <span class="material-symbols-outlined">login</span>
                    Sign In
                </button>
            </form>
            
            <!-- Default Credentials Info (Remove in production) -->
            <div class="mt-6 p-4 bg-gold/10 border border-gold/20 rounded-xl">
                <div class="flex gap-3">
                    <span class="material-symbols-outlined text-gold flex-shrink-0 text-sm">info</span>
                    <div class="text-xs">
                        <p class="font-semibold text-primary dark:text-gold mb-1">Default Login (for testing)</p>
                        <p class="text-slate-600 dark:text-slate-400">Username: <code class="bg-slate-200 dark:bg-slate-700 px-2 py-0.5 rounded">admin</code></p>
                        <p class="text-slate-600 dark:text-slate-400">Password: <code class="bg-slate-200 dark:bg-slate-700 px-2 py-0.5 rounded">admin123</code></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Back to Website -->
        <div class="text-center mt-6">
            <a href="../index.php" class="inline-flex items-center gap-2 text-white hover:text-gold transition-colors">
                <span class="material-symbols-outlined">arrow_back</span>
                Back to Website
            </a>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
