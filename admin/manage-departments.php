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
    
    // Get image file first
    $stmt = $conn->prepare("SELECT featured_image FROM academic_departments WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $image_path = '../assets/images/departments/' . $row['featured_image'];
        
        // Delete from database
        $delete_stmt = $conn->prepare("DELETE FROM academic_departments WHERE id = ?");
        $delete_stmt->bind_param("i", $id);
        
        if ($delete_stmt->execute()) {
            // Delete physical file if exists
            if ($row['featured_image'] && file_exists($image_path)) {
                unlink($image_path);
            }
            $_SESSION['success'] = "Department deleted successfully!";
            if (function_exists('logActivity')) {
                logActivity('delete_department', "Deleted department ID: $id");
            }
        } else {
            $_SESSION['error'] = "Error deleting department: " . $conn->error;
        }
        $delete_stmt->close();
    }
    $stmt->close();
    
    header('Location: manage-departments.php');
    exit();
}

// Handle toggle active status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $conn->prepare("UPDATE academic_departments SET is_active = NOT is_active WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Department status updated!";
    } else {
        $_SESSION['error'] = "Error updating status: " . $conn->error;
    }
    $stmt->close();
    
    header('Location: manage-departments.php');
    exit();
}

// Fetch departments
$query = "SELECT * FROM academic_departments ORDER BY display_order ASC, department_name ASC";
$result = $conn->query($query);

$page_title = "Manage Departments";
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
                        <h1 class="text-3xl font-bold text-slate-900 mb-2">Manage Academic Departments</h1>
                        <p class="text-slate-600">View, edit, and organize faculty departments</p>
                    </div>
                    <a href="add-department.php" class="bg-primary text-white px-6 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined">add_circle</span>
                        Add New Department
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
            
            <!-- Departments Grid -->
            <?php if ($result && $result->num_rows > 0): ?>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <?php while($dept = $result->fetch_assoc()): ?>
                
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden hover:shadow-xl transition-all">
                    <!-- Department Image -->
                    <div class="aspect-video overflow-hidden bg-gradient-to-br from-primary to-slate-700 relative">
                        <?php if ($dept['featured_image']): ?>
                        <img src="../assets/images/departments/<?php echo htmlspecialchars($dept['featured_image']); ?>" 
                             alt="<?php echo htmlspecialchars($dept['department_name']); ?>"
                             class="w-full h-full object-cover opacity-30"
                             onerror="this.style.display='none'">
                        <?php endif; ?>
                        
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="material-symbols-outlined text-8xl text-white/50">
                                <?php echo $dept['icon_type'] ? htmlspecialchars($dept['icon_type']) : 'school'; ?>
                            </span>
                        </div>
                        
                        <!-- Status Badge -->
                        <div class="absolute top-3 right-3">
                            <?php if ($dept['is_active']): ?>
                            <span class="px-3 py-1 bg-green-500 text-white text-xs font-semibold rounded-full">Active</span>
                            <?php else: ?>
                            <span class="px-3 py-1 bg-slate-500 text-white text-xs font-semibold rounded-full">Hidden</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Department Details -->
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-primary mb-2"><?php echo htmlspecialchars($dept['department_name']); ?></h3>
                        <p class="text-sm text-slate-600 mb-4 line-clamp-3"><?php echo htmlspecialchars($dept['description']); ?></p>
                        
                        <div class="mb-4">
                            <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Subjects Covered:</p>
                            <p class="text-sm text-slate-700"><?php echo htmlspecialchars($dept['subjects_covered']); ?></p>
                        </div>
                        
                        <div class="flex items-center justify-between text-xs text-slate-500 mb-4 pb-4 border-b border-slate-200">
                            <span>Order: <?php echo $dept['display_order']; ?></span>
                            <span>Slug: <?php echo htmlspecialchars($dept['slug']); ?></span>
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex gap-2">
                            <a href="?toggle=<?php echo $dept['id']; ?>" 
                               class="flex-1 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-lg transition-colors text-center"
                               onclick="return confirm('Toggle visibility for this department?')">
                                <?php echo $dept['is_active'] ? 'Hide' : 'Show'; ?>
                            </a>
                            <a href="../academics.php#departments" 
                               target="_blank"
                               class="px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-semibold rounded-lg transition-colors"
                               title="View on Website">
                                <span class="material-symbols-outlined text-sm">visibility</span>
                            </a>
                            <button onclick="confirmDelete(<?php echo $dept['id']; ?>)" 
                                    class="px-3 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold rounded-lg transition-colors"
                                    title="Delete">
                                <span class="material-symbols-outlined text-sm">delete</span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php endwhile; ?>
                
            </div>
            
            <?php else: ?>
            
            <!-- Empty State -->
            <div class="bg-white rounded-xl border border-slate-200 p-16 text-center">
                <span class="material-symbols-outlined text-6xl text-slate-300 mb-4 block">school</span>
                <h3 class="text-xl font-bold text-slate-900 mb-2">No Departments Found</h3>
                <p class="text-slate-600 mb-6">Start organizing your school by creating academic departments</p>
                <a href="add-department.php" class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition-all">
                    <span class="material-symbols-outlined">add_circle</span>
                    Add First Department
                </a>
            </div>
            
            <?php endif; ?>
            
        </main>
        
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this department? This action cannot be undone.')) {
        window.location.href = 'manage-departments.php?delete=' + id;
    }
}
</script>

</body>
</html>
