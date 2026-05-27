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
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $event_date = $_POST['event_date'];
    $event_time = trim($_POST['event_time']);
    $event_type = $_POST['event_type'];
    $location = trim($_POST['location']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $pdf_file = '';
    
    // Handle PDF upload
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] == 0) {
        $upload_dir = '../assets/documents/events/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $allowed_types = ['application/pdf'];
        $file_type = $_FILES['pdf_file']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $file_extension = pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION);
            $new_filename = time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $upload_path)) {
                $pdf_file = $new_filename;
            } else {
                $error = "Error uploading PDF file.";
            }
        } else {
            $error = "Invalid file type. Only PDF files are allowed.";
        }
    }
    
    if (!isset($error) || empty($error)) {
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO academic_events (title, description, event_date, event_time, event_type, location, pdf_file, is_featured, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssii", $title, $description, $event_date, $event_time, $event_type, $location, $pdf_file, $is_featured, $is_active);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Event added successfully!";
            if (function_exists('logActivity')) {
                logActivity('add_event', "Added event: $title");
            }
            header('Location: add-event.php');
            exit();
        } else {
            $error = "Database error: " . $conn->error;
        }
        $stmt->close();
    }
}

$page_title = "Add Event";
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
                    <a href="manage-events.php" class="hover:text-gold">Events</a>
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
                    <span class="text-slate-900">Add Event</span>
                </div>
                <h1 class="text-3xl font-bold text-slate-900 mb-2">Add Academic Event</h1>
                <p class="text-slate-600">Create a new calendar event for students and parents</p>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-start gap-3">
                <span class="material-symbols-outlined text-green-600">check_circle</span>
                <div class="flex-1">
                    <p class="text-green-800 font-semibold"><?php echo htmlspecialchars($success); ?></p>
                    <a href="manage-events.php" class="text-green-700 text-sm hover:underline">View all events →</a>
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
            <form method="POST" action="add-event.php" enctype="multipart/form-data" class="bg-white rounded-xl border border-slate-200 p-8">
                
                <input type="hidden" name="form_submitted" value="1">
                
                <div class="grid md:grid-cols-2 gap-6 mb-6">
                    
                    <!-- Event Title -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Event Title <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="title" 
                            required
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gold/20 focus:border-gold"
                            placeholder="e.g., Inter-House Sports Festival 2025"
                        >
                    </div>
                    
                    <!-- Event Date -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Event Date <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="date" 
                            name="event_date" 
                            required
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gold/20 focus:border-gold"
                        >
                    </div>
                    
                    <!-- Event Time -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Event Time
                        </label>
                        <input 
                            type="text" 
                            name="event_time" 
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gold/20 focus:border-gold"
                            placeholder="e.g., 8:00 AM - 4:00 PM"
                        >
                    </div>
                    
                    <!-- Event Type -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Event Type <span class="text-red-500">*</span>
                        </label>
                        <select 
                            name="event_type" 
                            required
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gold/20 focus:border-gold"
                        >
                            <option value="">Select Type</option>
                            <option value="Exam">Exam</option>
                            <option value="Holiday">Holiday</option>
                            <option value="Activity">Activity</option>
                            <option value="Meeting">Meeting</option>
                            <option value="Sports">Sports</option>
                            <option value="Cultural">Cultural</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <!-- Location -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Location
                        </label>
                        <input 
                            type="text" 
                            name="location" 
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gold/20 focus:border-gold"
                            placeholder="e.g., School Sports Complex"
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
                            rows="4"
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gold/20 focus:border-gold"
                            placeholder="Detailed description of the event"
                        ></textarea>
                    </div>
                    
                    <!-- PDF Upload -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Event Details PDF (Optional)
                        </label>
                        <input 
                            type="file" 
                            name="pdf_file" 
                            accept="application/pdf"
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gold/20 focus:border-gold"
                        >
                        <p class="text-xs text-slate-500 mt-1">Upload a PDF with additional event details (optional)</p>
                    </div>
                    
                    <!-- Featured Event -->
                    <div class="md:col-span-2">
                        <label class="flex items-center gap-3 p-4 bg-gold/5 border border-gold/20 rounded-lg cursor-pointer hover:bg-gold/10 transition-colors">
                            <input 
                                type="checkbox" 
                                name="is_featured"
                                class="w-5 h-5 text-gold focus:ring-gold rounded"
                            >
                            <div>
                                <span class="font-semibold text-slate-900">Featured Event</span>
                                <p class="text-sm text-slate-600">Show this event prominently on homepage and academics page</p>
                            </div>
                        </label>
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
                                <p class="text-sm text-slate-600">Event will be visible on the website</p>
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
                        Add Event
                    </button>
                    <a 
                        href="manage-events.php"
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
