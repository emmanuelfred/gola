<?php
require_once 'auth_check.php';

// Force error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session for messages
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$success = '';
$error = '';
$debug_info = [];

// Get messages from session if they exist
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

$debug_info[] = "Page loaded at: " . date('Y-m-d H:i:s');
$debug_info[] = "Request method: " . $_SERVER['REQUEST_METHOD'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $debug_info[] = "✅ POST request received";
    $debug_info[] = "Form submitted: " . (isset($_POST['form_submitted']) ? 'YES' : 'NO');
    $debug_info[] = "Title received: " . (isset($_POST['title']) ? $_POST['title'] : 'MISSING');
    
    if (isset($_POST['form_submitted'])) {
        try {
            $title = trim($_POST['title']);
            $category = $_POST['category'];
            $excerpt = trim($_POST['excerpt']);
            $content = trim($_POST['content']);
            $author = $admin_name;
            $published_date = $_POST['published_date'];
            $is_published = isset($_POST['is_published']) ? 1 : 0;
            $featured_image = '';
            
            $debug_info[] = "All form data captured successfully";
            
            // Handle image upload
            if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == 0) {
                $upload_dir = '../assets/images/news/';
                
                // Create directory if it doesn't exist
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                    $debug_info[] = "Created upload directory";
                }
                
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                $file_type = $_FILES['featured_image']['type'];
                
                if (in_array($file_type, $allowed_types)) {
                    $file_extension = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
                    $new_filename = time() . '_' . uniqid() . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $upload_path)) {
                        $featured_image = $new_filename;
                        $debug_info[] = "✅ Image uploaded: " . $featured_image;
                    } else {
                        $error = "Error uploading image file.";
                        $debug_info[] = "❌ Image upload failed";
                    }
                } else {
                    $error = "Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.";
                    $debug_info[] = "❌ Invalid file type: " . $file_type;
                }
            } else {
                $debug_info[] = "No image uploaded";
            }
            
            if (!isset($error) || empty($error)) {
                $debug_info[] = "Proceeding to database insert...";
                
                // Generate slug from title
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
                $debug_info[] = "Generated slug: " . $slug;
                
                // Check if slug already exists
                $check_stmt = $conn->prepare("SELECT id FROM news_articles WHERE slug = ?");
                if (!$check_stmt) {
                    $error = "Prepare check failed: " . $conn->error;
                    $debug_info[] = "❌ " . $error;
                } else {
                    $check_stmt->bind_param("s", $slug);
                    $check_stmt->execute();
                    $result = $check_stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $slug = $slug . '-' . time();
                        $debug_info[] = "Slug exists, using: " . $slug;
                    }
                    $check_stmt->close();
                    
                    // Insert into database
                    $sql = "INSERT INTO news_articles (title, slug, category, excerpt, content, featured_image, author, published_date, is_published) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    
                    if (!$stmt) {
                        $error = "Prepare insert failed: " . $conn->error;
                        $debug_info[] = "❌ " . $error;
                    } else {
                        $stmt->bind_param("ssssssssi", $title, $slug, $category, $excerpt, $content, $featured_image, $author, $published_date, $is_published);
                        
                        $debug_info[] = "Executing insert with data:";
                        $debug_info[] = "- Title: " . $title;
                        $debug_info[] = "- Slug: " . $slug;
                        $debug_info[] = "- Category: " . $category;
                        $debug_info[] = "- Image: " . ($featured_image ?: 'none');
                        $debug_info[] = "- Author: " . $author;
                        $debug_info[] = "- Date: " . $published_date;
                        $debug_info[] = "- Published: " . $is_published;
                        
                        if ($stmt->execute()) {
                            $insert_id = $stmt->insert_id;
                            $_SESSION['success'] = "✅ News article created successfully! (ID: " . $insert_id . ")";
                            if (!empty($featured_image)) {
                                $_SESSION['success'] .= " | Image: " . $featured_image;
                            }
                            
                            $debug_info[] = "✅ INSERT SUCCESSFUL! ID: " . $insert_id;
                            
                            // Log activity if function exists
                            if (function_exists('logActivity')) {
                                logActivity('create_news', "Created news article: $title");
                                $debug_info[] = "Activity logged";
                            }
                            
                            // Redirect to avoid resubmission
                            header('Location: add-news.php?success=1');
                            exit();
                        } else {
                            $error = "Execute failed: " . $stmt->error . " | Error code: " . $conn->errno;
                            $debug_info[] = "❌ " . $error;
                        }
                        $stmt->close();
                    }
                }
            }
        } catch (Exception $e) {
            $error = "Exception: " . $e->getMessage();
            $debug_info[] = "❌ Exception caught: " . $e->getMessage();
        }
    }
}

$page_title = "Add News Article";
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
                    <a href="manage-news.php" class="hover:text-gold">News</a>
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
                    <span class="text-slate-900">Add Article</span>
                </div>
                <h1 class="text-3xl font-bold text-slate-900 mb-2">Add News Article</h1>
                <p class="text-slate-600">Create and publish new articles to the website</p>
            </div>
            
            <!-- Debug Info -->
            <!--<div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
                <strong class="text-yellow-900">🐛 Debug Information:</strong>
                <div class="mt-2 text-sm font-mono text-yellow-800">
                    <?php
                    // foreach ($debug_info as $info): 
                        ?>
                        <div><?php //echo htmlspecialchars($info); ?></div>
                    <?php // endforeach; ?>
                </div>
            </div>-->
            
            <!-- Success/Error Messages -->
            <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-start gap-3">
                <span class="material-symbols-outlined text-green-600">check_circle</span>
                <div class="flex-1">
                    <p class="text-green-800 font-semibold"><?php echo htmlspecialchars($success); ?></p>
                    <a href="manage-news.php" class="text-green-700 text-sm hover:underline">View all articles →</a>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-start gap-3">
                <span class="material-symbols-outlined text-red-600">error</span>
                <div>
                    <p class="text-red-800 font-semibold">Error:</p>
                    <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Form -->
            <form method="POST" action="add-news.php" enctype="multipart/form-data" class="bg-white rounded-xl border border-slate-200 p-8">
                
                <!-- Hidden field to track form -->
                <input type="hidden" name="form_submitted" value="1">
                
                <div class="grid md:grid-cols-2 gap-6 mb-6">
                    
                    <!-- Title -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Article Title <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="title" 
                            required
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gold/20 focus:border-gold"
                            placeholder="Enter article title"
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
                            <option value="Academics">Academics</option>
                            <option value="Sports">Sports</option>
                            <option value="Events">Events</option>
                            <option value="Facilities">Facilities</option>
                            <option value="Achievements">Achievements</option>
                            <option value="General">General</option>
                        </select>
                    </div>
                    
                    <!-- Published Date -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Published Date <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="date" 
                            name="published_date" 
                            required
                            value="<?php echo date('Y-m-d'); ?>"
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gold/20 focus:border-gold"
                        >
                    </div>
                    
                    <!-- Featured Image -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Featured Image
                        </label>
                        <div class="border-2 border-dashed border-slate-300 rounded-lg p-6 text-center hover:border-gold transition-colors">
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
                                    <p class="text-slate-700 font-medium mb-1">Click to upload image</p>
                                    <p class="text-xs text-slate-500">JPG, PNG, GIF or WebP (Max 5MB)</p>
                                </div>
                                <div id="image-preview" class="image-preview">
                                    <img id="preview-img" src="" alt="Preview" class="max-h-64 mx-auto rounded-lg shadow-lg">
                                    <p class="text-sm text-slate-600 mt-3">Click to change image</p>
                                </div>
                            </label>
                        </div>
                        <p class="text-xs text-slate-500 mt-2">Optional: If no image is uploaded, a gradient will be used</p>
                    </div>
                    
                    <!-- Excerpt -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Excerpt <span class="text-red-500">*</span>
                        </label>
                        <textarea 
                            name="excerpt" 
                            required
                            rows="3"
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gold/20 focus:border-gold"
                            placeholder="Brief summary of the article (2-3 sentences)"
                        ></textarea>
                        <p class="text-xs text-slate-500 mt-1">This will appear on the news listing page</p>
                    </div>
                    
                    <!-- Content -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Article Content <span class="text-red-500">*</span>
                        </label>
                        <textarea 
                            name="content" 
                            required
                            rows="8"
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gold/20 focus:border-gold font-mono text-sm"
                            placeholder="Write your article content here. You can use HTML tags for formatting: <p>, <h3>, <ul>, <li>, <strong>, etc."
                        ></textarea>
                        <p class="text-xs text-slate-500 mt-1">HTML formatting is supported</p>
                    </div>
                    
                    <!-- Publish Status -->
                    <div class="md:col-span-2">
                        <label class="flex items-center gap-3 p-4 bg-slate-50 rounded-lg cursor-pointer hover:bg-slate-100 transition-colors">
                            <input 
                                type="checkbox" 
                                name="is_published" 
                                checked
                                class="w-5 h-5 text-gold focus:ring-gold rounded"
                            >
                            <div>
                                <span class="font-semibold text-slate-900">Publish Immediately</span>
                                <p class="text-sm text-slate-600">Article will be visible on the website</p>
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
                        Create Article
                    </button>
                    <a 
                        href="manage-news.php"
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