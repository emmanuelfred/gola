<?php 
$page_title = "News & Announcements";
require_once 'config/database.php';

// Get filter category
$category_filter = isset($_GET['category']) ? $_GET['category'] : 'all';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 9;
$offset = ($page - 1) * $per_page;

// Build query based on filter
$where_clause = "WHERE is_published = TRUE";
if ($category_filter != 'all') {
    $where_clause .= " AND category = '" . $conn->real_escape_string($category_filter) . "'";
}

// Get total count
$count_query = "SELECT COUNT(*) as total FROM news_articles $where_clause";
$count_result = $conn->query($count_query);
$total_articles = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_articles / $per_page);

// Get articles
$query = "SELECT * FROM news_articles $where_clause ORDER BY published_date DESC, created_at DESC LIMIT $per_page OFFSET $offset";
$articles = $conn->query($query);

// Get categories with counts
$categories_query = "SELECT category, COUNT(*) as count FROM news_articles WHERE is_published = TRUE GROUP BY category ORDER BY category";
$categories = $conn->query($categories_query);

include 'includes/header.php'; 
?>

<!-- Page Header -->
<section class="py-16 bg-gradient-to-br from-primary to-slate-800 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="inline-flex items-center gap-2 px-4 py-2 bg-gold/20 border border-gold/30 rounded-full text-gold font-semibold text-sm uppercase tracking-widest mb-6">
            <span class="material-symbols-outlined">newspaper</span>
            Stay Updated
        </div>
        <h1 class="text-4xl lg:text-6xl font-display font-black mb-4">News & Announcements</h1>
        <p class="text-xl text-slate-300 max-w-3xl mx-auto">Stay informed about the latest happenings, achievements, and events at Goodness Omogo Leadership Academy</p>
    </div>
</section>

<!-- News Section -->
<section class="py-16 bg-slate-50 dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Filter Tabs -->
        <div class="mb-12">
            <div class="flex flex-wrap gap-3 justify-center">
                <a href="news.php?category=all" 
                   class="px-6 py-3 rounded-lg font-semibold transition-all <?php echo $category_filter == 'all' ? 'bg-primary text-white' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'; ?>">
                    All News
                </a>
                <?php while($cat = $categories->fetch_assoc()): ?>
                <a href="news.php?category=<?php echo urlencode($cat['category']); ?>" 
                   class="px-6 py-3 rounded-lg font-semibold transition-all <?php echo $category_filter == $cat['category'] ? 'bg-primary text-white' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'; ?>">
                    <?php echo htmlspecialchars($cat['category']); ?>
                    <span class="ml-2 text-xs opacity-70">(<?php echo $cat['count']; ?>)</span>
                </a>
                <?php endwhile; ?>
            </div>
        </div>

        <?php if ($articles->num_rows > 0): ?>
        
        <!-- News Grid -->
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
            <?php while($article = $articles->fetch_assoc()): 
                // Generate gradient based on category
                $gradients = [
                    'Academics' => 'from-blue-500 to-blue-700',
                    'Sports' => 'from-green-500 to-green-700',
                    'Events' => 'from-purple-500 to-purple-700',
                    'Facilities' => 'from-gold to-yellow-700',
                    'Achievements' => 'from-red-500 to-red-700',
                    'General' => 'from-slate-500 to-slate-700'
                ];
                $gradient = $gradients[$article['category']] ?? 'from-primary to-slate-700';
            ?>
            
            <article class="bg-white dark:bg-slate-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all group">
                <!-- Featured Image -->
                <div class="h-56 bg-gradient-to-br <?php echo $gradient; ?> relative overflow-hidden">
                    <?php if (!empty($article['featured_image'])): ?>
                    <img src="assets/images/news/<?php echo htmlspecialchars($article['featured_image']); ?>" 
                         alt="<?php echo htmlspecialchars($article['title']); ?>"
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    <?php endif; ?>
                    
                    <!-- Category Badge -->
                    <div class="absolute top-4 left-4">
                        <span class="bg-white/90 backdrop-blur-sm text-primary px-3 py-1 rounded-full text-xs font-semibold uppercase">
                            <?php echo htmlspecialchars($article['category']); ?>
                        </span>
                    </div>
                </div>
                
                <!-- Content -->
                <div class="p-6">
                    <!-- Meta Info -->
                    <div class="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400 mb-3">
                        <span class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">calendar_today</span>
                            <?php echo date('M d, Y', strtotime($article['published_date'])); ?>
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">visibility</span>
                            <?php echo number_format($article['views']); ?> views
                        </span>
                    </div>
                    
                    <!-- Title -->
                    <h3 class="text-xl font-bold text-primary dark:text-gold mb-3 line-clamp-2 group-hover:text-gold dark:group-hover:text-white transition-colors">
                        <?php echo htmlspecialchars($article['title']); ?>
                    </h3>
                    
                    <!-- Excerpt -->
                    <p class="text-slate-600 dark:text-slate-400 text-sm mb-4 line-clamp-3">
                        <?php echo htmlspecialchars($article['excerpt']); ?>
                    </p>
                    
                    <!-- Read More Link -->
                    <a href="news-detail.php?slug=<?php echo urlencode($article['slug']); ?>" 
                       class="inline-flex items-center gap-2 text-primary dark:text-gold font-semibold hover:gap-3 transition-all">
                        Read Full Story
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>
            </article>
            
            <?php endwhile; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="flex justify-center items-center gap-2">
            <?php if ($page > 1): ?>
            <a href="news.php?category=<?php echo urlencode($category_filter); ?>&page=<?php echo $page - 1; ?>" 
               class="px-4 py-2 bg-white dark:bg-slate-800 rounded-lg hover:bg-primary hover:text-white transition-all">
                <span class="material-symbols-outlined">chevron_left</span>
            </a>
            <?php endif; ?>
            
            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $page): ?>
                <span class="px-4 py-2 bg-primary text-white rounded-lg font-semibold"><?php echo $i; ?></span>
                <?php else: ?>
                <a href="news.php?category=<?php echo urlencode($category_filter); ?>&page=<?php echo $i; ?>" 
                   class="px-4 py-2 bg-white dark:bg-slate-800 rounded-lg hover:bg-primary hover:text-white transition-all">
                    <?php echo $i; ?>
                </a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <a href="news.php?category=<?php echo urlencode($category_filter); ?>&page=<?php echo $page + 1; ?>" 
               class="px-4 py-2 bg-white dark:bg-slate-800 rounded-lg hover:bg-primary hover:text-white transition-all">
                <span class="material-symbols-outlined">chevron_right</span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        
        <!-- No Articles Found -->
        <div class="text-center py-16">
            <div class="inline-block p-6 bg-slate-200 dark:bg-slate-800 rounded-full mb-6">
                <span class="material-symbols-outlined text-6xl text-slate-400">article</span>
            </div>
            <h3 class="text-2xl font-bold text-primary dark:text-gold mb-4">No Articles Found</h3>
            <p class="text-slate-600 dark:text-slate-400 mb-6">
                <?php if ($category_filter != 'all'): ?>
                    No articles found in the "<?php echo htmlspecialchars($category_filter); ?>" category.
                <?php else: ?>
                    No articles available at the moment. Check back soon!
                <?php endif; ?>
            </p>
            <a href="news.php" class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-lg hover:bg-opacity-90 transition-all">
                View All News
            </a>
        </div>
        
        <?php endif; ?>

    </div>
</section>

<!-- Newsletter Subscription -->
<section class="py-16 bg-gradient-to-r from-primary to-slate-800 text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="inline-block p-4 bg-gold/10 rounded-full mb-6">
            <span class="material-symbols-outlined text-5xl text-gold">mail</span>
        </div>
        <h2 class="text-3xl lg:text-4xl font-display font-black mb-4">Stay in the Loop</h2>
        <p class="text-xl text-slate-300 mb-8">Subscribe to our newsletter and never miss an update from GOLA</p>
        
        <form class="flex flex-col sm:flex-row gap-4 max-w-2xl mx-auto">
            <input 
                type="email" 
                placeholder="Enter your email address" 
                required
                class="flex-1 px-6 py-4 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-gold"
            >
            <button 
                type="submit"
                class="bg-gold text-primary font-bold px-8 py-4 rounded-xl hover:scale-105 transition-transform shadow-lg flex items-center justify-center gap-2"
            >
                <span class="material-symbols-outlined">send</span>
                Subscribe
            </button>
        </form>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
