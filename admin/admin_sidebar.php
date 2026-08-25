<!-- Admin Sidebar Component -->
<?php require_once 'includes/permission_helper.php'; ?>

<!-- Mobile backdrop — tapping it closes the sidebar -->
<div id="sidebarBackdrop" onclick="closeSidebar()" class="hidden fixed inset-0 bg-black/50 z-40 lg:hidden"></div>

<aside id="adminSidebar" class="fixed lg:static inset-y-0 left-0 z-50 w-64 bg-primary text-white flex-shrink-0 overflow-y-auto transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
    <!-- Logo -->
    <div class="p-6 border-b border-white/10">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gold rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-primary">school</span>
                </div>
                <div>
                    <h1 class="font-bold text-sm">G.O.L.A.</h1>
                    <p class="text-xs text-slate-300">ADMIN PORTAL</p>
                </div>
            </div>
            <!-- Close button — mobile only -->
            <button onclick="closeSidebar()" class="lg:hidden p-1.5 hover:bg-white/10 rounded-lg transition-all" aria-label="Close menu">
                <span class="material-symbols-outlined text-xl">close</span>
            </button>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="p-4 space-y-6">

        <!-- Overview (always visible) -->
        <div>
            <a href="dashboard.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                <span class="material-symbols-outlined text-xl">dashboard</span>
                <span class="font-medium">Overview</span>
            </a>
            <?php if (userCan('my_class')): ?>
            <a href="form_class.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'form_class.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                <span class="material-symbols-outlined text-xl">groups</span>
                <span class="font-medium">My Class</span>
            </a>
            <?php endif; ?>
        </div>

        <!-- Students & Admissions -->
        <?php if (userCan('students') || userCan('admissions')): ?>
        <div>
            <p class="px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Students & Admissions</p>
            <div class="space-y-1">
                <?php if (userCan('students')): ?>
                <a href="manage_students.php" class="sidebar-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['manage_students.php','add_student.php','edit_student.php','view_student.php']) ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">group</span>
                    <span class="font-medium">Students</span>
                </a>
                <a href="manage_parents.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='manage_parents.php'?'active':''; ?> flex items-center gap-3 pl-8 pr-4 py-2.5 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-base">family_restroom</span>
                    <span class="font-medium text-sm">Parents & Comms</span>
                </a>
                <?php endif; ?>
                <?php if (userCan('admissions')): ?>
                <a href="manage_admissions.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='manage_admissions.php'?'active':''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">how_to_reg</span>
                    <span class="font-medium">Admissions</span>
                </a>
                <a href="manage_prospectus_requests.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='manage_prospectus_requests.php'?'active':''; ?> flex items-center gap-3 pl-8 pr-4 py-2.5 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-base">description</span>
                    <span class="font-medium text-sm">Prospectus Requests</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Classes & Academics -->
        <?php if (userCan('classes') || userCan('timetable') || userCan('results') || userCan('scratch_cards')): ?>
        <div>
            <p class="px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Classes & Academics</p>
            <div class="space-y-1">
                <?php if (userCan('classes')): ?>
                <a href="manage_classes.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='manage_classes.php'?'active':''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">school</span>
                    <span class="font-medium">Classes</span>
                </a>
                <a href="class_roster.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='class_roster.php'?'active':''; ?> flex items-center gap-3 pl-8 pr-4 py-2.5 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-base">how_to_reg</span>
                    <span class="font-medium text-sm">Class Roster</span>
                </a>
                <?php endif; ?>
                <?php if (userCan('results')): ?>
                <a href="manage_class_subjects.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='manage_class_subjects.php'?'active':''; ?> flex items-center gap-3 <?php echo userCan('classes') ? 'pl-8 pr-4 py-2.5' : 'px-4 py-3'; ?> rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined <?php echo userCan('classes') ? 'text-base' : 'text-xl'; ?>">menu_book</span>
                    <span class="font-medium <?php echo userCan('classes') ? 'text-sm' : ''; ?>">Class Subjects & Teachers</span>
                </a>
                <?php endif; ?>
                <?php if (userCan('timetable')): ?>
                <a href="manage_timetable.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='manage_timetable.php'?'active':''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">calendar_view_week</span>
                    <span class="font-medium">Timetable</span>
                </a>
                <a href="manage_timetable_periods.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='manage_timetable_periods.php'?'active':''; ?> flex items-center gap-3 pl-8 pr-4 py-2.5 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-base">schedule</span>
                    <span class="font-medium text-sm">Timetable Periods</span>
                </a>
                <?php endif; ?>
                <?php if (userCan('results')): ?>
                <a href="manage_results.php" class="sidebar-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['manage_results.php','enter_results.php']) ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">assignment</span>
                    <span class="font-medium">Results</span>
                </a>
                <?php endif; ?>
                <?php if (userCan('scratch_cards')): ?>
                <a href="manage_scratch_cards.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_scratch_cards.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">confirmation_number</span>
                    <span class="font-medium">Scratch Cards</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Staff -->
        <?php if (userCan('staff')): ?>
        <div>
            <p class="px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Staff</p>
            <div class="space-y-1">
                <a href="manage_staff.php" class="sidebar-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['manage_staff.php','add_staff.php','edit_staff.php','view_staff.php']) ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">badge</span>
                    <span class="font-medium">Staff Records</span>
                </a>
                <a href="manage_staff_roles.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='manage_staff_roles.php'?'active':''; ?> flex items-center gap-3 pl-8 pr-4 py-2.5 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-base">tune</span>
                    <span class="font-medium text-sm">Roles & Permissions</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Library -->
        <?php if (userCan('library')): ?>
        <div>
            <p class="px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Library</p>
            <div class="space-y-1">
                <a href="library_circulation.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='library_circulation.php'?'active':''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">local_library</span>
                    <span class="font-medium">Circulation</span>
                </a>
                <a href="manage_library_books.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='manage_library_books.php'?'active':''; ?> flex items-center gap-3 pl-8 pr-4 py-2.5 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-base">menu_book</span>
                    <span class="font-medium text-sm">Book Catalog</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Canteen -->
        <?php if (userCan('canteen')): ?>
        <div>
            <p class="px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Canteen</p>
            <div class="space-y-1">
                <a href="canteen_pos.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='canteen_pos.php'?'active':''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">point_of_sale</span>
                    <span class="font-medium">Sell</span>
                </a>
                <a href="canteen_tabs.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='canteen_tabs.php'?'active':''; ?> flex items-center gap-3 pl-8 pr-4 py-2.5 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-base">receipt_long</span>
                    <span class="font-medium text-sm">Student Tabs</span>
                </a>
                <a href="manage_canteen_items.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='manage_canteen_items.php'?'active':''; ?> flex items-center gap-3 pl-8 pr-4 py-2.5 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-base">inventory_2</span>
                    <span class="font-medium text-sm">Items & Stock</span>
                </a>
                <a href="canteen_reports.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='canteen_reports.php'?'active':''; ?> flex items-center gap-3 pl-8 pr-4 py-2.5 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-base">insights</span>
                    <span class="font-medium text-sm">Reports</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Finance -->
        <?php if (userCan('fees') || userCan('payroll') || userCan('expenses') || userCan('inventory')): ?>
        <div>
            <p class="px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Finance</p>
            <div class="space-y-1">
                <?php if (userCan('fees')): ?>
                <a href="fee_collection.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='fee_collection.php'?'active':''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">payments</span>
                    <span class="font-medium">Collect Fees</span>
                </a>
                <a href="manage_fee_structure.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='manage_fee_structure.php'?'active':''; ?> flex items-center gap-3 pl-8 pr-4 py-2.5 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-base">receipt_long</span>
                    <span class="font-medium text-sm">Fee Structure</span>
                </a>
                <a href="fee_reports.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='fee_reports.php'?'active':''; ?> flex items-center gap-3 pl-8 pr-4 py-2.5 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-base">insights</span>
                    <span class="font-medium text-sm">Fee Reports</span>
                </a>
                <?php endif; ?>
                <?php if (userCan('payroll')): ?>
                <a href="manage_payroll.php" class="sidebar-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['manage_payroll.php','payroll_run.php']) ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">account_balance_wallet</span>
                    <span class="font-medium">Payroll</span>
                </a>
                <?php endif; ?>
                <?php if (userCan('expenses')): ?>
                <a href="manage_expenses.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='manage_expenses.php'?'active':''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">receipt</span>
                    <span class="font-medium">Expenses</span>
                </a>
                <?php endif; ?>
                <?php if (userCan('inventory')): ?>
                <a href="manage_inventory.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='manage_inventory.php'?'active':''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">inventory</span>
                    <span class="font-medium">Inventory</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Website Content -->
        <?php if (userCan('content') || userCan('events_departments')): ?>
        <div>
            <p class="px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Website Content</p>
            <div class="space-y-1">
                <?php if (userCan('content')): ?>
                <a href="manage-news.php" class="sidebar-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['manage-news.php', 'add-news.php']) ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">newspaper</span>
                    <span class="font-medium">News Articles</span>
                </a>
                <a href="manage-gallery.php" class="sidebar-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['manage-gallery.php', 'add-gallery-image.php']) ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">photo_library</span>
                    <span class="font-medium">Gallery Images</span>
                </a>
                <?php endif; ?>
                <?php if (userCan('events_departments')): ?>
                <a href="manage-events.php" class="sidebar-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['manage-events.php', 'add-event.php']) ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">event</span>
                    <span class="font-medium">Academic Events</span>
                </a>
                <a href="manage-subjects.php" class="sidebar-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['manage-subjects.php', 'add-subject.php']) ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">menu_book</span>
                    <span class="font-medium">Public Curriculum</span>
                </a>
                <a href="manage-departments.php" class="sidebar-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['manage-departments.php', 'add-department.php']) ? 'active' : ''; ?> flex items-center gap-3 pl-8 pr-4 py-2.5 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-base">school</span>
                    <span class="font-medium text-sm">Departments</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Administration — deliberately NOT tied to role_permissions.
             Granting this is equivalent to granting the power to create more
             admins, so it stays hardcoded to the super_admin/admin tier. -->
        <?php if (isset($admin_role) && in_array($admin_role, ['super_admin', 'admin'])): ?>
        <div>
            <p class="px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Administration</p>
            <div class="space-y-1">
                <a href="manage-admin-users.php" class="sidebar-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['manage-admin-users.php', 'add-admin-user.php', 'edit-admin-user.php']) ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">admin_panel_settings</span>
                    <span class="font-medium">Admin Users</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Personal account settings — always visible, not permission-gated -->
        <div>
            <p class="px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">System</p>
            <div class="space-y-1">
                <a href="settings.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">settings</span>
                    <span class="font-medium">My Account</span>
                </a>
                <?php if (userCan('settings')): ?>
                <a href="manage_settings.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_settings.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-xl">tune</span>
                    <span class="font-medium">School Settings</span>
                </a>
                <?php endif; ?>
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

<script>
function openSidebar() {
    document.getElementById('adminSidebar').classList.remove('-translate-x-full');
    document.getElementById('sidebarBackdrop').classList.remove('hidden');
    document.body.classList.add('overflow-hidden', 'lg:overflow-visible');
}
function closeSidebar() {
    document.getElementById('adminSidebar').classList.add('-translate-x-full');
    document.getElementById('sidebarBackdrop').classList.add('hidden');
    document.body.classList.remove('overflow-hidden', 'lg:overflow-visible');
}
function toggleSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    if (sidebar.classList.contains('-translate-x-full')) {
        openSidebar();
    } else {
        closeSidebar();
    }
}
// Close automatically if the viewport is resized up to desktop width
// while the mobile drawer happens to be open.
window.addEventListener('resize', function() {
    if (window.innerWidth >= 1024) closeSidebar();
});
</script>