<!-- Admin Sidebar Component -->
 
<aside class="w-64 bg-primary text-white flex-shrink-0 overflow-y-auto">
    <!-- Logo -->
    <div class="p-6 border-b border-white/10">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gold rounded-lg flex items-center justify-center">
                <span class="material-symbols-outlined text-primary">school</span>
            </div>
            <div>
                <h1 class="font-bold text-sm">G.O.L.A.</h1>
                <p class="text-xs text-slate-300">ADMIN PORTAL</p>
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <nav class="p-4 space-y-6">
        
        <!-- Dashboard -->
        <div>
            <a href="dashboard.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                <span class="material-symbols-outlined text-xl">dashboard</span>
                <span class="font-medium">Overview</span>
            </a>
        </div>
        
        <!-- Management Section -->
        <div>
            <p class="px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Management</p>
            <div class="space-y-1">
                <a href="manage_students.php" class="sidebar-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['manage_students.php','add_student.php','edit_student.php','view_student.php']) ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">group</span>
                    <span class="font-medium">Students</span>
                </a>
                <!--<a href="manage_staff.php" class="sidebar-link <?php //echo basename($_SERVER['PHP_SELF']) == 'manage_staff.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">badge</span>
                    <span class="font-medium">Staff Records</span>
                </a>-->
            </div>
        </div>
        
        <!-- Academics Section -->
        <div>
            <p class="px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Academics</p>
            <div class="space-y-1">
                <a href="manage_results.php" class="sidebar-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['manage_results.php','enter_results.php']) ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">assignment</span>
                    <span class="font-medium">Results</span>
                </a>
                <a href="manage_scratch_cards.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_scratch_cards.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">confirmation_number</span>
                    <span class="font-medium">Scratch Cards</span>
                </a>

                <!-- Admissions sub-link -->
                <a href="manage_admissions.php" class="sidebar-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['manage_admissions.php']) ? 'active' : ''; ?> flex items-center gap-3 pl-8 pr-4 py-2.5 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-base">how_to_reg</span>
                    <span class="font-medium text-sm">Applications</span>
                </a>
            </div>
        </div>
        
        <!-- News & Content Section -->
        <div>
            <p class="px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Content</p>
            <div class="space-y-1">
                <a href="manage-news.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage-news.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">newspaper</span>
                    <span class="font-medium">News Articles</span>
                </a>
                <a href="add-news.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'add-news.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">add_circle</span>
                    <span class="font-medium">Add News</span>
                </a>
                <a href="manage-gallery.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage-gallery.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">photo_library</span>
                    <span class="font-medium">Gallery Images</span>
                </a>
                <a href="add-gallery-image.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'add-gallery-image.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">add_photo_alternate</span>
                    <span class="font-medium">Upload Image</span>
                </a>
            </div>
        </div>
        
        <!-- Events & Departments Section -->
        <div>
            <p class="px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Events & Departments</p>
            <div class="space-y-1">
                <a href="manage-events.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage-events.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">event</span>
                    <span class="font-medium">Academic Events</span>
                </a>
                <a href="add-event.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'add-event.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">event_available</span>
                    <span class="font-medium">Add Event</span>
                </a>
                <a href="manage-subjects.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage-subjects.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">menu_book</span>
                    <span class="font-medium">Subjects</span>
                </a>
                <a href="manage-departments.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage-departments.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">school</span>
                    <span class="font-medium">Departments</span>
                </a>
            </div>
        </div>
        
        <!-- 🆕 Admin Users Section (NEW!) -->
        <?php if (isset($admin_role) && in_array($admin_role, ['super_admin', 'admin'])): ?>
        <div>
            <p class="px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Administration</p>
            <div class="space-y-1">
                <a href="manage-admin-users.php" class="sidebar-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['manage-admin-users.php', 'add-admin-user.php', 'edit-admin-user.php']) ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">admin_panel_settings</span>
                    <span class="font-medium">Admin Users</span>
                </a>
                <a href="add-admin-user.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'add-admin-user.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">person_add</span>
                    <span class="font-medium">Add Admin User</span>
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- System -->
        <div>
            <p class="px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">System</p>
            <div class="space-y-1">
                <a href="settings.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">settings</span>
                    <span class="font-medium">Settings</span>
                </a>
                <a href="manage_settings.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_settings.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">tune</span>
                    <span class="font-medium">School Settings</span>
                </a>
            </div>
        </div>
        
    </nav>
</aside>

<style>
/* Active sidebar link styling */
.sidebar-link.active {
    background: rgba(255, 255, 255, 0.1);
    border-left: 4px solid var(--gold, #C5A059);
    padding-left: calc(1rem - 4px);
}
</style>