<?php
require_once 'auth_check.php';

// Start session for messages
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$success = '';
$error = '';

// Get messages from session
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    $delete_stmt = $conn->prepare("DELETE FROM curriculum_subjects WHERE id = ?");
    $delete_stmt->bind_param("i", $id);
    
    if ($delete_stmt->execute()) {
        $_SESSION['success'] = "Subject deleted successfully!";
        if (function_exists('logActivity')) {
            logActivity('delete_subject', "Deleted subject ID: $id");
        }
    } else {
        $_SESSION['error'] = "Error deleting subject: " . $conn->error;
    }
    $delete_stmt->close();
    
    header('Location: manage-subjects.php');
    exit();
}

// Handle toggle active status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $conn->prepare("UPDATE curriculum_subjects SET is_active = NOT is_active WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Subject status updated!";
    } else {
        $_SESSION['error'] = "Error updating status: " . $conn->error;
    }
    $stmt->close();
    
    header('Location: manage-subjects.php');
    exit();
}

// Filter by category
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$level_filter = isset($_GET['level']) ? $_GET['level'] : '';

// Build query
$where_clauses = [];
if (!empty($category_filter)) {
    $where_clauses[] = "category = '" . $conn->real_escape_string($category_filter) . "'";
}
if (!empty($level_filter)) {
    $where_clauses[] = "class_level = '" . $conn->real_escape_string($level_filter) . "'";
}
$where_clause = !empty($where_clauses) ? " WHERE " . implode(' AND ', $where_clauses) : "";

// Get total count
$count_query = "SELECT COUNT(*) as total FROM curriculum_subjects" . $where_clause;
$count_result = $conn->query($count_query);
$total_subjects = $count_result->fetch_assoc()['total'];

// Fetch subjects
$query = "SELECT * FROM curriculum_subjects" . $where_clause . " ORDER BY category, display_order ASC, subject_name ASC";
$result = $conn->query($query);

// Get counts by category
$category_counts = [];
$count_query = "SELECT category, COUNT(*) as count FROM curriculum_subjects GROUP BY category";
$count_result = $conn->query($count_query);
while ($row = $count_result->fetch_assoc()) {
    $category_counts[$row['category']] = $row['count'];
}

// Get counts by level
$level_counts = [];
$level_count_query = "SELECT class_level, COUNT(*) as count FROM curriculum_subjects GROUP BY class_level";
$level_count_result = $conn->query($level_count_query);
while ($row = $level_count_result->fetch_assoc()) {
    $level_counts[$row['class_level']] = $row['count'];
}

$page_title = "Manage Subjects";
?>
<!DOCTYPE html>
<html class="scroll-smooth" lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | G.O.L.A</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    
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
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900 mb-2">Manage Curriculum Subjects</h1>
                        <p class="text-slate-600">View, edit, and organize school subjects</p>
                    </div>
                    <a href="add-subject.php" class="bg-primary text-white px-6 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined">add_circle</span>
                        Add New Subject
                    </a>
                </div>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-start gap-3">
                <span class="material-symbols-outlined text-green-600">check_circle</span>
                <p class="text-green-800 font-semibold"><?php echo htmlspecialchars($success); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-start gap-3">
                <span class="material-symbols-outlined text-red-600">error</span>
                <p class="text-red-800"><?php echo htmlspecialchars($error); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Filter Tabs by Category -->
            <div class="mb-4">
                <p class="text-sm font-semibold text-slate-700 mb-3">Filter by Category:</p>
                <div class="flex flex-wrap gap-3">
                    <a href="manage-subjects.php" class="px-6 py-3 rounded-lg font-semibold transition-all <?php echo empty($category_filter) && empty($level_filter) ? 'bg-primary text-white' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50'; ?>">
                        All Subjects (<?php echo $total_subjects; ?>)
                    </a>
                    <a href="manage-subjects.php?category=Core" class="px-6 py-3 rounded-lg font-semibold transition-all <?php echo $category_filter == 'Core' ? 'bg-primary text-white' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50'; ?>">
                        Core (<?php echo isset($category_counts['Core']) ? $category_counts['Core'] : 0; ?>)
                    </a>
                    <a href="manage-subjects.php?category=Vocational" class="px-6 py-3 rounded-lg font-semibold transition-all <?php echo $category_filter == 'Vocational' ? 'bg-primary text-white' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50'; ?>">
                        Vocational (<?php echo isset($category_counts['Vocational']) ? $category_counts['Vocational'] : 0; ?>)
                    </a>
                    <a href="manage-subjects.php?category=Languages" class="px-6 py-3 rounded-lg font-semibold transition-all <?php echo $category_filter == 'Languages' ? 'bg-primary text-white' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50'; ?>">
                        Languages (<?php echo isset($category_counts['Languages']) ? $category_counts['Languages'] : 0; ?>)
                    </a>
                    <a href="manage-subjects.php?category=Elective" class="px-6 py-3 rounded-lg font-semibold transition-all <?php echo $category_filter == 'Elective' ? 'bg-primary text-white' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50'; ?>">
                        Elective (<?php echo isset($category_counts['Elective']) ? $category_counts['Elective'] : 0; ?>)
                    </a>
                </div>
            </div>
            
            <!-- Filter Tabs by Level -->
            <div class="mb-8">
                <p class="text-sm font-semibold text-slate-700 mb-3">Filter by Level:</p>
                <div class="flex flex-wrap gap-3">
                    <a href="manage-subjects.php?level=All" class="px-6 py-3 rounded-lg font-semibold transition-all <?php echo $level_filter == 'All' ? 'bg-gold text-primary' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50'; ?>">
                        All Levels (<?php echo isset($level_counts['All']) ? $level_counts['All'] : 0; ?>)
                    </a>
                    <a href="manage-subjects.php?level=SSS1" class="px-6 py-3 rounded-lg font-semibold transition-all <?php echo $level_filter == 'SSS1' ? 'bg-gold text-primary' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50'; ?>">
                        SSS Only (<?php echo (isset($level_counts['SSS1']) ? $level_counts['SSS1'] : 0) + (isset($level_counts['SSS2']) ? $level_counts['SSS2'] : 0) + (isset($level_counts['SSS3']) ? $level_counts['SSS3'] : 0); ?>)
                    </a>
                </div>
            </div>
            
            <!-- Subjects Table -->
            <?php if ($result && $result->num_rows > 0): ?>
            
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Subject</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Category</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Level</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Order</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Status</th>
                                <th class="px-6 py-4 text-right text-sm font-semibold text-slate-900">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php while($subject = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-start gap-3">
                                        <div class="flex-shrink-0 w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                                            <span class="material-symbols-outlined text-primary text-sm">menu_book</span>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-slate-900"><?php echo htmlspecialchars($subject['subject_name']); ?></h3>
                                            <?php if ($subject['description']): ?>
                                            <p class="text-sm text-slate-600 line-clamp-1"><?php echo htmlspecialchars($subject['description']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-block px-3 py-1 <?php 
                                        echo $subject['category'] == 'Core' ? 'bg-blue-100 text-blue-700' : 
                                            ($subject['category'] == 'Vocational' ? 'bg-green-100 text-green-700' : 
                                            ($subject['category'] == 'Languages' ? 'bg-purple-100 text-purple-700' : 'bg-amber-100 text-amber-700')); 
                                    ?> text-sm font-semibold rounded-full">
                                        <?php echo htmlspecialchars($subject['category']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-medium text-slate-700">
                                        <?php echo htmlspecialchars($subject['class_level']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-slate-600">
                                        <?php echo $subject['display_order']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($subject['is_active']): ?>
                                    <span class="inline-block px-3 py-1 bg-green-100 text-green-700 text-sm font-semibold rounded-full">Active</span>
                                    <?php else: ?>
                                    <span class="inline-block px-3 py-1 bg-slate-100 text-slate-600 text-sm font-semibold rounded-full">Hidden</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="?toggle=<?php echo $subject['id']; ?>" 
                                           class="p-2 hover:bg-slate-100 text-slate-600 rounded-lg transition-colors" 
                                           title="Toggle Status">
                                            <span class="material-symbols-outlined text-sm">visibility_off</span>
                                        </a>
                                        <button onclick="confirmDelete(<?php echo $subject['id']; ?>)" 
                                                class="p-2 hover:bg-red-50 text-red-600 rounded-lg transition-colors" 
                                                title="Delete">
                                            <span class="material-symbols-outlined text-sm">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php else: ?>
            
            <!-- Empty State -->
            <div class="bg-white rounded-xl border border-slate-200 p-16 text-center">
                <span class="material-symbols-outlined text-6xl text-slate-300 mb-4 block">menu_book</span>
                <h3 class="text-xl font-bold text-slate-900 mb-2">No Subjects Found</h3>
                <p class="text-slate-600 mb-6">Start building your curriculum by adding subjects</p>
                <a href="add-subject.php" class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition-all">
                    <span class="material-symbols-outlined">add_circle</span>
                    Add First Subject
                </a>
            </div>
            
            <?php endif; ?>
            
        </main>
        
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this subject? This action cannot be undone.')) {
        window.location.href = 'manage-subjects.php?delete=' + id;
    }
}
</script>

</body>
</html>