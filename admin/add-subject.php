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
    $subject_name = trim($_POST['subject_name']);
    $category = $_POST['category'];
    $class_level = $_POST['class_level'];
    $description = trim($_POST['description']);
    $display_order = (int)$_POST['display_order'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Insert into database
    $stmt = $conn->prepare("INSERT INTO curriculum_subjects (subject_name, category, class_level, description, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssii", $subject_name, $category, $class_level, $description, $display_order, $is_active);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Subject added successfully!";
        if (function_exists('logActivity')) {
            logActivity('add_subject', "Added subject: $subject_name");
        }
        header('Location: add-subject.php');
        exit();
    } else {
        $error = "Database error: " . $conn->error;
    }
    $stmt->close();
}

$page_title = "Add Subject";
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
                <div class="flex items-center gap-2 text-sm text-slate-600 mb-4">
                    <a href="dashboard.php" class="hover:text-gold">Dashboard</a>
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
                    <a href="manage-subjects.php" class="hover:text-gold">Subjects</a>
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
                    <span class="text-slate-900">Add Subject</span>
                </div>
                <h1 class="text-3xl font-bold text-slate-900 mb-2">Add Curriculum Subject</h1>
                <p class="text-slate-600">Add a new subject to the school curriculum</p>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-start gap-3">
                <span class="material-symbols-outlined text-green-600">check_circle</span>
                <div class="flex-1">
                    <p class="text-green-800 font-semibold"><?php echo htmlspecialchars($success); ?></p>
                    <a href="manage-subjects.php" class="text-green-700 text-sm hover:underline">View all subjects →</a>
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
            <form method="POST" action="add-subject.php" class="bg-white rounded-xl border border-slate-200 p-8">
                
                <input type="hidden" name="form_submitted" value="1">
                
                <div class="grid md:grid-cols-2 gap-6 mb-6">
                    
                    <!-- Subject Name -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Subject Name <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="subject_name" 
                            required
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gold/20 focus:border-gold"
                            placeholder="e.g., Mathematics, English Language, Physics"
                        >
                    </div>
                    
                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Category <span class="text-red-500">*</span>
                        </label>
                        <select 
                            name="category" 
                            required
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gold/20 focus:border-gold"
                        >
                            <option value="">Select Category</option>
                            <option value="Core">Core Subjects</option>
                            <option value="Vocational">Vocational & Creative</option>
                            <option value="Languages">Languages</option>
                            <option value="Elective">Elective (SSS Only)</option>
                        </select>
                        <p class="text-xs text-slate-500 mt-1">Core = Required, Vocational = Hands-on, Languages = Foreign/Local, Elective = Optional</p>
                    </div>
                    
                    <!-- Class Level -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Class Level <span class="text-red-500">*</span>
                        </label>
                        <select 
                            name="class_level" 
                            required
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gold/20 focus:border-gold"
                        >
                            <option value="">Select Level</option>
                            <option value="All">All Levels (JSS & SSS)</option>
                            <option value="JSS1">JSS 1 Only</option>
                            <option value="JSS2">JSS 2 Only</option>
                            <option value="JSS3">JSS 3 Only</option>
                            <option value="SSS1">SSS 1 Only</option>
                            <option value="SSS2">SSS 2 Only</option>
                            <option value="SSS3">SSS 3 Only</option>
                        </select>
                        <p class="text-xs text-slate-500 mt-1">JSS = Junior Secondary (Grades 7-9), SSS = Senior Secondary (Grades 10-12)</p>
                    </div>
                    
                    <!-- Description -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Description (Optional)
                        </label>
                        <textarea 
                            name="description" 
                            rows="3"
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gold/20 focus:border-gold"
                            placeholder="Brief description of the subject"
                        ></textarea>
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
                            placeholder="0 = auto-sort by name"
                        >
                        <p class="text-xs text-slate-500 mt-1">Lower numbers appear first. Use 0 for alphabetical sorting.</p>
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
                                <p class="text-sm text-slate-600">Subject will be visible on the website</p>
                            </div>
                        </label>
                    </div>
                    
                </div>
                
                <!-- Examples Section -->
                <div class="mb-6 p-6 bg-blue-50 rounded-xl">
                    <h3 class="font-bold text-blue-900 mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined">info</span>
                        Subject Examples
                    </h3>
                    <div class="grid md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="font-semibold text-blue-900 mb-2">Core Subjects (All Levels):</p>
                            <ul class="space-y-1 text-blue-800">
                                <li>• Mathematics</li>
                                <li>• English Language</li>
                                <li>• Basic Science / Physics / Chemistry / Biology</li>
                                <li>• Computer Studies</li>
                            </ul>
                        </div>
                        <div>
                            <p class="font-semibold text-blue-900 mb-2">Elective Subjects (SSS):</p>
                            <ul class="space-y-1 text-blue-800">
                                <li>• Further Mathematics</li>
                                <li>• Economics</li>
                                <li>• Literature in English</li>
                                <li>• Government</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex gap-4 pt-6 border-t border-slate-200">
                    <button 
                        type="submit"
                        class="px-8 py-3 bg-primary text-white font-semibold rounded-lg hover:bg-opacity-90 transition-all flex items-center gap-2"
                    >
                        <span class="material-symbols-outlined">add_circle</span>
                        Add Subject
                    </button>
                    <a 
                        href="manage-subjects.php"
                        class="px-8 py-3 bg-slate-100 text-slate-700 font-semibold rounded-lg hover:bg-slate-200 transition-all"
                    >
                        Cancel
                    </a>
                </div>
                
            </form>
            
        </main>
        
    </div>
</div>

</body>
</html>
