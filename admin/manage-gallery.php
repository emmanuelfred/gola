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
    
    // Get image path first
    $stmt = $conn->prepare("SELECT image_path FROM gallery_images WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $image_path = '../assets/images/gallery/' . $row['image_path'];
        
        // Delete from database
        $delete_stmt = $conn->prepare("DELETE FROM gallery_images WHERE id = ?");
        $delete_stmt->bind_param("i", $id);
        
        if ($delete_stmt->execute()) {
            // Delete physical file
            if (file_exists($image_path)) {
                unlink($image_path);
            }
            $_SESSION['success'] = "Gallery image deleted successfully!";
            if (function_exists('logActivity')) {
                logActivity('delete_gallery_image', "Deleted gallery image ID: $id");
            }
        } else {
            $_SESSION['error'] = "Error deleting image: " . $conn->error;
        }
        $delete_stmt->close();
    }
    $stmt->close();
    
    header('Location: manage-gallery.php');
    exit();
}

// Handle toggle active status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $conn->prepare("UPDATE gallery_images SET is_active = NOT is_active WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Image status updated!";
    } else {
        $_SESSION['error'] = "Error updating status: " . $conn->error;
    }
    $stmt->close();
    
    header('Location: manage-gallery.php');
    exit();
}

// Pagination
$images_per_page = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $images_per_page;

// Filter by category
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

// Build query
$where_clause = "";
$count_where = "";
if (!empty($category_filter)) {
    $where_clause = " WHERE category = '" . $conn->real_escape_string($category_filter) . "'";
    $count_where = $where_clause;
}

// Get total count
$count_query = "SELECT COUNT(*) as total FROM gallery_images" . $count_where;
$count_result = $conn->query($count_query);
$total_images = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_images / $images_per_page);

// Fetch images
$query = "SELECT * FROM gallery_images" . $where_clause . " ORDER BY display_order ASC, created_at DESC LIMIT $images_per_page OFFSET $offset";
$result = $conn->query($query);

// Get category counts
$category_counts = [];
$count_query = "SELECT category, COUNT(*) as count FROM gallery_images GROUP BY category";
$count_result = $conn->query($count_query);
while ($row = $count_result->fetch_assoc()) {
    $category_counts[$row['category']] = $row['count'];
}

$page_title = "Manage Gallery";
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
                        <h1 class="text-3xl font-bold text-slate-900 mb-2">Gallery Management</h1>
                        <p class="text-slate-600">Manage photos showcased on the website</p>
                    </div>
                    <a href="add-gallery-image.php" class="bg-primary text-white px-6 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined">add_photo_alternate</span>
                        Upload New Image
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
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-8">
                <a href="manage-gallery.php" class="bg-white rounded-xl p-4 border-2 <?php echo empty($category_filter) ? 'border-gold' : 'border-slate-200 hover:border-slate-300'; ?> transition-colors">
                    <p class="text-2xl font-bold text-primary"><?php echo $total_images; ?></p>
                    <p class="text-sm text-slate-600">All Images</p>
                </a>
                <a href="manage-gallery.php?category=Campus" class="bg-white rounded-xl p-4 border-2 <?php echo $category_filter == 'Campus' ? 'border-gold' : 'border-slate-200 hover:border-slate-300'; ?> transition-colors">
                    <p class="text-2xl font-bold text-blue-600"><?php echo isset($category_counts['Campus']) ? $category_counts['Campus'] : 0; ?></p>
                    <p class="text-sm text-slate-600">Campus</p>
                </a>
                <a href="manage-gallery.php?category=Classrooms" class="bg-white rounded-xl p-4 border-2 <?php echo $category_filter == 'Classrooms' ? 'border-gold' : 'border-slate-200 hover:border-slate-300'; ?> transition-colors">
                    <p class="text-2xl font-bold text-green-600"><?php echo isset($category_counts['Classrooms']) ? $category_counts['Classrooms'] : 0; ?></p>
                    <p class="text-sm text-slate-600">Classrooms</p>
                </a>
                <a href="manage-gallery.php?category=Facilities" class="bg-white rounded-xl p-4 border-2 <?php echo $category_filter == 'Facilities' ? 'border-gold' : 'border-slate-200 hover:border-slate-300'; ?> transition-colors">
                    <p class="text-2xl font-bold text-purple-600"><?php echo isset($category_counts['Facilities']) ? $category_counts['Facilities'] : 0; ?></p>
                    <p class="text-sm text-slate-600">Facilities</p>
                </a>
                <a href="manage-gallery.php?category=Sports" class="bg-white rounded-xl p-4 border-2 <?php echo $category_filter == 'Sports' ? 'border-gold' : 'border-slate-200 hover:border-slate-300'; ?> transition-colors">
                    <p class="text-2xl font-bold text-red-600"><?php echo isset($category_counts['Sports']) ? $category_counts['Sports'] : 0; ?></p>
                    <p class="text-sm text-slate-600">Sports</p>
                </a>
                <a href="manage-gallery.php?category=Events" class="bg-white rounded-xl p-4 border-2 <?php echo $category_filter == 'Events' ? 'border-gold' : 'border-slate-200 hover:border-slate-300'; ?> transition-colors">
                    <p class="text-2xl font-bold text-amber-600"><?php echo isset($category_counts['Events']) ? $category_counts['Events'] : 0; ?></p>
                    <p class="text-sm text-slate-600">Events</p>
                </a>
            </div>
            
            <!-- Gallery Grid -->
            <?php if ($result && $result->num_rows > 0): ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                
                <?php while($image = $result->fetch_assoc()): ?>
                
                <div class="bg-white rounded-xl overflow-hidden shadow-lg hover:shadow-2xl transition-all group">
                    <!-- Image -->
                    <div class="aspect-[4/3] overflow-hidden bg-slate-100 relative">
                        <img src="../assets/images/gallery/<?php echo htmlspecialchars($image['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($image['title']); ?>"
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                             onerror="this.src='../asset/courses-5.jpg'">
                        
                        <!-- Status Badge -->
                        <div class="absolute top-3 left-3">
                            <?php if ($image['is_active']): ?>
                            <span class="px-3 py-1 bg-green-500 text-white text-xs font-semibold rounded-full">Active</span>
                            <?php else: ?>
                            <span class="px-3 py-1 bg-slate-500 text-white text-xs font-semibold rounded-full">Hidden</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Category Badge -->
                        <div class="absolute top-3 right-3">
                            <span class="px-3 py-1 bg-primary/80 backdrop-blur-sm text-white text-xs font-semibold rounded-full">
                                <?php echo htmlspecialchars($image['category']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Details -->
                    <div class="p-4">
                        <h3 class="font-bold text-slate-900 mb-1 line-clamp-1"><?php echo htmlspecialchars($image['title']); ?></h3>
                        <p class="text-sm text-slate-600 mb-3 line-clamp-2"><?php echo htmlspecialchars($image['description']); ?></p>
                        
                        <div class="flex items-center justify-between text-xs text-slate-500 mb-4">
                            <span>Order: <?php echo $image['display_order']; ?></span>
                            <span><?php echo date('M d, Y', strtotime($image['created_at'])); ?></span>
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex gap-2">
                            <a href="?toggle=<?php echo $image['id']; ?>" 
                               class="flex-1 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-lg transition-colors text-center"
                               onclick="return confirm('Toggle visibility for this image?')">
                                <?php echo $image['is_active'] ? 'Hide' : 'Show'; ?>
                            </a>
                            <a href="../assets/images/gallery/<?php echo htmlspecialchars($image['image_path']); ?>" 
                               target="_blank"
                               class="px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-semibold rounded-lg transition-colors">
                                <span class="material-symbols-outlined text-sm">visibility</span>
                            </a>
                            <button onclick="confirmDelete(<?php echo $image['id']; ?>)" 
                                    class="px-3 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold rounded-lg transition-colors">
                                <span class="material-symbols-outlined text-sm">delete</span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php endwhile; ?>
                
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="flex justify-center gap-2">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($category_filter) ? '&category='.$category_filter : ''; ?>" 
                   class="px-4 py-2 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                    Previous
                </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo !empty($category_filter) ? '&category='.$category_filter : ''; ?>" 
                   class="px-4 py-2 <?php echo $i == $page ? 'bg-primary text-white' : 'bg-white border border-slate-300 hover:bg-slate-50'; ?> rounded-lg transition-colors">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($category_filter) ? '&category='.$category_filter : ''; ?>" 
                   class="px-4 py-2 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                    Next
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            
            <!-- Empty State -->
            <div class="bg-white rounded-xl border border-slate-200 p-16 text-center">
                <span class="material-symbols-outlined text-6xl text-slate-300 mb-4 block">photo_library</span>
                <h3 class="text-xl font-bold text-slate-900 mb-2">No Gallery Images</h3>
                <p class="text-slate-600 mb-6">Start showcasing your school by uploading photos</p>
                <a href="add-gallery-image.php" class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition-all">
                    <span class="material-symbols-outlined">add_photo_alternate</span>
                    Upload First Image
                </a>
            </div>
            
            <?php endif; ?>
            
        </main>
        
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this image? This action cannot be undone.')) {
        window.location.href = 'manage-gallery.php?delete=' + id;
    }
}
</script>

</body>
</html>
