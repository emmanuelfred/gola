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
    
    // Get PDF file first
    $stmt = $conn->prepare("SELECT pdf_file FROM academic_events WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $pdf_path = '../assets/documents/events/' . $row['pdf_file'];
        
        // Delete from database
        $delete_stmt = $conn->prepare("DELETE FROM academic_events WHERE id = ?");
        $delete_stmt->bind_param("i", $id);
        
        if ($delete_stmt->execute()) {
            // Delete physical file if exists
            if ($row['pdf_file'] && file_exists($pdf_path)) {
                unlink($pdf_path);
            }
            $_SESSION['success'] = "Event deleted successfully!";
            if (function_exists('logActivity')) {
                logActivity('delete_event', "Deleted event ID: $id");
            }
        } else {
            $_SESSION['error'] = "Error deleting event: " . $conn->error;
        }
        $delete_stmt->close();
    }
    $stmt->close();
    
    header('Location: manage-events.php');
    exit();
}

// Handle toggle active status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $conn->prepare("UPDATE academic_events SET is_active = NOT is_active WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Event status updated!";
    } else {
        $_SESSION['error'] = "Error updating status: " . $conn->error;
    }
    $stmt->close();
    
    header('Location: manage-events.php');
    exit();
}

// Handle toggle featured status
if (isset($_GET['feature']) && is_numeric($_GET['feature'])) {
    $id = (int)$_GET['feature'];
    $stmt = $conn->prepare("UPDATE academic_events SET is_featured = NOT is_featured WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Event featured status updated!";
    } else {
        $_SESSION['error'] = "Error updating featured status: " . $conn->error;
    }
    $stmt->close();
    
    header('Location: manage-events.php');
    exit();
}

// Filter by event type
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';

// Pagination
$events_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $events_per_page;

// Build query
$where_clause = "";
if (!empty($type_filter)) {
    $where_clause = " WHERE event_type = '" . $conn->real_escape_string($type_filter) . "'";
}

// Get total count
$count_query = "SELECT COUNT(*) as total FROM academic_events" . $where_clause;
$count_result = $conn->query($count_query);
$total_events = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_events / $events_per_page);

// Fetch events
$query = "SELECT * FROM academic_events" . $where_clause . " ORDER BY event_date DESC LIMIT $events_per_page OFFSET $offset";
$result = $conn->query($query);

// Get counts by type
$type_counts = [];
$count_query = "SELECT event_type, COUNT(*) as count FROM academic_events GROUP BY event_type";
$count_result = $conn->query($count_query);
while ($row = $count_result->fetch_assoc()) {
    $type_counts[$row['event_type']] = $row['count'];
}

$page_title = "Manage Events";
?>
<!DOCTYPE html>
<html class="scroll-smooth" lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | G.O.L.A</title>
    <link rel="icon" href="../asset/favicon.png" type="image/png">
    
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
                        <h1 class="text-3xl font-bold text-slate-900 mb-2">Manage Academic Events</h1>
                        <p class="text-slate-600">View, edit, and delete calendar events</p>
                    </div>
                    <a href="add-event.php" class="bg-primary text-white px-6 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined">add_circle</span>
                        Add New Event
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
            
            <!-- Filter Tabs -->
            <div class="flex flex-wrap gap-3 mb-8">
                <a href="manage-events.php" class="px-6 py-3 rounded-lg font-semibold transition-all <?php echo empty($type_filter) ? 'bg-primary text-white' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50'; ?>">
                    All Events (<?php echo $total_events; ?>)
                </a>
                <a href="manage-events.php?type=Exam" class="px-6 py-3 rounded-lg font-semibold transition-all <?php echo $type_filter == 'Exam' ? 'bg-primary text-white' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50'; ?>">
                    Exams (<?php echo isset($type_counts['Exam']) ? $type_counts['Exam'] : 0; ?>)
                </a>
                <a href="manage-events.php?type=Sports" class="px-6 py-3 rounded-lg font-semibold transition-all <?php echo $type_filter == 'Sports' ? 'bg-primary text-white' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50'; ?>">
                    Sports (<?php echo isset($type_counts['Sports']) ? $type_counts['Sports'] : 0; ?>)
                </a>
                <a href="manage-events.php?type=Cultural" class="px-6 py-3 rounded-lg font-semibold transition-all <?php echo $type_filter == 'Cultural' ? 'bg-primary text-white' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50'; ?>">
                    Cultural (<?php echo isset($type_counts['Cultural']) ? $type_counts['Cultural'] : 0; ?>)
                </a>
                <a href="manage-events.php?type=Holiday" class="px-6 py-3 rounded-lg font-semibold transition-all <?php echo $type_filter == 'Holiday' ? 'bg-primary text-white' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50'; ?>">
                    Holidays (<?php echo isset($type_counts['Holiday']) ? $type_counts['Holiday'] : 0; ?>)
                </a>
            </div>
            
            <!-- Events Table -->
            <?php if ($result && $result->num_rows > 0): ?>
            
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Event</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Date & Time</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Type</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Status</th>
                                <th class="px-6 py-4 text-right text-sm font-semibold text-slate-900">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php while($event = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-start gap-3">
                                        <div class="flex-shrink-0 w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                                            <span class="material-symbols-outlined text-primary">event</span>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-slate-900 mb-1"><?php echo htmlspecialchars($event['title']); ?></h3>
                                            <p class="text-sm text-slate-600 line-clamp-1"><?php echo htmlspecialchars($event['description']); ?></p>
                                            <?php if ($event['is_featured']): ?>
                                            <span class="inline-block mt-1 px-2 py-1 bg-gold/10 text-gold text-xs font-semibold rounded">Featured</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm">
                                        <p class="font-semibold text-slate-900"><?php echo date('M d, Y', strtotime($event['event_date'])); ?></p>
                                        <?php if ($event['event_time']): ?>
                                        <p class="text-slate-600"><?php echo htmlspecialchars($event['event_time']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-block px-3 py-1 bg-slate-100 text-slate-700 text-sm font-semibold rounded-full">
                                        <?php echo htmlspecialchars($event['event_type']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($event['is_active']): ?>
                                    <span class="inline-block px-3 py-1 bg-green-100 text-green-700 text-sm font-semibold rounded-full">Active</span>
                                    <?php else: ?>
                                    <span class="inline-block px-3 py-1 bg-slate-100 text-slate-600 text-sm font-semibold rounded-full">Hidden</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="../event-detail.php?id=<?php echo $event['id']; ?>" 
                                           target="_blank"
                                           class="p-2 hover:bg-blue-50 text-blue-600 rounded-lg transition-colors" 
                                           title="View">
                                            <span class="material-symbols-outlined text-sm">visibility</span>
                                        </a>
                                        <a href="?feature=<?php echo $event['id']; ?>" 
                                           class="p-2 hover:bg-gold/10 text-gold rounded-lg transition-colors" 
                                           title="<?php echo $event['is_featured'] ? 'Remove from Featured' : 'Mark as Featured'; ?>">
                                            <span class="material-symbols-outlined text-sm"><?php echo $event['is_featured'] ? 'star' : 'star_border'; ?></span>
                                        </a>
                                        <a href="?toggle=<?php echo $event['id']; ?>" 
                                           class="p-2 hover:bg-slate-100 text-slate-600 rounded-lg transition-colors" 
                                           title="Toggle Status">
                                            <span class="material-symbols-outlined text-sm">visibility_off</span>
                                        </a>
                                        <button onclick="confirmDelete(<?php echo $event['id']; ?>)" 
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
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="flex justify-center gap-2 mt-8">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($type_filter) ? '&type='.$type_filter : ''; ?>" 
                   class="px-4 py-2 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                    Previous
                </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo !empty($type_filter) ? '&type='.$type_filter : ''; ?>" 
                   class="px-4 py-2 <?php echo $i == $page ? 'bg-primary text-white' : 'bg-white border border-slate-300 hover:bg-slate-50'; ?> rounded-lg transition-colors">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($type_filter) ? '&type='.$type_filter : ''; ?>" 
                   class="px-4 py-2 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                    Next
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            
            <!-- Empty State -->
            <div class="bg-white rounded-xl border border-slate-200 p-16 text-center">
                <span class="material-symbols-outlined text-6xl text-slate-300 mb-4 block">event</span>
                <h3 class="text-xl font-bold text-slate-900 mb-2">No Events Found</h3>
                <p class="text-slate-600 mb-6">Start adding academic events to your calendar</p>
                <a href="add-event.php" class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition-all">
                    <span class="material-symbols-outlined">add_circle</span>
                    Add First Event
                </a>
            </div>
            
            <?php endif; ?>
            
        </main>
        
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this event? This action cannot be undone.')) {
        window.location.href = 'manage-events.php?delete=' + id;
    }
}
</script>

</body>
</html>
