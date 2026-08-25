<!-- Admin Top Bar Component -->
<?php
require_once 'includes/permission_helper.php';

// The searchable page list — mirrors the sidebar's own permission checks, so
// nobody gets suggested a page they'd immediately get blocked from. Also
// includes the "Add X" pages that were deliberately removed from the visual
// sidebar to reduce clutter — search is exactly where those belong instead.
$nav_items = [];
$nav_items[] = ['label'=>'Overview', 'url'=>'dashboard.php', 'icon'=>'dashboard', 'category'=>'General'];
if (userCan('my_class')) $nav_items[] = ['label'=>'My Class', 'url'=>'form_class.php', 'icon'=>'groups', 'category'=>'General'];

if (userCan('students')) {
    $nav_items[] = ['label'=>'Students', 'url'=>'manage_students.php', 'icon'=>'group', 'category'=>'Students & Admissions'];
    $nav_items[] = ['label'=>'Add Student', 'url'=>'add_student.php', 'icon'=>'person_add', 'category'=>'Students & Admissions'];
    $nav_items[] = ['label'=>'Parents & Comms', 'url'=>'manage_parents.php', 'icon'=>'family_restroom', 'category'=>'Students & Admissions'];
}
if (userCan('admissions')) {
    $nav_items[] = ['label'=>'Admissions', 'url'=>'manage_admissions.php', 'icon'=>'how_to_reg', 'category'=>'Students & Admissions'];
    $nav_items[] = ['label'=>'Prospectus Requests', 'url'=>'manage_prospectus_requests.php', 'icon'=>'description', 'category'=>'Students & Admissions'];
}
if (userCan('classes')) {
    $nav_items[] = ['label'=>'Classes', 'url'=>'manage_classes.php', 'icon'=>'school', 'category'=>'Classes & Academics'];
    $nav_items[] = ['label'=>'Class Roster', 'url'=>'class_roster.php', 'icon'=>'how_to_reg', 'category'=>'Classes & Academics'];
}
if (userCan('results')) {
    $nav_items[] = ['label'=>'Class Subjects & Teachers', 'url'=>'manage_class_subjects.php', 'icon'=>'menu_book', 'category'=>'Classes & Academics'];
    $nav_items[] = ['label'=>'Results', 'url'=>'manage_results.php', 'icon'=>'assignment', 'category'=>'Classes & Academics'];
}
if (userCan('timetable')) {
    $nav_items[] = ['label'=>'Timetable', 'url'=>'manage_timetable.php', 'icon'=>'calendar_view_week', 'category'=>'Classes & Academics'];
    $nav_items[] = ['label'=>'Timetable Periods', 'url'=>'manage_timetable_periods.php', 'icon'=>'schedule', 'category'=>'Classes & Academics'];
}
if (userCan('scratch_cards')) $nav_items[] = ['label'=>'Scratch Cards', 'url'=>'manage_scratch_cards.php', 'icon'=>'confirmation_number', 'category'=>'Classes & Academics'];

if (userCan('staff')) {
    $nav_items[] = ['label'=>'Staff Records', 'url'=>'manage_staff.php', 'icon'=>'badge', 'category'=>'Staff'];
    $nav_items[] = ['label'=>'Add Staff', 'url'=>'add_staff.php', 'icon'=>'person_add', 'category'=>'Staff'];
    $nav_items[] = ['label'=>'Roles & Permissions', 'url'=>'manage_staff_roles.php', 'icon'=>'tune', 'category'=>'Staff'];
}

if (userCan('library')) {
    $nav_items[] = ['label'=>'Library Circulation', 'url'=>'library_circulation.php', 'icon'=>'local_library', 'category'=>'Library'];
    $nav_items[] = ['label'=>'Book Catalog', 'url'=>'manage_library_books.php', 'icon'=>'menu_book', 'category'=>'Library'];
}

if (userCan('canteen')) {
    $nav_items[] = ['label'=>'Canteen — Sell', 'url'=>'canteen_pos.php', 'icon'=>'point_of_sale', 'category'=>'Canteen'];
    $nav_items[] = ['label'=>'Canteen — Student Tabs', 'url'=>'canteen_tabs.php', 'icon'=>'receipt_long', 'category'=>'Canteen'];
    $nav_items[] = ['label'=>'Canteen — Items & Stock', 'url'=>'manage_canteen_items.php', 'icon'=>'inventory_2', 'category'=>'Canteen'];
    $nav_items[] = ['label'=>'Canteen — Reports', 'url'=>'canteen_reports.php', 'icon'=>'insights', 'category'=>'Canteen'];
}

if (userCan('fees')) {
    $nav_items[] = ['label'=>'Collect Fees', 'url'=>'fee_collection.php', 'icon'=>'payments', 'category'=>'Finance'];
    $nav_items[] = ['label'=>'Fee Structure', 'url'=>'manage_fee_structure.php', 'icon'=>'receipt_long', 'category'=>'Finance'];
    $nav_items[] = ['label'=>'Fee Reports', 'url'=>'fee_reports.php', 'icon'=>'insights', 'category'=>'Finance'];
}
if (userCan('payroll')) $nav_items[] = ['label'=>'Payroll', 'url'=>'manage_payroll.php', 'icon'=>'account_balance_wallet', 'category'=>'Finance'];
if (userCan('expenses')) $nav_items[] = ['label'=>'Expenses', 'url'=>'manage_expenses.php', 'icon'=>'receipt', 'category'=>'Finance'];
if (userCan('inventory')) $nav_items[] = ['label'=>'Inventory', 'url'=>'manage_inventory.php', 'icon'=>'inventory', 'category'=>'Finance'];

if (userCan('content')) {
    $nav_items[] = ['label'=>'News Articles', 'url'=>'manage-news.php', 'icon'=>'newspaper', 'category'=>'Website Content'];
    $nav_items[] = ['label'=>'Add News', 'url'=>'add-news.php', 'icon'=>'add_circle', 'category'=>'Website Content'];
    $nav_items[] = ['label'=>'Gallery Images', 'url'=>'manage-gallery.php', 'icon'=>'photo_library', 'category'=>'Website Content'];
    $nav_items[] = ['label'=>'Upload Image', 'url'=>'add-gallery-image.php', 'icon'=>'add_photo_alternate', 'category'=>'Website Content'];
}
if (userCan('events_departments')) {
    $nav_items[] = ['label'=>'Academic Events', 'url'=>'manage-events.php', 'icon'=>'event', 'category'=>'Website Content'];
    $nav_items[] = ['label'=>'Add Event', 'url'=>'add-event.php', 'icon'=>'event_available', 'category'=>'Website Content'];
    $nav_items[] = ['label'=>'Public Curriculum', 'url'=>'manage-subjects.php', 'icon'=>'menu_book', 'category'=>'Website Content'];
    $nav_items[] = ['label'=>'Departments', 'url'=>'manage-departments.php', 'icon'=>'school', 'category'=>'Website Content'];
}

if (isset($admin_role) && in_array($admin_role, ['super_admin', 'admin'])) {
    $nav_items[] = ['label'=>'Admin Users', 'url'=>'manage-admin-users.php', 'icon'=>'admin_panel_settings', 'category'=>'Administration'];
    $nav_items[] = ['label'=>'Add Admin User', 'url'=>'add-admin-user.php', 'icon'=>'person_add', 'category'=>'Administration'];
}

$nav_items[] = ['label'=>'My Account', 'url'=>'settings.php', 'icon'=>'settings', 'category'=>'System'];
if (userCan('settings')) $nav_items[] = ['label'=>'School Settings', 'url'=>'manage_settings.php', 'icon'=>'tune', 'category'=>'System'];
?>
<link rel="shortcut icon" href="../asset/favicon.png" type="image/x-icon">

<header class="bg-white border-b border-slate-200 px-4 lg:px-8 py-4">
    <div class="flex justify-between items-center gap-3">
        <div class="flex items-center gap-3 flex-1 min-w-0">
            <!-- Hamburger menu — mobile only -->
            <button onclick="toggleSidebar()" class="lg:hidden p-2 hover:bg-slate-100 rounded-lg transition-all flex-shrink-0" aria-label="Open menu">
                <span class="material-symbols-outlined text-slate-700">menu</span>
            </button>
            <div class="flex-1 max-w-xl min-w-0">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">search</span>
                    <input 
                        type="search" 
                        id="quickNavSearch"
                        placeholder="Jump to a page…"
                        autocomplete="off"
                        class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gold/20 focus:border-gold"
                    >
                    <!-- Results dropdown -->
                    <div id="quickNavResults" class="hidden absolute left-0 right-0 mt-2 bg-white rounded-xl shadow-2xl border border-slate-200 py-2 z-50 max-h-96 overflow-y-auto"></div>
                </div>
            </div>
        </div>
        
        <div class="flex items-center gap-2 lg:gap-4 flex-shrink-0">
            <!-- Notifications -->
            <button class="relative p-2 hover:bg-slate-50 rounded-lg transition-all">
                <span class="material-symbols-outlined text-slate-600">notifications</span>
                <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
            </button>
            
            <!-- User Profile Dropdown -->
            <div class="relative">
                <button 
                    onclick="toggleUserDropdown()" 
                    class="flex items-center gap-2 lg:gap-3 lg:pl-4 lg:border-l border-slate-200 hover:bg-slate-50 p-2 rounded-lg transition-all"
                >
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-semibold text-slate-900"><?php echo htmlspecialchars($admin_name); ?></p>
                        <p class="text-xs text-slate-500 capitalize"><?php echo htmlspecialchars(str_replace('_', ' ', $admin_role)); ?></p>
                    </div>
                    <div class="w-10 h-10 bg-gold rounded-full flex items-center justify-center text-primary font-bold flex-shrink-0">
                        <?php echo strtoupper(substr($admin_name, 0, 2)); ?>
                    </div>
                    <span class="material-symbols-outlined text-slate-400 text-sm hidden sm:inline">expand_more</span>
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

// ── Quick-nav search ─────────────────────────────────────────────────────
// Client-side only — filters the list of pages the current user is allowed
// to see (built server-side with the same permission checks as the
// sidebar) and lets them jump straight there. Not a data search.
const QUICK_NAV_ITEMS = <?php echo json_encode($nav_items); ?>;

(function() {
    const input = document.getElementById('quickNavSearch');
    const resultsBox = document.getElementById('quickNavResults');
    let activeIndex = -1;
    let currentMatches = [];

    function render(matches) {
        currentMatches = matches;
        activeIndex = matches.length ? 0 : -1;
        if (matches.length === 0) {
            resultsBox.innerHTML = '<p class="px-4 py-3 text-sm text-slate-400">No matching page found.</p>';
            resultsBox.classList.remove('hidden');
            return;
        }
        resultsBox.innerHTML = matches.map((item, i) => `
            <a href="${item.url}" data-index="${i}"
               class="quick-nav-item flex items-center gap-3 px-4 py-2.5 text-sm transition-all ${i === activeIndex ? 'bg-gold/10 text-primary' : 'text-slate-700 hover:bg-slate-50'}">
                <span class="material-symbols-outlined text-slate-400 text-lg">${item.icon}</span>
                <span class="flex-1">${item.label}</span>
                <span class="text-xs text-slate-400">${item.category}</span>
            </a>
        `).join('');
        resultsBox.classList.remove('hidden');
    }

    function updateHighlight() {
        resultsBox.querySelectorAll('.quick-nav-item').forEach((el, i) => {
            el.classList.toggle('bg-gold/10', i === activeIndex);
            el.classList.toggle('text-primary', i === activeIndex);
            el.classList.toggle('text-slate-700', i !== activeIndex);
        });
        const activeEl = resultsBox.querySelector(`.quick-nav-item[data-index="${activeIndex}"]`);
        if (activeEl) activeEl.scrollIntoView({ block: 'nearest' });
    }

    function closeResults() {
        resultsBox.classList.add('hidden');
        activeIndex = -1;
    }

    input.addEventListener('input', function() {
        const q = this.value.trim().toLowerCase();
        if (!q) { closeResults(); return; }
        const matches = QUICK_NAV_ITEMS.filter(item =>
            item.label.toLowerCase().includes(q) || item.category.toLowerCase().includes(q)
        ).slice(0, 8);
        render(matches);
    });

    input.addEventListener('focus', function() {
        if (this.value.trim() && currentMatches.length) resultsBox.classList.remove('hidden');
    });

    input.addEventListener('keydown', function(e) {
        if (resultsBox.classList.contains('hidden') || currentMatches.length === 0) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, currentMatches.length - 1);
            updateHighlight();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            updateHighlight();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (activeIndex >= 0 && currentMatches[activeIndex]) {
                window.location.href = currentMatches[activeIndex].url;
            }
        } else if (e.key === 'Escape') {
            closeResults();
            input.blur();
        }
    });

    document.addEventListener('click', function(event) {
        if (!input.contains(event.target) && !resultsBox.contains(event.target)) {
            closeResults();
        }
    });
})();
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