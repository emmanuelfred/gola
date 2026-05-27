<?php
require_once 'auth_check.php';

// Fetch statistics
$stats = [];

// Total students
$result = $conn->query("SELECT COUNT(*) as total FROM students WHERE status = 'Active'");
$stats['total_students'] = $result->fetch_assoc()['total'];

// Total teachers
$result = $conn->query("SELECT COUNT(*) as total FROM admin_users WHERE role IN ('admin', 'teacher') AND is_active = TRUE");
$stats['total_teachers'] = $result->fetch_assoc()['total'];

// Total classes
$result = $conn->query("SELECT COUNT(*) as total FROM classes");
$stats['total_classes'] = $result->fetch_assoc()['total'];

// Total news articles
$result = $conn->query("SELECT COUNT(*) as total FROM news_articles WHERE is_published = TRUE");
$stats['total_news'] = $result->fetch_assoc()['total'];

// Current session and term
$result = $conn->query("SELECT session_name FROM academic_sessions WHERE is_current = TRUE LIMIT 1");
$current_session = $result->fetch_assoc();

$result = $conn->query("SELECT term_name FROM terms WHERE is_current = TRUE LIMIT 1");
$current_term = $result->fetch_assoc();

// Recent activities
$activities_stmt = $conn->prepare("
    SELECT al.*, au.full_name 
    FROM activity_logs al 
    JOIN admin_users au ON al.user_id = au.id 
    ORDER BY al.created_at DESC 
    LIMIT 8
");
$activities_stmt->execute();
$recent_activities = $activities_stmt->get_result();

$page_title = "Admin Dashboard";
?>
<!DOCTYPE html>
<html class="scroll-smooth" lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | G.O.L.A</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: "#0A2E4D",
                        gold: "#C5A059",
                    },
                    fontFamily: {
                        sans: ["Inter", "sans-serif"],
                    },
                },
            },
        };
    </script>
    
    <style>
        .sidebar-link.active {
            background: linear-gradient(90deg, rgba(197, 160, 89, 0.1) 0%, transparent 100%);
            border-left: 3px solid #C5A059;
            color: #C5A059;
        }
    </style>
</head>
<body class="bg-slate-50 font-sans">

<div class="flex h-screen overflow-hidden">
    
    <!-- Sidebar -->
    <?php include 'admin_sidebar.php'; ?>
    
    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col overflow-hidden">
        
        <!-- Top Bar -->
        <?php include 'admin_topbar.php'; ?>
        
        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto p-8">
            
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-slate-900 mb-2">Dashboard Overview</h1>
                <p class="text-slate-600">Welcome back. Here is what's happening today at the Academy.</p>
            </div>
            
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                
                <!-- Total Students -->
                <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-lg transition-shadow">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
                            <span class="material-symbols-outlined text-blue-600 text-2xl">groups</span>
                        </div>
                        <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded">+4.2%</span>
                    </div>
                    <h3 class="text-slate-600 text-sm font-medium mb-1">Total Students</h3>
                    <p class="text-3xl font-bold text-slate-900"><?php echo number_format($stats['total_students']); ?></p>
                    <p class="text-xs text-slate-500 mt-2">Current Active Enrollments</p>
                </div>
                
                <!-- Total Faculty -->
                <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-lg transition-shadow">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 bg-purple-50 rounded-lg flex items-center justify-center">
                            <span class="material-symbols-outlined text-purple-600 text-2xl">person</span>
                        </div>
                        <span class="text-xs font-semibold text-slate-600 bg-slate-50 px-2 py-1 rounded">Stable</span>
                    </div>
                    <h3 class="text-slate-600 text-sm font-medium mb-1">Total Faculty</h3>
                    <p class="text-3xl font-bold text-slate-900"><?php echo number_format($stats['total_teachers']); ?></p>
                    <p class="text-xs text-slate-500 mt-2">Certified Academic Staff</p>
                </div>
                
                <!-- Total Classes -->
                <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-lg transition-shadow">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center">
                            <span class="material-symbols-outlined text-green-600 text-2xl">class</span>
                        </div>
                        <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded">+12.5%</span>
                    </div>
                    <h3 class="text-slate-600 text-sm font-medium mb-1">Total Classes</h3>
                    <p class="text-3xl font-bold text-slate-900"><?php echo number_format($stats['total_classes']); ?></p>
                    <p class="text-xs text-slate-500 mt-2">Active Class Sections</p>
                </div>
                
                <!-- News Articles -->
                <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-lg transition-shadow">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 bg-gold/10 rounded-lg flex items-center justify-center">
                            <span class="material-symbols-outlined text-gold text-2xl">newspaper</span>
                        </div>
                        <span class="text-xs font-semibold text-gold bg-gold/10 px-2 py-1 rounded">PUBLISHED</span>
                    </div>
                    <h3 class="text-slate-600 text-sm font-medium mb-1">News Articles</h3>
                    <p class="text-3xl font-bold text-slate-900"><?php echo number_format($stats['total_news']); ?></p>
                    <p class="text-xs text-slate-500 mt-2">Published Articles</p>
                </div>
                
            </div>
            
            <!-- Recent Activity -->
            <div class="bg-white rounded-xl border border-slate-200">
                <div class="p-6 border-b border-slate-200">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Recent Activity</h2>
                            <p class="text-sm text-slate-600">Latest system activities and updates</p>
                        </div>
                        <a href="activity_logs.php" class="text-sm font-semibold text-gold hover:text-primary transition-colors">
                            View All
                        </a>
                    </div>
                </div>
                
                <div class="divide-y divide-slate-100">
                    <?php if ($recent_activities->num_rows > 0): ?>
                        <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                        <div class="p-6 hover:bg-slate-50 transition-colors">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 bg-gold/10 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <span class="material-symbols-outlined text-gold text-xl">history</span>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-slate-900">
                                        <?php echo htmlspecialchars($activity['full_name']); ?>
                                    </p>
                                    <p class="text-sm text-slate-600 mt-1">
                                        <?php echo htmlspecialchars($activity['description']); ?>
                                    </p>
                                    <p class="text-xs text-slate-500 mt-2">
                                        <?php echo date('M d, Y \a\t g:i A', strtotime($activity['created_at'])); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="p-12 text-center">
                            <span class="material-symbols-outlined text-6xl text-slate-300 mb-4">inbox</span>
                            <p class="text-slate-500">No recent activity</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </main>
        
        <!-- Footer -->
        <footer class="bg-white border-t border-slate-200 px-8 py-4">
            <div class="flex justify-between items-center text-sm text-slate-600">
                <p>© <?php echo date('Y'); ?> Goodness Omogo Leadership Academy. Academic Management System.</p>
                <div class="flex gap-4">
                    <a href="#" class="hover:text-gold transition-colors">Privacy Policy</a>
                    <a href="#" class="hover:text-gold transition-colors">Support</a>
                    <span>v2.4.0</span>
                </div>
            </div>
        </footer>
        
    </div>
</div>

</body>
</html>
