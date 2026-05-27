<?php 
$page_title = "About Us - Our Story & Mission";
require_once 'config/database.php';

// Fetch gallery images
$gallery_query = "SELECT * FROM gallery_images WHERE is_active = TRUE ORDER BY display_order ASC, created_at DESC";
$gallery_result = $conn->query($gallery_query);

// Get count by category for filter badges
$category_counts = [];
$count_query = "SELECT category, COUNT(*) as count FROM gallery_images WHERE is_active = TRUE GROUP BY category";
$count_result = $conn->query($count_query);
while ($row = $count_result->fetch_assoc()) {
    $category_counts[$row['category']] = $row['count'];
}

include 'includes/header.php'; 
?>

<!-- Page Header -->
<section class="py-16 bg-gradient-to-br from-primary to-slate-800 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="inline-flex items-center gap-2 px-4 py-2 bg-gold/20 border border-gold/30 rounded-full text-gold font-semibold text-sm uppercase tracking-widest mb-6">
            <span class="material-symbols-outlined">school</span>
            Our Story
        </div>
        <h1 class="text-4xl lg:text-6xl font-display font-black mb-4">About Goodness Omogo Leadership Academy</h1>
        <p class="text-xl text-slate-300 max-w-3xl mx-auto">Nurturing excellence in education since our founding, shaping tomorrow's leaders today</p>
    </div>
</section>

<!-- Our Story Section -->
<section class="py-20 bg-white dark:bg-background-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <div>
                <span class="text-gold text-sm font-semibold uppercase tracking-widest">Our Foundation</span>
                <h2 class="text-4xl lg:text-5xl font-display font-black text-primary dark:text-gold mt-4 mb-6">
                    A Legacy of Excellence
                </h2>
                <div class="space-y-4 text-slate-600 dark:text-slate-400 leading-relaxed">
                    <p>
                        Founded with a vision to revolutionize secondary education in Nigeria, Goodness Omogo Leadership Academy has grown from a bold dream into one of the nation's most respected educational institutions.
                    </p>
                    <p>
                        Our journey began with a simple yet powerful belief: that every child possesses unlimited potential, and with the right environment, guidance, and opportunities, they can achieve extraordinary things.
                    </p>
                    <p>
                        Today, we stand proud as a beacon of academic excellence, character development, and leadership training. Our state-of-the-art facilities, coupled with our experienced faculty and innovative curriculum, create an environment where students don't just learn—they thrive.
                    </p>
                    <p>
                        We have consistently maintained a 100% WAEC pass rate, with the majority of our graduates gaining admission to top universities both in Nigeria and internationally. But beyond academics, we measure our success by the character, resilience, and leadership qualities our students demonstrate.
                    </p>
                </div>
            </div>
            <div class="relative">
                <div class="bg-gradient-to-br from-gold/20 to-primary/20 rounded-3xl p-2">
                    <img src="./asset/courses-5.jpg" 
                         alt="Our Campus" 
                         class="w-full rounded-2xl shadow-2xl">
                </div>
                <div class="absolute -bottom-6 -left-6 bg-gold text-primary px-8 py-6 rounded-2xl shadow-xl">
                    <p class="text-3xl font-display font-bold">15+</p>
                    <p class="text-sm font-semibold uppercase">Years of Excellence</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Mission, Vision, Values -->
<section class="py-20 bg-slate-50 dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-display font-black text-primary dark:text-gold mb-4">Mission, Vision & Core Values</h2>
            <p class="text-slate-600 dark:text-slate-400 max-w-3xl mx-auto">The principles that guide everything we do</p>
        </div>
        
        <div class="grid md:grid-cols-3 gap-8">
            
            <!-- Mission -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-shadow">
                <div class="w-16 h-16 bg-blue-50 dark:bg-blue-900/20 rounded-xl flex items-center justify-center mb-6">
                    <span class="material-symbols-outlined text-4xl text-blue-600 dark:text-blue-400">flag</span>
                </div>
                <h3 class="text-2xl font-bold text-primary dark:text-gold mb-4">Our Mission</h3>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                    To provide a world-class education that develops the whole child—intellectually, morally, physically, and socially—preparing them to be ethical leaders who will make positive contributions to society.
                </p>
            </div>
            
            <!-- Vision -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-shadow">
                <div class="w-16 h-16 bg-gold/10 dark:bg-gold/20 rounded-xl flex items-center justify-center mb-6">
                    <span class="material-symbols-outlined text-4xl text-gold">visibility</span>
                </div>
                <h3 class="text-2xl font-bold text-primary dark:text-gold mb-4">Our Vision</h3>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                    To be the leading institution in Nigeria for nurturing future leaders who are academically excellent, morally upright, and globally competitive, contributing to the development of our nation and the world.
                </p>
            </div>
            
            <!-- Values -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-shadow">
                <div class="w-16 h-16 bg-green-50 dark:bg-green-900/20 rounded-xl flex items-center justify-center mb-6">
                    <span class="material-symbols-outlined text-4xl text-green-600 dark:text-green-400">favorite</span>
                </div>
                <h3 class="text-2xl font-bold text-primary dark:text-gold mb-4">Core Values</h3>
                <ul class="space-y-2 text-slate-600 dark:text-slate-400">
                    <li class="flex items-center gap-2">
                        <span class="w-2 h-2 bg-gold rounded-full"></span>
                        <span>Excellence</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="w-2 h-2 bg-gold rounded-full"></span>
                        <span>Integrity</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="w-2 h-2 bg-gold rounded-full"></span>
                        <span>Innovation</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="w-2 h-2 bg-gold rounded-full"></span>
                        <span>Leadership</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="w-2 h-2 bg-gold rounded-full"></span>
                        <span>Service</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="w-2 h-2 bg-gold rounded-full"></span>
                        <span>Community</span>
                    </li>
                </ul>
            </div>
            
        </div>
    </div>
</section>

<!-- What Makes Us Different -->
<section class="py-20 bg-white dark:bg-background-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <span class="text-gold text-sm font-semibold uppercase tracking-widest">Why Choose G.O.L.A</span>
            <h2 class="text-4xl font-display font-black text-primary dark:text-gold mt-4 mb-4">What Makes Us Different</h2>
            <p class="text-slate-600 dark:text-slate-400 max-w-3xl mx-auto">A unique combination of academic excellence and character development</p>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            
            <!-- Feature 1 -->
            <div class="group">
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-2xl p-8 hover:shadow-xl transition-all">
                    <span class="material-symbols-outlined text-5xl text-blue-600 dark:text-blue-400 mb-4 block">psychology</span>
                    <h3 class="text-xl font-bold text-primary dark:text-gold mb-3">Holistic Education</h3>
                    <p class="text-slate-600 dark:text-slate-400">
                        We develop the whole child—academically, emotionally, socially, and physically—preparing them for success in all areas of life.
                    </p>
                </div>
            </div>
            
            <!-- Feature 2 -->
            <div class="group">
                <div class="bg-gradient-to-br from-gold/10 to-gold/20 dark:from-gold/10 dark:to-gold/20 rounded-2xl p-8 hover:shadow-xl transition-all">
                    <span class="material-symbols-outlined text-5xl text-gold mb-4 block">person</span>
                    <h3 class="text-xl font-bold text-primary dark:text-gold mb-3">Small Class Sizes</h3>
                    <p class="text-slate-600 dark:text-slate-400">
                        With a 15:1 student-teacher ratio, every child receives personalized attention and tailored support for their unique learning journey.
                    </p>
                </div>
            </div>
            
            <!-- Feature 3 -->
            <div class="group">
                <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-2xl p-8 hover:shadow-xl transition-all">
                    <span class="material-symbols-outlined text-5xl text-green-600 dark:text-green-400 mb-4 block">science</span>
                    <h3 class="text-xl font-bold text-primary dark:text-gold mb-3">Modern Facilities</h3>
                    <p class="text-slate-600 dark:text-slate-400">
                        State-of-the-art laboratories, libraries, sports facilities, and technology infrastructure provide students with world-class learning environments.
                    </p>
                </div>
            </div>
            
            <!-- Feature 4 -->
            <div class="group">
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-2xl p-8 hover:shadow-xl transition-all">
                    <span class="material-symbols-outlined text-5xl text-purple-600 dark:text-purple-400 mb-4 block">workspace_premium</span>
                    <h3 class="text-xl font-bold text-primary dark:text-gold mb-3">Experienced Faculty</h3>
                    <p class="text-slate-600 dark:text-slate-400">
                        Our teachers are not just educators but mentors, role models, and lifelong learners committed to your child's success.
                    </p>
                </div>
            </div>
            
            <!-- Feature 5 -->
            <div class="group">
                <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 rounded-2xl p-8 hover:shadow-xl transition-all">
                    <span class="material-symbols-outlined text-5xl text-red-600 dark:text-red-400 mb-4 block">groups</span>
                    <h3 class="text-xl font-bold text-primary dark:text-gold mb-3">Leadership Development</h3>
                    <p class="text-slate-600 dark:text-slate-400">
                        Through our leadership programs, student government, and extracurricular activities, we cultivate confident, responsible leaders.
                    </p>
                </div>
            </div>
            
            <!-- Feature 6 -->
            <div class="group">
                <div class="bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20 rounded-2xl p-8 hover:shadow-xl transition-all">
                    <span class="material-symbols-outlined text-5xl text-amber-600 dark:text-amber-400 mb-4 block">public</span>
                    <h3 class="text-xl font-bold text-primary dark:text-gold mb-3">Global Perspective</h3>
                    <p class="text-slate-600 dark:text-slate-400">
                        We prepare students to be global citizens, equipped to compete and collaborate in an increasingly interconnected world.
                    </p>
                </div>
            </div>
            
        </div>
    </div>
</section>

<!-- Photo Gallery -->
<section class="py-20 bg-slate-50 dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <span class="text-gold text-sm font-semibold uppercase tracking-widest">Take a Tour</span>
            <h2 class="text-4xl font-display font-black text-primary dark:text-gold mt-4 mb-4">Experience Our Campus</h2>
            <p class="text-slate-600 dark:text-slate-400 max-w-3xl mx-auto">Explore our state-of-the-art facilities, vibrant campus life, and inspiring learning environments</p>
        </div>
        
        <!-- Gallery Filter Tabs -->
        <div class="flex flex-wrap justify-center gap-3 mb-12">
            <button class="gallery-filter active px-6 py-3 rounded-lg font-semibold transition-all bg-primary text-white" data-filter="all">
                All Photos
            </button>
            <button class="gallery-filter px-6 py-3 rounded-lg font-semibold transition-all bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700" data-filter="Campus">
                Campus <?php echo isset($category_counts['Campus']) ? '<span class="ml-1 text-xs opacity-70">('.$category_counts['Campus'].')</span>' : ''; ?>
            </button>
            <button class="gallery-filter px-6 py-3 rounded-lg font-semibold transition-all bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700" data-filter="Classrooms">
                Classrooms <?php echo isset($category_counts['Classrooms']) ? '<span class="ml-1 text-xs opacity-70">('.$category_counts['Classrooms'].')</span>' : ''; ?>
            </button>
            <button class="gallery-filter px-6 py-3 rounded-lg font-semibold transition-all bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700" data-filter="Facilities">
                Facilities <?php echo isset($category_counts['Facilities']) ? '<span class="ml-1 text-xs opacity-70">('.$category_counts['Facilities'].')</span>' : ''; ?>
            </button>
            <button class="gallery-filter px-6 py-3 rounded-lg font-semibold transition-all bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700" data-filter="Sports">
                Sports <?php echo isset($category_counts['Sports']) ? '<span class="ml-1 text-xs opacity-70">('.$category_counts['Sports'].')</span>' : ''; ?>
            </button>
            <button class="gallery-filter px-6 py-3 rounded-lg font-semibold transition-all bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700" data-filter="Events">
                Events <?php echo isset($category_counts['Events']) ? '<span class="ml-1 text-xs opacity-70">('.$category_counts['Events'].')</span>' : ''; ?>
            </button>
        </div>
        
        <!-- Gallery Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="gallery-grid">
            
            <?php if ($gallery_result && $gallery_result->num_rows > 0): ?>
                <?php while($image = $gallery_result->fetch_assoc()): ?>
                
                <div class="gallery-item group cursor-pointer" data-category="<?php echo htmlspecialchars($image['category']); ?>">
                    <div class="relative overflow-hidden rounded-2xl shadow-lg aspect-[4/3]">
                        <img src="assets/images/gallery/<?php echo htmlspecialchars($image['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($image['title']); ?>" 
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                             onerror="this.src='./asset/courses-5.jpg'">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <div class="absolute bottom-0 left-0 right-0 p-6">
                                <h3 class="text-white font-bold text-lg mb-1"><?php echo htmlspecialchars($image['title']); ?></h3>
                                <p class="text-slate-200 text-sm"><?php echo htmlspecialchars($image['description']); ?></p>
                            </div>
                            <div class="absolute top-4 right-4 bg-white/20 backdrop-blur-sm p-2 rounded-lg">
                                <span class="material-symbols-outlined text-white">zoom_in</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php endwhile; ?>
            <?php else: ?>
                
                <!-- No images message -->
                <div class="col-span-3 text-center py-16">
                    <span class="material-symbols-outlined text-6xl text-slate-300 mb-4 block">photo_library</span>
                    <p class="text-slate-500 mb-4">No gallery images available yet</p>
                    <p class="text-sm text-slate-400">Check back soon for photos of our beautiful campus!</p>
                </div>
                
            <?php endif; ?>
        
        <!-- View More Button -->
        
        
    </div>
</section>

<!-- Lightbox Modal -->
<div id="lightbox" class="fixed inset-0 bg-black/95 z-50 hidden items-center justify-center p-4">
    <button id="close-lightbox" class="absolute top-4 right-4 text-white hover:text-gold transition-colors">
        <span class="material-symbols-outlined text-4xl">close</span>
    </button>
    <button id="prev-image" class="absolute left-4 top-1/2 -translate-y-1/2 text-white hover:text-gold transition-colors">
        <span class="material-symbols-outlined text-5xl">chevron_left</span>
    </button>
    <button id="next-image" class="absolute right-4 top-1/2 -translate-y-1/2 text-white hover:text-gold transition-colors">
        <span class="material-symbols-outlined text-5xl">chevron_right</span>
    </button>
    <div class="max-w-6xl w-full">
        <img id="lightbox-image" src="" alt="" class="w-full h-auto rounded-lg shadow-2xl">
        <div class="text-center mt-6">
            <h3 id="lightbox-title" class="text-2xl font-bold text-white mb-2"></h3>
            <p id="lightbox-description" class="text-slate-300"></p>
        </div>
    </div>
</div>

<script>
// Gallery Filter
const filterButtons = document.querySelectorAll('.gallery-filter');
const galleryItems = document.querySelectorAll('.gallery-item');

filterButtons.forEach(button => {
    button.addEventListener('click', () => {
        const filter = button.dataset.filter;
        
        // Update active button
        filterButtons.forEach(btn => {
            btn.classList.remove('active', 'bg-primary', 'text-white');
            btn.classList.add('bg-white', 'dark:bg-slate-800', 'text-slate-700', 'dark:text-slate-300');
        });
        button.classList.add('active', 'bg-primary', 'text-white');
        button.classList.remove('bg-white', 'dark:bg-slate-800', 'text-slate-700', 'dark:text-slate-300');
        
        // Filter items
        galleryItems.forEach(item => {
            if (filter === 'all' || item.dataset.category === filter) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
});

// Lightbox functionality
const lightbox = document.getElementById('lightbox');
const lightboxImage = document.getElementById('lightbox-image');
const lightboxTitle = document.getElementById('lightbox-title');
const lightboxDescription = document.getElementById('lightbox-description');
const closeLightbox = document.getElementById('close-lightbox');
let currentImageIndex = 0;
let visibleImages = [];

function updateVisibleImages() {
    visibleImages = Array.from(galleryItems).filter(item => item.style.display !== 'none');
}

galleryItems.forEach((item, index) => {
    item.addEventListener('click', () => {
        updateVisibleImages();
        currentImageIndex = visibleImages.indexOf(item);
        showImage(currentImageIndex);
        lightbox.classList.remove('hidden');
        lightbox.classList.add('flex');
    });
});

function showImage(index) {
    const item = visibleImages[index];
    const img = item.querySelector('img');
    const title = item.querySelector('h3').textContent;
    const description = item.querySelector('p').textContent;
    
    lightboxImage.src = img.src;
    lightboxImage.alt = img.alt;
    lightboxTitle.textContent = title;
    lightboxDescription.textContent = description;
}

closeLightbox.addEventListener('click', () => {
    lightbox.classList.add('hidden');
    lightbox.classList.remove('flex');
});

document.getElementById('prev-image').addEventListener('click', () => {
    currentImageIndex = (currentImageIndex - 1 + visibleImages.length) % visibleImages.length;
    showImage(currentImageIndex);
});

document.getElementById('next-image').addEventListener('click', () => {
    currentImageIndex = (currentImageIndex + 1) % visibleImages.length;
    showImage(currentImageIndex);
});

// Close on escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !lightbox.classList.contains('hidden')) {
        lightbox.classList.add('hidden');
        lightbox.classList.remove('flex');
    }
    if (e.key === 'ArrowLeft' && !lightbox.classList.contains('hidden')) {
        currentImageIndex = (currentImageIndex - 1 + visibleImages.length) % visibleImages.length;
        showImage(currentImageIndex);
    }
    if (e.key === 'ArrowRight' && !lightbox.classList.contains('hidden')) {
        currentImageIndex = (currentImageIndex + 1) % visibleImages.length;
        showImage(currentImageIndex);
    }
});

// Close on background click
lightbox.addEventListener('click', (e) => {
    if (e.target === lightbox) {
        lightbox.classList.add('hidden');
        lightbox.classList.remove('flex');
    }
});
</script>

<!-- Leadership Team -->
<section class="py-20 bg-slate-50 dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-display font-black text-primary dark:text-gold mb-4">Our Leadership Team</h2>
            <p class="text-slate-600 dark:text-slate-400 max-w-3xl mx-auto">Experienced educators committed to your child's success</p>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            
            <!-- Principal -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all group">
                <div class="h-64 bg-gradient-to-br from-primary to-slate-700 overflow-hidden">
                    <img src="./asset/courses-5.jpg" 
                         alt="Principal" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                </div>
                <div class="p-6 text-center">
                    <h3 class="text-xl font-bold text-primary dark:text-gold mb-1">Prof. Alewale Omogo</h3>
                    <p class="text-sm text-gold mb-3">Principal & Founder</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        Ph.D. in Educational Leadership
                    </p>
                </div>
            </div>
            
            <!-- Vice Principal -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all group">
                <div class="h-64 bg-gradient-to-br from-gold to-amber-700"></div>
                <div class="p-6 text-center">
                    <h3 class="text-xl font-bold text-primary dark:text-gold mb-1">Dr. Ngozi Adebayo</h3>
                    <p class="text-sm text-gold mb-3">Vice Principal (Academics)</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        Ed.D. in Curriculum Development
                    </p>
                </div>
            </div>
            
            <!-- Vice Principal Admin -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all group">
                <div class="h-64 bg-gradient-to-br from-blue-600 to-blue-800"></div>
                <div class="p-6 text-center">
                    <h3 class="text-xl font-bold text-primary dark:text-gold mb-1">Mr. Chukwuma Okafor</h3>
                    <p class="text-sm text-gold mb-3">Vice Principal (Administration)</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        M.Ed. in Educational Management
                    </p>
                </div>
            </div>
            
            <!-- Dean of Students -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all group">
                <div class="h-64 bg-gradient-to-br from-green-600 to-green-800"></div>
                <div class="p-6 text-center">
                    <h3 class="text-xl font-bold text-primary dark:text-gold mb-1">Mrs. Funmilayo Adeleke</h3>
                    <p class="text-sm text-gold mb-3">Dean of Students</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        M.A. in Counseling Psychology
                    </p>
                </div>
            </div>
            
        </div>
    </div>
</section>

<!-- Accreditation & Partnerships -->
<section class="py-20 bg-white dark:bg-background-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-display font-black text-primary dark:text-gold mb-4">Accreditation & Partnerships</h2>
            <p class="text-slate-600 dark:text-slate-400">Recognized by leading educational bodies</p>
        </div>
        
        <div class="grid md:grid-cols-3 gap-8">
            <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-8 text-center">
                <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-4xl text-primary dark:text-gold">verified</span>
                </div>
                <h3 class="font-bold text-primary dark:text-gold mb-2">Federal Ministry of Education</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">Fully accredited secondary institution</p>
            </div>
            
            <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-8 text-center">
                <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-4xl text-primary dark:text-gold">school</span>
                </div>
                <h3 class="font-bold text-primary dark:text-gold mb-2">WAEC Examination Center</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">Approved examination center</p>
            </div>
            
            <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-8 text-center">
                <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-4xl text-primary dark:text-gold">military_tech</span>
                </div>
                <h3 class="font-bold text-primary dark:text-gold mb-2">Cambridge International</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">Partner for IGCSE programs</p>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-20 bg-gradient-to-r from-primary to-slate-800 text-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-4xl lg:text-5xl font-display font-black mb-6">Join Our Community of Leaders</h2>
        <p class="text-xl text-slate-300 mb-10 max-w-3xl mx-auto">
            Discover how Goodness Omogo Leadership Academy can help your child reach their full potential.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="admissions.php" class="bg-gold text-primary font-bold px-10 py-4 rounded-xl hover:scale-105 transition-transform shadow-lg flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">description</span>
                Apply for Admission
            </a>
            <a href="contact.php" class="bg-white/10 hover:bg-white/20 backdrop-blur-sm border border-white/20 font-bold px-10 py-4 rounded-xl transition-all flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">calendar_month</span>
                Schedule a Visit
            </a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
