<?php
require_once 'auth_check.php';

// Handle delete action
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM news_articles WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        logActivity('delete_news', "Deleted news article ID: $id");
        header('Location: manage-news.php?success=deleted');
        exit;
    }
}

// Handle publish/unpublish toggle
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $conn->prepare("UPDATE news_articles SET is_published = NOT is_published WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        logActivity('toggle_news', "Toggled publish status for article ID: $id");
        header('Location: manage-news.php?success=updated');
        exit;
    }
}

// Fetch all news articles
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$count_query = "SELECT COUNT(*) as total FROM news_articles";
$count_result = $conn->query($count_query);
$total_articles = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_articles / $per_page);

$query = "SELECT * FROM news_articles ORDER BY created_at DESC, published_date DESC LIMIT $per_page OFFSET $offset";
$articles = $conn->query($query);

$page_title = "Manage News Articles";
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
                    <span class="text-slate-900">News Articles</span>
                </div>
                
                <div class="flex justify-between items-end">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900 mb-2">News Articles</h1>
                        <p class="text-slate-600">Manage and publish articles to the website</p>
                    </div>
                    <a href="add-news.php" class="px-6 py-3 bg-primary text-white font-semibold rounded-lg hover:bg-opacity-90 transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined">add_circle</span>
                        Add New Article
                    </a>
                </div>
            </div>
            
            <!-- Success Message -->
            <?php if (isset($_GET['success'])): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-start gap-3">
                <span class="material-symbols-outlined text-green-600">check_circle</span>
                <p class="text-green-800 font-semibold">
                    <?php 
                    if ($_GET['success'] == 'deleted') echo 'Article deleted successfully!';
                    if ($_GET['success'] == 'updated') echo 'Article updated successfully!';
                    ?>
                </p>
            </div>
            <?php endif; ?>
            
            <!-- Articles Table -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Author</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Views</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if ($articles->num_rows > 0): ?>
                                <?php while ($article = $articles->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-900 max-w-md truncate">
                                            <?php echo htmlspecialchars($article['title']); ?>
                                        </div>
                                        <div class="text-xs text-slate-500 mt-1">
                                            <?php echo htmlspecialchars(substr($article['excerpt'], 0, 60)); ?>...
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 bg-slate-100 text-slate-700 text-xs font-medium rounded-full">
                                            <?php echo htmlspecialchars($article['category']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600">
                                        <?php echo htmlspecialchars($article['author']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600">
                                        <?php echo date('M d, Y', strtotime($article['published_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600">
                                        <?php echo number_format($article['views']); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($article['is_published']): ?>
                                        <span class="px-3 py-1 bg-green-50 text-green-700 text-xs font-semibold rounded-full">
                                            Published
                                        </span>
                                        <?php else: ?>
                                        <span class="px-3 py-1 bg-slate-100 text-slate-600 text-xs font-semibold rounded-full">
                                            Draft
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="../news-detail.php?slug=<?php echo urlencode($article['slug']); ?>" 
                                               target="_blank"
                                               class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-all" 
                                               title="View">
                                                <span class="material-symbols-outlined text-lg">visibility</span>
                                            </a>
                                            <a href="manage-news.php?toggle=<?php echo $article['id']; ?>" 
                                               class="p-2 text-gold hover:bg-gold/10 rounded-lg transition-all" 
                                               title="Toggle Publish">
                                                <span class="material-symbols-outlined text-lg">
                                                    <?php echo $article['is_published'] ? 'visibility_off' : 'visibility'; ?>
                                                </span>
                                            </a>
                                            <a href="manage-news.php?delete=<?php echo $article['id']; ?>" 
                                               onclick="return confirm('Are you sure you want to delete this article?')"
                                               class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-all" 
                                               title="Delete">
                                                <span class="material-symbols-outlined text-lg">delete</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <span class="material-symbols-outlined text-6xl text-slate-300 mb-4">article</span>
                                        <p class="text-slate-500 mb-4">No articles found</p>
                                        <a href="add-news.php" class="text-gold hover:underline">Create your first article →</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="px-6 py-4 border-t border-slate-200 flex justify-between items-center">
                    <p class="text-sm text-slate-600">
                        Showing page <?php echo $page; ?> of <?php echo $total_pages; ?>
                    </p>
                    <div class="flex gap-2">
                        <?php if ($page > 1): ?>
                        <a href="manage-news.php?page=<?php echo $page - 1; ?>" 
                           class="px-4 py-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-all">
                            Previous
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <a href="manage-news.php?page=<?php echo $page + 1; ?>" 
                           class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-opacity-90 transition-all">
                            Next
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
        </main>
        
    </div>
</div>

</body>
</html>
