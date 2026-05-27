<?php 
require_once 'config/database.php';

// Get article slug
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if (empty($slug)) {
    header('Location: news.php');
    exit;
}

// Fetch article
$stmt = $conn->prepare("SELECT * FROM news_articles WHERE slug = ? AND is_published = TRUE");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();
$article = $result->fetch_assoc();

if (!$article) {
    header('Location: news.php');
    exit;
}

// Update view count
$update_views = $conn->prepare("UPDATE news_articles SET views = views + 1 WHERE id = ?");
$update_views->bind_param("i", $article['id']);
$update_views->execute();

// Get related articles (same category, exclude current)
$related_stmt = $conn->prepare("
    SELECT * FROM news_articles 
    WHERE category = ? AND id != ? AND is_published = TRUE 
    ORDER BY published_date DESC 
    LIMIT 3
");
$related_stmt->bind_param("si", $article['category'], $article['id']);
$related_stmt->execute();
$related_articles = $related_stmt->get_result();

$page_title = $article['title'];
include 'includes/header.php'; 

// Gradient based on category
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

<!-- Article Header -->
<section class="py-12 bg-gradient-to-br <?php echo $gradient; ?> text-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm mb-6 text-white/80">
            <a href="index.php" class="hover:text-white transition-colors">Home</a>
            <span class="material-symbols-outlined text-sm">chevron_right</span>
            <a href="news.php" class="hover:text-white transition-colors">News</a>
            <span class="material-symbols-outlined text-sm">chevron_right</span>
            <a href="news.php?category=<?php echo urlencode($article['category']); ?>" class="hover:text-white transition-colors">
                <?php echo htmlspecialchars($article['category']); ?>
            </a>
        </div>
        
        <!-- Category Badge -->
        <div class="mb-4">
            <span class="bg-white/90 backdrop-blur-sm text-primary px-4 py-2 rounded-full text-sm font-semibold uppercase">
                <?php echo htmlspecialchars($article['category']); ?>
            </span>
        </div>
        
        <!-- Title -->
        <h1 class="text-3xl lg:text-5xl font-display font-black mb-6 leading-tight">
            <?php echo htmlspecialchars($article['title']); ?>
        </h1>
        
        <!-- Meta Info -->
        <div class="flex flex-wrap items-center gap-6 text-sm text-white/90">
            <span class="flex items-center gap-2">
                <span class="material-symbols-outlined">calendar_today</span>
                <?php echo date('F j, Y', strtotime($article['published_date'])); ?>
            </span>
            <span class="flex items-center gap-2">
                <span class="material-symbols-outlined">person</span>
                <?php echo htmlspecialchars($article['author']); ?>
            </span>
            <span class="flex items-center gap-2">
                <span class="material-symbols-outlined">visibility</span>
                <?php echo number_format($article['views']); ?> views
            </span>
        </div>
    </div>
</section>

<!-- Article Content -->
<section class="py-16 bg-white dark:bg-slate-900">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-3 gap-12">
            
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- Featured Image -->
                <?php if (!empty($article['featured_image'])): ?>
                <div class="mb-8 rounded-2xl overflow-hidden shadow-xl">
                    <img src="assets/images/news/<?php echo htmlspecialchars($article['featured_image']); ?>" 
                         alt="<?php echo htmlspecialchars($article['title']); ?>"
                         class="w-full h-auto">
                </div>
                <?php endif; ?>
                
                <!-- Article Body -->
                <div class="prose prose-lg max-w-none dark:prose-invert prose-headings:text-primary dark:prose-headings:text-gold prose-a:text-gold prose-strong:text-primary dark:prose-strong:text-gold">
                    <?php echo $article['content']; ?>
                </div>
                
                <!-- Tags (if any) -->
                <div class="mt-12 pt-8 border-t border-slate-200 dark:border-slate-700">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-slate-600 dark:text-slate-400 font-semibold">Tags:</span>
                        <a href="news.php?category=<?php echo urlencode($article['category']); ?>" 
                           class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-primary hover:text-white dark:hover:bg-gold dark:hover:text-primary rounded-full text-sm font-medium transition-all">
                            <?php echo htmlspecialchars($article['category']); ?>
                        </a>
                    </div>
                </div>
                
                <!-- Share Buttons -->
                <div class="mt-8 p-6 bg-slate-50 dark:bg-slate-800 rounded-2xl">
                    <h3 class="text-lg font-bold text-primary dark:text-gold mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined">share</span>
                        Share This Article
                    </h3>
                    <div class="flex flex-wrap gap-3">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                           target="_blank"
                           class="flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            Facebook
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($article['title']); ?>" 
                           target="_blank"
                           class="flex items-center gap-2 px-6 py-3 bg-sky-500 text-white rounded-lg hover:bg-sky-600 transition-all">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                            Twitter
                        </a>
                        <a href="https://wa.me/?text=<?php echo urlencode($article['title'] . ' - ' . 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                           target="_blank"
                           class="flex items-center gap-2 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                            WhatsApp
                        </a>
                    </div>
                </div>
                
                <!-- Navigation to Previous/Next -->
                <div class="mt-12 pt-8 border-t border-slate-200 dark:border-slate-700 flex justify-between items-center">
                    <a href="news.php" class="inline-flex items-center gap-2 text-primary dark:text-gold font-semibold hover:gap-3 transition-all">
                        <span class="material-symbols-outlined">arrow_back</span>
                        Back to All News
                    </a>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="lg:col-span-1">
                
                <!-- Related Articles -->
                <?php if ($related_articles->num_rows > 0): ?>
                <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-6 mb-8">
                    <h3 class="text-xl font-bold text-primary dark:text-gold mb-6 flex items-center gap-2">
                        <span class="material-symbols-outlined">article</span>
                        Related Articles
                    </h3>
                    
                    <div class="space-y-6">
                        <?php while($related = $related_articles->fetch_assoc()): ?>
                        <a href="news-detail.php?slug=<?php echo urlencode($related['slug']); ?>" 
                           class="block group">
                            <div class="flex gap-4">
                                <div class="w-20 h-20 flex-shrink-0 rounded-lg overflow-hidden bg-gradient-to-br <?php echo $gradients[$related['category']] ?? 'from-primary to-slate-700'; ?>">
                                    <?php if (!empty($related['featured_image'])): ?>
                                    <img src="assets/images/news/<?php echo htmlspecialchars($related['featured_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($related['title']); ?>"
                                         class="w-full h-full object-cover">
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">
                                        <?php echo date('M d, Y', strtotime($related['published_date'])); ?>
                                    </p>
                                    <h4 class="font-bold text-sm text-primary dark:text-gold group-hover:text-gold dark:group-hover:text-white line-clamp-2 transition-colors">
                                        <?php echo htmlspecialchars($related['title']); ?>
                                    </h4>
                                </div>
                            </div>
                        </a>
                        <?php endwhile; ?>
                    </div>
                    
                    <a href="news.php?category=<?php echo urlencode($article['category']); ?>" 
                       class="mt-6 inline-flex items-center gap-2 text-primary dark:text-gold font-semibold hover:gap-3 transition-all">
                        View More in <?php echo htmlspecialchars($article['category']); ?>
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>
                <?php endif; ?>
                
                <!-- Categories -->
                <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-6 mb-8">
                    <h3 class="text-xl font-bold text-primary dark:text-gold mb-6 flex items-center gap-2">
                        <span class="material-symbols-outlined">category</span>
                        Categories
                    </h3>
                    
                    <div class="space-y-2">
                        <?php
                        $categories_query = "SELECT category, COUNT(*) as count FROM news_articles WHERE is_published = TRUE GROUP BY category ORDER BY category";
                        $categories = $conn->query($categories_query);
                        while($cat = $categories->fetch_assoc()):
                        ?>
                        <a href="news.php?category=<?php echo urlencode($cat['category']); ?>" 
                           class="flex items-center justify-between p-3 rounded-lg hover:bg-white dark:hover:bg-slate-700 transition-all group">
                            <span class="font-semibold text-slate-700 dark:text-slate-300 group-hover:text-primary dark:group-hover:text-gold">
                                <?php echo htmlspecialchars($cat['category']); ?>
                            </span>
                            <span class="text-sm text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-700 px-2 py-1 rounded-full">
                                <?php echo $cat['count']; ?>
                            </span>
                        </a>
                        <?php endwhile; ?>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="bg-gradient-to-br from-primary to-slate-800 text-white rounded-2xl p-6">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined">link</span>
                        Quick Links
                    </h3>
                    
                    <div class="space-y-3">
                        <a href="result-checker/" class="flex items-center gap-3 p-3 bg-white/10 hover:bg-white/20 rounded-lg transition-all">
                            <span class="material-symbols-outlined">school</span>
                            <span>Check Results</span>
                        </a>
                        <a href="admissions.php" class="flex items-center gap-3 p-3 bg-white/10 hover:bg-white/20 rounded-lg transition-all">
                            <span class="material-symbols-outlined">description</span>
                            <span>Admissions</span>
                        </a>
                        <a href="academics.php" class="flex items-center gap-3 p-3 bg-white/10 hover:bg-white/20 rounded-lg transition-all">
                            <span class="material-symbols-outlined">menu_book</span>
                            <span>Academics</span>
                        </a>
                        <a href="contact.php" class="flex items-center gap-3 p-3 bg-white/10 hover:bg-white/20 rounded-lg transition-all">
                            <span class="material-symbols-outlined">contact_mail</span>
                            <span>Contact Us</span>
                        </a>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
