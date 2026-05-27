<!-- Admin Top Bar Component -->
<link rel="shortcut icon" href="../asset/favicon.png" type="image/x-icon">

<header class="bg-white border-b border-slate-200 px-8 py-4">
    <div class="flex justify-between items-center">
        <div class="flex-1 max-w-xl">
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                <input 
                    type="search" 
                    placeholder="Search students, staff or records..."
                    class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gold/20 focus:border-gold"
                >
            </div>
        </div>
        
        <div class="flex items-center gap-4">
            <!-- Notifications -->
            <button class="relative p-2 hover:bg-slate-50 rounded-lg transition-all">
                <span class="material-symbols-outlined text-slate-600">notifications</span>
                <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
            </button>
            
            <!-- User Profile Dropdown -->
            <div class="relative">
                <button 
                    onclick="toggleUserDropdown()" 
                    class="flex items-center gap-3 pl-4 border-l border-slate-200 hover:bg-slate-50 p-2 rounded-lg transition-all"
                >
                    <div class="text-right">
                        <p class="text-sm font-semibold text-slate-900"><?php echo htmlspecialchars($admin_name); ?></p>
                        <p class="text-xs text-slate-500 capitalize"><?php echo htmlspecialchars(str_replace('_', ' ', $admin_role)); ?></p>
                    </div>
                    <div class="w-10 h-10 bg-gold rounded-full flex items-center justify-center text-primary font-bold">
                        <?php echo strtoupper(substr($admin_name, 0, 2)); ?>
                    </div>
                    <span class="material-symbols-outlined text-slate-400 text-sm">expand_more</span>
                </button>
                
                <!-- Dropdown Menu -->
                <div id="userDropdown" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-2xl border border-slate-200 py-2 z-50">
                    <!-- User Info Header -->
                    <div class="px-4 py-3 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-gold rounded-full flex items-center justify-center text-primary font-bold text-lg">
                                <?php echo strtoupper(substr($admin_name, 0, 2)); ?>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($admin_name); ?></p>
                                <p class="text-xs text-slate-500"><?php echo htmlspecialchars($admin_email ?? ''); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Menu Items -->
                    <div class="py-2">
                        <a href="settings.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-all">
                            <span class="material-symbols-outlined text-slate-400">person</span>
                            <span>My Profile</span>
                        </a>
                        <a href="settings.php?tab=password" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-all">
                            <span class="material-symbols-outlined text-slate-400">lock</span>
                            <span>Change Password</span>
                        </a>
                        <a href="settings.php?tab=account" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-all">
                            <span class="material-symbols-outlined text-slate-400">settings</span>
                            <span>Account Settings</span>
                        </a>
                    </div>
                    
                    <!-- Admin Users (Only for Super Admin & Admin) -->
                    <?php if (isset($admin_role) && in_array($admin_role, ['super_admin', 'admin'])): ?>
                    <div class="border-t border-slate-100 py-2">
                        <a href="manage-admin-users.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-all">
                            <span class="material-symbols-outlined text-slate-400">admin_panel_settings</span>
                            <span>Manage Admin Users</span>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Logout -->
                    <div class="border-t border-slate-100 py-2">
                        <a href="logout.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-all">
                            <span class="material-symbols-outlined text-red-500">logout</span>
                            <span class="font-semibold">Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
function toggleUserDropdown() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('hidden');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('userDropdown');
    const button = event.target.closest('button[onclick="toggleUserDropdown()"]');
    
    if (!button && !dropdown.contains(event.target)) {
        dropdown.classList.add('hidden');
    }
});

// Close dropdown on escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.getElementById('userDropdown').classList.add('hidden');
    }
});
</script>

<style>
/* Smooth dropdown animation */
#userDropdown {
    animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>