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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['form_submitted'])) {
    $department_name = trim($_POST['department_name']);
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $department_name)));
    $description = trim($_POST['description']);
    $subjects_covered = trim($_POST['subjects_covered']);
    $icon_type = trim($_POST['icon_type']);
    $display_order = (int)$_POST['display_order'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $featured_image = '';
    
    // Handle image upload
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == 0) {
        $upload_dir = '../assets/images/departments/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['featured_image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $file_extension = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
            $new_filename = time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $upload_path)) {
                $featured_image = $new_filename;
            } else {
                $error = "Error uploading image file.";
            }
        } else {
            $error = "Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.";
        }
    }
    
    if (!isset($error) || empty($error)) {
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO academic_departments (department_name, slug, description, subjects_covered, featured_image, icon_type, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss sii", $department_name, $slug, $description, $subjects_covered, $featured_image, $icon_type, $display_order, $is_active);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Department added successfully!";
            if (function_exists('logActivity')) {
                logActivity('add_department', "Added department: $department_name");
            }
            header('Location: add-department.php');
            exit();
        } else {
            $error = "Database error: " . $conn->error;
        }
        $stmt->close();
    }
}

$page_title = "Add Department";
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
        .image-preview {
            display: none;
        }
        .image-preview.show {
            display: block;
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
                <div class="flex items-center gap-2 text-sm text-slate-600 mb-4">
                    <a href="dashboard.php" class="hover:text-gold">Dashboard</a>
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
                    <a href="manage-departments.php" class="hover:text-gold">Departments</a>
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
                    <span class="text-slate-900">Add Department</span>
                </div>
                <h1 class="text-3xl font-bold text-slate-900 mb-2">Add Academic Department</h1>
                <p class="text-slate-600">Create a new faculty department for the school</p>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-start gap-3">
                <span class="material-symbols-outlined text-green-600">check_circle</span>
                <div class="flex-1">
                    <p class="text-green-800 font-semibold"><?php echo htmlspecialchars($success); ?></p>
                    <a href="manage-departments.php" class="text-green-700 text-sm hover:underline">View all departments →</a>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-start gap-3">
                <span class="material-symbols-outlined text-red-600">error</span>
                <p class="text-red-800"><?php echo htmlspecialchars($error); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Form -->
            <form method="POST" action="add-department.php" enctype="multipart/form-data" class="bg-white rounded-xl border border-slate-200 p-8">
                
                <input type="hidden" name="form_submitted" value="1">
                
                <div class="grid md:grid-cols-2 gap-6 mb-6">
                    
                    <!-- Department Featured Image -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Department Image (Optional)
                        </label>
                        <div class="border-2 border-dashed border-slate-300 rounded-lg p-8 text-center hover:border-gold transition-colors">
                            <input 
                                type="file" 
                                name="featured_image" 
                                id="featured_image"
                                accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                                class="hidden"
                                onchange="previewImage(this)"
                            >
                            <label for="featured_image" class="cursor-pointer">
                                <div id="upload-placeholder">
                                    <span class="material-symbols-outlined text-6xl text-slate-400 mb-3 block">cloud_upload</span>
                                    <p class="text-slate-700 font-medium mb-1">Click to upload department image</p>
                                    <p class="text-xs text-slate-500">JPG, PNG, GIF or WebP (Recommended: 1200x800px)</p>
                                </div>
                                <div id="image-preview" class="image-preview">
                                    <img id="preview-img" src="" alt="Preview" class="max-h-80 mx-auto rounded-lg shadow-lg">
                                    <p class="text-sm text-slate-600 mt-3">Click to change image</p>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Department Name -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Department Name <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="department_name" 
                            required
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gold/20 focus:border-gold"
                            placeholder="e.g., Sciences, Humanities & Arts, Commercials"
                        >
                    </div>
                    
                    <!-- Description -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Description <span class="text-red-500">*</span>
                        </label>
                        <textarea 
                            name="description" 
                            required
                            rows="3"
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gold/20 focus:border-gold"
                            placeholder="Brief overview of the department"
                        ></textarea>
                    </div>
                    
                    <!-- Subjects Covered -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Subjects Covered <span class="text-red-500">*</span>
                        </label>
                        <textarea 
                            name="subjects_covered" 
                            required
                            rows="2"
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gold/20 focus:border-gold"
                            placeholder="e.g., Physics, Chemistry, Biology, Further Mathematics"
                        ></textarea>
                        <p class="text-xs text-slate-500 mt-1">List the main subjects taught in this department, separated by commas</p>
                    </div>
                    
                    <!-- Icon Type -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Icon <span class="text-red-500">*</span>
                        </label>
                        <select 
                            name="icon_type" 
                            required
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gold/20 focus:border-gold"
                        >
                            <option value="">Select Icon</option>
                            <option value="science">Science (Beaker)</option>
                            <option value="menu_book">Humanities (Book)</option>
                            <option value="business_center">Business (Briefcase)</option>
                            <option value="group">Leadership (People)</option>
                            <option value="school">School (Building)</option>
                            <option value="devices">Technology (Devices)</option>
                            <option value="palette">Arts (Palette)</option>
                            <option value="sports_soccer">Sports (Soccer)</option>
                        </select>
                    </div>
                    
                    <!-- Display Order -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Display Order
                        </label>
                        <input 
                            type="number" 
                            name="display_order" 
                            value="0"
                            min="0"
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gold/20 focus:border-gold"
                            placeholder="0 = default order"
                        >
                        <p class="text-xs text-slate-500 mt-1">Lower numbers appear first</p>
                    </div>
                    
                    <!-- Active Status -->
                    <div class="md:col-span-2">
                        <label class="flex items-center gap-3 p-4 bg-slate-50 rounded-lg cursor-pointer hover:bg-slate-100 transition-colors">
                            <input 
                                type="checkbox" 
                                name="is_active" 
                                checked
                                class="w-5 h-5 text-gold focus:ring-gold rounded"
                            >
                            <div>
                                <span class="font-semibold text-slate-900">Active</span>
                                <p class="text-sm text-slate-600">Department will be visible on the website</p>
                            </div>
                        </label>
                    </div>
                    
                </div>
                
                <!-- Action Buttons -->
                <div class="flex gap-4 pt-6 border-t border-slate-200">
                    <button 
                        type="submit"
                        class="px-8 py-3 bg-primary text-white font-semibold rounded-lg hover:bg-opacity-90 transition-all flex items-center gap-2"
                    >
                        <span class="material-symbols-outlined">add_circle</span>
                        Add Department
                    </button>
                    <a 
                        href="manage-departments.php"
                        class="px-8 py-3 bg-slate-100 text-slate-700 font-semibold rounded-lg hover:bg-slate-200 transition-all"
                    >
                        Cancel
                    </a>
                </div>
                
            </form>
            
        </main>
        
    </div>
</div>

<script>
function previewImage(input) {
    const placeholder = document.getElementById('upload-placeholder');
    const preview = document.getElementById('image-preview');
    const previewImg = document.getElementById('preview-img');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            placeholder.style.display = 'none';
            preview.classList.add('show');
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

</body>
</html>
